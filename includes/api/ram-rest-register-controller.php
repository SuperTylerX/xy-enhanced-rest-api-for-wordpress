<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Register_Controller extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'register';
		$this->max_duration = 5; // 注册/找回密码的流程最大时间，单位为分钟
	}

	public function register_routes() {

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getGraphicCaptcha', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'getGraphicCaptcha'),
				'permission_callback' => '__return_true',
				'args' => array(
					'token' => array(
						'required' => false
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getEmailCaptcha', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'getEmailCaptcha'),
				'permission_callback' => array($this, 'get_email_captcha_permission_check'),
				'args' => array(
					'token' => array(
						'required' => true
					),
					'graphicCaptcha' => array(
						'required' => true
					),
					'email' => array(
						'required' => true,
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/submit', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'submit'),
				'permission_callback' => array($this, 'submit_permission_check'),
				'args' => array(
					'token' => array(
						'required' => true
					),
					'graphicCaptcha' => array(
						'required' => true
					),
					'emailCaptcha' => array(
						'required' => true
					),
					'email' => array(
						'required' => true
					),
					'password' => array(
						'required' => true
					),
					'nickname' => array(
						'required' => true
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/reset', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'reset'),
				'permission_callback' => array($this, 'reset_permission_check'),
				'args' => array(
					'token' => array(
						'required' => true
					),
					'graphicCaptcha' => array(
						'required' => true
					),
					'emailCaptcha' => array(
						'required' => true
					),
					'email' => array(
						'required' => true
					),
					'password' => array(
						'required' => true
					)
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/updateEmail', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'update_email'),
				'args' => array(
					'token' => array(
						'required' => true,
						'type' => 'string',
					),
					'graphicCaptcha' => array(
						'required' => true,
						'description' => '图形验证码',
						'type' => 'string',
						'validate_callback' => function ($param) {
							return preg_match('/^[A-Z0-9]{4}$/', $param);
						},
					),
					'emailCaptcha' => array(
						'required' => true,
						'description' => '邮箱验证码',
						'type' => 'string',
						'validate_callback' => function ($param) {
							return preg_match('/^[0-9]{6}$/', $param);
						},
					),
					'email' => array(
						'required' => true,
						'description' => '新邮箱',
						'type' => 'string',
						'validate_callback' => function ($param) {
							return is_email($param);
						},
					),
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/unbindSocial', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'unbind_social'),
				'validate_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'platform' => array(
						'required' => true,
						'description' => '解绑平台',
						'type' => 'string',
						'validate_callback' => function ($param) {
							return in_array($param, array('wechat', 'qq', 'bytedance'));
						},
					),
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 上传头像
		register_rest_route($this->namespace, '/' . $this->resource_name . '/uploadAvatar', array(
			array(
				'methods' => 'POST',
				'callback' => array($this, 'handle_avatar_upload'),
				'permission_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'avatar' => array(
						'required' => true,
						'description' => '头像数据',
						'type' => 'string',
					),
				)
			),
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	function getGraphicCaptcha($request) {
		$width = 120; //设置图片宽为150像素
		$height = 40; //设置图片高为40像素

		$image = imagecreatetruecolor($width, $height); //设置验证码大小的函数
		$bgColor = imagecolorallocate($image, 255, 255, 255); //验证码颜色RGB为(255,255,255) #ffffff
		imagefill($image, 0, 0, $bgColor); //区域填充

		$cap_code = "";
		for ($i = 0; $i < 4; $i++) {
			$fontSize = 8; //设置字体大小
			$fontColor = imagecolorallocate($image, rand(0, 120), rand(0, 120), rand(0, 120)); //数字越大，颜色越浅，这里是深颜色0-120
			$data = 'QWERTYUIPASDFGHJKLZXCVBNM123456789'; //添加字符串
			$fontContent = substr($data, rand(0, strlen($data) - 1), 1); //去除值，字符串截取方法
			$cap_code .= $fontContent; //.=连续定义变量

			//设置坐标
			$x = ($i * $width / 4) + rand(5, 10);
			$y = rand(5, 10);

			// 写入image
			imagestring($image, $fontSize, $x, $y, $fontContent, $fontColor);
		}

		//设置干扰元素，设置雪花点
		for ($i = 0; $i < 300; $i++) {
			$inputColor = imagecolorallocate($image, rand(50, 200), rand(20, 200), rand(50, 200));
			//设置颜色，20-200颜色比数字浅，不干扰阅读
			imagesetpixel($image, rand(1, 149), rand(1, 39), $inputColor);
			//画一个单一像素的元素
		}

		//增加干扰元素，设置横线(先设置线的颜色，在设置横线)
		for ($i = 0; $i < 4; $i++) {
			$lineColor = imagecolorallocate($image, rand(20, 220), rand(20, 220), rand(20, 220));
			//设置线的颜色
			imageline($image, rand(1, 149), rand(1, 39), rand(1, 299), rand(1, 149), $lineColor);
		}

		// 将图片暂存至buffer中
		ob_start();
		imagepng($image);
		imagedestroy($image);
		$buffer = ob_get_clean();
		ob_end_clean();

		// 获取token，若无token，创建出一个新的token，记录验证码和过期时间，
		//           若有token，使用原有的token并覆盖掉原有的验证码和过期时间。
		$token = $request["token"];
		if (!empty($token)) {
			session_id($token);
		}
		session_start(["name" => "token", "use_cookies" => false]);
		$token = session_id();
		$_SESSION["graphic_captcha_challenge_count"] = 0;
		$_SESSION["email_send_count"] = 0; // 重置邮件发送计数
		$_SESSION["uni_captcha"] = $cap_code;
		$_SESSION["uni_captcha_date"] = time();

		// 返回处理的图片，以base64格式返回
		$res["image"] = "data:image/png;base64," . base64_encode($buffer);
		$res["token"] = $token;

		return rest_ensure_response($res);
	}

	function getEmailCaptcha($request) {

		$graphicCaptcha = $request["graphicCaptcha"];
		$token = $request["token"];
		$email = $request["email"];
		session_id($token);
		session_start(["name" => "token", "use_cookies" => false]);
		$_SESSION["email_captcha_challenge_count"] = 0;  // 重置邮件验证码尝试次数
		$_SESSION["email_send_count"] = empty($_SESSION["email_send_count"]) ? 1 : $_SESSION["email_send_count"] + 1;

		if (empty($_SESSION)) {
			$res["code"] = "4001";
			// 实际上是session过期了
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}
		if ($_SESSION["email_send_count"] > 5) {
			// 一个验证码最多允许尝试发送5次邮件，5次后过期重新获取验证码
			$res["code"] = "4001";
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		$server_side_captcha = $_SESSION["uni_captcha"];
		$server_side_captcha_date = $_SESSION["uni_captcha_date"];

		// 也许在某些情况下，SESSION并没有自动过期，于是使用存储的时间戳进行比较
		if (time() - $server_side_captcha_date > 1000 * 60 * $this->max_duration) {
			$res["code"] = "4002";
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		// 验证码尝试失败，最多进行5次尝试
		if ($server_side_captcha !== $graphicCaptcha) {
			$res["code"] = "4000";
			$res["message"] = "验证码错误";
			$_SESSION["graphic_captcha_challenge_count"] += 1;
			if ($_SESSION["graphic_captcha_challenge_count"] > 5) {
				// 图形验证码最多允许尝试5次，5次后过期重新获取
				$res["code"] = "4001";
				$res["message"] = "验证码过期";
				session_destroy();
				return rest_ensure_response($res);
			}
			// 验证码错误，但是允许再次尝试
			return rest_ensure_response($res);
		}
		$email_code = strval(mt_rand(99999, 999999));
		$is_mail_success = wp_mail($email, "[" . get_bloginfo('name') . "] " . "验证码", "您的验证码为【" . $email_code . "】。验证码" . $this->max_duration . "分钟内有效。");

		if (!$is_mail_success) {
			$res["code"] = "4003";
			$res["message"] = "验证码发送失败";
			return rest_ensure_response($res);
		}

		// 将email和校验码存在SEESION中，以备后面校验
		$_SESSION["uni_captcha_email"] = $request["email"];
		$_SESSION["uni_captcha_email_code"] = $email_code;
		$_SESSION["uni_captcha_email_date"] = time();

		$res["code"] = "200";
		$res["message"] = "成功";
		return rest_ensure_response($res);
	}

	function submit($request) {
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$password = $request["password"];
		$nickname = $request["nickname"];
		$token = $request["token"];

		session_id($token);
		session_start(["name" => "token", "use_cookies" => false]);

		if (empty($_SESSION)) {
			$res["code"] = "4001";
			// 实际上是session过期了
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		$server_side_captcha = $_SESSION["uni_captcha"];
		$server_side_captcha_email = $_SESSION["uni_captcha_email"];
		$server_side_captcha_email_code = $_SESSION["uni_captcha_email_code"];
		$server_side_captcha_email_date = $_SESSION["uni_captcha_email_date"];

		// 也许在某些情况下，SESSION并没有自动过期，于是使用存储的时间戳进行比较
		if (time() - $server_side_captcha_email_date > 1000 * 60 * $this->max_duration) {
			$res["code"] = "4002";
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		// 验证码尝试失败，最多进行5次尝试
		if ($server_side_captcha_email_code !== $emailCaptcha ||
			$server_side_captcha !== $graphicCaptcha ||
			$server_side_captcha_email !== $email
		) {
			$res["code"] = "4000";
			$res["message"] = "验证码错误";
			$_SESSION["email_captcha_challenge_count"] += 1;
			if ($_SESSION["email_captcha_challenge_count"] > 5) {
				// 图形验证码最多允许尝试5次，5次后过期重新获取
				$res["code"] = "4001";
				$res["message"] = "验证码过期";
				session_destroy();
				return rest_ensure_response($res);
			}
			// 验证码错误，但是允许再次尝试
			return rest_ensure_response($res);
		}

		// 验证成功，创建新用户
		$new_user_data = apply_filters('new_user_data', array(
			'user_pass' => $password,
			'user_login' => get_random_string(16),
			'user_nicename' => $nickname,
			'user_email' => $email,
			'display_name' => $nickname,
			'nickname' => $nickname,
			'first_name' => $nickname,
		));

		$userId = wp_insert_user($new_user_data);
		if (is_wp_error($userId)) {
			$res["code"] = "5000";
			$res["message"] = $userId->get_error_message();
			session_destroy();
			return rest_ensure_response($res);
		}

		$res["code"] = "200";
		$res["message"] = "注册成功";
		session_destroy();
		return rest_ensure_response($res);
	}

	function reset($request) {
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$password = $request["password"];
		$token = $request["token"];

		session_id($token);
		session_start(["name" => "token", "use_cookies" => false]);

		if (empty($_SESSION)) {
			$res["code"] = "4001";
			// 实际上是session过期了
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		$server_side_captcha = $_SESSION["uni_captcha"];
		$server_side_captcha_email = $_SESSION["uni_captcha_email"];
		$server_side_captcha_email_code = $_SESSION["uni_captcha_email_code"];
		$server_side_captcha_email_date = $_SESSION["uni_captcha_email_date"];

		// 也许在某些情况下，SESSION并没有自动过期，于是使用存储的时间戳进行比较
		if (time() - $server_side_captcha_email_date > 1000 * 60 * $this->max_duration) {
			$res["code"] = "4002";
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		// 验证码尝试失败，最多进行5次尝试
		if ($server_side_captcha_email_code !== $emailCaptcha ||
			$server_side_captcha !== $graphicCaptcha ||
			$server_side_captcha_email !== $email
		) {
			$res["code"] = "4000";
			$res["message"] = "验证码错误";
			$_SESSION["email_captcha_challenge_count"] += 1;
			if ($_SESSION["email_captcha_challenge_count"] > 5) {
				// 图形验证码最多允许尝试5次，5次后过期重新获取
				$res["code"] = "4001";
				$res["message"] = "验证码过期";
				session_destroy();
				return rest_ensure_response($res);
			}
			// 验证码错误，但是允许再次尝试
			return rest_ensure_response($res);
		}
		// 验证成功，更新用户密码

		$the_user = get_user_by_email($email);

		if ($the_user === false) {
			$res["code"] = "5000";
			$res["message"] = "该用户未注册";
			session_destroy();
			return rest_ensure_response($res);
		}
		$userId = wp_update_user(array(
			'user_pass' => $password,
			'user_email' => $email,
			'ID' => $the_user->ID
		));
		if (is_wp_error($userId)) {
			$res["code"] = "5000";
			$res["message"] = $userId->get_error_message();
			session_destroy();
			return rest_ensure_response($res);
		}

		$res["code"] = "200";
		$res["message"] = "更新密码成功";
		session_destroy();
		return rest_ensure_response($res);
	}

	function update_email($request) {
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$token = $request["token"];

		session_id($token);
		session_start(["name" => "token", "use_cookies" => false]);

		if (empty($_SESSION)) {
			$res["code"] = "4001";
			// 实际上是session过期了
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		$server_side_captcha = $_SESSION["uni_captcha"];
		$server_side_captcha_email = $_SESSION["uni_captcha_email"];
		$server_side_captcha_email_code = $_SESSION["uni_captcha_email_code"];
		$server_side_captcha_email_date = $_SESSION["uni_captcha_email_date"];

		// 也许在某些情况下，SESSION并没有自动过期，于是使用存储的时间戳进行比较
		if (time() - $server_side_captcha_email_date > 1000 * 60 * $this->max_duration) {
			$res["code"] = "4002";
			$res["message"] = "验证码过期";
			session_destroy();
			return rest_ensure_response($res);
		}

		// 验证码尝试失败，最多进行5次尝试
		if ($server_side_captcha_email_code !== $emailCaptcha ||
			$server_side_captcha !== $graphicCaptcha ||
			$server_side_captcha_email !== $email
		) {
			$res["code"] = "4000";
			$res["message"] = "验证码错误";
			$_SESSION["email_captcha_challenge_count"] += 1;
			if ($_SESSION["email_captcha_challenge_count"] > 5) {
				// 图形验证码最多允许尝试5次，5次后过期重新获取
				$res["code"] = "4001";
				$res["message"] = "验证码过期";
				session_destroy();
				return rest_ensure_response($res);
			}
			// 验证码错误，但是允许再次尝试
			return rest_ensure_response($res);
		}

		// 验证成功，更新用户邮箱

		$current_user = wp_get_current_user();

		if ($current_user === false) {
			$res["code"] = "5000";
			$res["message"] = "您尚未登录";
			session_destroy();
			return rest_ensure_response($res);
		}
		$userId = wp_update_user(array(
			'user_email' => $email,
			'ID' => $current_user->ID
		));
		if (is_wp_error($userId)) {
			$res["code"] = "5000";
			$res["message"] = $userId->get_error_message();
			session_destroy();
			return rest_ensure_response($res);
		}

		$res["code"] = "200";
		$res["message"] = "更新邮箱成功";
		session_destroy();
		return rest_ensure_response($res);
	}

	function unbind_social($request) {
		$current_user = wp_get_current_user();
		$ID = $current_user->ID;

		// 检查用户是否已经绑定了邮箱
		if (empty($current_user->user_email)) {
			$res["code"] = "500";
			$res["message"] = "您尚未绑定邮箱";
			return rest_ensure_response($res);
		}

		// 获取用户需要解绑的平台
		$platform = $request["platform"];

		// 罗列已有的平台
		switch ($platform) {
			case "qq":
				// 删除meta信息
				delete_user_meta($ID, "qq_unionid");
				delete_user_meta($ID, "qq_mini_openid");
				delete_user_meta($ID, "qq_app_openid");
				delete_user_meta($ID, "qq_h5_openid");
				// 更新社交互联信息
				$social_connect = unserialize(get_user_meta($ID, "social_connect"));
				unset($social_connect["qq"]);
				update_user_meta($ID, "social_connect", serialize($social_connect));

				break;
			case "wechat":
				// 删除meta信息
				delete_user_meta($ID, "wx_unionid");
				delete_user_meta($ID, "wx_mini_openid");
				delete_user_meta($ID, "wx_app_openid");
				delete_user_meta($ID, "wx_h5_openid");
				// 更新社交互联信息
				$social_connect = unserialize(get_user_meta($ID, "social_connect"));
				unset($social_connect["wechat"]);
				update_user_meta($ID, "social_connect", serialize($social_connect));

				break;
			case "bytedance":
				// 删除meta信息
				delete_user_meta($ID, "bytedance_unionid");
				delete_user_meta($ID, "bytedance_mini_openid");
				// 更新社交互联信息
				$social_connect = unserialize(get_user_meta($ID, "social_connect"));
				unset($social_connect["bytedance"]);
				update_user_meta($ID, "social_connect", serialize($social_connect));

				break;
			default:
				$res["code"] = "500";
				$res["message"] = "未查找到平台";
				return rest_ensure_response($res);

		}

		$res["code"] = "200";
		$res["message"] = "解绑成功";
		return rest_ensure_response($res);
	}

	function get_email_captcha_permission_check($request) {
		// 校验网站是否关闭了用户注册
		if (get_option('users_can_register') === "0") {
			return false;
		}
		$graphicCaptcha = $request["graphicCaptcha"];
		$email = $request["email"];
		if (!is_email($email)) {
			return false;
		}
		if (strlen($graphicCaptcha) !== 4) {
			return false;
		}
		return true;
	}

	function submit_permission_check($request) {
		// 校验网站是否关闭了用户注册
		if (get_option('users_can_register') === "0") {
			return false;
		}
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$password = $request["password"];
		$nickname = $request["nickname"];
		if (!is_email($email)) {
			return false;
		}
		if (strlen($graphicCaptcha) !== 4) {
			return false;
		}
		if (strlen($emailCaptcha) !== 6) {
			return false;
		}
		if (strlen($password) > 20 || strlen($password) < 6) {
			return false;
		}
		if (strlen($nickname) > 20) {
			return false;
		}
		return true;
	}

	function reset_permission_check($request) {
		$graphicCaptcha = $request["graphicCaptcha"];
		$emailCaptcha = $request["emailCaptcha"];
		$email = $request["email"];
		$password = $request["password"];
		if (!is_email($email)) {
			return false;
		}
		if (strlen($graphicCaptcha) !== 4) {
			return false;
		}
		if (strlen($emailCaptcha) !== 6) {
			return false;
		}
		if (strlen($password) > 20 || strlen($password) < 6) {
			return false;
		}
		return true;
	}

	function handle_avatar_upload($request) {
		// Check if avatar data is provided
		$avatar_data_base64 = $request->get_param('avatar');

		// 移除base64头部
		$avatar_data_base64_no_head = preg_replace('/^data:image\/\w+;base64,/', '', $avatar_data_base64);

		// Decode the base64 avatar data
		$avatar_data = base64_decode($avatar_data_base64_no_head);

		// Get current user ID
		$user_id = get_current_user_id();

		// Check if uploaded data is an image
		$image = imagecreatefromstring($avatar_data);
		if (empty($image)) {
			return new WP_Error('invalid_image', '无效头像文件', array('status' => 400));
		}

		// 从$avatar_data_base64 base64的head 中使用正则匹配出文件类型
		$extension = preg_replace('/^data:image\/(\w+);base64,.*/', '$1', $avatar_data_base64);
		$extension = $extension === 'jpeg' ? 'jpg' : $extension;

		// 只接受jpg和png格式的头像
		if (!in_array($extension, array('jpg', 'png'))) {
			return new WP_Error('invalid_image', '非支持的图片格式', array('status' => 400));
		}

		// Generate unique filename for the avatar
		$filename = $user_id . '.' . $extension;

		// Upload directory
		$upload_dir = wp_upload_dir();
		$upload_path = $upload_dir['basedir'] . '/avatar/';

		// Create avatar directory if not exists
		if (!file_exists($upload_path)) {
			mkdir($upload_path, 0755, true);
		}

		// Upload the avatar image
		$upload_file = file_put_contents($upload_path . $filename, $avatar_data);
		if ($upload_file !== false) {
			$url = $upload_dir['baseurl'] . '/avatar/' . $filename . '?t=' . time();
		} else {
			// Error uploading avatar
			return new WP_Error('upload_error', '上传失败', array('status' => 500));
		}

		// Update user meta with the new avatar URL
		if (!update_user_meta($user_id, 'avatar', $url)) {
			return new WP_Error('update_error', '更新失败', array('status' => 500));
		}

		return new WP_REST_Response([
			'code' => 'success',
			'message' => '上传成功',
			'data' => [
				'avatarUrl' => $url
			],
		], 200);

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
