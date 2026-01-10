<?php
if (!defined('ABSPATH')) { exit; }

add_action('wp_footer', function () {
    if (!is_singular('model')) {
        return;
    }

    if (defined('TMW_DEBUG') && TMW_DEBUG) {
        error_log('[TMW-LAYOUT] slot-width-sync ran on model page: ' . get_the_ID());
    }
    ?>
    <script>
      (function () {
        var heroSel = '.tmw-banner-frame, .tmw-banner-container';
        var slotWrapSel = '.single-model .tmw-slot-banner-wrap';
        var slotSel = '.single-model .tmw-slot-banner';

        function syncSlotWidth() {
          var hero = document.querySelector(heroSel);
          if (!hero) {
            return;
          }

          var slotWrap = document.querySelector(slotWrapSel);
          var slotTarget = slotWrap || document.querySelector(slotSel);
          if (!slotTarget) {
            return;
          }

          var heroRect = hero.getBoundingClientRect();
          var slotRect = slotTarget.getBoundingClientRect();
          var deltaLeft = heroRect.left - slotRect.left;

          slotTarget.style.width = heroRect.width + 'px';
          slotTarget.style.maxWidth = heroRect.width + 'px';
          slotTarget.style.marginLeft = '0';
          slotTarget.style.marginRight = '0';
          slotTarget.style.transform = deltaLeft ? 'translateX(' + deltaLeft + 'px)' : '';

          if (slotWrap) {
            var inner = slotWrap.querySelector('.tmw-slot-banner');
            if (inner) {
              inner.style.width = '100%';
              inner.style.maxWidth = '100%';
            }
          }
        }

        var syncRaf;
        function scheduleSync() {
          if (syncRaf) {
            cancelAnimationFrame(syncRaf);
          }
          syncRaf = requestAnimationFrame(syncSlotWidth);
        }

        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', scheduleSync, { passive: true });
        } else {
          scheduleSync();
        }

        window.addEventListener('load', scheduleSync, { passive: true });
        window.addEventListener('resize', scheduleSync, { passive: true });
      })();
    </script>
    <?php
}, 20);
