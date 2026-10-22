<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the [on_this_day] shortcode and holds the shared markup
 * renderer used by both the shortcode and the widget.
 */
class OnThisDay_Shortcode {

	public static function init() {
		add_shortcode( 'on_this_day', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function enqueue_assets() {
		wp_enqueue_style(
			'on-this-day',
			ONTHISDAY_PLUGIN_URL . 'assets/css/style.css',
			array(),
			ONTHISDAY_VERSION
		);
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array $atts Shortcode attributes: count="", faith="1" (faith entries first), onlyfaith="1" (faith entries only).
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'count'     => '',
				'faith'     => '',
				'onlyfaith' => '',
			),
			$atts,
			'on_this_day'
		);

		$count = '' !== $atts['count'] ? (int) $atts['count'] : (int) get_option( 'onthisday_default_count', 15 );
		$count = max( 1, min( 100, $count ) );

		$mode = 'mix';
		if ( wp_validate_boolean( $atts['onlyfaith'] ) ) {
			$mode = 'only';
		} elseif ( wp_validate_boolean( $atts['faith'] ) ) {
			$mode = 'faith';
		}

		return self::render_html( $count, $mode );
	}

	/**
	 * Render the events and births markup. Shared by the shortcode and the widget.
	 *
	 * @param int    $count Number of events, and of births, to display.
	 * @param string $mode  'mix' (default), 'faith' (faith entries first) or 'only' (faith entries only).
	 * @return string
	 */
	public static function render_html( $count, $mode = 'mix' ) {
		$events = OnThisDay_Events_API::get_events( $count, $mode );
		$births = OnThisDay_Events_API::get_births( $count, $mode );

		// Null means the feed could not be loaded; an empty array means nothing qualified (faith-only mode).
		if ( null === $events && null === $births ) {
			return '<p class="on-this-day-empty">' . esc_html__( 'No historical events could be loaded right now.', 'on-this-day' ) . '</p>';
		}

		$now         = current_time( 'timestamp' );
		$today_label = date_i18n( 'F j', $now );
		$source_url  = 'https://en.wikipedia.org/wiki/' . date( 'F_j', $now );

		$sections = array(
			array(
				'items'   => $events,
				'class'   => 'on-this-day-heading',
				/* translators: %s: Month and day, e.g. "September 23". */
				'heading' => sprintf( __( 'On This Day: %s', 'on-this-day' ), $today_label ),
			),
			array(
				'items'   => $births,
				'class'   => 'on-this-day-heading on-this-day-births-heading',
				/* translators: %s: Month and day, e.g. "September 23". */
				'heading' => sprintf( __( 'Born on This Day: %s', 'on-this-day' ), $today_label ),
			),
		);

		ob_start();
		?>
		<div class="on-this-day">
			<?php foreach ( $sections as $section ) : ?>
				<?php
				if ( null === $section['items'] ) {
					continue;
				}
				?>
				<h3 class="<?php echo esc_attr( $section['class'] ); ?>"><?php echo esc_html( $section['heading'] ); ?></h3>
				<?php if ( $section['items'] ) : ?>
					<?php self::render_items( $section['items'] ); ?>
				<?php else : ?>
					<p class="on-this-day-none"><?php esc_html_e( 'Nothing to Report Today', 'on-this-day' ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
			<p class="on-this-day-footer">
				<?php esc_html_e( 'WP On This Day news sourced from', 'on-this-day' ); ?>
				<a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Wikipedia', 'on-this-day' ); ?></a>
				(<a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'CC BY-SA 4.0', 'on-this-day' ); ?></a>)
				-sysop- &middot;
				<a href="https://github.com/md99999/WP-On-this-day" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Source on GitHub', 'on-this-day' ); ?></a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Print a year + text list, linking each item to its Wikipedia article when known.
	 *
	 * @param array[] $items List of ['year' => int, 'text' => string, 'url' => string].
	 */
	private static function render_items( $items ) {
		?>
		<ul class="on-this-day-list">
			<?php foreach ( $items as $item ) : ?>
				<li class="on-this-day-item">
					<span class="on-this-day-year"><?php echo esc_html( $item['year'] ); ?></span>
					<span class="on-this-day-text">
						<?php if ( ! empty( $item['url'] ) ) : ?>
							<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['text'] ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $item['text'] ); ?>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}
