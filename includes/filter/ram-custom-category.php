<?php

function custom_fields_rest_prepare_category($data, $item, $request) {
	$cover_image = get_term_meta($item->term_id, 'cover', true);
	$data->data['cover_image'] = $cover_image;
	return $data;
}


// 增加新增分类和标签的封面图字段填写
add_action('category_add_form_fields', 'uni_new_term_cover_field');
add_action('add_tag_form_fields', 'uni_new_term_cover_field');

function uni_new_term_cover_field() {
	wp_nonce_field(basename(__FILE__), 'uni_app_term_cover_nonce');
	if (function_exists('wp_enqueue_media')) {
		wp_enqueue_media();
	}
	wp_enqueue_script('rawscript', plugins_url() . '/' . REST_API_TO_MINIPROGRAM_PLUGIN_NAME . '/includes/js/script.js', false, '1.0');
	?>
    <div class="form-field uni-app-term-cover-wrap">
        <label for="uni-app-term-cover">封面图</label>
        <input type="url" name="uni_app_term_cover" id="uni-app-term-cover" class="type-image regular-text"
               data-default-cover=""/>
        <input id="uni_app_term_cover-btn" class="button im-upload" type="button" value="选择图片"/>
    </div>
	<?php
}


// 增加编辑分类和标签的封面图字段填写
add_action('category_edit_form_fields', 'uni_edit_term_cover_field');
add_action('edit_tag_form_fields', 'uni_edit_term_cover_field');

function uni_edit_term_cover_field($term) {
	$default = '';
	$cover = get_term_meta($term->term_id, 'cover', true);
	if (!$cover) {
		$cover = $default;
	}
	if (function_exists('wp_enqueue_media')) {
		wp_enqueue_media();
	}
	wp_enqueue_script('rawscript', plugins_url() . '/' . REST_API_TO_MINIPROGRAM_PLUGIN_NAME . '/includes/js/script.js', false, '1.0');
	?>

    <tr class="form-field uni-app-term-cover-wrap">
        <th scope="row"><label for="uni-app-term-cover">封面图</label></th>
        <td>
			<?php echo wp_nonce_field(basename(__FILE__), 'uni_app_term_cover_nonce'); ?>
            <input type="url" name="uni_app_term_cover" id="uni-app-term-cover"
                   class="type-image regular-text" value="<?php echo esc_attr($cover); ?>"
                   data-default-cover="<?php echo esc_attr($default); ?>"/>
            <input id="uni_app_term_cover-btn" class="button im-upload" type="button" value="选择图片"/>
        </td>
    </tr>
<?php }


// 保存分类和标签的封面图字段
add_action('create_category', 'uni_app_save_term_cover');
add_action('edit_category', 'uni_app_save_term_cover');
add_action('create_post_tag', 'uni_app_save_term_cover');
add_action('edited_post_tag', 'uni_app_save_term_cover');
add_action('edited_topic-tag', 'uni_app_save_term_cover');

function uni_app_save_term_cover($term_id) {
	if (!isset($_POST['uni_app_term_cover_nonce']) || !wp_verify_nonce($_POST['uni_app_term_cover_nonce'], basename(__FILE__))) {
		return;
	}

	$cover = isset($_POST['uni_app_term_cover']) ? $_POST['uni_app_term_cover'] : '';

	if ('' === $cover) {
		delete_term_meta($term_id, 'cover');
	} else {
		update_term_meta($term_id, 'cover', $cover);
	}
}
