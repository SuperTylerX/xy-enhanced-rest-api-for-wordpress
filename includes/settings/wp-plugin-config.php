<?php

if (!defined('ABSPATH')) {
	exit;
}

function weixinapp_create_menu() {
	// 创建新的顶级菜单
	add_menu_page('Uni App设置', 'Uni App设置', 'administrator', 'weixinapp_slug', 'weixinapp_settings_page', 'dashicons-smartphone', 99);
	add_submenu_page('weixinapp_slug', "基础设置", "基础设置", "administrator", 'weixinapp_slug', 'weixinapp_settings_page');
	// 调用注册设置函数
	add_action('admin_init', 'register_weixinappsettings');
}

function get_jquery_source() {
	$url = plugins_url('', __FILE__);
	wp_enqueue_style("tabs", plugins_url() . "/rest-api-to-miniprogram-enhanced/includes/js/tab/tabs.css", false, "1.0", "all");
	wp_enqueue_script("tabs", plugins_url() . "/rest-api-to-miniprogram-enhanced/includes/js/tab/tabs.min.js", false, "1.0");
	wp_enqueue_script('rawscript', plugins_url() . '/' . REST_API_TO_MINIPROGRAM_PLUGIN_NAME . '/includes/js/script.js', false, '1.0');
	if (function_exists('wp_enqueue_media')) {
		wp_enqueue_media();
	}
}


function register_weixinappsettings() {

	/** 通用设置 **/
	// 评论和发帖审核
	register_setting('uniapp-group', 'uni_enable_manual_censorship'); // 是否开启人工审核
	register_setting('uniapp-group', 'uni_enable_ai_censorship');   // 是否开启AI审核
	// 显示评论IP位置
	register_setting('uniapp-group', 'uni_show_comment_location');
	// 默认图配置
	register_setting('uniapp-group', 'uni_logo_imageurl');
	register_setting('uniapp-group', 'uni_share_imageurl');

	/** 微信设置 **/
	register_setting('uniapp-group', 'wf_appid');
	register_setting('uniapp-group', 'wf_secret');
	register_setting('uniapp-group', 'uni_enable_weixin_comment_option'); // 是否开启评论
	register_setting('uniapp-group', 'uni_weixin_enterprise_minapp');

	// 微信广告设置
	register_setting('uniapp-group', 'wf_list_ad');
	register_setting('uniapp-group', 'wf_list_ad_id');
	register_setting('uniapp-group', 'wf_list_ad_every');
	register_setting('uniapp-group', 'wf_excitation_ad_id');
	register_setting('uniapp-group', 'wf_video_ad_id');
	register_setting('uniapp-group', 'wf_interstitial_ad_id');
	register_setting('uniapp-group', 'wf_detail_ad');
	register_setting('uniapp-group', 'wf_detail_ad_id');
	register_setting('uniapp-group', 'enable_index_interstitial_ad');
	register_setting('uniapp-group', 'enable_detail_interstitial_ad');
	register_setting('uniapp-group', 'enable_topic_interstitial_ad');
	register_setting('uniapp-group', 'enable_list_interstitial_ad');
	register_setting('uniapp-group', 'enable_hot_interstitial_ad');
	register_setting('uniapp-group', 'enable_comments_interstitial_ad');
	register_setting('uniapp-group', 'enable_live_interstitial_ad');


	/** QQ设置 **/
	register_setting('uniapp-group', 'wf_qq_appid');
	register_setting('uniapp-group', 'wf_qq_secret');
	register_setting('uniapp-group', 'uni_enable_qq_comment_option'); // 是否开启评论
	register_setting('uniapp-group', 'uni_qq_enterprise_minapp');


	/** 字节跳动设置 **/
	register_setting('uniapp-group', 'uni_bytedance_appid');
	register_setting('uniapp-group', 'uni_bytedance_secret');
	register_setting('uniapp-group', 'uni_enable_bytedance_comment_option'); // 是否开启评论

	/** 百度ID和密钥设置 **/
	register_setting('uniapp-group', 'uni_baidu_appid');
	register_setting('uniapp-group', 'uni_baidu_secret');
	register_setting('uniapp-group', 'uni_baidu_key');
	register_setting('uniapp-group', 'uni_enable_baidu_comment_option'); // 是否开启评论

	/** 支付宝设置 */
	register_setting('uniapp-group', 'uni_alipay_appid');
	register_setting('uniapp-group', 'uni_alipay_private_secret');
	register_setting('uniapp-group', 'uni_alipay_public_secret');
	register_setting('uniapp-group', 'uni_enable_alipay_comment_option'); // 是否开启评论

	/** H5设置 **/
	// H5端QQ互联
	register_setting('uniapp-group', 'uni_h5_qq_client_id');
	register_setting('uniapp-group', 'uni_h5_qq_callback_url');
	register_setting('uniapp-group', 'uni_enable_h5_comment_option'); // 是否开启评论

	/** APP 设置 **/
	register_setting('uniapp-group', 'uni_app_updated_version');
	register_setting('uniapp-group', 'uni_app_updated_version_code');
	register_setting('uniapp-group', 'uni_app_updated_content');
	register_setting('uniapp-group', 'uni_app_updated_download_link');

}

