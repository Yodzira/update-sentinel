<?php
/**
 * Uninstall cleanup.
 *
 * @package UpdateSentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

usn_uninstall_cleanup();

/**
 * Remove every trace of the plugin.
 *
 * @global wpdb $wpdb
 * @return void
 */
function usn_uninstall_cleanup() {
	global $wpdb;

	delete_option( 'usn_snapshot' );
	delete_option( 'usn_baseline_ms' );
	delete_option( 'usn_last_notice' );
	wp_clear_scheduled_hook( 'usn_prune' );

	$table = $wpdb->prefix . 'usn_log';
	// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix only.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}
