<?php
/**
 * Archive template for Models CPT
 * With SEO text accordion matching video/model page styling.
 *
 * @package RetrotubeChild
 * @version 2.1.0
 */

get_header();

// === SEO Text Configuration ===
$seo_text = '';

// Try to get SEO text from ACF (if available)
if (function_exists('get_field')) {
    $seo_text = get_field('models_archive_seo_text', 'option');
}

// Fallback default SEO text - EDIT THIS to your preferred text
if (empty($seo_text)) {
    $seo_text = 'Welcome to the Models page at top-models.webcam, your curated guide to professional webcam models and live cam girls who bring personality, creativity, and charm to every broadcast. This introduction is here if you want a quick overview, while the grid below lets you dive straight into the talent. Each profile includes videos, bio details, and direct links to watch them live. Whether you prefer sultry performances, playful energy, or intimate conversations, our ever-growing roster has someone for every taste. Explore freely, discover new favorites, and enjoy the best in adult webcam entertainment.';
}
?>
<main id="primary" class="site-main">
  <div class="tmw-layout container">
    <section class="tmw-content" data-mobile-guard="true">
      <header class="entry-header tmw-models-archive-header">
        <h1 class="widget-title"><span class="tmw-star">★</span> Models</h1>
      </header>
      
      <?php if (!empty($seo_text)) : ?>
        <!-- SEO Text Accordion - Matches Video/Model page styling -->
        <style>
          /* Container for the SEO accordion */
          .tmw-models-seo-accordion {
            margin: 0 0 20px;
            padding: 15px 20px;
            background: transparent;
          }
          
          /* Description container - matches .video-description .desc.more */
          .tmw-models-seo-accordion .desc {
            font-size: 14px;
            line-height: 1.6;
            color: #ccc;
            margin: 0;
            padding: 0;
          }
          
          /* Clamped state - show only ~2 lines */
          .tmw-models-seo-accordion .desc.more {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            max-height: 3.2em; /* ~2 lines */
          }
          
          .tmw-models-seo-accordion .desc p {
            margin: 0;
            display: inline;
          }
          
          /* Read more link container - centered with lines */
          .tmw-models-seo-accordion .morelink-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 12px;
            gap: 0;
          }
          
          /* Red gradient lines on sides */
          .tmw-models-seo-accordion .morelink-wrap::before,
          .tmw-models-seo-accordion .morelink-wrap::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #e74c3c 50%, #e74c3c 100%);
          }
          
          .tmw-models-seo-accordion .morelink-wrap::after {
            background: linear-gradient(90deg, #e74c3c 0%, #e74c3c 50%, transparent 100%);
          }
          
          /* The Read more link itself */
          .tmw-models-seo-accordion .morelink {
            color: #e74c3c;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            padding: 0 15px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
          }
          
          .tmw-models-seo-accordion .morelink:hover {
            color: #ff6b5b;
          }
          
          .tmw-models-seo-accordion .morelink i {
            font-size: 12px;
            transition: transform 0.3s ease;
          }
          
          /* Rotate chevron when expanded */
          .tmw-models-seo-accordion .morelink.expanded i {
            transform: rotate(180deg);
          }
        </style>
        
        <div class="tmw-models-seo-accordion">
          <div id="tmw-seo-desc" class="desc more">
            <?php echo wp_kses_post($seo_text); ?>
          </div>
          <div class="morelink-wrap">
            <a id="tmw-seo-toggle" class="morelink" href="javascript:void(0);">
              <?php esc_html_e('Read more', 'retrotube-child'); ?> <i class="fa fa-chevron-down"></i>
            </a>
          </div>
        </div>
        
        <script>
        (function(){
          var desc = document.getElementById('tmw-seo-desc');
          var toggle = document.getElementById('tmw-seo-toggle');
          if (!desc || !toggle) return;

          toggle.addEventListener('click', function(e){
            e.preventDefault();
            var isExpanded = !desc.classList.contains('more');
            
            if (isExpanded) {
              // Collapse
              desc.classList.add('more');
              toggle.classList.remove('expanded');
              toggle.innerHTML = '<?php echo esc_js(__('Read more', 'retrotube-child')); ?> <i class="fa fa-chevron-down"></i>';
            } else {
              // Expand
              desc.classList.remove('more');
              toggle.classList.add('expanded');
              toggle.innerHTML = '<?php echo esc_js(__('Close', 'retrotube-child')); ?> <i class="fa fa-chevron-up"></i>';
            }
          });
        })();
        </script>
      <?php endif; ?>
      
      <?php
      // Models grid - NO featured models
      add_filter('tmw_model_flipbox_link', 'tmw_flipbox_link_guard_filter', 10, 2);
      echo do_shortcode('[actors_flipboxes per_page="16" cols="4" show_pagination="true"]');
      remove_filter('tmw_model_flipbox_link', 'tmw_flipbox_link_guard_filter', 10, 2);
      ?>
    </section>
    <aside class="tmw-sidebar">
      <?php get_sidebar(); ?>
    </aside>
  </div>
</main>
<?php get_footer(); ?>
