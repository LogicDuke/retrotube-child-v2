<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detect whether the current request is for a login/registration screen.
 */
function tmw_perf_is_login_page(): bool {
    if (!isset($GLOBALS['pagenow'])) {
        return false;
    }

    return in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'], true);
}

/**
 * Determine whether frontend performance hooks should run for this request.
 */
function tmw_perf_should_run(): bool {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return false;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    if (tmw_perf_is_login_page()) {
        return false;
    }

    return true;
}

/**
 * Decide if third-party deferral should run on this request.
 */
function tmw_perf_should_delay_thirdparty(): bool {
    return tmw_perf_should_run() && is_singular('model');
}

/**
 * Log performance debug messages with consistent prefixes.
 */
function tmw_perf_debug_log(string $message): void {
    $normalized = preg_replace('/^\[TMW-PERF\]\s*/', '[PERF] ', $message);

    if (function_exists('tmw_debug_log')) {
        tmw_debug_log($normalized);
        return;
    }

    if (defined('TMW_DEBUG') && TMW_DEBUG) {
        error_log('[TMW] ' . $normalized);
    }
}

/**
 * Case-insensitive substring matcher for asset source URLs.
 *
 * @param string $src Source URL to check.
 * @param array  $needles Substrings to match.
 */
function tmw_perf_src_matches(string $src, array $needles): bool {
    foreach ($needles as $needle) {
        if (stripos($src, $needle) !== false) {
            return true;
        }
    }

    return false;
}

add_action('wp_enqueue_scripts', function () {
    if (!tmw_perf_should_run() || is_singular('video')) {
        return;
    }

    $did_dequeue = false;

    global $wp_scripts;
    if ($wp_scripts instanceof WP_Scripts && !empty($wp_scripts->queue)) {
        foreach ($wp_scripts->queue as $handle) {
            if (!isset($wp_scripts->registered[$handle])) {
                continue;
            }

            $src = (string) $wp_scripts->registered[$handle]->src;
            if ($src && tmw_perf_src_matches($src, ['vjs.zencdn.net', 'video.min.js'])) {
                wp_dequeue_script($handle);
                $did_dequeue = true;
            }
        }
    }

    global $wp_styles;
    if ($wp_styles instanceof WP_Styles && !empty($wp_styles->queue)) {
        foreach ($wp_styles->queue as $handle) {
            if (!isset($wp_styles->registered[$handle])) {
                continue;
            }

            $src = (string) $wp_styles->registered[$handle]->src;
            if ($src && tmw_perf_src_matches($src, ['vjs.zencdn.net', 'video-js.css'])) {
                wp_dequeue_style($handle);
                $did_dequeue = true;
            }
        }
    }

    if ($did_dequeue) {
        $path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        tmw_perf_debug_log('[TMW-PERF] Dequeued VideoJS on non-video page: ' . esc_url_raw($path));
    }
}, 999);

add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (!tmw_perf_should_delay_thirdparty()) {
        return $tag;
    }

    $targets = [
        'googletagmanager.com/gtag/js',
        'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js',
        'connect.facebook.net',
    ];

    if (!tmw_perf_src_matches($src, $targets)) {
        return $tag;
    }

    if (stripos($src, 'connect.facebook.net') !== false && stripos($src, '/sdk.js') === false) {
        return $tag;
    }

    $attrs = '';
    if (preg_match('/\scrossorigin(=(["\"]).*?\2)?/i', $tag, $match)) {
        $attrs .= ' ' . trim($match[0]);
    }
    if (preg_match('/\sreferrerpolicy=(["\"]).*?\1/i', $tag, $match)) {
        $attrs .= ' ' . trim($match[0]);
    }
    if (preg_match('/\snonce=(["\"]).*?\1/i', $tag, $match)) {
        $attrs .= ' ' . trim($match[0]);
    }
    if (preg_match('/\sintegrity=(["\"]).*?\1/i', $tag, $match)) {
        $attrs .= ' ' . trim($match[0]);
    }

    tmw_perf_debug_log('[TMW-PERF] Deferred 3P script: ' . esc_url_raw($src));

    return sprintf(
        '<script type="text/tmw-deferred" data-src="%s"%s></script>',
        esc_url($src),
        $attrs
    );
}, 10, 3);

add_action('wp_enqueue_scripts', function () {
    if (!tmw_perf_should_delay_thirdparty()) {
        return;
    }

    $path = get_stylesheet_directory() . '/js/tmw-thirdparty-delay.js';
    if (!file_exists($path)) {
        tmw_perf_debug_log('[TMW-PERF] Missing loader: js/tmw-thirdparty-delay.js');
        return;
    }

    $filemtime = filemtime($path);
    if ($filemtime === false) {
        tmw_perf_debug_log('[TMW-PERF] Unable to read loader mtime: js/tmw-thirdparty-delay.js');
        $version = null;
    } else {
        $version = (string) $filemtime;
    }

    wp_enqueue_script(
        'tmw-thirdparty-delay',
        get_stylesheet_directory_uri() . '/js/tmw-thirdparty-delay.js',
        [],
        $version,
        true
    );
}, 20);
