<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('tmw_tpa_is_target_archive')) {
  /**
   * Check whether the current request is a target taxonomy archive.
   *
   * @return bool
   */
  function tmw_tpa_is_target_archive(): bool {
    if (is_admin() || wp_doing_ajax() || is_feed()) {
      return false;
    }

    return is_category() || is_tax('blog_category');
  }
}

if (!function_exists('tmw_tpa_get_linked_post')) {
  /**
   * Resolve the linked taxonomy page post for the current term.
   *
   * @return WP_Post|null
   */
  function tmw_tpa_get_linked_post(): ?WP_Post {
    $term = get_queried_object();
    if (!$term instanceof WP_Term) {
      return null;
    }

    if (!function_exists('tmw_taxpage_get_linked_post_id')) {
      return null;
    }

    $post_id = tmw_taxpage_get_linked_post_id($term);
    if ($post_id <= 0) {
      return null;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
      return null;
    }

    return $post;
  }
}

if (!function_exists('tmw_tpa_filter_archive_title')) {
  /**
   * [TMW-TAXPAGES] Override archive titles with linked taxonomy page title.
   *
   * @param string $title Archive title.
   * @return string
   */
  function tmw_tpa_filter_archive_title(string $title): string {
    if (!tmw_tpa_is_target_archive()) {
      return $title;
    }

    $post = tmw_tpa_get_linked_post();
    if (!$post) {
      return $title;
    }

    return esc_html($post->post_title);
  }
}

add_filter('get_the_archive_title', 'tmw_tpa_filter_archive_title', 9);

if (!function_exists('tmw_tpa_filter_archive_description')) {
  /**
   * [TMW-TAXPAGES] Render linked taxonomy page content above the video grid.
   *
   * @param string $description Archive description HTML.
   * @return string
   */
  function tmw_tpa_filter_archive_description(string $description): string {
    if (!tmw_tpa_is_target_archive()) {
      return $description;
    }

    $term = get_queried_object();
    if (!$term instanceof WP_Term) {
      return $description;
    }

    $post = tmw_tpa_get_linked_post();
    if (!$post) {
      return $description;
    }

    $content = '';
    if (!empty($post->post_excerpt)) {
      $content .= apply_filters('the_excerpt', $post->post_excerpt);
    }

    if (!empty($post->post_content)) {
      $content .= apply_filters('the_content', $post->post_content);
    }

    if (trim(wp_strip_all_tags($content)) === '') {
      return $description;
    }

    $lines = (int) apply_filters('tmw_taxonomy_page_lines', 1, $term->taxonomy, $term->term_id);

    return tmw_render_accordion([
      'content_html'    => $content,
      'lines'           => $lines,
      'collapsed'       => true,
      'accordion_class' => 'tmw-accordion--taxonomy-page',
      'id_base'         => sprintf('tmw-tax-page-%s-%d-', $term->taxonomy, $term->term_id),
    ]);
  }
}

add_filter('get_the_archive_description', 'tmw_tpa_filter_archive_description', 9);
