<?php
/**
 * Flipboxes - featured models display with flip animation.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Frontend;

defined('ABSPATH') || exit;

use TMW\Models\ModelCPT;
use TMW\Models\ModelBanner;

class Flipboxes {

    public function __construct() {
        add_action('after_setup_theme', [$this, 'register_functions']);
        add_shortcode('tmw_featured_models', [$this, 'shortcode']);
    }

    public function register_functions() {
        if (!function_exists('tmw_render_featured_models')) {
            function tmw_render_featured_models($args = []) {
                return Flipboxes::render($args);
            }
        }
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(['count' => 6, 'orderby' => 'rand', 'order' => 'DESC'], $atts, 'tmw_featured_models');
        return self::render(['count' => (int) $atts['count'], 'orderby' => $atts['orderby'], 'order' => strtoupper($atts['order'])]);
    }

    public static function render($args = []) {
        $args = wp_parse_args($args, ['count' => 6, 'orderby' => 'rand', 'order' => 'DESC', 'exclude' => []]);

        $models = new \WP_Query([
            'post_type'      => ModelCPT::POST_TYPE,
            'posts_per_page' => $args['count'],
            'post_status'    => 'publish',
            'orderby'        => $args['orderby'],
            'order'          => $args['order'],
            'post__not_in'   => (array) $args['exclude'],
            'no_found_rows'  => true,
        ]);

        if (!$models->have_posts()) return '';

        ob_start();
        ?>
        <div class="tmw-featured-models">
            <h2 class="tmw-featured-title"><i class="fa fa-random"></i> <?php esc_html_e('Featured Models', 'flavor'); ?></h2>
            <div class="tmw-flipbox-grid">
                <?php while ($models->have_posts()) : $models->the_post(); self::render_single(get_the_ID()); endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_single($post_id) {
        $title = get_the_title($post_id);
        $url = get_permalink($post_id);
        $thumb = get_the_post_thumbnail_url($post_id, 'tmw-flipbox') ?: ModelBanner::get_url($post_id);
        if (!$thumb) $thumb = get_stylesheet_directory_uri() . '/assets/img/placeholder.jpg';
        ?>
        <div class="tmw-flipbox">
            <a href="<?php echo esc_url($url); ?>" class="tmw-flipbox-link" aria-label="<?php echo esc_attr($title); ?>">
                <div class="tmw-flipbox-inner">
                    <div class="tmw-flipbox-front"><img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy"></div>
                    <div class="tmw-flipbox-back"><span class="tmw-flipbox-name"><?php echo esc_html($title); ?></span></div>
                </div>
            </a>
            <span class="tmw-flipbox-label"><?php esc_html_e('Model', 'flavor'); ?></span>
        </div>
        <?php
    }
}
