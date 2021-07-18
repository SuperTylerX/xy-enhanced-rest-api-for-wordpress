<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Options_Controller extends WP_REST_Controller {

	public function __construct() {

		$this->namespace = 'watch-life-net/v1';
		$this->resource_name = 'options';
	}

	// Register our routes.
	public function register_routes() {

		register_rest_route($this->namespace, '/' . $this->resource_name . '/homeconfig', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_general_setting'),
				'permission_callback' => array($this, __return_true()),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

	}

	public function get_general_setting($request) {

		$expand = get_option('minapper_expand_settings_page');
		$downloadfileDomain = get_option('wf_downloadfile_domain');
		$businessDomain = get_option('wf_business_domain');
		$result["downloadfileDomain"] = $downloadfileDomain;
		$result["businessDomain"] = $businessDomain;

		$zanImageurl = get_option('wf_zan_imageurl');
		$logoImageurl = get_option('wf_logo_imageurl');
		$result["zanImageurl"] = $zanImageurl;
		$result["logoImageurl"] = $logoImageurl;

		$swipe_nav = $expand['swipe_nav'];
		$selected_nav = $expand['selected_nav'];
		$_expand['swipe_nav'] = $swipe_nav;
		$_expand['selected_nav'] = $selected_nav;
		$result["expand"] = $_expand;

		$result["wf_enable_comment_option"] = empty(get_option('wf_enable_comment_option')) ? "0" : get_option('wf_enable_comment_option');
		$result["wf_enterprise_minapp"] = empty(get_option('wf_enterprise_minapp')) ? "0" : get_option('wf_enterprise_minapp');

		$result["interstitialAdId"] = empty(get_option('wf_interstitial_ad_id')) ? "" : get_option('wf_interstitial_ad_id');
		$result["enable_index_interstitial_ad"] = empty(get_option('enable_index_interstitial_ad')) ? "0" : get_option('enable_index_interstitial_ad');
		$result["enable_detail_interstitial_ad"] = empty(get_option('enable_detail_interstitial_ad')) ? "0" : get_option('enable_detail_interstitial_ad');
		$result["enable_topic_interstitial_ad"] = empty(get_option('enable_topic_interstitial_ad')) ? "0" : get_option('enable_topic_interstitial_ad');
		$result["enable_list_interstitial_ad"] = empty(get_option('enable_list_interstitial_ad')) ? "0" : get_option('enable_list_interstitial_ad');
		$result["enable_hot_interstitial_ad"] = empty(get_option('enable_hot_interstitial_ad')) ? "0" : get_option('enable_hot_interstitial_ad');
		$result["enable_comments_interstitial_ad"] = empty(get_option('enable_comments_interstitial_ad')) ? "0" : get_option('enable_comments_interstitial_ad');
		$result["enable_live_interstitial_ad"] = empty(get_option('enable_live_interstitial_ad')) ? "0" : get_option('enable_comments_interstitial_ad');

		$response = rest_ensure_response($result);
		return $response;

	}

}