<?php
/**
 * Tag archive template override for Retrotube Child theme.
 *
 * Uses child theme layout to ensure Featured Models renders correctly at the bottom of the left column.
 */

get_header();

tmw_render_sidebar_layout('tag-archive', function () {
    ?>
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/content', get_post_type()); ?>
        <?php endwhile; ?>

        <?php the_posts_navigation(); ?>
      <?php else : ?>
        <?php get_template_part('template-parts/content', 'none'); ?>
      <?php endif; ?>
    <?php
});

get_footer();
