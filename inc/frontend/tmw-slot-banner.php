<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!function_exists('tmw_slot_banner_meta_keys_enabled')) {
	function tmw_slot_banner_meta_keys_enabled(): array {
		return [
			'tmw_slot_banner_enabled',
			'tmw_model_slot_enabled',
			'tmw_slot_enabled',
			'slot_banner_enabled',
			'_tmw_slot_enabled',
		];
	}
}

if (!function_exists('tmw_slot_banner_meta_keys_shortcode')) {
	function tmw_slot_banner_meta_keys_shortcode(): array {
		return [
			'tmw_slot_banner_shortcode',
			'tmw_model_slot_shortcode',
			'tmw_slot_shortcode',
			'slot_banner_shortcode',
			'_tmw_slot_shortcode',
		];
	}
}

if (!function_exists('tmw_slot_banner_get_shortcode_data')) {
	function tmw_slot_banner_get_shortcode_data(int $post_id): array {
		foreach (tmw_slot_banner_meta_keys_shortcode() as $key) {
			$value = get_post_meta($post_id, $key, true);
			if (is_string($value) && trim($value) !== '') {
				return [
					'shortcode' => trim($value),
					'key' => $key,
				];
			}
		}

		return [
			'shortcode' => '',
			'key' => '',
		];
	}
}

if (!function_exists('tmw_model_slot_is_enabled')) {
	function tmw_model_slot_is_enabled(int $post_id): bool {
		$enabled_values = [1, '1', 'yes', 'on', true];
		$has_enabled_key = false;
		$is_enabled = false;

		foreach (tmw_slot_banner_meta_keys_enabled() as $key) {
			if (!metadata_exists('post', $post_id, $key)) {
				continue;
			}

			$has_enabled_key = true;
			$value = get_post_meta($post_id, $key, true);
			$normalized = is_string($value) ? strtolower(trim($value)) : $value;
			if (in_array($normalized, $enabled_values, true)) {
				$is_enabled = true;
			}
		}

		if ($has_enabled_key) {
			return $is_enabled;
		}

		foreach (tmw_slot_banner_meta_keys_shortcode() as $key) {
			$value = get_post_meta($post_id, $key, true);
			if (is_string($value) && trim($value) !== '') {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('tmw_model_slot_get_shortcode')) {
	function tmw_model_slot_get_shortcode(int $post_id): string {
		$data = tmw_slot_banner_get_shortcode_data($post_id);
		$shortcode = $data['shortcode'];

		if ($shortcode === '' && tmw_model_slot_is_enabled($post_id)) {
			return '[tmw_slot_machine]';
		}

		return $shortcode;
	}
}

if (!function_exists('tmw_render_model_slot_banner')) {
	function tmw_render_model_slot_banner(int $post_id): string {
		if (!tmw_model_slot_is_enabled($post_id)) {
			return '';
		}

		$debug = defined('WP_DEBUG') && WP_DEBUG;
		$shortcode_data = tmw_slot_banner_get_shortcode_data($post_id);
		$shortcode = $shortcode_data['shortcode'];
		$shortcode_key = $shortcode_data['key'];

		if ($shortcode === '') {
			if ($debug) {
				error_log('[TMW-SLOT] enabled but shortcode empty; fallback=[tmw_slot_machine]');
			}
			$shortcode = '[tmw_slot_machine]';
			$shortcode_key = 'fallback';
		}

		$out = do_shortcode($shortcode);
		$out_len = is_string($out) ? strlen(trim($out)) : 0;

		if ($debug) {
			error_log('[TMW-SLOT] render model_id=' . $post_id . ' enabled=1 shortcode_key=' . $shortcode_key . ' out_len=' . $out_len);
		}

		if ($out_len <= 0) {
			return '';
		}

		if (function_exists('tmw_child_inject_slot_machine_dimensions')) {
			$out = tmw_child_inject_slot_machine_dimensions($out);
		}

		return '<div class="tmw-slot-banner-wrap" data-tmw-slot="model"><div class="tmw-slot-banner">' . wp_kses_post($out) . '</div></div>';
	}
}
