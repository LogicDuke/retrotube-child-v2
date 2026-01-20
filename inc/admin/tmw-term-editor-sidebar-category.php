<?php
if (!defined('ABSPATH')) {
  exit;
}

add_action('admin_enqueue_scripts', function($hook) {
  if ($hook !== 'term.php') {
    return;
  }

  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen || !in_array($screen->taxonomy, ['category', 'blog_category'], true)) {
    return;
  }

  $taxonomy = $screen->taxonomy;
  $term_id = isset($_GET['tag_ID']) ? (int) $_GET['tag_ID'] : 0;

  add_action('admin_head', function() {
    echo '<style id="tmw-term-editor-sidebar">\n'
      . '/* [TMW-ADMIN-SIDEBAR] */\n'
      . '.tmw-term-two-col{display:flex;gap:24px;align-items:flex-start;}\n'
      . '.tmw-term-two-col .tmw-term-main{flex:1;min-width:0;}\n'
      . '.tmw-term-two-col .tmw-term-right{width:360px;flex:0 0 360px;}\n'
      . '.tmw-term-tools .inside > :first-child{margin-top:0;}\n'
      . '@media (max-width:1024px){.tmw-term-two-col{flex-direction:column;} .tmw-term-two-col .tmw-term-right{width:100%;flex:1 1 auto;}}\n'
      . '</style>';
  });

  add_action('admin_footer', function() use ($taxonomy, $term_id) {
    ob_start();
    ?>
    <div class="postbox tmw-term-tools">
      <h2 class="hndle"><span><?php echo esc_html__('TMW Tools', 'tmw'); ?></span></h2>
      <div class="inside">
        <?php
        do_action('tmw_admin_term_sidebar_before', $taxonomy, $term_id);
        ?>
        <p><?php echo esc_html__('Slot Machine integration area.', 'tmw'); ?></p>
        <p><?php echo esc_html__('SEO Autopilot integration area.', 'tmw'); ?></p>
        <?php
        do_action('tmw_admin_term_sidebar_after', $taxonomy, $term_id);
        ?>
      </div>
    </div>
    <?php
    $tools_box = trim(ob_get_clean());
    ?>
    <div id="tmw-term-tools-box" style="display:none;">
      <?php echo $tools_box; ?>
    </div>
    <script>
      jQuery(function($) {
        function applyResponsiveFallback($two, $main, $right) {
          var isNarrow = window.innerWidth <= 1024;
          $two.css({
            display: 'flex',
            gap: '24px',
            alignItems: 'flex-start',
            flexDirection: isNarrow ? 'column' : 'row'
          });
          $main.css({ flex: '1 1 auto', minWidth: 0 });
          $right.css({
            width: isNarrow ? '100%' : '360px',
            flex: isNarrow ? '1 1 auto' : '0 0 360px'
          });
        }

        function buildLayout() {
          var $wrap = $('.wrap').first();
          var $form = $('#edittag');
          if (!$wrap.length || !$form.length) {
            return;
          }

          if ($wrap.find('.tmw-term-two-col').length) {
            return;
          }

          var $two = $('<div class="tmw-term-two-col"></div>'); // [TMW-ADMIN-SIDEBAR]
          var $main = $('<div class="tmw-term-main"></div>');
          var $right = $('<div class="tmw-term-right"></div>');

          $main.append($form);
          $two.append($main).append($right);

          var $header = $wrap.find('h1').first();
          if ($header.length) {
            $two.insertAfter($header);
          } else {
            $wrap.prepend($two);
          }

          var $seoBoxes = $('#rank_math_metabox, .rank-math-metabox, #wpseo_meta').first();
          if ($seoBoxes.length) {
            $right.append($seoBoxes);
          }

          var $toolsBox = $('#tmw-term-tools-box').children().first();
          if ($toolsBox.length) {
            $right.append($toolsBox);
          }

          applyResponsiveFallback($two, $main, $right);
          $(window).on('resize.tmwTermSidebar', function() {
            applyResponsiveFallback($two, $main, $right);
          });
        }

        function detectDuplicateFields() {
          var $form = $('#edittag');
          if (!$form.length) {
            return;
          }

          function countLabel(labelText) {
            return $form.find('label').filter(function() {
              return $(this).text().trim().toLowerCase() === labelText;
            }).length;
          }

          var duplicates = [];
          if (countLabel('seo h1') > 1) {
            duplicates.push('SEO H1');
          }
          if (countLabel('intro') > 1) {
            duplicates.push('Intro');
          }

          if (!duplicates.length) {
            return;
          }

          if ($('.tmw-duplicate-field-notice').length) {
            return;
          }

          var fields = duplicates.join(', ');
          var $notice = $(
            '<div class="notice notice-warning tmw-duplicate-field-notice">' +
              '<p><!-- [TMW-ADMIN-NOTICE] -->' +
              'Multiple field helpers detected (' + fields + '). Please disable duplicates to avoid conflicts.' +
              '</p>' +
            '</div>'
          );
          $('.wrap').first().prepend($notice);
        }

        buildLayout();
        detectDuplicateFields();
        setTimeout(buildLayout, 500);
      });
    </script>
    <?php
  });
});
