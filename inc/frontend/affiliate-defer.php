<?php
if (!defined('ABSPATH')) { exit; }

function tmw_affiliate_is_livejasmin_src(string $src): bool {
    $src = strtolower($src);
    return strpos($src, 'livejasmin') !== false;
}

add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (is_admin() || !is_string($src) || $src === '') {
        return $tag;
    }

    if (!tmw_affiliate_is_livejasmin_src($src)) {
        return $tag;
    }

    static $logged = false;
    if (!$logged && defined('TMW_DEBUG') && TMW_DEBUG) {
        error_log('[TMW-AFFILIATE-DEFER] LiveJasmin script deferred.');
        $logged = true;
    }

    return '<script type="text/plain" data-tmw-affiliate-src="' . esc_url($src) . '" data-tmw-affiliate="livejasmin"></script>';
}, 20, 3);
