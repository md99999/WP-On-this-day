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

		register_setting(
			'onthisday_settings',
			'onthisday_faith_keywords',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_keywords' ),
				'default'           => OnThisDay_Events_API::DEFAULT_FAITH_KEYWORDS,
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

		add_settings_field(
			'onthisday_faith_keywords',
			__( 'Faith keywords', 'on-this-day' ),
			array( __CLASS__, 'render_keywords_field' ),
			'on-this-day',
			'onthisday_main'
		);
	}

	/** Keep letters, digits, spaces, hyphens and apostrophes in each keyword; store as a comma-separated list. */
	public static function sanitize_keywords( $value ) {
		$words = array();
		foreach ( OnThisDay_Events_API::parse_keywords( sanitize_textarea_field( (string) $value ) ) as $word ) {
			$word = trim( preg_replace( "/[^\p{L}\p{N} '-]+/u", '', $word ) );
			if ( '' !== $word ) {
				$words[] = $word;
			}
		}
		return implode( ', ', array_unique( $words ) );
	}

	public static function render_keywords_field() {
		$keywords = implode( ', ', OnThisDay_Events_API::faith_keywords() );
		?>
		<textarea name="onthisday_faith_keywords" rows="3" cols="60" class="large-text"><?php echo esc_textarea( $keywords ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Comma-separated, whole words, not case-sensitive. An entry counts as faith-related when its text, or a Wikipedia article it links to, contains one of these words. Clear the box and save to restore the defaults.', 'on-this-day' ); ?>
		</p>
		<?php
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
			<p><?php esc_html_e( 'Faith entries: by default about a third of each list are faith-related entries (when the day has them) and the rest are random. To put faith entries first and fill the rest at random:', 'on-this-day' ); ?></p>
			<p><code>[on_this_day faith="1"]</code></p>
			<p><?php esc_html_e( 'To show only faith-related entries ("Nothing to Report Today" when a section has none):', 'on-this-day' ); ?></p>
			<p><code>[on_this_day onlyfaith="1"]</code></p>
			<p><?php esc_html_e( 'Both of those add a "Today in the Church" section (the day\'s church calendar and feast days). To leave it out, or to add it to the default mix:', 'on-this-day' ); ?></p>
			<p><code>[on_this_day faith="1" church="0"]</code> &nbsp; <code>[on_this_day church="1"]</code></p>
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
			<p class="description">
				<?php esc_html_e( 'Building an installable zip from the repository is covered in the plugin\'s', 'on-this-day' ); ?>
				<a href="<?php echo esc_url( OnThisDay_Health::BUILD_DOCS_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'README on GitHub', 'on-this-day' ); ?></a>.
			</p>
		</div>
		<?php
	}
}
