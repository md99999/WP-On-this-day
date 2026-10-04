<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install health: warns an administrator when the plugin was not installed from a release zip.
 *
 * GitHub's "Download ZIP" names the folder after the branch, so the next proper install becomes a
 * second copy of the plugin, and a git clone carries a .git directory that some servers will serve.
 * Documentation only helps people who read it, so the plugin checks itself and says so in wp-admin.
 */
class OnThisDay_Health {

	/** The folder name a release zip unpacks to, and the one updates will use. */
	const SLUG = 'WP-on-this-day';

	const MAIN_FILE = 'on-this-day.php';

	/**
	 * @return array[] List of ['level' => 'error'|'warning', 'title' => string, 'body' => string (HTML)].
	 */
	public static function issues() {
		$out = array();
		foreach ( array( self::check_duplicates(), self::check_git(), self::check_folder() ) as $issue ) {
			if ( $issue ) {
				$out[] = $issue;
			}
		}
		return $out;
	}

	/** The folder this copy lives in, e.g. "WP-On-this-day-main". */
	public static function folder() {
		return basename( untrailingslashit( ONTHISDAY_PLUGIN_DIR ) );
	}

	/** A second copy of the plugin in wp-content/plugins; WordPress may update or deactivate the wrong one. */
	private static function check_duplicates() {
		$others = self::other_copies();
		if ( ! $others ) {
			return null;
		}
		$list = '<code>' . implode( '</code>, <code>', array_map( 'esc_html', $others ) ) . '</code>';
		return array(
			'level' => 'error',
			'title' => 'There is more than one copy of this plugin installed',
			'body'  => '<p>This copy is running from <code>' . esc_html( self::folder() ) . '</code>, and these other copies'
				. ' are also in <code>wp-content/plugins</code>: ' . $list . '.</p>'
				. '<p>WordPress treats them as separate plugins, so an update or a deactivation can easily land on the'
				. ' wrong one, and both register the same shortcode and widget. Keep the copy in <code>'
				. esc_html( self::SLUG ) . '</code> and delete the others from the <a href="'
				. esc_url( admin_url( 'plugins.php' ) ) . '">Plugins</a> screen. The plugin stores only a single'
				. ' setting, so deleting a copy loses nothing important.</p>',
		);
	}

	/** A .git directory inside the plugin means the whole project history is sitting in the web root. */
	private static function check_git() {
		if ( ! is_dir( ONTHISDAY_PLUGIN_DIR . '.git' ) ) {
			return null;
		}
		$reachable = self::git_reachable();
		$htaccess  = file_exists( ONTHISDAY_PLUGIN_DIR . '.htaccess' );
		$body      = '<p>This copy was installed from a git clone, so <code>' . esc_html( self::folder() )
			. '/.git</code> sits inside <code>wp-content/plugins</code>. That directory holds the project\'s entire'
			. ' history, and on many servers it can be read by anyone who knows the path.</p>';
		if ( true === $reachable ) {
			$body .= '<p><strong>It is readable over the web on this site right now.</strong> The plugin ships an'
				. ' <code>.htaccess</code> that blocks it, but your server is not applying it'
				. ( $htaccess ? ' (nginx does not read <code>.htaccess</code> at all).' : ', and the file is missing from this copy.' )
				. '</p>';
		} elseif ( false === $reachable ) {
			$body .= '<p>A request for it from outside was refused, so your server is not serving it today. That can'
				. ' change with a server or host configuration change, which is why it is worth removing anyway.</p>';
		} else {
			$body .= '<p>Whether your server serves it could not be checked from here.</p>';
		}
		$body .= '<p>The fix is to not keep <code>.git</code> on the server: install a release zip, or build one with'
			. ' <code>git archive</code> as the README describes, and upload that. Deleting the <code>.git</code>'
			. ' directory by hand works too.</p>';
		return array(
			'level' => true === $reachable ? 'error' : 'warning',
			'title' => true === $reachable ? 'The repository history is exposed on this site' : 'This copy contains a .git directory',
			'body'  => $body,
		);
	}

	/** Installed under a branch-named folder, which makes the next proper install a second copy. */
	private static function check_folder() {
		if ( self::folder() === self::SLUG ) {
			return null;
		}
		return array(
			'level' => 'warning',
			'title' => 'The plugin folder is not named ' . self::SLUG,
			'body'  => '<p>This copy is installed as <code>wp-content/plugins/' . esc_html( self::folder() ) . '</code>,'
				. ' which is what GitHub\'s <em>Download ZIP</em> produces: it names the folder after the branch.</p>'
				. '<p>The plugin runs fine like this, but WordPress identifies a plugin by its folder, so uploading a'
				. ' release zip later adds a <em>second</em> copy instead of updating this one. Deactivate the plugin,'
				. ' rename the folder to <code>' . esc_html( self::SLUG ) . '</code> over FTP, SFTP or your host\'s'
				. ' file manager, then activate <strong>On This Day</strong> again on the Plugins screen.</p>',
		);
	}

	/** Other directories in wp-content/plugins that hold a copy of this plugin. */
	private static function other_copies() {
		$dir     = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins';
		$here    = self::folder();
		$found   = array();
		$entries = @scandir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $entries ) {
			return $found;
		}
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry || $entry === $here || ! is_dir( $dir . '/' . $entry ) ) {
				continue;
			}
			// The main file name alone could belong to an unrelated plugin, so also require one of ours.
			if ( file_exists( $dir . '/' . $entry . '/' . self::MAIN_FILE ) && file_exists( $dir . '/' . $entry . '/includes/class-events-api.php' ) ) {
				$found[] = $entry;
			}
		}
		return $found;
	}

	/**
	 * Asks this site, over HTTP, whether it will serve the clone's .git/HEAD.
	 * Cached for a day: it is one request, but there is no reason to repeat it on every page.
	 *
	 * @param bool $fresh Skip the cache.
	 * @return bool|null True if served, false if refused, null if it could not be determined.
	 */
	public static function git_reachable( $fresh = false ) {
		$key = 'onthisday_git_reachable';
		if ( ! $fresh ) {
			$cached = get_transient( $key );
			if ( false !== $cached ) {
				return 'yes' === $cached ? true : ( 'no' === $cached ? false : null );
			}
		}
		$url = plugins_url( '.git/HEAD', ONTHISDAY_PLUGIN_DIR . self::MAIN_FILE );
		// Follow redirects, or a site that sends http to https would look safe when it is not.
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 5,
				'redirection' => 3,
				'sslverify'   => false,
			)
		);
		if ( is_wp_error( $response ) ) {
			$result = null;
		} else {
			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 200 === $code && 0 === strpos( (string) wp_remote_retrieve_body( $response ), 'ref:' ) ) {
				$result = true;
			} elseif ( in_array( $code, array( 401, 403, 404, 410, 451 ), true ) ) {
				$result = false;
			} else {
				$result = null;
			}
		}
		set_transient( $key, true === $result ? 'yes' : ( false === $result ? 'no' : 'unknown' ), DAY_IN_SECONDS );
		return $result;
	}

	/** The admin notice, shown on the Plugins screen; the full panel lives on the settings page. */
	public static function notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		foreach ( self::issues() as $issue ) {
			printf(
				'<div class="notice notice-%s"><p><strong>On This Day &mdash; %s</strong></p>%s</div>',
				'error' === $issue['level'] ? 'error' : 'warning',
				esc_html( $issue['title'] ),
				$issue['body'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
	}
}
