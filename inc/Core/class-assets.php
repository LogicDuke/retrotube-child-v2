<?php
/**
 * Assets management - enqueue scripts and styles.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Core;

defined('ABSPATH') || exit;

class Assets {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
        add_action('wp_head', [$this, 'preload_critical'], 1);
    }

    public function enqueue_frontend() {
        $version = Theme::VERSION;
        $url = get_stylesheet_directory_uri();

        // Parent theme styles
        wp_enqueue_style('retrotube-child', get_stylesheet_uri(), ['retrotube-style'], $version);

        // Child theme CSS
        wp_enqueue_style('tmw-main', $url . '/assets/css/main.css', ['retrotube-child'], $version);
        wp_enqueue_style('tmw-a11y', $url . '/assets/css/a11y.css', [], $version);

        // Accordion JS
        wp_enqueue_script('tmw-accordion', $url . '/assets/js/accordion.js', [], $version, true);

        // Voting JS on single posts/models
        if (is_singular(['post', 'video', 'model'])) {
            wp_enqueue_script('tmw-voting', $url . '/assets/js/voting.js', ['jquery'], $version, true);
            wp_localize_script('tmw-voting', 'tmwVoting', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('tmw_voting_nonce'),
            ]);
        }

        // Flipboxes where needed
        if ($this->needs_flipboxes()) {
            wp_enqueue_style('tmw-flipboxes', $url . '/assets/css/flipboxes.css', [], $version);
            wp_enqueue_script('tmw-flipboxes', $url . '/assets/js/flipboxes.js', [], $version, true);
        }
    }

    public function enqueue_admin() {
        $screen = get_current_screen();
        if (!$screen) return;

        $url = get_stylesheet_directory_uri();
        $version = Theme::VERSION;

        if (in_array($screen->post_type, ['model', 'category_page'], true)) {
            wp_enqueue_style('tmw-admin-banner', $url . '/assets/css/admin-banner.css', [], $version);
            wp_enqueue_script('tmw-admin-banner', $url . '/assets/js/admin-banner.js', ['jquery'], $version, true);
        }
    }

    public function preload_critical() {
        if (is_singular('model')) {
            $banner = tmw_get_model_banner_url(get_the_ID());
            if ($banner) {
                printf('<link rel="preload" as="image" href="%s" fetchpriority="high">', esc_url($banner));
            }
        }
    }

    private function needs_flipboxes() {
        return is_singular('model') || is_post_type_archive('model') || is_front_page();
    }
}
