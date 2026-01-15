<?php
/**
 * Featured Models Global Injection
 *
 * Injects Featured Models block into pages via output buffer.
 * Uses improved logic to find the correct </main> position (before sidebar).
 */

if (!defined('ABSPATH')) {
    exit;
}

$GLOBALS['tmw_featured_models_markup'] = '';

if (!function_exists('tmw_featured_models_should_inject')) {
    function tmw_featured_models_should_inject() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return false;
        }

        if (is_category()) {
            tmw_featured_models_debug_log('TMW-FEATURED-LOCK', 'injector_disabled=1 ctx=category');
            return false;
        }

        if (is_tag()) {
            tmw_featured_models_debug_log('TMW-FEATURED-LOCK', 'injector_disabled=1 ctx=tag');
            return false;
        }

        if (is_front_page() || is_home()) {
            tmw_featured_models_debug_log('TMW-FEATURED-LOCK', 'injector_disabled=1 ctx=home');
            return false;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        if (is_feed()) {
            return false;
        }

        if (is_embed()) {
            return false;
        }

        if (is_front_page() || is_home()) {
            return false;
        }

        $excluded = [
            '18-u-s-c-2257',
            'dmca',
            'privacy-policy-top-models-webcam',
            'terms-of-use-of-top-models-webcam-directory',
            'submit-a-video',
        ];

        if (is_page($excluded)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('tmw_featured_models_bootstrap_markup')) {
    function tmw_featured_models_bootstrap_markup(): string {
        $shortcode = function_exists('tmw_get_featured_shortcode_for_context')
            ? tmw_get_featured_shortcode_for_context()
            : '[tmw_featured_models]';
        if (function_exists('tmw_clean_featured_shortcode')) {
            $shortcode = tmw_clean_featured_shortcode($shortcode);
        }

        if (!is_string($shortcode) || trim($shortcode) === '') {
            return '';
        }

        set_query_var('tmw_featured_shortcode', $shortcode);
        ob_start();
        $template = locate_template('partials/featured-models-block.php', false, false);
        if ($template) {
            include $template;
        } else {
            get_template_part('partials/featured-models-block');
        }
        $markup = ob_get_clean();
        set_query_var('tmw_featured_shortcode', '');

        if (!is_string($markup)) {
            return '';
        }

        $markup = trim($markup);
        if ($markup === '') {
            return '';
        }

        $wrapped = '<!-- TMW-FEATURED-MODELS:START -->' . "\n"
            . $markup . "\n"
            . '<!-- TMW-FEATURED-MODELS:END -->';

        return trim($wrapped);
    }
}

if (!function_exists('tmw_featured_models_block_markup')) {
    function tmw_featured_models_block_markup(): string {
        return tmw_featured_models_bootstrap_markup();
    }
}

if (!function_exists('tmw_featured_models_is_force_relocate_context')) {
    function tmw_featured_models_is_force_relocate_context(): bool {
        return is_category() || is_tag();
    }
}

if (!function_exists('tmw_featured_models_debug_log')) {
    function tmw_featured_models_debug_log(string $tag, string $message): void {
        if (!defined('TMW_DEBUG') || !TMW_DEBUG) {
            return;
        }

        error_log('[' . $tag . '] ' . $message);
    }
}

if (!function_exists('tmw_featured_models_get_page_type')) {
    function tmw_featured_models_get_page_type(): string {
        if (is_category()) {
            return 'category';
        }

        if (is_tag()) {
            return 'tag';
        }

        if (is_page('categories')) {
            return 'page(categories)';
        }

        return 'other';
    }
}

if (!function_exists('tmw_featured_models_find_insertion_pos')) {
    /**
     * Find the correct insertion point for Featured Models markup.
     */
    function tmw_featured_models_find_insertion_pos(string $html) {
        $GLOBALS['tmw_featured_models_insertion_anchor'] = 'skipped';

        if ($html === '') {
            return false;
        }

        $aside_pos = stripos($html, '<aside');
        $search_end = $aside_pos !== false ? $aside_pos : strlen($html);
        $primary_open_pos = false;
        $primary_open_end = false;

        if (preg_match('~<div\b[^>]*\bid\s*=\s*["\']primary["\'][^>]*>~i', $html, $primary_match, PREG_OFFSET_CAPTURE)) {
            $primary_open_pos = $primary_match[0][1];
            $primary_open_end = $primary_open_pos + strlen($primary_match[0][0]);
        }

        if ($primary_open_pos !== false) {
            $sub = substr($html, $primary_open_pos, $search_end - $primary_open_pos);
            $main_close_pos = strripos($sub, '</main>');
            if ($main_close_pos !== false) {
                $GLOBALS['tmw_featured_models_insertion_anchor'] = 'primary-main-before-aside';
                return $primary_open_pos + $main_close_pos;
            }

            $cursor = $primary_open_end;
            $depth = 1;
            while (preg_match('~</?div\b[^>]*>~i', $html, $tag_match, PREG_OFFSET_CAPTURE, $cursor)) {
                $tag = $tag_match[0][0];
                $tag_pos = $tag_match[0][1];
                if ($tag_pos >= $search_end) {
                    break;
                }

                if (stripos($tag, '</div') === 0) {
                    $depth--;
                } else {
                    $depth++;
                }

                if ($depth === 0) {
                    $GLOBALS['tmw_featured_models_insertion_anchor'] = 'primary-close-before-aside';
                    return $tag_pos;
                }

                $cursor = $tag_pos + strlen($tag);
            }
        }

        $footer_pos = strripos($html, '</footer>');
        if ($footer_pos !== false) {
            $before_footer = substr($html, 0, $footer_pos);
            $fallback_pos = strripos($before_footer, '</main>');
            if ($fallback_pos !== false) {
                $GLOBALS['tmw_featured_models_insertion_anchor'] = 'fallback-last-main';
                return $fallback_pos;
            }

            $GLOBALS['tmw_featured_models_insertion_anchor'] = 'fallback-footer';
            return $footer_pos;
        }

        $GLOBALS['tmw_featured_models_insertion_anchor'] = 'fallback-append';
        return strlen($html);
    }
}

if (!function_exists('tmw_featured_models_strip_existing_blocks')) {
    function tmw_featured_models_strip_existing_blocks(string $content): string {
        $pattern = '~<!--\\s*TMW-FEATURED-MODELS\\s*-->.*?<!--\\s*/TMW-FEATURED-MODELS\\s*-->~is';
        $removed = 0;
        if (defined('TMW_DEBUG') && TMW_DEBUG) {
            preg_match_all($pattern, $content, $matches);
            $removed = isset($matches[0]) ? count($matches[0]) : 0;
        }

        $content = preg_replace($pattern, '', $content);

        if (defined('TMW_DEBUG') && TMW_DEBUG) {
            tmw_featured_models_debug_log(
                'TMW-FEATURED-INJECT',
                'strip=1 removed_blocks=' . $removed
            );
        }

        return $content;
    }
}

if (!function_exists('tmw_featured_models_find_primary_main_close_pos')) {
    function tmw_featured_models_find_primary_main_close_pos(string $content) {
        if ($content === '') {
            return false;
        }

        if (!preg_match('~<div\\b[^>]*\\bid\\s*=\\s*["\']primary["\'][^>]*>~i', $content, $match, PREG_OFFSET_CAPTURE)) {
            return false;
        }

        $primary_start = $match[0][1];
        $primary_end = null;
        $primary_end_match = null;

        if (preg_match('~</div>\\s*<!--\\s*#primary\\s*-->~i', $content, $primary_end_match, PREG_OFFSET_CAPTURE, $primary_start)) {
            $primary_end = $primary_end_match[0][1];
        } else {
            $aside_pos = stripos($content, '<aside');
            if ($aside_pos !== false) {
                $primary_end = $aside_pos;
            } else {
                $primary_end = strlen($content);
            }
        }

        $region = substr($content, $primary_start, $primary_end - $primary_start);
        $main_close_pos = strripos($region, '</main>');
        if ($main_close_pos === false) {
            return false;
        }

        $absolute_pos = $primary_start + $main_close_pos;
        $GLOBALS['tmw_featured_models_primary_region'] = [
            'primary_start' => $primary_start,
            'primary_end' => $primary_end,
            'pos' => $absolute_pos,
        ];

        return $absolute_pos;
    }
}

if (!function_exists('tmw_featured_models_inject_into_buffer')) {
    function tmw_featured_models_inject_into_buffer(string $buffer): string {
        if ($buffer === '') {
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        $markup = isset($GLOBALS['tmw_featured_models_markup']) ? $GLOBALS['tmw_featured_models_markup'] : '';
        if ($markup === '') {
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        $page_type = tmw_featured_models_get_page_type();
        $force = tmw_featured_models_is_force_relocate_context();

        if (!$force && strpos($buffer, '<!-- TMW-FEATURED-MODELS:START -->') !== false) {
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        if ($force) {
            $buffer = tmw_featured_models_strip_existing_blocks($buffer);
        }

        $block_to_insert = $markup;
        $insert_pos = false;
        $anchor_label = 'skipped';

        if ($force) {
            $insert_pos = tmw_featured_models_find_primary_main_close_pos($buffer);
            $region = isset($GLOBALS['tmw_featured_models_primary_region'])
                ? $GLOBALS['tmw_featured_models_primary_region']
                : [];
            if ($insert_pos !== false) {
                $anchor_label = 'primary-main';
                if (defined('TMW_DEBUG') && TMW_DEBUG) {
                    tmw_featured_models_debug_log(
                        'TMW-FEATURED-INJECT',
                        'anchor=primary-main primary_start=' . ($region['primary_start'] ?? 'n/a')
                        . ' primary_end=' . ($region['primary_end'] ?? 'n/a')
                        . ' pos=' . ($region['pos'] ?? 'n/a')
                    );
                }
            }
        }

        if ($insert_pos === false) {
            $insert_pos = tmw_featured_models_find_insertion_pos($buffer);
            $anchor_label = isset($GLOBALS['tmw_featured_models_insertion_anchor'])
                ? $GLOBALS['tmw_featured_models_insertion_anchor']
                : 'skipped';
        }

        if ($insert_pos !== false) {
            $buffer = substr_replace($buffer, $block_to_insert, $insert_pos, 0);
        }

        tmw_featured_models_debug_log(
            'TMW-FEATURED-INJECT',
            'page_type=' . $page_type
            . ' target=fallback'
            . ' anchor=' . $anchor_label
            . ' done output_length=' . strlen($buffer)
        );

        $GLOBALS['tmw_featured_models_markup'] = '';
        return $buffer;
    }
}

if (!function_exists('tmw_featured_models_injector_shutdown')) {
    function tmw_featured_models_injector_shutdown(): void {
        if (!empty($GLOBALS['tmw_featured_models_shutdown_ran'])) {
            return;
        }

        $GLOBALS['tmw_featured_models_shutdown_ran'] = true;

        if (!isset($GLOBALS['tmw_featured_models_ob_level'])) {
            return;
        }

        $target_level = (int) $GLOBALS['tmw_featured_models_ob_level'];
        while (ob_get_level() > $target_level) {
            ob_end_flush();
        }

        if (ob_get_level() !== $target_level) {
            return;
        }

        $content = ob_get_clean();
        if (!is_string($content)) {
            return;
        }

        echo tmw_featured_models_inject_into_buffer($content);
    }
}

if (!function_exists('tmw_featured_models_injector_start')) {
    function tmw_featured_models_injector_start() {
        if (!tmw_featured_models_should_inject()) {
            return;
        }

        if (!empty($GLOBALS['tmw_featured_models_injector_started'])) {
            return;
        }

        $markup = tmw_featured_models_block_markup();
        if ($markup === '') {
            return;
        }

        $GLOBALS['tmw_featured_models_injector_started'] = true;
        $GLOBALS['tmw_featured_models_markup'] = $markup;

        ob_start();
        $GLOBALS['tmw_featured_models_ob_level'] = ob_get_level();
        add_action('shutdown', 'tmw_featured_models_injector_shutdown', 0);
    }
}

add_action('template_redirect', 'tmw_featured_models_injector_start', 0);
