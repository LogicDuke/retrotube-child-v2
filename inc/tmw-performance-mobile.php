<?php
if (!defined('ABSPATH')) { exit; }

function tmw_perf_mobile_is_target_request() {
    return !is_admin() && !is_user_logged_in() && is_singular('model');
}

function tmw_perf_mobile_log($message) {
    if (defined('TMW_DEBUG') && TMW_DEBUG) {
        error_log($message);
    }
}

function tmw_perf_mobile_should_async_css($href) {
    if (empty($href)) {
        return false;
    }

    $href = (string) $href;
    $theme_urls = array(
        get_stylesheet_directory_uri(),
        get_template_directory_uri(),
    );

    foreach ($theme_urls as $theme_url) {
        if ($theme_url && strpos($href, $theme_url) !== false) {
            return false;
        }
    }

    if (strpos($href, '/wp-content/cache/autoptimize/css/autoptimize_single_') !== false) {
        return true;
    }

    if (strpos($href, '/wp-content/plugins/') === false) {
        return false;
    }

    $keywords = array(
        'cookie',
        'consent',
        'gdpr',
        'gtranslate',
        'translate',
        'slot',
        'popup',
        'optin',
    );

    foreach ($keywords as $keyword) {
        if (strpos($href, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

function tmw_perf_mobile_should_delay_src($src) {
    if (empty($src)) {
        return false;
    }

    $patterns = array(
        '#googletagmanager\.com/gtag/js#i',
        '#pagead2\.googlesyndication\.com/pagead/js/adsbygoogle\.js#i',
        '#connect\.facebook\.net/.*/sdk\.js#i',
        '#static\.cloudflareinsights\.com/#i',
        '#cdn\.gtranslate\.net/#i',
        '#vjs\.zencdn\.net/#i',
        '#jquery\.bxslider\.min\.js#i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $src)) {
            return true;
        }
    }

    return false;
}

add_filter('style_loader_tag', function ($html, $handle, $href, $media) {
    if (!tmw_perf_mobile_is_target_request()) {
        return $html;
    }

    if (!tmw_perf_mobile_should_async_css($href)) {
        return $html;
    }

    static $logged = false;
    if (!$logged) {
        tmw_perf_mobile_log('[TMW-PERF] Async CSS enabled on model page');
        $logged = true;
    }

    $media_attr = ($media && $media !== 'all') ? ' media="' . esc_attr($media) . '"' : '';
    $href_attr = esc_url($href);

    $preload = '<link rel="preload" as="style" href="' . $href_attr . '"' . $media_attr . ' onload="this.onload=null;this.rel=\'stylesheet\'">';
    $noscript = '<noscript><link rel="stylesheet" href="' . $href_attr . '"' . $media_attr . '></noscript>';

    return $preload . $noscript;
}, 10, 4);

add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (!tmw_perf_mobile_is_target_request()) {
        return $tag;
    }

    if (!tmw_perf_mobile_should_delay_src($src)) {
        return $tag;
    }

    return '<script type="text/plain" data-tmw-delay="1" data-src="' . esc_url($src) . '"></script>';
}, 10, 3);

function tmw_perf_mobile_buffer_rewrite_scripts($buffer) {
    $count = 0;

    $buffer = preg_replace_callback(
        '#<script\b([^>]*?)\bsrc=(["\'])([^"\']+)\2([^>]*)>\s*</script>#i',
        function ($matches) use (&$count) {
            $attrs = $matches[1] . ' ' . $matches[4];
            if (stripos($attrs, 'data-tmw-delay') !== false) {
                return $matches[0];
            }
            if (stripos($attrs, 'type="text/plain"') !== false || stripos($attrs, "type='text/plain'") !== false) {
                return $matches[0];
            }

            $src = $matches[3];
            if (!tmw_perf_mobile_should_delay_src($src)) {
                return $matches[0];
            }

            $count++;
            return '<script type="text/plain" data-tmw-delay="1" data-src="' . esc_url($src) . '"></script>';
        },
        $buffer
    );

    if ($count > 0) {
        tmw_perf_mobile_log('[TMW-PERF] Buffer rewrote delayed scripts: ' . $count);
    }

    return $buffer;
}

add_action('template_redirect', function () {
    if (!tmw_perf_mobile_is_target_request()) {
        return;
    }

    ob_start('tmw_perf_mobile_buffer_rewrite_scripts');
}, 0);

add_action('wp_enqueue_scripts', function () {
    if (!tmw_perf_mobile_is_target_request()) {
        return;
    }

    $src = get_stylesheet_directory_uri() . '/js/tmw-delay-loader.js';
    wp_enqueue_script('tmw-delay-loader', $src, array(), TMW_CHILD_VERSION, true);
}, 20);
