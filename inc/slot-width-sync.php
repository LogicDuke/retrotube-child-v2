<?php
if (!defined('ABSPATH')) { exit; }

/**
 * v4.3.0 — Slot ↔ Hero width/position sync (no layout changes elsewhere)
 * - Runs only on single model pages (front-end).
 * - Measures the hero frame and applies the same width and horizontal alignment to .tmw-slot-banner.
 * - Recomputes on resize/orientation changes.
 */

if (!function_exists('tmw_should_boot_slot_width_sync')) {
  function tmw_should_boot_slot_width_sync(): bool {
    if (!is_singular('model') || is_admin()) {
      return false;
    }

    if (function_exists('tmw_current_post_has_slot_machine')) {
      return tmw_current_post_has_slot_machine();
    }

    if (function_exists('has_shortcode')) {
      $post = get_post();
      return $post ? has_shortcode((string) $post->post_content, 'tmw_slot_machine') : false;
    }

    return false;
  }
}

if (!function_exists('tmw_output_slot_width_sync_script')) {
  function tmw_output_slot_width_sync_script(): void {
    if (!tmw_should_boot_slot_width_sync()) {
      return;
    }
?>
  <script>
  (function() {
    var heroSel = '.tmw-banner-frame, .tmw-banner-container';
    var slotSel = '.single-model .tmw-slot-banner';
    var t;

    function syncSlotToHero() {
      var hero = document.querySelector(heroSel);
      var slot = document.querySelector(slotSel);
      if (!hero || !slot) return;

      // Reset any previous transform/width we set so we can measure cleanly.
      slot.style.transform = '';
      slot.style.width = '';
      slot.style.maxWidth = '';
      slot.style.marginLeft = '';
      slot.style.marginRight = '';

      // Measure hero & slot.
      var h = hero.getBoundingClientRect();
      var s = slot.getBoundingClientRect();

      // Apply hero width to slot.
      var targetW = Math.round(h.width);
      slot.style.width = targetW + 'px';
      slot.style.maxWidth = targetW + 'px';

      // Re-measure slot after width change, then align centers to match hero.
      var s2 = slot.getBoundingClientRect();
      var heroCenter = h.left + h.width / 2;
      var slotCenter = s2.left + s2.width / 2;
      var dx = Math.round(heroCenter - slotCenter);

      // Shift the slot horizontally to match hero's alignment.
      slot.style.transform = 'translateX(' + dx + 'px)';

      // Keep the slot visually centered like hero (in case theme centers via margins)
      slot.style.marginLeft = '0';
      slot.style.marginRight = '0';
    }

    function debouncedSync() {
      clearTimeout(t); t = setTimeout(syncSlotToHero, 60);
    }

    window.addEventListener('load', syncSlotToHero, { once: true });
    window.addEventListener('resize', debouncedSync);
    window.addEventListener('orientationchange', debouncedSync);
  })();
  </script>
<?php }
}

add_action('wp', function () {
  if (!function_exists('tmw_output_slot_width_sync_script')) {
    return;
  }

  if (!tmw_should_boot_slot_width_sync()) {
    return;
  }

  add_action('wp_footer', 'tmw_output_slot_width_sync_script', 100);
}, 30);
