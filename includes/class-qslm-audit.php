<?php
/**
 * Audit log.
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Audit helper.
 */
class QSLM_Audit {

	/**
	 * Add an audit entry.
	 *
	 * @param string $action      Action.
	 * @param string $object_type Object type.
	 * @param int    $object_id   Object ID.
	 * @param string $message     Message.
	 */
	public static function log( $action, $object_type = '', $object_id = 0, $message = '' ) {
		QSLM_DB::insert(
			'audit',
			array(
				'action'      => sanitize_key( $action ),
				'object_type' => sanitize_key( $object_type ),
				'object_id'   => absint( $object_id ),
				'message'     => wp_kses_post( $message ),
				'user_id'     => get_current_user_id(),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%d', '%s' )
		);
	}
}
