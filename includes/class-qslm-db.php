<?php
/**
 * Database access and sequences.
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Database helper.
 */
class QSLM_DB {

	/**
	 * Table name.
	 *
	 * @param string $name Logical name.
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;
		$allowed = array(
			'qso',
			'cards',
			'card_requests',
			'exchange_requests',
			'receives',
			'addresses',
			'bureaus',
			'station_cards',
			'activities',
			'audit',
			'sequences',
		);
		if ( ! in_array( $name, $allowed, true ) ) {
			return $wpdb->prefix . 'qslm_qso';
		}
		return $wpdb->prefix . 'qslm_' . $name;
	}

	/**
	 * Get a row.
	 *
	 * @param string $table Logical table.
	 * @param int    $id    Row ID.
	 * @return array|null
	 */
	public static function get( $table, $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( $table ) . ' WHERE id = %d', $id ), ARRAY_A );
		return $row ? $row : null;
	}

	/**
	 * Find one row by a column.
	 *
	 * @param string $table  Logical table.
	 * @param string $column Column.
	 * @param mixed  $value  Value.
	 * @return array|null
	 */
	public static function find( $table, $column, $value ) {
		global $wpdb;
		$column = self::column( $column );
		if ( ! $column ) {
			return null;
		}
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( $table ) . " WHERE `$column` = %s LIMIT 1", $value ), ARRAY_A );
		return $row ? $row : null;
	}

	/**
	 * Insert.
	 *
	 * @param string $table  Logical table.
	 * @param array  $data   Data.
	 * @param array  $format Formats.
	 * @return int
	 */
	public static function insert( $table, $data, $format = array() ) {
		global $wpdb;
		$wpdb->insert( self::table( $table ), $data, $format );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update by ID.
	 *
	 * @param string $table  Logical table.
	 * @param int    $id     ID.
	 * @param array  $data   Data.
	 * @param array  $format Formats.
	 * @return bool
	 */
	public static function update( $table, $id, $data, $format = array() ) {
		global $wpdb;
		return false !== $wpdb->update( self::table( $table ), $data, array( 'id' => $id ), $format, array( '%d' ) );
	}

	/**
	 * Delete by ID.
	 *
	 * @param string $table Logical table.
	 * @param int    $id    ID.
	 * @return bool
	 */
	public static function delete( $table, $id ) {
		global $wpdb;
		return false !== $wpdb->delete( self::table( $table ), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Count rows.
	 *
	 * @param string $table Logical table.
	 * @param string $where Optional where; may contain placeholders.
	 * @param array  $params Optional prepare params.
	 * @return int
	 */
	public static function count( $table, $where = '', $params = array() ) {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM ' . self::table( $table );
		if ( $where ) {
			$sql .= ' WHERE ' . ( $params ? $wpdb->prepare( $where, $params ) : $where );
		}
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Next sequence value, atomically.
	 *
	 * @param string $name Sequence.
	 * @return int
	 */
	public static function next_sequence( $name ) {
		global $wpdb;
		$table = self::table( 'sequences' );
		$wpdb->query( $wpdb->prepare( "INSERT INTO `$table` (`name`, `value`) VALUES (%s, 1) ON DUPLICATE KEY UPDATE `value` = `value` + 1", $name ) );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT `value` FROM `$table` WHERE `name` = %s", $name ) );
	}

	/**
	 * Card number.
	 *
	 * @param string $version Card version.
	 * @return string
	 */
	public static function next_card_no( $version = '' ) {
		$prefix = '不发卡' === $version ? 'NC' : 'C';
		return $prefix . str_pad( (string) self::next_sequence( 'card' ), 4, '0', STR_PAD_LEFT );
	}

	/**
	 * Receive number.
	 *
	 * @return string
	 */
	public static function next_receive_no() {
		return 'R' . str_pad( (string) self::next_sequence( 'receive' ), 4, '0', STR_PAD_LEFT );
	}

	/**
	 * Request number.
	 *
	 * @return string
	 */
	public static function next_request_no() {
		return 'REQ-' . gmdate( 'Ymd' ) . '-' . str_pad( (string) self::next_sequence( 'request' ), 4, '0', STR_PAD_LEFT );
	}

	/**
	 * Allowed column whitelist.
	 *
	 * @param string $column Column.
	 * @return string|false
	 */
	public static function column( $column ) {
		$allowed = array(
			'id',
			'card_no',
			'request_no',
			'receive_no',
			'call_sign',
			'qso_id',
			'card_id',
			'status',
			'scene',
			'qso_date',
			'created_at',
			'version',
		);
		return in_array( $column, $allowed, true ) ? $column : false;
	}

	/**
	 * Paged rows.
	 *
	 * @param string $table   Logical table.
	 * @param string $where   Prepared where without WHERE.
	 * @param array  $params  Prepare params.
	 * @param string $orderby Safe order by.
	 * @param int    $page    Page.
	 * @param int    $per     Per page.
	 * @return array
	 */
	public static function paged( $table, $where = '1=1', $params = array(), $orderby = 'id DESC', $page = 1, $per = 20 ) {
		global $wpdb;
		$sql   = 'SELECT * FROM ' . self::table( $table ) . ' WHERE ' . $where . ' ORDER BY ' . $orderby;
		$sql  .= ' LIMIT %d OFFSET %d';
		$args  = array_merge( (array) $params, array( $per, max( 0, ( $page - 1 ) * $per ) ) );
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
	}

	/**
	 * Reset a sequence to at least a value.
	 *
	 * @param string $name  Sequence.
	 * @param int    $value Value.
	 */
	public static function ensure_sequence( $name, $value ) {
		global $wpdb;
		$table = self::table( 'sequences' );
		$wpdb->query( $wpdb->prepare( "INSERT INTO `$table` (`name`, `value`) VALUES (%s, %d) ON DUPLICATE KEY UPDATE `value` = GREATEST(`value`, %d)", $name, $value, $value ) );
	}
}
