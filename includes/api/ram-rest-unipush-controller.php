<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Unipush_Controller extends WP_REST_Controller {

	public function __construct() {

		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'unipush';
	}

	// Register our routes.
	public function register_routes() {

		register_rest_route($this->namespace, '/' . $this->resource_name . '/registerCid', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'POST',
				'callback' => array($this, 'registerCid'),
				'args' => array(
					'cid' => array(
						'required' => true,
						'type' => 'string',
						'description' => 'cid',
					),
				),
				'permission_callback' => 'jwt_permissions_check',
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));


	}

	// 注册CID
	function registerCid($request): WP_REST_Response {
		$cid = $request['cid'];
		$userId = get_current_user_id();

		// 保存CID
		$result = update_user_meta($userId, 'cid', $cid);

		return new WP_REST_Response([
			'code' => 200,
			'msg' => $result,
		], 200);
	}

}