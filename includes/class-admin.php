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
		add_action( 'admin_notices', array( 'OnThisDay_Health', 'notice' ) );
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
			__( 'Number of items to display', 'on-this-day' ),
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
			<?php esc_html_e( 'Default number of events shown by the [on_this_day] shortcode and widget (1-100). The same number of people born on this day is shown below the events. A shortcode\'s count="" attribute overrides this for that instance.', 'on-this-day' ); ?>
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
			<?php self::render_health_panel(); ?>
		</div>
		<?php
	}

	public static function render_health_panel() {
		$issues = OnThisDay_Health::issues();
		?>
		<hr>
		<div style="max-width:860px;background:#fff;padding:1px 20px 10px;<?php echo $issues ? 'border-left:4px solid #d63638;' : 'border-left:4px solid #00a32a;'; ?>">
			<h2><?php esc_html_e( 'Install health', 'on-this-day' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'How this copy of the plugin was installed, and whether that will cause trouble later.', 'on-this-day' ); ?>
				<?php esc_html_e( 'Installed from', 'on-this-day' ); ?> <code><?php echo esc_html( OnThisDay_Health::folder() ); ?></code>.
			</p>
			<?php if ( ! $issues ) : ?>
				<p><strong><?php esc_html_e( 'Nothing to report.', 'on-this-day' ); ?></strong>
					<?php esc_html_e( 'The plugin is in the folder updates expect, there is only one copy of it, and no repository metadata is sitting in your web root.', 'on-this-day' ); ?></p>
			<?php else : ?>
				<?php foreach ( $issues as $issue ) : ?>
					<h3 style="color:<?php echo 'error' === $issue['level'] ? '#d63638' : '#996800'; ?>"><?php echo esc_html( $issue['title'] ); ?></h3>
					<?php echo $issue['body']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'Building an installable zip from the repository is covered in the plugin\'s README.md, under "Building a release zip".', 'on-this-day' ); ?></p>
		</div>
		<?php
	}
}
