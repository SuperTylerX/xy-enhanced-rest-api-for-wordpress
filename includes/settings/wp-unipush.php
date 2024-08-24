<?php

$PackageName = 'com.supertyler.xy';

// 收到文章评论，给文章作者推送消息，点击通知后，会跳转到文章详情页
add_action('wp_insert_comment', 'send_push_notification_on_new_comment', 10, 2);

function send_push_notification_on_new_comment($comment_id, $comment_object) {
	global $PackageName;

	$post_id = $comment_object->comment_post_ID;
	// 获取文章作者的ID
	$user_id = get_post_field('post_author', $post_id);

	// 若文章作者是当前评论者，则不发送推送
	if ($user_id == $comment_object->user_id) {
		return;
	}

	if ($user_id) {
		// 获取用户的推送ID
		$cid = get_user_meta($user_id, 'cid', true);

		if ($cid) {
			// 准备推送内容
			$title = '您有新的评论';
			$msg = '有人在您的文章 "' . get_the_title($post_id) . '" 中评论了: "' . $comment_object->comment_content . '"';
			$intent = "intent://" .
				$PackageName .
				"/?#Intent;scheme=unipush;launchFlags=0x4000000;component=" .
				$PackageName .
				"/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title=" .
				urlencode($title) .
				";S.content=" .
				urlencode($msg) .
				";S.payload=" .
				urlencode(json_encode([
					"type" => "redirect",
					"data" => [
						"url" => "/pages/post/post?id=" . $post_id,
					]
				]))
				. ";end";
			// 调用个推API发送推送
			$result = send_push_notification(cid: $cid, title: $title, msg: $msg, clickType: 'intent', intent: $intent);
		}
	}
}


// 收到评论回复，给评论作者推送消息，点击通知后，会跳转到文章详情页
add_action('wp_insert_comment', 'send_push_notification_on_new_reply', 10, 2);

function send_push_notification_on_new_reply($comment_id, $comment_object) {
	global $PackageName;

	$parent_comment_id = $comment_object->comment_parent;
	$parent_comment = get_comment($parent_comment_id);

	// 若评论作者是当前评论者，则不发送推送
	if ($parent_comment->user_id == $comment_object->user_id) {
		return;
	}
	// 若评论是一级评论，则不发送推送
	if ($parent_comment->comment_parent != 0) {
		return;
	}


	// 获取评论作者的ID
	$user_id = $parent_comment->user_id;

	if ($user_id) {
		// 获取用户的推送ID
		$cid = get_user_meta($user_id, 'cid', true);

		if ($cid) {
			// 准备推送内容
			$title = '您有新的回复';
			$msg = '有人在您的评论 "' . $parent_comment->comment_content . '" 中回复了: "' . $comment_object->comment_content . '"';
			$intent = "intent://" .
				$PackageName .
				"/?#Intent;scheme=unipush;launchFlags=0x4000000;component=" .
				$PackageName .
				"/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title=" .
				urlencode($title) .
				";S.content=" .
				urlencode($msg) .
				";S.payload=" .
				urlencode(json_encode([
					"type" => "redirect",
					"data" => [
						"url" => "/pages/post/post?id=" . $parent_comment->comment_post_ID,
					]
				]))
				. ";end";
			// 调用个推API发送推送
			$result = send_push_notification(cid: $cid, title: $title, msg: $msg, clickType: 'intent', intent: $intent);
		}
	}
}


function send_push_notification($cid, $title, $msg, $clickType, $intent) {
	$push = new GTPushRequest();
	// 随机生成一个10-32位的请求ID
	$push->setRequestId(uniqid());

	// 发个推消息
	$message = new GTPushMessage();
	$notify = new GTNotification();
	$notify->setBadgeAddNum(1);
	// 0：无声音，无振动，不显示；
	// 1：无声音，无振动，锁屏不显示，通知栏中被折叠显示，导航栏无logo;
	// 2：无声音，无振动，锁屏和通知栏中都显示，通知不唤醒屏幕;
	// 3：有声音，无振动，锁屏和通知栏中都显示，通知唤醒屏幕;
	// 4：有声音，有振动，亮屏下通知悬浮展示，锁屏通知以默认形式展示且唤醒屏幕;
	$notify->setChannelLevel(3);
	$notify->setTitle($title);
	$notify->setBody($msg);
	// 点击通知后续动作，目前支持以下后续动作:
	// 1、intent：打开应用内特定页面url：打开网页地址。
	// 2、payload：自定义消息内容启动应用。
	// 3、payload_custom：自定义消息内容不启动应用。
	// 4、startapp：打开应用首页。
	// 5、none：纯通知，无后续动作
	// 6、url：打开网页地址
	$notify->setClickType($clickType);
	$notify->setIntent($intent);
	$message->setNotification($notify);

	// 厂商推送消息参数
	$pushChannel = new GTPushChannel();
	// 安卓
	$android = new GTAndroid();
	$ups = new GTUps();
	$thirdNotification = new GTThirdNotification();
	$thirdNotification->setTitle($title);
	$thirdNotification->setBody($msg);
	$thirdNotification->setIntent($intent);

	$thirdNotification->setClickType($clickType);
	$ups->addOption("HW", "badgeAddNum", 1);
	$ups->addOption("OP", "channel", "Default");

	$ups->setNotification($thirdNotification);
	$android->setUps($ups);
	$pushChannel->setAndroid($android);
	$push->setPushChannel($pushChannel);

	$push->setPushMessage($message);
	$push->setCid($cid);


	//处理返回结果
	return UniRestAPIInstance()->GTClient->pushApi()->pushToSingleByCid($push);
}