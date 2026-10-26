<?php
/**
 * Settings.
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings helper.
 */
class QSLM_Settings {

	const OPTION = 'qslm_settings';

	/**
	 * Defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'station_call'      => 'BI1KBU',
			'station_name'      => '',
			'station_email'     => '',
			'station_address'   => '',
			'guest_limit'       => 30,
			'online_policy'     => 'MANUAL',
			'online_cooldown'   => 5,
			'card_remarks'      => '通联愉快，期待空中常见。',
			'online_remarks'    => '期待与您空中相遇。',
			'remove_data'       => 0,
			'public_page_note'  => '',
			'turnstile_enabled' => 0,
			'turnstile_site_key'=> '',
			'turnstile_secret'  => '',
			'turnstile_theme'   => 'auto',
		);
	}

	/**
	 * All settings.
	 *
	 * @return array
	 */
	public static function all() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key     Key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public static function get( $key, $default = '' ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Save.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function save( $input ) {
		$current = self::all();
		$secret  = sanitize_text_field( $input['turnstile_secret'] ?? '' );
		if ( '' === $secret ) {
			$secret = (string) $current['turnstile_secret'];
		}
		$clean   = array(
			'station_call'     => strtoupper( sanitize_text_field( $input['station_call'] ?? '' ) ),
			'station_name'     => sanitize_text_field( $input['station_name'] ?? '' ),
			'station_email'    => sanitize_email( $input['station_email'] ?? '' ),
			'station_address'  => sanitize_textarea_field( $input['station_address'] ?? '' ),
			'guest_limit'      => max( 1, min( 300, absint( $input['guest_limit'] ?? 30 ) ) ),
			'online_policy'    => in_array( $input['online_policy'] ?? 'MANUAL', array( 'DISABLED', 'MANUAL', 'AUTO_APPROVE' ), true ) ? $input['online_policy'] : 'MANUAL',
			'online_cooldown'  => max( 0, min( 1440, absint( $input['online_cooldown'] ?? 5 ) ) ),
			'card_remarks'     => sanitize_textarea_field( $input['card_remarks'] ?? '' ),
			'online_remarks'   => sanitize_textarea_field( $input['online_remarks'] ?? '' ),
			'remove_data'      => empty( $input['remove_data'] ) ? 0 : 1,
			'public_page_note' => sanitize_textarea_field( $input['public_page_note'] ?? '' ),
			'turnstile_enabled'=> empty( $input['turnstile_enabled'] ) ? 0 : 1,
			'turnstile_site_key' => sanitize_text_field( $input['turnstile_site_key'] ?? '' ),
			'turnstile_secret'   => $secret,
			'turnstile_theme'    => in_array( $input['turnstile_theme'] ?? 'auto', array( 'auto', 'light', 'dark' ), true ) ? $input['turnstile_theme'] : 'auto',
		);
		update_option( self::OPTION, $clean, false );
		return $clean;
	}

	/**
	 * Station cards.
	 *
	 * @param bool $only_active Only active.
	 * @return array
	 */
	public static function station_cards( $only_active = true ) {
		$rows = QSLM_DB::paged( 'station_cards', $only_active ? 'active = 1' : '1=1', array(), 'sort_order ASC, id ASC', 1, 200 );
		return $rows;
	}
}
