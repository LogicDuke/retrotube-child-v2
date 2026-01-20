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

if (!function_exists('tmw_tpa_get_term_ref')) {
  /**
   * Build the ACF term reference string for the queried term.
   *
   * @return string|null
   */
  function tmw_tpa_get_term_ref(): ?string {
    $term = get_queried_object();
    if (!$term instanceof WP_Term) {
      return null;
    }

    return sprintf('%s_%d', $term->taxonomy, $term->term_id);
  }
}

if (!function_exists('tmw_tpa_filter_archive_title')) {
  /**
   * [TMW-TAX-PAGE] Override archive titles with ACF SEO H1.
   *
   * @param string $title Archive title.
   * @return string
   */
  function tmw_tpa_filter_archive_title(string $title): string {
    if (!tmw_tpa_is_target_archive()) {
      return $title;
    }

    if (!function_exists('get_field')) {
      return $title;
    }

    $term_ref = tmw_tpa_get_term_ref();
    if ($term_ref === null) {
      return $title;
    }

    $override = get_field('seo_h1', $term_ref);
    if (!is_string($override) || trim($override) === '') {
      return $title;
    }

    return esc_html($override);
  }
}

add_filter('get_the_archive_title', 'tmw_tpa_filter_archive_title', 9);

if (!function_exists('tmw_tpa_filter_archive_description')) {
  /**
   * [TMW-TAX-PAGE-ACF] Merge ACF taxonomy page content into the archive description.
   *
   * @param string $description Archive description HTML.
   * @return string
   */
  function tmw_tpa_filter_archive_description(string $description): string {
    if (!tmw_tpa_is_target_archive()) {
      return $description;
    }

    if (!function_exists('get_field')) {
      return $description;
    }

    $term = get_queried_object();
    if (!$term instanceof WP_Term) {
      return $description;
    }

    $term_ref = sprintf('%s_%d', $term->taxonomy, $term->term_id);

    $chunks = [];
    $seo_intro = get_field('seo_intro', $term_ref);
    if (is_string($seo_intro) && trim($seo_intro) !== '') {
      $intro_html = apply_filters('the_content', $seo_intro);
      if (function_exists('tmw_sanitize_accordion_html')) {
        $intro_html = tmw_sanitize_accordion_html($intro_html);
      }
      $chunks[] = $intro_html;
    }

    $page_content = get_field('page_content', $term_ref);
    if (is_string($page_content) && trim($page_content) !== '') {
      $content_html = apply_filters('the_content', $page_content);
      if (function_exists('tmw_sanitize_accordion_html')) {
        $content_html = tmw_sanitize_accordion_html($content_html);
      }
      $chunks[] = $content_html;
    }

    if (trim(wp_strip_all_tags($description)) !== '') {
      $chunks[] = $description;
    }

    $combined_html = trim(implode("\n", array_filter($chunks)));
    if (trim(wp_strip_all_tags($combined_html)) === '') {
      return $description;
    }

    $lines = (int) apply_filters('tmw_taxonomy_page_lines', 1, $term->taxonomy, $term->term_id);

    // [TMW-TAX-PAGE-ACC] Render a unified accordion for taxonomy page content.
    return tmw_render_accordion([
      'content_html'    => $combined_html,
      'lines'           => $lines,
      'collapsed'       => true,
      'accordion_class' => 'tmw-accordion--taxonomy-page',
      'id_base'         => sprintf('tmw-tax-page-%s-%d-', $term->taxonomy, $term->term_id),
    ]);
  }
}

add_filter('get_the_archive_description', 'tmw_tpa_filter_archive_description', 9);
