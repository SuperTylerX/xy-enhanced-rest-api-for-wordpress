<?php
//获取文章的第一张图片
function get_post_content_first_image($post_content) {
	if (!$post_content) {
		$the_post = get_post();
		$post_content = $the_post->post_content;
	}

	preg_match_all('/class=[\'"].*?wp-image-([\d]*)[\'"]/i', $post_content, $matches);
	if ($matches && isset($matches[1]) && isset($matches[1][0])) {
		$image_id = $matches[1][0];
		if ($image_url = get_post_image_url($image_id)) {
			return $image_url;
		}
	}

	preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', do_shortcode($post_content), $matches);
	if ($matches && isset($matches[1]) && isset($matches[1][0])) {
		return $matches[1][0];
	}

	// no image
	return false;
}

//获取文章图片的地址
function get_post_image_url($image_id, $size = 'full') {
	if ($thumb = wp_get_attachment_image_src($image_id, $size)) {
		return $thumb[0];
	}
	return false;
}

function getPostImages($content, $postId) {
	$post_first_image = get_post_content_first_image($content);

	if (empty($post_first_image)) {
		$post_first_image = '';
	}

	$post_thumbnail_image = "";
	$post_medium_image = "";
	$post_large_image = "";
	$post_full_image = "";

	$_data = array();

	if (has_post_thumbnail($postId)) {
		//获取缩略的ID
		$thumbnailId = get_post_thumbnail_id($postId);
		if ($thumbnailId !== 0) {
			//特色图缩略图
			$image = wp_get_attachment_image_src($thumbnailId, 'thumbnail');
			$post_thumbnail_image = $image[0];
			//特色中等图
			$image = wp_get_attachment_image_src($thumbnailId, 'medium');
			$post_medium_image = $image[0];
			//特色大图
			$image = wp_get_attachment_image_src($thumbnailId, 'large');
			$post_large_image = $image[0];
			//特色原图
			$image = wp_get_attachment_image_src($thumbnailId, 'full');
			$post_full_image = $image[0];
		}
	}

	if (!empty($post_first_image) && empty($post_thumbnail_image)) {
		$post_thumbnail_image = $post_first_image;
	}

	if (!empty($post_first_image) && empty($post_medium_image)) {
		$post_medium_image = $post_first_image;
	}

	if (!empty($post_first_image) && empty($post_large_image)) {
		$post_large_image = $post_first_image;
	}

	if (!empty($post_first_image) && empty($post_full_image)) {
		$post_full_image = $post_first_image;
	}

	//$post_all_images = get_attached_media( 'image', $postId);
	$post_all_images = get_post_content_images($content);

	$_data['post_first_image'] = $post_first_image;
	$_data['post_thumbnail_image'] = $post_thumbnail_image;
	$_data['post_medium_image'] = $post_medium_image;
	$_data['post_large_image'] = $post_large_image;
	$_data['post_full_image'] = $post_full_image;
	$_data['post_all_images'] = $post_all_images;

	return $_data;
}

function get_post_content_images($post_content) {
	if (!$post_content) {
		$the_post = get_post();
		$post_content = $the_post->post_content;
	}


	preg_match_all('|<img.*?src=[\'"](.*?)[\'"].*?>|i', do_shortcode($post_content), $matches);
	$images = array();
	if ($matches && isset($matches[1])) {

		for ($i = 0; $i < count($matches[1]); $i++) {
			$images[] = $matches[1][$i];
		}

		return $images;

	}

	return null;

}


// get_avatar_url函数无法被重写，所以改用这个函数
function get_avatar_url_2($id_or_email) {

	if (is_numeric($id_or_email)) {
		// isUserID
		// 若存在本地头像则使用本地头像
		$avatar = get_user_meta($id_or_email, "avatar", true);
		if (empty($avatar)) {
			$avatar = get_avatar_url($id_or_email);
		}
		return $avatar;
	} elseif (is_email($id_or_email)) {
		// isEmail
		$user = get_user_by_email($id_or_email);
		if (!empty($user)) {
			$avatar = get_user_meta($user->ID, "avatar", true);
			if (empty($avatar)) {
				$avatar = get_avatar_url($id_or_email);
			}
			return $avatar;
		} else {
			return get_avatar_url($id_or_email);
		}
	} else {
		// gravatar md5 hash
		return esc_url(home_url('/wp-content/plugins/rest-api-to-miniprogram-enhanced/includes/images/gravatar.png'));
	}
}

function get_content_post($url, $post_data = array(), $header = array()) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	$content = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($code == "200") {
		return $content;
	} else {
		return "error";
	}
}

