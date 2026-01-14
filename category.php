<?php
/**
 * Category archive template for Retrotube Child theme.
 *
 * Uses parent template for video rendering, then injects Featured Models
 * at the correct position (bottom of left column, before sidebar).
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
 * Inject Featured Models into HTML at the correct position.
 */
function tmw_category_inject_featured_models($html) {
    // Skip if already has marker
    if (strpos($html, '<!-- TMW-FEATURED-MODELS -->') !== false) {
        return $html;
    }

    $fm_markup = tmw_category_get_featured_models_markup();
    if ($fm_markup === '') {
        return $html;
    }

    // Try insertion points in order of preference:
    // 1. Before <aside (sidebar) - most reliable for two-column layouts
    // 2. Before <footer
    // 3. Before </body>

    $insertion_patterns = [
        '~(<aside[\s>])~i',           // Before sidebar
        '~(<footer[\s>])~i',          // Before footer
        '~(</body>)~i',               // Before body close (last resort)
    ];

    foreach ($insertion_patterns as $pattern) {
        if (preg_match($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[1][1];
            return substr($html, 0, $pos) . $fm_markup . substr($html, $pos);
        }
    }

    // Absolute fallback: append to end
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
    // Inject Featured Models at correct position and output
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

    // Featured Models in fallback
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
