<?php
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
            if (defined('TMW_DEBUG') && TMW_DEBUG) {
                error_log('[TMW-FEATURED] excluded page');
            }
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

if (!function_exists('tmw_featured_models_injector_callback')) {
    function tmw_featured_models_injector_callback($buffer) {
        if (!is_string($buffer) || $buffer === '') {
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        $markup = isset($GLOBALS['tmw_featured_models_markup']) ? $GLOBALS['tmw_featured_models_markup'] : '';
        if ($markup === '') {
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        if (strpos($buffer, '<!-- TMW-FEATURED-MODELS -->') !== false) {
            $GLOBALS['tmw_featured_models_markup'] = '';
            return $buffer;
        }

        $log_anchor = '';
        $primary_pattern = '#</div>\s*<!--\s*#primary\s*-->#i';
        if (preg_match_all($primary_pattern, $buffer, $matches, PREG_OFFSET_CAPTURE) && !empty($matches[0])) {
            $last_match = $matches[0][count($matches[0]) - 1];
            $pos = $last_match[1];
            $log_anchor = 'primary';
            $buffer = substr_replace($buffer, $markup, $pos, 0);
        } else {
            $main_pattern = '#<main\b[^>]*\bid=["\']main["\'][^>]*>#i';
            if (preg_match_all($main_pattern, $buffer, $matches, PREG_OFFSET_CAPTURE) && !empty($matches[0])) {
                $last_match = $matches[0][count($matches[0]) - 1];
                $search_start = $last_match[1] + strlen($last_match[0]);
                $pos = stripos($buffer, '</main>', $search_start);
                if ($pos !== false) {
                    $log_anchor = 'main-id';
                    $buffer = substr_replace($buffer, $markup, $pos, 0);
                }
            }

            if ($log_anchor === '') {
                $pos = strripos($buffer, '</main>');
                if ($pos !== false) {
                    $log_anchor = 'main-last';
                    $buffer = substr_replace($buffer, $markup, $pos, 0);
                }
            }

            if ($log_anchor === '') {
                $pos = strripos($buffer, '</footer>');
                if ($pos !== false) {
                    $log_anchor = 'footer';
                    $buffer = substr_replace($buffer, $markup, $pos, 0);
                }
            }
        }

        if (defined('TMW_DEBUG') && TMW_DEBUG) {
            if ($log_anchor === '') {
                $log_anchor = 'skipped-no-anchor';
            }
            error_log('[TMW-FEATURED-INJECT] anchor=' . $log_anchor);
        }

        $GLOBALS['tmw_featured_models_markup'] = '';
        return $buffer;
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

        $markup = tmw_featured_models_render_block();
        if ($markup === '') {
            return;
        }

        $GLOBALS['tmw_featured_models_injector_started'] = true;
        $GLOBALS['tmw_featured_models_markup'] = $markup;

        ob_start('tmw_featured_models_injector_callback');
    }
}

add_action('template_redirect', 'tmw_featured_models_injector_start', 0);
