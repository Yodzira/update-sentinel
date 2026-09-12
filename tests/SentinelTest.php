<?php

use PHPUnit\Framework\TestCase;

/**
 * Snapshot diff + smoke verdicts with fake transport.
 */
class SentinelTest extends TestCase {

	public function test_diff_detects_update_install_remove() {
		$before = array( 'woocommerce' => '9.1.0', 'akismet' => '5.3', 'old-plugin' => '1.0' );
		$after  = array( 'woocommerce' => '9.4.2', 'akismet' => '5.3', 'new-plugin' => '0.1' );

		$changed = USN_Snapshot::diff( $before, $after );
		$by_slug = array_column( $changed, null, 'slug' );

		$this->assertSame( '9.1.0', $by_slug['woocommerce']['from'] );
		$this->assertSame( '9.4.2', $by_slug['woocommerce']['to'] );
		$this->assertSame( '(new)', $by_slug['new-plugin']['from'] );
		$this->assertSame( '(removed)', $by_slug['old-plugin']['to'] );
		$this->assertArrayNotHasKey( 'akismet', $by_slug );
	}

	public function test_describe_is_human() {
		$changed = USN_Snapshot::diff( array( 'a' => '1.0' ), array( 'a' => '2.0' ) );
		$this->assertSame( 'a 1.0 → 2.0', USN_Snapshot::describe( $changed ) );
		$this->assertSame( 'Nothing changed.', USN_Snapshot::describe( array() ) );
	}

	public function test_smoke_verdicts() {
		$ok   = new USN_Smoke( static function () { return array( 'code' => 200, 'ttfb_ms' => 200 ); } );
		$down = new USN_Smoke( static function () { return array( 'code' => null, 'ttfb_ms' => 10000 ); } );
		$err  = new USN_Smoke( static function () { return array( 'code' => 500, 'ttfb_ms' => 100 ); } );
		$slow = new USN_Smoke( static function () { return array( 'code' => 200, 'ttfb_ms' => 9000 ); } );

		$this->assertSame( USN_Smoke::OK, $ok->run( 'https://x/' )['verdict'] );
		$this->assertSame( USN_Smoke::DOWN, $down->run( 'https://x/' )['verdict'] );
		$this->assertSame( USN_Smoke::DOWN, $err->run( 'https://x/' )['verdict'] );
		$this->assertSame( USN_Smoke::SLOW, $slow->run( 'https://x/', 1000 )['verdict'] );
	}

	public function test_smoke_slow_threshold_respects_baseline() {
		// 2500 ms is "slow" against 1000 baseline, but ok against 5000.
		$smoke = new USN_Smoke( static function () { return array( 'code' => 200, 'ttfb_ms' => 2500 ); } );
		$this->assertSame( USN_Smoke::SLOW, $smoke->run( 'https://x/', 1000 )['verdict'] );
		$this->assertSame( USN_Smoke::OK, $smoke->run( 'https://x/', 5000 )['verdict'] );
	}
}
