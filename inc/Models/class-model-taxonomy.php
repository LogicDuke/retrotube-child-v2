<?php
/**
 * Models taxonomy registration and management.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Models;

defined('ABSPATH') || exit;

class ModelTaxonomy {

    const TAXONOMY = 'models';
    const URL_SLUG = 'model-tag';

    public function __construct() {
        add_action('init', [$this, 'register'], 5);
        add_action('init', [$this, 'add_rewrite_rules'], 20);
        add_action('template_redirect', [$this, 'redirect_to_cpt']);
        add_action('registered_post_type', [$this, 'bind_to_post_types'], 30);
    }

    public function register() {
        $labels = [
            'name'              => __('Models', 'flavor'),
            'singular_name'     => __('Model', 'flavor'),
            'menu_name'         => __('Model Tags', 'flavor'),
            'all_items'         => __('All Models', 'flavor'),
            'edit_item'         => __('Edit Model', 'flavor'),
            'view_item'         => __('View Model', 'flavor'),
            'add_new_item'      => __('Add New Model', 'flavor'),
            'search_items'      => __('Search Models', 'flavor'),
            'not_found'         => __('No models found', 'flavor'),
        ];

        register_taxonomy(self::TAXONOMY, [ModelCPT::POST_TYPE], [
            'labels'            => $labels,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'hierarchical'      => false,
            'query_var'         => self::TAXONOMY,
            'rewrite'           => ['slug' => self::URL_SLUG, 'with_front' => false],
            'show_in_rest'      => true,
        ]);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^actor/([^/]+)/?$', 'index.php?' . self::TAXONOMY . '=$matches[1]', 'top');
        add_rewrite_rule('^actors/([^/]+)/?$', 'index.php?' . self::TAXONOMY . '=$matches[1]', 'top');

        if (!get_option('tmw_models_rewrite_v5')) {
            flush_rewrite_rules(false);
            update_option('tmw_models_rewrite_v5', 1);
        }
    }

    public function redirect_to_cpt() {
        if (!is_tax(self::TAXONOMY)) return;

        $term = get_queried_object();
        if (!$term instanceof \WP_Term) return;

        $model = get_page_by_path($term->slug, OBJECT, ModelCPT::POST_TYPE);
        if ($model) {
            wp_redirect(get_permalink($model), 301);
            exit;
        }
    }

    public function bind_to_post_types($post_type) {
        if (!taxonomy_exists(self::TAXONOMY)) return;
        $video_types = ['post', 'video'];
        if (in_array($post_type, $video_types, true)) {
            if (!is_object_in_taxonomy($post_type, self::TAXONOMY)) {
                register_taxonomy_for_object_type(self::TAXONOMY, $post_type);
            }
        }
    }

    public static function get_model_link($term) {
        if (!$term instanceof \WP_Term) return '';
        $model = get_page_by_path($term->slug, OBJECT, ModelCPT::POST_TYPE);
        if ($model) return get_permalink($model);
        $link = get_term_link($term);
        return is_wp_error($link) ? '' : $link;
    }
}
