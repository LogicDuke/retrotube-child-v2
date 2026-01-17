<?php
/**
 * Template Name: Models Grid (Flipbox)
 * Description: Shows the models flipbox grid with pagination and sidebar.
 */
get_header();
?>
<div class="tmw-title"><span class="tmw-star">★</span>Models</div>
<div class="tmw-layout">
  <main id="primary" class="site-main" data-mobile-guard="true">
    <?php
      if (is_front_page() || is_home()) :
        if (have_posts()) :
          while (have_posts()) :
            the_post();
            get_template_part('template-parts/content', 'model');
          endwhile;

          the_posts_navigation();
        endif;
      else :
        echo tmw_models_flipboxes_cb([
          'per_page'        => 12,
          'cols'            => 3,
          'show_pagination' => true,
          'page_var'        => 'pg',
        ]);
      endif;
    ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
