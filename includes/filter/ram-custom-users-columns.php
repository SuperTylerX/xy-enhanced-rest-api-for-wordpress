<?php
// 禁止直接访问
if (!defined('ABSPATH')) exit;

// 在用户列表添加自定义列
function users_columns($columns) {
	$columns['platform'] = __('绑定平台');
	// 在columns数组头部插入一个新的元素
	$columns = array_slice($columns, 0, 1, true) +
		array('avatar' => __('头像')) +
		array_slice($columns, 1, count($columns) - 1, true);

	return $columns;
}

function custom_user_avatar_text_custom_css() {
	// Adjust the width of custom avatar text column
	echo '<style>
        .column-avatar {
            width: 32px; /* Adjust the width as needed */
        }
    </style>';
}

function output_users_columns($var, $columnName, $userId) {
	switch ($columnName) {
		// 平台绑定列
		case "platform":
			$platform = unserialize(get_user_meta($userId, 'social_connect', true));
			$output = "";
			if (!empty($platform)) {
				foreach ($platform as $key => $value) {
					$output .= $key . "：" . $value . "<br/>";
				}
			}
			return $output;
		// 头像列
		case "avatar":
			$avatar = get_avatar_url_2($userId);
			return "<img src='$avatar' style='width: 32px; height: 32px; border-radius: 50%;'>";
		default:
			return $columnName;
	}
}

function addCustomUserField(WP_REST_Response $response, WP_User $user, WP_REST_Request $request) {
	// 增加自定义头像返回
	$response->data["avatar"] = get_avatar_url_2($user->ID);

	// 增加社交绑定信息返回
	$social_connect = get_user_meta($user->ID, "social_connect", true);
	$response->data["social_connect"] = unserialize(empty($social_connect) ? "" : $social_connect);

	// 增加用户角色名称
	global $wp_roles;
	$roles = $wp_roles->get_names();    //得到一个值列表包括 $role_name => $display_name
	$roleStr = $user->roles[0]; // 这里只取第一个角色
	if (empty($roleStr)) {
		$response->data["role"] = '无角色';
	} else {
		$response->data["role"] = translate_user_role($roles[$roleStr]);
	}

	return $response;
}