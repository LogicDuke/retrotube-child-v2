<?php
/**
 * Archive template for Models CPT
 * Safely reuses the Models Flipboxes template with archive header.
 */

get_header();

if (!wp_style_is('rt-child-flip', 'enqueued')) {
  if (!wp_style_is('rt-child-flip', 'registered')) {
    $flipboxes_path = get_stylesheet_directory() . '/assets/flipboxes.css';
    $flipboxes_ver  = file_exists($flipboxes_path) ? filemtime($flipboxes_path) : tmw_child_style_version();

    wp_register_style(
      'rt-child-flip',
      get_stylesheet_directory_uri() . '/assets/flipboxes.css',
      ['retrotube-child-style'],
      $flipboxes_ver
    );
  }

  wp_enqueue_style('rt-child-flip');
}

if (!wp_style_is('tmw-flip-a11y', 'registered')) {
  $a11y_style_path = get_stylesheet_directory() . '/css/a11y.css';
  if (file_exists($a11y_style_path)) {
    $a11y_style_ver = filemtime($a11y_style_path) ?: tmw_child_style_version();
    wp_register_style(
      'tmw-flip-a11y',
      get_stylesheet_directory_uri() . '/css/a11y.css',
      ['retrotube-child-style'],
      $a11y_style_ver
    );
  }
}

if (wp_style_is('tmw-flip-a11y', 'registered')) {
  wp_enqueue_style('tmw-flip-a11y');
}

$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$per_page = 16;

$model_query = new WP_Query([
  'post_type'              => 'model',
  'post_status'            => 'publish',
  'posts_per_page'         => $per_page,
  'paged'                  => $paged,
  'no_found_rows'          => false,
  'update_post_meta_cache' => true,
  'update_post_term_cache' => false,
]);

if ($model_query->have_posts()) {
  update_post_thumbnail_cache($model_query);

  if (function_exists('tmw_prime_model_acf_cache_for_posts')) {
    tmw_prime_model_acf_cache_for_posts(wp_list_pluck($model_query->posts, 'ID'));
  }
}
?>
<main id="primary" class="site-main">
  <div class="tmw-layout container">
    <section class="tmw-content" data-mobile-guard="true">
      <header class="entry-header">
        <h1 class="widget-title"><span class="tmw-star">★</span> Models</h1>
      </header>
      <?php if ($model_query->have_posts()) : ?>
        <div class="tmw-grid tmw-cols-4">
          <?php
          while ($model_query->have_posts()) :
            $model_query->the_post();
            $model_id = get_the_ID();
            $name = get_the_title();
            $link = get_permalink();
            $front_url = get_the_post_thumbnail_url($model_id, 'medium');
            if (!$front_url && function_exists('tmw_placeholder_image_url')) {
              $front_url = tmw_placeholder_image_url();
            }

            $back_url = $front_url;
            $front_style = function_exists('tmw_bg_style')
              ? tmw_bg_style($front_url)
              : 'background-image:url(' . esc_url($front_url) . ');';
            $back_style = function_exists('tmw_bg_style')
              ? tmw_bg_style($back_url)
              : 'background-image:url(' . esc_url($back_url) . ');';

            $card_attrs = [];
            if (function_exists('tmw_flipbox_a11y_attrs')) {
              $card_attrs = tmw_flipbox_a11y_attrs([
                'aria_label' => $name,
                'tabindex'   => 0,
              ]);
            }

            if ($link) {
              $card_attrs[] = 'data-href="' . esc_url($link) . '"';
            }

            $card_attr_html = '';
            if (!empty($card_attrs)) {
              $card_attr_html = ' ' . implode(' ', array_map('trim', $card_attrs));
            }

            $sr_label = function_exists('tmw_sr_text')
              ? tmw_sr_text(sprintf(__('Open %s profile', 'retrotube-child'), $name))
              : '';

            $use_lcp_img = function_exists('tmw_child_should_use_lcp_image') && tmw_child_should_use_lcp_image();
            $front_img = function_exists('tmw_child_flipbox_front_image_markup')
              ? tmw_child_flipbox_front_image_markup($front_url, $name, $use_lcp_img)
              : '';
            ?>
            <div class="tmw-flip"<?php echo $card_attr_html; ?>>
              <div class="tmw-flip-inner">
                <div class="tmw-flip-front" style="<?php echo esc_attr($front_style); ?>">
                  <?php echo $front_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                  <span class="tmw-name"><?php echo esc_html($name); ?></span>
                </div>
                <div class="tmw-flip-back" style="<?php echo esc_attr($back_style); ?>">
                  <?php if ($link) : ?>
                    <a href="<?php echo esc_url($link); ?>" data-href="<?php echo esc_url($link); ?>" class="tmw-view" style="display:inline-block; text-decoration:none; color:inherit;"><?php echo $sr_label; ?>View profile &raquo;&raquo;&raquo;</a>
                  <?php else : ?>
                    <span class="tmw-view"><?php echo $sr_label; ?>View profile &raquo;&raquo;&raquo;</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
        <?php
        $pagination = paginate_links([
          'current'   => $paged,
          'total'     => (int) $model_query->max_num_pages,
          'type'      => 'list',
          'prev_text' => __('&laquo; Previous', 'retrotube-child'),
          'next_text' => __('Next &raquo;', 'retrotube-child'),
        ]);
        if ($pagination) :
          ?>
          <nav class="tmw-pagination" aria-label="<?php esc_attr_e('Models pagination', 'retrotube-child'); ?>">
            <?php echo $pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
          </nav>
        <?php endif; ?>
      <?php else : ?>
        <p><?php esc_html_e('No models found.', 'retrotube-child'); ?></p>
      <?php endif; ?>
    </section>
    <aside class="tmw-sidebar">
      <?php get_sidebar(); ?>
    </aside>
  </div>
</main>
<?php
wp_reset_postdata();
get_footer();
?>
