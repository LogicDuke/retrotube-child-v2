<?php
/**
 * Model banner handling - URLs, focal points, rendering.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Models;

defined('ABSPATH') || exit;

use TMW\Core\Helpers;

class ModelBanner {

    const DEFAULT_HEIGHT = 350;
    const DEFAULT_WIDTH = 1035;

    public function __construct() {
        add_action('after_setup_theme', [$this, 'register_functions']);
    }

    public function register_functions() {
        if (!function_exists('tmw_get_model_banner_url')) {
            function tmw_get_model_banner_url($post_id) {
                return ModelBanner::get_url($post_id);
            }
        }
        if (!function_exists('tmw_render_model_banner')) {
            function tmw_render_model_banner($post_id = 0, $context = 'frontend') {
                return ModelBanner::render($post_id, $context);
            }
        }
        if (!function_exists('tmw_resolve_model_banner_url')) {
            function tmw_resolve_model_banner_url($post_id = 0, $term_id = 0) {
                return ModelBanner::resolve_url($post_id, $term_id);
            }
        }
    }

    public static function get_url($post_id) {
        return self::resolve_url($post_id);
    }

    public static function resolve_url($post_id = 0, $term_id = 0) {
        $post_id = (int) $post_id;
        $banner_url = '';

        if ($post_id) {
            // ACF field
            if (function_exists('get_field')) {
                $acf = get_field('banner_image', $post_id);
                if (is_array($acf) && !empty($acf['url'])) {
                    $banner_url = $acf['url'];
                } elseif (is_string($acf) && filter_var($acf, FILTER_VALIDATE_URL)) {
                    $banner_url = $acf;
                }
            }

            // Legacy meta
            if (empty($banner_url)) {
                $legacy = get_post_meta($post_id, 'banner_image', true);
                $banner_url = self::extract_url($legacy);
            }
            if (empty($banner_url)) {
                $legacy = get_post_meta($post_id, 'banner_image_url', true);
                $banner_url = self::extract_url($legacy);
            }

            // Featured image
            if (empty($banner_url) && has_post_thumbnail($post_id)) {
                $banner_url = get_the_post_thumbnail_url($post_id, 'full');
            }
        }

        // HTTPS
        if (!empty($banner_url)) {
            $banner_url = set_url_scheme($banner_url, 'https');
        }

        return $banner_url ? esc_url_raw($banner_url) : '';
    }

    private static function extract_url($meta) {
        if (is_array($meta) && !empty($meta['url'])) return $meta['url'];
        if (is_string($meta) && filter_var($meta, FILTER_VALIDATE_URL)) return $meta;
        if (is_numeric($meta)) return wp_get_attachment_url((int) $meta) ?: '';
        return '';
    }

    public static function get_focal_y($post_id) {
        $stored = get_post_meta((int) $post_id, '_banner_focal_y', true);
        if ($stored !== '' && $stored !== null) {
            return max(0, min(100, (float) $stored));
        }
        return 50.0;
    }

    public static function render($post_id = 0, $context = 'frontend') {
        if (!$post_id) $post_id = get_the_ID();
        $post_id = (int) $post_id;
        if (!$post_id) return false;

        $url = self::resolve_url($post_id);
        if (!$url) return false;

        $focal_y = self::get_focal_y($post_id);
        $dims = Helpers::get_image_dimensions($url);
        ?>
        <div class="tmw-banner-container">
            <div class="tmw-banner-frame <?php echo esc_attr($context); ?>">
                <img src="<?php echo esc_url($url); ?>" alt="" width="<?php echo esc_attr($dims['width']); ?>" height="<?php echo esc_attr($dims['height']); ?>" style="object-position: 50% <?php echo esc_attr($focal_y); ?>%;" loading="eager" fetchpriority="high" decoding="async">
            </div>
        </div>
        <?php
        return true;
    }
}
