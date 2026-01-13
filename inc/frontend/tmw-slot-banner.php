<?php
if (!defined('ABSPATH')) {
	exit;
}

add_action('widgets_init', function () {
	register_sidebar([
		'id' => 'tmw-model-slot-banner-global',
		'name' => __('Model Page – Slot Banner (Global)', 'retrotube-child'),
		'description' => __('Global fallback slot banner shown on model pages when enabled per model.', 'retrotube-child'),
		'before_widget' => '',
		'after_widget' => '',
		'before_title' => '',
		'after_title' => '',
	]);
});

if (!function_exists('tmw_model_slot_is_enabled')) {
	function tmw_model_slot_is_enabled(int $post_id): bool {
		return (get_post_meta($post_id, '_tmw_slot_enabled', true) === '1');
	}
}

if (!function_exists('tmw_model_slot_get_shortcode')) {
	function tmw_model_slot_get_shortcode(int $post_id): string {
		$sc = (string) get_post_meta($post_id, '_tmw_slot_shortcode', true);
		return trim($sc);
	}
}

if (!function_exists('tmw_model_slot_get_mode')) {
	function tmw_model_slot_get_mode(int $post_id): string {
		$mode = (string) get_post_meta($post_id, '_tmw_slot_mode', true);
		if ($mode === 'widget' || $mode === 'shortcode') {
			return $mode;
		}

		$shortcode = tmw_model_slot_get_shortcode($post_id);
		return ($shortcode !== '') ? 'shortcode' : 'widget';
	}
}

if (!function_exists('tmw_render_model_slot_banner_zone')) {
	function tmw_render_model_slot_banner_zone(int $post_id): string {
		$debug = defined('TMW_DEBUG') && TMW_DEBUG;
		$enabled = tmw_model_slot_is_enabled($post_id);
		$mode = tmw_model_slot_get_mode($post_id);
		$output_len = 0;
		$source = $mode === 'shortcode' ? 'shortcode' : 'widget';

		if (!$enabled) {
			if ($debug) {
				error_log('[TMW-SLOT] model_id=' . $post_id . ' enabled=no mode=' . $mode . ' source=' . $source . ' output_len=' . $output_len);
			}
			return '';
		}

		if ($mode === 'shortcode') {
			$shortcode = tmw_model_slot_get_shortcode($post_id);
			if ($shortcode === '') {
				if ($debug) {
					error_log('[TMW-SLOT] model_id=' . $post_id . ' enabled=yes mode=shortcode source=shortcode output_len=0');
				}
				return '';
			}

			$out = do_shortcode($shortcode);
			$output_len = is_string($out) ? strlen(trim($out)) : 0;

			if ($debug) {
				error_log('[TMW-SLOT] model_id=' . $post_id . ' enabled=yes mode=shortcode source=shortcode output_len=' . $output_len);
			}

			if ($output_len === 0) {
				return '';
			}

			return '<div class="tmw-slot-banner-zone"><div class="tmw-slot-banner">' . $out . '</div></div>';
		}

		if (!is_active_sidebar('tmw-model-slot-banner-global')) {
			if ($debug) {
				error_log('[TMW-SLOT] model_id=' . $post_id . ' enabled=yes mode=widget source=widget output_len=0');
			}
			return '';
		}

		ob_start();
		dynamic_sidebar('tmw-model-slot-banner-global');
		$out = ob_get_clean();
		$output_len = is_string($out) ? strlen(trim($out)) : 0;

		if ($debug) {
			error_log('[TMW-SLOT] model_id=' . $post_id . ' enabled=yes mode=widget source=widget output_len=' . $output_len);
		}

		if ($output_len === 0) {
			return '';
		}

		return '<div class="tmw-slot-banner-zone"><div class="tmw-slot-banner">' . $out . '</div></div>';
	}
}

if (!function_exists('tmw_render_model_slot_banner')) {
	function tmw_render_model_slot_banner(int $post_id): string {
		return tmw_render_model_slot_banner_zone($post_id);
	}
}
