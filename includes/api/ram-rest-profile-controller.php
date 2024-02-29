<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Profile_Controller extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'profile';
	}

	public function register_routes() {
		// 注册获取某篇文章的评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/getUserProfile', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_user_profile'),
				'args' => array(
					'userId' => array(
						'required' => true,
						'type' => 'integer',
					),
				)

			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	function get_user_profile($request) {
		$userId = $request->get_param('userId');
		$user = get_user_by('id', $userId);

		// 获取该用户的昵称
		$nickname = $user->nickname;
		// 获取该用户的头像
		$avatarUrl = get_avatar_url_2($userId);
		// 获取该用户的注册时间
		$registered = $user->user_registered;
		// 获取该用户的个性签名
		$description = $user->description;
		// 获取该用户角色
		$role = uni_get_user_role($userId);

		// 获取该用户的最后一次评论的IP地址
		$lastComment = get_comments(array(
			'author__in' => array($userId),
			'number' => 1,
			'orderby' => 'comment_date',
			'order' => 'DESC',
		));
		$lastCommentIp = $lastComment ? $lastComment[0]->comment_author_IP : '';

		$lastIp = $lastCommentIp;

		// 获取IP对应的地理位置
		$location = $lastIp ? get_ip_location($lastIp) : array(
			'country_name' => '未知',
			'region_name' => '未知',
			'city_name' => '未知',
		);

		$data = array(
			'nickname' => $nickname,
			'userId' => $userId,
			'avatarUrl' => $avatarUrl,
			'registered' => $registered,
			'description' => $description,
			'location' => $location,
			'role' => $role,
		);

		return new WP_REST_Response($data, 200);
	}
}
