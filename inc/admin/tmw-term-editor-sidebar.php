<?php
if (!defined('ABSPATH')) { exit; }

// [TMW-ADMIN-TAX] Term editor layout + dedupe guard.
function tmw_register_term_editor_sidebar($hook) {
    if ($hook !== 'term.php') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || empty($screen->taxonomy)) {
        return;
    }

    $allowed_taxonomies = array('category', 'blog_category');
    if (!in_array($screen->taxonomy, $allowed_taxonomies, true)) {
        return;
    }

    // [TMW-ADMIN-TAX-SIDEBAR] Sidebar layout styles.
    wp_register_style('tmw-term-editor-sidebar', false);
    wp_enqueue_style('tmw-term-editor-sidebar');
    wp_add_inline_style(
        'tmw-term-editor-sidebar',
        '.tmw-term-two-col{display:flex;gap:24px;align-items:flex-start;}
        .tmw-term-main{flex:1;min-width:0;}
        .tmw-term-right{width:340px;flex:0 0 340px;}
        @media (max-width:1024px){.tmw-term-two-col{flex-direction:column;}.tmw-term-right{width:100%;flex:1 1 auto;}}'
    );

    // [TMW-ADMIN-TAX-DEDUP] Sidebar layout + ACF dedupe guard.
    wp_register_script('tmw-term-editor-sidebar', '', array('jquery'), false, true);
    wp_enqueue_script('tmw-term-editor-sidebar');
    wp_add_inline_script(
        'tmw-term-editor-sidebar',
        "(function($){\n"
        . "  // [TMW-ADMIN-TAX-SIDEBAR] Build right sidebar layout.\n"
        . "  function buildLayout(){\n"
        . "    var $form = $('#edittag');\n"
        . "    if (!$form.length) { return; }\n"
        . "    var $wrap = $form.closest('.wrap');\n"
        . "    if (!$wrap.length || $wrap.find('.tmw-term-two-col').length) { return; }\n"
        . "    var $container = $('<div class=\"tmw-term-two-col\"></div>');\n"
        . "    var $main = $('<div class=\"tmw-term-main\"></div>');\n"
        . "    var $right = $('<div class=\"tmw-term-right\"></div>');\n"
        . "    $form.appendTo($main);\n"
        . "    $container.append($main, $right);\n"
        . "    var $heading = $wrap.children('h1').first();\n"
        . "    if ($heading.length) {\n"
        . "      $container.insertAfter($heading);\n"
        . "    } else {\n"
        . "      $wrap.prepend($container);\n"
        . "    }\n"
        . "    var $card = $('<div class=\"postbox\"><h2 class=\"hndle\"><span>TMW Sidebar</span></h2><div class=\"inside\"><ul><li>Fill SEO H1</li><li>Fill Intro</li><li>Fill Page Content</li></ul><p>Duplicates mean multiple registrations; remove extra plugins/ACF groups.</p></div></div>');\n"
        . "    $right.append($card);\n"
        . "    var $seo = $('#rank_math_metabox, .rank-math-metabox, #wpseo_meta');\n"
        . "    if ($seo.length) {\n"
        . "      $seo.appendTo($right);\n"
        . "    }\n"
        . "  }\n"
        . "  // [TMW-ADMIN-TAX-DEDUP] Hide duplicate ACF fields and warn.\n"
        . "  function guardDuplicates(){\n"
        . "    var names = ['seo_h1','seo_intro','page_content'];\n"
        . "    var hasDuplicates = false;\n"
        . "    names.forEach(function(name){\n"
        . "      var $fields = $('.acf-field[data-name=\"' + name + '\"]');\n"
        . "      if ($fields.length > 1) {\n"
        . "        hasDuplicates = true;\n"
        . "        $fields.slice(1).closest('.acf-field').hide();\n"
        . "      }\n"
        . "    });\n"
        . "    if (hasDuplicates) {\n"
        . "      var $wrap = $('.wrap').first();\n"
        . "      if ($wrap.find('.tmw-term-dup-notice').length) { return; }\n"
        . "      var notice = '<div class=\"notice notice-warning tmw-term-dup-notice\"><p>Duplicate taxonomy editor fields detected. This usually means multiple plugins/ACF field groups register the same fields. Keep only one source.</p></div>';\n"
        . "      $wrap.prepend(notice);\n"
        . "    }\n"
        . "  }\n"
        . "  $(function(){\n"
        . "    buildLayout();\n"
        . "    guardDuplicates();\n"
        . "  });\n"
        . "})(jQuery));\n"
    );
}
add_action('admin_enqueue_scripts', 'tmw_register_term_editor_sidebar');
