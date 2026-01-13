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

    // Step 1: Check if enabled
    $enabled = get_post_meta($post_id, '_tmw_slot_enabled', true);
    if ($enabled !== '1') {
        if ($debug) {
            error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . " SKIP: not enabled (value='" . $enabled . "')");
        }
        return '';
    }

    $mode = get_post_meta($post_id, '_tmw_slot_mode', true);
    $shortcode = trim(get_post_meta($post_id, '_tmw_slot_shortcode', true));

    if ($debug) {
        error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . " enabled=1 mode='" . $mode . "' shortcode='" . $shortcode . "'");
    }

    // Step 2: Try the configured shortcode first
    if ($shortcode !== '') {
        $out = do_shortcode($shortcode);
        if (strlen(trim($out)) > 0) {
            if ($debug) {
                error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . ' SUCCESS via configured shortcode, len=' . strlen($out));
            }
            return '<div class="tmw-slot-banner-zone"><div class="tmw-slot-banner">' . $out . '</div></div>';
        }
        if ($debug) {
            error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . ' configured shortcode returned empty');
        }
    }

    // Step 3: Try default [tmw_slot_machine] shortcode
    if (shortcode_exists('tmw_slot_machine')) {
        $out = do_shortcode('[tmw_slot_machine]');
        if (strlen(trim($out)) > 0) {
            if ($debug) {
                error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . ' SUCCESS via default shortcode, len=' . strlen($out));
            }
            return '<div class="tmw-slot-banner-zone"><div class="tmw-slot-banner">' . $out . '</div></div>';
        }
        if ($debug) {
            error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . ' default shortcode returned empty');
        }
    } else {
        if ($debug) {
            error_log("[TMW-SLOT-RENDER] post_id=$post_id shortcode 'tmw_slot_machine' NOT REGISTERED");
        }
    }

    // Step 4: Try widget area
    if (is_active_sidebar('tmw-model-slot-banner-global')) {
        ob_start();
        dynamic_sidebar('tmw-model-slot-banner-global');
        $out = ob_get_clean();
        if (strlen(trim($out)) > 0) {
            if ($debug) {
                error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . ' SUCCESS via widget area, len=' . strlen($out));
            }
            return '<div class="tmw-slot-banner-zone"><div class="tmw-slot-banner">' . $out . '</div></div>';
        }
        if ($debug) {
            error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . ' widget area returned empty');
        }
    } else {
        if ($debug) {
            error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . ' widget area not active');
        }
    }

    if ($debug) {
        error_log('[TMW-SLOT-RENDER] post_id=' . $post_id . ' ALL SOURCES EXHAUSTED - returning empty');
    }
    return '';
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
