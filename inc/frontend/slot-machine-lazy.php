<?php
if (!defined('ABSPATH')) { exit; }

function tmw_slot_machine_should_lazy(): bool {
    return !is_admin();
}

add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (!tmw_slot_machine_should_lazy()) {
        return $tag;
    }

    if (stripos((string) $handle, 'slot') === false) {
        return $tag;
    }

    if (!is_string($src) || $src === '') {
        return $tag;
    }

    return '<script type="text/plain" data-tmw-slot-src="' . esc_url($src) . '" data-tmw-slot-handle="' . esc_attr($handle) . '"></script>';
}, 15, 3);

add_filter('style_loader_tag', function ($html, $handle, $href, $media) {
    if (!tmw_slot_machine_should_lazy()) {
        return $html;
    }

    if (stripos((string) $handle, 'slot') === false) {
        return $html;
    }

    if (!is_string($href) || $href === '') {
        return $html;
    }

    $media_attr = $media ?: 'all';

    return '<link rel="stylesheet" data-tmw-slot-href="' . esc_url($href) . '" data-tmw-slot-handle="' . esc_attr($handle) . '" media="' . esc_attr($media_attr) . '">';
}, 15, 4);
