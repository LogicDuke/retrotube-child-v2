<?php
/**
 * Theme shortcodes.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Frontend;

defined('ABSPATH') || exit;

use TMW\Core\Helpers;

class Shortcodes {

    public function __construct() {
        add_shortcode('tmw_accordion', [$this, 'accordion']);
        add_shortcode('tmw_video_count', [$this, 'video_count']);
        add_shortcode('tmw_model_count', [$this, 'model_count']);
        add_shortcode('tmw_current_year', [$this, 'current_year']);
    }

    public function accordion($atts, $content = '') {
        $atts = shortcode_atts(['lines' => 3, 'collapsed' => 'true', 'class' => ''], $atts, 'tmw_accordion');
        if (empty($content)) return '';
        return Helpers::render_accordion([
            'content_html' => do_shortcode($content),
            'lines' => (int) $atts['lines'],
            'collapsed' => $atts['collapsed'] === 'true',
            'class' => sanitize_html_class($atts['class']),
        ]);
    }

    public function video_count($atts) {
        $atts = shortcode_atts(['format' => 'true'], $atts, 'tmw_video_count');
        $post_type = post_type_exists('video') ? 'video' : 'post';
        $count = wp_count_posts($post_type);
        $total = isset($count->publish) ? (int) $count->publish : 0;
        return $atts['format'] === 'true' ? number_format_i18n($total) : (string) $total;
    }

    public function model_count($atts) {
        $atts = shortcode_atts(['format' => 'true'], $atts, 'tmw_model_count');
        $count = wp_count_posts('model');
        $total = isset($count->publish) ? (int) $count->publish : 0;
        return $atts['format'] === 'true' ? number_format_i18n($total) : (string) $total;
    }

    public function current_year() {
        return date('Y');
    }
}
