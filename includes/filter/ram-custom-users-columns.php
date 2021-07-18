<?php
//禁止直接访问
if (!defined('ABSPATH')) exit;

// 在用户列表添加头像列
function users_columns($columns) {
	$columns['avatar'] = __('头像');
	return $columns;
}

function output_users_columns($var, $columnName, $userId) {
	switch ($columnName) {
		case "avatar":
			return getAvatar($userId);
			break;
	}
}

function getAvatar($userId) {
	$avatar = get_user_meta($userId, 'avatar', true);
	if (empty($avatar)) {
		$avatarImg = '<img  src="' . plugins_url() . '/' . REST_API_TO_MINIPROGRAM_PLUGIN_NAME . '/includes/images/gravatar.png"  width="20px" heigth="20px"/>';
	} else {
		$avatarImg = '<img  src="' . $avatar . '"  width="20px" heigth="20px"/>';
	}

	return $avatarImg;
}

function addCustomUserField(WP_REST_Response $response, WP_User $user, WP_REST_Request $request) {

	$response->data["avatar"] = $user->$ID["avatar"][0];
	// 移除不用的属性
	unset($response->data["capabilities"]);
	return $response;
}
