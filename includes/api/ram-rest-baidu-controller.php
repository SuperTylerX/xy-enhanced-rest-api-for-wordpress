<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Baidu_Controller extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'baidu';
	}

	public function register_routes() {

		// 更新用户信息
		register_rest_route($this->namespace, '/' . $this->resource_name . '/updateUserInfo', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'POST',
				'callback' => array($this, 'updateUserInfo'),
				'validate_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'avatarUrl' => array(
						'required' => true
					),
					'nickname' => array(
						'required' => true
					)
				)

			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 小程序用户登录
		register_rest_route($this->namespace, '/' . $this->resource_name . '/miniAppLogin', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'miniAppLogin'),
				'args' => array(
					'avatarUrl' => array(
						'required' => true
					),
					'nickname' => array(
						'required' => true
					),
					'js_code' => array(
						'required' => true
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	// 更新用户信息
	function updateUserInfo($request) {
		$nickname = $request['nickname'];
		$nickname = filterEmoji($nickname);
		$_nickname = base64_encode($nickname);
		$_nickname = strlen($_nickname) > 49 ? mb_substr($_nickname, 49) : $_nickname;
		$avatarUrl = $request['avatarUrl'];

		$current_user = wp_get_current_user();
		$ID = $current_user->ID;

		$userdata = array(
			'ID' => $ID,
			'first_name' => $nickname,
			'nickname' => $nickname,
			'user_nicename' => $_nickname,
			'display_name' => $nickname,
			'meta_input' => array(
				"avatar" => $avatarUrl
			)
		);
		$userId = wp_update_user($userdata);
		if (is_wp_error($userId)) {
			return new WP_Error('error', '更新wp用户错误：', array('status' => 500));
		}

		$result["code"] = "success";
		$result["message"] = "更新成功";
		$result["status"] = "200";
		return rest_ensure_response($result);
	}

	function processLogin($nickname, $avatarUrl, $openId) {
		$nickname = filterEmoji($nickname);
		$_nickname = base64_encode($nickname);
		$_nickname = strlen($_nickname) > 49 ? substr($_nickname, 49) : $_nickname;

		$user = get_users(array(
			'meta_key' => 'baidu_mini_openid',
			'meta_value' => $openId
		))[0];

		// 是否创建新用户
		if (empty($user)) {
			$meta_input = array();
			$meta_input["baidu_mini_openid"] = $openId;

			// 设置用户头像
			$meta_input["avatar"] = $avatarUrl;
			// 设定关联平台信息
			$meta_input["social_connect"] = serialize(array(
				"baidu" => "$nickname"
			));
			// 第一次登录，创建新用户
			$userId = wp_insert_user(array(
				'user_login' => $openId,
				'first_name' => $nickname,
				'nickname' => $nickname,
				'user_nicename' => $_nickname,
				'display_name' => $nickname,
				'user_pass' => null,
//				'user_email' => $openId . '@baidu.com',
				'meta_input' => $meta_input
			));
			if (is_wp_error($userId) || empty($userId) || $userId == 0) {
				return new WP_Error('error', '插入wordpress用户错误：', array('status' => 500));
			}
		} else {
			$userId = $user->ID;
		}

		$result["code"] = "success";
		$result["message"] = "获取用户信息成功";
		$result["status"] = "200";
		$result["openid"] = $openId;
		$result["userId"] = $userId;

		// 使用JWT插件签发 Token
		$issuedAt = time();
		$notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
		$expire = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);

		$token = array(
			'iss' => get_bloginfo('url'),
			'iat' => $issuedAt,
			'nbf' => $notBefore,
			'exp' => $expire,
			'data' => array(
				'user' => array(
					'id' => $userId,
				),
			),
		);

		$secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;

		$token = \Firebase\JWT\JWT::encode(apply_filters('jwt_auth_token_before_sign', $token, $user), $secret_key);
		$result["token"] = $token;

		return rest_ensure_response($result);
	}

	// 用户小程序登录
	function miniAppLogin($request) {
		$code = $request['js_code'];
		$avatarUrl = $request['avatarUrl'];
		$nickname = $request['nickname'];

		$appid = get_option('uni_baidu_appid');
		$appsecret = get_option('uni_baidu_secret');
		$appkey = get_option('uni_baidu_key');

		if (empty($appid) || empty($appsecret)) {
			return new WP_Error('error', 'appid或appsecret为空', array('status' => 500));
		}

		// 获取 openid， 用js_code换取openid
		$access_url = "https://spapi.baidu.com/oauth/jscode2sessionkey";


		$access_result = wp_remote_request($access_url, array(
			'method' => 'POST',
			'body' => json_encode(array(
				'client_id' => $appkey,
				'code' => $code,
				'sk' => $appsecret,
			)),
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		));


		if (is_wp_error($access_result)) {
			return new WP_Error('error', '获取access_token错误', array('status' => 500));
		}

		$api_result = json_decode($access_result['body'], true);

		if (empty($api_result['openid']) || empty($api_result['session_key'])) {
			return new WP_Error('error', 'API错误：' . json_encode($api_result), array('status' => 502));
		}

		$openId = $api_result['openid'];

		return $this->processLogin($nickname, $avatarUrl, $openId);

	}

	public function jwt_permissions_check($request) {
		$current_user = wp_get_current_user();
		$ID = $current_user->ID;
		if ($ID == 0) {
			return new WP_Error('error', '尚未登录或Token无效', array('status' => 400));
		}
		return true;
	}
}
