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
	 * @param array $atts Shortcode attributes. Supports count="".
	 * @return string
	 */
	public static function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'count' => '',
			),
			$atts,
			'on_this_day'
		);

		$count = '' !== $atts['count'] ? (int) $atts['count'] : (int) get_option( 'onthisday_default_count', 15 );
		$count = max( 1, min( 100, $count ) );

		return self::render_html( $count );
	}

	/**
	 * Render the events list markup. Shared by the shortcode and the widget.
	 *
	 * @param int $count Number of events to display.
	 * @return string
	 */
	public static function render_html( $count ) {
		$events = OnThisDay_Events_API::get_events( $count );

		if ( empty( $events ) ) {
			return '<p class="on-this-day-empty">' . esc_html__( 'No historical events could be loaded right now.', 'on-this-day' ) . '</p>';
		}

		$today_label = date_i18n( 'F j', current_time( 'timestamp' ) );

		ob_start();
		?>
		<div class="on-this-day">
			<h3 class="on-this-day-heading">
				<?php
				/* translators: %s: Month and day, e.g. "September 23". */
				echo esc_html( sprintf( __( 'On This Day: %s', 'on-this-day' ), $today_label ) );
				?>
			</h3>
			<ul class="on-this-day-list">
				<?php foreach ( $events as $event ) : ?>
					<li class="on-this-day-item">
						<span class="on-this-day-year"><?php echo esc_html( $event['year'] ); ?></span>
						<span class="on-this-day-text">
							<?php if ( ! empty( $event['url'] ) ) : ?>
								<a href="<?php echo esc_url( $event['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $event['text'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $event['text'] ); ?>
							<?php endif; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}
}
