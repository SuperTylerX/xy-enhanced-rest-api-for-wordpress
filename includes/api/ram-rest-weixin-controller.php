<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Weixin_Controller extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'weixin';
	}

	public function register_routes() {

		// 获取微信小程序文章二维码
		register_rest_route($this->namespace, '/' . $this->resource_name . '/qrcodeimg', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'POST',
				'callback' => array($this, 'getWinxinQrcodeImg'),
				'args' => array(
					'postid' => array(
						'required' => true
					),
					'path' => array(
						'required' => true
					)
				)

			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 更新用户信息
		register_rest_route($this->namespace, '/' . $this->resource_name . '/updateuserinfo', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'POST',
				'callback' => array($this, 'updateUserInfo'),
				'validate_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'openid' => array(
						'required' => true
					),
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

		// 用户登录
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
			'meta_key' => 'wx_unionid',
			'meta_value' => $unionid
		))[0];
		if (empty($user)) {
			$user = get_users(array(
				'meta_key' => 'wx_' . $platform . '_openid',
				'meta_value' => $openId
			))[0];
		}

		// 是否创建新用户
		if (empty($user)) {
			$meta_input = array();
			// 根据用户平台，设置openid
			switch ($platform) {
				case "mini":
					$meta_input["wx_mini_openid"] = $openId;
					break;
				case "app":
					$meta_input["wx_app_openid"] = $openId;
					break;
				case "h5":
					$meta_input["wx_h5_openid"] = $openId;

			}
			if (!empty($unionid)) {
				$meta_input["wx_unionid"] = $unionid;
			}

			// 设置用户头像
			$meta_input["avatar"] = $avatarUrl;
			// 设定关联平台信息
			$meta_input["social_connect"] = serialize(array(
				"wechat" => "$nickname"
			));
			// 第一次登录，创建新用户
			$userId = wp_insert_user(array(
				'user_login' => $openId,
				'first_name' => $nickname,
				'nickname' => $nickname,
				'user_nicename' => $_nickname,
				'display_name' => $nickname,
				'user_pass' => null,
//				'user_email' => $openId . '@wechat.com',
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

		$response = rest_ensure_response($result);
		return $response;
	}

	// 微信小程序用户登录
	function miniAppLogin($request) {
		// 获取到的用户信息
		$js_code = $request['js_code'];
		$avatarUrl = $request['avatarUrl'];
		$nickname = $request['nickname'];

		// 后台配置参数
		$appid = get_option('wf_appid');
		$appsecret = get_option('wf_secret');

		if (empty($appid) || empty($appsecret)) {
			return new WP_Error('error', 'appid或appsecret为空', array('status' => 500));
		}

		// 获取 openid， 用js_code换取openid
		$access_url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $appid . "&secret=" . $appsecret . "&js_code=" . $js_code . "&grant_type=authorization_code";
		$access_result = https_request($access_url);
		if ($access_result == 'ERROR') {
			return new WP_Error('error', 'API错误：' . json_encode($access_result), array('status' => 501));
		}
		$api_result = json_decode($access_result, true);
		if (empty($api_result['openid']) || empty($api_result['session_key'])) {
			return new WP_Error('error', 'API错误：' . json_encode($api_result), array('status' => 502));
		}
		$openId = $api_result['openid'];
		$unionid = $api_result['unionid'];

		return $this->processLogin($nickname, $avatarUrl, $openId, $unionid, "mini");

	}

	function getWinxinQrcodeImg($request) {
		$postid = $request['postid'];
		$path = $request['path'];

		$qrcodeName = 'qrcode-' . $postid . '.png'; //文章小程序二维码文件名
		$qrcodeurl = REST_API_TO_MINIPROGRAM_PLUGIN_DIR . 'qrcode/' . $qrcodeName; //文章小程序二维码路径
		$qrcodeimgUrl = plugins_url() . '/' . REST_API_TO_MINIPROGRAM_PLUGIN_NAME . '/qrcode/' . $qrcodeName;
		//自定义参数区域，可自行设置
		$appid = get_option('wf_appid');
		$appsecret = get_option('wf_secret');

		//判断文章小程序二维码是否存在，如不存在，在此生成并保存
		if (!is_file($qrcodeurl)) {
			//$ACCESS_TOKEN = getAccessToken($appid,$appsecret,$access_token);
			$access_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret;
			$access_token_result = https_request($access_token_url);
			if ($access_token_result != "ERROR") {
				$access_token_array = json_decode($access_token_result, true);
				if (empty($access_token_array['errcode'])) {
					$access_token = $access_token_array['access_token'];
					if (!empty($access_token)) {

						//接口A小程序码,总数10万个（永久有效，扫码进入path对应的动态页面）
						$url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $access_token;
						//接口B小程序码,不限制数量（永久有效，将统一打开首页，可根据scene跟踪推广人员或场景）
						//$url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$ACCESS_TOKEN;
						//接口C小程序二维码,总数10万个（永久有效，扫码进入path对应的动态页面）
						//$url = 'http://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token='.$ACCESS_TOKEN;

						//header('content-type:image/png');
						$color = array(
							"r" => "0",  //这个颜色码自己到Photoshop里设
							"g" => "0",  //这个颜色码自己到Photoshop里设
							"b" => "0",  //这个颜色码自己到Photoshop里设
						);
						$data = array(
							//$data['scene'] = "scene";//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
							//$data['page'] = "pages/index/index";//扫码后对应的path，只能是固定页面
							'path' => $path, //前端传过来的页面path
							'width' => intval(100), //设置二维码尺寸
							'auto_color' => false,
							'line_color' => $color,
						);
						$data = json_encode($data);
						//可在此处添加或者减少来自前端的字段
						$QRCode = get_content_post($url, $data); //小程序二维码
						if ($QRCode != 'error') {
							//输出二维码
							file_put_contents($qrcodeurl, $QRCode);
							//imagedestroy($QRCode);
							$flag = true;
						}
					} else {
						$flag = false;
					}
				} else {
					$flag = false;
				}
			} else {
				$flag = false;
			}
		} else {

			$flag = true;
		}

		if ($flag) {
			$result["code"] = "success";
			$result["message"] = "小程序码创建成功";
			$result["qrcodeimgUrl"] = $qrcodeimgUrl;
			$result["status"] = "200";
		} else {
			$result["code"] = "success";
			$result["message"] = "小程序码创建失败";
			$result["status"] = "500";
		}

		$response = rest_ensure_response($result);
		return $response;
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
