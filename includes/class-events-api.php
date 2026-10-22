<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches, caches, and selects "on this day" events and births from the
 * Wikipedia "On this day" REST API, optionally favouring faith-related entries.
 */
class OnThisDay_Events_API {

	const API_URL = 'https://en.wikipedia.org/api/rest_v1/feed/onthisday/%s/%02d/%02d';

	/** Selection modes: a mix with about a third faith entries, faith entries first, or faith entries only. */
	const MODES = array( 'mix', 'faith', 'only' );

	const DEFAULT_FAITH_KEYWORDS = 'church, pope, priest, theologian, bishop, archbishop, cardinal, pastor, lutheran, protestant, catholic, missionary, nun, abbot, evangelist';

	/**
	 * Get a randomly-selected, year-ascending list of events for today.
	 *
	 * @param int    $count Number of events to return.
	 * @param string $mode  One of self::MODES.
	 * @return array[]|null List of ['year' => int, 'text' => string, 'url' => string], or null if the feed could not be loaded.
	 */
	public static function get_events( $count, $mode = 'mix' ) {
		return self::get_selection( 'events', $count, $mode );
	}

	/**
	 * Get a randomly-selected, year-ascending list of people born today.
	 *
	 * @param int    $count Number of people to return.
	 * @param string $mode  One of self::MODES.
	 * @return array[]|null Same shape as get_events().
	 */
	public static function get_births( $count, $mode = 'mix' ) {
		return self::get_selection( 'births', $count, $mode );
	}

	/** The faith keywords from Settings, or the defaults when none are saved. */
	public static function faith_keywords() {
		$keywords = self::parse_keywords( get_option( 'onthisday_faith_keywords', '' ) );
		return $keywords ? $keywords : self::parse_keywords( self::DEFAULT_FAITH_KEYWORDS );
	}

	/**
	 * Split a comma- or line-separated keyword list into unique lowercase words.
	 *
	 * @param string $raw Keyword list.
	 * @return string[]
	 */
	public static function parse_keywords( $raw ) {
		$words = array();
		foreach ( preg_split( '/[,\r\n]+/', (string) $raw ) as $word ) {
			$word = strtolower( trim( $word ) );
			if ( '' !== $word ) {
				$words[ $word ] = true;
			}
		}
		return array_keys( $words );
	}

	/**
	 * Pick $count items from today's pool for a feed, sorted by year ascending.
	 *
	 * The selection is cached per feed, mode, day, count and keyword list, so every
	 * visitor sees the same list until the cache rolls over at midnight.
	 *
	 * @param string $type  Feed name: 'events' or 'births'.
	 * @param int    $count Number of items to return.
	 * @param string $mode  One of self::MODES.
	 * @return array[]|null Null if the feed could not be loaded; an empty array if nothing qualified.
	 */
	private static function get_selection( $type, $count, $mode ) {
		$count    = max( 1, min( 100, (int) $count ) );
		$mode     = in_array( $mode, self::MODES, true ) ? $mode : 'mix';
		$keywords = self::faith_keywords();

		$now   = current_time( 'timestamp' );
		$month = (int) date( 'n', $now );
		$day   = (int) date( 'j', $now );

		$cache_key = 'onthisday_' . $type . '_' . $mode . '_' . $month . '_' . $day . '_' . $count . '_' . substr( md5( implode( ',', $keywords ) ), 0, 8 );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$pool = self::fetch_pool( $type, $month, $day );

		if ( empty( $pool ) ) {
			return null;
		}

		$pattern = self::faith_pattern( $keywords );
		$faith   = array();
		$other   = array();
		foreach ( $pool as $item ) {
			if ( $pattern && preg_match( $pattern, $item['text'] . ' ' . $item['about'] ) ) {
				$faith[] = $item;
			} else {
				$other[] = $item;
			}
		}
		shuffle( $faith );

		if ( 'only' === $mode ) {
			$selected = array_slice( $faith, 0, $count );
		} elseif ( 'faith' === $mode ) {
			$selected = array_slice( $faith, 0, $count );
			$selected = array_merge( $selected, self::pick( $other, $count - count( $selected ) ) );
		} else {
			$quota    = (int) round( $count / 3 );
			$selected = array_slice( $faith, 0, $quota );
			$leftover = array_merge( array_slice( $faith, $quota ), $other );
			$selected = array_merge( $selected, self::pick( $leftover, $count - count( $selected ) ) );
		}

		$selected = array_map(
			function ( $item ) {
				return array(
					'year' => $item['year'],
					'text' => $item['text'],
					'url'  => $item['url'],
				);
			},
			$selected
		);

		usort(
			$selected,
			function ( $a, $b ) {
				return $a['year'] <=> $b['year'];
			}
		);

		// Cache until local midnight so the list stays stable for the rest of the day.
		$seconds_until_midnight = strtotime( 'tomorrow', $now ) - $now;
		set_transient( $cache_key, $selected, max( $seconds_until_midnight, HOUR_IN_SECONDS ) );

		return $selected;
	}

	/**
	 * Up to $n random items from a list.
	 *
	 * @param array $items Items to choose from.
	 * @param int   $n     How many to take.
	 * @return array
	 */
	private static function pick( $items, $n ) {
		if ( $n <= 0 || ! $items ) {
			return array();
		}
		shuffle( $items );
		return array_slice( $items, 0, $n );
	}

	/**
	 * A case-insensitive, whole-word regex matching any keyword, or '' if there are none.
	 *
	 * @param string[] $keywords Keywords.
	 * @return string
	 */
	private static function faith_pattern( $keywords ) {
		if ( ! $keywords ) {
			return '';
		}
		$quoted = array_map(
			function ( $word ) {
				return preg_quote( $word, '/' );
			},
			$keywords
		);
		return '/\b(?:' . implode( '|', $quoted ) . ')\b/iu';
	}

	/**
	 * Fetch the full pool of items for a feed on a given month/day from Wikipedia.
	 *
	 * @param string $type  Feed name: 'events' or 'births'; also the key of the list in the response.
	 * @param int    $month 1-12.
	 * @param int    $day   1-31.
	 * @return array[] List of ['year' => int, 'text' => string, 'url' => string, 'about' => string],
	 *                 where 'about' holds the linked articles' short descriptions, used for faith matching.
	 */
	private static function fetch_pool( $type, $month, $day ) {
		$url = sprintf( self::API_URL, $type, $month, $day );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'User-Agent' => 'WordPress/OnThisDayPlugin (' . home_url() . ')',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$items = isset( $body[ $type ] ) && is_array( $body[ $type ] ) ? $body[ $type ] : array();

		$pool = array();

		foreach ( $items as $item ) {
			if ( ! isset( $item['year'], $item['text'] ) ) {
				continue;
			}

			$page_url     = '';
			$descriptions = array();
			if ( ! empty( $item['pages'] ) && is_array( $item['pages'] ) ) {
				if ( ! empty( $item['pages'][0]['content_urls']['desktop']['page'] ) ) {
					$page_url = $item['pages'][0]['content_urls']['desktop']['page'];
				}
				foreach ( $item['pages'] as $page ) {
					if ( ! empty( $page['description'] ) && is_string( $page['description'] ) ) {
						$descriptions[] = $page['description'];
					}
				}
			}

			$pool[] = array(
				'year'  => (int) $item['year'],
				'text'  => wp_strip_all_tags( $item['text'] ),
				'url'   => $page_url,
				'about' => wp_strip_all_tags( implode( ' | ', $descriptions ) ),
			);
		}

		return $pool;
	}
}
