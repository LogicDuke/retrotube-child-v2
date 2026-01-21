<?php
/**
 * Model query helpers - get videos, stats, related content.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Models;

defined('ABSPATH') || exit;

class ModelQuery {

    public function __construct() {
        add_action('after_setup_theme', [$this, 'register_functions']);
    }

    public function register_functions() {
        if (!function_exists('tmw_get_videos_for_model')) {
            function tmw_get_videos_for_model($slug, $limit = 24) {
                return ModelQuery::get_videos($slug, $limit);
            }
        }
        if (!function_exists('tmw_get_model_video_count')) {
            function tmw_get_model_video_count($slug) {
                return ModelQuery::get_video_count($slug);
            }
        }
        if (!function_exists('tmw_get_model_tags')) {
            function tmw_get_model_tags($slug, $limit = 50) {
                return ModelQuery::get_tags($slug, $limit);
            }
        }
    }

    public static function get_videos($model_slug, $limit = 24) {
        if (empty($model_slug)) return [];

        $post_type = self::detect_video_post_type();
        
        if (!is_object_in_taxonomy($post_type, ModelTaxonomy::TAXONOMY)) {
            register_taxonomy_for_object_type(ModelTaxonomy::TAXONOMY, $post_type);
        }

        $query = new \WP_Query([
            'post_type'      => $post_type,
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
            'no_found_rows'  => ($limit > 0),
            'tax_query'      => [[
                'taxonomy' => ModelTaxonomy::TAXONOMY,
                'field'    => 'slug',
                'terms'    => $model_slug,
            ]],
        ]);

        return $query->posts;
    }

    public static function get_video_count($model_slug) {
        if (empty($model_slug)) return 0;
        $term = get_term_by('slug', $model_slug, ModelTaxonomy::TAXONOMY);
        return ($term && !is_wp_error($term)) ? (int) $term->count : 0;
    }

    public static function get_tags($model_slug, $limit = 50) {
        $videos = self::get_videos($model_slug, -1);
        if (empty($videos)) return [];

        $tag_ids = [];
        $tags = [];

        foreach ($videos as $video) {
            $video_tags = wp_get_post_terms($video->ID, 'post_tag');
            if (!is_wp_error($video_tags)) {
                foreach ($video_tags as $tag) {
                    if (!isset($tag_ids[$tag->term_id])) {
                        $tag_ids[$tag->term_id] = true;
                        $tags[] = $tag;
                    }
                }
            }
        }

        usort($tags, function($a, $b) {
            return strcasecmp($a->name, $b->name);
        });

        return $limit > 0 && count($tags) > $limit ? array_slice($tags, 0, $limit) : $tags;
    }

    public static function detect_video_post_type() {
        static $detected = null;
        if ($detected !== null) return $detected;
        $detected = post_type_exists('video') ? 'video' : 'post';
        return $detected;
    }

    public static function get_stats($post_id) {
        $post_id = (int) $post_id;
        $views = (int) get_post_meta($post_id, 'post_views_count', true);
        $likes = (int) get_post_meta($post_id, 'likes_count', true);
        $dislikes = (int) get_post_meta($post_id, 'dislikes_count', true);
        $total = $likes + $dislikes;
        $rating = $total > 0 ? round(($likes / $total) * 100, 1) : 0;
        return compact('views', 'likes', 'dislikes', 'rating');
    }
}
