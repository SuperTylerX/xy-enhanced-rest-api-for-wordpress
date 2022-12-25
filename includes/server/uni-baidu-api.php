<?php

//禁止直接访问
if (!defined('ABSPATH')) exit;

class UniBaiduAPI {

	public static $API_URL = array(
		'msgSecCheck' => 'https://openapi.baidu.com/rest/2.0/smartapp/riskDetection/v2/syncCheckText',
	);

	// 获取Access Token
	public function get_access_token() {

		// 读取头条 AppID 和 AppSecret
		$appid = get_option('uni_baidu_appid');
		$secret = get_option('uni_baidu_secret');
		$key = get_option('uni_baidu_key');

		$access_token = get_option('uni_baidu_access_token');

		// 读取缓存
		if (!empty($access_token) && time() < $access_token['expire_time']) {
			return $access_token['access_token'];
		}

		// 未缓存或者缓存过期
		$api_url = 'https://openapi.baidu.com/oauth/2.0/token';
		$response = wp_remote_get($api_url . '?' . http_build_query(array(
				'client_id' => $key,
				'client_secret' => $secret,
				'grant_type' => 'client_credentials',
				'scope' => 'smartapp_snsapi_base',
			)));

		if (!is_wp_error($response) && is_array($response) && isset($response['body'])) {
			$result = json_decode($response['body'], true);
			if (!isset($result['err_no']) || $result['err_no'] === 0) {
				$access_token = array(
					'access_token' => $result['access_token'],
					'expire_time' => time() + intval($result['expires_in']) - 300
				);
				update_option('uni_baidu_access_token', $access_token);
				return $access_token['access_token'];
			}
		}
		return false;
	}

	// 发起API请求
	private function request($url, $method, $body) {
		if (strpos($url, 'syncCheckText')) {
			// 内容安全检测不进行unicode转码
			$body = json_encode($body, JSON_UNESCAPED_UNICODE);
		} else {
			$body = json_encode($body);
		}
		$access_token = $this->get_access_token();
		$response = wp_remote_request($url . '?access_token=' . $access_token, array(
			'method' => $method,
			'body' => $body,
			'headers' => array(
				'Content-Type' => 'application/json'
			)
		));

		return !is_wp_error($response) ? json_decode($response['body'], true) : false;
	}

	public function invokingRequest($api, $data) {
		$api_url = UniBaiduAPI::$API_URL[$api];
		return $this->request($api_url, 'POST', $data);
	}

	// 文字内容审查
	public function msgSecCheck($data) {
		return $this->invokingRequest('msgSecCheck', $data);
	}

}

