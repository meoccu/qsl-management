<?php
/**
 * Import and export.
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Import/export controller.
 */
class QSLM_Import_Export {

	/**
	 * Tables included in backups.
	 *
	 * @return array
	 */
	public static function tables() {
		return array( 'qso', 'cards', 'card_requests', 'exchange_requests', 'receives', 'addresses', 'bureaus', 'station_cards', 'activities', 'audit', 'sequences' );
	}

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_post_qslm_export', array( __CLASS__, 'export' ) );
	}

	/**
	 * Export endpoint.
	 */
	public static function export() {
		if ( ! current_user_can( 'manage_qslm' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'qsl-management' ) );
		}
		check_admin_referer( 'qslm_export' );
		$type = sanitize_key( wp_unslash( $_GET['type'] ?? 'json' ) );
		if ( 'qso_csv' === $type ) {
			self::export_qso_csv();
		}
		self::export_json();
	}

	/**
	 * Export JSON.
	 */
	private static function export_json() {
		global $wpdb;
		$data = array(
			'plugin'   => 'qsl-management',
			'version'  => QSLM_VERSION,
			'exported' => current_time( 'mysql' ),
			'settings' => QSLM_Settings::all(),
			'tables'   => array(),
		);
		foreach ( self::tables() as $table ) {
			$data['tables'][ $table ] = $wpdb->get_results( 'SELECT * FROM ' . QSLM_DB::table( $table ), ARRAY_A );
		}
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="qsl-management-' . gmdate( 'Ymd-His' ) . '.json"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Export QSO CSV.
	 */
	private static function export_qso_csv() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . QSLM_DB::table( 'qso' ) . ' ORDER BY id ASC', ARRAY_A );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="qsl-management-qso-' . gmdate( 'Ymd-His' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		if ( $rows ) {
			fputcsv( $out, array_keys( $rows[0] ) );
			foreach ( $rows as $row ) {
				fputcsv( $out, $row );
			}
		}
		fclose( $out );
		exit;
	}

	/**
	 * Import JSON from uploaded file.
	 *
	 * @return true|\WP_Error
	 */
	public static function import_json() {
		if ( empty( $_FILES['qslm_json']['tmp_name'] ) || empty( $_POST['confirm_import'] ) ) {
			return new \WP_Error( 'qslm_import', 'No file or confirmation.' );
		}
		$file = $_FILES['qslm_json'];
		if ( ! empty( $file['error'] ) || (int) $file['size'] > 20 * MB_IN_BYTES ) {
			return new \WP_Error( 'qslm_import', 'Upload failed or file too large.' );
		}
		$raw = file_get_contents( $file['tmp_name'] );
		$data = json_decode( (string) $raw, true );
		if ( ! is_array( $data ) || empty( $data['tables'] ) || 'qsl-management' !== ( $data['plugin'] ?? '' ) ) {
			return new \WP_Error( 'qslm_import', 'Invalid backup file.' );
		}
		global $wpdb;
		foreach ( self::tables() as $table ) {
			$rows = isset( $data['tables'][ $table ] ) && is_array( $data['tables'][ $table ] ) ? $data['tables'][ $table ] : array();
			$columns = $wpdb->get_col( 'DESC ' . QSLM_DB::table( $table ) );
			if ( ! $columns ) {
				continue;
			}
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$clean = array_intersect_key( $row, array_flip( $columns ) );
				if ( $clean ) {
					$wpdb->replace( QSLM_DB::table( $table ), $clean );
				}
			}
		}
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			update_option( QSLM_Settings::OPTION, QSLM_Settings::save( $data['settings'] ), false );
		}
		self::repair_sequences();
		QSLM_Audit::log( 'import_json', 'backup', 0, 'JSON backup imported' );
		return true;
	}

	/**
	 * Ensure sequence counters are ahead of existing IDs/numbers.
	 */
	private static function repair_sequences() {
		global $wpdb;
		$cards = (int) $wpdb->get_var( 'SELECT MAX(id) FROM ' . QSLM_DB::table( 'cards' ) );
		$recv  = (int) $wpdb->get_var( 'SELECT MAX(id) FROM ' . QSLM_DB::table( 'receives' ) );
		$req   = (int) $wpdb->get_var( 'SELECT MAX(id) FROM ' . QSLM_DB::table( 'card_requests' ) );
		$ex    = (int) $wpdb->get_var( 'SELECT MAX(id) FROM ' . QSLM_DB::table( 'exchange_requests' ) );
		QSLM_DB::ensure_sequence( 'card', max( $cards, $req, $ex ) );
		QSLM_DB::ensure_sequence( 'receive', $recv );
		QSLM_DB::ensure_sequence( 'request', max( $req, $ex ) );
	}
}
