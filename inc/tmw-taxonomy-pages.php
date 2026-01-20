<?php
if (!defined('ABSPATH')) {
  exit;
}

if (!function_exists('tmw_register_taxonomy_pages_cpt')) {
  function tmw_register_taxonomy_pages_cpt(): void {
    if (post_type_exists('tmw_tax_page')) {
      return;
    }

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
      'supports'        => ['title', 'editor', 'excerpt', 'thumbnail'],
      'capability_type' => 'post',
      'has_archive'     => false,
      'rewrite'         => false,
      'menu_icon'       => 'dashicons-admin-page',
    ]);
  }
}
add_action('init', 'tmw_register_taxonomy_pages_cpt');

if (!function_exists('tmw_taxpage_register_meta')) {
  function tmw_taxpage_register_meta(): void {
    register_post_meta('tmw_tax_page', '_tmw_taxonomy', [
      'type'              => 'string',
      'single'            => true,
      'show_in_rest'      => true,
      'sanitize_callback' => 'sanitize_key',
      'auth_callback'     => '__return_true',
    ]);

    register_post_meta('tmw_tax_page', '_tmw_term_id', [
      'type'              => 'integer',
      'single'            => true,
      'show_in_rest'      => true,
      'sanitize_callback' => 'absint',
      'auth_callback'     => '__return_true',
    ]);

    register_post_meta('tmw_tax_page', '_tmw_term_slug', [
      'type'              => 'string',
      'single'            => true,
      'show_in_rest'      => true,
      'sanitize_callback' => 'sanitize_title',
      'auth_callback'     => '__return_true',
    ]);
  }
}
add_action('init', 'tmw_taxpage_register_meta');

if (!function_exists('tmw_taxpage_get_allowed_taxonomies')) {
  function tmw_taxpage_get_allowed_taxonomies(): array {
    return ['category', 'blog_category'];
  }
}

if (!function_exists('tmw_taxpage_debug_log')) {
  function tmw_taxpage_debug_log(string $message, string $tag): void {
    if (!defined('TMW_DEBUG') || !TMW_DEBUG) {
      return;
    }

    error_log(sprintf('%s %s', $tag, $message));
  }
}

if (!function_exists('tmw_taxpage_get_term_meta_value')) {
  function tmw_taxpage_get_term_meta_value(WP_Term $term, string $meta_key): string {
    $value = get_term_meta($term->term_id, $meta_key, true);
    return is_string($value) ? $value : '';
  }
}

if (!function_exists('tmw_taxpage_get_acf_term_field')) {
  function tmw_taxpage_get_acf_term_field(WP_Term $term, string $field_key): string {
    if (!function_exists('get_field')) {
      return '';
    }

    $value = get_field($field_key, $term);
    if (is_string($value) && trim($value) !== '') {
      return $value;
    }

    $value = get_field($field_key, sprintf('%s_%d', $term->taxonomy, $term->term_id));
    return is_string($value) ? $value : '';
  }
}

