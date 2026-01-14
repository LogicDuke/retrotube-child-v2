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

        $wrapped = '<!-- TMW-FEATURED-MODELS:START -->' . "\n"
            . $markup . "\n"
            . '<!-- TMW-FEATURED-MODELS:END -->';

        return trim($wrapped);
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

if (!function_exists('tmw_featured_models_log_anchor')) {
    function tmw_featured_models_log_anchor(string $anchor, string $archive_relocate = '') {
        if (!defined('TMW_DEBUG') || !TMW_DEBUG) {
            return;
        }

        if (!empty($GLOBALS['tmw_featured_models_injector_logged'])) {
            return;
        }

        $GLOBALS['tmw_featured_models_injector_logged'] = true;
        $message = '[TMW-FEATURED-INJECT] ';
        if ($archive_relocate !== '') {
            $message .= 'archive_relocate=' . $archive_relocate . ' ';
        }
        $message .= 'anchor=' . $anchor;
        error_log($message);
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

        $start_marker = '<!-- TMW-FEATURED-MODELS:START -->';
        $end_marker = '<!-- TMW-FEATURED-MODELS:END -->';
        $block_to_insert = $markup;
        $start_pos = strpos($buffer, $start_marker);
        if ($start_pos !== false) {
            $end_pos = strpos($buffer, $end_marker, $start_pos);
            if ($end_pos !== false) {
                $end_pos += strlen($end_marker);
                $block_to_insert = substr($buffer, $start_pos, $end_pos - $start_pos);
                $buffer = substr_replace($buffer, '', $start_pos, $end_pos - $start_pos);
            }
        }

        if (defined('TMW_DEBUG') && TMW_DEBUG) {
            $aside_pos = stripos($buffer, '<aside');
            $primary_pos = false;
            $primary_match = [];
            if (preg_match('~<div\b[^>]*\bid\s*=\s*["\']primary["\']~i', $buffer, $primary_match, PREG_OFFSET_CAPTURE)) {
                $primary_pos = $primary_match[0][1];
            }

            $main_close_positions = [];
            $main_close_matches = [];
            if (preg_match_all('~</main>~i', $buffer, $main_close_matches, PREG_OFFSET_CAPTURE)) {
                foreach ($main_close_matches[0] as $match) {
                    $main_close_positions[] = $match[1];
                }
            }

            $main_close_count = count($main_close_positions);
            $last_three = $main_close_count > 0 ? array_slice($main_close_positions, -3) : [];
            $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

            error_log('[TMW-FEATURED-AUDIT] context is_category=' . (is_category() ? '1' : '0')
                . ' is_tag=' . (is_tag() ? '1' : '0')
                . ' is_page=' . (is_page() ? '1' : '0')
                . ' request_uri=' . $request_uri);
            error_log('[TMW-FEATURED-AUDIT] aside_pos=' . ($aside_pos === false ? 'false' : $aside_pos)
                . ' primary_pos=' . ($primary_pos === false ? 'false' : $primary_pos));
            error_log('[TMW-FEATURED-AUDIT] main_close_count=' . $main_close_count
                . ' last_three=' . (empty($last_three) ? '[]' : '[' . implode(', ', $last_three) . ']'));
        }

        $insert_pos = false;
        $log_anchor = 'skipped';
        $archive_relocate = '';

        if (is_category() || is_tag()) {
            $archive_relocate = is_category() ? 'category' : 'tag';
            $aside_pos = stripos($buffer, '<aside');
            if ($aside_pos !== false) {
                $left = substr($buffer, 0, $aside_pos);
                $main_close = strripos($left, '</main>');
                if ($main_close !== false) {
                    $insert_pos = $main_close;
                    $log_anchor = 'main-before-aside';
                }
            }

            if ($insert_pos === false) {
                $main_close = strripos($buffer, '</main>');
                if ($main_close !== false) {
                    $insert_pos = $main_close;
                    $log_anchor = 'main-before-footer';
                }
            }

            if ($insert_pos === false) {
                $footer_pos = strripos($buffer, '</footer>');
                if ($footer_pos !== false) {
                    $insert_pos = $footer_pos;
                    $log_anchor = 'fallback-footer';
                }
            }

            if ($insert_pos === false) {
                $insert_pos = strlen($buffer);
                $log_anchor = 'append-end';
            }

            $block_to_insert = '<div class="tmw-featured-models-anchor">' . $block_to_insert . '</div>';
        } else {
            $insert_pos = tmw_featured_models_find_insertion_pos($buffer);
            $log_anchor = isset($GLOBALS['tmw_featured_models_insertion_anchor'])
                ? $GLOBALS['tmw_featured_models_insertion_anchor']
                : 'skipped';
        }

        if (defined('TMW_DEBUG') && TMW_DEBUG) {
            $used_main_offset = false;
            if (!isset($main_close_positions)) {
                $main_close_positions = [];
                $main_close_matches = [];
                if (preg_match_all('~</main>~i', $buffer, $main_close_matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($main_close_matches[0] as $match) {
                        $main_close_positions[] = $match[1];
                    }
                }
            }

            if ($insert_pos !== false && in_array($insert_pos, $main_close_positions, true)) {
                $used_main_offset = $insert_pos;
            }

            error_log('[TMW-FEATURED-AUDIT] insertion_pos=' . ($insert_pos === false ? 'false' : $insert_pos)
                . ' used_main_offset=' . ($used_main_offset === false ? 'false' : $used_main_offset));
        }

        if ($insert_pos !== false) {
            $buffer = substr_replace($buffer, $block_to_insert, $insert_pos, 0);
        }

        tmw_featured_models_log_anchor($log_anchor, $archive_relocate);

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
