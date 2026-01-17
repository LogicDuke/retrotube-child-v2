<?php
/**
 * Archive template for Models CPT
 * With SEO text accordion - pulls content from the "models" page editor.
 *
 * @package RetrotubeChild
 * @version 2.4.0
 */

// Disable FEATURED MODELS injection on this page
$GLOBALS['tmw_featured_models_disabled'] = true;

get_header();

// === GET SEO TEXT FROM THE WORDPRESS PAGE EDITOR ===
// Get the "models" page content (the page you edit in wp-admin)
$seo_text = '';
$models_page = get_page_by_path('models');

if ($models_page) {
    // Get the raw content from the page
    $seo_text = $models_page->post_content;
    
    // Apply content filters (processes shortcodes, etc.)
    $seo_text = apply_filters('the_content', $seo_text);
    
    // Strip tags but keep basic formatting
    $seo_text = strip_tags($seo_text, '<p><br><strong><em><b><i><a><h2><h3><ul><ol><li>');
    $seo_text = trim($seo_text);
}
?>
<main id="primary" class="site-main">
  <div class="tmw-layout container">
    <section class="tmw-content" data-mobile-guard="true">
      <header class="entry-header tmw-models-archive-header">
        <h1 class="widget-title"><span class="tmw-star">★</span> Models</h1>
      </header>
      
      <?php if (!empty($seo_text)) : ?>
        <!-- SEO Text Accordion - Content from WordPress Page Editor -->
        <style>
          /* Container for the SEO accordion */
          .tmw-models-seo-accordion {
            margin: 0 0 20px;
            padding: 0 20px 15px;
            background: transparent;
          }
          
          /* Description container */
          .tmw-models-seo-accordion .desc {
            font-size: 14px;
            line-height: 1.6;
            color: #ccc;
            margin: 0;
            padding: 0;
          }
          
          /* Clamped state - show only 1 line */
          .tmw-models-seo-accordion .desc.more {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 1;
            overflow: hidden;
            max-height: 1.6em;
          }
          
          .tmw-models-seo-accordion .desc p {
            margin: 0 0 10px;
          }
          
          .tmw-models-seo-accordion .desc p:first-child {
            display: inline;
          }
          
          .tmw-models-seo-accordion .desc h2,
          .tmw-models-seo-accordion .desc h3 {
            font-size: 16px;
            margin: 15px 0 8px;
            color: #fff;
          }
          
          .tmw-models-seo-accordion .desc ul,
          .tmw-models-seo-accordion .desc ol {
            margin: 10px 0;
            padding-left: 20px;
          }
          
          .tmw-models-seo-accordion .desc li {
            margin-bottom: 5px;
          }
          
          /* Read more link container - centered with red lines */
          .tmw-models-seo-accordion .morelink-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 15px;
            gap: 0;
          }
          
          /* Red gradient lines on both sides */
          .tmw-models-seo-accordion .morelink-wrap::before,
          .tmw-models-seo-accordion .morelink-wrap::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #e74c3c 100%);
          }
          
          .tmw-models-seo-accordion .morelink-wrap::after {
            background: linear-gradient(90deg, #e74c3c 0%, transparent 100%);
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
            <?php echo $seo_text; ?>
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
      // Models flipbox grid - NO featured models
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
