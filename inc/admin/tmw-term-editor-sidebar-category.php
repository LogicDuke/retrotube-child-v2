<?php
if (!defined('ABSPATH')) { exit; }

add_action('admin_enqueue_scripts', function($hook) {
  if ($hook !== 'term.php') {
    return;
  }

  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || !in_array($screen->taxonomy, ['category', 'blog_category'], true)) {
    return;
  }

  wp_enqueue_style('tmw-term-editor-sidebar-category', false, [], null);
  wp_add_inline_style('tmw-term-editor-sidebar-category', '
    .tmw-term-two-col { display:flex; gap:24px; align-items:flex-start; }
    .tmw-term-two-col .tmw-term-main { flex:1; min-width:0; }
    .tmw-term-two-col .tmw-term-right { width:360px; flex:0 0 360px; }
    @media (max-width: 1024px) {
      .tmw-term-two-col { flex-direction:column; }
      .tmw-term-two-col .tmw-term-right { width:100%; flex:1 1 auto; }
    }
  ');

  wp_enqueue_script('jquery');
  wp_add_inline_script('jquery', "
    jQuery(function($) {
      function buildLayout() {
        var \$wrap = $('.wrap').first();
        var \$form = $('#edittag');
        if (!\$wrap.length || !\$form.length) {
          return;
        }

        if (\$wrap.find('.tmw-term-two-col').length) {
          return;
        }

        var \$two = $('<div class=\"tmw-term-two-col\"></div>'); // [TMW-ADMIN-CAT-SIDEBAR]
        var \$main = $('<div class=\"tmw-term-main\"></div>');
        var \$right = $('<div class=\"tmw-term-right\"></div>');

        \$main.append(\$form);
        \$two.append(\$main).append(\$right);

        var \$header = \$wrap.find('h1').first();
        if (\$header.length) {
          \$two.insertAfter(\$header);
        } else {
          \$wrap.prepend(\$two);
        }

        var \$seo = $('#rank_math_metabox, .rank-math-metabox, #wpseo_meta').first();
        if (\$seo.length) {
          \$right.append(\$seo);
        }

        \$right.append(
          '<div class=\"postbox\">' +
            '<h2 class=\"hndle\"><span>TMW Tools</span></h2>' +
            '<div class=\"inside\">' +
              '<p>TMW Slot Machine (coming)</p>' +
              '<p>TMW SEO Autopilot (coming)</p>' +
            '</div>' +
          '</div>'
        );
      }

      buildLayout();
      setTimeout(buildLayout, 500);
    });
  ");
});
