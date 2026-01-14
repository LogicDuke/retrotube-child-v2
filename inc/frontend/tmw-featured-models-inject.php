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

if (!function_exists('tmw_featured_models_render_block')) {
    function tmw_featured_models_render_block(): string {
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

        return '<!-- TMW-FEATURED-MODELS -->' . $markup;
    }
}

if (!function_exists('tmw_featured_models_find_main_close_pos')) {
    /**
     * Find the correct </main> closing position.
     *
     * IMPROVED: Uses sidebar position as reference to ensure we find the
     * </main> that closes the main content area, not some nested main tag.
     */
    function tmw_featured_models_find_main_close_pos(string $html) {
        if ($html === '') {
            return false;
        }

        // IMPROVED: Find sidebar position first, then look for </main> before it
        $aside_pos = stripos($html, '<aside');
        if ($aside_pos !== false) {
            $content_before_sidebar = substr($html, 0, $aside_pos);
            $last_main_close = strripos($content_before_sidebar, '</main>');
            if ($last_main_close !== false) {
                return $last_main_close;
            }
        }

        // Fallback to original logic if no sidebar found
        if (!preg_match_all('~<main\b[^>]*>~i', $html, $matches, PREG_OFFSET_CAPTURE) || empty($matches[0])) {
            return false;
        }

        $selected = null;
        $matches_count = count($matches[0]);
        for ($i = 0; $i < $matches_count; $i++) {
            $tag = $matches[0][$i][0];
            if (preg_match('~\bid\s*=\s*["\']main["\']~i', $tag)) {
                $selected = $matches[0][$i];
                break;
            }
        }

        if ($selected === null) {
            for ($i = 0; $i < $matches_count; $i++) {
                $tag = $matches[0][$i][0];
                if (preg_match('~\bid\s*=\s*["\']primary["\']~i', $tag)) {
                    $selected = $matches[0][$i];
                    break;
                }
            }
        }

        if ($selected === null) {
            $selected = $matches[0][$matches_count - 1];
        }

        $open_pos = $selected[1];
        $open_end = $open_pos + strlen($selected[0]);
        $depth = 1;
        $cursor = $open_end;

        while (preg_match('~</?main\b[^>]*>~i', $html, $tag_match, PREG_OFFSET_CAPTURE, $cursor)) {
            $tag = $tag_match[0][0];
            $tag_pos = $tag_match[0][1];
            if (stripos($tag, '</main') === 0) {
                $depth--;
            } else {
                $depth++;
            }

            if ($depth === 0) {
                return $tag_pos;
            }

            $cursor = $tag_pos + strlen($tag);
        }

        return false;
    }
}

if (!function_exists('tmw_featured_models_log_anchor')) {
    function tmw_featured_models_log_anchor(string $anchor) {
        if (!defined('TMW_DEBUG') || !TMW_DEBUG) {
            return;
        }

        if (!empty($GLOBALS['tmw_featured_models_injector_logged'])) {
            return;
        }

        $GLOBALS['tmw_featured_models_injector_logged'] = true;
        error_log('[TMW-FEATURED-INJECT] anchor=' . $anchor);
    }
}

if (!function_exists('tmw_featured_models_injector_callback')) {
    function tmw_featured_models_injector_callback($buffer) {
        if (!is_string($buffer) || $buffer === '') {
            tmw_featured_models_log_anchor('skipped');
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        $markup = isset($GLOBALS['tmw_featured_models_markup']) ? $GLOBALS['tmw_featured_models_markup'] : '';
        if ($markup === '') {
            tmw_featured_models_log_anchor('skipped');
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        if (strpos($buffer, '<!-- TMW-FEATURED-MODELS -->') !== false) {
            tmw_featured_models_log_anchor('skipped');
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        $log_anchor = 'skipped';
        $main_pos = tmw_featured_models_find_main_close_pos($buffer);
        if ($main_pos !== false) {
            $log_anchor = 'main-scan';
            $buffer = substr_replace($buffer, $markup, $main_pos, 0);
        } else {
            $footer_pos = strripos($buffer, '</footer>');
            if ($footer_pos !== false) {
                $log_anchor = 'footer-fallback';
                $buffer = substr_replace($buffer, $markup, $footer_pos, 0);
            }
        }

        tmw_featured_models_log_anchor($log_anchor);

        $GLOBALS['tmw_featured_models_markup'] = '';
        return $buffer;
    }
}

if (!function_exists('tmw_featured_models_injector_start')) {
    function tmw_featured_models_injector_start() {
        if (!tmw_featured_models_should_inject()) {
            tmw_featured_models_log_anchor('skipped');
            return;
        }

        if (!empty($GLOBALS['tmw_featured_models_injector_started'])) {
            return;
        }

        $markup = tmw_featured_models_render_block();
        if ($markup === '') {
            tmw_featured_models_log_anchor('skipped');
            return;
        }

        $GLOBALS['tmw_featured_models_injector_started'] = true;
        $GLOBALS['tmw_featured_models_markup'] = $markup;

        ob_start('tmw_featured_models_injector_callback');
    }
}

add_action('template_redirect', 'tmw_featured_models_injector_start', 0);
