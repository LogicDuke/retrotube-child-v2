<?php
/**
 * Model Custom Post Type registration.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Models;

defined('ABSPATH') || exit;

class ModelCPT {

    const POST_TYPE = 'model';
    const ARCHIVE_SLUG = 'models';

    public function __construct() {
        add_action('init', [$this, 'register'], 5);
        add_filter('template_include', [$this, 'force_template'], 999);
    }

    public function register() {
        $labels = [
            'name'               => __('Models', 'flavor'),
            'singular_name'      => __('Model', 'flavor'),
            'menu_name'          => __('Models', 'flavor'),
            'add_new'            => __('Add New', 'flavor'),
            'add_new_item'       => __('Add New Model', 'flavor'),
            'edit_item'          => __('Edit Model', 'flavor'),
            'view_item'          => __('View Model', 'flavor'),
            'all_items'          => __('All Models', 'flavor'),
            'search_items'       => __('Search Models', 'flavor'),
            'not_found'          => __('No models found.', 'flavor'),
            'not_found_in_trash' => __('No models found in Trash.', 'flavor'),
        ];

        register_post_type(self::POST_TYPE, [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'has_archive'        => self::ARCHIVE_SLUG,
            'rewrite'            => ['slug' => self::POST_TYPE, 'with_front' => false],
            'hierarchical'       => false,
            'supports'           => ['title', 'editor', 'thumbnail', 'comments', 'excerpt'],
            'taxonomies'         => ['category', 'post_tag'],
            'menu_icon'          => 'dashicons-groups',
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
        ]);
    }

    public function force_template($template) {
        if (!is_singular(self::POST_TYPE)) return $template;
        $child = get_stylesheet_directory() . '/single-model.php';
        return file_exists($child) ? $child : $template;
    }

    public static function exists() {
        return post_type_exists(self::POST_TYPE);
    }
}
