<?php
/**
 * Model frontend display - breadcrumbs, content filters, templates.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Models;

defined('ABSPATH') || exit;

class ModelFrontend {

    public function __construct() {
        add_filter('rank_math/frontend/breadcrumb/items', [$this, 'filter_breadcrumbs']);
        add_filter('the_content', [$this, 'add_models_to_video'], 45);
        add_action('after_setup_theme', [$this, 'register_functions']);
    }

    public function register_functions() {
        if (!function_exists('tmw_get_model_link_for_term')) {
            function tmw_get_model_link_for_term($term) {
                return ModelTaxonomy::get_model_link($term);
            }
        }
        if (!function_exists('tmw_render_models_breadcrumbs')) {
            function tmw_render_models_breadcrumbs() {
                if (function_exists('rank_math_the_breadcrumbs')) {
                    rank_math_the_breadcrumbs();
                }
            }
        }
    }

    public function filter_breadcrumbs($crumbs) {
        if (!function_exists('rank_math_the_breadcrumbs')) return $crumbs;

        $models_url = home_url('/' . ModelCPT::ARCHIVE_SLUG . '/');

        if (is_post_type_archive(ModelCPT::POST_TYPE)) {
            return [
                ['label' => __('Home', 'flavor'), 'url' => home_url('/')],
                ['label' => __('Models', 'flavor'), 'url' => $models_url],
            ];
        }

        if (is_singular(ModelCPT::POST_TYPE)) {
            return [
                ['label' => __('Home', 'flavor'), 'url' => home_url('/')],
                ['label' => __('Models', 'flavor'), 'url' => $models_url],
                ['label' => get_the_title(), 'url' => ''],
            ];
        }

        foreach ($crumbs as $key => $crumb) {
            if (!is_array($crumb) || !isset($crumb['label'])) continue;
            $label = strtolower(trim((string) $crumb['label']));
            if ($label === 'model' || $label === 'model bio') {
                $crumbs[$key]['label'] = __('Models', 'flavor');
                $crumbs[$key]['url'] = $models_url;
            }
        }

        return $crumbs;
    }

    public function add_models_to_video($content) {
        if (!is_singular(['post', 'video']) || !in_the_loop() || !is_main_query()) return $content;
        if (strpos($content, 'id="video-models"') !== false) return $content;

        $post_id = get_the_ID();
        $terms = get_the_terms($post_id, ModelTaxonomy::TAXONOMY);
        if (empty($terms) || is_wp_error($terms)) {
            $terms = get_the_terms($post_id, 'actors');
        }
        if (empty($terms) || is_wp_error($terms)) return $content;

        $links = [];
        foreach ($terms as $term) {
            $url = ModelTaxonomy::get_model_link($term);
            if ($url) {
                $links[] = sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($term->name));
            }
        }
        if (empty($links)) return $content;

        $label = count($terms) === 1 ? __('Model', 'flavor') : __('Models', 'flavor');
        $block = sprintf('<div id="video-models"><i class="fa fa-star"></i> %s: %s</div>', esc_html($label), implode(', ', $links));

        if (preg_match('~(<div[^>]+id="video-date"[^>]*>.*?</div>)~is', $content)) {
            return preg_replace('~(<div[^>]+id="video-date"[^>]*>.*?</div>)~is', '$1' . $block, $content, 1);
        }
        return $block . $content;
    }

    public static function render_video_grid($model_slug, $limit = 24) {
        $videos = ModelQuery::get_videos($model_slug, $limit);
        if (empty($videos)) {
            echo '<p class="no-videos">' . esc_html__('No videos found for this model.', 'flavor') . '</p>';
            return;
        }

        echo '<div class="videos-list model-videos">';
        foreach ($videos as $video) {
            global $post;
            $post = $video;
            setup_postdata($post);
            get_template_part('template-parts/loop', 'video');
        }
        wp_reset_postdata();
        echo '</div>';
    }
}
