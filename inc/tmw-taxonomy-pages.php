<?php
if (!defined('ABSPATH')) {
  exit;
}

if (!function_exists('tmw_taxpage_debug_log')) {
  function tmw_taxpage_debug_log(string $message, string $tag = '[TMW-TAXPAGE]'): void {
    if (!defined('TMW_DEBUG') || !TMW_DEBUG) {
      return;
    }

    error_log(sprintf('%s %s', $tag, $message));
  }
}

if (!function_exists('tmw_register_taxonomy_pages_cpt')) {
  function tmw_register_taxonomy_pages_cpt(): void {
    $labels = [
      'name'               => __('Taxonomy Pages', 'retrotube-child'),
      'singular_name'      => __('Taxonomy Page', 'retrotube-child'),
      'add_new_item'       => __('Add New Taxonomy Page', 'retrotube-child'),
      'edit_item'          => __('Edit Taxonomy Page', 'retrotube-child'),
      'new_item'           => __('New Taxonomy Page', 'retrotube-child'),
      'view_item'          => __('View Taxonomy Page', 'retrotube-child'),
      'search_items'       => __('Search Taxonomy Pages', 'retrotube-child'),
      'not_found'          => __('No Taxonomy Pages found', 'retrotube-child'),
      'not_found_in_trash' => __('No Taxonomy Pages found in Trash', 'retrotube-child'),
      'menu_name'          => __('Taxonomy Pages', 'retrotube-child'),
    ];

    register_post_type('tmw_tax_page', [
      'labels'          => $labels,
      'public'          => false,
      'show_ui'         => true,
      'show_in_menu'    => true,
      'show_in_rest'    => true,
      'menu_position'   => 25,
      'supports'        => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
      'capability_type' => 'post',
      'has_archive'     => false,
      'rewrite'         => false,
      'menu_icon'       => 'dashicons-admin-page',
    ]);
  }
}
add_action('init', 'tmw_register_taxonomy_pages_cpt');

if (!function_exists('tmw_taxpage_get_allowed_taxonomies')) {
  function tmw_taxpage_get_allowed_taxonomies(): array {
    return ['category', 'blog_category'];
  }
}

if (!function_exists('tmw_taxpage_get_linked_post_id')) {
  function tmw_taxpage_get_linked_post_id($term): int {
    if (!$term instanceof WP_Term) {
      return 0;
    }

    $post_id = (int) get_term_meta($term->term_id, 'tmw_taxpage_post_id', true);
    if ($post_id > 0) {
      $post = get_post($post_id);
      if ($post instanceof WP_Post && $post->post_type === 'tmw_tax_page' && $post->post_status !== 'trash') {
        return $post_id;
      }

      delete_term_meta($term->term_id, 'tmw_taxpage_post_id');
    }

    $posts = get_posts([
      'post_type'      => 'tmw_tax_page',
      'post_status'    => ['publish', 'draft', 'pending', 'private'],
      'fields'         => 'ids',
      'posts_per_page' => 1,
      'no_found_rows'  => true,
      'meta_query'     => [
        [
          'key'   => '_tmw_taxpage_taxonomy',
          'value' => $term->taxonomy,
        ],
        [
          'key'   => '_tmw_taxpage_term_id',
          'value' => (string) $term->term_id,
        ],
      ],
    ]);

    $fallback_id = !empty($posts) ? (int) $posts[0] : 0;
    if ($fallback_id > 0) {
      update_term_meta($term->term_id, 'tmw_taxpage_post_id', $fallback_id);
      tmw_taxpage_debug_log(
        sprintf('Recovered mapping for %s:%d -> post %d', $term->taxonomy, $term->term_id, $fallback_id),
        '[TMW-TAXPAGE-MAP]'
      );
    }

    return $fallback_id;
  }
}

