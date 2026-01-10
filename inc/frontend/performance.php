<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Front-end performance trims scoped primarily to the homepage.
 */

/**
 * Inline critical CSS for heavy media views to speed up banner/LCP.
 */
add_action('wp_head', function () {
    if (is_admin() || is_user_logged_in()) {
        return;
    }

    if (!is_singular('model')) {
        return;
    }
    ?>
    <style id="tmw-critical-css">
    /* Critical above-the-fold styles for model pages */
    *,*::before,*::after{box-sizing:border-box}
    body{margin:0}
    img,video{max-width:100%;height:auto}
    .tmw-banner-container,.tmw-banner-frame{
        position:relative;width:100%;max-width:1035px;
        aspect-ratio:1035/350;overflow:hidden;background:#111;
        margin:0 auto 25px;border-radius:8px
    }
    .tmw-banner-frame img{
        display:block;width:100%!important;height:100%!important;
        object-fit:cover!important
    }
    .entry-header{contain:layout style}
    .title-block{background:#1a1a1a;padding:15px;border-radius:8px}
    h1.entry-title{margin:0;color:#fff;font-size:clamp(1.5rem,4vw,2rem)}
    </style>
    <?php
}, 1);

add_action('wp_head', function () {
    if (is_admin() || is_user_logged_in() || !tmw_child_is_heavy_media_view()) {
        return;
    }

    if (defined('TMW_DEBUG') && TMW_DEBUG) {
        error_log('[TMW-PERF] Inline critical CSS active.');
    }
    ?>
    <style>
    #masthead,
    .site-header{background:#111;color:#fff}
    .site-header .site-branding{display:flex;align-items:center;gap:12px}
    .main-navigation{display:block}
    .tmw-model-hero{margin:0 0 18px;position:relative}
    .tmw-banner-container,
    .tmw-banner-frame{position:relative;width:100%;max-width:1035px;aspect-ratio:1035/350;overflow:hidden;background:#000;margin:0 auto 25px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.5)}
    .tmw-banner-frame img,
    .tmw-banner-frame picture>img,
    .tmw-banner-frame .wp-post-image{display:block;width:100%!important;height:100%!important;object-fit:cover!important;object-position:50% 50%}
    </style>
    <?php
}, 2);

/**
 * Remove jQuery migrate unless explicitly required.
 */
add_action('wp_default_scripts', function ($scripts) {
    if (is_admin() || !($scripts instanceof WP_Scripts)) {
        return;
    }

    if (!tmw_child_is_heavy_media_view()) {
        return;
    }

    if (!apply_filters('tmw_perf_remove_jquery_migrate', true)) {
        return;
    }

    if (!empty($scripts->registered['jquery'])) {
        $scripts->registered['jquery']->deps = array_diff(
            (array) $scripts->registered['jquery']->deps,
            ['jquery-migrate']
        );
    }
});

function tmw_child_is_heavy_media_view(): bool {
    return is_front_page()
        || is_singular('model')
        || is_post_type_archive('model')
        || is_tax('models')
        || is_page_template('page-models-grid.php')
        || is_page_template('template-models-flipboxes.php')
        || is_page_template('page-videos.php')
        || is_singular('video');
}

/**
 * Dequeue non-critical styles on heavy media views.
 */
add_action('wp_enqueue_scripts', function () {
    if (is_admin() || is_user_logged_in()) {
        return;
    }

    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');

    if (wp_script_is('wp-embed', 'enqueued')) {
        wp_dequeue_script('wp-embed');
    }

    if (wp_style_is('dashicons', 'enqueued')) {
        wp_dequeue_style('dashicons');
        wp_deregister_style('dashicons');
    }
}, 5);

add_action('wp_enqueue_scripts', function () {
    if (!tmw_child_is_heavy_media_view()) {
        return;
    }

    if (is_front_page()) {
        $tml_handles = ['theme-my-login', 'tml', 'theme-my-login-widget'];
        foreach ($tml_handles as $handle) {
            if (wp_style_is($handle, 'enqueued')) {
                wp_dequeue_style($handle);
            }
        }
    }

    $fancybox_handles = ['jquery-fancybox', 'fancybox', 'fancybox-css'];
    foreach ($fancybox_handles as $handle) {
        if (wp_style_is($handle, 'enqueued')) {
            wp_dequeue_style($handle);
        }
    }
}, 99);

/**
 * Delay non-critical styles on heavy media views without changing final appearance.
 */
add_filter('style_loader_tag', function ($html, $handle, $href, $media) {
    if (is_admin() || is_user_logged_in() || !tmw_child_is_heavy_media_view()) {
        return $html;
    }

    $critical_handles = [
        'retrotube-parent',
        'retrotube-child-style',
        'rt-child-flip',
    ];

    if (in_array($handle, $critical_handles, true)) {
        return $html;
    }

    $matched = false;

    if (strpos($href, '/wp-content/cache/autoptimize/css/autoptimize_single_') !== false) {
        $matched = true;
    }

    if (!$matched && strpos($href, '/wp-content/plugins/wps-cookie-consent/') !== false) {
        $matched = strpos($href, 'cookie-consent.css') !== false;
    }

    if (!$matched && strpos($href, '/wp-content/plugins/tmw-slot-machine/') !== false) {
        $matched = strpos($href, 'slot-machine.css') !== false;
    }

    if (!$matched) {
        return $html;
    }

    static $delay_count = 0;
    static $log_hooked = false;
    $delay_count++;

    if (!$log_hooked) {
        $log_hooked = true;
        add_action('wp_footer', function () use (&$delay_count) {
            if ($delay_count > 0) {
                if (defined('TMW_DEBUG') && TMW_DEBUG) {
                    error_log(sprintf('[TMW-PERF] Async CSS applied: %d.', $delay_count));
                }
            }
        }, 999);
    }

    $media_attr = $media ?: 'all';
    $escaped_href = esc_url($href);
    $escaped_id = esc_attr($handle) . '-css';

    return '<link rel="preload" as="style" id="' . $escaped_id . '" href="' . $escaped_href . '" media="' . esc_attr($media_attr) . '" onload="this.onload=null;this.rel=\'stylesheet\'">'
        . '<noscript><link rel="stylesheet" id="' . $escaped_id . '" href="' . $escaped_href . '" media="' . esc_attr($media_attr) . '"></noscript>';
}, 20, 4);

/**
 * Force font-display: swap on all Google Fonts and local fonts.
 */
add_filter('style_loader_tag', function ($html, $handle, $href) {
    if (strpos($href, 'fonts.googleapis.com') !== false && strpos($href, 'display=') === false) {
        $updated_href = add_query_arg('display', 'swap', $href);
        $html = str_replace($href, $updated_href, $html);
    }
    return $html;
}, 10, 3);

/**
 * Defer non-critical stylesheets.
 */
add_filter('style_loader_tag', function ($html, $handle, $href, $media) {
    if (is_admin() || is_user_logged_in() || !tmw_child_is_heavy_media_view()) {
        return $html;
    }

    $critical = [
        'retrotube-parent',
        'retrotube-child-style',
    ];

    if (in_array($handle, $critical, true)) {
        return $html;
    }

    if (strpos($html, 'rel="preload"') !== false) {
        return $html;
    }

    return sprintf(
        '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" media="%s"><noscript>%s</noscript>',
        esc_url($href),
        esc_attr($media ?: 'all'),
        $html
    );
}, 999, 4);

/**
 * Add defer to non-critical scripts and delay third-party tags until interaction.
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (is_admin() || is_user_logged_in() || is_preview()) {
        return $tag;
    }

    $defer_handles = [
        'jquery-bxslider',
        'bxslider',
        'jquery-fancybox',
        'fancybox',
        'jquery-touchSwipe',
        'jquery-touchswipe',
        'cookie-consent',
        'tmw-tml-links',
        'retrotube-main',
        'tmw-main-js',
        'videojs',
        'video-js',
        'videojs-quality',
        'videojs-quality-selector',
    ];

    if (in_array($handle, $defer_handles, true)) {
        return '<script src="' . esc_url($src) . '" defer></script>';
    }

    $host = parse_url($src, PHP_URL_HOST);
    $delay_hosts = [
        'www.googletagmanager.com',
        'pagead2.googlesyndication.com',
        'pagead2.g.doubleclick.net',
        'analytics.google.com',
        'static.cloudflareinsights.com',
        'cdn.gtranslate.net',
        'connect.facebook.net',
        'vk.com',
        'unpkg.com',
        'vjs.zencdn.net',
    ];

    if ($host && in_array($host, $delay_hosts, true)) {
        return '<script type="text/plain" data-tmw-delay="true" data-tmw-defer="true" data-src="' . esc_url($src) . '"></script>';
    }

    $is_videojs = stripos($handle, 'videojs') !== false || stripos($handle, 'video-js') !== false || $host === 'vjs.zencdn.net';

    if ($is_videojs) {
        return '<script src="' . esc_url($src) . '" defer></script>';
    }

    return $tag;
}, 10, 3);

/**
 * Convert third-party scripts to delayed loading.
 * They only load after first user interaction (scroll, click, touch).
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (is_admin() || is_user_logged_in()) {
        return $tag;
    }

    if (strpos($tag, 'text/lazyload') !== false || strpos($tag, 'data-tmw-delay') !== false) {
        return $tag;
    }

    $attrs = [];
    if (preg_match('/\scrossorigin(=(["\'])(.*?)\2)?/i', $tag, $match)) {
        $value = isset($match[3]) && $match[3] !== '' ? $match[3] : 'anonymous';
        $attrs['data-crossorigin'] = $value;
    }
    if (preg_match('/\sreferrerpolicy=(["\'])(.*?)\1/i', $tag, $match)) {
        $attrs['data-referrerpolicy'] = $match[2];
    }
    if (preg_match('/\snonce=(["\'])(.*?)\1/i', $tag, $match)) {
        $attrs['data-nonce'] = $match[2];
    }
    if (preg_match('/\sintegrity=(["\'])(.*?)\1/i', $tag, $match)) {
        $attrs['data-integrity'] = $match[2];
    }

    $delay_patterns = [
        'googletagmanager.com',
        'google-analytics.com',
        'analytics.google.com',
        'googlesyndication.com',
        'doubleclick.net',
        'connect.facebook.net',
        'facebook.com/tr',
        'cloudflareinsights.com',
        'gtranslate.net',
    ];

    foreach ($delay_patterns as $pattern) {
        if (stripos($src, $pattern) !== false) {
            $data_attrs = '';
            foreach ($attrs as $key => $value) {
                $data_attrs .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
            return sprintf(
                '<script type="text/lazyload" data-src="%s"%s></script>',
                esc_url($src),
                $data_attrs
            );
        }
    }

    return $tag;
}, 9999, 3);

add_action('wp_footer', function () {
    if (is_admin() || is_user_logged_in()) {
        return;
    }
    ?>
    <script>
    (function(){
        var loaded=false;
        function loadScripts(){
            if(loaded)return;
            loaded=true;
            document.querySelectorAll('script[type="text/lazyload"]').forEach(function(el){
                var s=document.createElement('script');
                s.src=el.getAttribute('data-src');
                s.async=true;
                var nonce = el.getAttribute('data-nonce');
                if (nonce) {
                    s.setAttribute('nonce', nonce);
                }
                var integrity = el.getAttribute('data-integrity');
                if (integrity) {
                    s.integrity = integrity;
                }
                var crossOrigin = el.getAttribute('data-crossorigin');
                if (crossOrigin) {
                    s.crossOrigin = crossOrigin;
                }
                var referrerPolicy = el.getAttribute('data-referrerpolicy');
                if (referrerPolicy) {
                    s.referrerPolicy = referrerPolicy;
                }
                el.parentNode.replaceChild(s,el);
            });
        }
        ['scroll','click','touchstart','mousemove','keydown'].forEach(function(e){
            window.addEventListener(e,loadScripts,{once:true,passive:true});
        });
        setTimeout(loadScripts,5000);
    })();
    </script>
    <?php
}, 999);

add_action('wp_footer', function () {
    ?>
    <script>
    (function () {
        var loaded = false;
        function loadDelayedScripts() {
            if (loaded) { return; }
            loaded = true;
            var delayed = Array.prototype.slice.call(document.querySelectorAll('script[data-tmw-delay]'));
            var total = delayed.length;
            var index = 0;

            function injectNext() {
                if (index >= delayed.length) {
                    if (typeof console !== 'undefined' && total > 0) {
                        console.log('[TMW-PERF-LAZY] Delayed scripts injected: ' + total + '.');
                    }
                    return;
                }
                var node = delayed[index++];
                var src = node.getAttribute('data-src');
                if (!src) {
                    injectNext();
                    return;
                }
                var s = document.createElement('script');
                s.src = src;
                s.async = node.getAttribute('data-async') === '1';
                if (node.getAttribute('data-defer') === '1' || node.getAttribute('data-tmw-defer') === 'true') {
                    s.defer = true;
                }
                s.onload = s.onerror = injectNext;
                node.parentNode.insertBefore(s, node.nextSibling);
            }

            injectNext();
        }

        function scheduleLoad() {
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(loadDelayedScripts, { timeout: 2000 });
            } else {
                window.setTimeout(loadDelayedScripts, 2000);
            }
        }

        ['scroll', 'pointerdown', 'click', 'touchstart', 'keydown'].forEach(function (eventName) {
            window.addEventListener(eventName, scheduleLoad, { once: true, passive: true });
        });

        window.addEventListener('load', scheduleLoad, { once: true });
        window.setTimeout(scheduleLoad, 2500);
    })();
    </script>
    <?php
});

/**
 * Utility: fetch image dimensions from attachment metadata or headers.
 */
function tmw_child_image_dimensions(string $url, int $fallback_width = 364, int $fallback_height = 546): array {
    $width  = null;
    $height = null;

    if ($url !== '') {
        if (!tmw_is_local_url($url)) {
            return [
                'width'  => $fallback_width,
                'height' => $fallback_height,
            ];
        }

        $attachment_id = tmw_get_attachment_id_cached($url);
        if ($attachment_id) {
            $meta = wp_get_attachment_metadata($attachment_id);
            if (is_array($meta)) {
                $width  = isset($meta['width']) ? (int) $meta['width'] : $width;
                $height = isset($meta['height']) ? (int) $meta['height'] : $height;
            }

            if (!$width || !$height) {
                $full = wp_get_attachment_image_src($attachment_id, 'full');
                if (is_array($full)) {
                    $width  = isset($full[1]) ? (int) $full[1] : $width;
                    $height = isset($full[2]) ? (int) $full[2] : $height;
                }
            }
        }
    }

    return [
        'width'  => $width ?: $fallback_width,
        'height' => $height ?: $fallback_height,
    ];
}

/**
 * Resolve the first front-page model image for preload/fetchpriority.
 */
function tmw_child_front_page_lcp_image(): array {
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $cache = [];

    if (!is_front_page()) {
        return $cache;
    }

    $terms = get_terms([
        'taxonomy'   => 'models',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'number'     => 1,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return $cache;
    }

    $term = $terms[0];
    $front_url = '';
    $back_url  = '';
    $attachment_id = 0;

    if (function_exists('tmw_aw_card_data')) {
        $card = tmw_aw_card_data($term->term_id);
        if (!empty($card['front'])) {
            $front_url = $card['front'];
        }
        if (!empty($card['back'])) {
            $back_url = $card['back'];
        }
    }

    if (($front_url === '' || $back_url === '') && function_exists('get_field')) {
        $acf_front = get_field('actor_card_front', 'models_' . $term->term_id);
        $acf_back  = get_field('actor_card_back', 'models_' . $term->term_id);
        if ($front_url === '' && is_array($acf_front) && !empty($acf_front['url'])) {
            $front_url = $acf_front['url'];
        }
        if ($back_url === '' && is_array($acf_back) && !empty($acf_back['url'])) {
            $back_url = $acf_back['url'];
        }
    }

    $ov = function_exists('tmw_tools_overrides_for_term') ? tmw_tools_overrides_for_term($term->term_id) : ['front_url' => '', 'back_url' => '', 'css_front' => '', 'css_back' => ''];
    $front_url = ($ov['front_url'] ?: $front_url) ?: (function_exists('tmw_placeholder_image_url') ? tmw_placeholder_image_url() : '');
    $back_url  = ($ov['back_url'] ?: $back_url) ?: $front_url;

    if (function_exists('tmw_same_image') && tmw_same_image($back_url, $front_url) && function_exists('tmw_aw_find_by_candidates')) {
        $cands = [];
        $explicit = get_term_meta($term->term_id, 'tmw_aw_nick', true);
        if (!$explicit) {
            $explicit = get_term_meta($term->term_id, 'tm_lj_nick', true);
        }
        if ($explicit) {
            $cands[] = $explicit;
        }
        $cands[] = $term->slug;
        $cands[] = $term->name;
        $cands[] = str_replace(['-', '_', ' '], '', $term->slug);
        $cands[] = str_replace(['-', '_', ' '], '', $term->name);
        $row = tmw_aw_find_by_candidates(array_unique(array_filter($cands)));
        if ($row && function_exists('tmw_aw_pick_images_from_row')) {
            list($_f, $_b) = tmw_aw_pick_images_from_row($row);
            if ($_b && !tmw_same_image($_b, $front_url)) {
                $back_url = $_b;
            }
        }
    }

    if ($front_url === '') {
        return $cache;
    }

    $dims = tmw_child_image_dimensions($front_url);

    $attachment_id = tmw_get_attachment_id_cached($front_url);
    if ($attachment_id) {
        $optimized = wp_get_attachment_image_src($attachment_id, 'tmw-front-optimized');
        if (is_array($optimized) && !empty($optimized[0])) {
            $front_url = $optimized[0];
            if (!empty($optimized[1])) {
                $dims['width'] = (int) $optimized[1];
            }
            if (!empty($optimized[2])) {
                $dims['height'] = (int) $optimized[2];
            }
        }
    }

    $cache = [
        'url'    => $front_url,
        'alt'    => $term->name,
        'width'  => $dims['width'],
        'height' => $dims['height'],
        'attachment_id' => $attachment_id,
    ];

    return $cache;
}

/**
 * Determines whether the current flipbox should expose the inline <img> for LCP.
 */
function tmw_child_should_use_lcp_image(): bool {
    static $done = false;

    if (!is_front_page() || is_paged()) {
        return false;
    }

    if ($done) {
        return false;
    }

    $done = true;
    return true;
}

/**
 * Ensure slot machine images have width/height attributes to reserve space.
 */
function tmw_child_inject_slot_machine_dimensions(string $html): string {
    $html = (string) $html;
    if (trim($html) === '' || stripos($html, '<img') === false) {
        return $html;
    }

    $previous_libxml = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $wrapped = '<!DOCTYPE html><html><body>' . $html . '</body></html>';
    $loaded = $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous_libxml);

    if (!$loaded) {
        return $html;
    }

    $default_width = 110;
    $default_height = 110;
    $images = $doc->getElementsByTagName('img');

    foreach ($images as $img) {
        $width_attr = trim((string) $img->getAttribute('width'));
        $height_attr = trim((string) $img->getAttribute('height'));

        if ($width_attr !== '' && $height_attr !== '') {
            continue;
        }

        $data_width = trim((string) $img->getAttribute('data-width'));
        $data_height = trim((string) $img->getAttribute('data-height'));
        $data_size = trim((string) $img->getAttribute('data-size'));
        $data_dimensions = trim((string) $img->getAttribute('data-dimensions'));

        if (($data_width === '' || $data_height === '') && $data_size !== '') {
            if (preg_match('/(\d+)\s*[x×]\s*(\d+)/i', $data_size, $matches)) {
                $data_width = $data_width ?: $matches[1];
                $data_height = $data_height ?: $matches[2];
            }
        }

        if (($data_width === '' || $data_height === '') && $data_dimensions !== '') {
            if (preg_match('/(\d+)\s*[x×]\s*(\d+)/i', $data_dimensions, $matches)) {
                $data_width = $data_width ?: $matches[1];
                $data_height = $data_height ?: $matches[2];
            }
        }

        $width_value = $data_width !== '' ? (int) $data_width : $default_width;
        $height_value = $data_height !== '' ? (int) $data_height : $default_height;

        if ($width_attr === '' && $width_value > 0) {
            $img->setAttribute('width', (string) $width_value);
        }

        if ($height_attr === '' && $height_value > 0) {
            $img->setAttribute('height', (string) $height_value);
        }
    }

    $body = $doc->getElementsByTagName('body')->item(0);
    if (!$body) {
        return $html;
    }

    $output = '';
    foreach ($body->childNodes as $child) {
        $output .= $doc->saveHTML($child);
    }

    return $output !== '' ? $output : $html;
}
