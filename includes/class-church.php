<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Today in the Church": the day's celebrations from the Church Calendar API (Roman Catholic,
 * General Roman Calendar), plus Christian feast days from Wikipedia's "On this day" holidays list.
 */
class OnThisDay_Church {

	const CALENDAR_URL  = 'http://calapi.inadiutorium.cz/api/v0/en/calendars/general-en/%d/%d/%d';
	const CALENDAR_HOME = 'http://calapi.inadiutorium.cz/';
	const HOLIDAYS_URL  = 'https://en.wikipedia.org/api/rest_v1/feed/onthisday/holidays/%02d/%02d';

	/** How long to remember that a source failed, so a down server does not slow every page load. */
	const RETRY_AFTER = HOUR_IN_SECONDS;

	const COLOURS = array( 'green', 'violet', 'white', 'red', 'rose', 'black' );

	/** Ranks worth naming next to a celebration; the others are self-explanatory or jargon. */
	const SHOWN_RANKS = array( 'solemnity', 'feast', 'memorial', 'optional memorial', 'commemoration' );

	/**
	 * Today's church calendar section data.
	 *
	 * @param int $max_feasts Most Wikipedia feast days to list.
	 * @return array|null ['day' => array|null, 'feasts' => array|null], or null if both sources failed.
	 */
	public static function get_today( $max_feasts ) {
		$now  = current_time( 'timestamp' );
		$day  = self::get_calendar_day( (int) date( 'Y', $now ), (int) date( 'n', $now ), (int) date( 'j', $now ), $now );
		$skip = $day ? implode( ' ', wp_list_pluck( $day['celebrations'], 'title' ) ) : '';

		$feasts = self::get_feasts( (int) date( 'n', $now ), (int) date( 'j', $now ), max( 1, min( 5, (int) $max_feasts ) ), $skip, $now );

		if ( null === $day && null === $feasts ) {
			return null;
		}
		return array(
			'day'    => $day,
			'feasts' => $feasts,
		);
	}

	/**
	 * The day's celebrations from the Church Calendar API, cached until midnight.
	 *
	 * @return array|null ['season' => string, 'celebrations' => [['title', 'rank', 'colour']]], or null if unavailable.
	 */
	private static function get_calendar_day( $year, $month, $day, $now ) {
		$key    = 'onthisday_church_cal_' . $year . '_' . $month . '_' . $day;
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null;
		}

		$data = self::fetch_json( sprintf( self::CALENDAR_URL, $year, $month, $day ), 5 );
		if ( ! $data || empty( $data['celebrations'] ) || ! is_array( $data['celebrations'] ) ) {
			set_transient( $key, 'unavailable', self::RETRY_AFTER );
			return null;
		}

		$season       = '';
		$celebrations = array();
		foreach ( $data['celebrations'] as $c ) {
			$title  = isset( $c['title'] ) && is_string( $c['title'] ) ? trim( wp_strip_all_tags( $c['title'] ) ) : '';
			$rank   = isset( $c['rank'] ) && is_string( $c['rank'] ) ? strtolower( trim( $c['rank'] ) ) : '';
			$colour = isset( $c['colour'] ) && in_array( $c['colour'], self::COLOURS, true ) ? $c['colour'] : '';
			if ( '' === $title ) {
				continue;
			}
			// The plain weekday ("Thursday, 29th week in Ordinary Time") is shown as the season line.
			if ( 'ferial' === $rank ) {
				$season = $title;
				continue;
			}
			$celebrations[] = array(
				'title'  => $title,
				'rank'   => in_array( $rank, self::SHOWN_RANKS, true ) ? $rank : '',
				'colour' => $colour,
			);
		}

		if ( ! $celebrations && '' === $season ) {
			set_transient( $key, 'unavailable', self::RETRY_AFTER );
			return null;
		}

