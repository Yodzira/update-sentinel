<?php
/**
 * Version snapshots, diffs and smoke verdicts (pure).
 *
 * @package UpdateSentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure snapshot/diff logic.
 */
class USN_Snapshot {

	/**
	 * Diff two version maps: slug => version.
	 *
	 * @param array $before slug => version.
	 * @param array $after  slug => version.
	 * @return array[] {slug, from, to} — added/updated/removed items.
	 */
	public static function diff( array $before, array $after ) {
		$changed = array();
		foreach ( $after as $slug => $to ) {
			$from = isset( $before[ $slug ] ) ? (string) $before[ $slug ] : null;
			if ( null === $from || $from !== (string) $to ) {
				$changed[] = array(
					'slug' => (string) $slug,
					'from' => null === $from ? '(new)' : $from,
					'to'   => (string) $to,
				);
			}
		}
		foreach ( $before as $slug => $from ) {
			if ( ! isset( $after[ $slug ] ) ) {
				$changed[] = array(
					'slug' => (string) $slug,
					'from' => (string) $from,
					'to'   => '(removed)',
				);
			}
		}

		return $changed;
	}

	/**
	 * Human one-line description of the changed set.
	 *
	 * @param array[] $changed Diff rows.
	 * @return string
	 */
	public static function describe( array $changed ) {
		if ( ! $changed ) {
			return 'Nothing changed.';
		}
		$parts = array();
		foreach ( $changed as $row ) {
			$parts[] = $row['slug'] . ' ' . $row['from'] . ' → ' . $row['to'];
		}

		return implode( ', ', $parts );
	}
}

/**
 * Smoke verdict of the site after an update (fetch injectable).
 */
class USN_Smoke {

	const DOWN = 'down';
	const SLOW = 'slow';
	const OK = 'ok';

	/** @var callable|null fn(string $url): array{code:int|null, ttfb_ms:int} */
	private $fetch;

	public function __construct( $fetch = null ) {
		$this->fetch = $fetch;
	}

	/**
	 * Run the smoke check.
	 *
	 * @param string $url             Site URL.
	 * @param int    $baseline_ms     Healthy TTFB baseline (option).
	 * @return array {verdict, code, ttfb_ms, detail}
	 */
	public function run( $url, $baseline_ms = 1000 ) {
		$fetch = $this->fetch ? $this->fetch : array( $this, 'default_fetch' );
		$t0    = microtime( true );
		$result = call_user_func( $fetch, (string) $url );
		$ttfb  = isset( $result['ttfb_ms'] ) ? (int) $result['ttfb_ms'] : (int) round( ( microtime( true ) - $t0 ) * 1000 );
		$code  = isset( $result['code'] ) ? $result['code'] : null;

		if ( null === $code || 200 !== (int) $code ) {
			return array(
				'verdict' => self::DOWN,
				'code'    => $code,
				'ttfb_ms' => $ttfb,
				'detail'  => null === $code ? 'Site did not answer.' : 'Site answered with HTTP ' . (int) $code . '.',
			);
		}
		if ( $ttfb > max( 1500, (int) $baseline_ms * 2 ) ) {
			return array(
				'verdict' => self::SLOW,
				'code'    => $code,
				'ttfb_ms' => $ttfb,
				'detail'  => 'Site answers but is slow: ' . $ttfb . ' ms (baseline ' . (int) $baseline_ms . ' ms).',
			);
		}

		return array(
			'verdict' => self::OK,
			'code'    => $code,
			'ttfb_ms' => $ttfb,
			'detail'  => 'Site answers in ' . $ttfb . ' ms.',
		);
	}

	/**
	 * Default transport: one HEAD-style GET to the home URL.
	 *
	 * @param string $url URL.
	 * @return array
	 */
	private function default_fetch( $url ) {
		$t0       = microtime( true );
		$response = wp_remote_get( $url, array( 'timeout' => 10, 'sslverify' => true ) );
		$ttfb     = (int) round( ( microtime( true ) - $t0 ) * 1000 );
		if ( is_wp_error( $response ) ) {
			return array( 'code' => null, 'ttfb_ms' => $ttfb );
		}

		return array( 'code' => (int) wp_remote_retrieve_response_code( $response ), 'ttfb_ms' => $ttfb );
	}
}
