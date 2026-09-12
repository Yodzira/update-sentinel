<?php
/**
 * Journal storage.
 *
 * @package UpdateSentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class USN_Journal {

	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'usn_log';
	}

	public static function activate() {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			logged_at datetime NOT NULL DEFAULT '1970-01-01 00:00:00',
			verdict varchar(10) NOT NULL DEFAULT '',
			changed varchar(500) NOT NULL DEFAULT '',
			detail varchar(500) NOT NULL DEFAULT '',
			ttfb_ms int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY logged_at (logged_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public static function add( $verdict, $changed, $detail, $ttfb_ms ) {
		global $wpdb;

		$wpdb->insert(
			self::table_name(),
			array(
				'logged_at' => current_time( 'mysql' ),
				'verdict'   => substr( (string) $verdict, 0, 10 ),
				'changed'   => substr( (string) $changed, 0, 500 ),
				'detail'    => substr( (string) $detail, 0, 500 ),
				'ttfb_ms'   => (int) $ttfb_ms,
			),
			array( '%s', '%s', '%s', '%s', '%d' )
		);

		return (int) $wpdb->insert_id;
	}

	public static function recent( $limit = 30 ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->get_results( $wpdb->prepare( "SELECT id, logged_at, verdict, changed, detail, ttfb_ms FROM {$table} ORDER BY id DESC LIMIT %d", (int) $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL -- table from prefix.
	}

	public static function prune( $days = 60 ) {
		global $wpdb;
		$table = self::table_name();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE logged_at < DATE_SUB(%s, INTERVAL %d DAY)", current_time( 'mysql' ), (int) $days ) );
	}

	public static function erase_all() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.
	}

	public static function drop_table() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL -- identifier derived from $wpdb->prefix.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