//发起https请求
function https_request($url) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 500);
	$data = curl_exec($curl);
	if (curl_errno($curl)) {
		return 'ERROR';
	}
	curl_close($curl);
	return $data;
}

function time_tran($the_time) {
	date_default_timezone_set('Asia/Shanghai');
	$now_time = date("Y-m-d H:i:s", time());
	$now_time = strtotime($now_time);
	$show_time = strtotime($the_time);
	$dur = $now_time - $show_time;
	if ($dur < 0) {
		return $the_time;
	} else {
		if ($dur < 60) {
			return $dur . '秒前';
		} else {
			if ($dur < 3600) {
				return floor($dur / 60) . '分钟前';
			} else {
				if ($dur < 86400) {
					return floor($dur / 3600) . '小时前';
				} else {
					if ($dur < 259200) {//3天内
						return floor($dur / 86400) . '天前';
					} else {
						return date("Y-m-d", $show_time);
					}
				}
			}
		}
	}
}

function get_client_ip() {
	foreach (array(
		         'HTTP_CLIENT_IP',
		         'HTTP_X_FORWARDED_FOR',
		         'HTTP_X_FORWARDED',
		         'HTTP_X_CLUSTER_CLIENT_IP',
		         'HTTP_FORWARDED_FOR',
		         'HTTP_FORWARDED',
		         'REMOTE_ADDR') as $key) {
		if (array_key_exists($key, $_SERVER)) {
			foreach (explode(',', $_SERVER[$key]) as $ip) {
				$ip = trim($ip);
				//会过滤掉保留地址和私有地址段的IP，例如 127.0.0.1会被过滤
				//也可以修改成正则验证IP
				if ((bool)filter_var($ip, FILTER_VALIDATE_IP,
					FILTER_FLAG_IPV4 |
					FILTER_FLAG_NO_PRIV_RANGE |
					FILTER_FLAG_NO_RES_RANGE)) {
					return $ip;
				}
			}
		}
	}
	return null;
}

function filterEmoji($str) {
	$str = preg_replace_callback(
		'/./u',
		function (array $match) {
			return strlen($match[0]) >= 4 ? '' : $match[0];
		},
		$str);

	return $str;
}

