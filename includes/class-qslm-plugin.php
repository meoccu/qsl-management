<?php
/**
 * Plugin bootstrap.
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin.
 */
final class QSLM_Plugin {

	/**
	 * Instance.
	 *
	 * @var QSLM_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return QSLM_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init.
	 */
	public function init() {
		if ( is_admin() ) {
			QSLM_Admin::init();
			QSLM_Import_Export::init();
		}
		QSLM_Public::init();
		add_filter( 'plugin_action_links_' . plugin_basename( QSLM_FILE ), array( $this, 'action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
	}

	/**
	 * Plugin row attribution under the original author line.
	 *
	 * @param array  $links Links.
	 * @param string $file  Plugin file.
	 * @return array
	 */
	public function row_meta( $links, $file ) {
		if ( plugin_basename( QSLM_FILE ) !== $file ) {
			return $links;
		}
		$links[] = '原作者：<a href="https://github.com/bi1kbu" target="_blank" rel="noopener noreferrer">bi1kbu</a>';
		$links[] = 'GitHub 仓库：<a href="https://github.com/bi1kbu/qsl-management" target="_blank" rel="noopener noreferrer">bi1kbu/qsl-management</a>';
		$links[] = 'WordPress 移植：砂糖橘猫 · <a href="https://www.luna.tel" target="_blank" rel="noopener noreferrer">www.luna.tel</a>';
		return $links;
	}

	/**
	 * Plugin action links.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=qslm-settings' ) ) . '">设置</a>' );
		return $links;
	}
}
