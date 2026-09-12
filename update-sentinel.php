<?php
/**
 * Plugin Name:       Update Sentinel
 * Plugin URI:        https://github.com/Yodzira/update-sentinel
 * Description:      Every update gets an instant health check: site answers, no new fatals, TTFB measured. Know immediately when an update breaks your site — and what to roll back.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Yodzira
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       update-sentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'USN_VERSION', '0.1.0' );
define( 'USN_FILE', __FILE__ );
define( 'USN_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'USN_' ) ) {
			return;
		}
		$snake = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', substr( $class, 4 ) ) );
		$snake = str_replace( '_', '-', $snake );
		$file  = USN_DIR . '/includes/class-usn-' . $snake . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

add_action( 'plugins_loaded', array( 'USN_Plugin', 'boot' ), 20 );

register_activation_hook(
	__FILE__,
	static function () {
		require_once USN_DIR . '/includes/class-usn-journal.php';
		USN_Journal::activate();
		if ( ! wp_next_scheduled( 'usn_prune' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'usn_prune' );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		wp_clear_scheduled_hook( 'usn_prune' );
	}
);
