<?php

if (!defined('ABSPATH')) {
	exit;
}
//require_once(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . "includes/alipay/AopSdk.php");
require_once(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . "includes/alipay/aop/AopClient.php");
require_once(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . "includes/alipay/aop/SignData.php");
require_once(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . "includes/alipay/aop/request/AlipaySystemOauthTokenRequest.php");
require_once(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . "includes/alipay/aop/request/AlipayUserInfoShareRequest.php");
require_once(REST_API_TO_MINIPROGRAM_PLUGIN_DIR . "includes/alipay/aop/request/AlipayOpenAppMiniTemplatemessageSendRequest.php");

class RAM_REST_Alipay_Controller extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'alipay';
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

	function processLogin($nickname, $avatarUrl, $openId, $unionid, $platform) {
		$nickname = filterEmoji($nickname);
		$_nickname = base64_encode($nickname);
		$_nickname = strlen($_nickname) > 49 ? substr($_nickname, 49) : $_nickname;

		$user = get_users(array(
			'meta_key' => 'alipay_unionid',
			'meta_value' => $unionid
		))[0];
		if (empty($user)) {
			$user = get_users(array(
				'meta_key' => 'alipay_' . $platform . '_openid',
				'meta_value' => $openId
			))[0];
		}

		// 是否创建新用户
		if (empty($user)) {
			$meta_input = array();
			if ($platform == "mini") {
				$meta_input["alipay_mini_openid"] = $openId;
			}
			if (!empty($unionid)) {
				$meta_input["alipay_unionid"] = $unionid;
			}

			// 设置用户头像
			$meta_input["avatar"] = $avatarUrl;
			// 设定关联平台信息
			$meta_input["social_connect"] = serialize(array(
				"alipay" => "$nickname"
			));
			// 第一次登录，创建新用户
			$userId = wp_insert_user(array(
				'user_login' => $openId,
				'first_name' => $nickname,
				'nickname' => $nickname,
				'user_nicename' => $_nickname,
				'display_name' => $nickname,
				'user_pass' => null,
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
		$authCode = $request['js_code'];
//		$avatarUrl = $request['avatarUrl'];
//		$nickname = $request['nickname'];

		// appid
		$appid = get_option('uni_alipay_appid');
		//应用私钥
		$rsaPrivateKey = get_option('uni_alipay_private_secret');
		//支付宝公钥
		$alipayrsaPublicKey = get_option('uni_alipay_public_secret');

		if (empty($appid) || empty($rsaPrivateKey) || empty($alipayrsaPublicKey)) {
			return new WP_Error('error', 'appid、应用私钥或支付宝公钥为空', array('status' => 500));
		}

		// 初始化
		$aop = new AopClient();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = $appid;
		$aop->rsaPrivateKey = $rsaPrivateKey;
		$aop->alipayrsaPublicKey = $alipayrsaPublicKey;
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset = 'UTF-8';
		$aop->format = 'json';

		//获取access_token
		$systemOauthTokenRequest = new AlipaySystemOauthTokenRequest();
		$systemOauthTokenRequest->setGrantType("authorization_code");
		$systemOauthTokenRequest->setCode($authCode); //这里传入 code
		$executeResult = $aop->execute($systemOauthTokenRequest);

		if (isset($executeResult->error_response)) {
			$error_response = $executeResult->error_response;
			return new WP_Error('error', 'API错误：' . $error_response->msg, array('status' => 501));
		}
		$alipay_system_oauth_token_response = isset($executeResult->alipay_system_oauth_token_response) ? $executeResult->alipay_system_oauth_token_response : null;
		if (empty($alipay_system_oauth_token_response)) {
			return new WP_Error('error', 'API错误：alipay_system_oauth_token_response 对象为空', array('status' => 501));
		}
		$responseCode = $alipay_system_oauth_token_response->code;
		if (!empty($responseCode) && $responseCode != 10000) {
			$msg = $alipay_system_oauth_token_response->sub_msg;
			return new WP_Error('error', 'responseCode:' . $responseCode . ',message:' . $msg, array('status' => 501));
		}


		$access_token = $alipay_system_oauth_token_response->access_token;
		$refresh_token = $alipay_system_oauth_token_response->refresh_token;
		$unionId = $alipay_system_oauth_token_response->union_id;
		$openId = $alipay_system_oauth_token_response->open_id;
		if (empty($openId)) {
			// 若没有openId，则使用user_id
			$openId = $alipay_system_oauth_token_response->user_id;
		}

		$request = new AlipayUserInfoShareRequest ();
		$result = $aop->execute($request, $access_token);

		$response = $result->alipay_user_info_share_response;
		$resultCode = $response->code;

		if (!empty($resultCode) && $resultCode == 10000) {
			$avatarUrl = $response->avatar;
			$nickname = $response->nick_name;
		} else {
			return new WP_Error('error', '获取用户信息错误：' . $result->msg, array('status' => 501));
		}

		return $this->processLogin($nickname, $avatarUrl, $openId, $unionId, "mini");

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
