<?php
/**
 * Performance optimizations.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Performance;

defined('ABSPATH') || exit;

class Optimizer {

    public function __construct() {
        add_action('init', [$this, 'disable_emojis']);
        add_action('wp_enqueue_scripts', [$this, 'optimize_scripts'], 999);
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_lazy_loading']);
        add_action('wp_head', [$this, 'preconnect'], 1);
        add_action('init', [$this, 'clean_head']);
        add_action('init', [$this, 'optimize_heartbeat']);
    }

    public function disable_emojis() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', function ($plugins) {
            return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
        });
    }

    public function optimize_scripts() {
        if (!is_admin()) {
            if (!is_singular() || !comments_open()) {
                wp_dequeue_script('comment-reply');
            }
        }
    }

    public function add_lazy_loading($attr) {
        if (!isset($attr['loading'])) $attr['loading'] = 'lazy';
        if (!isset($attr['decoding'])) $attr['decoding'] = 'async';
        return $attr;
    }

    public function preconnect() {
        $domains = ['https://fonts.googleapis.com', 'https://fonts.gstatic.com'];
        foreach ($domains as $domain) {
            printf('<link rel="preconnect" href="%s" crossorigin>' . "\n", esc_url($domain));
        }
    }

    public function clean_head() {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_generator');
    }

    public function optimize_heartbeat() {
        if (!is_admin()) {
            add_action('init', function () {
                wp_deregister_script('heartbeat');
            }, 1);
        }
        add_filter('heartbeat_settings', function ($settings) {
            $settings['interval'] = 60;
            return $settings;
        });
    }

    public static function get_load_time() {
        return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    }

    public static function get_memory_usage() {
        return size_format(memory_get_peak_usage(true));
    }
}