if (!function_exists('tmw_taxpage_render_linked_term_metabox')) {
  function tmw_taxpage_render_linked_term_metabox(WP_Post $post): void {
    wp_nonce_field('tmw_taxpage_linked_term_save', 'tmw_taxpage_linked_term_nonce');

    $allowed_taxonomies = tmw_taxpage_get_allowed_taxonomies();
    $selected_taxonomy = get_post_meta($post->ID, '_tmw_taxpage_taxonomy', true);
    $selected_taxonomy = is_string($selected_taxonomy) ? $selected_taxonomy : '';

    $selected_term_id = (int) get_post_meta($post->ID, '_tmw_taxpage_term_id', true);

    if ($selected_taxonomy === '' && isset($_GET['tmw_tax'])) {
      $selected_taxonomy = sanitize_key(wp_unslash($_GET['tmw_tax']));
    }

    if (!$selected_term_id && isset($_GET['tmw_term_id'])) {
      $selected_term_id = (int) $_GET['tmw_term_id'];
    }

    if (!in_array($selected_taxonomy, $allowed_taxonomies, true)) {
      $selected_taxonomy = $allowed_taxonomies[0];
    }

    $terms = get_terms([
      'taxonomy'   => $selected_taxonomy,
      'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
      $terms = [];
    }
    ?>
    <p>
      <label for="tmw_taxpage_taxonomy" class="tmw-label"><strong><?php esc_html_e('Taxonomy', 'retrotube-child'); ?></strong></label>
      <select name="tmw_taxpage_taxonomy" id="tmw_taxpage_taxonomy" class="widefat">
        <?php foreach ($allowed_taxonomies as $taxonomy) : ?>
          <?php $taxonomy_obj = get_taxonomy($taxonomy); ?>
          <?php $taxonomy_label = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : $taxonomy; ?>
          <option value="<?php echo esc_attr($taxonomy); ?>" <?php selected($selected_taxonomy, $taxonomy); ?>>
            <?php echo esc_html($taxonomy_label); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </p>
    <p>
      <label for="tmw_taxpage_term_id" class="tmw-label"><strong><?php esc_html_e('Term', 'retrotube-child'); ?></strong></label>
      <select name="tmw_taxpage_term_id" id="tmw_taxpage_term_id" class="widefat">
        <option value="0"><?php esc_html_e('Select a term', 'retrotube-child'); ?></option>
        <?php foreach ($terms as $term) : ?>
          <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($selected_term_id, (int) $term->term_id); ?>>
            <?php echo esc_html($term->name); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </p>
    <p class="description">
      <?php esc_html_e('Link this Gutenberg page to a taxonomy term. The term archive will render this content.', 'retrotube-child'); ?>
    </p>
    <?php
  }
}

if (!function_exists('tmw_taxpage_render_tools_metabox')) {
  function tmw_taxpage_render_tools_metabox(WP_Post $post): void {
    do_action('tmw_taxpage_tools_metabox', $post);
    ?>
    <p><?php esc_html_e('TMW Slot Machine integration area.', 'retrotube-child'); ?></p>
    <p><?php esc_html_e('TMW SEO Autopilot integration area.', 'retrotube-child'); ?></p>
    <?php
  }
}

add_action('add_meta_boxes_tmw_tax_page', function () {
  add_meta_box(
    'tmw-taxpage-linked-term',
    __('Linked Taxonomy Term', 'retrotube-child'),
    'tmw_taxpage_render_linked_term_metabox',
    'tmw_tax_page',
    'side',
    'high'
  );

  add_meta_box(
    'tmw-taxpage-tools',
    __('TMW Tools', 'retrotube-child'),
    'tmw_taxpage_render_tools_metabox',
    'tmw_tax_page',
    'side',
    'default'
  );
});

add_action('save_post_tmw_tax_page', function ($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return;
  }

  if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
    return;
  }

  if (!isset($_POST['tmw_taxpage_linked_term_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tmw_taxpage_linked_term_nonce'])), 'tmw_taxpage_linked_term_save')) {
    return;
  }

  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  $allowed_taxonomies = tmw_taxpage_get_allowed_taxonomies();
  $taxonomy = isset($_POST['tmw_taxpage_taxonomy']) ? sanitize_key(wp_unslash($_POST['tmw_taxpage_taxonomy'])) : '';
  $term_id = isset($_POST['tmw_taxpage_term_id']) ? (int) $_POST['tmw_taxpage_term_id'] : 0;

  $previous_taxonomy = get_post_meta($post_id, '_tmw_taxpage_taxonomy', true);
  $previous_taxonomy = is_string($previous_taxonomy) ? $previous_taxonomy : '';
  $previous_term_id = (int) get_post_meta($post_id, '_tmw_taxpage_term_id', true);

  if (!in_array($taxonomy, $allowed_taxonomies, true)) {
    $taxonomy = '';
  }

  $term = null;
  if ($taxonomy !== '' && $term_id > 0) {
    $term = get_term($term_id, $taxonomy);
    if (!$term instanceof WP_Term) {
      $term = null;
      $term_id = 0;
    }
  }

  if ($previous_taxonomy && $previous_term_id && ($previous_taxonomy !== $taxonomy || $previous_term_id !== $term_id)) {
    $previous_term = get_term($previous_term_id, $previous_taxonomy);
    if ($previous_term instanceof WP_Term) {
      $linked_post_id = (int) get_term_meta($previous_term->term_id, 'tmw_taxpage_post_id', true);
      if ($linked_post_id === (int) $post_id) {
        delete_term_meta($previous_term->term_id, 'tmw_taxpage_post_id');
        tmw_taxpage_debug_log(
          sprintf('Cleared mapping for %s:%d from post %d', $previous_taxonomy, $previous_term_id, $post_id),
          '[TMW-TAXPAGE-MAP]'
        );
      }
    }
  }

  if ($taxonomy !== '' && $term_id > 0 && $term instanceof WP_Term) {
    update_post_meta($post_id, '_tmw_taxpage_taxonomy', $taxonomy);
    update_post_meta($post_id, '_tmw_taxpage_term_id', $term_id);
    update_term_meta($term_id, 'tmw_taxpage_post_id', $post_id);

    tmw_taxpage_debug_log(
      sprintf('Mapped post %d -> %s:%d', $post_id, $taxonomy, $term_id),
      '[TMW-TAXPAGE-MAP]'
    );
  } else {
    delete_post_meta($post_id, '_tmw_taxpage_taxonomy');
    delete_post_meta($post_id, '_tmw_taxpage_term_id');

    tmw_taxpage_debug_log(
      sprintf('Missing or invalid mapping for post %d', $post_id),
      '[TMW-TAXPAGE-MAP]'
    );
  }
});

