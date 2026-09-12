<?php
/**
 * Standalone bootstrap: snapshot/smoke are pure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/wp/' );
}
if ( ! defined( 'USN_DIR' ) ) {
	define( 'USN_DIR', dirname( __DIR__ ) );
}
if ( ! defined( 'USN_VERSION' ) ) {
	define( 'USN_VERSION', '0.1.0-test' );
}

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
