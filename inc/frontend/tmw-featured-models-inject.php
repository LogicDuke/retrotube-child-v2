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

if (!function_exists('tmw_featured_models_is_force_relocate_context')) {
    function tmw_featured_models_is_force_relocate_context(): bool {
        return is_category() || is_tag() || is_page('categories');
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

if (!function_exists('tmw_featured_models_strip_duplicates_fallback')) {
    function tmw_featured_models_strip_duplicates_fallback(string $html, array &$counts): string {
        $counts = [
            'marker' => 0,
            'tmw-featured-models-wrap' => 0,
            'tmwfm-slot' => 0,
            'tmwfm-wrap' => 0,
            'widget' => 0,
        ];

        $marker_pattern = '/<!--\\s*TMW-FEATURED-MODELS.*?-->/is';
        $html = preg_replace($marker_pattern, '', $html, -1, $counts['marker']);

        $widget_pattern = '#<([a-z0-9]+)([^>]*\\bclass=["\'][^"\']*\\bwidget\\b[^"\']*["\'][^>]*)>.*?<[^>]*\\bclass=["\'][^"\']*\\btmwfm-wrap\\b[^"\']*["\'][^>]*>.*?</[^>]*>.*?</\\1>#is';
        $html = preg_replace($widget_pattern, '', $html, -1, $counts['widget']);

        $wrap_patterns = [
            'tmw-featured-models-wrap' => '#<([a-z0-9]+)([^>]*\\bclass=["\'][^"\']*\\btmw-featured-models-wrap\\b[^"\']*["\'][^>]*)>.*?</\\1>#is',
            'tmwfm-slot' => '#<([a-z0-9]+)([^>]*\\bclass=["\'][^"\']*\\btmwfm-slot\\b[^"\']*["\'][^>]*)>.*?</\\1>#is',
            'tmwfm-wrap' => '#<([a-z0-9]+)([^>]*\\bclass=["\'][^"\']*\\btmwfm-wrap\\b[^"\']*["\'][^>]*)>.*?</\\1>#is',
        ];

        foreach ($wrap_patterns as $key => $pattern) {
            $html = preg_replace($pattern, '', $html, -1, $counts[$key]);
        }

        return $html;
    }
}

if (!function_exists('tmw_featured_models_relocate_dom')) {
    function tmw_featured_models_relocate_dom(string $html, string $markup): string {
        $meta = [
            'removed' => [
                'marker' => 0,
                'tmw-featured-models-wrap' => 0,
                'tmwfm-slot' => 0,
                'tmwfm-wrap' => 0,
                'widget' => 0,
            ],
            'target' => '',
            'output_length' => 0,
        ];

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        if (!$loaded) {
            return '';
        }

        $xpath = new DOMXPath($dom);

        $comment_nodes = $xpath->query('//comment()[contains(., "TMW-FEATURED-MODELS")]');
        foreach ($comment_nodes as $comment_node) {
            $comment_node->parentNode->removeChild($comment_node);
            $meta['removed']['marker']++;
        }

        $class_queries = [
            'tmw-featured-models-wrap' => 'tmw-featured-models-wrap',
            'tmwfm-slot' => 'tmwfm-slot',
            'tmwfm-wrap' => 'tmwfm-wrap',
        ];

        foreach ($class_queries as $key => $class_name) {
            $nodes = $xpath->query(
                '//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class_name . ' ")]'
            );

            foreach ($nodes as $node) {
                if ($class_name === 'tmwfm-wrap') {
                    $ancestor = $node->parentNode;
                    $widget_removed = false;
                    while ($ancestor && $ancestor->nodeType === XML_ELEMENT_NODE) {
                        $ancestor_class = $ancestor->getAttribute('class');
                        if ($ancestor_class !== '' && preg_match('/\\bwidget\\b/i', $ancestor_class)) {
                            $ancestor->parentNode->removeChild($ancestor);
                            $meta['removed']['widget']++;
                            $widget_removed = true;
                            break;
                        }
                        $ancestor = $ancestor->parentNode;
                    }

                    if ($widget_removed) {
                        continue;
                    }
                }

                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                    $meta['removed'][$key]++;
                }
            }
        }

        $target = null;
        $primary = $xpath->query('//*[@id="primary"]')->item(0);
        if ($primary) {
            $main = $xpath->query('//*[@id="primary"]//*[@id="main"]')->item(0);
            if ($main) {
                $target = $main;
                $meta['target'] = 'primary-main';
            } else {
                $target = $primary;
                $meta['target'] = 'primary';
            }
        } else {
            $content_area = $xpath->query(
                '//*[contains(concat(" ", normalize-space(@class), " "), " content-area ")]'
            )->item(0);
            if ($content_area) {
                $target = $content_area;
                $meta['target'] = 'content-area';
            }
        }

        if (!$target) {
            $GLOBALS['tmw_featured_models_relocate_meta'] = $meta;
            return '';
        }

        $fragment = new DOMDocument();
        libxml_use_internal_errors(true);
        $fragment_loaded = $fragment->loadHTML($markup, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        if (!$fragment_loaded) {
            $GLOBALS['tmw_featured_models_relocate_meta'] = $meta;
            return '';
        }

        $fragment_nodes = [];
        foreach ($fragment->childNodes as $child) {
            $fragment_nodes[] = $child;
        }

        foreach ($fragment_nodes as $child) {
            $target->appendChild($dom->importNode($child, true));
        }

        $output = $dom->saveHTML();
        $meta['output_length'] = is_string($output) ? strlen($output) : 0;
        $GLOBALS['tmw_featured_models_relocate_meta'] = $meta;

        return is_string($output) ? $output : '';
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

        $page_type = tmw_featured_models_get_page_type();
        $force = tmw_featured_models_is_force_relocate_context();

        if (!$force && strpos($buffer, '<!-- TMW-FEATURED-MODELS:START -->') !== false) {
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

        if ($force) {
            $relocated = tmw_featured_models_relocate_dom($buffer, $block_to_insert);
            $relocate_meta = isset($GLOBALS['tmw_featured_models_relocate_meta'])
                ? $GLOBALS['tmw_featured_models_relocate_meta']
                : [];
            if ($relocated !== '') {
                $removed = isset($relocate_meta['removed']) ? $relocate_meta['removed'] : [];
                $removed_total = 0;
                foreach ($removed as $count) {
                    $removed_total += (int) $count;
                }
                $target_label = isset($relocate_meta['target']) ? $relocate_meta['target'] : 'fallback';
                $output_length = isset($relocate_meta['output_length']) ? (int) $relocate_meta['output_length'] : strlen($relocated);

                tmw_featured_models_debug_log(
                    'TMW-FEATURED-RELOCATE',
                    'page_type=' . $page_type
                    . ' duplicates_removed=' . $removed_total
                    . ' target=' . $target_label
                    . ' done output_length=' . $output_length
                );

                $GLOBALS['tmw_featured_models_markup'] = '';
                return $relocated;
            }
        }

        $insert_pos = false;
        $log_anchor = 'skipped';

        $duplicate_counts = [
            'marker' => 0,
            'tmw-featured-models-wrap' => 0,
            'tmwfm-slot' => 0,
            'tmwfm-wrap' => 0,
            'widget' => 0,
        ];

        if ($force) {
            $buffer = tmw_featured_models_strip_duplicates_fallback($buffer, $duplicate_counts);
        }

        $insert_pos = tmw_featured_models_find_insertion_pos($buffer);
        $log_anchor = isset($GLOBALS['tmw_featured_models_insertion_anchor'])
            ? $GLOBALS['tmw_featured_models_insertion_anchor']
            : 'skipped';

        if ($insert_pos !== false) {
            $buffer = substr_replace($buffer, $block_to_insert, $insert_pos, 0);
        }

        if ($force) {
            $removed_total = 0;
            foreach ($duplicate_counts as $count) {
                $removed_total += (int) $count;
            }

            tmw_featured_models_debug_log(
                'TMW-FEATURED-INJECT',
                'page_type=' . $page_type
                . ' duplicates_removed=' . $removed_total
                . ' target=fallback'
                . ' anchor=' . $log_anchor
                . ' done output_length=' . strlen($buffer)
            );
        } else {
            tmw_featured_models_debug_log(
                'TMW-FEATURED-INJECT',
                'page_type=' . $page_type
                . ' target=fallback'
                . ' anchor=' . $log_anchor
                . ' done output_length=' . strlen($buffer)
            );
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

        $markup = tmw_featured_models_bootstrap_markup();
        if ($markup === '') {
            return;
        }

        $GLOBALS['tmw_featured_models_injector_started'] = true;
        $GLOBALS['tmw_featured_models_markup'] = $markup;

        ob_start('tmw_featured_models_injector_callback');
    }
}

add_action('template_redirect', 'tmw_featured_models_injector_start', 0);