add_action('admin_notices', function () {
  if (!is_admin()) {
    return;
  }

  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || $screen->base !== 'term' || empty($screen->taxonomy)) {
    return;
  }

  $allowed_taxonomies = tmw_taxpage_get_allowed_taxonomies();
  if (!in_array($screen->taxonomy, $allowed_taxonomies, true)) {
    return;
  }

  $term_id = isset($_GET['tag_ID']) ? (int) $_GET['tag_ID'] : 0;
  if ($term_id <= 0) {
    return;
  }

  $term = get_term($term_id, $screen->taxonomy);
  if (!$term instanceof WP_Term) {
    return;
  }

  $linked_post_id = tmw_taxpage_get_linked_post_id($term);
  $edit_url = '';
  $label = '';

  if ($linked_post_id > 0) {
    $edit_url = get_edit_post_link($linked_post_id, '');
    $label = __('Edit SEO Page', 'retrotube-child');
  } else {
    $edit_url = add_query_arg([
      'post_type'   => 'tmw_tax_page',
      'tmw_tax'     => $term->taxonomy,
      'tmw_term_id' => $term->term_id,
    ], admin_url('post-new.php'));
    $label = __('Create SEO Page', 'retrotube-child');
  }

  if (!$edit_url) {
    return;
  }

  tmw_taxpage_debug_log(
    sprintf('Rendered admin link for %s:%d (post %d)', $term->taxonomy, $term->term_id, $linked_post_id),
    '[TMW-TAXPAGE-ADMIN]'
  );
  ?>
  <div class="notice notice-info tmw-taxpage-admin-notice">
    <p>
      <strong><?php esc_html_e('Taxonomy SEO Page', 'retrotube-child'); ?></strong>
      &mdash;
      <?php echo esc_html($term->name); ?>
      <a class="button button-primary" style="margin-left:10px;" href="<?php echo esc_url($edit_url); ?>">
        <?php echo esc_html($label); ?>
      </a>
    </p>
  </div>
  <?php
});