		$result = array(
			'season'       => $season,
			'celebrations' => $celebrations,
		);
		set_transient( $key, $result, self::seconds_until_midnight( $now ) );
		return $result;
	}

	/**
	 * Up to $max Christian feast days for the date from Wikipedia's holidays list, cached until midnight.
	 *
	 * @param string $skip Celebration titles already shown, so the same saint is not listed twice.
	 * @return array|null List of ['name' => string, 'url' => string], or null if unavailable.
	 */
	private static function get_feasts( $month, $day, $max, $skip, $now ) {
		$key    = 'onthisday_church_feasts_' . $month . '_' . $day . '_' . $max . '_' . substr( md5( $skip ), 0, 8 );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : null;
		}

		$data = self::fetch_json( sprintf( self::HOLIDAYS_URL, $month, $day ), 10 );
		if ( ! $data || ! isset( $data['holidays'] ) || ! is_array( $data['holidays'] ) ) {
			set_transient( $key, 'unavailable', self::RETRY_AFTER );
			return null;
		}

		$skip_words = array_flip( self::name_words( $skip ) );
		$found      = array();
		foreach ( $data['holidays'] as $item ) {
			$feast = self::parse_feast( $item );
			if ( ! $feast || isset( $found[ strtolower( $feast['name'] ) ] ) ) {
				continue;
			}
			// "Pope John Paul II" (Wikipedia) and "Saint John Paul II, pope" (calendar) are the same person:
			// compare the first two distinctive words of the name rather than the wording.
			$probe = array_slice( self::name_words( $feast['name'] ), 0, 2 );
			if ( $probe && count( array_intersect_key( array_flip( $probe ), $skip_words ) ) === count( $probe ) ) {
				continue;
			}
			$found[ strtolower( $feast['name'] ) ] = $feast;
		}

		$feasts = array_values( $found );
		if ( count( $feasts ) > $max ) {
			$keys   = array_rand( $feasts, $max );
			$feasts = array_values( array_intersect_key( $feasts, array_flip( (array) $keys ) ) );
		}

		set_transient( $key, $feasts, self::seconds_until_midnight( $now ) );
		return $feasts;
	}

	/**
	 * Turn one Wikipedia holidays entry into a feast, or null if it is not a Christian observance.
	 *
	 * Entries look like "Christian feast day:\nAlphonsus Rodriguez"; the labels vary in wording and case.
	 */
	private static function parse_feast( $item ) {
		if ( empty( $item['text'] ) || ! is_string( $item['text'] ) ) {
			return null;
		}
		$text  = wp_strip_all_tags( $item['text'] );
		$parts = explode( "\n", $text );
		$label = '';
		if ( count( $parts ) > 1 && ':' === substr( rtrim( $parts[0] ), -1 ) ) {
			$label = array_shift( $parts );
		}
		$name = trim( $parts[0] );

		$about = '';
		$url   = '';
		if ( ! empty( $item['pages'] ) && is_array( $item['pages'] ) ) {
			foreach ( $item['pages'] as $page ) {
				$about .= ' ' . ( isset( $page['description'] ) && is_string( $page['description'] ) ? $page['description'] : '' );
			}
			if ( ! empty( $item['pages'][0]['content_urls']['desktop']['page'] ) ) {
				$url = $item['pages'][0]['content_urls']['desktop']['page'];
			}
		}

		$christian = preg_match( '/christian/i', $label ) || ( '' === $label && preg_match( '/\b(christian|feast day)\b/i', $text . ' ' . $about ) );
		// Skip notes about movable feasts ("Earliest day on which ...") and bare links to a calendar day ("October 22 (Eastern Orthodox liturgics)").
		if ( ! $christian || '' === $name || preg_match( '/^(earliest|latest) day|^(january|february|march|april|may|june|july|august|september|october|november|december) \d/i', $name ) ) {
			return null;
		}

		// Long notes in brackets ("if this date falls on Sunday, ...") are trimmed; short ones like "(Anglican Communion)" stay.
		if ( strlen( $name ) > 70 && false !== strpos( $name, '(' ) ) {
			$name = trim( substr( $name, 0, strpos( $name, '(' ) ) );
		}
		return array(
			'name' => $name,
			'url'  => $url,
		);
	}

	/** The distinctive words of a saint's name or title: lowercase, without titles and filler words. */
	private static function name_words( $name ) {
		$stop  = array( 'saint', 'saints', 'blessed', 'pope', 'the', 'and', 'of', 'st' );
		$words = array();
		foreach ( preg_split( '/[^\p{L}]+/u', strtolower( (string) $name ), -1, PREG_SPLIT_NO_EMPTY ) as $word ) {
			if ( strlen( $word ) > 2 && ! in_array( $word, $stop, true ) ) {
				$words[] = $word;
			}
		}
		return $words;
	}

	private static function fetch_json( $url, $timeout ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => $timeout,
				'headers' => array(
					'User-Agent' => 'WordPress/OnThisDayPlugin (' . home_url() . ')',
				),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $data ) ? $data : null;
	}

	private static function seconds_until_midnight( $now ) {
		return max( strtotime( 'tomorrow', $now ) - $now, HOUR_IN_SECONDS );
	}
}
