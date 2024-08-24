<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Options_Controller extends WP_REST_Controller {

	public function __construct() {

		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'options';
	}

	// Register our routes.
	public function register_routes() {

		register_rest_route($this->namespace, '/' . $this->resource_name . '/homeconfig', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_general_setting'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getAppUpdatedVersion', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getAppUpdatedVersion'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

	}

	public function get_general_setting($request) {

		$expand = get_option('minapper_expand_settings_page');
		if (!empty($expand)) {
			$result["expand"] = ['swipe_nav' => $expand['swipe_nav'], 'selected_nav' => $expand['selected_nav']];
		} else {
			$result["expand"] = ['swipe_nav' => [], 'selected_nav' => []];
		}


		$result["blogName"] = get_option('blogname'); // 获取网站标题
		$result["blogDescription"] = get_option('blogdescription'); // 获取网站副标题

		$result["logoImageUrl"] = get_option('uni_logo_imageurl');
		$result["shareImageUrl"] = get_option('uni_share_imageurl');

		$result["uni_h5_qq_client_id"] = get_option('uni_h5_qq_client_id');
		$result["uni_h5_qq_callback_url"] = get_option('uni_h5_qq_callback_url');

		$result["uni_enable_weixin_comment_option"] = !empty(get_option('uni_enable_weixin_comment_option'));
		$result["uni_enable_qq_comment_option"] = !empty(get_option('uni_enable_qq_comment_option'));
		$result["uni_enable_bytedance_comment_option"] = !empty(get_option('uni_enable_bytedance_comment_option'));
		$result["uni_enable_baidu_comment_option"] = !empty(get_option('uni_enable_baidu_comment_option'));
		$result["uni_enable_alipay_comment_option"] = !empty(get_option('uni_enable_alipay_comment_option'));
		$result["uni_enable_h5_comment_option"] = !empty(get_option('uni_enable_h5_comment_option'));
		$result["uni_weixin_enterprise_minapp"] = !empty(get_option('uni_weixin_enterprise_minapp'));
		$result["uni_qq_enterprise_minapp"] = !empty(get_option('uni_qq_enterprise_minapp'));
		$result["is_user_registration_enable"] = get_option('users_can_register') === "1";
		$result["uni_enable_weixin_push"] = !empty(get_option("uni_enable_weixin_push"));
		$result["uni_weixin_comment_template_id"] = get_option("uni_weixin_comment_template_id") ?? '';
		$result["uni_weixin_comment_reply_template_id"] = get_option("uni_weixin_comment_reply_template_id") ?? '';

		return rest_ensure_response($result);
	}


	public function getAppUpdatedVersion($request) {
		return array(
			"updatedVersion" => get_option('uni_app_updated_version'),
			"updatedVersionCode" => (int)get_option('uni_app_updated_version_code'),
			"downloadLink" => get_option('uni_app_updated_download_link'),
			"iosDownloadLink" => get_option('uni_app_updated_ios_download_link'),
			"isForceUpdate" => !empty(get_option('uni_app_force_update')),
			"updateLog" => get_option('uni_app_updated_log'),
		);
	}
}