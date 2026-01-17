<?php
/**
 * Front page template override for Retrotube Child theme.
 */

get_header();

tmw_render_sidebar_layout('front-page', function () {
    ?>
      <?php if (have_posts()) : ?>
        <div class="video-grid tmw-home-video-grid">
          <?php while (have_posts()) : the_post(); ?>
            <?php get_template_part('template-parts/content', get_post_type()); ?>
          <?php endwhile; ?>
        </div>

        <?php
        the_posts_pagination([
            'mid_size'  => 2,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);
        ?>
      <?php else : ?>
        <?php get_template_part('template-parts/content', 'none'); ?>
      <?php endif; ?>
    <?php
});

get_footer();
