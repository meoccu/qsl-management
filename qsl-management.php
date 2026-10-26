<?php
/**
 * Plugin Name:       QSL 卡片管理系统
 * Plugin URI:        https://github.com/bi1kbu/qsl-management
 * Description:       业余无线电 QSO/SWL、QSL 卡片、线上/线下换卡、收卡与公开查询管理。WordPress 版移植。
 * Version:           1.0.3
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            bi1kbu
 * Author URI:        https://github.com/bi1kbu
 * Original Author:   bi1kbu
 * Source Repository: https://github.com/bi1kbu/qsl-management
 * Port Author:       砂糖橘猫
 * Port Author URI:   https://www.luna.tel
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       qsl-management
 * Domain Path:       /languages
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

define( 'QSLM_VERSION', '1.0.3' );
define( 'QSLM_FILE', __FILE__ );
define( 'QSLM_DIR', plugin_dir_path( __FILE__ ) );
define( 'QSLM_URL', plugin_dir_url( __FILE__ ) );

require_once QSLM_DIR . 'includes/class-qslm-db.php';
require_once QSLM_DIR . 'includes/class-qslm-install.php';
require_once QSLM_DIR . 'includes/class-qslm-audit.php';
require_once QSLM_DIR . 'includes/class-qslm-settings.php';
require_once QSLM_DIR . 'includes/class-qslm-admin.php';
require_once QSLM_DIR . 'includes/class-qslm-public.php';
require_once QSLM_DIR . 'includes/class-qslm-import-export.php';
require_once QSLM_DIR . 'includes/class-qslm-plugin.php';

register_activation_hook( __FILE__, array( 'QSLM_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'QSLM_Install', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'qsl-management', false, dirname( plugin_basename( QSLM_FILE ) ) . '/languages' );
		QSLM_Install::maybe_upgrade();
		QSLM_Plugin::instance()->init();
	}
);
