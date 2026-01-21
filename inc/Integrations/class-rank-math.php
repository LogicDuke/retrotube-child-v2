<?php
/**
 * Rank Math SEO integration.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Integrations;

defined('ABSPATH') || exit;

use TMW\Models\ModelCPT;
use TMW\Categories\CategoryPages;

class RankMath {

    public function __construct() {
        add_filter('rank_math/post_types', [$this, 'register_post_types']);
        add_filter('rank_math/metabox/post_types', [$this, 'enable_metabox']);
        add_filter('rank_math/admin/editor_scripts', [$this, 'load_scripts']);
        add_filter('option_rank-math-options-titles', [$this, 'force_settings']);
    }

    public function register_post_types($post_types) {
        if (!is_array($post_types)) $post_types = [];
        if (!in_array(ModelCPT::POST_TYPE, $post_types, true)) $post_types[] = ModelCPT::POST_TYPE;
        if (!in_array(CategoryPages::POST_TYPE, $post_types, true)) $post_types[] = CategoryPages::POST_TYPE;
        return $post_types;
    }

    public function enable_metabox($post_types) {
        if (!is_array($post_types)) $post_types = [];
        if (!in_array(ModelCPT::POST_TYPE, $post_types, true)) $post_types[] = ModelCPT::POST_TYPE;
        if (!in_array(CategoryPages::POST_TYPE, $post_types, true)) $post_types[] = CategoryPages::POST_TYPE;
        return $post_types;
    }

    public function load_scripts($load) {
        global $post_type;
        $our_types = [ModelCPT::POST_TYPE, CategoryPages::POST_TYPE];
        return in_array($post_type, $our_types, true) ? true : $load;
    }

    public function force_settings($options) {
        if (!is_array($options)) return $options;
        $options['pt_' . ModelCPT::POST_TYPE . '_add_meta_box'] = 'on';
        $options['pt_' . CategoryPages::POST_TYPE . '_add_meta_box'] = 'on';
        return $options;
    }

    public static function is_active() {
        return class_exists('RankMath');
    }
}