function weixinapp_settings_page() {
	?>
    <div class="wrap">
        <h2>Uni APP设置</h2>
		<?php

		if (!empty($_REQUEST['settings-updated'])) {
			echo '<div id="message" class="updated fade"><p><strong>设置已保存</strong></p></div>';
		}

		if (version_compare(PHP_VERSION, '5.6.0', '<=')) {

			echo '<div class="notice notice-error is-dismissible">
    <p><font color="red">提示：php版本小于5.6.0, 插件程序将无法正常使用,当前系统的php版本是:' . PHP_VERSION . '</font></p>
    </div>';
		}
		?>
        <form method="post" action="options.php">
			<?php settings_fields('uniapp-group'); ?>
			<?php do_settings_sections('uniapp-group'); ?>
            <div class="responsive-tabs">
                <h2>通用设置</h2>
                <div class="section">
                    <table class="form-table">

                        <tr valign="top">
                            <th scope="row">是否开启人工评论和发帖审核</th>
                            <td>
								<?php
								$uni_enable_manual_censorship = get_option('uni_enable_manual_censorship');
								$is_uni_enable_manual_censorship = empty($uni_enable_manual_censorship) ? '' : 'checked';
								echo '<input name="uni_enable_manual_censorship"  type="checkbox" ' . $is_uni_enable_manual_censorship . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">是否开启AI评论和发帖审核</th>
                            <td>
								<?php
								$uni_enable_ai_censorship = get_option('uni_enable_ai_censorship');
								$is_uni_enable_ai_censorship = empty($uni_enable_ai_censorship) ? '' : 'checked';
								echo '<input name="uni_enable_ai_censorship"  type="checkbox" ' . $is_uni_enable_ai_censorship . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">是否开启显示评论IP位置</th>
                            <td>
								<?php
								$uni_show_comment_location = get_option('uni_show_comment_location');
								$is_uni_show_comment_location = empty($uni_show_comment_location) ? '' : 'checked';
								echo '<input name="uni_show_comment_location"  type="checkbox" ' . $is_uni_show_comment_location . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">小程序logo图片地址</th>
                            <td><input type="text" name="uni_logo_imageurl" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_logo_imageurl')); ?>"/> <input
                                        id="uni_logo_imageurl-btn" class="button im-upload" type="button"
                                        value="选择图片"/><br/>
                            </td>

                        </tr>

                        <tr valign="top">
                            <th scope="row">分享默认图片地址</th>
                            <td><input type="text" name="uni_share_imageurl" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_share_imageurl')); ?>"/> <input
                                        id="uni_share_imageurl-btn" class="button im-upload" type="button"
                                        value="选择图片"/><br/>
                            </td>

                        </tr>
                    </table>
                </div>

                <h2>微信小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">AppID</th>
                            <td><input type="text" name="wf_appid" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_appid')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="wf_secret" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_secret')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启微信小程序的评论</th>
                            <td>
								<?php
								$uni_enable_weixin_comment_option = get_option('uni_enable_weixin_comment_option');
								$checkbox = empty($uni_enable_weixin_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_weixin_comment_option"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">小程序是否是企业主体</th>
                            <td>
								<?php
								$uni_weixin_enterprise_minapp = get_option('uni_weixin_enterprise_minapp');
								$checkbox = empty($uni_weixin_enterprise_minapp) ? '' : 'checked';
								echo '<input name="uni_weixin_enterprise_minapp"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?><p style="color: #959595; display:inline">* 如果是企业主体的小程序，请勾选</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启文章列表广告</th>
                            <td>
								<?php
								$wf_list_ad = get_option('wf_list_ad');
								$checkbox = empty($wf_list_ad) ? '' : 'checked';
								echo '<input name="wf_list_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;&emsp;&emsp;Banner广告id:&emsp;<input type="text" name="wf_list_ad_id"
                                                                            style="width:300px; height:40px"
                                                                            value="<?php echo esc_attr(get_option('wf_list_ad_id')); ?>"/>
                                <br/>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;每<input
                                        type="number" name="wf_list_ad_every" style="width:40px; height:40px"
                                        value="<?php echo esc_attr(get_option('wf_list_ad_every')); ?>"/>条列表展示一条广告<br/>
                                <p style="color: #959595; display:inline">&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;请输入整数,否则无法正常展示广告</p>
                            </td>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启内容详情页广告</th>
                            <td>

								<?php
								$wf_detail_ad = get_option('wf_detail_ad');
								$checkbox = empty($wf_detail_ad) ? '' : 'checked';
								echo '<input name="wf_detail_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;&emsp;&emsp;Banner广告id:&emsp;<input type="text" name="wf_detail_ad_id"
                                                                            style="width:300px; height:40px"
                                                                            value="<?php echo esc_attr(get_option('wf_detail_ad_id')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">激励视频广告id</th>
                            <td>
                                <input type="text" name="wf_excitation_ad_id" style="width:300px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_excitation_ad_id')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">视频广告id</th>
                            <td>
                                <input type="text" name="wf_video_ad_id" style="width:300px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_video_ad_id')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">插屏广告id</th>
                            <td>
                                <input type="text" name="wf_interstitial_ad_id" style="width:300px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_interstitial_ad_id')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">启动插屏广告的页面</th>
                            <td>
								<?php
								$enable_index_interstitial_ad = get_option('enable_index_interstitial_ad');
								$checkbox = empty($enable_index_interstitial_ad) ? '' : 'checked';
								echo '首页<input name="enable_index_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;
								<?php
								$enable_detail_interstitial_ad = get_option('enable_detail_interstitial_ad');
								$checkbox = empty($enable_detail_interstitial_ad) ? '' : 'checked';
								echo '文章详情页<input name="enable_detail_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>

                                &emsp;
								<?php
								$enable_topic_interstitial_ad = get_option('enable_topic_interstitial_ad');
								$checkbox = empty($enable_topic_interstitial_ad) ? '' : 'checked';
								echo '专题(分类)页<input name="enable_topic_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;
								<?php
								$enable_list_interstitial_ad = get_option('enable_list_interstitial_ad');
								$checkbox = empty($enable_list_interstitial_ad) ? '' : 'checked';
								echo '专题(分类)文章列表页 &emsp;<input name="enable_list_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;
								<?php
								$enable_hot_interstitial_ad = get_option('enable_hot_interstitial_ad');
								$checkbox = empty($enable_hot_interstitial_ad) ? '' : 'checked';
								echo '排行页<input name="enable_hot_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                                &emsp;
								<?php
								$enable_comments_interstitial_ad = get_option('enable_comments_interstitial_ad');
								$checkbox = empty($enable_comments_interstitial_ad) ? '' : 'checked';
								echo '最新评论页<input name="enable_comments_interstitial_ad"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>

                            </td>
                        </tr>

                    </table>
                </div>

                <h2>QQ小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">AppID</th>
                            <td><input type="text" name="wf_qq_appid" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_qq_appid')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="wf_qq_secret" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('wf_qq_secret')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启QQ小程序的评论</th>
                            <td>
								<?php
								$uni_enable_qq_comment_option = get_option('uni_enable_qq_comment_option');
								$checkbox = empty($uni_enable_qq_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_qq_comment_option"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">小程序是否是企业主体</th>
                            <td>
								<?php
								$uni_qq_enterprise_minapp = get_option('uni_qq_enterprise_minapp');
								$checkbox = empty($uni_qq_enterprise_minapp) ? '' : 'checked';
								echo '<input name="uni_qq_enterprise_minapp"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?><p style="color: #959595; display:inline">* 如果是企业主体的小程序，请勾选</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>头条小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">AppID</th>
                            <td><input type="text" name="uni_bytedance_appid" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_bytedance_appid')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="uni_bytedance_secret" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_bytedance_secret')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启头条小程序的评论</th>
                            <td>
								<?php
								$uni_enable_bytedance_comment_option = get_option('uni_enable_bytedance_comment_option');
								$checkbox = empty($uni_enable_bytedance_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_bytedance_comment_option"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                    </table>
                </div>

                <h2>百度小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">AppID</th>
                            <td><input type="text" name="uni_baidu_appid" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_baidu_appid')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="uni_baidu_secret" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_baidu_secret')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">AppKey</th>
                            <td><input type="text" name="uni_baidu_key" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_baidu_key')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启百度小程序的评论</th>
                            <td>
								<?php
								$uni_enable_baidu_comment_option = get_option('uni_enable_baidu_comment_option');
								$checkbox = empty($uni_enable_baidu_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_baidu_comment_option"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                    </table>
                </div>

                <h2>支付宝小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">AppID</th>
                            <td><input type="text" name="uni_alipay_appid" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_alipay_appid')); ?>"/>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">应用私钥</th>
                            <td><textarea name="uni_alipay_private_secret" style="width:400px"
                                ><?php echo esc_attr(get_option('uni_alipay_private_secret')); ?></textarea>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">支付宝公钥</th>
                            <td><textarea name="uni_alipay_public_secret" style="width:400px"
                                ><?php echo esc_attr(get_option('uni_alipay_public_secret')); ?></textarea>*
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">开启支付宝小程序的评论</th>
                            <td>
								<?php
								$uni_enable_alipay_comment_option = get_option('uni_enable_alipay_comment_option');
								$checkbox = empty($uni_enable_alipay_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_alipay_comment_option"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                    </table>
                </div>

                <h2>APP设置</h2>
                <div class="section">
                    <table class="form-table">

                        <tr valign="top">
                            <th scope="row"><label for="uni_app_updated_version">APP版本名称</label></th>
                            <td><input type="text" name="uni_app_updated_version" style="width:400px; height:40px"
                                       id="uni_app_updated_version"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_version')); ?>"/>
                                <p style="color: #959595; display:inline">*版本名称用于用户端显示，形式类似于"1.2.0"</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="uni_app_updated_version_code">APP版本号</label></th>
                            <td><input type="number" name="uni_app_updated_version_code"
                                       style="width:400px; height:40px"
                                       id="uni_app_updated_version_code"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_version_code')); ?>"/>
                                <p style="color: #959595; display:inline">
                                    *版本号用于开发者区分，请使用纯数字表示，形式类似于"120"</p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="uni_app_updated_content">APP下载链接</label></th>
                            <td><input type="text" name="uni_app_updated_content" style="width:400px; height:40px"
                                       id="uni_app_updated_content"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_content')); ?>"/>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><label for="uni_app_updated_download_link">APP更新内容</label></th>
                            <td>
                                <textarea name="uni_app_updated_download_link" cols="60"
                                          rows="10"
                                          id="uni_app_updated_download_link"><?php echo esc_attr(get_option('uni_app_updated_download_link')); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>H5设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">H5 QQ互联登录ClientID</th>
                            <td><input type="text" name="uni_h5_qq_client_id" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_h5_qq_client_id')); ?>"/>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">H5 QQ互联回调地址</th>
                            <td><input type="text" name="uni_h5_qq_callback_url" style="width:400px; height:40px"
                                       value="<?php echo esc_attr(get_option('uni_h5_qq_callback_url')); ?>"/>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">开启H5的评论和发帖</th>
                            <td>
								<?php
								$uni_enable_h5_comment_option = get_option('uni_enable_h5_comment_option');
								$checkbox = empty($uni_enable_h5_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_h5_comment_option" type="checkbox"  value="true" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>


			<?php submit_button(); ?>
        </form>
		<?php get_jquery_source(); ?>
        <script>
            jQuery(document).ready(function ($) {
                RESPONSIVEUI.responsiveTabs();
            });
        </script>
    </div>
<?php }
