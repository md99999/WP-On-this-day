<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches, caches, and selects "on this day" events and births from the
 * Wikipedia "On this day" REST API.
 */
class OnThisDay_Events_API {

	const API_URL = 'https://en.wikipedia.org/api/rest_v1/feed/onthisday/%s/%02d/%02d';

	/**
	 * Get a randomly-selected, year-ascending list of events for today.
	 *
	 * @param int $count Number of events to return.
	 * @return array[] List of ['year' => int, 'text' => string, 'url' => string].
	 */
	public static function get_events( $count ) {
		return self::get_selection( 'events', $count );
	}

	/**
	 * Get a randomly-selected, year-ascending list of people born today.
	 *
	 * @param int $count Number of people to return.
	 * @return array[] List of ['year' => int, 'text' => string, 'url' => string].
	 */
	public static function get_births( $count ) {
		return self::get_selection( 'births', $count );
	}

	/**
	 * Pick $count random items from today's pool for a feed, sorted by year ascending.
	 *
	 * The selection is cached per feed, day and requested count so every visitor
	 * sees the same list until the cache rolls over at midnight.
	 *
	 * @param string $type  Feed name: 'events' or 'births'.
	 * @param int    $count Number of items to return.
	 * @return array[]
	 */
	private static function get_selection( $type, $count ) {
		$count = max( 1, min( 100, (int) $count ) );

		$now   = current_time( 'timestamp' );
		$month = (int) date( 'n', $now );
		$day   = (int) date( 'j', $now );

		$cache_key = 'onthisday_' . $type . '_' . $month . '_' . $day . '_' . $count;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$pool = self::fetch_pool( $type, $month, $day );

		if ( empty( $pool ) ) {
			return array();
		}

		$sample_size = min( $count, count( $pool ) );
		$keys        = array_rand( $pool, $sample_size );
		$keys        = is_array( $keys ) ? $keys : array( $keys );

		$selected = array();
		foreach ( $keys as $key ) {
			$selected[] = $pool[ $key ];
		}

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
	 * Fetch the full pool of items for a feed on a given month/day from Wikipedia.
	 *
	 * @param string $type  Feed name: 'events' or 'births'; also the key of the list in the response.
	 * @param int    $month 1-12.
	 * @param int    $day   1-31.
	 * @return array[] List of ['year' => int, 'text' => string, 'url' => string].
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

			$page_url = '';
			if ( ! empty( $item['pages'][0]['content_urls']['desktop']['page'] ) ) {
				$page_url = $item['pages'][0]['content_urls']['desktop']['page'];
			}

			$pool[] = array(
				'year' => (int) $item['year'],
				'text' => wp_strip_all_tags( $item['text'] ),
				'url'  => $page_url,
			);
		}

		return $pool;
	}
}
