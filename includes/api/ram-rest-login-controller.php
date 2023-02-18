<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Login_Controller extends WP_REST_Controller {

	// Token有效时长
	private $token_expire_time = 5 * 60; // 5分钟
	private $resource_name;

	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'login';
	}

	public function register_routes() {
		register_rest_route($this->namespace, '/' . $this->resource_name . '/getQRToken', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'get_qr_code_token'),
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getQRInfo', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'get_qr_info'),
				'args' => array(
					'token' => array(
						'required' => true,
						'type' => 'string',
					),
				),
				'permission_callback' => 'jwt_permissions_check',
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/confirmLogin', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'confirmLogin'),
				'args' => array(
					'token' => array(
						'required' => true,
						'type' => 'string',
					),
					'isContinue' => array(
						'required' => true,
						'type' => 'boolean',
					),
				),
				'permission_callback' => 'jwt_permissions_check',
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 轮询接口，获取当前用户扫码状态
		register_rest_route($this->namespace, '/' . $this->resource_name . '/getQRStatus', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'getQRStatus'),
				'args' => array(
					'token' => array(
						'required' => true,
						'type' => 'string',
					),
				),
			),
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	function get_qr_code_token() {
		// 随机生成一个32位的token
		$token = md5(uniqid(md5(microtime(true)), true));
		$expire_time = (time() + $this->token_expire_time) * 1000;
		// 获取用户的UA和IP地址
//		$ua = $_SERVER['HTTP_USER_AGENT'];
		// 根据UA判断用户的操作系统
//		$ip = $_SERVER['REMOTE_ADDR'];
		// 获取用户的地理位置
//		$location = get_ip_location($ip);

		// 将token和过期时间缓存起来
		$cache = array(
			'token' => $token,
			'expire_time' => $expire_time,
//			'os' => get_user_os($ua),
//			'ip' => $ip,
//			'location' => $location,
			'status' => 0,     // 0未扫码 1已扫码（待确认） 2已确认 3已取消 4确认登录
		);
		set_transient('XY_QR_' . $token, $cache, $this->token_expire_time);

		return array(
			'token' => $token,
		);
	}

	function get_qr_info($args) {
		$info = get_transient('XY_QR_' . $args['token']);

		if (empty($info)) {
			return new WP_Error('error', '二维码无效', array('status' => 500));
		}
		// 若status不为0或者1，说明token已经失效
		if ($info['status'] !== 0 && $info['status'] !== 1) {
			return new WP_Error('error', '二维码已失效，请重新扫码', array('status' => 500));
		}
		// 若info存在，那么更新token的status
		$info['status'] = 1;
		// 重新计算过期时间
		$expire_in_seconds = ($info['expire_time'] - time() * 1000) / 1000;
		set_transient('XY_QR_' . $args['token'], $info, $expire_in_seconds);
		return $info;
	}

	function confirmLogin($args) {
		$info = get_transient('XY_QR_' . $args['token']);
		$isContinue = $args['isContinue'];

		if (empty($info)) {
			return new WP_Error('error', '二维码无效', array('status' => 500));
		}
		// 若status不为1，说明token已经失效
		if ($info['status'] !== 1) {
			return new WP_Error('error', '二维码已失效，请重新扫码', array('status' => 500));
		}
		// 若info存在，那么更新token的status
		$info['status'] = $isContinue ? 2 : 3;
		if ($isContinue) {
			// 记录该用户的ID到$info中
			$info['user_id'] = get_current_user_id();
		}
		// 重新计算过期时间
		$expire_in_seconds = ($info['expire_time'] - time() * 1000) / 1000;
		set_transient('XY_QR_' . $args['token'], $info, $expire_in_seconds);
		return new WP_REST_Response('操作成功', 200);
	}

	// 轮询接口，获取当前用户扫码状态
	function getQRStatus($args) {
		$info = get_transient('XY_QR_' . $args['token']);

		if (empty($info)) {
			return new WP_REST_Response(array('status' => -1, 'msg' => '二维码已失效'), 200);
		}
		// 0未扫码 1已扫码（待确认） 2已确认 3已取消 4确认登录
		if ($info['status'] === 0) {
			return new WP_REST_Response(array('status' => 0, 'msg' => '未扫码'), 200);
		} else if ($info['status'] === 1) {
			return new WP_REST_Response(array('status' => 1, 'msg' => '待确认'), 200);
		} else if ($info['status'] === 2) {
			$user = get_user_by('id', $info['user_id']);
			if (!$user) {
				return new WP_Error('invalid_user', 'Invalid user.');
			}

			// Switch to the specified user
			wp_clear_auth_cookie();
			wp_set_current_user($user->ID);
			wp_set_auth_cookie($user->ID);
			return new WP_REST_Response(array('status' => 2, 'msg' => '已确认'), 200);

		} else if ($info['status'] === 3) {
			return new WP_REST_Response(array('status' => 3, 'msg' => '已取消'), 200);
		}
		return new WP_Error('error', '无效二维码', array('status' => 500));
	}
}


// 添加登录页面二维码链接
add_action('login_form', function () {
	$qr_code_url = home_url('/qr-code-login/');
	echo '<div style="position: absolute;top: 5px; right: 5px"><a style="display: block" href="' . $qr_code_url . '" >
					<img style="width: 50px; height: 50px" src="data:image/png;base64,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" alt="">
					</a></div>
					<style>
						#loginform{
							position: relative;
							padding-top: 4	0px;
						}
					</style>';
});

// 添加二维码登录页面
add_action('init', function () {
	if (strpos($_SERVER['REQUEST_URI'], 'qr-code-login') !== false) {
		include REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'includes/templates/qrcode-login.php';
		exit;
	}
});