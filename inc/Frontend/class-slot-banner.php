<?php
/**
 * Slot Banner - custom banner zones on model pages.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Frontend;

defined('ABSPATH') || exit;

class SlotBanner {

    const META_KEY = '_tmw_slot_banner';

    public function __construct() {
        add_action('after_setup_theme', [$this, 'register_functions']);
    }

    public function register_functions() {
        if (!function_exists('tmw_render_model_slot_banner_zone')) {
            function tmw_render_model_slot_banner_zone($post_id) {
                return SlotBanner::render($post_id);
            }
        }
    }

    public static function render($post_id) {
        $post_id = (int) $post_id;
        if (!$post_id) return '';

        $content = get_post_meta($post_id, self::META_KEY, true);
        if (empty($content)) return '';

        $content = do_shortcode($content);
        return sprintf('<div class="tmw-slot-banner-zone">%s</div>', wp_kses_post($content));
    }

    public static function get_content($post_id) {
        return get_post_meta((int) $post_id, self::META_KEY, true);
    }

    public static function save_content($post_id, $content) {
        if (empty($content)) return delete_post_meta($post_id, self::META_KEY);
        return (bool) update_post_meta($post_id, self::META_KEY, wp_kses_post($content));
    }
}
