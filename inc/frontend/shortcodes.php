<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('tmw_render_accordion')) {
  /**
   * Render a reusable TMW accordion block.
   *
   * @param array $args Accordion arguments.
   * @return string Accordion HTML.
   */
  function tmw_render_accordion(array $args): string {
    $defaults = [
      'content_html'    => '',
      'lines'           => 20,
      'collapsed'       => true,
      'accordion_class' => '',
      'content_class'   => '',
      'toggle_class'    => '',
      'readmore_text'   => 'Read more',
      'close_text'      => 'Close',
      'id_base'         => '',
    ];

    $args = wp_parse_args($args, $defaults);

    $content_html = (string) $args['content_html'];
    if (trim($content_html) === '') {
      return '';
    }

    $lines = max(1, (int) $args['lines']);
    $collapsed = (bool) $args['collapsed'];

    $accordion_classes = trim('tmw-accordion ' . $args['accordion_class']);
    $collapsed_class = $collapsed ? 'tmw-accordion-collapsed' : '';
    $content_classes = trim('tmw-accordion-content ' . $args['content_class'] . ' ' . $collapsed_class);
    $toggle_classes = trim('tmw-accordion-toggle ' . $args['toggle_class']);

    $id_base = $args['id_base'] ? sanitize_html_class($args['id_base']) : 'tmw-accordion-';
    $content_id = wp_unique_id($id_base);

    $readmore_text = (string) $args['readmore_text'];
    $close_text = (string) $args['close_text'];
    $is_expanded = $collapsed ? 'false' : 'true';
    $toggle_text = $collapsed ? $readmore_text : $close_text;

    return sprintf(
      '<div class="%1$s"><div id="%2$s" class="%3$s" data-tmw-accordion-lines="%4$d">%5$s</div><div class="tmw-accordion-toggle-wrap"><a class="%6$s" href="javascript:void(0);" data-tmw-accordion-toggle aria-controls="%2$s" aria-expanded="%7$s" data-readmore-text="%8$s" data-close-text="%9$s"><span class="tmw-accordion-text">%10$s</span><i class="fa fa-chevron-down"></i></a></div></div>',
      esc_attr($accordion_classes),
      esc_attr($content_id),
      esc_attr($content_classes),
      $lines,
      $content_html,
      esc_attr($toggle_classes),
      esc_attr($is_expanded),
      esc_attr($readmore_text),
      esc_attr($close_text),
      esc_html($toggle_text)
    );
  }
}

if (!function_exists('tmw_render_title_bar')) {
  /**
   * Render a reusable TMW title bar.
   *
   * @param string $title Title text.
   * @param int $level Heading level.
   * @return string Title bar HTML.
   */
  function tmw_render_title_bar(string $title, int $level = 1): string {
    $heading_level = min(6, max(1, $level));

    return sprintf(
      '<div class="tmw-title"><span class="tmw-star">★</span><h%1$d class="tmw-title-text">%2$s</h%1$d></div>',
      $heading_level,
      esc_html($title)
    );
  }
}

if (!function_exists('tmw_accordion_shortcode')) {
  /**
   * Shortcode wrapper for the global TMW accordion.
   *
   * @param array $atts Shortcode attributes.
   * @param string|null $content Shortcode content.
   * @return string
   */
  function tmw_accordion_shortcode($atts, $content = null): string {
    $atts = shortcode_atts([
      'lines'     => '20',
      'collapsed' => '1',
      'class'     => '',
      'readmore'  => 'Read more',
      'close'     => 'Close',
    ], $atts, 'tmw_accordion');

    $collapsed = $atts['collapsed'] !== '0';
    $content_html = '';

    if ($content !== null) {
      $content_html = do_shortcode($content);
      $content_html = wpautop($content_html);
    }

    return tmw_render_accordion([
      'content_html'    => $content_html,
      'lines'           => (int) $atts['lines'],
      'collapsed'       => $collapsed,
      'accordion_class' => $atts['class'],
      'readmore_text'   => $atts['readmore'],
      'close_text'      => $atts['close'],
    ]);
  }
}

add_shortcode('tmw_accordion', 'tmw_accordion_shortcode');

if (!function_exists('tmw_category_archive_desc_to_accordion')) {
  /**
   * Wrap category descriptions in the unified accordion.
   *
   * @param string $description Archive description HTML.
   * @return string
   */
  function tmw_category_archive_desc_to_accordion(string $description): string {
    if (is_admin()) {
      return $description;
    }

    if (!is_category()) {
      return $description;
    }

    if (trim($description) === '') {
      return $description;
    }

    if (stripos($description, 'tmw-accordion') !== false) {
      return $description;
    }

    $lines = (int) apply_filters('tmw_category_desc_lines', 1);
    $queried_id = get_queried_object_id();

    return tmw_render_accordion([
      'content_html'    => $description,
      'lines'           => $lines,
      'collapsed'       => true,
      'accordion_class' => 'tmw-accordion--category-desc',
      'id_base'         => $queried_id ? 'tmw-category-desc-' . $queried_id : 'tmw-category-desc-',
    ]);
  }
}

add_filter('get_the_archive_description', 'tmw_category_archive_desc_to_accordion', 20);
