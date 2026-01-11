<?php
if (!defined('ABSPATH')) { exit; }

// General shortcodes will be migrated here in Phase 2.

if (!function_exists('tmw_audit_slot_machine_shortcode')) {
	function tmw_audit_slot_machine_shortcode($output, $tag, $attr, $m) {
		if ($tag !== 'tmw_slot_machine') {
			return $output;
		}

		if (defined('TMW_DEBUG') && TMW_DEBUG) {
			$current_filter = function_exists('current_filter') ? (string) current_filter() : '';
			$location_hint = (function_exists('doing_filter') && doing_filter('the_content')) ? 'the_content' : 'other';
			$caller_summary = function_exists('wp_debug_backtrace_summary') ? wp_debug_backtrace_summary() : '';

			if (is_array($caller_summary)) {
				$caller_summary = implode(', ', $caller_summary);
			}

			error_log(
				'[TMW-SLOT-AUDIT] shortcode_run location_hint=' . $location_hint .
				' current_filter=' . ($current_filter !== '' ? $current_filter : 'none') .
				' caller=' . $caller_summary
			);
		}

		return $output;
	}
}

add_filter('do_shortcode_tag', 'tmw_audit_slot_machine_shortcode', 10, 4);
