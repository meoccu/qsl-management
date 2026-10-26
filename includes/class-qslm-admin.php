<?php
/**
 * Admin screens and actions.
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin controller.
 */
class QSLM_Admin {

	/**
	 * Last card creation error.
	 *
	 * @var string
	 */
	private static $card_error = '';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_qslm_admin', array( __CLASS__, 'handle' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_menu_page( 'QSL 卡片管理', 'QSL 管理', 'manage_qslm', 'qslm', array( __CLASS__, 'dashboard' ), 'dashicons-email-alt', 26 );
		add_submenu_page( 'qslm', '总览', '总览', 'manage_qslm', 'qslm', array( __CLASS__, 'dashboard' ) );
		add_submenu_page( 'qslm', '通联记录', '通联记录', 'manage_qslm', 'qslm-qso', array( __CLASS__, 'qso' ) );
		add_submenu_page( 'qslm', '本台卡片样式', '本台卡片样式', 'manage_qslm', 'qslm-station-cards', array( __CLASS__, 'station_cards' ) );
		add_submenu_page( 'qslm', '卡片记录', '卡片记录', 'manage_qslm', 'qslm-cards', array( __CLASS__, 'cards' ) );
		add_submenu_page( 'qslm', '实体卡申请', '实体卡申请', 'manage_qslm', 'qslm-requests', array( __CLASS__, 'requests' ) );
		add_submenu_page( 'qslm', '线上换卡', '线上换卡', 'manage_qslm', 'qslm-exchanges', array( __CLASS__, 'exchanges' ) );
		add_submenu_page( 'qslm', '收卡记录', '收卡记录', 'manage_qslm', 'qslm-receives', array( __CLASS__, 'receives' ) );
		add_submenu_page( 'qslm', '地址簿', '地址簿', 'manage_qslm', 'qslm-addresses', array( __CLASS__, 'addresses' ) );
		add_submenu_page( 'qslm', '卡片局', '卡片局', 'manage_qslm', 'qslm-bureaus', array( __CLASS__, 'bureaus' ) );
		add_submenu_page( 'qslm', '设置', '设置', 'manage_qslm', 'qslm-settings', array( __CLASS__, 'settings' ) );
		add_submenu_page( 'qslm', '导入导出', '导入导出', 'manage_qslm', 'qslm-import-export', array( __CLASS__, 'import_export' ) );
		add_submenu_page( 'qslm', '审计日志', '审计日志', 'manage_qslm', 'qslm-audit', array( __CLASS__, 'audit' ) );
	}

	/**
	 * Assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function assets( $hook ) {
		if ( false === strpos( $hook, 'qslm' ) ) {
			return;
		}
		wp_enqueue_style( 'qslm-admin', QSLM_URL . 'assets/admin.css', array(), QSLM_VERSION );
		wp_enqueue_media();
	}

	/**
	 * Admin URL.
	 *
	 * @param string $page Page slug.
	 * @param array  $args Extra args.
	 * @return string
	 */
	public static function url( $page, $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Redirect to page.
	 *
	 * @param string $page   Page.
	 * @param string $notice Notice code.
	 * @param array  $args   Extra.
	 */
	private static function redirect( $page, $notice = '', $args = array() ) {
		$args['qslm_notice'] = $notice;
		wp_safe_redirect( self::url( $page, $args ) );
		exit;
	}

	/**
	 * Notice.
	 */
	private static function notice() {
		$code = isset( $_GET['qslm_notice'] ) ? sanitize_key( wp_unslash( $_GET['qslm_notice'] ) ) : '';
		if ( ! $code ) {
			return;
		}
		$messages = array(
			'saved'      => '已保存。',
			'deleted'    => '已删除。',
			'created'    => '已创建。',
			'updated'    => '状态已更新。',
			'imported'   => '导入完成。',
			'error'      => '操作失败，请检查输入。',
			'not_found'  => '未找到记录。',
		);
		$class = 'error' === $code ? 'notice-error' : 'notice-success';
		printf( '<div class="notice %s is-dismissible"><p>%s</p>', esc_attr( $class ), esc_html( $messages[ $code ] ?? '操作完成。' ) );
		$reason = isset( $_GET['qslm_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['qslm_reason'] ) ) : '';
		if ( $reason ) {
			echo '<p>' . esc_html( $reason ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Page open.
	 *
	 * @param string $title Title.
	 * @param string $desc  Description.
	 */
	private static function open( $title, $desc = '' ) {
		echo '<div class="wrap qslm-wrap"><h1>' . esc_html( $title ) . '</h1>';
		echo '<p class="qslm-attribution">'
			. '原作者：<a href="https://github.com/bi1kbu" target="_blank" rel="noopener noreferrer">bi1kbu</a>'
			. ' · GitHub 仓库：<a href="https://github.com/bi1kbu/qsl-management" target="_blank" rel="noopener noreferrer">bi1kbu/qsl-management</a>'
			. ' · WordPress 移植：砂糖橘猫'
			. ' · <a href="https://www.luna.tel" target="_blank" rel="noopener noreferrer">www.luna.tel</a>'
			. '</p>';
		if ( $desc ) {
			echo '<p class="qslm-muted">' . esc_html( $desc ) . '</p>';
		}
		self::notice();
	}

	/**
	 * Page close.
	 */
	private static function close() {
		echo '</div>';
	}

	/**
	 * Field row.
	 *
	 * @param string $label Label.
	 * @param string $name  Name.
	 * @param mixed  $value Value.
	 * @param string $type  Type.
	 * @param array  $opts  Options.
	 */
	private static function field( $label, $name, $value = '', $type = 'text', $opts = array() ) {
		echo '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'textarea' === $type ) {
			printf( '<textarea class="large-text" rows="4" id="%1$s" name="%1$s">%2$s</textarea>', esc_attr( $name ), esc_textarea( (string) $value ) );
		} elseif ( 'select' === $type ) {
			printf( '<select id="%1$s" name="%1$s">', esc_attr( $name ) );
			foreach ( $opts as $key => $text ) {
				printf( '<option value="%s" %s>%s</option>', esc_attr( (string) $key ), selected( (string) $value, (string) $key, false ), esc_html( $text ) );
			}
			echo '</select>';
		} elseif ( 'checkbox' === $type ) {
			printf( '<label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s> %3$s</label>', esc_attr( $name ), checked( (bool) $value, true, false ), esc_html( $opts['label'] ?? '' ) );
		} elseif ( 'date' === $type ) {
			printf( '<input type="date" id="%1$s" name="%1$s" value="%2$s" class="regular-text">', esc_attr( $name ), esc_attr( (string) $value ) );
		} elseif ( 'number' === $type ) {
			printf( '<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text">', esc_attr( $name ), esc_attr( (string) $value ) );
		} else {
			printf( '<input type="%3$s" id="%1$s" name="%1$s" value="%2$s" class="regular-text">', esc_attr( $name ), esc_attr( (string) $value ), esc_attr( $type ) );
		}
		if ( ! empty( $opts['help'] ) ) {
			echo '<p class="description">' . esc_html( $opts['help'] ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Nonce fields for admin action.
	 *
	 * @param string $task Task.
	 */
	private static function nonce( $task ) {
		wp_nonce_field( 'qslm_admin' );
		printf( '<input type="hidden" name="task" value="%s">', esc_attr( $task ) );
		printf( '<input type="hidden" name="return_page" value="%s">', esc_attr( sanitize_key( $_GET['page'] ?? 'qslm' ) ) );
	}

	/**
	 * Delete URL.
	 *
	 * @param string $task Task.
	 * @param int    $id   ID.
	 * @param string $page Page.
	 * @return string
	 */
	private static function action_url( $task, $id, $page ) {
		return wp_nonce_url( add_query_arg( array( 'action' => 'qslm_admin', 'task' => $task, 'id' => $id, 'return_page' => $page ), admin_url( 'admin-post.php' ) ), 'qslm_admin' );
	}

	/**
	 * Pagination.
	 *
	 * @param int    $total Total.
	 * @param int    $page  Current.
	 * @param string $slug  Page slug.
	 * @param array  $args  Extra.
	 */
	private static function pagination( $total, $page, $slug, $args = array() ) {
		$pages = (int) ceil( $total / 20 );
		if ( $pages < 2 ) {
			return;
		}
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(
			paginate_links(
				array(
					'base'      => add_query_arg( array_merge( array( 'page' => $slug, 'paged' => '%#%' ), $args ), admin_url( 'admin.php' ) ),
					'format'    => '',
					'current'   => max( 1, $page ),
					'total'     => $pages,
					'prev_text' => '«',
					'next_text' => '»',
				)
			)
		) . '</div></div>';
	}

	/**
	 * Status badge.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function badge( $status ) {
		$class = 'qslm-badge';
		if ( in_array( $status, array( '已签收', '已收卡', '已通过', '已发卡' ), true ) ) {
			$class .= ' is-success';
		} elseif ( in_array( $status, array( '已拒绝', '异常' ), true ) ) {
			$class .= ' is-danger';
		} elseif ( in_array( $status, array( '待审核', '待制卡', '待发卡' ), true ) ) {
			$class .= ' is-warning';
		}
		return '<span class="' . esc_attr( $class ) . '">' . esc_html( $status ) . '</span>';
	}

	/**
	 * Dashboard.
	 */
	public static function dashboard() {
		self::open( 'QSL 总览', '从通联到收发卡片的闭环概览。' );
		$cards   = QSLM_DB::count( 'cards' );
		$qso     = QSLM_DB::count( 'qso' );
		$pending = QSLM_DB::count( 'card_requests', "status = '待审核'" );
		$online  = QSLM_DB::count( 'exchange_requests', "status = '待审核'" );
		$recv    = QSLM_DB::count( 'receives' );
		?>
		<div class="qslm-stats">
			<div class="qslm-stat"><span>通联记录</span><strong><?php echo esc_html( number_format_i18n( $qso ) ); ?></strong></div>
			<div class="qslm-stat"><span>卡片记录</span><strong><?php echo esc_html( number_format_i18n( $cards ) ); ?></strong></div>
			<div class="qslm-stat"><span>待审核申请</span><strong><?php echo esc_html( number_format_i18n( $pending ) ); ?></strong></div>
			<div class="qslm-stat"><span>待审核换卡</span><strong><?php echo esc_html( number_format_i18n( $online ) ); ?></strong></div>
			<div class="qslm-stat"><span>收卡记录</span><strong><?php echo esc_html( number_format_i18n( $recv ) ); ?></strong></div>
		</div>
		<div class="qslm-grid-2">
			<div class="qslm-panel">
				<h2>快捷入口</h2>
				<p><a class="button button-primary" href="<?php echo esc_url( self::url( 'qslm-qso' ) ); ?>">新增通联记录</a>
				<a class="button" href="<?php echo esc_url( self::url( 'qslm-station-cards' ) ); ?>">新建卡片样式</a>
				<a class="button" href="<?php echo esc_url( self::url( 'qslm-cards' ) ); ?>">创建卡片记录</a>
				<a class="button" href="<?php echo esc_url( self::url( 'qslm-requests' ) ); ?>">审核实体卡申请</a>
				<a class="button" href="<?php echo esc_url( self::url( 'qslm-exchanges' ) ); ?>">审核线上换卡</a></p>
			</div>
			<div class="qslm-panel">
				<h2>公开入口</h2>
				<p class="qslm-muted">把短码放入任意页面：</p>
				<code>[qsl-card]</code><br><code>[qsl-online-exchange-card]</code><br><code>[qsl-offline-exchange-card]</code><br><code>[qsl-receipt-card]</code>
			</div>
		</div>
		<?php
		self::close();
	}

	/**
	 * QSO page.
	 */
	public static function qso() {
		global $wpdb;
		$edit = isset( $_GET['edit'] ) ? QSLM_DB::get( 'qso', absint( $_GET['edit'] ) ) : null;
		$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$s    = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$where = '1=1';
		$args  = array();
		if ( $s ) {
			$where .= ' AND (call_sign LIKE %s OR qth LIKE %s OR comment LIKE %s)';
			$like   = '%' . $wpdb->esc_like( $s ) . '%';
			$args   = array( $like, $like, $like );
		}
		$total = QSLM_DB::count( 'qso', $where, $args );
		$rows  = QSLM_DB::paged( 'qso', $where, $args, 'qso_date DESC, id DESC', $page, 20 );
		self::open( $edit ? '编辑通联记录' : '通联记录', 'QSO / SWL 记录是卡片业务的起点。' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qslm-form-card">
			<input type="hidden" name="action" value="qslm_admin">
			<input type="hidden" name="id" value="<?php echo esc_attr( $edit['id'] ?? 0 ); ?>">
			<?php self::nonce( 'save_qso' ); ?>
			<table class="form-table">
				<?php
				self::field( '场景', 'scene', $edit['scene'] ?? 'QSO', 'select', array( 'QSO' => 'QSO 通联', 'SWL' => 'SWL 收听' ) );
				self::field( '对方呼号', 'call_sign', $edit['call_sign'] ?? '', 'text' );
				self::field( '通联日期', 'qso_date', $edit['qso_date'] ?? current_time( 'Y-m-d' ), 'date' );
				self::field( '时间', 'time_on', $edit['time_on'] ?? '', 'text' );
				self::field( '时区', 'timezone', $edit['timezone'] ?? 'UTC+8', 'text' );
				self::field( '模式', 'mode', $edit['mode'] ?? '', 'text' );
				self::field( '频率', 'frequency', $edit['frequency'] ?? '', 'text' );
				self::field( '波段', 'band', $edit['band'] ?? '', 'text' );
				self::field( '我方 RST', 'rst_sent', $edit['rst_sent'] ?? '59', 'text' );
				self::field( '对方 RST', 'rst_received', $edit['rst_received'] ?? '59', 'text' );
				self::field( '对方 QTH', 'qth', $edit['qth'] ?? '', 'text' );
				self::field( '网格', 'grid', $edit['grid'] ?? '', 'text' );
				self::field( '我方设备', 'my_rig', $edit['my_rig'] ?? '', 'text' );
				self::field( '我方天线', 'my_antenna', $edit['my_antenna'] ?? '', 'text' );
				self::field( '发射功率', 'tx_power', $edit['tx_power'] ?? '', 'text' );
				self::field( '操作员', 'operator', $edit['operator'] ?? '', 'text' );
				self::field( '备注', 'comment', $edit['comment'] ?? '', 'textarea' );
				?>
			</table>
			<p><button class="button button-primary" type="submit"><?php echo $edit ? '保存修改' : '新增记录'; ?></button>
			<?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( self::url( 'qslm-qso' ) ); ?>">取消</a><?php endif; ?></p>
		</form>

		<form method="get" class="qslm-toolbar">
			<input type="hidden" name="page" value="qslm-qso">
			<input type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="呼号 / QTH / 备注">
			<button class="button">搜索</button>
			<a class="button" href="<?php echo esc_url( self::url( 'qslm-qso' ) ); ?>">重置</a>
		</form>

		<table class="widefat fixed striped">
			<thead><tr><th>ID</th><th>呼号</th><th>场景</th><th>日期</th><th>模式/频率</th><th>QTH</th><th>操作</th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="7">暂无记录。</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row['id'] ); ?></td>
					<td><strong><?php echo esc_html( $row['call_sign'] ); ?></strong></td>
					<td><?php echo esc_html( $row['scene'] ); ?></td>
					<td><?php echo esc_html( $row['qso_date'] ); ?><br><small><?php echo esc_html( $row['time_on'] ); ?></small></td>
					<td><?php echo esc_html( $row['mode'] ); ?><br><small><?php echo esc_html( $row['frequency'] ); ?></small></td>
					<td><?php echo esc_html( $row['qth'] ); ?></td>
					<td>
						<a class="button button-small" href="<?php echo esc_url( self::url( 'qslm-qso', array( 'edit' => $row['id'] ) ) ); ?>">编辑</a>
						<a class="button button-small qslm-danger" href="<?php echo esc_url( self::action_url( 'delete_qso', $row['id'], 'qslm-qso' ) ); ?>" onclick="return confirm('确定删除这条通联记录？');">删除</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php self::pagination( $total, $page, 'qslm-qso', $s ? array( 's' => $s ) : array() ); ?>
		<?php
		self::close();
	}

	/**
	 * Cards page.
	 */
	public static function cards() {
		global $wpdb;
		$page  = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$s     = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$where = '1=1';
		$args  = array();
		if ( $s ) {
			$where .= ' AND (call_sign LIKE %s OR card_no LIKE %s)';
			$like   = '%' . $wpdb->esc_like( $s ) . '%';
			$args   = array( $like, $like );
		}
		$total = QSLM_DB::count( 'cards', $where, $args );
		$rows  = QSLM_DB::paged( 'cards', $where, $args, 'id DESC', $page, 20 );
		$qso_candidates = $wpdb->get_results( 'SELECT q.* FROM ' . QSLM_DB::table( 'qso' ) . ' q LEFT JOIN ' . QSLM_DB::table( 'cards' ) . ' c ON c.qso_id = q.id WHERE c.id IS NULL ORDER BY q.qso_date DESC, q.id DESC LIMIT 200', ARRAY_A );
		$versions = QSLM_Settings::station_cards();
		self::open( '卡片记录', '正式卡片记录必须关联通联记录；卡片样式请先在「本台卡片样式」配置。' );
		echo '<div class="qslm-panel qslm-workflow"><strong>流程：</strong>本台卡片样式 → 访客选择样式并提交线上换卡申请 → 管理员审核 → 审核通过后创建正式卡片记录。';
		echo '<br><a class="button button-small" href="' . esc_url( self::url( 'qslm-station-cards' ) ) . '">先管理卡片样式</a></div>';
		if ( ! $qso_candidates ) {
			echo '<div class="notice notice-warning"><p>暂无可建卡通联记录：请先在「通联记录」新增 QSO/SWL，并确认该记录还没有卡片。</p></div>';
		}
		if ( ! $versions ) {
			echo '<div class="notice notice-warning"><p>尚未配置可用的本台卡片版本，正式卡片无法创建；请先到「本台卡片」新增版本与库存。</p></div>';
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qslm-form-card">
			<input type="hidden" name="action" value="qslm_admin">
			<?php self::nonce( 'create_card' ); ?>
			<h2>从通联记录创建卡片</h2>
			<table class="form-table">
				<tr><th><label for="qso_id">通联记录</label></th><td>
					<select name="qso_id" id="qso_id" class="regular-text">
						<option value="">选择尚未建卡的通联记录</option>
						<?php foreach ( $qso_candidates as $qso ) : ?>
							<option value="<?php echo esc_attr( $qso['id'] ); ?>">#<?php echo esc_html( $qso['id'] ); ?> · <?php echo esc_html( $qso['call_sign'] ); ?> · <?php echo esc_html( $qso['qso_date'] ); ?> · <?php echo esc_html( $qso['mode'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td></tr>
				<tr><th><label for="card_version">卡片版本</label></th><td>
					<select name="card_version" id="card_version" required>
						<option value="" disabled selected>请选择卡片版本</option>
						<option value="不发卡">不发卡（不制作实体卡）</option>
						<?php foreach ( $versions as $version ) : ?>
							<option value="<?php echo esc_attr( $version['version'] ); ?>"><?php echo esc_html( $version['version'] ); ?>（剩余 <?php echo esc_html( max( 0, (int) $version['total'] - (int) $version['used'] ) ); ?>）</option>
						<?php endforeach; ?>
					</select>
				</td></tr>
				<?php self::field( '卡片备注', 'remarks', QSLM_Settings::get( 'card_remarks' ), 'textarea' ); ?>
			</table>
			<p><button class="button button-primary" type="submit">创建卡片</button></p>
		</form>

		<form method="get" class="qslm-toolbar">
			<input type="hidden" name="page" value="qslm-cards">
			<input type="search" name="s" value="<?php echo esc_attr( $s ); ?>" placeholder="呼号 / 卡片编号">
			<button class="button">搜索</button>
		</form>

		<table class="widefat fixed striped">
			<thead><tr><th>卡片编号</th><th>呼号</th><th>场景</th><th>版本</th><th>状态</th><th>制卡/发卡</th><th>操作</th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="7">暂无卡片。</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $row['card_no'] ); ?></strong></td>
					<td><?php echo esc_html( $row['call_sign'] ); ?></td>
					<td><?php echo esc_html( $row['scene'] ); ?></td>
					<td><?php echo esc_html( $row['card_version'] ); ?></td>
					<td><?php echo wp_kses_post( self::badge( $row['status'] ) ); ?></td>
					<td><small>制卡：<?php echo $row['card_issued'] ? '是' : '否'; ?><br>发卡：<?php echo $row['card_sent'] ? '是' : '否'; ?><br>签收：<?php echo $row['receipt_confirmed'] ? '是' : '否'; ?></small></td>
					<td class="qslm-actions">
						<?php if ( ! (int) $row['card_issued'] ) : ?><a class="button button-small" href="<?php echo esc_url( self::action_url( 'card_issue', $row['id'], 'qslm-cards' ) ); ?>">确认制卡</a><?php endif; ?>
						<?php if ( ! (int) $row['card_sent'] ) : ?><a class="button button-small" href="<?php echo esc_url( self::action_url( 'card_send', $row['id'], 'qslm-cards' ) ); ?>">确认发卡</a><?php endif; ?>
						<?php if ( ! (int) $row['receipt_confirmed'] && (int) $row['card_sent'] ) : ?><a class="button button-small" href="<?php echo esc_url( self::action_url( 'card_receipt', $row['id'], 'qslm-cards' ) ); ?>">确认签收</a><?php endif; ?>
						<a class="button button-small qslm-danger" href="<?php echo esc_url( self::action_url( 'delete_card', $row['id'], 'qslm-cards' ) ); ?>" onclick="return confirm('确定删除这张卡片？');">删除</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php self::pagination( $total, $page, 'qslm-cards', $s ? array( 's' => $s ) : array() ); ?>
		<?php
		self::close();
	}

	/**
	 * Card requests.
	 */
	public static function requests() {
		$page  = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$total = QSLM_DB::count( 'card_requests' );
		$rows  = QSLM_DB::paged( 'card_requests', '1=1', array(), 'id DESC', $page, 20 );
		self::open( '实体卡申请审核', '公开页面提交的实体 QSL 卡申请。' );
		?>
		<table class="widefat fixed striped">
			<thead><tr><th>申请号</th><th>呼号</th><th>QSO</th><th>卡片版本</th><th>状态</th><th>地址</th><th>操作</th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="7">暂无申请。</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<?php $address = json_decode( (string) $row['address_json'], true ) ?: array(); ?>
				<tr>
					<td><?php echo esc_html( $row['request_no'] ); ?></td>
					<td><?php echo esc_html( $row['call_sign'] ); ?></td>
					<td>#<?php echo esc_html( $row['qso_id'] ); ?></td>
					<td><?php echo esc_html( $row['card_version'] ); ?></td>
					<td><?php echo wp_kses_post( self::badge( $row['status'] ) ); ?></td>
					<td><small><?php echo esc_html( implode( ' / ', array_filter( array( $address['name'] ?? '', $address['postal_code'] ?? '', $address['address'] ?? '' ) ) ) ); ?></small></td>
					<td class="qslm-actions">
						<?php if ( '待审核' === $row['status'] ) : ?>
							<a class="button button-small" href="<?php echo esc_url( self::action_url( 'request_approve', $row['id'], 'qslm-requests' ) ); ?>">通过</a>
							<a class="button button-small qslm-danger" href="<?php echo esc_url( self::action_url( 'request_reject', $row['id'], 'qslm-requests' ) ); ?>">拒绝</a>
						<?php endif; ?>
						<?php if ( '已通过' === $row['status'] && ! (int) $row['card_id'] ) : ?>
							<a class="button button-small button-primary" href="<?php echo esc_url( self::action_url( 'request_create_card', $row['id'], 'qslm-requests' ) ); ?>">创建卡片</a>
						<?php endif; ?>
						<?php if ( $row['card_id'] ) : ?><span class="qslm-muted">卡片 #<?php echo esc_html( $row['card_id'] ); ?></span><?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php self::pagination( $total, $page, 'qslm-requests' ); ?>
		<?php
		self::close();
	}

	/**
	 * Online exchange requests.
	 */
	public static function exchanges() {
		$page  = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$total = QSLM_DB::count( 'exchange_requests' );
		$rows  = QSLM_DB::paged( 'exchange_requests', '1=1', array(), 'id DESC', $page, 20 );
		$styles = QSLM_Settings::station_cards();
		self::open( '线上换卡审核', '访客先选择本台卡片样式提交申请；这里审核通过后再创建正式卡片记录。' );
		echo '<div class="qslm-panel qslm-workflow"><strong>顺序：</strong>① 本台卡片样式 → ② 访客提交线上换卡申请（生成申请记录） → ③ 审核通过 → ④ 创建正式卡片。';
		if ( ! $styles ) {
			echo '<br><span class="qslm-badge is-warning">尚未配置卡片样式，访客无法选择。</span> <a class="button button-small" href="' . esc_url( self::url( 'qslm-station-cards' ) ) . '">去新建样式</a>';
		}
		echo '</div>';
		?>
		<table class="widefat fixed striped">
			<thead><tr><th>申请号</th><th>呼号</th><th>版本</th><th>地址类型</th><th>状态</th><th>邮箱</th><th>操作</th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="7">暂无换卡申请。</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<?php $address = json_decode( (string) $row['address_json'], true ) ?: array(); ?>
				<tr>
					<td><?php echo esc_html( $row['request_no'] ); ?></td>
					<td><?php echo esc_html( $row['call_sign'] ); ?></td>
					<td><?php echo esc_html( $row['card_version'] ); ?></td>
					<td><?php echo esc_html( 'bureau' === $row['address_type'] ? '卡片局' : '个人地址' ); ?><br><small><?php echo esc_html( $address['name'] ?? ( $address['bureau_name'] ?? '' ) ); ?></small></td>
					<td><?php echo wp_kses_post( self::badge( $row['status'] ) ); ?></td>
					<td><?php echo esc_html( $row['email'] ); ?></td>
					<td class="qslm-actions">
						<?php if ( '待审核' === $row['status'] ) : ?>
							<a class="button button-small" href="<?php echo esc_url( self::action_url( 'exchange_approve', $row['id'], 'qslm-exchanges' ) ); ?>">通过</a>
							<a class="button button-small qslm-danger" href="<?php echo esc_url( self::action_url( 'exchange_reject', $row['id'], 'qslm-exchanges' ) ); ?>">拒绝</a>
						<?php endif; ?>
						<?php if ( '已通过' === $row['status'] && ! (int) $row['card_id'] ) : ?>
							<a class="button button-small button-primary" href="<?php echo esc_url( self::action_url( 'exchange_create_card', $row['id'], 'qslm-exchanges' ) ); ?>">创建卡片</a>
						<?php endif; ?>
						<?php if ( $row['card_id'] ) : ?><span class="qslm-muted">卡片 #<?php echo esc_html( $row['card_id'] ); ?></span><?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php self::pagination( $total, $page, 'qslm-exchanges' ); ?>
		<?php
		self::close();
	}

	/**
	 * Receives page.
	 */
	public static function receives() {
		global $wpdb;
		$cards = $wpdb->get_results( 'SELECT id, card_no, call_sign, scene FROM ' . QSLM_DB::table( 'cards' ) . ' ORDER BY id DESC LIMIT 200', ARRAY_A );
		$page  = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$total = QSLM_DB::count( 'receives' );
		$rows  = QSLM_DB::paged( 'receives', '1=1', array(), 'id DESC', $page, 20 );
		self::open( '收卡记录', '登记对方来卡，生成收卡编号并回写卡片状态。' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qslm-form-card">
			<input type="hidden" name="action" value="qslm_admin">
			<?php self::nonce( 'add_receive' ); ?>
			<table class="form-table">
				<tr><th><label for="card_id">关联卡片</label></th><td><select name="card_id" id="card_id"><?php foreach ( $cards as $card ) : ?><option value="<?php echo esc_attr( $card['id'] ); ?>"><?php echo esc_html( $card['card_no'] . ' · ' . $card['call_sign'] ); ?></option><?php endforeach; ?></select></td></tr>
				<?php self::field( '收卡日期', 'received_at', current_time( 'Y-m-d' ), 'date' ); ?>
				<?php self::field( '备注', 'note', '', 'textarea' ); ?>
			</table>
			<p><button class="button button-primary">登记收卡</button></p>
		</form>
		<table class="widefat fixed striped">
			<thead><tr><th>收卡编号</th><th>呼号</th><th>场景</th><th>卡片</th><th>收卡日期</th><th>备注</th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="6">暂无收卡记录。</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr><td><?php echo esc_html( $row['receive_no'] ); ?></td><td><?php echo esc_html( $row['call_sign'] ); ?></td><td><?php echo esc_html( $row['scene'] ); ?></td><td>#<?php echo esc_html( $row['card_id'] ); ?></td><td><?php echo esc_html( $row['received_at'] ); ?></td><td><?php echo esc_html( $row['note'] ); ?></td></tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php self::pagination( $total, $page, 'qslm-receives' ); ?>
		<?php
		self::close();
	}

	/**
	 * Addresses page.
	 */
	public static function addresses() {
		$edit = isset( $_GET['edit'] ) ? QSLM_DB::get( 'addresses', absint( $_GET['edit'] ) ) : null;
		$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$total = QSLM_DB::count( 'addresses' );
		$rows = QSLM_DB::paged( 'addresses', '1=1', array(), 'id DESC', $page, 20 );
		self::open( $edit ? '编辑地址' : '地址簿', '用于卡片寄送与换卡申请。' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qslm-form-card">
			<input type="hidden" name="action" value="qslm_admin"><input type="hidden" name="id" value="<?php echo esc_attr( $edit['id'] ?? 0 ); ?>">
			<?php self::nonce( 'save_address' ); ?>
			<table class="form-table">
				<?php self::field( '呼号', 'call_sign', $edit['call_sign'] ?? '' ); ?>
				<?php self::field( '姓名', 'name', $edit['name'] ?? '' ); ?>
				<?php self::field( '电话', 'phone', $edit['phone'] ?? '' ); ?>
				<?php self::field( '邮编', 'postal_code', $edit['postal_code'] ?? '' ); ?>
				<?php self::field( '国家/地区', 'country', $edit['country'] ?? '' ); ?>
				<?php self::field( '地址', 'address', $edit['address'] ?? '', 'textarea' ); ?>
				<?php self::field( '邮箱', 'email', $edit['email'] ?? '', 'email' ); ?>
				<?php self::field( '备注', 'note', $edit['note'] ?? '', 'textarea' ); ?>
			</table>
			<p><button class="button button-primary">保存地址</button><?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( self::url( 'qslm-addresses' ) ); ?>">取消</a><?php endif; ?></p>
		</form>
		<table class="widefat fixed striped">
			<thead><tr><th>呼号</th><th>姓名</th><th>邮编</th><th>地址</th><th>邮箱</th><th>操作</th></tr></thead>
			<tbody><?php if ( ! $rows ) : ?><tr><td colspan="6">暂无地址。</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row['call_sign'] ); ?></td><td><?php echo esc_html( $row['name'] ); ?></td><td><?php echo esc_html( $row['postal_code'] ); ?></td><td><?php echo esc_html( $row['address'] ); ?></td><td><?php echo esc_html( $row['email'] ); ?></td><td><a class="button button-small" href="<?php echo esc_url( self::url( 'qslm-addresses', array( 'edit' => $row['id'] ) ) ); ?>">编辑</a> <a class="button button-small qslm-danger" href="<?php echo esc_url( self::action_url( 'delete_address', $row['id'], 'qslm-addresses' ) ); ?>">删除</a></td></tr><?php endforeach; ?>
			</tbody>
		</table>
		<?php self::pagination( $total, $page, 'qslm-addresses' ); ?>
		<?php
		self::close();
	}

	/**
	 * Bureaus page.
	 */
	public static function bureaus() {
		$edit = isset( $_GET['edit'] ) ? QSLM_DB::get( 'bureaus', absint( $_GET['edit'] ) ) : null;
		$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$total = QSLM_DB::count( 'bureaus' );
		$rows = QSLM_DB::paged( 'bureaus', '1=1', array(), 'id DESC', $page, 20 );
		self::open( $edit ? '编辑卡片局' : '卡片局', '公开清单中的卡片局地址。' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qslm-form-card">
			<input type="hidden" name="action" value="qslm_admin"><input type="hidden" name="id" value="<?php echo esc_attr( $edit['id'] ?? 0 ); ?>">
			<?php self::nonce( 'save_bureau' ); ?>
			<table class="form-table">
				<?php self::field( '名称', 'bureau_name', $edit['name'] ?? '' ); ?>
				<?php self::field( '国家/地区', 'country', $edit['country'] ?? '' ); ?>
				<?php self::field( '邮编', 'postal_code', $edit['postal_code'] ?? '' ); ?>
				<?php self::field( '地址', 'address', $edit['address'] ?? '', 'textarea' ); ?>
				<?php self::field( '电话', 'phone', $edit['phone'] ?? '' ); ?>
				<?php self::field( '备注', 'note', $edit['note'] ?? '', 'textarea' ); ?>
			</table>
			<p><button class="button button-primary">保存卡片局</button><?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( self::url( 'qslm-bureaus' ) ); ?>">取消</a><?php endif; ?></p>
		</form>
		<table class="widefat fixed striped">
			<thead><tr><th>名称</th><th>国家/地区</th><th>邮编</th><th>地址</th><th>操作</th></tr></thead>
			<tbody><?php if ( ! $rows ) : ?><tr><td colspan="5">暂无卡片局。</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row['name'] ); ?></td><td><?php echo esc_html( $row['country'] ); ?></td><td><?php echo esc_html( $row['postal_code'] ); ?></td><td><?php echo esc_html( $row['address'] ); ?></td><td><a class="button button-small" href="<?php echo esc_url( self::url( 'qslm-bureaus', array( 'edit' => $row['id'] ) ) ); ?>">编辑</a> <a class="button button-small qslm-danger" href="<?php echo esc_url( self::action_url( 'delete_bureau', $row['id'], 'qslm-bureaus' ) ); ?>">删除</a></td></tr><?php endforeach; ?>
			</tbody>
		</table>
		<?php self::pagination( $total, $page, 'qslm-bureaus' ); ?>
		<?php
		self::close();
	}

	/**
	 * Station cards page.
	 */
	public static function station_cards() {
		$edit = isset( $_GET['edit'] ) ? QSLM_DB::get( 'station_cards', absint( $_GET['edit'] ) ) : null;
		$rows = QSLM_Settings::station_cards( false );
		self::open( '本台卡片样式', '先在这里新建访客公开申请时可选择的卡片样式与库存；它不是正式卡片记录。' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qslm-form-card">
			<input type="hidden" name="action" value="qslm_admin"><input type="hidden" name="id" value="<?php echo esc_attr( $edit['id'] ?? 0 ); ?>">
			<?php self::nonce( 'save_station_card' ); ?>
			<table class="form-table">
				<?php self::field( '版本名称', 'version', $edit['version'] ?? '' ); ?>
				<?php self::field( '总量', 'total', $edit['total'] ?? 100, 'number' ); ?>
				<?php self::field( '已用', 'used', $edit['used'] ?? 0, 'number' ); ?>
				<?php self::field( '图片 URL', 'image_url', $edit['image_url'] ?? '', 'url', array( 'help' => '公开换卡页会以卡面图片显示；留空显示带版本号的渐变占位卡面。' ) ); ?>
				<?php self::field( '排序', 'sort_order', $edit['sort_order'] ?? 0, 'number' ); ?>
				<?php self::field( '启用', 'active', $edit['active'] ?? 1, 'checkbox', array( 'label' => '公开页面可选择' ) ); ?>
			</table>
			<p><button class="button button-primary">保存版本</button><?php if ( $edit ) : ?><a class="button" href="<?php echo esc_url( self::url( 'qslm-station-cards' ) ); ?>">取消</a><?php endif; ?></p>
		</form>
		<table class="widefat fixed striped">
			<thead><tr><th>预览</th><th>版本</th><th>库存</th><th>已用</th><th>剩余</th><th>状态</th><th>操作</th></tr></thead>
			<tbody><?php foreach ( $rows as $row ) : ?><tr><td><?php if ( $row['image_url'] ) : ?><img class="qslm-style-thumb" src="<?php echo esc_url( $row['image_url'] ); ?>" alt=""><?php else : ?><span class="qslm-style-thumb qslm-style-thumb--empty">QSL</span><?php endif; ?></td><td><strong><?php echo esc_html( $row['version'] ); ?></strong></td><td><?php echo esc_html( $row['total'] ); ?></td><td><?php echo esc_html( $row['used'] ); ?></td><td><?php echo esc_html( max( 0, (int) $row['total'] - (int) $row['used'] ) ); ?></td><td><?php echo $row['active'] ? '启用' : '停用'; ?></td><td><a class="button button-small" href="<?php echo esc_url( self::url( 'qslm-station-cards', array( 'edit' => $row['id'] ) ) ); ?>">编辑</a> <a class="button button-small qslm-danger" href="<?php echo esc_url( self::action_url( 'delete_station_card', $row['id'], 'qslm-station-cards' ) ); ?>">删除</a></td></tr><?php endforeach; ?></tbody>
		</table>
		<?php
		self::close();
	}

	/**
	 * Settings page.
	 */
	public static function settings() {
		$s = QSLM_Settings::all();
		self::open( '设置', '本台资料、公开策略和默认备注。' );
		if ( ! empty( $s['turnstile_enabled'] ) && ( '' === (string) $s['turnstile_site_key'] || '' === (string) $s['turnstile_secret'] ) ) {
			echo '<div class="notice notice-warning"><p>Turnstile 已启用但缺少 Site Key 或 Secret Key，公开换卡表单不会显示验证框。</p></div>';
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qslm-form-card">
			<input type="hidden" name="action" value="qslm_admin">
			<?php self::nonce( 'save_settings' ); ?>
			<table class="form-table">
				<?php self::field( '本台呼号', 'station_call', $s['station_call'] ); ?>
				<?php self::field( '本台名称', 'station_name', $s['station_name'] ); ?>
				<?php self::field( '本台邮箱', 'station_email', $s['station_email'], 'email' ); ?>
				<?php self::field( '通信地址', 'station_address', $s['station_address'], 'textarea' ); ?>
				<?php self::field( '公开接口限流/分钟', 'guest_limit', $s['guest_limit'], 'number' ); ?>
				<?php self::field( '线上换卡策略', 'online_policy', $s['online_policy'], 'select', array( 'DISABLED' => '关闭', 'MANUAL' => '手动审核', 'AUTO_APPROVE' => '自动通过' ) ); ?>
				<?php self::field( '同呼号冷却分钟', 'online_cooldown', $s['online_cooldown'], 'number' ); ?>
				<?php self::field( '通联卡片默认备注', 'card_remarks', $s['card_remarks'], 'textarea' ); ?>
				<?php self::field( '线上换卡默认备注', 'online_remarks', $s['online_remarks'], 'textarea' ); ?>
				<?php self::field( '公开页面说明', 'public_page_note', $s['public_page_note'], 'textarea' ); ?>
				<?php self::field( '启用 Turnstile', 'turnstile_enabled', $s['turnstile_enabled'], 'checkbox', array( 'label' => '线上换卡、线下换卡公开表单启用 Cloudflare Turnstile' ) ); ?>
				<?php self::field( 'Turnstile Site Key', 'turnstile_site_key', $s['turnstile_site_key'], 'text' ); ?>
				<?php self::field( 'Turnstile Secret Key', 'turnstile_secret', '', 'password', array( 'help' => '已保存时留空即可保持不变；只保存在后台设置中。' ) ); ?>
				<?php self::field( 'Turnstile 主题', 'turnstile_theme', $s['turnstile_theme'], 'select', array( 'auto' => '自动', 'light' => '浅色', 'dark' => '深色' ) ); ?>
				<?php self::field( '卸载时删除数据', 'remove_data', $s['remove_data'], 'checkbox', array( 'label' => '删除插件数据表与设置（不可恢复）' ) ); ?>
			</table>
			<p><button class="button button-primary">保存设置</button></p>
		</form>
		<?php
		self::close();
	}

	/**
	 * Import/export page.
	 */
	public static function import_export() {
		self::open( '导入导出', 'JSON 备份包含全部业务表与设置；CSV 仅导出通联记录。' );
		?>
		<div class="qslm-grid-2">
			<div class="qslm-panel">
				<h2>导出</h2>
				<p><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'qslm_export', 'type' => 'json' ), admin_url( 'admin-post.php' ) ), 'qslm_export' ) ); ?>">下载 JSON 备份</a></p>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'qslm_export', 'type' => 'qso_csv' ), admin_url( 'admin-post.php' ) ), 'qslm_export' ) ); ?>">导出通联 CSV</a></p>
			</div>
			<div class="qslm-panel">
				<h2>导入 JSON</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="qslm_admin">
					<?php self::nonce( 'import_json' ); ?>
					<p><input type="file" name="qslm_json" accept="application/json,.json" required></p>
					<p><label><input type="checkbox" name="confirm_import" value="1" required> 我确认导入会覆盖同 ID 数据</label></p>
					<p><button class="button">开始导入</button></p>
				</form>
			</div>
		</div>
		<?php
		self::close();
	}

	/**
	 * Audit page.
	 */
	public static function audit() {
		$page  = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$total = QSLM_DB::count( 'audit' );
		$rows  = QSLM_DB::paged( 'audit', '1=1', array(), 'id DESC', $page, 30 );
		self::open( '审计日志', '记录后台与公开表单的关键写操作。' );
		?>
		<table class="widefat fixed striped">
			<thead><tr><th>时间</th><th>操作</th><th>对象</th><th>对象 ID</th><th>用户</th><th>说明</th></tr></thead>
			<tbody><?php if ( ! $rows ) : ?><tr><td colspan="6">暂无日志。</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr><td><?php echo esc_html( $row['created_at'] ); ?></td><td><?php echo esc_html( $row['action'] ); ?></td><td><?php echo esc_html( $row['object_type'] ); ?></td><td><?php echo esc_html( $row['object_id'] ); ?></td><td><?php echo esc_html( $row['user_id'] ); ?></td><td><?php echo esc_html( $row['message'] ); ?></td></tr>
			<?php endforeach; ?></tbody>
		</table>
		<?php self::pagination( $total, $page, 'qslm-audit' ); ?>
		<?php
		self::close();
	}

	/**
	 * Admin action router.
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_qslm' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'qsl-management' ) );
		}
		check_admin_referer( 'qslm_admin' );
		$task = sanitize_key( wp_unslash( $_REQUEST['task'] ?? '' ) );
		$id   = absint( $_REQUEST['id'] ?? 0 );
		$back = sanitize_key( wp_unslash( $_REQUEST['return_page'] ?? 'qslm' ) );

		switch ( $task ) {
			case 'save_qso':
				$data = array(
					'scene'        => in_array( $_POST['scene'] ?? 'QSO', array( 'QSO', 'SWL' ), true ) ? $_POST['scene'] : 'QSO',
					'call_sign'    => strtoupper( sanitize_text_field( wp_unslash( $_POST['call_sign'] ?? '' ) ) ),
					'qso_date'     => sanitize_text_field( wp_unslash( $_POST['qso_date'] ?? current_time( 'Y-m-d' ) ) ),
					'time_on'      => sanitize_text_field( wp_unslash( $_POST['time_on'] ?? '' ) ),
					'timezone'     => sanitize_text_field( wp_unslash( $_POST['timezone'] ?? 'UTC+8' ) ),
					'mode'         => sanitize_text_field( wp_unslash( $_POST['mode'] ?? '' ) ),
					'frequency'    => sanitize_text_field( wp_unslash( $_POST['frequency'] ?? '' ) ),
					'band'         => sanitize_text_field( wp_unslash( $_POST['band'] ?? '' ) ),
					'rst_sent'     => sanitize_text_field( wp_unslash( $_POST['rst_sent'] ?? '' ) ),
					'rst_received' => sanitize_text_field( wp_unslash( $_POST['rst_received'] ?? '' ) ),
					'qth'          => sanitize_text_field( wp_unslash( $_POST['qth'] ?? '' ) ),
					'grid'         => sanitize_text_field( wp_unslash( $_POST['grid'] ?? '' ) ),
					'comment'      => sanitize_textarea_field( wp_unslash( $_POST['comment'] ?? '' ) ),
					'my_rig'       => sanitize_text_field( wp_unslash( $_POST['my_rig'] ?? '' ) ),
					'my_antenna'   => sanitize_text_field( wp_unslash( $_POST['my_antenna'] ?? '' ) ),
					'tx_power'     => sanitize_text_field( wp_unslash( $_POST['tx_power'] ?? '' ) ),
					'operator'     => sanitize_text_field( wp_unslash( $_POST['operator'] ?? '' ) ),
					'updated_at'   => current_time( 'mysql' ),
				);
				if ( ! $data['call_sign'] ) {
					self::redirect( $back, 'error' );
				}
				if ( $id ) {
					QSLM_DB::update( 'qso', $id, $data );
					QSLM_Audit::log( 'update_qso', 'qso', $id, $data['call_sign'] );
				} else {
					$data['created_at'] = current_time( 'mysql' );
					$data['created_by'] = get_current_user_id();
					$id = QSLM_DB::insert( 'qso', $data );
					QSLM_Audit::log( 'create_qso', 'qso', $id, $data['call_sign'] );
				}
				self::redirect( 'qslm-qso', 'saved' );

			case 'delete_qso':
				QSLM_DB::delete( 'qso', $id );
				QSLM_Audit::log( 'delete_qso', 'qso', $id, '' );
				self::redirect( 'qslm-qso', 'deleted' );

			case 'create_card':
				$qso_id = absint( $_POST['qso_id'] ?? 0 );
				$qso    = QSLM_DB::get( 'qso', $qso_id );
				if ( ! $qso ) {
					self::redirect( 'qslm-cards', 'not_found', array( 'qslm_reason' => '选择的通联记录不存在。' ) );
				}
				$version = sanitize_text_field( wp_unslash( $_POST['card_version'] ?? '' ) );
				if ( '' === $version ) {
					self::redirect( 'qslm-cards', 'error', array( 'qslm_reason' => '请选择卡片版本；如果不制作实体卡，请明确选择「不发卡」。' ) );
				}
				$card_id = self::create_card_for( $qso, $version, wp_unslash( $_POST['remarks'] ?? '' ) );
				self::redirect( 'qslm-cards', $card_id ? 'created' : 'error', $card_id ? array() : array( 'qslm_reason' => self::$card_error ) );

			case 'card_issue':
				$card = QSLM_DB::get( 'cards', $id );
				if ( $card ) {
					QSLM_DB::update( 'cards', $id, array( 'card_issued' => 1, 'card_issued_at' => current_time( 'mysql' ), 'envelope_printed' => 1, 'status' => '已制卡', 'updated_at' => current_time( 'mysql' ) ) );
					QSLM_Audit::log( 'card_issue', 'card', $id, $card['card_no'] );
				}
				self::redirect( 'qslm-cards', 'updated' );

			case 'card_send':
				$card = QSLM_DB::get( 'cards', $id );
				if ( $card ) {
					QSLM_DB::update( 'cards', $id, array( 'card_issued' => 1, 'card_issued_at' => $card['card_issued_at'] ?: current_time( 'mysql' ), 'envelope_printed' => 1, 'card_sent' => 1, 'sent_at' => current_time( 'mysql' ), 'status' => '已发卡', 'updated_at' => current_time( 'mysql' ) ) );
					QSLM_Audit::log( 'card_send', 'card', $id, $card['card_no'] );
				}
				self::redirect( 'qslm-cards', 'updated' );

			case 'card_receipt':
				$card = QSLM_DB::get( 'cards', $id );
				if ( $card ) {
					QSLM_DB::update( 'cards', $id, array( 'receipt_confirmed' => 1, 'status' => '已签收', 'updated_at' => current_time( 'mysql' ) ) );
					QSLM_Audit::log( 'card_receipt', 'card', $id, $card['card_no'] );
				}
				self::redirect( 'qslm-cards', 'updated' );

			case 'delete_card':
				QSLM_DB::delete( 'cards', $id );
				QSLM_Audit::log( 'delete_card', 'card', $id, '' );
				self::redirect( 'qslm-cards', 'deleted' );

			case 'request_approve':
				QSLM_DB::update( 'card_requests', $id, array( 'status' => '已通过', 'updated_at' => current_time( 'mysql' ) ) );
				QSLM_Audit::log( 'request_approve', 'card_request', $id, '' );
				self::redirect( 'qslm-requests', 'updated' );

			case 'request_reject':
				QSLM_DB::update( 'card_requests', $id, array( 'status' => '已拒绝', 'updated_at' => current_time( 'mysql' ) ) );
				QSLM_Audit::log( 'request_reject', 'card_request', $id, '' );
				self::redirect( 'qslm-requests', 'updated' );

			case 'request_create_card':
				$request = QSLM_DB::get( 'card_requests', $id );
				if ( $request && '已通过' === $request['status'] ) {
					$qso = QSLM_DB::get( 'qso', (int) $request['qso_id'] );
					if ( $qso ) {
						$card_id = self::create_card_for( $qso, $request['card_version'], QSLM_Settings::get( 'card_remarks' ) );
						if ( $card_id ) {
							QSLM_DB::update( 'card_requests', $id, array( 'card_id' => $card_id, 'status' => '已建卡', 'updated_at' => current_time( 'mysql' ) ) );
						}
						self::redirect( 'qslm-requests', $card_id ? 'created' : 'error', $card_id ? array() : array( 'qslm_reason' => self::$card_error ) );
					}
				}
				self::redirect( 'qslm-requests', 'error', array( 'qslm_reason' => '申请状态或关联 QSO 不允许建卡。' ) );

			case 'exchange_approve':
				QSLM_DB::update( 'exchange_requests', $id, array( 'status' => '已通过', 'updated_at' => current_time( 'mysql' ) ) );
				QSLM_Audit::log( 'exchange_approve', 'exchange', $id, '' );
				self::redirect( 'qslm-exchanges', 'updated' );

			case 'exchange_reject':
				QSLM_DB::update( 'exchange_requests', $id, array( 'status' => '已拒绝', 'updated_at' => current_time( 'mysql' ) ) );
				QSLM_Audit::log( 'exchange_reject', 'exchange', $id, '' );
				self::redirect( 'qslm-exchanges', 'updated' );

			case 'exchange_create_card':
				$request = QSLM_DB::get( 'exchange_requests', $id );
				if ( $request && '已通过' === $request['status'] ) {
					$qso_like = array(
						'id'        => 0,
						'call_sign' => $request['call_sign'],
						'scene'     => 'ONLINE_EYEBALL',
					);
					$card_id = self::create_card_for( $qso_like, $request['card_version'], QSLM_Settings::get( 'online_remarks' ), $request );
					if ( $card_id ) {
						QSLM_DB::update( 'exchange_requests', $id, array( 'card_id' => $card_id, 'updated_at' => current_time( 'mysql' ) ) );
					}
					self::redirect( 'qslm-exchanges', $card_id ? 'created' : 'error', $card_id ? array() : array( 'qslm_reason' => self::$card_error ) );
				}
				self::redirect( 'qslm-exchanges', 'error', array( 'qslm_reason' => '申请状态或卡片版本不允许建卡。' ) );

			case 'add_receive':
				$card_id = absint( $_POST['card_id'] ?? 0 );
				$card    = QSLM_DB::get( 'cards', $card_id );
				if ( $card ) {
					$rid = QSLM_DB::insert(
						'receives',
						array(
							'receive_no' => QSLM_DB::next_receive_no(),
							'scene'      => $card['scene'],
							'call_sign'  => $card['call_sign'],
							'card_id'    => $card_id,
							'received_at'=> sanitize_text_field( wp_unslash( $_POST['received_at'] ?? current_time( 'Y-m-d' ) ) ),
							'note'       => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
							'created_at' => current_time( 'mysql' ),
						)
					);
					QSLM_DB::update( 'cards', $card_id, array( 'received' => 1, 'status' => '已收卡', 'updated_at' => current_time( 'mysql' ) ) );
					QSLM_Audit::log( 'add_receive', 'receive', $rid, $card['card_no'] );
				}
				self::redirect( 'qslm-receives', 'created' );

			case 'save_address':
				$data = array(
					'call_sign'   => strtoupper( sanitize_text_field( wp_unslash( $_POST['call_sign'] ?? '' ) ) ),
					'name'        => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
					'phone'       => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
					'postal_code' => sanitize_text_field( wp_unslash( $_POST['postal_code'] ?? '' ) ),
					'country'     => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ),
					'address'     => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
					'email'       => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
					'note'        => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
					'updated_at'  => current_time( 'mysql' ),
				);
				if ( $id ) {
					QSLM_DB::update( 'addresses', $id, $data );
				} else {
					$data['created_at'] = current_time( 'mysql' );
					$id = QSLM_DB::insert( 'addresses', $data );
				}
				QSLM_Audit::log( 'save_address', 'address', $id, $data['call_sign'] );
				self::redirect( 'qslm-addresses', 'saved' );

			case 'delete_address':
				QSLM_DB::delete( 'addresses', $id );
				self::redirect( 'qslm-addresses', 'deleted' );

			case 'save_bureau':
				$data = array(
					'name'        => sanitize_text_field( wp_unslash( $_POST['bureau_name'] ?? '' ) ),
					'country'     => sanitize_text_field( wp_unslash( $_POST['country'] ?? '' ) ),
					'postal_code' => sanitize_text_field( wp_unslash( $_POST['postal_code'] ?? '' ) ),
					'address'     => sanitize_textarea_field( wp_unslash( $_POST['address'] ?? '' ) ),
					'phone'       => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
					'note'        => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
				);
				if ( $id ) {
					QSLM_DB::update( 'bureaus', $id, $data );
				} else {
					$data['created_at'] = current_time( 'mysql' );
					$id = QSLM_DB::insert( 'bureaus', $data );
				}
				QSLM_Audit::log( 'save_bureau', 'bureau', $id, $data['name'] );
				self::redirect( 'qslm-bureaus', 'saved' );

			case 'delete_bureau':
				QSLM_DB::delete( 'bureaus', $id );
				self::redirect( 'qslm-bureaus', 'deleted' );

			case 'save_station_card':
				$data = array(
					'version'    => sanitize_text_field( wp_unslash( $_POST['version'] ?? '' ) ),
					'total'      => absint( $_POST['total'] ?? 0 ),
					'used'       => absint( $_POST['used'] ?? 0 ),
					'image_url'  => esc_url_raw( wp_unslash( $_POST['image_url'] ?? '' ) ),
					'sort_order' => (int) wp_unslash( $_POST['sort_order'] ?? 0 ),
					'active'     => empty( $_POST['active'] ) ? 0 : 1,
				);
				if ( $id ) {
					QSLM_DB::update( 'station_cards', $id, $data );
				} else {
					$data['created_at'] = current_time( 'mysql' );
					$id = QSLM_DB::insert( 'station_cards', $data );
				}
				self::redirect( 'qslm-station-cards', 'saved' );

			case 'delete_station_card':
				QSLM_DB::delete( 'station_cards', $id );
				self::redirect( 'qslm-station-cards', 'deleted' );

			case 'save_settings':
				QSLM_Settings::save( wp_unslash( $_POST ) );
				self::redirect( 'qslm-settings', 'saved' );

			case 'import_json':
				$result = QSLM_Import_Export::import_json();
				self::redirect( 'qslm-import-export', is_wp_error( $result ) ? 'error' : 'imported' );

			default:
				self::redirect( $back, 'error' );
		}
	}

	/**
	 * Create a card.
	 *
	 * @param array  $qso     QSO row.
	 * @param string $version Version.
	 * @param string $remarks Remarks.
	 * @param array  $request Optional exchange request.
	 * @return int
	 */
	private static function create_card_for( $qso, $version, $remarks = '', $request = null ) {
		$version = $version ?: '不发卡';
		$scene   = $qso['scene'] ?? 'QSO';
		if ( $request ) {
			$scene = $request['scene'] ?? 'ONLINE_EYEBALL';
		}
		$qso_id = (int) ( $qso['id'] ?? 0 );
		if ( $qso_id ) {
			global $wpdb;
			$existing = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . QSLM_DB::table( 'cards' ) . ' WHERE qso_id = %d', $qso_id ) );
			if ( $existing ) {
				self::$card_error = '该通联记录已经创建过卡片，不能重复建卡。';
				return 0;
			}
		}
		if ( '不发卡' !== $version ) {
			$stock = QSLM_DB::find( 'station_cards', 'version', $version );
			if ( ! $stock || (int) $stock['used'] >= (int) $stock['total'] ) {
				self::$card_error = '卡片版本不存在或库存不足，请先到「本台卡片」检查版本与库存。';
				return 0;
			}
		}
		$card_id = QSLM_DB::insert(
			'cards',
			array(
				'card_no'       => QSLM_DB::next_card_no( $version ),
				'scene'         => $scene,
				'qso_id'        => (int) ( $qso['id'] ?? 0 ),
				'call_sign'     => strtoupper( sanitize_text_field( $qso['call_sign'] ?? '' ) ),
				'card_version'  => $version,
				'status'        => '不发卡' === $version ? '不发卡' : '待制卡',
				'remarks'       => sanitize_textarea_field( $remarks ),
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( $card_id ) {
			$table = QSLM_DB::table( 'station_cards' );
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "UPDATE `$table` SET used = used + 1 WHERE version = %s", $version ) );
			QSLM_Audit::log( 'create_card', 'card', $card_id, $version );
		} else {
			self::$card_error = '卡片写入数据库失败，请检查数据库权限后重试。';
		}
		return $card_id;
	}
}
