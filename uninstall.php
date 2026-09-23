<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'onthisday_default_count' );
delete_option( 'widget_on_this_day_widget' );

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_onthisday\_%' OR option_name LIKE '\_transient\_timeout\_onthisday\_%'" );
