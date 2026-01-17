<?php
/**
 * Archive template for Models CPT
 * With SEO text accordion below H1 header.
 *
 * @package RetrotubeChild
 * @version 2.0.0
 */

get_header();

// === SEO Text Configuration ===
// You can customize this text via ACF field 'models_archive_seo_text' on the Models archive page,
// or edit the default text below directly.
$seo_text = '';
$read_lines = 3; // Number of visible lines before "Read more"

// Try to get SEO text from ACF (if available)
if (function_exists('get_field')) {
    // Get from ACF Options page or term meta
    $seo_text = get_field('models_archive_seo_text', 'option');
    $custom_lines = get_field('models_archive_read_lines', 'option');
    if ($custom_lines && is_numeric($custom_lines)) {
        $read_lines = (int) $custom_lines;
    }
}

// Fallback default SEO text - EDIT THIS to your preferred text
if (empty($seo_text)) {
    $seo_text = 'Welcome to our exclusive collection of top webcam models. Browse through our curated selection of the most talented and captivating performers from around the world. Each model brings their unique personality, charm, and entertainment style to create unforgettable live experiences. Whether you\'re looking for engaging conversations, stunning performances, or simply want to discover new favorites, our models page makes it easy to find exactly what you\'re looking for. Click on any model to view their profile, watch their latest videos, and connect with them during their live shows.';
}
?>
<main id="primary" class="site-main">
  <div class="tmw-layout container">
    <section class="tmw-content" data-mobile-guard="true">
      <header class="entry-header tmw-models-archive-header">
        <h1 class="widget-title"><span class="tmw-star">★</span> Models</h1>
        
        <?php if (!empty($seo_text)) : ?>
          <style>
            .tmw-models-seo-wrap {
              margin: 15px 0 20px;
              padding: 0;
            }
            .tmw-models-seo {
              font-size: 14px;
              line-height: 1.6;
              color: #ccc;
            }
            .tmw-models-seo.js-clamp {
              display: -webkit-box;
              -webkit-box-orient: vertical;
              overflow: hidden;
              -webkit-line-clamp: <?php echo (int) $read_lines; ?>;
            }
            .tmw-models-seo p {
              margin: 0 0 10px;
            }
            .tmw-models-seo p:last-child {
              margin-bottom: 0;
            }
            .tmw-seo-toggle {
              margin: 8px 0 0;
              padding: 0;
            }
            .tmw-seo-toggle .morelink {
              color: #e74c3c;
              text-decoration: none;
              font-size: 14px;
              cursor: pointer;
              display: inline-flex;
              align-items: center;
              gap: 5px;
              transition: color 0.2s ease;
            }
            .tmw-seo-toggle .morelink:hover {
              color: #ff6b5b;
            }
            .tmw-seo-toggle .morelink i {
              font-size: 12px;
              transition: transform 0.3s ease;
            }
            .tmw-seo-toggle .morelink[aria-expanded="true"] i {
              transform: rotate(180deg);
            }
            /* Gradient fade effect when clamped */
            .tmw-models-seo-wrap.is-clamped .tmw-models-seo::after {
              content: '';
              position: absolute;
              bottom: 0;
              left: 0;
              right: 0;
              height: 20px;
              background: linear-gradient(transparent, rgba(30, 30, 30, 0.9));
              pointer-events: none;
            }
            .tmw-models-seo-wrap.is-clamped .tmw-models-seo {
              position: relative;
            }
          </style>
          
          <div class="tmw-models-seo-wrap">
            <div id="tmw-models-seo" class="tmw-models-seo js-clamp">
              <?php echo wpautop(wp_kses_post($seo_text)); ?>
            </div>
            <p class="tmw-seo-toggle">
              <a class="morelink" href="#" aria-controls="tmw-models-seo" aria-expanded="false">
                <?php esc_html_e('Read more', 'retrotube-child'); ?> <i class="fa fa-chevron-down"></i>
              </a>
            </p>
          </div>
          
          <script>
          (function(){
            var seo = document.getElementById('tmw-models-seo');
            var wrap = document.querySelector('.tmw-seo-toggle');
            var container = document.querySelector('.tmw-models-seo-wrap');
            if (!seo || !wrap) return;

            // Check if content actually needs clamping
            var clone = seo.cloneNode(true);
            clone.style.cssText = 'visibility:hidden;position:absolute;-webkit-line-clamp:unset;display:block;overflow:visible;';
            clone.classList.remove('js-clamp');
            document.body.appendChild(clone);
            var needsClamp = clone.scrollHeight > seo.clientHeight + 5;
            document.body.removeChild(clone);
            
            if (!needsClamp) { 
              wrap.style.display = 'none'; 
              return; 
            }
            
            // Add clamped indicator for gradient effect
            container.classList.add('is-clamped');

            var link = wrap.querySelector('a.morelink');
            link.addEventListener('click', function(e){
              e.preventDefault();
              var expanded = link.getAttribute('aria-expanded') === 'true';
              if (expanded) {
                seo.classList.add('js-clamp');
                container.classList.add('is-clamped');
                link.setAttribute('aria-expanded', 'false');
                link.innerHTML = '<?php echo esc_js(__('Read more', 'retrotube-child')); ?> <i class="fa fa-chevron-down"></i>';
              } else {
                seo.classList.remove('js-clamp');
                container.classList.remove('is-clamped');
                link.setAttribute('aria-expanded', 'true');
                link.innerHTML = '<?php echo esc_js(__('Read less', 'retrotube-child')); ?> <i class="fa fa-chevron-up"></i>';
              }
            });
          })();
          </script>
        <?php endif; ?>
        
      </header>
      <?php
      // Edit banner file at /assets/models-banner.html or pass banner_* via shortcode below.
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
