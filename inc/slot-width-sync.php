<?php
if (!defined('ABSPATH')) { exit; }

add_action('wp_footer', function () {
    if (is_admin() || !is_singular('model')) {
        return;
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[TMW-LAYOUT] slot-width-sync v4.3.1 ran for model ' . get_the_ID());
    }
    ?>
    <script>
      (function () {
        var heroSel = '.tmw-banner-frame, .tmw-banner-container';
        var targets = [
          '.single-model .tmw-slot-banner-wrap',
          '.single-model .tmw-model-tags',
          '.single-model .tmw-model-videos'
        ];
        var fallbackSlot = '.single-model .tmw-slot-banner';

        function resetStyles(el) {
          el.style.transform = '';
          el.style.width = '';
          el.style.maxWidth = '';
          el.style.marginLeft = '';
          el.style.marginRight = '';
        }

        function syncTarget(heroRect, el) {
          resetStyles(el);

          var elRect = el.getBoundingClientRect();
          var heroCenter = heroRect.left + (heroRect.width / 2);
          var elCenter = elRect.left + (elRect.width / 2);
          var dx = heroCenter - elCenter;

          el.style.width = heroRect.width + 'px';
          el.style.maxWidth = heroRect.width + 'px';
          el.style.marginLeft = '0';
          el.style.marginRight = '0';
          el.style.transform = dx ? 'translateX(' + dx + 'px)' : '';
        }

        function syncSlotWidth() {
          var hero = document.querySelector(heroSel);
          if (!hero) {
            return;
          }

          var heroRect = hero.getBoundingClientRect();

          var slotWrap = document.querySelector(targets[0]);
          if (slotWrap) {
            syncTarget(heroRect, slotWrap);
          } else {
            var slotFallback = document.querySelector(fallbackSlot);
            if (slotFallback) {
              syncTarget(heroRect, slotFallback);
            }
          }

          targets.slice(1).forEach(function (selector) {
            var el = document.querySelector(selector);
            if (el) {
              syncTarget(heroRect, el);
            }
          });
        }

        var syncRaf;
        function scheduleSync() {
          if (syncRaf) {
            cancelAnimationFrame(syncRaf);
          }
          syncRaf = requestAnimationFrame(syncSlotWidth);
        }

        var resizeTimer;
        function debounceSync() {
          if (resizeTimer) {
            clearTimeout(resizeTimer);
          }
          resizeTimer = setTimeout(scheduleSync, 90);
        }

        if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', scheduleSync, { passive: true });
        } else {
          scheduleSync();
        }

        window.addEventListener('load', scheduleSync, { passive: true });
        window.addEventListener('resize', debounceSync, { passive: true });
        window.addEventListener('orientationchange', debounceSync, { passive: true });
      })();
    </script>
    <?php
}, 20);
