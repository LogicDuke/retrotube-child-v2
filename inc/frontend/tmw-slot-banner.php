<?php
/**
 * TMW Slot Banner Frontend Renderer - Bulletproof Version
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register widget area
add_action('widgets_init', function () {
    register_sidebar([
        'id'            => 'tmw-model-slot-banner-global',
        'name'          => __('Model Page – Slot Banner (Global)', 'retrotube-child'),
        'description'   => __('Fallback slot banner for model pages.', 'retrotube-child'),
        'before_widget' => '',
        'after_widget'  => '',
        'before_title'  => '',
        'after_title'   => '',
    ]);
});

/**
 * BULLETPROOF renderer - tries ALL sources until one works
 */
function tmw_render_model_slot_banner_zone(int $post_id): string
{
    $debug = defined('TMW_DEBUG') && TMW_DEBUG;

    $enabled = get_post_meta($post_id, '_tmw_slot_enabled', true);
    if ($enabled !== '1') {
        return '';
    }

    $mode = get_post_meta($post_id, '_tmw_slot_mode', true);
    if (!in_array($mode, ['widget', 'shortcode'], true)) {
        $mode = 'shortcode';
    }

    $shortcode = trim(get_post_meta($post_id, '_tmw_slot_shortcode', true));
    $source = '';
    $out = '';

    if ($mode === 'widget') {
        ob_start();
        dynamic_sidebar('tmw-model-slot-banner-global');
        $out = trim(ob_get_clean());

        if ($out === '' && shortcode_exists('tmw_slot_machine')) {
            $out = trim(do_shortcode('[tmw_slot_machine]'));
            $source = $out !== '' ? 'fallback' : '';
        } elseif ($out !== '') {
            $source = 'widget';
        }
    } else {
        if ($shortcode === '' && shortcode_exists('tmw_slot_machine')) {
            $shortcode = '[tmw_slot_machine]';
            $source = 'fallback';
        }

        if ($shortcode !== '') {
            $out = trim(do_shortcode($shortcode));
            if ($out !== '' && $source === '') {
                $source = 'shortcode';
            }
        }
    }

    if ($debug) {
        error_log('[TMW-SLOT] model_id=' . $post_id . ' enabled=yes mode=' . $mode . ' source=' . ($source !== '' ? $source : 'none') . ' output_len=' . strlen($out));
    }

    if ($out === '') {
        return '';
    }

    return '<div class="tmw-slot-banner-zone"><div class="tmw-slot-banner">' . $out . '</div></div>';
}

// Backwards compatibility alias
function tmw_render_model_slot_banner(int $post_id): string
{
    return tmw_render_model_slot_banner_zone($post_id);
}

// Helper functions
function tmw_model_slot_is_enabled(int $post_id): bool
{
    return get_post_meta($post_id, '_tmw_slot_enabled', true) === '1';
}

function tmw_model_slot_get_mode(int $post_id): string
{
    $mode = get_post_meta($post_id, '_tmw_slot_mode', true);
    return in_array($mode, ['widget', 'shortcode']) ? $mode : 'shortcode';
}

function tmw_model_slot_get_shortcode(int $post_id): string
{
    return trim(get_post_meta($post_id, '_tmw_slot_shortcode', true));
}
