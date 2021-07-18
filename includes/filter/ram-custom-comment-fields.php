<?php

function custom_comment_fields($data, $comment, $request) {
	global $wpdb;
	$_data = $data->data;
	$comment_id = $comment->comment_ID;

	// 添加该评论的评论文章标题
	$sql = $wpdb->prepare("SELECT post_title FROM " . $wpdb->comments . ", " . $wpdb->posts . " WHERE comment_post_ID = ID AND comment_ID = " . $comment_id . " LIMIT 1");
	$comment = $wpdb->get_row($sql);
	$_data['post_title'] = $comment->post_title;

	$data->data = $_data;
	return $data;
}
