<?php
/**
 * Public shortcodes and anonymous forms.
 *
 * @package QSLM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public controller.
 */
class QSLM_Public {

	/**
	 * Unique radio group counter for card style pickers.
	 *
	 * @var int
	 */
	private static $style_index = 0;

	/**
	 * Init.
	 */
	public static function init() {
		add_shortcode( 'qsl-card', array( __CLASS__, 'card_query' ) );
		add_shortcode( 'qsl-card-query', array( __CLASS__, 'card_query' ) );
		add_shortcode( 'qsl-online-exchange-card', array( __CLASS__, 'online_exchange' ) );
		add_shortcode( 'qsl-offline-exchange-card', array( __CLASS__, 'offline_exchange' ) );
		add_shortcode( 'qsl-receipt-card', array( __CLASS__, 'receipt' ) );
		add_action( 'admin_post_qslm_public', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_qslm_public', array( __CLASS__, 'handle' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Enqueue assets on pages containing a QSL shortcode.
	 */
	public static function assets() {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}
		$tags = array( 'qsl-card', 'qsl-card-query', 'qsl-online-exchange-card', 'qsl-offline-exchange-card', 'qsl-receipt-card' );
		$has  = false;
		$has_exchange = false;
		foreach ( $tags as $tag ) {
			if ( has_shortcode( $post->post_content, $tag ) ) {
				$has = true;
				if ( in_array( $tag, array( 'qsl-online-exchange-card', 'qsl-offline-exchange-card' ), true ) ) {
					$has_exchange = true;
				}
			}
		}
		if ( ! $has ) {
			return;
		}
		wp_enqueue_style( 'qslm-public', QSLM_URL . 'assets/public.css', array(), QSLM_VERSION );
		wp_enqueue_script( 'qslm-public', QSLM_URL . 'assets/public.js', array(), QSLM_VERSION, true );
		if ( $has_exchange && self::turnstile_active() ) {
			// Share the core Turnstile handle with the theme so a page that
			// contains comments and an exchange form loads api.js only once.
			wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', array(), null, true );
			wp_script_add_data( 'cf-turnstile', 'strategy', 'defer' );
		}
	}

	/**
	 * Notice.
	 *
	 * @return string
	 */
	private static function notice() {
		$code = isset( $_GET['qslm_msg'] ) ? sanitize_key( wp_unslash( $_GET['qslm_msg'] ) ) : '';
		if ( ! $code ) {
			return '';
		}
		$messages = array(
			'sent'      => '提交成功，请等待管理员审核。',
			'approved'  => '申请已通过，请等待建卡与寄送。',
			'confirmed' => '确认成功。',
			'error'     => '提交失败，请检查必填项。',
			'cooldown'  => '同一呼号提交过于频繁，请稍后再试。',
			'disabled'  => '线上换卡暂时关闭。',
			'not_found' => '没有找到匹配记录。',
			'turnstile_failed' => '人机验证失败，请刷新页面后重试。',
		);
		$class = 'error' === $code ? 'is-error' : 'is-success';
		return '<div class="qslm-public-notice ' . esc_attr( $class ) . '">' . esc_html( $messages[ $code ] ?? '操作完成。' ) . '</div>';
	}

	/**
	 * Open wrapper.
	 *
	 * @param string $title Title.
	 * @param string $desc  Desc.
	 * @return string
	 */
	private static function open( $title, $desc = '' ) {
		$html  = '<div class="qslm-public">';
		$html .= '<div class="qslm-public__head"><h2>' . esc_html( $title ) . '</h2>';
		if ( $desc ) {
			$html .= '<p>' . esc_html( $desc ) . '</p>';
		}
		$html .= '</div>' . self::notice();
		return $html;
	}

	/**
	 * Close.
	 *
	 * @return string
	 */
	private static function close() {
		return '<div class="qslm-public__credit">基于 <a href="https://github.com/bi1kbu/qsl-management" target="_blank" rel="noopener noreferrer">bi1kbu/qsl-management</a> 移植'
			. ' · 移植作者：砂糖橘猫'
			. ' · <a href="https://www.luna.tel" target="_blank" rel="noopener noreferrer">www.luna.tel</a></div></div>';
	}

	/**
	 * Form action.
	 *
	 * @return string
	 */
	private static function action() {
		return esc_url( admin_url( 'admin-post.php' ) );
	}

	/**
	 * Nonce and task.
	 *
	 * @param string $task Task.
	 * @return string
	 */
	private static function nonce( $task ) {
		$return = get_permalink();
		if ( ! $return ) {
			$return = home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
		}
		return wp_nonce_field( 'qslm_public', 'qslm_nonce', true, false )
			. '<input type="hidden" name="action" value="qslm_public">'
			. '<input type="hidden" name="task" value="' . esc_attr( $task ) . '">'
			. '<input type="hidden" name="qslm_return" value="' . esc_url( $return ) . '">';
	}

	/**
	 * Address fields.
	 *
	 * @param string $prefix Prefix.
	 * @return string
	 */
	private static function address_fields( $prefix = '' ) {
		$p = $prefix ? $prefix . '_' : '';
		ob_start();
		?>
		<div class="qslm-public__grid">
			<label>收件人姓名<input type="text" name="<?php echo esc_attr( $p ); ?>name" autocomplete="name"></label>
			<label>联系电话<input type="text" name="<?php echo esc_attr( $p ); ?>phone" autocomplete="tel"></label>
			<label>邮政编码<input type="text" name="<?php echo esc_attr( $p ); ?>postal_code" autocomplete="postal-code"></label>
			<label>国家 / 地区<input type="text" name="<?php echo esc_attr( $p ); ?>country" autocomplete="country-name"></label>
		</div>
		<label>通信地址<textarea name="<?php echo esc_attr( $p ); ?>address" rows="3" autocomplete="street-address"></textarea></label>
		<label>电子邮箱<input type="email" name="<?php echo esc_attr( $p ); ?>email" autocomplete="email"></label>
		<?php
		return ob_get_clean();
	}

	/**
	 * Styles that still have stock.
	 *
	 * @return array
	 */
	private static function available_styles() {
		$versions = QSLM_Settings::station_cards();
		return array_values(
			array_filter(
				$versions,
				static function ( $version ) {
					return ( (int) $version['total'] - (int) $version['used'] ) > 0;
				}
			)
		);
	}

	/**
	 * Card style picker with visible card faces.
	 *
	 * @param array  $versions Active station card styles.
	 * @param string $legend   Legend text.
	 * @return string
	 */
	private static function style_picker( $versions, $legend = '选择卡片样式' ) {
		if ( ! $versions ) {
			return '';
		}
		self::$style_index++;
		$group   = 'qslm-card-style-' . self::$style_index;
		$count   = 0;
		$html    = '<fieldset class="qslm-public__styles"><legend>' . esc_html( $legend ) . '</legend><div class="qslm-public__style-grid">';
		foreach ( $versions as $version ) {
			$remaining = max( 0, (int) $version['total'] - (int) $version['used'] );
			if ( $remaining < 1 ) {
				continue;
			}
			$id      = $group . '-' . (int) $version['id'];
			$image   = (string) $version['image_url'];
			$checked = 0 === $count ? ' checked' : '';
			$count++;
			$html   .= '<label class="qslm-public__style" for="' . esc_attr( $id ) . '">';
			$html   .= '<input type="radio" id="' . esc_attr( $id ) . '" name="card_version" value="' . esc_attr( $version['version'] ) . '" required' . $checked . '>';
			$html   .= '<span class="qslm-public__style-media">';
			if ( $image ) {
				$html .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $version['version'] ) . ' 卡片样式" loading="lazy">';
			} else {
				$html .= '<span class="qslm-public__style-placeholder" aria-hidden="true"><span>QSL</span><small>' . esc_html( $version['version'] ) . '</small></span>';
			}
			$html .= '</span>';
			$html .= '<span class="qslm-public__style-info"><strong>' . esc_html( $version['version'] ) . '</strong><small>剩余 ' . esc_html( number_format_i18n( $remaining ) ) . ' 张</small></span>';
			$html .= '</label>';
		}
		$html .= '</div></fieldset>';
		return $count ? $html : '';
	}

