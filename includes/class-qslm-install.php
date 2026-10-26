<?php
/**
 * Activation, schema and defaults.
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Installer.
 */
class QSLM_Install {

	const DB_VERSION = '1.0.0';

	/**
	 * Activate.
	 */
	public static function activate() {
		self::create_tables();
		self::defaults();
		self::capabilities();
		update_option( 'qslm_db_version', self::DB_VERSION, false );
	}

	/**
	 * Deactivate.
	 */
	public static function deactivate() {
		wp_cache_flush();
	}

	/**
	 * Upgrade if needed.
	 */
	public static function maybe_upgrade() {
		if ( get_option( 'qslm_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
			self::defaults();
			self::capabilities();
			update_option( 'qslm_db_version', self::DB_VERSION, false );
		}
	}

	/**
	 * Create tables.
	 */
	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$tables = array();

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'qso' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			scene varchar(20) NOT NULL DEFAULT 'QSO',
			call_sign varchar(32) NOT NULL DEFAULT '',
			qso_date date NOT NULL DEFAULT '0000-00-00',
			time_on varchar(12) NOT NULL DEFAULT '',
			timezone varchar(40) NOT NULL DEFAULT 'UTC+8',
			mode varchar(24) NOT NULL DEFAULT '',
			frequency varchar(40) NOT NULL DEFAULT '',
			band varchar(20) NOT NULL DEFAULT '',
			rst_sent varchar(16) NOT NULL DEFAULT '',
			rst_received varchar(16) NOT NULL DEFAULT '',
			qth varchar(191) NOT NULL DEFAULT '',
			grid varchar(20) NOT NULL DEFAULT '',
			comment text NULL,
			my_rig varchar(191) NOT NULL DEFAULT '',
			my_antenna varchar(191) NOT NULL DEFAULT '',
			tx_power varchar(40) NOT NULL DEFAULT '',
			operator varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY call_sign (call_sign),
			KEY qso_date (qso_date),
			KEY scene (scene)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'cards' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			card_no varchar(24) NOT NULL DEFAULT '',
			scene varchar(20) NOT NULL DEFAULT 'QSO',
			qso_id bigint(20) unsigned NOT NULL DEFAULT 0,
			call_sign varchar(32) NOT NULL DEFAULT '',
			card_version varchar(80) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT '待制卡',
			card_issued tinyint(1) NOT NULL DEFAULT 0,
			card_issued_at datetime NULL,
			envelope_printed tinyint(1) NOT NULL DEFAULT 0,
			card_sent tinyint(1) NOT NULL DEFAULT 0,
			sent_at datetime NULL,
			receipt_confirmed tinyint(1) NOT NULL DEFAULT 0,
			received tinyint(1) NOT NULL DEFAULT 0,
			address_id bigint(20) unsigned NOT NULL DEFAULT 0,
			remarks text NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY card_no (card_no),
			KEY call_sign (call_sign),
			KEY qso_id (qso_id),
			KEY scene (scene),
			KEY status (status)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'card_requests' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_no varchar(32) NOT NULL DEFAULT '',
			call_sign varchar(32) NOT NULL DEFAULT '',
			qso_id bigint(20) unsigned NOT NULL DEFAULT 0,
			card_version varchar(80) NOT NULL DEFAULT '',
			address_json longtext NULL,
			note text NULL,
			status varchar(24) NOT NULL DEFAULT '待审核',
			admin_note text NULL,
			card_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY request_no (request_no),
			KEY call_sign (call_sign),
			KEY status (status)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'exchange_requests' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_no varchar(32) NOT NULL DEFAULT '',
			scene varchar(24) NOT NULL DEFAULT 'ONLINE_EYEBALL',
			call_sign varchar(32) NOT NULL DEFAULT '',
			card_version varchar(80) NOT NULL DEFAULT '',
			email varchar(191) NOT NULL DEFAULT '',
			address_type varchar(20) NOT NULL DEFAULT 'personal',
			address_json longtext NULL,
			note text NULL,
			status varchar(24) NOT NULL DEFAULT '待审核',
			review_note text NULL,
			card_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY request_no (request_no),
			KEY call_sign (call_sign),
			KEY status (status)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'receives' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			receive_no varchar(24) NOT NULL DEFAULT '',
			scene varchar(24) NOT NULL DEFAULT 'QSO',
			call_sign varchar(32) NOT NULL DEFAULT '',
			card_id bigint(20) unsigned NOT NULL DEFAULT 0,
			received_at date NOT NULL DEFAULT '0000-00-00',
			note text NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY receive_no (receive_no),
			KEY call_sign (call_sign),
			KEY card_id (card_id)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'addresses' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			call_sign varchar(32) NOT NULL DEFAULT '',
			name varchar(191) NOT NULL DEFAULT '',
			phone varchar(64) NOT NULL DEFAULT '',
			postal_code varchar(32) NOT NULL DEFAULT '',
			country varchar(80) NOT NULL DEFAULT '',
			address text NULL,
			email varchar(191) NOT NULL DEFAULT '',
			note text NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY call_sign (call_sign)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'bureaus' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL DEFAULT '',
			country varchar(80) NOT NULL DEFAULT '',
			postal_code varchar(32) NOT NULL DEFAULT '',
			address text NULL,
			phone varchar(64) NOT NULL DEFAULT '',
			note text NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'station_cards' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			version varchar(80) NOT NULL DEFAULT '',
			total int(11) NOT NULL DEFAULT 0,
			used int(11) NOT NULL DEFAULT 0,
			image_url text NULL,
			active tinyint(1) NOT NULL DEFAULT 1,
			sort_order int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY version (version)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'activities' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL DEFAULT '',
			location varchar(191) NOT NULL DEFAULT '',
			activity_date date NOT NULL DEFAULT '0000-00-00',
			note text NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'audit' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			action varchar(64) NOT NULL DEFAULT '',
			object_type varchar(32) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			message text NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY object_type (object_type),
			KEY created_at (created_at)
		) $charset;";

		$tables[] = "CREATE TABLE " . QSLM_DB::table( 'sequences' ) . " (
			name varchar(40) NOT NULL,
			value bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (name)
		) $charset;";

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Default options and demo station card.
	 */
	public static function defaults() {
		if ( false === get_option( 'qslm_settings', false ) ) {
			add_option( 'qslm_settings', QSLM_Settings::defaults(), '', false );
		}
		global $wpdb;
		$table = QSLM_DB::table( 'station_cards' );
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table`" );
		if ( ! $count ) {
			QSLM_DB::insert(
				'station_cards',
				array(
					'version'    => '2026 v1',
					'total'      => 100,
					'used'       => 0,
					'active'     => 1,
					'sort_order' => 1,
					'created_at' => current_time( 'mysql' ),
				),
				array( '%s', '%d', '%d', '%d', '%d', '%s' )
			);
		}
	}

	/**
	 * Capabilities.
	 */
	public static function capabilities() {
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'manage_qslm' );
		}
	}
}
