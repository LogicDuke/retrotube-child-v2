<?php
if (!defined('ABSPATH')) { exit; }

// Bridge: keep legacy flipbox shortcodes and helpers until refactored.
$flipboxes_legacy = TMW_CHILD_PATH . '/inc/tmw-video-hooks.php';
if (is_readable($flipboxes_legacy)) {
    require_once $flipboxes_legacy;
}

add_action('init', function () {
    if (get_option('show_on_front') !== 'page') {
        return;
    }

    $front_id = (int) get_option('page_on_front');
    if (!$front_id) {
        return;
    }

    if (!is_page_template('page-models-grid.php', $front_id)) {
        return;
    }

    add_rewrite_rule(
        '^page/([0-9]+)/?$',
        'index.php?page_id=' . $front_id . '&paged=$matches[1]',
        'top'
    );
}, 1);

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option('show_on_front') !== 'page') {
        return;
    }

    $front_id = (int) get_option('page_on_front');
    if (!$front_id) {
        return;
    }

    if (!is_page_template('page-models-grid.php', $front_id)) {
        return;
    }

    $option_key = 'tmw_rewrite_frontpage_paged_v1';
    if (get_option($option_key)) {
        return;
    }

    flush_rewrite_rules(false);
    update_option($option_key, time());
});

add_action('template_redirect', function () {
    if (!get_option('permalink_structure')) {
        return;
    }

    if (!is_front_page() && !is_page_template('page-models-grid.php')) {
        return;
    }

    if (!isset($_GET['pg'])) {
        return;
    }

    $n = (int) $_GET['pg'];
    if ($n < 1) {
        return;
    }

    $target = get_pagenum_link($n);
    $clean = remove_query_arg('pg', $target);
    wp_safe_redirect($clean, 301);
    exit;
});

add_filter('redirect_canonical', function ($redirect_url, $requested_url) {
    $paged = (int) get_query_var('paged');
    if ($paged > 1) {
        $front_id = (int) get_option('page_on_front');
        $queried_id = (int) get_queried_object_id();
        if (is_front_page() || ($front_id && $queried_id === $front_id)) {
            return false;
        }
    }

    return $redirect_url;
}, 1, 2);

if (!function_exists('tmw_get_flipbox_link_guard')) {
    /**
     * Returns a flipbox link guard closure used by archive + template views.
     *
     * @param array $args {
     *   Optional arguments.
     *
     *   @type bool $disable_mobile When true, mobile requests disable the link entirely.
     * }
     *
     * @return callable
     */
    function tmw_get_flipbox_link_guard(array $args = []): callable {
        $disable_mobile = !empty($args['disable_mobile']);

        return static function ($link, $term) use ($disable_mobile) {
            if ($disable_mobile && function_exists('wp_is_mobile') && wp_is_mobile()) {
                return false;
            }

            $home_url = trailingslashit(home_url('/'));
            $current  = is_string($link) ? trailingslashit($link) : '';

            if ($current && $current !== $home_url) {
                return $link;
            }

            if (function_exists('tmw_get_model_post_for_term')) {
                $post = tmw_get_model_post_for_term($term);
                if ($post instanceof WP_Post) {
                    $post_link = get_permalink($post);
                    if ($post_link) {
                        return $post_link;
                    }
                }
            }

            if (!$current || $current === $home_url) {
                $term_obj = $term;
                if (is_numeric($term)) {
                    $term_obj = get_term((int) $term, 'models');
                }

                if ($term_obj && !is_wp_error($term_obj)) {
                    $term_link = get_term_link($term_obj);
                    if (!is_wp_error($term_link) && $term_link) {
                        return $term_link;
                    }
                }
            }

            return $link;
        };
    }
}

/**
 * Filter: Disable flipbox links on mobile to prevent accidental navigation.
 */
function tmw_flipbox_link_guard_filter($link, $term_id) {
    if (wp_is_mobile()) {
        return false;
    }
    return $link;
}
