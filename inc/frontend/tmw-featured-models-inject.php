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
        $scope = $html;
        $used_aside_scope = false;

        if ($aside_pos !== false) {
            $scope = substr($html, 0, $aside_pos);
            $used_aside_scope = true;
        }

        $matches = [];
        if (preg_match_all('~<main\b[^>]*>~i', $scope, $matches, PREG_OFFSET_CAPTURE) && !empty($matches[0])) {
            $selected = null;
            foreach ($matches[0] as $match) {
                if (preg_match('~\bid\s*=\s*["\']main["\']~i', $match[0])) {
                    $selected = $match;
                    break;
                }
            }

            if ($selected === null) {
                foreach ($matches[0] as $match) {
                    if (preg_match('~\bid\s*=\s*["\']primary["\']~i', $match[0])) {
                        $selected = $match;
                        break;
                    }
                }
            }

            if ($selected === null) {
                $selected = $matches[0][count($matches[0]) - 1];
            }

            $open_pos = $selected[1];
            $open_end = $open_pos + strlen($selected[0]);
            $depth = 1;
            $cursor = $open_end;

            while (preg_match('~</?main\b[^>]*>~i', $scope, $tag_match, PREG_OFFSET_CAPTURE, $cursor)) {
                $tag = $tag_match[0][0];
                $tag_pos = $tag_match[0][1];
                if (stripos($tag, '</main') === 0) {
                    $depth--;
                } else {
                    $depth++;
                }

                if ($depth === 0) {
                    $GLOBALS['tmw_featured_models_insertion_anchor'] = $used_aside_scope
                        ? 'scope-main-before-aside'
                        : 'main-scan';
                    return $tag_pos;
                }

                $cursor = $tag_pos + strlen($tag);
            }
        }

        $fallback_pos = strripos($scope, '</main>');
        if ($fallback_pos !== false) {
            $GLOBALS['tmw_featured_models_insertion_anchor'] = 'fallback-last-main';
            return $fallback_pos;
        }

        $fallback_pos = strripos($html, '</main>');
        if ($fallback_pos !== false) {
            $GLOBALS['tmw_featured_models_insertion_anchor'] = 'fallback-last-main';
            return $fallback_pos;
        }

        $fallback_pos = strripos($html, '</footer>');
        if ($fallback_pos !== false) {
            $GLOBALS['tmw_featured_models_insertion_anchor'] = 'footer';
            return $fallback_pos;
        }

        $fallback_pos = strripos($html, '</body>');
        if ($fallback_pos !== false) {
            $GLOBALS['tmw_featured_models_insertion_anchor'] = 'body';
            return $fallback_pos;
        }

        $GLOBALS['tmw_featured_models_insertion_anchor'] = 'skipped';
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

        $insert_pos = tmw_featured_models_find_insertion_pos($buffer);
        $log_anchor = isset($GLOBALS['tmw_featured_models_insertion_anchor'])
            ? $GLOBALS['tmw_featured_models_insertion_anchor']
            : 'skipped';

        if ($insert_pos !== false) {
            $buffer = substr_replace($buffer, $markup, $insert_pos, 0);
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
