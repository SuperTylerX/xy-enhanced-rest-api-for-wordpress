<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Posts_Controller extends WP_REST_Controller {

	public function __construct() {

		$this->namespace = 'uni-app-rest-enhanced/v1';
		$this->resource_name = 'post';
	}

	// Register our routes.
	public function register_routes() {
		// 点赞
		register_rest_route($this->namespace, '/' . $this->resource_name . '/like', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'POST',
				'callback' => array($this, 'postLike'),
				'permission_callback' => array($this, 'jwt_permissions_check'),
				'args' => array(
					'postid' => array(
						'required' => true
					)
				)
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 获取点赞
		register_rest_route($this->namespace, '/' . $this->resource_name . '/mylike', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'POST',
				'callback' => array($this, 'getmyLike'),
				'permission_callback' => array($this, 'jwt_permissions_check')
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getTopCommentPosts', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getTopHotCommentPosts'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getTopLikePosts', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getTopLikePosts'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/getTopPageViewPosts', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getTopPageViewsPosts'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	function getTopPageViewsPosts($request) {
		$cachedata = '';
		if (function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->get_cache();
			if (!empty($cachedata)) {

				$response = rest_ensure_response($cachedata);
				return $response;
			}
		}
		global $wpdb;

		$query = "
        SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_date, $wpdb->posts.post_content, SUM($wpdb->postmeta.meta_value) AS views
        FROM $wpdb->posts
        INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
        WHERE $wpdb->posts.post_type = 'post'
        AND $wpdb->posts.post_status = 'publish'
        AND $wpdb->postmeta.meta_key = 'views' -- 假设阅读量存储在名为 'views' 的自定义字段中
        -- AND $wpdb->posts.post_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        GROUP BY $wpdb->posts.ID
        ORDER BY views DESC
        LIMIT 20
    ";

		$results = $wpdb->get_results($query);

		$posts = $this->handlePostListResult($results);

		if ($cachedata == '' && function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->set_cache($posts, 'pageviewsthisyear', 0);
		}
		return rest_ensure_response($posts);
	}

	function getTopLikePosts($request) {
		global $wpdb;
		$cachedata = '';
		if (function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->get_cache();
			if (!empty($cachedata)) {

				$response = rest_ensure_response($cachedata);
				return $response;
			}
		}
		$query = "
        SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_date, $wpdb->posts.post_content, SUM($wpdb->postmeta.meta_value) AS views, SUM($wpdb->postmeta.meta_value) AS approval_count
        FROM $wpdb->posts
        INNER JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
        WHERE $wpdb->posts.post_type = 'post'
        AND $wpdb->posts.post_status = 'publish'
        AND $wpdb->postmeta.meta_key = 'postApprovalCount' -- 假设点赞数存储在名为 'postApprovalCount' 的自定义字段中
        -- AND $wpdb->posts.post_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        GROUP BY $wpdb->posts.ID
        ORDER BY approval_count DESC
        LIMIT 20
    ";
		$results = $wpdb->get_results($query);

		$posts = $this->handlePostListResult($results);

		if ($cachedata == '' && function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->set_cache($posts, 'likethisyear', 0);
		}

		$response = rest_ensure_response($posts);
		return $response;
	}

	function getTopHotCommentPosts($request) {
		$cachedata = '';
		if (function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->get_cache();
			if (!empty($cachedata)) {
				return rest_ensure_response($cachedata);
			}
		}

		global $wpdb;
		// 查询一个月内有评论的文章ID及其评论数量
		$query = "
		    SELECT p.ID, p.post_title, p.post_date, p.post_content, 
		           SUM(pm.meta_value) AS views, COUNT(c.comment_post_ID) AS comment_count
		    FROM {$wpdb->prefix}posts p
		    LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'views'
		    LEFT JOIN {$wpdb->prefix}comments c ON p.ID = c.comment_post_ID
		    WHERE p.post_type = 'post' AND p.post_status = 'publish'  
        	-- AND  p.post_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
		    GROUP BY p.ID, p.post_title, p.post_date, p.post_content
		    ORDER BY comment_count DESC
		    LIMIT 20; ";

		$results = $wpdb->get_results($query);

		$posts = $this->handlePostListResult($results);

		if ($cachedata == '' && function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->set_cache($posts, 'hotpostthisyear', 0);
		}

		return rest_ensure_response($posts);
	}

	function handlePostListResult($results) {
		global $wpdb;
		$posts = array();
		foreach ($results as $post) {
			$post_id = (int)$post->ID;
			$post_title = stripslashes($post->post_title);
			$pageviews = (int)$post->views;
			$post_date = $post->post_date;
			$post_permalink = get_permalink($post->ID);
			$_data["id"] = $post_id;
			$_data["title"] = ['rendered' => $post_title];
			$_data["pageviews"] = $pageviews;
			$_data["date"] = $post_date;
			$_data["post_permalink"] = $post_permalink;
			$_data['like_count'] = (int)get_post_meta($post_id, 'postApprovalCount', true);
			$_data['total_comments'] = $wpdb->get_var("SELECT COUNT(1) FROM " . $wpdb->comments . " where  comment_approved = '1' and comment_post_ID=" . $post_id);

			$images = getPostImages($post->post_content, $post_id);

			$_data['post_thumbnail_image'] = $images['post_thumbnail_image'];
			$_data['post_first_image'] = $images['post_first_image'];
			$_data['post_medium_image'] = $images['post_medium_image'];
			$_data['post_large_image'] = $images['post_large_image'];
			$_data['post_full_image'] = $images['post_full_image'];
			$_data['post_all_images'] = $images['post_all_images'];
			$posts[] = $_data;
		}
		return $posts;
	}

	public function getmyLike($request) {
		global $wpdb;

		$current_user = wp_get_current_user();
		$userid = $current_user->ID;
		$userApprovalPosts = get_user_meta($userid, 'userApprovalPosts', true);
		if (empty($userApprovalPosts)) {
			$userApprovalPosts = [];
		}
		$posts_str = implode(",", $userApprovalPosts);

		$sql = "SELECT *  from " . $wpdb->posts . " where id in(" . $posts_str . ") ORDER BY find_in_set(id,'" . $posts_str . "')";
		$_posts = $wpdb->get_results($sql);

		$posts = array();
		foreach ($_posts as $post) {

			$post_id = $post->ID;
			$_data["id"] = $post_id;
			$_data["title"] = ['rendered' => $post->post_title];
			$_data["post_medium_image"] = getPostImages($post->post_content, $post->ID)["post_medium_image"];
			// 添加日期
			$post_date = $post->post_date;
			$_data["date"] = $post_date;
			// 添加评论数
			$comments_count = wp_count_comments($post_id);
			$_data['total_comments'] = $comments_count->approved;

			// 添加点赞数
			$like_count = get_post_meta($post_id, 'postApprovalCount', true);
			if (empty($like_count)) {
				$_data['like_count'] = 0;
			} else {
				$_data['like_count'] = $like_count;
			}

			// 添加阅读数
			$post_views = (int)get_post_meta($post_id, 'views', true);
			$_data['pageviews'] = $post_views;

			$posts[] = $_data;
		}

		$result["code"] = "success";
		$result["message"] = "获取我点赞的文章成功";
		$result["data"] = $posts;

		return rest_ensure_response($result);
	}

	public function postLike($request) {
		$postid = $request['postid'];
		$current_user = wp_get_current_user();
		$userid = $current_user->ID;

		$postApprovalUsers = get_post_meta($postid, 'postApprovalUsers', true);
		$postApprovalCount = get_post_meta($postid, 'postApprovalCount', true);
		$userApprovalPosts = get_user_meta($userid, 'userApprovalPosts', true);

		$result = array();
		if (empty($postApprovalUsers)) {
			$postApprovalUsers = [$userid];
			$postApprovalCount = 1;

			if (empty($userApprovalPosts)) {
				$userApprovalPosts = [$postid];
			} else {
				$userApprovalPosts[] = $postid;
			}
			$result["code"] = "like_success";
			$result["message"] = "点赞成功";
		} else {
			if (!in_array($userid, $postApprovalUsers)) {
				$postApprovalUsers[] = $userid;
				$postApprovalCount += 1;

				if (empty($userApprovalPosts)) {
					$userApprovalPosts = [$postid];
				} else {
					$userApprovalPosts[] = $postid;
				}

				$result["code"] = "like_success";
				$result["message"] = "点赞成功";
			} else {
				$key = array_search($userid, $postApprovalUsers);
				unset($postApprovalUsers[$key]);
				$postApprovalCount -= 1;

				$key = array_search($postid, $userApprovalPosts);
				unset($userApprovalPosts[$key]);

				$result["code"] = "cancel_like_success";
				$result["message"] = "取消点赞成功";
			}
		}

		if (update_post_meta($postid, 'postApprovalUsers', $postApprovalUsers) &&
			update_post_meta($postid, 'postApprovalCount', $postApprovalCount) &&
			update_user_meta($userid, 'userApprovalPosts', $userApprovalPosts)
		) {
			if (function_exists('MRAC')) {
				$cachedata = MRAC()->cacheManager->delete_cache('post', $postid);
			}
		} else {
			return new WP_Error('error', '点赞失败', array('status' => "500"));
		}

		return rest_ensure_response($result);
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
