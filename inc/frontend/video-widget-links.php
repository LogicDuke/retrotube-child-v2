<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('wpst_WP_Widget_Videos_Block')) {
    return;
}

add_filter('widget_display_callback', function ($instance, $widget, $args) {
    if (!$widget instanceof wpst_WP_Widget_Videos_Block) {
        return $instance;
    }

    static $is_rewriting = false;
    if ($is_rewriting) {
        return $instance;
    }

    $is_rewriting = true;

    ob_start();
    $widget->widget($args, $instance);
    $output = ob_get_clean();

    $host = preg_quote(wp_parse_url(home_url(), PHP_URL_HOST), '#');
    $pattern = '#(href=["\'])(?:https?://' . $host . ')?/\?filter=([^"\']+)#i';
    $updated = preg_replace($pattern, '$1' . home_url('/videos/?filter=') . '$2', $output);

    if (defined('TMW_DEBUG') && TMW_DEBUG && $updated !== $output) {
        error_log('[TMW-VIDEO-LINKS] rewritten widget links to /videos/');
    }

    echo $updated;
    $is_rewriting = false;

    return false;
}, 10, 3);
