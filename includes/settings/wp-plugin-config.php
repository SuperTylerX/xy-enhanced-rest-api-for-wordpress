<?php

if (!defined('ABSPATH')) {
	exit;
}

function weixinapp_create_menu() {
	// 创建新的顶级菜单
	add_menu_page('星荧小程序设置', '星荧小程序设置', 'administrator', 'uni_app_slug', 'uni_app_settings_page', 'dashicons-smartphone', 99);
	add_submenu_page('uni_app_slug', "基础设置", "基础设置", "administrator", 'uni_app_slug', 'uni_app_settings_page');
	// 调用注册设置函数
	add_action('admin_init', 'register_uni_app_settings');
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


function register_uni_app_settings() {

	/** 通用设置 **/
	// 评论和发帖审核
	register_setting('uniapp-group', 'uni_enable_manual_censorship'); // 是否开启人工审核
	register_setting('uniapp-group', 'uni_enable_ai_censorship');   // 是否开启AI审核
	// 显示评论IP位置
	register_setting('uniapp-group', 'uni_show_comment_location');
	// 默认图配置
	register_setting('uniapp-group', 'uni_logo_imageurl');
	register_setting('uniapp-group', 'uni_share_imageurl');
	// Uni Push 设置
	register_setting('uniapp-group', 'uni_enable_uni_push');
	register_setting('uniapp-group', 'uni_push_app_id');
	register_setting('uniapp-group', 'uni_push_app_key');
	register_setting('uniapp-group', 'uni_push_master_secret');

	/** 微信小程序设置 **/
	register_setting('uniapp-group', 'wf_appid');
	register_setting('uniapp-group', 'wf_secret');
	register_setting('uniapp-group', 'uni_enable_weixin_comment_option'); // 是否开启评论
	register_setting('uniapp-group', 'uni_weixin_enterprise_minapp');

	// 一次性订阅消息
	register_setting('uniapp-group', 'uni_enable_weixin_push');
	register_setting('uniapp-group', 'uni_weixin_comment_template_id');
	register_setting('uniapp-group', 'uni_weixin_comment_reply_template_id');

	/** QQ小程序设置 **/
	register_setting('uniapp-group', 'wf_qq_appid');
	register_setting('uniapp-group', 'wf_qq_secret');
	register_setting('uniapp-group', 'uni_enable_qq_comment_option'); // 是否开启评论
	register_setting('uniapp-group', 'uni_qq_enterprise_minapp');

	/** 字节跳动小程序设置 **/
	register_setting('uniapp-group', 'uni_bytedance_appid');
	register_setting('uniapp-group', 'uni_bytedance_secret');
	register_setting('uniapp-group', 'uni_enable_bytedance_comment_option'); // 是否开启评论

	/** 百度小程序设置 **/
	register_setting('uniapp-group', 'uni_baidu_appid');
	register_setting('uniapp-group', 'uni_baidu_secret');
	register_setting('uniapp-group', 'uni_baidu_key');
	register_setting('uniapp-group', 'uni_enable_baidu_comment_option'); // 是否开启评论

	/** 支付宝小程序设置 */
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
	register_setting('uniapp-group', 'uni_app_updated_log');
	register_setting('uniapp-group', 'uni_app_updated_download_link');
	register_setting('uniapp-group', 'uni_app_updated_ios_download_link');
	register_setting('uniapp-group', 'uni_app_force_update');
	register_setting('uniapp-group', 'uni_app_android_package_name');

}

function uni_app_settings_page() {
	?>
    <div class="wrap">
        <h2>星荧小程序设置</h2>
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
                    <h3 class="title">评论与发帖设置</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="uni_enable_manual_censorship">是否开启评论和发帖人工审核</label>
                            </th>
                            <td>
								<?php
								$uni_enable_manual_censorship = get_option('uni_enable_manual_censorship');
								$is_uni_enable_manual_censorship = empty($uni_enable_manual_censorship) ? '' : 'checked';
								echo '<input name="uni_enable_manual_censorship" id="uni_enable_manual_censorship" type="checkbox" ' . $is_uni_enable_manual_censorship . ' />';
								?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="uni_enable_ai_censorship">是否开启评论和发帖AI审核</label>
                            </th>
                            <td>
								<?php
								$uni_enable_ai_censorship = get_option('uni_enable_ai_censorship');
								$is_uni_enable_ai_censorship = empty($uni_enable_ai_censorship) ? '' : 'checked';
								echo '<input name="uni_enable_ai_censorship" id="uni_enable_ai_censorship"  type="checkbox" ' . $is_uni_enable_ai_censorship . ' />';
								?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_show_comment_location">是否开启显示评论IP位置</label></th>
                            <td>
								<?php
								$uni_show_comment_location = get_option('uni_show_comment_location');
								$is_uni_show_comment_location = empty($uni_show_comment_location) ? '' : 'checked';
								echo '<input name="uni_show_comment_location" id="uni_show_comment_location" type="checkbox" ' . $is_uni_show_comment_location . ' />';
								?>
                            </td>
                        </tr>

                    </table>

                    <h3 class="title">默认图设置</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="uni_logo_imageurl">小程序logo图片地址</label></th>
                            <td><input type="text" name="uni_logo_imageurl" id="uni_logo_imageurl" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_logo_imageurl')); ?>"/> <input
                                        id="uni_logo_imageurl-btn" class="button im-upload" type="button"
                                        value="选择图片"/><br/>
                            </td>

                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_share_imageurl">分享默认图片地址</label></th>
                            <td><input type="text" name="uni_share_imageurl" id="uni_share_imageurl"
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_share_imageurl')); ?>"/> <input
                                        id="uni_share_imageurl-btn" class="button im-upload" type="button"
                                        value="选择图片"/><br/>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>微信小程序设置</h2>
                <div class="section">
                    <h3 class="title">基本设置</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="wf_appid">AppID</label></th>
                            <td><input type="text" name="wf_appid" id="wf_appid" class="regular-text"
                                       value="<?php echo esc_attr(get_option('wf_appid')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="wf_secret">AppSecret</label></th>
                            <td><input type="text" name="wf_secret" id="wf_secret" class="regular-text"
                                       value="<?php echo esc_attr(get_option('wf_secret')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_enable_weixin_comment_option">开启微信小程序的评论</label>
                            </th>
                            <td>
								<?php
								$uni_enable_weixin_comment_option = get_option('uni_enable_weixin_comment_option');
								$checkbox = empty($uni_enable_weixin_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_weixin_comment_option" id="uni_enable_weixin_comment_option" type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_weixin_enterprise_minapp">小程序是否是企业主体</label></th>
                            <td>
								<?php
								$uni_weixin_enterprise_minapp = get_option('uni_weixin_enterprise_minapp');
								$checkbox = empty($uni_weixin_enterprise_minapp) ? '' : 'checked';
								echo '<input name="uni_weixin_enterprise_minapp" id="uni_weixin_enterprise_minapp" type="checkbox" value="1" ' . $checkbox . ' />';
								?>
                                <p style="color: #959595; display:inline">* 企业主体小程序会启用webview功能</p>
                            </td>
                        </tr>

                    </table>

                    <h3 class="title">推送设置</h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="uni_enable_weixin_push">是否启用一次性订阅消息</label>
                            </th>
                            <td>
                                <input name="uni_enable_weixin_push" id="uni_enable_weixin_push"
                                       type="checkbox" <?php echo get_option('uni_enable_weixin_push') ? 'checked' : ''; ?> />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_weixin_comment_template_id">新评论提醒模版ID</label></th>
                            <td><input type="text" name="uni_weixin_comment_template_id"
                                       id="uni_weixin_comment_template_id" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_weixin_comment_template_id')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_weixin_comment_reply_template_id">评论回复通知模版ID</label>
                            </th>
                            <td><input type="text" name="uni_weixin_comment_reply_template_id"
                                       id="uni_weixin_comment_reply_template_id" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_weixin_comment_reply_template_id')); ?>"/>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>QQ小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr>
                            <th scope="row">AppID</th>
                            <td><input type="text" name="wf_qq_appid" class="regular-text"
                                       value="<?php echo esc_attr(get_option('wf_qq_appid')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="wf_qq_secret" class="regular-text"
                                       value="<?php echo esc_attr(get_option('wf_qq_secret')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">开启QQ小程序的评论</th>
                            <td>
								<?php
								$uni_enable_qq_comment_option = get_option('uni_enable_qq_comment_option');
								$checkbox = empty($uni_enable_qq_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_qq_comment_option"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">小程序是否是企业主体</th>
                            <td>
								<?php
								$uni_qq_enterprise_minapp = get_option('uni_qq_enterprise_minapp');
								$checkbox = empty($uni_qq_enterprise_minapp) ? '' : 'checked';
								echo '<input name="uni_qq_enterprise_minapp"  type="checkbox"  value="1" ' . $checkbox . ' />';
								?><p style="color: #959595; display:inline">* 企业主体小程序会启用webview功能</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <h2>头条小程序设置</h2>
                <div class="section">
                    <table class="form-table">
                        <tr>
                            <th scope="row">AppID</th>
                            <td><input type="text" name="uni_bytedance_appid" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_bytedance_appid')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="uni_bytedance_secret" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_bytedance_secret')); ?>"/>
                            </td>
                        </tr>

                        <tr>
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
                        <tr>
                            <th scope="row">AppID</th>
                            <td><input type="text" name="uni_baidu_appid" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_baidu_appid')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">AppSecret</th>
                            <td><input type="text" name="uni_baidu_secret" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_baidu_secret')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">AppKey</th>
                            <td><input type="text" name="uni_baidu_key" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_baidu_key')); ?>"/>
                            </td>
                        </tr>

                        <tr>
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
                        <tr>
                            <th scope="row">AppID</th>
                            <td><input type="text" name="uni_alipay_appid" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_alipay_appid')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">应用私钥</th>
                            <td><textarea name="uni_alipay_private_secret" class="large-text"
                                ><?php echo esc_attr(get_option('uni_alipay_private_secret')); ?></textarea>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">支付宝公钥</th>
                            <td><textarea name="uni_alipay_public_secret" class="large-text"
                                ><?php echo esc_attr(get_option('uni_alipay_public_secret')); ?></textarea>
                            </td>
                        </tr>

                        <tr>
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
                    <h3 class="title">基本设置</h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="uni_app_updated_version">APP版本名称</label></th>
                            <td><input type="text" name="uni_app_updated_version" class="regular-text"
                                       id="uni_app_updated_version"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_version')); ?>"
                                       placeholder="版本名称用于用户端显示，形式类似于1.2.0"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_app_updated_version_code">APP版本号</label></th>
                            <td><input type="number" name="uni_app_updated_version_code"
                                       class="regular-text"
                                       id="uni_app_updated_version_code"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_version_code')); ?>"
                                       placeholder="版本号用于开发者区分，请使用纯数字表示，形式类似于120"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_app_android_package_name">APP Android版本包名</label></th>
                            <td><input type="text" name="uni_app_android_package_name" id="uni_app_android_package_name"
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_app_android_package_name')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_app_updated_download_link">安卓APP更新链接</label></th>
                            <td>
                                <input type="text" class="regular-text"
                                       name="uni_app_updated_download_link"
                                       id="uni_app_updated_download_link"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_download_link')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_app_updated_ios_download_link">iOS APP应用商店地址</label>
                            </th>
                            <td>
                                <input type="text" class="regular-text"
                                       name="uni_app_updated_ios_download_link"
                                       id="uni_app_updated_ios_download_link"
                                       value="<?php echo esc_attr(get_option('uni_app_updated_ios_download_link')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_app_force_update">是否强制更新</label></th>
                            <td>
								<?php
								$uni_app_force_update = get_option('uni_app_force_update');
								$checkbox = empty($uni_app_force_update) ? '' : 'checked';
								echo '<input name="uni_app_force_update" id="uni_app_force_update" type="checkbox"  value="1" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_app_updated_log">APP更新日志</label></th>
                            <td><textarea name="uni_app_updated_log"
                                          id="uni_app_updated_log"
                                          class="large-text"
                                          rows="10"
                                ><?php echo esc_attr(get_option('uni_app_updated_log')); ?></textarea>
                            </td>
                        </tr>

                    </table>


                    <h3 class="title">推送设置</h3>
                    <table class="form-table">

                        <tr>
                            <th scope="row"><label for="uni_enable_uni_push">是否启用Uni Push 1.0</label></th>
                            <td>
                                <input name="uni_enable_uni_push" id="uni_enable_uni_push"
                                       type="checkbox" <?php echo get_option('uni_enable_uni_push') ? 'checked' : ''; ?> />
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_push_app_id">Uni Push AppID</label></th>
                            <td><input type="text" name="uni_push_app_id" id="uni_push_app_id" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_push_app_id')); ?>"/>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="uni_push_app_key">Push AppKey</label></th>
                            <td><input type="text" name="uni_push_app_key" id="uni_push_app_key" class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_push_app_key')); ?>"/>
                            </td>
                        </tr>


                        <tr>
                            <th scope="row"><label for="uni_push_master_secret">Uni Push MasterSecret</label></th>
                            <td><input type="text" name="uni_push_master_secret" id="uni_push_master_secret"
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_push_master_secret')); ?>"/>
                            </td>
                        </tr>

                    </table>
                </div>

                <h2>H5设置</h2>
                <div class="section">
                    <h3 class="title">基本设置</h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="uni_enable_h5_comment_option">开启H5的评论和发帖</label></th>
                            <td>
								<?php
								$uni_enable_h5_comment_option = get_option('uni_enable_h5_comment_option');
								$checkbox = empty($uni_enable_h5_comment_option) ? '' : 'checked';
								echo '<input name="uni_enable_h5_comment_option" id="uni_enable_h5_comment_option" type="checkbox"  value="true" ' . $checkbox . ' />';
								?>
                            </td>
                        </tr>
                    </table>

                    <h3 class="title">QQ互联</h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="uni_h5_qq_client_id">H5 QQ互联登录ClientID</label></th>
                            <td><input type="text" name="uni_h5_qq_client_id" id="uni_h5_qq_client_id"
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_h5_qq_client_id')); ?>"/>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="uni_h5_qq_callback_url">H5 QQ互联回调地址</label></th>
                            <td><input type="text" name="uni_h5_qq_callback_url" id="uni_h5_qq_callback_url"
                                       class="regular-text"
                                       value="<?php echo esc_attr(get_option('uni_h5_qq_callback_url')); ?>"/>
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
