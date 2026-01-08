<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Front-end performance trims scoped primarily to the homepage.
 */

/**
 * Remove jQuery migrate unless explicitly required.
 */
add_action('wp_default_scripts', function ($scripts) {
    if (!($scripts instanceof WP_Scripts)) {
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
        || is_page_template('template-models-flipboxes.php');
}

/**
 * Dequeue non-critical styles on heavy media views.
 */
add_action('wp_enqueue_scripts', function () {
    if (!tmw_child_is_heavy_media_view()) {
        return;
    }

    if (!is_user_logged_in()) {
        if (wp_style_is('dashicons', 'enqueued')) {
            wp_dequeue_style('dashicons');
            wp_deregister_style('dashicons');
        }
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
 * Delay non-critical styles on model pages without changing final appearance.
 */
add_filter('style_loader_tag', function ($html, $handle, $href, $media) {
    if (is_admin()) {
        return $html;
    }

    $fontawesome_inline = '';
    if (stripos($handle, 'font-awesome') !== false || stripos($handle, 'fontawesome') !== false) {
        $fontawesome_inline = tmw_child_fontawesome_display_swap($href);
    }

    $critical = [
        'retrotube-parent',
        'retrotube-child-style',
    ];

    if (in_array($handle, $critical, true)) {
        return $html . $fontawesome_inline;
    }

    if (is_singular('model')) {
        $defer_handles = [
            'font-awesome',
            'fontawesome',
            'video-js',
            'videojs',
            'theme-my-login',
            'tml',
            'cookie-consent',
            'disclaimer',
            'toc_list_style',
            'slot-machine',
            'rt-child-flip',
            'flipboxes',
        ];

        foreach ($defer_handles as $defer) {
            if (stripos($handle, $defer) !== false) {
                return sprintf(
                    '<link rel="preload" as="style" href="%s" onload="this.onload=null;this.rel=\'stylesheet\'"><noscript>%s</noscript>',
                    esc_url($href),
                    $html
                ) . $fontawesome_inline;
            }
        }
    }

    return $html . $fontawesome_inline;
}, 10, 4);

add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }

    $preconnects = [
        'https://www.googletagmanager.com',
        'https://connect.facebook.net',
        'https://pagead2.googlesyndication.com',
    ];

    foreach ($preconnects as $origin) {
        printf('<link rel="preconnect" href="%s" crossorigin>%s', esc_url($origin), "\n");
    }
}, 1);

add_action('wp_footer', function () {
    if (!is_singular('model')) {
        return;
    }
    ?>
    <script>
    (function () {
        function stabilizeSlotImages() {
            var imgs = document.querySelectorAll('.tmw-slot-machine img');
            imgs.forEach(function (img) {
                if (!img.hasAttribute('loading')) {
                    img.setAttribute('loading', 'lazy');
                }
                if (!img.hasAttribute('decoding')) {
                    img.setAttribute('decoding', 'async');
                }
                if (!img.hasAttribute('width') && img.naturalWidth) {
                    img.setAttribute('width', img.naturalWidth);
                }
                if (!img.hasAttribute('height') && img.naturalHeight) {
                    img.setAttribute('height', img.naturalHeight);
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', stabilizeSlotImages, { once: true });
        } else {
            stabilizeSlotImages();
        }
    })();
    </script>
    <?php
}, 60);

/**
 * Add defer to non-critical heavy-view scripts and delay third-party tags until interaction.
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (is_admin()) {
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
        'googletagmanager.com',
        'connect.facebook.net',
        'googlesyndication.com',
        'google-analytics.com',
    ];

    foreach ($delay_hosts as $delay_host) {
        if ($host && stripos($host, $delay_host) !== false) {
            return sprintf('<script data-src="%s"></script>', esc_url($src));
        }
    }

    return $tag;
}, 10, 3);

add_action('wp_footer', function () {
    if (is_admin()) {
        return;
    }
    ?>
    <script>
    (function () {
        var loaded = false;
        function loadScripts() {
            if (loaded) { return; }
            loaded = true;
            document.querySelectorAll('script[data-src]').forEach(function (el) {
                var s = document.createElement('script');
                s.src = el.getAttribute('data-src');
                s.async = true;
                document.body.appendChild(s);
            });
        }

        ['scroll', 'click', 'touchstart', 'mousemove', 'keydown'].forEach(function (evt) {
            window.addEventListener(evt, loadScripts, { once: true, passive: true });
        });

        setTimeout(loadScripts, 5000);
    })();
    </script>
    <?php
}, 99);

add_filter('wp_editor_set_quality', function ($quality, $mime_type, $size) {
    if ($mime_type === 'image/webp') {
        return 80;
    }

    return $quality;
}, 10, 3);

/**
 * Append FontAwesome @font-face rules with font-display: swap when local CSS is available.
 */
function tmw_child_fontawesome_display_swap(string $href): string {
    if (!function_exists('tmw_is_local_url') || !tmw_is_local_url($href)) {
        return '';
    }

    $path = wp_parse_url($href, PHP_URL_PATH);
    if (!$path || !defined('ABSPATH')) {
        return '';
    }

    $path = str_replace("\0", '', $path);
    $path = wp_normalize_path(ltrim($path, '/'));
    $path = preg_replace('#(\.\./|\.\/)#', '', $path);
    $file = wp_normalize_path(ABSPATH . $path);
    $resolved = realpath($file);
    if ($resolved === false) {
        return '';
    }

    $root = wp_normalize_path(ABSPATH);
    if (strpos($resolved, $root) !== 0) {
        return '';
    }

    $file = $resolved;
    if (!is_readable($file)) {
        return '';
    }

    $css = file_get_contents($file);
    if ($css === false || stripos($css, '@font-face') === false) {
        return '';
    }

    preg_match_all('/@font-face\\s*\\{[^}]*\\}/i', $css, $matches);
    if (empty($matches[0])) {
        return '';
    }

    $blocks = [];
    foreach ($matches[0] as $block) {
        if (stripos($block, 'font-display') === false) {
            $block = rtrim($block, " \t\n\r\0\x0B}") . 'font-display: swap;}';
        }
        $blocks[] = $block;
    }

    return '<style>' . implode("\n", $blocks) . '</style>';
}

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
