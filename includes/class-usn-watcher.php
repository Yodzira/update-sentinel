<?php
/**
 * Watches updates: keeps the "before" snapshot, smokes the site after,
 * journals the verdict and shows an admin notice.
 *
 * @package UpdateSentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class USN_Watcher {

	const SNAPSHOT_OPTION = 'usn_snapshot';
	const BASELINE_OPTION = 'usn_baseline_ms';
	const NOTICE_OPTION   = 'usn_last_notice';

	public static function boot() {
		// Keep a fresh "before" snapshot while in admin (cheap compare).
		add_action( 'admin_init', array( __CLASS__, 'refresh_snapshot' ), 5 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'on_upgrade_complete' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'render_notice' ) );
		add_action( 'usn_prune', array( __CLASS__, 'run_prune' ) );
	}

	/**
	 * Store the current version map when it differs from the stored one —
	 * BEFORE any new upgrade starts this is the "before" state.
	 *
	 * @return void
	 */
	public static function refresh_snapshot() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$map = array();
		foreach ( get_plugins() as $file => $data ) {
			$map[ dirname( $file ) ] = isset( $data['Version'] ) ? (string) $data['Version'] : '';
		}

		$stored = get_option( self::SNAPSHOT_OPTION, array() );
		if ( ! is_array( $stored ) || $stored !== $map ) {
			update_option( self::SNAPSHOT_OPTION, $map, false );
		}
	}

	/**
	 * Upgrade finished: diff against the stored snapshot, smoke the site,
	 * journal the verdict, queue an admin notice.
	 *
	 * @param WP_Upgrader $upgrader Upgrader instance.
	 * @param array       $hooks     {type, action, plugins?...}.
	 * @return void
	 */
	public static function on_upgrade_complete( $upgrader = null, $hooks = array() ) {
		$type = isset( $hooks['type'] ) ? (string) $hooks['type'] : '';
		if ( '' !== $type && 'plugin' !== $type && 'theme' !== $type ) {
			return; // Translations etc. — nothing to smoke yet.
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$now = array();
		foreach ( get_plugins() as $file => $data ) {
			$now[ dirname( $file ) ] = isset( $data['Version'] ) ? (string) $data['Version'] : '';
		}
		$stored = get_option( self::SNAPSHOT_OPTION, array() );
		$changed = USN_Snapshot::diff( is_array( $stored ) ? $stored : array(), $now );

		// Refresh the baseline for the next round.
		self::refresh_snapshot();

		if ( ! $changed ) {
			return;
		}

		$baseline = (int) get_option( self::BASELINE_OPTION, 1000 );
		$smoke    = ( new USN_Smoke() )->run( home_url( '/' ), $baseline );

		$verdict = USN_Smoke::DOWN === $smoke['verdict'] ? 'broken' : ( USN_Smoke::SLOW === $smoke['verdict'] ? 'slow' : 'healthy' );
		USN_Journal::add( $verdict, USN_Snapshot::describe( $changed ), $smoke['detail'], $smoke['ttfb_ms'] );
		update_option( self::NOTICE_OPTION, array( 'verdict' => $verdict, 'changed' => USN_Snapshot::describe( $changed ), 'detail' => $smoke['detail'] ), false );

		if ( 'broken' === $verdict ) {
			wp_mail(
				get_option( 'admin_email' ),
				'🔴 Update Sentinel — site is DOWN after an update',
				"Changed: " . USN_Snapshot::describe( $changed ) . "\n" . $smoke['detail'] . "\n\nRoll back the last-updated plugin from wordpress.org (Previous Versions) or via FTP rename."
			);
		}
	}

	/**
	 * Show the last verdict once.
	 *
	 * @return void
	 */
	public static function render_notice() {
		$notice = get_option( self::NOTICE_OPTION, array() );
		if ( ! is_array( $notice ) || empty( $notice['verdict'] ) ) {
			return;
		}
		// Show once, then forget.
		delete_option( self::NOTICE_OPTION );

		$class = 'broken' === $notice['verdict'] ? 'notice-error' : ( 'slow' === $notice['verdict'] ? 'notice-warning' : 'notice-success' );
		printf(
			'<div class="notice %1$s is-dismissible"><p><strong>Update Sentinel:</strong> %2$s — %3$s (%4$s)</p></div>',
			esc_attr( $class ),
			esc_html( $notice['verdict'] ),
			esc_html( $notice['changed'] ),
			esc_html( $notice['detail'] )
		);
	}

	public static function run_prune() {
		USN_Journal::prune( 60 );
	}
}
