<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Determine whether the performance buffer rewrite should run.
 */
function tmw_child_perf_buffer_should_run(): bool {
    if (is_admin() || is_user_logged_in() || is_preview()) {
        return false;
    }

    return function_exists('tmw_child_is_heavy_media_view') && tmw_child_is_heavy_media_view();
}

/**
 * Start the output buffer rewrite for inline script tags.
 */
function tmw_child_perf_buffer_start(): void {
    if (!tmw_child_perf_buffer_should_run()) {
        return;
    }

    static $started = false;
    if ($started) {
        return;
    }

    $started = true;
    $GLOBALS['tmw_perf_buffer_counts'] = [
        'gtag' => 0,
        'ads' => 0,
        'fb' => 0,
        'cf' => 0,
        'gtranslate' => 0,
        'vjs' => 0,
        'quality' => 0,
        'bxslider' => 0,
    ];

    ob_start('tmw_child_perf_buffer_rewrite');

    register_shutdown_function(function () {
        if (!defined('TMW_DEBUG') || !TMW_DEBUG) {
            return;
        }
        if (empty($GLOBALS['tmw_perf_buffer_counts'])) {
            return;
        }
        $counts = $GLOBALS['tmw_perf_buffer_counts'];
        $total = array_sum($counts);
        if ($total === 0) {
            return;
        }
        error_log(sprintf(
            '[TMW-PERF-BUFFER] Rewrote scripts: gtag=%d ads=%d fb=%d cf=%d gtranslate=%d vjs=%d quality=%d bxslider=%d.',
            $counts['gtag'],
            $counts['ads'],
            $counts['fb'],
            $counts['cf'],
            $counts['gtranslate'],
            $counts['vjs'],
            $counts['quality'],
            $counts['bxslider']
        ));
    });
}
add_action('template_redirect', 'tmw_child_perf_buffer_start', 0);

/**
 * Rewrite matching script tags in the buffered HTML output.
 */
function tmw_child_perf_buffer_rewrite(string $html): string {
    if (stripos($html, '<script') === false) {
        return $html;
    }

    $pattern = '/<script\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1[^>]*>\s*<\/script>/is';
    return preg_replace_callback($pattern, function ($matches) {
        $tag = $matches[0];
        $src = $matches[2] ?? '';

        if ($src === '' || stripos($tag, 'data-tmw-delay') !== false) {
            return $tag;
        }

        $lower = strtolower($src);
        $key = null;

        if (strpos($lower, 'www.googletagmanager.com/gtag/js') !== false) {
            $key = 'gtag';
        } elseif (strpos($lower, 'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js') !== false) {
            $key = 'ads';
        } elseif (strpos($lower, 'connect.facebook.net/') !== false && strpos($lower, '/sdk.js') !== false) {
            $key = 'fb';
        } elseif (strpos($lower, 'static.cloudflareinsights.com/beacon.min.js') !== false) {
            $key = 'cf';
        } elseif (strpos($lower, 'cdn.gtranslate.net/widgets/latest/dropdown.js') !== false) {
            $key = 'gtranslate';
        } elseif (strpos($lower, 'vjs.zencdn.net/') !== false && strpos($lower, '/video.min.js') !== false) {
            $key = 'vjs';
        } elseif (strpos($lower, 'unpkg.com/@silvermine/videojs-quality-selector') !== false) {
            $key = 'quality';
        } elseif (strpos($lower, '/assets/js/jquery.bxslider.min.js') !== false) {
            $key = 'bxslider';
        }

        if ($key === null) {
            return $tag;
        }

        $GLOBALS['tmw_perf_buffer_counts'][$key]++;

        $flags = [
            'async' => preg_match('/\basync\b/i', $tag) === 1,
            'defer' => preg_match('/\bdefer\b/i', $tag) === 1,
        ];

        return tmw_child_perf_buffer_build_tag($tag, $src, $flags);
    }, $html);
}

/**
 * Convert a script tag to a delayed loader placeholder.
 */
function tmw_child_perf_buffer_build_tag(string $tag, string $src, array $flags): string {
    $tag = preg_replace('/\s+src\s*=\s*(["\']).*?\1/i', '', $tag);
    $tag = preg_replace('/\s+\basync\b/i', '', $tag);
    $tag = preg_replace('/\s+\bdefer\b/i', '', $tag);

    $data = ' data-tmw-delay="1" data-src="' . esc_url($src) . '"';
    if (!empty($flags['async'])) {
        $data .= ' data-async="1"';
    }
    if (!empty($flags['defer'])) {
        $data .= ' data-defer="1"';
    }

    if (preg_match('/<script\b[^>]*>/i', $tag, $open_match)) {
        $open = $open_match[0];
        if (preg_match('/\btype\s*=/i', $open)) {
            $open_updated = preg_replace('/\btype\s*=\s*(["\']).*?\1/i', 'type="text/plain"', $open, 1);
        } else {
            $open_updated = rtrim(substr($open, 0, -1)) . ' type="text/plain">';
        }
        $open_updated = rtrim(substr($open_updated, 0, -1)) . $data . '>';
        $tag = str_replace($open, $open_updated, $tag);
    }

    return $tag;
}
