<?php

if (!defined('ABSPATH')) {
	exit;
}

class RAM_REST_Comments_Controller extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'watch-life-net/v1';
		$this->resource_name = 'comment';
	}

	public function register_routes() {
		// 注册获取某篇文章的评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/getcomments', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_comments'),
				'permission_callback' => array($this, 'get_item_permissions_check'),
				'args' => array(
					'postid' => array(
						'required' => true
					)
				)

			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册获取某个用户的评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/get', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getcomment'),
				'permission_callback' => array($this, 'comment_permissions_check')
			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册删除某个用户的评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/delete', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'DELETE',
				'callback' => array($this, 'delete_comment'),
				'permission_callback' => array($this, 'comment_permissions_check'),
				'args' => array(
					'commentId' => array(
						'required' => true
					)
				)

			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));

		// 注册添加评论的路由
		register_rest_route($this->namespace, '/' . $this->resource_name . '/add', array(
			// Here we register the readable endpoint for collections.
			array(
				'methods' => 'POST',
				'callback' => array($this, 'add_comment'),
				'permission_callback' => array($this, 'comment_permissions_check'),
				'args' => array(
					'post' => array(
						'required' => true
					),
					'parent' => array(
						'required' => true
					),
					'content' => array(
						'required' => true
					)
				)

			),
			// Register our schema callback.
			'schema' => array($this, 'get_public_item_schema'),
		));
	}


	//获取某个用户的评论
	function getcomment($request) {
		global $wpdb;

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;

		$sql = "SELECT * from " . $wpdb->posts . ", " . $wpdb->comments . " WHERE comment_approved = 1 AND comment_post_ID = ID AND user_id= " . $user_id . " order by comment_date DESC LIMIT 20";
		$_posts = $wpdb->get_results($sql);
		$posts = array();
		foreach ($_posts as $post) {
			$_data["post"] = $post->ID;
			$_data["id"] = $post->comment_ID;
			$_data["post_title"] = $post->post_title;
			$_data["comment_content"] = $post->comment_content;
			$posts[] = $_data;
		}
		$result["code"] = "success";
		$result["message"] = "获取评论成功！";
		$result["status"] = "200";
		$result["data"] = $posts;


		$response = rest_ensure_response($result);
		return $response;
	}

	// 获取某篇文章的评论
	function get_comments($request) {
		$cachedata = '';
		if (function_exists('MRAC')) {
			$cachedata = MRAC()->cacheManager->get_cache();
			if (!empty($cachedata)) {

				$response = rest_ensure_response($cachedata);
				return $response;
			}
		}
		global $wpdb;
		$postid = isset($request['postid']) ? (int)$request['postid'] : 0;
		$limit = isset($request['limit']) ? (int)$request['limit'] : 0;
		$page = isset($request['page']) ? (int)$request['page'] : 0;
		$order = isset($request['order']) ? $request['order'] : '';
		if (empty($order)) {
			$order = "asc";
		}
		$page = ($page - 1) * $limit;
		$sql = $wpdb->prepare("SELECT t.* FROM " . $wpdb->comments . " t WHERE t.comment_post_ID =%d and t.comment_parent=0 and t.comment_approved='1' order by t.comment_date " . $order . " limit %d,%d", $postid, $page, $limit);

		$comments = $wpdb->get_results($sql);
		$commentslist = array();
		foreach ($comments as $comment) {
			if ($comment->comment_parent == 0) {
				$data["id"] = $comment->comment_ID;
				$data["author_name"] = $comment->comment_author;

				// 兼容以前的头像写法，读取用户url内是否为头像
				$author_url = $comment->comment_author_url;
				$author_url = strpos($author_url, "wx.qlogo.cn") ? $author_url : "";


				if (isset($comment->user_id) && $author_url == "") {
					// 存在该用户且头像为空
					$_avatar = get_user_meta($comment->user_id, "avatar", true);
					// 读取 avatar字段的user meta 作为头像
					if (!empty($_avatar)) {
						$author_url = $_avatar;
					} else {
						// 依然不存在，那么就使用默认头像
						$author_url = "../../static/gravatar.png";
					}
				}
				$data["author_url"] = $author_url;
				$data["date"] = time_tran($comment->comment_date);
				$data["content"] = $comment->comment_content;
				$data["userid"] = $comment->user_id;
				$order = "asc";
				$data["child"] = $this->getchildcomment($postid, $comment->comment_ID, 5, $order);
				$commentslist[] = $data;
			}
		}
		$result["code"] = "success";
		$result["message"] = "获取评论成功";
		$result["status"] = "200";
		$result["data"] = $commentslist;

		if ($cachedata == '' && function_exists('MRAC')) {

			$cachedata = MRAC()->cacheManager->set_cache($result, 'postcomments', $postid);
		}
		$response = rest_ensure_response($result);
		return $response;
	}

	function getchildcomment($postid, $comment_id, $limit, $order) {
		global $wpdb;
		if ($limit > 0) {
			$commentslist = array();
			$sql = $wpdb->prepare("SELECT t.* FROM " . $wpdb->comments . " t WHERE t.comment_post_ID =%d and t.comment_parent=%d and t.comment_approved='1' order by comment_date " . $order, $postid, $comment_id);

			$comments = $wpdb->get_results($sql);
			foreach ($comments as $comment) {
				$data["id"] = $comment->comment_ID;
				$data["author_name"] = $comment->comment_author;
				$author_url = $comment->comment_author_url;
				$data["date"] = time_tran($comment->comment_date);
				$data["content"] = $comment->comment_content;
				$data["userid"] = $comment->user_id;
				$data["child"] = $this->getchildcomment($postid, $comment->comment_ID, $limit - 1, $order);
				$commentslist[] = $data;
			}
		}
		return $commentslist;
	}

	// 删除用户的评论
	function delete_comment($request) {
		$commentId = $request["commentId"];
		$comment = get_comment($commentId);
		$current_user = wp_get_current_user();
		$ID = $current_user->ID;

		$result = [];
		if ($comment->user_id == $ID) {
			if (wp_delete_comment($commentId)) {
				$result["code"] = "success";
				$result["message"] = "删除评论成功";
				$result["status"] = "200";
			} else {
				$result["code"] = "failed";
				$result["message"] = "删除评论失败";
				$result["status"] = "500";
			}
		} else {
			$result["code"] = "failed";
			$result["message"] = "删除评论失败，权限拒绝";
			$result["status"] = "401";
		}
		return rest_ensure_response($result);
	}

	function add_comment($request) {

		$post = $request['post'];
		$parent = $request['parent'];
		$content = $request['content'];

		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$author_name = $current_user->display_name;
		$author_email = $current_user->user_email;
		$author_url = $current_user->user_url;

		$authorIp = ram_get_client_ip();
		$authorIp = empty($authorIp) ? '' : $authorIp;
		$wf_enable_comment_check = get_option('wf_enable_comment_check');


		$data = array(
			'content' => $content
		);

		$msgSecCheckResult = RAM()->wxapi->msgSecCheck($data);
		$errcode = $msgSecCheckResult['errcode'];
		$errmsg = $msgSecCheckResult['errmsg'];
		if ($errcode == 87014) {
			return new WP_Error($errcode, $errmsg, array('status' => 403));
		}

		$comment_approved = "1";
		$userLevel = getUserLevel($user_id);

		if (!empty($wf_enable_comment_check) && $userLevel["level"] == '0') {
			$comment_approved = "0";
		}

		$commentdata = array(
			'comment_post_ID' => $post, // to which post the comment will show up
			'comment_author' => $author_name, //fixed value - can be dynamic
			'comment_author_email' => $author_email, //fixed value - can be dynamic
			'comment_author_url' => $author_url, //fixed value - can be dynamic
			'comment_content' => $content, //fixed value - can be dynamic
			'comment_type' => '', //empty for regular comments, 'pingback' for pingbacks, 'trackback' for trackbacks
			'comment_parent' => $parent, //0 if it's not a reply to another comment; if it's a reply, mention the parent comment ID here
			'user_id' => $user_id, //passing current user ID or any predefined as per the demand
			'comment_author_IP' => $authorIp,
			'comment_approved' => $comment_approved
			// 'comment_agent' => $_SERVER['HTTP_USER_AGENT'] // 加入UA后小程序端无法正常留言
		);

		$comment_id = wp_insert_comment(wp_filter_comment($commentdata));

		if (empty($comment_id)) {
			return new WP_Error('error', '添加评论失败', array('status' => 500));
		} else {

			$result["code"] = "success";
			$message = '留言成功';

			if (!empty($wf_enable_comment_check) && $userLevel["level"] == '0') {
				$message = '留言已提交,需管理员审核方可显示。';
			}

			if (function_exists('MRAC')) {

				$cachedata = MRAC()->cacheManager->delete_cache('postcomments', $post);
			}


			$result["status"] = "200";
			$result["level"] = $userLevel;
			$result['comment_approved'] = $comment_approved;
			$result["message"] = $message;
			$response = rest_ensure_response($result);
			return $response;
		}
	}

	public function get_item_permissions_check($request) {
		$postid = isset($request['postid']) ? (int)$request['postid'] : 0;
		$limit = isset($request['limit']) ? (int)$request['limit'] : 0;
		$page = isset($request['page']) ? (int)$request['page'] : 0;
		$order = isset($request['order']) ? $request['order'] : '';
		if (empty($order)) {
			$order = "asc";
		}

		if (empty($postid) || empty($limit) || empty($page) || get_post($postid) == null) {
			return new WP_Error('error', ' 参数不能为空：postid,limit,page', array('status' => 500));
		} elseif (!is_numeric($limit) || !is_numeric($page) || !is_numeric($postid)) {
			return new WP_Error('error', ' 参数错误', array('status' => 500));
		}
		return true;
	}

	function comment_permissions_check($request) {

		$current_user = wp_get_current_user();
		$ID = $current_user->ID;
		if ($ID == 0) {
			return new WP_Error('error', '尚未登录或Token无效', array('status' => 400));
		}

		return true;
	}
}
