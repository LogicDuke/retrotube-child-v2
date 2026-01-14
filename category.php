<?php
/**
 * Category archive template for Retrotube Child theme.
 *
 * Uses parent template for video rendering, then injects Featured Models
 * INSIDE the main content area (before </main>), not outside it.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render Featured Models markup for injection.
 */
function tmw_category_get_featured_models_markup() {
    if (!function_exists('tmw_featured_models_should_inject') || !tmw_featured_models_should_inject()) {
        return '';
    }

    $shortcode = function_exists('tmw_get_featured_shortcode_for_context')
        ? tmw_get_featured_shortcode_for_context()
        : '[tmw_featured_models]';

    if (function_exists('tmw_clean_featured_shortcode')) {
        $shortcode = tmw_clean_featured_shortcode($shortcode);
    }

    if (!is_string($shortcode) || trim($shortcode) === '') {
        return '';
    }

    $output = do_shortcode($shortcode);
    if (!is_string($output) || trim($output) === '') {
        return '';
    }

    return '<!-- TMW-FEATURED-MODELS --><div class="tmwfm-slot" data-tmwfm="wrap">' . $output . '</div>';
}

/**
 * Find the correct </main> position - the one that closes the main content area.
 * Uses the sidebar position as reference to ensure we find the right closing tag.
 */
function tmw_category_find_main_close_position($html) {
    // Find the sidebar start position (we want to insert BEFORE this, inside main)
    $aside_pos = stripos($html, '<aside');

    if ($aside_pos !== false) {
        // Search only in the content BEFORE the sidebar
        $content_before_sidebar = substr($html, 0, $aside_pos);

        // Find the LAST </main> before the sidebar - this is the main content closing tag
        $last_main_close = strripos($content_before_sidebar, '</main>');
        if ($last_main_close !== false) {
            return $last_main_close;
        }
    }

    // Fallback: use the existing function if available (from tmw-featured-models-inject.php)
    if (function_exists('tmw_featured_models_find_main_close_pos')) {
        return tmw_featured_models_find_main_close_pos($html);
    }

    // Last resort: find the last </main> in the entire document
    $last_main = strripos($html, '</main>');
    if ($last_main !== false) {
        return $last_main;
    }

    return false;
}

/**
 * Inject Featured Models into HTML at the correct position (inside <main>, before </main>).
 */
function tmw_category_inject_featured_models($html) {
    // Skip if already has marker (prevent duplicate injection)
    if (strpos($html, '<!-- TMW-FEATURED-MODELS -->') !== false) {
        return $html;
    }

    $fm_markup = tmw_category_get_featured_models_markup();
    if ($fm_markup === '') {
        return $html;
    }

    // Find the correct </main> position
    $main_close_pos = tmw_category_find_main_close_position($html);

    if ($main_close_pos !== false) {
        // Insert BEFORE </main> - this keeps Featured Models INSIDE the main content area
        return substr($html, 0, $main_close_pos) . $fm_markup . substr($html, $main_close_pos);
    }

    // Fallback: insert before footer
    $footer_pos = stripos($html, '<footer');
    if ($footer_pos !== false) {
        return substr($html, 0, $footer_pos) . $fm_markup . substr($html, $footer_pos);
    }

    // Ultimate fallback: append before </body>
    $body_close = strripos($html, '</body>');
    if ($body_close !== false) {
        return substr($html, 0, $body_close) . $fm_markup . substr($html, $body_close);
    }

    return $html . $fm_markup;
}

// === MAIN TEMPLATE LOGIC ===

// Try to use parent template (required for video grid rendering)
$parent_dir = trailingslashit(get_template_directory());
$parent_output = null;

foreach (['category.php', 'archive.php', 'index.php'] as $candidate) {
    $path = $parent_dir . $candidate;
    if (file_exists($path)) {
        ob_start();
        include $path;
        $captured = ob_get_clean();
        if ($captured !== false && $captured !== '') {
            $parent_output = $captured;
            break;
        }
    }
}

// If parent template rendered successfully
if ($parent_output !== null) {
    // Inject Featured Models at correct position (inside main) and output
    echo tmw_category_inject_featured_models($parent_output);
    return;
}

// === FALLBACK: Child theme rendering (if parent template fails) ===
get_header();

tmw_render_sidebar_layout('category-archive', function () {
    if (have_posts()) :
        ?>
        <header class="page-header">
            <?php
            the_archive_title('<h1 class="page-title">', '</h1>');
            the_archive_description('<div class="archive-description">', '</div>');
            ?>
        </header>
        <?php
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/content', get_post_type());
        endwhile;
        the_posts_navigation();
    else :
        get_template_part('template-parts/content', 'none');
    endif;

    // Featured Models in fallback (already inside main via tmw_render_sidebar_layout)
    echo '<!-- TMW-FEATURED-MODELS -->';
    $shortcode = function_exists('tmw_get_featured_shortcode_for_context')
        ? tmw_get_featured_shortcode_for_context()
        : '[tmw_featured_models]';
    if (function_exists('tmw_clean_featured_shortcode')) {
        $shortcode = tmw_clean_featured_shortcode($shortcode);
    }
    if (is_string($shortcode) && trim($shortcode) !== '') {
        $output = do_shortcode($shortcode);
        if (is_string($output) && $output !== '') {
            echo '<div class="tmwfm-slot" data-tmwfm="wrap">' . $output . '</div>';
        }
    }
});

get_footer();
