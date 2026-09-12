<?php
/**
 * Update Sentinel integration — inside QA container:
 *   docker exec infra-wordpress-1 wp eval-file /tmp/usn-integration.php --allow-root
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check( $label, $cond ) {
	if ( $cond ) {
		$GLOBALS['pass']++;
		echo "  ok   {$label}\n";
	} else {
		$GLOBALS['fail']++;
		echo "  FAIL {$label}\n";
	}
}

echo "== Update Sentinel integration ==\n";

check( 'plugin active', is_plugin_active( 'update-sentinel/update-sentinel.php' ) );
check( 'classes loaded', class_exists( 'USN_Watcher' ) && class_exists( 'USN_Journal' ) );

global $wpdb;
$table     = USN_Journal::table_name();
$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
if ( $has_table !== $table ) {
	USN_Journal::activate(); // install --force does not re-run the activation hook.
	$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
}
check( 'journal table present', $has_table === $table );

// Baseline snapshot.
USN_Journal::erase_all();
delete_option( USN_Watcher::NOTICE_OPTION );
USN_Watcher::refresh_snapshot();
$snapshot = get_option( USN_Watcher::SNAPSHOT_OPTION, array() );
check( 'snapshot stored', is_array( $snapshot ) && count( $snapshot ) > 0 );
check( 'snapshot contains this plugin', isset( $snapshot['update-sentinel'] ) );

// Simulate an update of one plugin: bump its version in the "after" map.
$after               = $snapshot;
$after['update-sentinel'] = '9.9.9-fake';
$changed             = USN_Snapshot::diff( $snapshot, $after );
check( 'diff spots the fake update', 1 === count( $changed ) && 'update-sentinel' === $changed[0]['slug'] );

// Smoke against the stand (the container can reach :80; home_url is :8080).
$smoke = ( new USN_Smoke( static function ( $url ) {
	$url = str_replace( ':8080', ':80', $url );
	$t0  = microtime( true );
	$r   = wp_remote_get( $url, array( 'timeout' => 10, 'sslverify' => false ) );
	$ttfb = (int) round( ( microtime( true ) - $t0 ) * 1000 );
	return array( 'code' => is_wp_error( $r ) ? null : (int) wp_remote_retrieve_response_code( $r ), 'ttfb_ms' => $ttfb );
} ) )->run( home_url( '/' ), 1000 );
check( 'smoke verdict is healthy on live stand', 'ok' === $smoke['verdict'] );

// Journal the verdict (the path on_upgrade_complete takes).
USN_Journal::add( 'healthy', USN_Snapshot::describe( $changed ), $smoke['detail'], $smoke['ttfb_ms'] );
$journal = USN_Journal::recent( 5 );
check( 'journal row written', 1 === count( $journal ) && 'healthy' === $journal[0]['verdict'] );
check( 'journal names the changed plugin', false !== strpos( $journal[0]['changed'], 'update-sentinel' ) );

// Notice option shape (rendered on next admin hit).
update_option( USN_Watcher::NOTICE_OPTION, array( 'verdict' => 'healthy', 'changed' => 'x', 'detail' => 'y' ), false );
check( 'notice option stored', is_array( get_option( USN_Watcher::NOTICE_OPTION ) ) );
delete_option( USN_Watcher::NOTICE_OPTION );

// Cleanup.
USN_Journal::erase_all();
check( 'erase clears journal', array() === USN_Journal::recent( 5 ) );

printf( "\n== Update Sentinel integration: %d pass, %d fail ==\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