function getUserLevel($userId) {
	global $wpdb;
	$sql = $wpdb->prepare("SELECT  t.meta_value
            FROM
                " . $wpdb->usermeta . " t
            WHERE
                t.meta_key = '" . $wpdb->prefix . "user_level' 
            AND t.user_id =%d", $userId);

	$level = $wpdb->get_var($sql);
	$levelName = "订阅者";
	switch ($level) {
		case "10":
			$levelName = "管理者";
			break;

		case "7":
			$levelName = "编辑";
			break;

		case "2":
			$levelName = "作者";
			break;

		case "1":
			$levelName = "投稿者";
			break;

		case "0":
			$levelName = "订阅者";
			break;

	}
	$userLevel["level"] = $level;
	$userLevel["levelName"] = $levelName;
	return $userLevel;

}

function get_post_qq_video($content) {
	$vcontent = '';
	preg_match('/https\:\/\/v.qq.com\/x\/(\S*)\/(\S*)\.html/', $content, $matches);
	if ($matches) {
		$vids = $matches[2];
		$videoUrl = get_qq_video_url($vids);
		$vcontent = preg_replace('~<video (.*?)></video>~s', '<video src="' . $videoUrl . '" poster="https://puui.qpic.cn/qqvideo_ori/0/' . $vids . '_496_280/0" controls="controls" width="100%"></video>', $content);

	}

	return $vcontent;
}

function get_qq_video_url($vid) {
	$url = 'https://vv.video.qq.com/getinfo?vids=' . $vid . '&platform=101001&charge=0&otype=json';
	$json = file_get_contents($url);
	preg_match('/^QZOutputJson=(.*?);$/', $json, $json2);
	$tempStr = json_decode($json2[1], true);
	$vurl = 'https://ugcws.video.gtimg.com/' . $tempStr['vl']['vi'][0]['fn'] . "?vkey=" . $tempStr['vl']['vi'][0]['fvkey'];
	return $vurl;
}


function get_content_gallery($content, $flag) {
	$list = array();
	//$content=self::nl2p($content,true,false);//把换行转换成p标签
	if ($flag) {
		$content = nl2br($content);
	}
	$vcontent = $content;

	$c1 = preg_match_all('|\[gallery.*?ids=[\'"](.*?)[\'"].*?\]|i', $content, $m1);  //先取出所有gallery短代码
	for ($i = 0; $i < $c1; $i++) {    //对所有的img标签进行取属性
		$c2 = preg_match_all('/(\w+)\s*=\s*(?:(?:(["\'])(.*?)(?=\2))|([^\/\s]*))/', $m1[0][$i], $m2);   //匹配出所有的属性
		for ($j = 0; $j < $c2; $j++) {    //将匹配完的结果进行结构重组
			$list[$i][$m2[1][$j]] = !empty($m2[4][$j]) ? $m2[4][$j] : $m2[3][$j];
		}
	}

	$ids = $list[0]['ids'];
	if (!empty($ids)) {
		$ids = explode(',', $ids);
		$img = '';
		foreach ($ids as $id) {
			$image = wp_get_attachment_image_src((int)$id, 'full');

			$img .= '<img width="' . $image[1] . '" height="' . $image[2] . '" src="' . $image[0] . '" />';


		}
		$vcontent = preg_replace('~\[gallery (.*?)\]~s', $img, $content);


	}

	return $vcontent;

}

function custom_minapper_post_fields($_data, $post, $request) {
	global $wpdb;
	$post_id = $post->ID;

	$content = $_data['content']['rendered'];
	$content_protected = $_data['content']['protected'];
	$raw = empty($_data['content']['raw']) ? '' : $_data['content']['raw'];

	// 补全Url
	$siteurl = get_option('siteurl');
	$upload_dir = wp_upload_dir();
	$content = str_replace('http:' . strstr($siteurl, '//'), 'https:' . strstr($siteurl, '//'), $content);
	$content = str_replace('http:' . strstr($upload_dir['baseurl'], '//'), 'https:' . strstr($upload_dir['baseurl'], '//'), $content);

	$images = getPostImages($content, $post_id);
	$_data['post_first_image'] = $images['post_first_image'];
	$_data['post_thumbnail_image'] = $images['post_thumbnail_image'];
	$_data['post_medium_image'] = $images['post_medium_image'];
	$_data['post_large_image'] = $images['post_large_image'];
	$_data['post_full_image'] = $images['post_full_image'];
	$_data['post_all_images'] = $images['post_all_images'];

	//	//获取广告参数
	//
	//	$videoAdId = empty(get_option('wf_video_ad_id')) ? '' : get_option('wf_video_ad_id');
	//	$_data['videoAdId'] = $videoAdId;
	//
	//	$listAdId = empty(get_option('wf_list_ad_id')) ? '' : get_option('wf_list_ad_id');
	//	$listAd = empty(get_option('wf_list_ad')) ? '0' : "1";
	//	$listAdEvery = empty(get_option('wf_list_ad_every')) ? 5 : (int)get_option('wf_list_ad_every');
	//
	//	$_data['listAd'] = $listAd;
	//	$_data['listAdId'] = $listAdId;
	//	$_data['listAdEvery'] = $listAdEvery;

	// 分类名称
	$category = get_the_category($post_id);
	if (!empty($category)) {
		$_data['category_name'] = $category[0]->cat_name;
	}
	// 发布日期
	$post_date = $post->post_date;
	$_data['post_date'] = time_tran($post_date);
	// 点赞数
	$like_count = (int)get_post_meta($post_id, 'postApprovalCount', true);
	$_data['like_count'] = $like_count;
	// 阅读数
	$post_views = (int)get_post_meta($post_id, 'views', true);
	$params = $request->get_params();
	// 评论数
	$comments_count = wp_count_comments($post_id);
	$_data['total_comments'] = $comments_count->approved;

	// 是读取单篇文章
	if (isset($params['id'])) {
		//		//获取广告参数
		//		$detailAdId = empty(get_option('wf_detail_ad_id')) ? '' : get_option('wf_detail_ad_id');
		//		$detailAd = empty(get_option('wf_detail_ad')) ? '0' : "1";
		//
		//		$rewardedVideoAdId = empty(get_option('wf_excitation_ad_id')) ? '' : get_option('wf_excitation_ad_id');
		//		$excitationAd = empty(get_post_meta($post_id, '_excitation', true)) ? "0" : get_post_meta($post_id, '_excitation', true);
		//
		//		$_data['excitationAd'] = $excitationAd;
		//		$_data['rewardedVideoAdId'] = $rewardedVideoAdId;
		//
		//		$_data['detailAdId'] = $detailAdId;
		//		$_data['detailAd'] = $detailAd;

		// 解析腾讯视频
		$vcontent = get_post_qq_video($content);
		if (!empty($vcontent)) {
			$content = $vcontent;
		}

		// 处理内容里的相册显示
		$sql = "select post_content from " . $wpdb->posts . " where id=" . $post_id;
		$postContent = $wpdb->get_var($sql);
		if (has_shortcode($postContent, 'gallery')) {
			$content = get_content_gallery($postContent, true);
		}
		$_content['rendered'] = $content;
		$_content['raw'] = $raw;//古腾堡编辑器需要该属性，否则报错
		$_content['protected'] = $content_protected;
		$_data['content'] = $_content;

		// 获取所有点赞用户的头像
		$postApprovalUsers = get_post_meta($post_id, 'postApprovalUsers', true);
		if (empty($postApprovalUsers)) {
			$postApprovalUsers = [];
		}
		$avatarurls = array();
		foreach ($postApprovalUsers as $userid) {
			$_avatarurl['avatarurl'] = get_avatar_url_2($userid);
//			$_avatarurl['userId'] = $userid;
			$avatarurls[] = $_avatarurl;
		}
		$_data['avatarurls'] = $avatarurls;

		// 阅读数+1
		$post_views = $post_views + 1;
		if (!update_post_meta($post_id, 'views', $post_views)) {
			add_post_meta($post_id, 'views', 1, true);
		}

		// 获取上一篇下一篇文章
		if (!empty($category)) {
			$category_id = $category[0]->term_id;
			$next_post = get_next_post($category_id, '', 'category');
			$previous_post = get_previous_post($category_id, '', 'category');
			$_data['next_post_id'] = !empty($next_post->ID) ? $next_post->ID : null;
			$_data['next_post_title'] = !empty($next_post->post_title) ? $next_post->post_title : null;
			$_data['previous_post_id'] = !empty($previous_post->ID) ? $previous_post->ID : null;
			$_data['previous_post_title'] = !empty($previous_post->post_title) ? $previous_post->post_title : null;
		}
	} else {
		// 获取的是文章列表，移除文章内容
		unset($_data['content']);
	}
	$_data['pageviews'] = $post_views;

	return $_data;
}

/**
 * 生成一个随机字符串
 * @param $length int 随机字符串的长度
 * @return string 随机字符串
 */
function get_random_string($length) {
	//字符组合
	$str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$len = strlen($str) - 1;
	$randstr = '';
	for ($i = 0; $i < $length; $i++) {
		$num = mt_rand(0, $len);
		$randstr .= $str[$num];
	}
	return $randstr;
}

/**
 * 获取IP对应的地理位置
 * @param $ip string IP地址
 * @return array 地理位置信息
 */
function get_ip_location($ip) {
	$ipip_city = new ipip\db\City(__DIR__ . '/vendor/ipip/db/ipipfree.ipdb');
	return $ipip_city->findMap($ip, 'CN');
}

/**
 * @desc 根据UA判断用户的操作系统
 */
function get_user_os($ua) {
	if (empty($ua)) {
		$ua = $_SERVER['HTTP_USER_AGENT'];
	}
	$os_platform = "未知操作系统";
	$os_array = array(
		'/windows nt 10/i' => 'Windows 10',
		'/windows nt 6.3/i' => 'Windows 8.1',
		'/windows nt 6.2/i' => 'Windows 8',
		'/windows nt 6.1/i' => 'Windows 7',
		'/windows nt 6.0/i' => 'Windows Vista',
		'/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
		'/windows nt 5.1/i' => 'Windows XP',
		'/windows xp/i' => 'Windows XP',
		'/macintosh|mac os x/i' => 'Mac OS X',
		'/mac_powerpc/i' => 'Mac OS 9',
		'/linux/i' => 'Linux',
		'/ubuntu/i' => 'Ubuntu',
		'/iphone/i' => 'iPhone',
		'/ipad/i' => 'iPad',
		'/android/i' => 'Android',
		'/webos/i' => 'Mobile'
	);
	foreach ($os_array as $regex => $value) {
		if (preg_match($regex, $ua)) {
			$os_platform = $value;
		}
	}
	return $os_platform;
}

/**
 * @desc 获取用户是否已经登录
 * @return true|WP_Error
 */
function jwt_permissions_check() {
	$current_user = wp_get_current_user();
	$ID = $current_user->ID;
	if ($ID == 0) {
		return new WP_Error('error', '尚未登录或Token无效', array('status' => 400));
	}
	return true;
}

/**
 * @desc 获取用户的角色
 * @param $userId int 用户ID
 * @return string 用户角色
 */

function uni_get_user_role(int $userId) {
	global $wp_roles;
	$roles = $wp_roles->get_names();    //得到一个值列表包括 $role_name => $display_name
	$user = get_user_by('id', $userId);
	$roleStr = $user->roles[0]; // 这里只取第一个角色
	if (empty($roleStr)) {
		return '无角色';
	} else {
		return translate_user_role($roles[$roleStr]);
	}
}