if (!function_exists('tmw_taxpage_find_linked_post')) {
  function tmw_taxpage_find_linked_post(WP_Term $term): int {
    $posts = get_posts([
      'post_type'      => 'tmw_tax_page',
      'post_status'    => ['publish', 'draft', 'pending', 'private'],
      'fields'         => 'ids',
      'posts_per_page' => 1,
      'no_found_rows'  => true,
      'meta_query'     => [
        [
          'key'   => '_tmw_taxonomy',
          'value' => $term->taxonomy,
        ],
        [
          'key'   => '_tmw_term_id',
          'value' => (string) $term->term_id,
        ],
      ],
    ]);

    if (!empty($posts)) {
      return (int) $posts[0];
    }

    $legacy_posts = get_posts([
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

    if (!empty($legacy_posts)) {
      return (int) $legacy_posts[0];
    }

    $posts = get_posts([
      'post_type'      => 'tmw_tax_page',
      'post_status'    => ['publish', 'draft', 'pending', 'private'],
      'fields'         => 'ids',
      'posts_per_page' => 1,
      'no_found_rows'  => true,
      'name'           => $term->slug,
    ]);

    return !empty($posts) ? (int) $posts[0] : 0;
  }
}

if (!function_exists('tmw_taxpage_sync_term')) {
  function tmw_taxpage_sync_term(int $term_id, string $taxonomy): void {
    if (!in_array($taxonomy, tmw_taxpage_get_allowed_taxonomies(), true)) {
      return;
    }

    $term = get_term($term_id, $taxonomy);
    if (!$term instanceof WP_Term) {
      return;
    }

    $linked_post_id = tmw_taxpage_find_linked_post($term);
    $was_created = false;

    if ($linked_post_id <= 0) {
      $linked_post_id = wp_insert_post([
        'post_type'   => 'tmw_tax_page',
        'post_status' => 'publish',
        'post_title'  => $term->name,
        'post_name'   => $term->slug,
      ]);

      if (is_wp_error($linked_post_id) || $linked_post_id <= 0) {
        return;
      }

      $was_created = true;
    }

    $post = get_post($linked_post_id);
    if (!$post instanceof WP_Post) {
      return;
    }

    $update_args = [
      'ID'         => $linked_post_id,
      'post_title' => $term->name,
      'post_name'  => $term->slug,
    ];

    $content = '';
    $excerpt = '';

    if (trim($post->post_content) === '') {
      $content = tmw_taxpage_get_term_meta_value($term, 'tmw_term_page_content');
      if ($content === '') {
        $content = tmw_taxpage_get_acf_term_field($term, 'page_content');
      }
      if ($content === '') {
        $content = term_description($term->term_id, $taxonomy);
      }
      $update_args['post_content'] = $content;
    }

    if (trim($post->post_excerpt) === '') {
      $excerpt = tmw_taxpage_get_term_meta_value($term, 'tmw_term_short_intro');
      if ($excerpt === '') {
        $excerpt = tmw_taxpage_get_acf_term_field($term, 'seo_intro');
      }
      $update_args['post_excerpt'] = $excerpt;
    }

    wp_update_post($update_args);

    update_post_meta($linked_post_id, '_tmw_taxonomy', $term->taxonomy);
    update_post_meta($linked_post_id, '_tmw_term_id', $term->term_id);
    update_post_meta($linked_post_id, '_tmw_term_slug', $term->slug);

    tmw_taxpage_debug_log(
      sprintf(
        '%s taxonomy page %d for %s:%d',
        $was_created ? 'Created' : 'Linked',
        $linked_post_id,
        $term->taxonomy,
        $term->term_id
      ),
      '[TMW-TAXPAGES]'
    );
  }
}

add_action('created_term', function (int $term_id, int $tt_id, string $taxonomy) {
  tmw_taxpage_sync_term($term_id, $taxonomy);
}, 10, 3);

add_action('edited_term', function (int $term_id, int $tt_id, string $taxonomy) {
  tmw_taxpage_sync_term($term_id, $taxonomy);
}, 10, 3);

if (!function_exists('tmw_taxpage_get_linked_post_id')) {
  function tmw_taxpage_get_linked_post_id($term): int {
    if (!$term instanceof WP_Term) {
      return 0;
    }

    $post_id = tmw_taxpage_find_linked_post($term);
    if ($post_id <= 0) {
      return 0;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post || $post->post_type !== 'tmw_tax_page' || $post->post_status === 'trash') {
      return 0;
    }

    return $post_id;
  }
}

if (!function_exists('tmw_taxpage_render_linked_term_metabox')) {
  function tmw_taxpage_render_linked_term_metabox(WP_Post $post): void {
    wp_nonce_field('tmw_taxpage_linked_term_save', 'tmw_taxpage_linked_term_nonce');

    $allowed_taxonomies = tmw_taxpage_get_allowed_taxonomies();
    $selected_taxonomy = get_post_meta($post->ID, '_tmw_taxonomy', true);
    $selected_taxonomy = is_string($selected_taxonomy) ? $selected_taxonomy : '';

    $selected_term_id = (int) get_post_meta($post->ID, '_tmw_term_id', true);

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
    <?php
  }
}

if (!function_exists('tmw_taxpage_render_tools_metabox')) {
  function tmw_taxpage_render_tools_metabox(WP_Post $post): void {
    do_action('tmw_tax_page_tools_metabox', $post);
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

  $previous_taxonomy = get_post_meta($post_id, '_tmw_taxonomy', true);
  $previous_taxonomy = is_string($previous_taxonomy) ? $previous_taxonomy : '';
  $previous_term_id = (int) get_post_meta($post_id, '_tmw_term_id', true);

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

  if ($taxonomy !== '' && $term_id > 0 && $term instanceof WP_Term) {
    update_post_meta($post_id, '_tmw_taxonomy', $taxonomy);
    update_post_meta($post_id, '_tmw_term_id', $term_id);
    update_post_meta($post_id, '_tmw_term_slug', $term->slug);
  } else {
    delete_post_meta($post_id, '_tmw_taxonomy');
    delete_post_meta($post_id, '_tmw_term_id');
    delete_post_meta($post_id, '_tmw_term_slug');
  }
});

add_action('admin_init', function () {
  remove_action('category_add_form_fields', 'tmw_category_term_editor_add_fields');
  remove_action('category_edit_form_fields', 'tmw_category_term_editor_edit_fields');
});

add_filter('post_type_link', function (string $permalink, WP_Post $post): string {
  if ($post->post_type !== 'tmw_tax_page') {
    return $permalink;
  }

  $taxonomy = get_post_meta($post->ID, '_tmw_taxonomy', true);
  $taxonomy = is_string($taxonomy) ? $taxonomy : '';
  $term_id = (int) get_post_meta($post->ID, '_tmw_term_id', true);

  if ($taxonomy === '' || $term_id <= 0) {
    $legacy_taxonomy = get_post_meta($post->ID, '_tmw_taxpage_taxonomy', true);
    $legacy_term_id = (int) get_post_meta($post->ID, '_tmw_taxpage_term_id', true);
    $taxonomy = $taxonomy ?: (is_string($legacy_taxonomy) ? $legacy_taxonomy : '');
    $term_id = $term_id ?: $legacy_term_id;
  }

  if ($taxonomy === '' || $term_id <= 0) {
    return $permalink;
  }

  $term = get_term($term_id, $taxonomy);
  if (!$term instanceof WP_Term) {
    return $permalink;
  }

  $term_link = get_term_link($term);
  if (is_wp_error($term_link) || !is_string($term_link)) {
    return $permalink;
  }

  return $term_link;
}, 10, 2);

add_action('template_redirect', function () {
  if (!is_singular('tmw_tax_page')) {
    return;
  }

  $post_id = get_queried_object_id();
  if ($post_id <= 0) {
    return;
  }

  $taxonomy = get_post_meta($post_id, '_tmw_taxonomy', true);
  $taxonomy = is_string($taxonomy) ? $taxonomy : '';
  $term_id = (int) get_post_meta($post_id, '_tmw_term_id', true);

  if ($taxonomy === '' || $term_id <= 0) {
    $legacy_taxonomy = get_post_meta($post_id, '_tmw_taxpage_taxonomy', true);
    $legacy_term_id = (int) get_post_meta($post_id, '_tmw_taxpage_term_id', true);
    $taxonomy = $taxonomy ?: (is_string($legacy_taxonomy) ? $legacy_taxonomy : '');
    $term_id = $term_id ?: $legacy_term_id;
  }

  if ($taxonomy === '' || $term_id <= 0) {
    return;
  }

  $term = get_term($term_id, $taxonomy);
  if (!$term instanceof WP_Term) {
    return;
  }

  $term_link = get_term_link($term);
  if (is_wp_error($term_link) || !is_string($term_link)) {
    return;
  }

  tmw_taxpage_debug_log(
    sprintf('Redirected tmw_tax_page %d to %s:%d', $post_id, $taxonomy, $term_id),
    '[TMW-TAXPAGES]'
  );
  wp_safe_redirect($term_link, 301);
  exit;
});

add_filter('rank_math/metabox/post_types', function (array $post_types): array {
  static $logged = false;

  if (!in_array('tmw_tax_page', $post_types, true)) {
    $post_types[] = 'tmw_tax_page';
    if (!$logged) {
      tmw_taxpage_debug_log('Injected tmw_tax_page into RankMath metabox types.', '[TMW-TAXPAGES-RM]');
      $logged = true;
    }
  }

  return $post_types;
});
