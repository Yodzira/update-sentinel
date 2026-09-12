<?php
/**
 * Plugin boot + journal admin page.
 *
 * @package UpdateSentinel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class USN_Plugin {

	public static function boot() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( 'USN_Admin', 'menu' ) );
		}
		USN_Watcher::boot();
	}
}

class USN_Admin {

	public static function menu() {
		add_menu_page( 'Update Sentinel', 'Update Sentinel', 'manage_options', 'update-sentinel', array( __CLASS__, 'render' ), 'dashicons-update' );
	}

	public static function render() {
		$rows = USN_Journal::recent( 30 );
		?>
		<div class="wrap">
			<h1>Update Sentinel</h1>
			<p>Every plugin/theme update gets an instant smoke check. Baseline TTFB: <code><?php echo esc_html( (int) get_option( USN_Watcher::BASELINE_OPTION, 1000 ) ); ?> ms</code> (auto-learned over time).</p>

			<h2>Update journal</h2>
			<?php if ( ! $rows ) : ?>
				<p><em>No updates yet. Update something — or wait for the next one — and the verdict appears here.</em></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:1100px">
					<thead><tr><th style="width:140px">When</th><th style="width:90px">Verdict</th><th>Changed</th><th>Detail</th><th style="width:90px">TTFB</th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['logged_at'] ); ?></td>
							<td><strong style="color:<?php echo esc_attr( 'healthy' === $row['verdict'] ? '#00a32a' : ( 'broken' === $row['verdict'] ? '#b32d2e' : '#dba617' ) ); ?>"><?php echo esc_html( strtoupper( $row['verdict'] ) ); ?></strong></td>
							<td><?php echo esc_html( $row['changed'] ); ?></td>
							<td><?php echo esc_html( $row['detail'] ); ?></td>
							<td><?php echo esc_html( $row['ttfb_ms'] ); ?> ms</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
