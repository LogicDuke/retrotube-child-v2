<?php
if (!defined('ABSPATH')) {
	exit;
}

add_action('add_meta_boxes', function () {
	add_meta_box(
		'tmw-slot-banner',
		__('Slot Banner', 'retrotube-child'),
		function ($post) {
			if (!$post || $post->post_type !== 'model') {
				return;
			}

			$enabled = (get_post_meta($post->ID, '_tmw_slot_enabled', true) === '1');
			$shortcode = (string) get_post_meta($post->ID, '_tmw_slot_shortcode', true);

			wp_nonce_field('tmw_slot_banner_save', 'tmw_slot_banner_nonce');
			?>
			<p>
				<label>
					<input type="checkbox" name="tmw_slot_enabled" value="1" <?php checked($enabled); ?> />
					<?php esc_html_e('Enable slot banner on this model page', 'retrotube-child'); ?>
				</label>
			</p>

			<p style="margin-top:10px;">
				<label for="tmw_slot_shortcode" style="display:block; font-weight:600;">
					<?php esc_html_e('Shortcode', 'retrotube-child'); ?>
				</label>
				<input type="text"
					id="tmw_slot_shortcode"
					name="tmw_slot_shortcode"
					value="<?php echo esc_attr($shortcode); ?>"
					style="width:100%;"
					placeholder="[tmw_slot_machine]" />
				<span class="description" style="display:block; margin-top:6px;">
					<?php esc_html_e('Paste the shortcode for the slot banner you want to display on this model.', 'retrotube-child'); ?>
				</span>
			</p>
			<?php
		},
		'model',
		'side',
		'default'
	);
});

add_action('save_post_model', function ($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
		return;
	}

	if (!isset($_POST['tmw_slot_banner_nonce']) ||
		!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tmw_slot_banner_nonce'])), 'tmw_slot_banner_save')) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$enabled = isset($_POST['tmw_slot_enabled']) ? '1' : '0';
	$shortcode = '';

	if (isset($_POST['tmw_slot_shortcode'])) {
		$shortcode = sanitize_text_field(wp_unslash($_POST['tmw_slot_shortcode']));
		$shortcode = trim($shortcode);
	}

	update_post_meta($post_id, '_tmw_slot_enabled', $enabled);
	update_post_meta($post_id, '_tmw_slot_shortcode', $shortcode);
});