	/**
	 * Card query shortcode.
	 *
	 * @return string
	 */
	public static function card_query() {
		global $wpdb;
		$call = strtoupper( sanitize_text_field( wp_unslash( $_GET['qsl_call'] ?? '' ) ) );
		$rows = array();
		if ( $call ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT q.*, c.card_no, c.status AS card_status, c.card_version, c.card_issued, c.card_sent, c.receipt_confirmed FROM ' . QSLM_DB::table( 'qso' ) . ' q LEFT JOIN ' . QSLM_DB::table( 'cards' ) . ' c ON c.qso_id = q.id WHERE q.call_sign = %s ORDER BY q.qso_date DESC, q.id DESC LIMIT 100',
					$call
				),
				ARRAY_A
			);
		}
		$versions = self::available_styles();
		$html     = self::open( 'QSL 卡片查询', '输入对方呼号，查看通联记录与卡片状态，并可为可申请记录提交实体卡申请。' );
		$html    .= '<form class="qslm-public__search" method="get">';
		$html    .= '<input type="text" name="qsl_call" value="' . esc_attr( $call ) . '" placeholder="例如 BI1KBU" required>';
		$html    .= '<button type="submit">查询</button></form>';
		if ( $call ) {
			$html .= '<div class="qslm-public__section"><h3>' . esc_html( $call ) . ' 的通联记录</h3>';
			if ( ! $rows ) {
				$html .= '<p class="qslm-public__muted">没有找到该呼号的通联记录。</p>';
			} else {
				$html .= '<div class="qslm-public__table-wrap"><table class="qslm-public__table"><thead><tr><th>日期</th><th>时间</th><th>模式</th><th>频率</th><th>卡片</th><th>状态</th></tr></thead><tbody>';
				foreach ( $rows as $row ) {
					$html .= '<tr><td>' . esc_html( $row['qso_date'] ) . '</td><td>' . esc_html( $row['time_on'] ) . '</td><td>' . esc_html( $row['mode'] ) . '</td><td>' . esc_html( $row['frequency'] ) . '</td><td>' . esc_html( $row['card_no'] ?: '—' ) . '</td><td>' . esc_html( $row['card_status'] ?: '未申请' ) . '</td></tr>';
				}
				$html .= '</tbody></table></div>';
				$html .= '<div class="qslm-public__section"><h3>申请实体卡</h3>';
				if ( ! $versions ) {
					$html .= '<p class="qslm-public__muted">管理员尚未配置可选的卡片样式，暂时无法提交申请。</p></div>';
					return $html . self::close();
				}
				$html .= '<form class="qslm-public__form" method="post" action="' . self::action() . '">' . self::nonce( 'apply_card' );
				$html .= '<label>选择通联记录<select name="qso_id" required><option value="">请选择</option>';
				foreach ( $rows as $row ) {
					if ( ! $row['card_no'] ) {
						$html .= '<option value="' . esc_attr( $row['id'] ) . '">' . esc_html( $row['qso_date'] . ' · ' . $row['mode'] . ' · ' . $row['frequency'] ) . '</option>';
					}
				}
				$html .= '</select></label>' . self::style_picker( $versions, '选择卡片样式' ) . self::address_fields() . '<label>备注<textarea name="note" rows="2"></textarea></label>';
				$html .= '<button type="submit">提交申请</button></form>';
				$html .= '<p class="qslm-public__muted">个人地址仅用于本次申请；请确认呼号与地址准确。</p></div>';
			}
		}
		return $html . self::close();
	}

	/**
	 * Online exchange shortcode.
	 *
	 * @return string
	 */
	public static function online_exchange() {
		$versions = self::available_styles();
		$bureaus  = QSLM_DB::paged( 'bureaus', '1=1', array(), 'name ASC', 1, 200 );
		$policy   = QSLM_Settings::get( 'online_policy' );
		$html     = self::open( '线上换卡申请', '选择本台卡片版本，填写地址后提交。管理员审核通过后会安排寄卡。' );
		if ( 'DISABLED' === $policy ) {
			return $html . '<p class="qslm-public__muted">线上换卡暂时关闭，请通过邮件联系本站。</p>' . self::close();
		}
		if ( ! $versions ) {
			return $html . '<p class="qslm-public__muted">管理员尚未配置可选的卡片样式，暂时无法提交换卡申请。</p>' . self::close();
		}
		$html .= '<form class="qslm-public__form" method="post" action="' . self::action() . '">' . self::nonce( 'online_exchange' );
		$html .= '<label>对方呼号<input type="text" name="call_sign" required placeholder="例如 BI1KBU"></label>';
		$html .= '<label>电子邮箱<input type="email" name="email" required></label>';
		$html .= self::style_picker( $versions, '选择卡片样式' );
		$html .= '<fieldset class="qslm-public__choice"><legend>收件方式</legend><label><input type="radio" name="address_type" value="personal" checked> 个人地址</label><label><input type="radio" name="address_type" value="bureau"> 卡片局</label></fieldset>';
		$html .= '<div data-qslm-address="personal">' . self::address_fields() . '</div>';
		$html .= '<div data-qslm-address="bureau" hidden><label>选择卡片局<select name="bureau_id"><option value="">手动填写</option>';
		foreach ( $bureaus as $bureau ) {
			$html .= '<option value="' . esc_attr( $bureau['id'] ) . '">' . esc_html( $bureau['name'] . ' · ' . $bureau['country'] . ' · ' . $bureau['postal_code'] ) . '</option>';
		}
		$html .= '</select></label><label>卡片局名称<input type="text" name="bureau_name"></label><label>卡片局地址<textarea name="bureau_address" rows="3"></textarea></label><label>卡片局邮编<input type="text" name="bureau_postal_code"></label></div>';
		$html .= '<label>备注<textarea name="note" rows="2"></textarea></label>' . self::turnstile_widget() . '<button type="submit">提交换卡申请</button></form>';
		return $html . self::close();
	}

	/**
	 * Offline exchange shortcode.
	 *
	 * @return string
	 */
	public static function offline_exchange() {
		$html  = self::open( '线下换卡确认', '活动现场或事后确认线下交换的卡片，提交后将记录寄送状态。' );
		$html .= '<form class="qslm-public__form" method="post" action="' . self::action() . '">' . self::nonce( 'offline_confirm' );
		$html .= '<label>卡片编号<input type="text" name="card_no" required placeholder="例如 C0001"></label>';
		$html .= '<label>对方呼号<input type="text" name="call_sign" required></label>';
		$html .= '<label>备注<textarea name="note" rows="3"></textarea></label>' . self::turnstile_widget() . '<button type="submit">确认换卡</button></form>';
		return $html . self::close();
	}

	/**
	 * Receipt shortcode.
	 *
	 * @return string
	 */
	public static function receipt() {
		$html  = self::open( '公开签收', '确认已收到本站寄出的 QSL 卡片。' );
		$html .= '<form class="qslm-public__form" method="post" action="' . self::action() . '">' . self::nonce( 'receipt_confirm' );
		$html .= '<label>卡片编号<input type="text" name="card_no" required></label>';
		$html .= '<label>对方呼号<input type="text" name="call_sign"></label>';
		$html .= '<label>备注<textarea name="note" rows="2"></textarea></label>';
		$html .= '<button type="submit">确认签收</button></form>';
		return $html . self::close();
	}

	/**
	 * Handle public submissions.
	 */
	public static function handle() {
		$nonce = isset( $_POST['qslm_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['qslm_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'qslm_public' ) ) {
			self::redirect( 'error' );
		}
		$task = sanitize_key( wp_unslash( $_POST['task'] ?? '' ) );
		if ( ! self::rate_limit( $task ) ) {
			self::redirect( 'cooldown' );
		}
		if ( in_array( $task, array( 'online_exchange', 'offline_confirm' ), true ) && self::turnstile_active() && ! self::verify_turnstile() ) {
			self::redirect( 'turnstile_failed' );
		}

		switch ( $task ) {
			case 'apply_card':
				self::apply_card();
				break;
			case 'online_exchange':
				self::online_exchange_submit();
				break;
			case 'offline_confirm':
				self::offline_confirm();
				break;
			case 'receipt_confirm':
				self::receipt_confirm();
				break;
			default:
				self::redirect( 'error' );
		}
	}

	/**
	 * Rate limit by IP and task.
	 *
	 * @param string $task Task.
	 * @return bool
	 */
	private static function rate_limit( $task ) {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key   = 'qslm_rl_' . md5( $ip . '|' . $task );
		$count = (int) get_transient( $key );
		if ( $count >= (int) QSLM_Settings::get( 'guest_limit', 30 ) ) {
			return false;
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Apply for a card.
	 */
	private static function apply_card() {
		global $wpdb;
		$qso_id  = absint( $_POST['qso_id'] ?? 0 );
		$version = sanitize_text_field( wp_unslash( $_POST['card_version'] ?? '' ) );
		$qso     = QSLM_DB::get( 'qso', $qso_id );
		if ( ! $qso || ! $version ) {
			self::redirect( 'error' );
		}
		$existing = QSLM_DB::count( 'cards', $GLOBALS['wpdb']->prepare( 'qso_id = %d', $qso_id ) );
		if ( $existing ) {
			self::redirect( 'error' );
		}
		$stock = QSLM_DB::find( 'station_cards', 'version', $version );
		if ( ! $stock || ! (int) $stock['active'] || (int) $stock['total'] <= (int) $stock['used'] ) {
			self::redirect( 'error' );
		}
		$call    = strtoupper( sanitize_text_field( $qso['call_sign'] ) );
		$address = self::address_from_post();
		if ( empty( $address['name'] ) || empty( $address['postal_code'] ) || empty( $address['address'] ) ) {
			self::redirect( 'error' );
		}
		$request_id = QSLM_DB::insert(
			'card_requests',
			array(
				'request_no'   => QSLM_DB::next_request_no(),
				'call_sign'    => $call,
				'qso_id'       => $qso_id,
				'card_version' => $version,
				'address_json' => wp_json_encode( $address ),
				'note'         => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
				'status'       => '待审核',
				'created_at'   => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			)
		);
		QSLM_Audit::log( 'public_apply_card', 'card_request', $request_id, $call );
		self::redirect( $request_id ? 'sent' : 'error' );
	}

	/**
	 * Online exchange submission.
	 */
	private static function online_exchange_submit() {
		$policy = QSLM_Settings::get( 'online_policy' );
		if ( 'DISABLED' === $policy ) {
			self::redirect( 'disabled' );
		}
		$call    = strtoupper( sanitize_text_field( wp_unslash( $_POST['call_sign'] ?? '' ) ) );
		$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$version = sanitize_text_field( wp_unslash( $_POST['card_version'] ?? '' ) );
		if ( ! $call || ! is_email( $email ) || ! $version ) {
			self::redirect( 'error' );
		}
		$stock = QSLM_DB::find( 'station_cards', 'version', $version );
		if ( ! $stock || ! (int) $stock['active'] || (int) $stock['total'] <= (int) $stock['used'] ) {
			self::redirect( 'error' );
		}
		global $wpdb;
		$cooldown = (int) QSLM_Settings::get( 'online_cooldown', 5 );
		if ( $cooldown > 0 ) {
			$last = $wpdb->get_var( $wpdb->prepare( 'SELECT created_at FROM ' . QSLM_DB::table( 'exchange_requests' ) . ' WHERE call_sign = %s ORDER BY id DESC LIMIT 1', $call ) );
			if ( $last && strtotime( $last ) > time() - $cooldown * MINUTE_IN_SECONDS ) {
				self::redirect( 'cooldown' );
			}
		}
		$pending = QSLM_DB::count( 'exchange_requests', $wpdb->prepare( "call_sign = %s AND status = '待审核'", $call ) );
		if ( $pending ) {
			self::redirect( 'cooldown' );
		}
		$address_type = 'bureau' === ( $_POST['address_type'] ?? '' ) ? 'bureau' : 'personal';
		$address      = 'bureau' === $address_type ? self::bureau_from_post() : self::address_from_post();
		if ( empty( $address['name'] ) || empty( $address['postal_code'] ) || empty( $address['address'] ) ) {
			self::redirect( 'error' );
		}
		$status       = 'AUTO_APPROVE' === $policy ? '已通过' : '待审核';
		$request_id   = QSLM_DB::insert(
			'exchange_requests',
			array(
				'request_no'   => QSLM_DB::next_request_no(),
				'scene'        => 'ONLINE_EYEBALL',
				'call_sign'    => $call,
				'card_version' => $version,
				'email'        => $email,
				'address_type' => $address_type,
				'address_json' => wp_json_encode( $address ),
				'note'         => sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ),
				'status'       => $status,
				'created_at'   => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			)
		);
		QSLM_Audit::log( 'public_online_exchange', 'exchange', $request_id, $call );
		self::redirect( $request_id ? ( '已通过' === $status ? 'approved' : 'sent' ) : 'error' );
	}

	/**
	 * Offline confirmation.
	 */
	private static function offline_confirm() {
		$card_no = strtoupper( sanitize_text_field( wp_unslash( $_POST['card_no'] ?? '' ) ) );
		$call    = strtoupper( sanitize_text_field( wp_unslash( $_POST['call_sign'] ?? '' ) ) );
		$card    = QSLM_DB::find( 'cards', 'card_no', $card_no );
		if ( ! $card || ( $call && strtoupper( $card['call_sign'] ) !== $call ) ) {
			self::redirect( 'not_found' );
		}
		QSLM_DB::update(
			'cards',
			(int) $card['id'],
			array(
				'card_sent'   => 1,
				'sent_at'     => current_time( 'mysql' ),
				'received'    => 1,
				'status'      => '已收卡',
				'remarks'     => trim( $card['remarks'] . "\n" . sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ) ),
				'updated_at'  => current_time( 'mysql' ),
			)
		);
		QSLM_Audit::log( 'public_offline_confirm', 'card', (int) $card['id'], $card_no );
		self::redirect( 'confirmed' );
	}

	/**
	 * Public receipt.
	 */
	private static function receipt_confirm() {
		$card_no = strtoupper( sanitize_text_field( wp_unslash( $_POST['card_no'] ?? '' ) ) );
		$call    = strtoupper( sanitize_text_field( wp_unslash( $_POST['call_sign'] ?? '' ) ) );
		$card    = QSLM_DB::find( 'cards', 'card_no', $card_no );
		if ( ! $card || ( $call && strtoupper( $card['call_sign'] ) !== $call ) ) {
			self::redirect( 'not_found' );
		}
		QSLM_DB::update( 'cards', (int) $card['id'], array( 'receipt_confirmed' => 1, 'status' => '已签收', 'updated_at' => current_time( 'mysql' ) ) );
		QSLM_Audit::log( 'public_receipt', 'card', (int) $card['id'], $card_no );
		self::redirect( 'confirmed' );
	}

	/**
	 * Turnstile is active and fully configured.
	 *
	 * @return bool
	 */
	private static function turnstile_active() {
		return (bool) QSLM_Settings::get( 'turnstile_enabled' )
			&& '' !== (string) QSLM_Settings::get( 'turnstile_site_key' )
			&& '' !== (string) QSLM_Settings::get( 'turnstile_secret' );
	}

	/**
	 * Turnstile widget markup.
	 *
	 * @return string
	 */
	private static function turnstile_widget() {
		if ( ! self::turnstile_active() ) {
			return '';
		}
		return '<div class="qslm-public__turnstile cf-turnstile" data-sitekey="' . esc_attr( (string) QSLM_Settings::get( 'turnstile_site_key' ) ) . '" data-theme="' . esc_attr( (string) QSLM_Settings::get( 'turnstile_theme', 'auto' ) ) . '" data-action="qslm_exchange"></div>';
	}

	/**
	 * Validate a Turnstile token server-side.
	 *
	 * @return bool
	 */
	private static function verify_turnstile() {
		$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		if ( ! $token ) {
			return false;
		}
		$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$response = wp_remote_post(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => (string) QSLM_Settings::get( 'turnstile_secret' ),
					'response' => $token,
					'remoteip' => $ip,
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['success'] ) ) {
			return false;
		}
		if ( ! empty( $body['action'] ) && 'qslm_exchange' !== $body['action'] ) {
			return false;
		}
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( ! empty( $body['hostname'] ) && $host && strtolower( (string) $body['hostname'] ) !== strtolower( (string) $host ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Address from POST.
	 *
	 * @param string $prefix Prefix.
	 * @return array
	 */
	private static function address_from_post( $prefix = '' ) {
		$p = $prefix ? $prefix . '_' : '';
		return array(
			'name'        => sanitize_text_field( wp_unslash( $_POST[ $p . 'name' ] ?? '' ) ),
			'phone'       => sanitize_text_field( wp_unslash( $_POST[ $p . 'phone' ] ?? '' ) ),
			'postal_code' => sanitize_text_field( wp_unslash( $_POST[ $p . 'postal_code' ] ?? '' ) ),
			'country'     => sanitize_text_field( wp_unslash( $_POST[ $p . 'country' ] ?? '' ) ),
			'address'     => sanitize_textarea_field( wp_unslash( $_POST[ $p . 'address' ] ?? '' ) ),
			'email'       => sanitize_email( wp_unslash( $_POST[ $p . 'email' ] ?? '' ) ),
		);
	}

	/**
	 * Bureau address from POST.
	 *
	 * @return array
	 */
	private static function bureau_from_post() {
		$bureau_id = absint( $_POST['bureau_id'] ?? 0 );
		if ( $bureau_id ) {
			$bureau = QSLM_DB::get( 'bureaus', $bureau_id );
			if ( $bureau ) {
				return array(
					'bureau_id'   => $bureau_id,
					'bureau_name' => $bureau['name'],
					'name'        => $bureau['name'],
					'country'     => $bureau['country'],
					'postal_code' => $bureau['postal_code'],
					'address'     => $bureau['address'],
					'phone'       => $bureau['phone'],
					'email'       => '',
				);
			}
		}
		return array(
			'bureau_name' => sanitize_text_field( wp_unslash( $_POST['bureau_name'] ?? '' ) ),
			'name'        => sanitize_text_field( wp_unslash( $_POST['bureau_name'] ?? '' ) ),
			'postal_code' => sanitize_text_field( wp_unslash( $_POST['bureau_postal_code'] ?? '' ) ),
			'address'     => sanitize_textarea_field( wp_unslash( $_POST['bureau_address'] ?? '' ) ),
			'country'     => '',
			'phone'       => '',
			'email'       => '',
		);
	}

	/**
	 * Redirect back with a message.
	 *
	 * @param string $msg Message.
	 */
	private static function redirect( $msg ) {
		$url = '';
		if ( ! empty( $_POST['qslm_return'] ) ) {
			$url = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['qslm_return'] ) ), '' );
		}
		if ( ! $url ) {
			$url = wp_get_referer();
		}
		if ( ! $url ) {
			$url = home_url( '/' );
		}
		$url = remove_query_arg( 'qslm_msg', $url );
		wp_safe_redirect( add_query_arg( 'qslm_msg', sanitize_key( $msg ), $url ) );
		exit;
	}
}
