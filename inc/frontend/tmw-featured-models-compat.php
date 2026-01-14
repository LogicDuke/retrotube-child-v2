<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tmw_should_output_featured_block')) {
    function tmw_should_output_featured_block() {
        $excluded = [
            '18-u-s-c-2257',
            'dmca',
            'privacy-policy-top-models-webcam',
            'terms-of-use-of-top-models-webcam-directory',
            'submit-a-video',
        ];

        if (function_exists('is_page') && is_page($excluded)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('tmw_featured_block_markup')) {
    function tmw_featured_block_markup() {
        if (function_exists('tmw_featured_models_render_block')) {
            return tmw_featured_models_render_block();
        }

        return '';
    }
}

if (!function_exists('tmw_featured_block_output_buffer_start')) {
    function tmw_featured_block_output_buffer_start() {
        return;
    }
}

if (!function_exists('tmw_featured_block_inject_into_main')) {
    function tmw_featured_block_inject_into_main() {
        return;
    }
}

if (!function_exists('tmw_featured_block_output_buffer_shutdown')) {
    function tmw_featured_block_output_buffer_shutdown() {
        return;
    }
}

if (!function_exists('tmw_featured_block_dedup')) {
    function tmw_featured_block_dedup() {
        return true;
    }
}
