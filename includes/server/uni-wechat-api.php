<?php

//禁止直接访问
if (!defined('ABSPATH')) exit;

class UniWechatAPI {

	public static $API_URL = array(
		'msgSecCheck' => 'https://api.weixin.qq.com/wxa/msg_sec_check',
		'sendMessage' => 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send',
	);

	// 获取Access Token
	public function get_access_token() {

		// 读取微信 AppID 和 AppSecret
		$appid = get_option('wf_appid');
		$secret = get_option('wf_secret');

		$access_token = get_option('uni_wechat_access_token');

		// 读取缓存
		if (!empty($access_token) && time() < $access_token['expire_time']) {
			return $access_token['access_token'];
		}

		// 未缓存或者缓存过期
		$api_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
		$response = wp_remote_get($api_url);

		if (!is_wp_error($response) && is_array($response) && isset($response['body'])) {
			$result = json_decode($response['body'], true);
			if (!isset($result['errcode']) || $result['errcode'] == 0) {
				$access_token = array(
					'access_token' => $result['access_token'],
					'expire_time' => time() + intval($result['expires_in']) - 300
				);
				update_option('uni_wechat_access_token', $access_token);
				return $access_token['access_token'];
			}
		}
		return false;
	}

	// 发起API请求
	private function request($url, $method, $body) {

		if (strpos($url, 'msg_sec_check') != false) {
			//内容安全检测不进行unicode转码
			$body = json_encode($body, JSON_UNESCAPED_UNICODE);

		} else {
			$body = json_encode($body);

		}
		$response = wp_remote_request($url, array(
			'method' => $method,
			'body' => $body
		));

		return !is_wp_error($response) ? json_decode($response['body'], true) : false;
	}

	public function invokingRequest($api, $data) {
		$access_token = $this->get_access_token();
		$access_token = $access_token ? '?access_token=' . $access_token : '';
		$api_url = UniWechatAPI::$API_URL[$api];
		$result = "";
		if (!empty($access_token)) {
			$api_url = $api_url . $access_token;
			$result = $this->request($api_url, 'POST', $data);
		}
		return $result;
	}

	// 文字内容审查
	public function msgSecCheck($data) {
		return $this->invokingRequest('msgSecCheck', $data);
	}

	// 发送订阅消息
	public function sendMessage($data) {
		return $this->invokingRequest('sendMessage', $data);
	}

}

if (!empty(get_option('uni_enable_weixin_push'))) {
	add_action('wp_insert_comment', 'send_wx_notification_on_new_comment', 10, 2);
	add_action('wp_insert_comment', 'send_wx_notification_on_new_reply', 10, 2);
}

// 收到文章评论，给文章作者推送消息，点击通知后，会跳转到文章详情页
function send_wx_notification_on_new_comment($comment_id, $comment_object) {

	$post_id = $comment_object->comment_post_ID;
	// 获取文章作者的ID
	$user_id = get_post_field('post_author', $post_id);

	// 若文章作者是当前评论者，则不发送推送
	if ($user_id == $comment_object->user_id) {
		return;
	}

	if ($user_id) {
		// 获取用户的openid
		$openid = get_user_meta($user_id, 'wx_mini_openid', true);

		if ($openid) {
			$uniWechatAPI = new UniWechatAPI();

			echo '$post_id: ' . $post_id;

			$result = $uniWechatAPI->sendMessage([
				'touser' => $openid,
				'template_id' => get_option('uni_weixin_comment_template_id'),
				'page' => '/pages/post/post?id=' . $post_id,
				'miniprogram_state' => 'formal',
				'lang' => 'zh_CN',
				'data' => [
					'thing4' => [
						// 评论用户名称
						'value' => get_comment_author($comment_object->comment_ID)
					],
					'thing1' => [
						// 文章标题，截取前17个字符，过长的话，显示...
						'value' => mb_strimwidth(get_the_title($post_id), 0, 17, '...')
					],
					'thing2' => [
						// 评论内容，截取前17个字符，过长的话，显示...
						'value' => mb_strimwidth($comment_object->comment_content, 0, 17, '...')
					],
					'time3' => [
						// 评论时间，格式为 2019年10月1日 15:01
						'value' => get_comment_date('Y年m月d日 H:i', $comment_object->comment_ID)
					]]]);

		}
	}
}

// 收到评论回复，给评论作者推送消息，点击通知后，会跳转到文章详情页
function send_wx_notification_on_new_reply($comment_id, $comment_object) {

	$parent_comment_id = $comment_object->comment_parent;
	$parent_comment = get_comment($parent_comment_id);

	// 若评论作者是当前评论者，则不发送推送
	if ($parent_comment->user_id == $comment_object->user_id) {
		return;
	}
	// 若评论是一级评论，则不发送推送
	if ($parent_comment->comment_parent != 0) {
		return;
	}

	// 获取评论作者的ID
	$user_id = $parent_comment->user_id;

	if ($user_id) {
		// 获取用户的openid
		$openid = get_user_meta($user_id, 'wx_mini_openid', true);

		if ($openid) {
			$uniWechatAPI = new UniWechatAPI();

			$post_id = $comment_object->comment_post_ID;

			$result = $uniWechatAPI->sendMessage([
				'touser' => $openid,
				'template_id' => get_option('uni_weixin_comment_reply_template_id'),
				'page' => '/pages/post/post?id=' . $post_id,
				'miniprogram_state' => 'formal',
				'lang' => 'zh_CN',
				'data' => [
					'thing3' => [
						// 回复者名称
						'value' => get_comment_author($comment_object->comment_ID)
					],
					'thing1' => [
						// 原评论，截取前17个字符，过长的话，显示...
						'value' => mb_strimwidth($parent_comment->comment_content, 0, 17, '...')
					],
					'thing2' => [
						// 评论内容，截取前17个字符，过长的话，显示...
						'value' => mb_strimwidth($comment_object->comment_content, 0, 17, '...')
					],
					'time4' => [
						// 评论时间，格式为 2019年10月1日 15:01
						'value' => get_comment_date('Y年m月d日 H:i', $comment_object->comment_ID)
					]]]);

		}
	}
}
