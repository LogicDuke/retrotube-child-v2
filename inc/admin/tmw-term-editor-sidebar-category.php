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
    .tmw-term-main { flex:1; min-width:0; }
    .tmw-term-right { width:360px; flex:0 0 360px; }
    @media (max-width: 1024px) {
      .tmw-term-two-col { flex-direction:column; }
      .tmw-term-right { width:100%; flex:1 1 auto; }
    }
  ');

  wp_enqueue_script('jquery');
  wp_add_inline_script('jquery', "
    jQuery(function($) {
      var \$form = $('#edittag');
      if (!\$form.length) {
        return;
      }

      var \$layout = $('<div class=\"tmw-term-two-col\" />'); // [TMW-ADMIN-CAT-SIDEBAR]
      var \$main = $('<div class=\"tmw-term-main\" />');
      var \$right = $('<div class=\"tmw-term-right\" />');

      \$form.before(\$layout);
      \$layout.append(\$main, \$right);
      \$main.append(\$form);

      var \$seoBox = $('#rank_math_metabox, .rank-math-metabox, #wpseo_meta').first();
      if (\$seoBox.length) {
        \$right.append(\$seoBox);
      }

      var toolsBox = [
        '<div class=\"postbox\">', // [TMW-ADMIN-CAT-SEOBOX]
        '  <h2 class=\"hndle\"><span>TMW Tools</span></h2>',
        '  <div class=\"inside\">',
        '    <p>TMW Slot Machine (coming)</p>',
        '    <p>TMW SEO Autopilot (coming)</p>',
        '  </div>',
        '</div>'
      ].join('');

      if (!\$right.children().length) {
        \$right.append(toolsBox);
      } else {
        \$right.append(toolsBox);
      }
    });
  ");
});
