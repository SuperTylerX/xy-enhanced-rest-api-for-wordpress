<?php

//禁止直接访问
if (!defined('ABSPATH')) exit;

class UniByteDanceAPI {

	public static $API_URL = array(
		'msgSecCheck' => 'https://developer.toutiao.com/api/v2/tags/text/antidirt',
	);

	// 获取Access Token
	public function get_access_token() {

		// 读取头条 AppID 和 AppSecret
		$appid = get_option('uni_bytedance_appid');
		$secret = get_option('uni_bytedance_secret');

		$access_token = get_option('uni_bytedance_access_token');


		// 读取缓存
		if (!empty($access_token) && time() < $access_token['expire_time']) {
			return $access_token['access_token'];
		}

		// 未缓存或者缓存过期
		$api_url = 'https://developer.toutiao.com/api/apps/v2/token';
		$response = wp_remote_post($api_url, array(
			'body' => json_encode(array(
				'appid' => $appid,
				'secret' => $secret,
				'grant_type' => 'client_credential'
			)),
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		));

		if (!is_wp_error($response) && is_array($response) && isset($response['body'])) {
			$result = json_decode($response['body'], true);
			if (!isset($result['err_no']) || $result['err_no'] === 0) {
				$access_token = array(
					'access_token' => $result['data']['access_token'],
					'expire_time' => time() + intval($result['data']['expires_in']) - 300
				);
				update_option('uni_bytedance_access_token', $access_token);
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
			'body' => $body,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Token' => $this->get_access_token()
			)
		));

		return !is_wp_error($response) ? json_decode($response['body'], true) : false;
	}

	public function invokingRequest($api, $data) {
		$api_url = UniByteDanceAPI::$API_URL[$api];
		return $this->request($api_url, 'POST', $data);
	}

	// 文字内容审查
	public function msgSecCheck($data) {
		return $this->invokingRequest('msgSecCheck', $data);
	}

}

