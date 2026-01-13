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
			$mode = (string) get_post_meta($post->ID, '_tmw_slot_mode', true);
			if ($mode !== 'widget' && $mode !== 'shortcode') {
				$mode = $shortcode !== '' ? 'shortcode' : 'widget';
			}

			wp_nonce_field('tmw_slot_banner_save', 'tmw_slot_banner_nonce');
			?>
			<p>
				<label>
					<input type="checkbox" name="tmw_slot_enabled" value="1" <?php checked($enabled); ?> />
					<?php esc_html_e('Enable slot banner on this model page', 'retrotube-child'); ?>
				</label>
			</p>

			<p style="margin-top:10px;">
				<strong><?php esc_html_e('Banner source', 'retrotube-child'); ?></strong><br />
				<label style="display:block; margin-top:6px;">
					<input type="radio" name="tmw_slot_mode" value="widget" <?php checked($mode, 'widget'); ?> />
					<?php esc_html_e('Use Global Widget Area', 'retrotube-child'); ?>
				</label>
				<label style="display:block; margin-top:6px;">
					<input type="radio" name="tmw_slot_mode" value="shortcode" <?php checked($mode, 'shortcode'); ?> />
					<?php esc_html_e('Use Custom Shortcode', 'retrotube-child'); ?>
				</label>
				<span class="description" style="display:block; margin-top:6px;">
					<?php esc_html_e('Widget area name: Model Page – Slot Banner (Global)', 'retrotube-child'); ?>
				</span>
			</p>

			<p style="margin-top:10px;">
				<label for="tmw_slot_shortcode" style="display:block; font-weight:600;">
					<?php esc_html_e('Shortcode (used when "Use Custom Shortcode" is selected)', 'retrotube-child'); ?>
				</label>
				<textarea
					id="tmw_slot_shortcode"
					name="tmw_slot_shortcode"
					style="width:100%; min-height:80px;"
					placeholder="[tmw_slot_machine]"><?php echo esc_textarea($shortcode); ?></textarea>
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

	$enabled = isset($_POST['tmw_slot_enabled']);

	if (!$enabled) {
		delete_post_meta($post_id, '_tmw_slot_enabled');
		delete_post_meta($post_id, '_tmw_slot_mode');
		delete_post_meta($post_id, '_tmw_slot_shortcode');
		return;
	}

	update_post_meta($post_id, '_tmw_slot_enabled', '1');

	$mode = 'widget';
	if (isset($_POST['tmw_slot_mode'])) {
		$mode_value = sanitize_text_field(wp_unslash($_POST['tmw_slot_mode']));
		if ($mode_value === 'shortcode' || $mode_value === 'widget') {
			$mode = $mode_value;
		}
	}

	if ($mode === 'shortcode') {
		update_post_meta($post_id, '_tmw_slot_mode', 'shortcode');
		$shortcode = '';
		if (isset($_POST['tmw_slot_shortcode'])) {
			$shortcode = sanitize_textarea_field(wp_unslash($_POST['tmw_slot_shortcode']));
			$shortcode = trim($shortcode);
		}

		if ($shortcode === '') {
			delete_post_meta($post_id, '_tmw_slot_shortcode');
		} else {
			update_post_meta($post_id, '_tmw_slot_shortcode', $shortcode);
		}
	} else {
		update_post_meta($post_id, '_tmw_slot_mode', 'widget');
		delete_post_meta($post_id, '_tmw_slot_shortcode');
	}
});
