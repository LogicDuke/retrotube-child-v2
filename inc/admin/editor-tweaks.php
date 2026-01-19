<?php
if (!defined('ABSPATH')) { exit; }

// Bridge: keep legacy admin tooling (metabox tweaks, reports) until refactored.
$admin_legacy = TMW_CHILD_PATH . '/inc/tmw-admin-tools.php';
if (is_readable($admin_legacy)) {
    require_once $admin_legacy;
}

function tmw_enqueue_term_editor_ui($hook) {
    if ($hook !== 'term.php') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || empty($screen->taxonomy)) {
        return;
    }

    $allowed_taxonomies = array('category', 'post_tag');
    if (!in_array($screen->taxonomy, $allowed_taxonomies, true)) {
        return;
    }

    wp_register_style('tmw-term-editor-ui', false);
    wp_enqueue_style('tmw-term-editor-ui');
    wp_add_inline_style(
        'tmw-term-editor-ui',
        '.tmw-term-editor-large .wp-editor-area{min-height:420px;}
        .tmw-term-editor-large .mce-edit-area iframe{height:420px;}
        .tmw-term-editor-small .wp-editor-area{min-height:160px;}
        .tmw-term-editor-small .mce-edit-area iframe{height:160px;}'
    );

    wp_register_script('tmw-term-editor-ui', '', array(), false, true);
    wp_enqueue_script('tmw-term-editor-ui');
    wp_add_inline_script(
        'tmw-term-editor-ui',
        "(function(){\n"
        . "  function applyEditorHeight(target, sizeClass){\n"
        . "    if (!target) { return; }\n"
        . "    target.classList.add(sizeClass);\n"
        . "    var iframes = target.querySelectorAll('.mce-edit-area iframe');\n"
        . "    iframes.forEach(function(iframe){ iframe.style.height = sizeClass === 'tmw-term-editor-large' ? '420px' : '160px'; });\n"
        . "    var textareas = target.querySelectorAll('.wp-editor-area');\n"
        . "    textareas.forEach(function(textarea){ textarea.style.minHeight = sizeClass === 'tmw-term-editor-large' ? '420px' : '160px'; });\n"
        . "  }\n"
        . "  function findRowByLabel(text){\n"
        . "    var rows = document.querySelectorAll('tr');\n"
        . "    for (var i = 0; i < rows.length; i += 1) {\n"
        . "      var label = rows[i].querySelector('th label');\n"
        . "      if (label && label.textContent && label.textContent.toLowerCase().indexOf(text) !== -1) {\n"
        . "        return rows[i];\n"
        . "      }\n"
        . "    }\n"
        . "    return null;\n"
        . "  }\n"
        . "  document.addEventListener('DOMContentLoaded', function(){\n"
        . "    var mainWrapper = document.querySelector('.term-page-content-wrap');\n"
        . "    if (mainWrapper) {\n"
        . "      applyEditorHeight(mainWrapper.closest('tr') || mainWrapper, 'tmw-term-editor-large');\n"
        . "    } else {\n"
        . "      var accordionRow = findRowByLabel('page content (accordion body)');\n"
        . "      applyEditorHeight(accordionRow, 'tmw-term-editor-large');\n"
        . "    }\n"
        . "    var shortIntroRow = findRowByLabel('short intro');\n"
        . "    applyEditorHeight(shortIntroRow, 'tmw-term-editor-small');\n"
        . "  });\n"
        . "})();\n"
    );
}
add_action('admin_enqueue_scripts', 'tmw_enqueue_term_editor_ui');
