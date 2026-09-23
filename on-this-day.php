<?php
/**
 * Plugin Name: On This Day
 * Description: Displays historical events that happened on today's date via a [on_this_day] shortcode and matching widget, sourced from Wikipedia's "On this day" API.
 * Version: 1.0.1
 * Author: Bill Mantz
 * License: GPL-2.0-or-later
 * Text Domain: on-this-day
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONTHISDAY_VERSION', '1.0.1' );
define( 'ONTHISDAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ONTHISDAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ONTHISDAY_PLUGIN_DIR . 'includes/class-events-api.php';
require_once ONTHISDAY_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once ONTHISDAY_PLUGIN_DIR . 'includes/class-widget.php';
require_once ONTHISDAY_PLUGIN_DIR . 'includes/class-admin.php';

add_action(
	'plugins_loaded',
	function () {
		OnThisDay_Shortcode::init();
		OnThisDay_Admin::init();
	}
);

add_action(
	'widgets_init',
	function () {
		register_widget( 'OnThisDay_Widget' );
	}
);
