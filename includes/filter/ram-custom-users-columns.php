<?php
// 禁止直接访问
if (!defined('ABSPATH')) exit;

// 在用户列表添加自定义列
function users_columns($columns) {
	$columns['platform'] = __('绑定平台');
	return $columns;
}

function output_users_columns($var, $columnName, $userId) {
	switch ($columnName) {
		case "platform":
			$platform = unserialize(get_user_meta($userId, 'social_connect', true));
			$output = "";
			if (!empty($platform)) {
				foreach ($platform as $key => $value) {
					$output .= $key . "：" . $value . "<br/>";
				}
			}
			return $output;
			break;
		default:
			return $columnName;
	}
}

function addCustomUserField(WP_REST_Response $response, WP_User $user, WP_REST_Request $request) {
	$response->data["avatar"] = get_avatar_url_2($user->ID);
	$social_connect = get_user_meta($user->ID, "social_connect", true);
	$response->data["social_connect"] = unserialize(empty($social_connect) ? "" : $social_connect);
	// 移除不用的属性
	unset($response->data["capabilities"]);
	return $response;
}
