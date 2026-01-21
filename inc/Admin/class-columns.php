<?php
/**
 * Custom admin columns for models.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Admin;

defined('ABSPATH') || exit;

use TMW\Models\ModelCPT;
use TMW\Models\ModelBanner;
use TMW\Models\ModelQuery;

class Columns {

    public function __construct() {
        add_filter('manage_' . ModelCPT::POST_TYPE . '_posts_columns', [$this, 'add_columns']);
        add_action('manage_' . ModelCPT::POST_TYPE . '_posts_custom_column', [$this, 'render_column'], 10, 2);
    }

    public function add_columns($columns) {
        $new = [];
        foreach ($columns as $key => $label) {
            if ($key === 'cb') {
                $new[$key] = $label;
                $new['thumbnail'] = __('Image', 'flavor');
                continue;
            }
            if ($key === 'date') {
                $new['videos'] = __('Videos', 'flavor');
                $new['views'] = __('Views', 'flavor');
                $new['rating'] = __('Rating', 'flavor');
            }
            $new[$key] = $label;
        }
        return $new;
    }

    public function render_column($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                $banner = ModelBanner::get_url($post_id);
                if (!$banner && has_post_thumbnail($post_id)) {
                    $banner = get_the_post_thumbnail_url($post_id, 'thumbnail');
                }
                echo $banner ? '<img src="' . esc_url($banner) . '" style="width:60px;height:60px;object-fit:cover;border-radius:4px;">' : '—';
                break;
            case 'videos':
                echo esc_html(ModelQuery::get_video_count(get_post_field('post_name', $post_id)));
                break;
            case 'views':
                $stats = ModelQuery::get_stats($post_id);
                echo esc_html(number_format_i18n($stats['views']));
                break;
            case 'rating':
                $stats = ModelQuery::get_stats($post_id);
                if ($stats['likes'] + $stats['dislikes'] === 0) { echo '—'; break; }
                $color = $stats['rating'] >= 70 ? '#46b450' : ($stats['rating'] >= 50 ? '#ffb900' : '#dc3232');
                printf('<span style="color:%s;">%s%%</span>', $color, esc_html($stats['rating']));
                break;
        }
    }
}
