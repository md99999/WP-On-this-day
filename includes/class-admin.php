<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings > On This Day admin page: lets the site owner change how many
 * events are shown by default.
 */
class OnThisDay_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function add_settings_page() {
		add_options_page(
			__( 'On This Day', 'on-this-day' ),
			__( 'On This Day', 'on-this-day' ),
			'manage_options',
			'on-this-day',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'onthisday_settings',
			'onthisday_default_count',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_count' ),
				'default'           => 15,
			)
		);

		add_settings_section( 'onthisday_main', '', '__return_false', 'on-this-day' );

		add_settings_field(
			'onthisday_default_count',
			__( 'Number of events to display', 'on-this-day' ),
			array( __CLASS__, 'render_count_field' ),
			'on-this-day',
			'onthisday_main'
		);
	}

	public static function sanitize_count( $value ) {
		$value = (int) $value;

		if ( $value < 1 ) {
			$value = 1;
		} elseif ( $value > 100 ) {
			$value = 100;
		}

		return $value;
	}

	public static function render_count_field() {
		$count = (int) get_option( 'onthisday_default_count', 15 );
		?>
		<input type="number" min="1" max="100" name="onthisday_default_count" value="<?php echo esc_attr( $count ); ?>">
		<p class="description">
			<?php esc_html_e( 'Default number of events shown by the [on_this_day] shortcode and widget (1-100). A shortcode\'s count="" attribute overrides this for that instance.', 'on-this-day' ); ?>
		</p>
		<?php
	}

	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'On This Day Settings', 'on-this-day' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'onthisday_settings' );
				do_settings_sections( 'on-this-day' );
				submit_button();
				?>
			</form>
			<hr>
			<p><?php esc_html_e( 'Add the events list to any page or post with this shortcode:', 'on-this-day' ); ?></p>
			<p><code>[on_this_day]</code></p>
			<p><?php esc_html_e( 'Optionally override the count for a single instance:', 'on-this-day' ); ?></p>
			<p><code>[on_this_day count="10"]</code></p>
			<p><?php esc_html_e( 'The same content is also available as the "On This Day" widget under Appearance > Widgets.', 'on-this-day' ); ?></p>
		</div>
		<?php
	}
}
