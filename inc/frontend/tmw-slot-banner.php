<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('tmw_model_slot_is_enabled')) {
	function tmw_model_slot_is_enabled(int $post_id): bool {
		return (get_post_meta($post_id, '_tmw_slot_enabled', true) === '1');
	}
}

if (!function_exists('tmw_model_slot_get_shortcode')) {
	function tmw_model_slot_get_shortcode(int $post_id): string {
		$sc = (string) get_post_meta($post_id, '_tmw_slot_shortcode', true);
		$sc = trim($sc);
		return ($sc !== '') ? $sc : '[tmw_slot_machine]';
	}
}

if (!function_exists('tmw_render_model_slot_banner')) {
	function tmw_render_model_slot_banner(int $post_id): string {
		$debug = defined('TMW_DEBUG') && TMW_DEBUG;
		$slot_enabled_raw = get_post_meta($post_id, '_tmw_slot_enabled', true);
		$slot_shortcode_raw = get_post_meta($post_id, '_tmw_slot_shortcode', true);

		if ($debug) {
			$enabled_log_value = ($slot_enabled_raw === '' || $slot_enabled_raw === null) ? 'empty' : (string) $slot_enabled_raw;
			$shortcode_log_value = ($slot_shortcode_raw === '' || $slot_shortcode_raw === null) ? 'empty' : (string) $slot_shortcode_raw;
			error_log('[TMW-SLOT-AUDIT] model_id=' . $post_id . ' slot_enabled_raw=' . $enabled_log_value . ' slot_shortcode_raw=' . $shortcode_log_value);
		}

		if (!tmw_model_slot_is_enabled($post_id)) {
			if ($debug) {
				error_log('[TMW-SLOT] disabled model_id=' . $post_id);
			}
			return '';
		}

		$shortcode = tmw_model_slot_get_shortcode($post_id);

		// Render via shortcode only
		$out = do_shortcode($shortcode);

		// IMPORTANT: do NOT use wp_strip_all_tags gating (iframe/img-only banners become “empty”).
		$raw_len = is_string($out) ? strlen(trim($out)) : 0;

		if ($debug) {
			error_log('[TMW-SLOT] render model_id=' . $post_id . ' shortcode=' . $shortcode . ' raw_len=' . $raw_len);
			error_log('[TMW-SLOT-AUDIT] model_id=' . $post_id . ' do_shortcode_len=' . $raw_len);
		}

		if ($raw_len <= 0) {
			return '';
		}

		// Keep wrappers minimal and stable; no layout changes elsewhere
		return '<div class="tmw-slot-banner">' . wp_kses_post($out) . '</div>';
	}
}
