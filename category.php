<?php
/**
 * Category archive template for Retrotube Child theme.
 * Featured Models rendered explicitly at bottom of left column.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

tmw_render_sidebar_layout('category-archive', function () {
    ?>
    <?php if (have_posts()) : ?>
        <header class="page-header">
            <?php
            the_archive_title('<h1 class="page-title">', '</h1>');
            the_archive_description('<div class="archive-description">', '</div>');
            ?>
        </header>

        <?php while (have_posts()) : the_post(); ?>
            <?php get_template_part('template-parts/content', get_post_type()); ?>
        <?php endwhile; ?>

        <?php the_posts_navigation(); ?>
    <?php else : ?>
        <?php get_template_part('template-parts/content', 'none'); ?>
    <?php endif; ?>

    <?php
    // === FEATURED MODELS - Rendered explicitly at bottom of left column ===
    echo '<!-- TMW-FEATURED-MODELS -->';
    $fm_shortcode = function_exists('tmw_get_featured_shortcode_for_context')
        ? tmw_get_featured_shortcode_for_context()
        : '[tmw_featured_models]';
    if (function_exists('tmw_clean_featured_shortcode')) {
        $fm_shortcode = tmw_clean_featured_shortcode($fm_shortcode);
    }
    if (is_string($fm_shortcode) && trim($fm_shortcode) !== '') {
        $fm_output = do_shortcode($fm_shortcode);
        if (is_string($fm_output) && $fm_output !== '') {
            echo '<div class="tmwfm-slot" data-tmwfm="wrap">' . $fm_output . '</div>';
        }
    }
    ?>
    <?php
});

get_footer();
