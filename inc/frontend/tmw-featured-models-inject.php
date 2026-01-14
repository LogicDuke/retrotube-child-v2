<?php
if (!defined('ABSPATH')) {
    exit;
}

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
            return $buffer;
        }

        if (strpos($buffer, '<!-- TMW-FEATURED-MODELS -->') !== false) {
            return $buffer;
        }

        $markup = tmw_featured_models_render_block();
        if ($markup === '') {
            if (defined('TMW_DEBUG') && TMW_DEBUG) {
                error_log('[TMW-FEATURED-INJECT] skipped');
            }
            return $buffer;
        }

        $pos = strrpos($buffer, '</main>');
        if ($pos !== false) {
            if (defined('TMW_DEBUG') && TMW_DEBUG) {
                error_log('[TMW-FEATURED-INJECT] injected before </main>');
            }
            return substr_replace($buffer, $markup, $pos, 0);
        }

        $pos = strrpos($buffer, '</footer>');
        if ($pos !== false) {
            if (defined('TMW_DEBUG') && TMW_DEBUG) {
                error_log('[TMW-FEATURED-INJECT] injected before </footer>');
            }
            return substr_replace($buffer, $markup, $pos, 0);
        }

        if (defined('TMW_DEBUG') && TMW_DEBUG) {
            error_log('[TMW-FEATURED-INJECT] skipped: no target');
        }

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

        $GLOBALS['tmw_featured_models_injector_started'] = true;

        if (defined('TMW_DEBUG') && TMW_DEBUG) {
            error_log('[TMW-FEATURED] injector buffering start');
        }

        ob_start('tmw_featured_models_injector_callback');
    }
}

add_action('template_redirect', 'tmw_featured_models_injector_start', 0);
