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

		register_rest_route($this->namespace, '/' . $this->resource_name . '/hotpostthisyear', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getTopHotPostsThisYear'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/hotpost', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getTopHotPosts'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/likethisyear', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getTopLikePostsThisYear'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		register_rest_route($this->namespace, '/' . $this->resource_name . '/pageviewsthisyear', array(
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getTopPageviewsPostsThisYear'),
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));
	}

	function getTopPageviewsPostsThisYear($request) {
		$cachedata = '';
		if (function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->get_cache();
			if (!empty($cachedata)) {

				$response = rest_ensure_response($cachedata);
				return $response;
			}
		}
		$limit = 10;
		global $wpdb, $post, $tableposts, $tablecomments, $time_difference, $post;
		date_default_timezone_set('Asia/Shanghai');
		$today = date("Y-m-d H:i:s"); //获取今天日期时间
		$fristday = date("Y-m-d H:i:s", strtotime("-1 year"));
		$sql = "SELECT  " . $wpdb->posts . ".ID as ID, post_title, post_name,post_content,post_date, CONVERT(" . $wpdb->postmeta . ".meta_value,SIGNED) AS 'pageviews_total' FROM " . $wpdb->posts . " LEFT JOIN " . $wpdb->postmeta . " ON " . $wpdb->posts . ".ID = " . $wpdb->postmeta . ".post_id WHERE " . $wpdb->postmeta . ".meta_key ='views' AND post_date BETWEEN '" . $fristday . "' AND '" . $today . "' AND post_status = 'publish' AND post_type='post'  AND post_password = '' ORDER  BY pageviews_total DESC LIMIT " . $limit;
		$mostlikes = $wpdb->get_results($sql);
		$posts = array();
		foreach ($mostlikes as $post) {

			$post_id = (int)$post->ID;
			$post_title = stripslashes($post->post_title);
			$pageviews = (int)$post->pageviews_total;
			$post_date = $post->post_date;
			$post_permalink = get_permalink($post->ID);
			$_data["post_id"] = $post_id;
			$_data["post_title"] = $post_title;
			$_data["pageviews"] = $pageviews;
			$_data["post_date"] = $post_date;
			$_data["post_permalink"] = $post_permalink;

			$like_count = get_post_meta($post_id, 'postApprovalCount', true);
			if (empty($like_count)) {
				$_data['like_count'] = 0;
			} else {
				$_data['like_count'] = $like_count;
			}

			$comment_total = $wpdb->get_var("SELECT COUNT(1) FROM " . $wpdb->comments . " where  comment_approved = '1' and comment_post_ID=" . $post_id);
			$_data['comment_total'] = $comment_total;

			$images = getPostImages($post->post_content, $post_id);

			$_data['post_thumbnail_image'] = $images['post_thumbnail_image'];
			$_data['content_first_image'] = $images['content_first_image'];
			$_data['post_medium_image_300'] = $images['post_medium_image_300'];
			$_data['post_thumbnail_image_624'] = $images['post_thumbnail_image_624'];
			$_data['post_frist_image'] = $images['post_frist_image'];
			$_data['post_medium_image'] = $images['post_medium_image'];
			$_data['post_large_image'] = $images['post_large_image'];
			$_data['post_full_image'] = $images['post_full_image'];
			$_data['post_all_images'] = $images['post_all_images'];
			$posts[] = $_data;
		}
		if ($cachedata == '' && function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->set_cache($posts, 'pageviewsthisyear', 0);
		}
		$response = rest_ensure_response($posts);
		return $response;
	}

	function getTopLikePostsThisYear($request) {
		$cachedata = '';
		if (function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->get_cache();
			if (!empty($cachedata)) {

				$response = rest_ensure_response($cachedata);
				return $response;
			}
		}
		global $wpdb, $post, $tableposts, $tablecomments, $time_difference, $post;
		$limit = 10;
		date_default_timezone_set('Asia/Shanghai');
		$today = date("Y-m-d H:i:s"); //获取今天日期时间
		$fristday = date("Y-m-d H:i:s", strtotime("-1 year"));
		$sql = "SELECT  " . $wpdb->posts . ".ID as ID, post_title, post_name, post_content, post_date FROM " . $wpdb->posts . ", " . $wpdb->postmeta . " WHERE ID = post_id AND meta_key ='postApprovalCount' AND post_date BETWEEN '" . $fristday . "' AND '" . $today . "' AND post_status = 'publish' AND post_type='post'  AND post_password = '' ORDER BY meta_value DESC LIMIT " . $limit;
		$mostlikes = $wpdb->get_results($sql);
		$posts = array();
		foreach ($mostlikes as $post) {

			$post_id = (int)$post->ID;
			$post_title = stripslashes($post->post_title);
			$post_date = $post->post_date;
			$post_permalink = get_permalink($post->ID);
			$_data["post_id"] = $post_id;
			$_data["post_title"] = $post_title;
			$_data["post_date"] = $post_date;
			$_data["post_permalink"] = $post_permalink;

			$pageviews = (int)get_post_meta($post_id, 'views', true);
			$_data['pageviews'] = $pageviews;

			$like_count = get_post_meta($post_id, 'postApprovalCount', true);
			if (empty($like_count)) {
				$_data['like_count'] = 0;
			} else {
				$_data['like_count'] = $like_count;
			}

			$images = getPostImages($post->post_content, $post_id);

			$_data['post_thumbnail_image'] = $images['post_thumbnail_image'];
			$_data['content_first_image'] = $images['content_first_image'];
			$_data['post_medium_image_300'] = $images['post_medium_image_300'];
			$_data['post_thumbnail_image_624'] = $images['post_thumbnail_image_624'];
			$_data['post_frist_image'] = $images['post_frist_image'];
			$_data['post_medium_image'] = $images['post_medium_image'];
			$_data['post_large_image'] = $images['post_large_image'];
			$_data['post_full_image'] = $images['post_full_image'];
			$_data['post_all_images'] = $images['post_all_images'];
			$posts[] = $_data;
		}
		if ($cachedata == '' && function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->set_cache($posts, 'likethisyear', 0);
		}

		$response = rest_ensure_response($posts);
		return $response;
	}

	function getTopHotPosts($request) {
		$limit = 10;
		global $wpdb, $post, $tableposts, $tablecomments, $time_difference, $post;
		date_default_timezone_set('Asia/Shanghai');
		$sql = "SELECT  " . $wpdb->posts . ".ID as ID, post_title, post_name, post_content,post_date, COUNT(" . $wpdb->comments . ".comment_post_ID) AS 'comment_total' FROM " . $wpdb->posts . " LEFT JOIN " . $wpdb->comments . " ON " . $wpdb->posts . ".ID = " . $wpdb->comments . ".comment_post_ID WHERE comment_approved = '1' AND post_date < '" . date("Y-m-d H:i:s", (time() + ($time_difference * 3600))) . "' AND post_status = 'publish' AND post_type='post'  AND post_password = '' GROUP BY " . $wpdb->comments . ".comment_post_ID ORDER  BY comment_total DESC LIMIT " . $limit;
		$mostcommenteds = $wpdb->get_results($sql);
		$posts = array();
		foreach ($mostcommenteds as $post) {
			$post_id = (int)$post->ID;
			$post_title = stripslashes($post->post_title);
			$comment_total = (int)$post->comment_total;
			$post_date = $post->post_date;
			$post_permalink = get_permalink($post->ID);
			$_data["post_id"] = $post_id;
			$_data["post_title"] = $post_title;
			$_data["comment_total"] = $comment_total;
			$_data["post_date"] = $post_date;
			$_data["post_permalink"] = $post_permalink;
			$pageviews = (int)get_post_meta($post_id, 'views', true);
			$_data['pageviews'] = $pageviews;

			$like_count = get_post_meta($post_id, 'postApprovalCount', true);
			if (empty($like_count)) {
				$_data['like_count'] = 0;
			} else {
				$_data['like_count'] = $like_count;
			}

			$images = getPostImages($post->post_content, $post_id);

			$_data['post_thumbnail_image'] = $images['post_thumbnail_image'];
			$_data['content_first_image'] = $images['content_first_image'];
			$_data['post_medium_image_300'] = $images['post_medium_image_300'];
			$_data['post_thumbnail_image_624'] = $images['post_thumbnail_image_624'];
			$_data['post_frist_image'] = $images['post_frist_image'];
			$_data['post_medium_image'] = $images['post_medium_image'];
			$_data['post_large_image'] = $images['post_large_image'];
			$_data['post_full_image'] = $images['post_full_image'];
			$_data['post_all_images'] = $images['post_all_images'];

			$posts[] = $_data;
		}
		$response = rest_ensure_response($posts);
		return $response;
	}

	function getTopHotPostsThisYear($request) {
		$cachedata = '';
		if (function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->get_cache();
			if (!empty($cachedata)) {

				$response = rest_ensure_response($cachedata);
				return $response;
			}
		}

		global $wpdb, $post, $tableposts, $tablecomments, $time_difference, $post;
		date_default_timezone_set('Asia/Shanghai');
		$limit = 10;
		$today = date("Y-m-d H:i:s"); //获取今天日期时间
		// $fristday = date( "Y-m-d H:i:s",  strtotime(date("Y",time())."-1"."-1"));  //本年第一天;
		$fristday = date("Y-m-d H:i:s", strtotime("-1 year"));
		$sql = "SELECT  " . $wpdb->posts . ".ID as ID, post_title, post_name,post_content,post_date, COUNT(" . $wpdb->comments . ".comment_post_ID) AS 'comment_total' FROM " . $wpdb->posts . " LEFT JOIN " . $wpdb->comments . " ON " . $wpdb->posts . ".ID = " . $wpdb->comments . ".comment_post_ID WHERE comment_approved = '1' AND post_date BETWEEN '" . $fristday . "' AND '" . $today . "' AND post_status = 'publish' AND post_type='post' AND post_password = '' GROUP BY " . $wpdb->comments . ".comment_post_ID ORDER  BY comment_total DESC LIMIT " . $limit;
		$mostcommenteds = $wpdb->get_results($sql);
		$posts = array();
		foreach ($mostcommenteds as $post) {

			$post_id = (int)$post->ID;
			$post_title = stripslashes($post->post_title);
			$comment_total = (int)$post->comment_total;
			$post_date = $post->post_date;
			$post_permalink = get_permalink($post->ID);
			$_data["post_id"] = $post_id;
			$_data["post_title"] = $post_title;
			$_data["comment_total"] = $comment_total;
			$_data["post_date"] = $post_date;
			$_data["post_permalink"] = $post_permalink;

			$pageviews = (int)get_post_meta($post_id, 'views', true);
			$_data['pageviews'] = $pageviews;

			$like_count = get_post_meta($post_id, 'postApprovalCount', true);
			if (empty($like_count)) {
				$_data['like_count'] = 0;
			} else {
				$_data['like_count'] = $like_count;
			}

			$images = getPostImages($post->post_content, $post_id);

			$_data['post_thumbnail_image'] = $images['post_thumbnail_image'];
			$_data['content_first_image'] = $images['content_first_image'];
			$_data['post_medium_image_300'] = $images['post_medium_image_300'];
			$_data['post_thumbnail_image_624'] = $images['post_thumbnail_image_624'];

			$_data['post_frist_image'] = $images['post_frist_image'];
			$_data['post_medium_image'] = $images['post_medium_image'];
			$_data['post_large_image'] = $images['post_large_image'];
			$_data['post_full_image'] = $images['post_full_image'];
			$_data['post_all_images'] = $images['post_all_images'];
			$posts[] = $_data;
		}
		if ($cachedata == '' && function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->set_cache($posts, 'hotpostthisyear', 0);
		}

		$response = rest_ensure_response($posts);
		return $response;
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
