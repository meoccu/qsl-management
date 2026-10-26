<?php
/**
 * Uninstall.
 *
 * @package QSLM
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'qslm_settings', array() );
if ( empty( $settings['remove_data'] ) ) {
	delete_option( 'qslm_db_version' );
	return;
}

global $wpdb;
$tables = array( 'qso', 'cards', 'card_requests', 'exchange_requests', 'receives', 'addresses', 'bureaus', 'station_cards', 'activities', 'audit', 'sequences' );
foreach ( $tables as $table ) {
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'qslm_' . $table );
}
delete_option( 'qslm_settings' );
delete_option( 'qslm_db_version' );
$role = get_role( 'administrator' );
if ( $role ) {
	$role->remove_cap( 'manage_qslm' );
}
