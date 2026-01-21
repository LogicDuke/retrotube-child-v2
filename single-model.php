<?php
/**
 * Single Model template.
 *
 * @package suspended-flavor-flavor
 */

defined('ABSPATH') || exit;

get_header();
?>

<div id="primary" class="content-area with-sidebar-right">
    <main id="main" class="site-main with-sidebar-right" role="main">
        <?php
        while (have_posts()) :
            the_post();

            $post_id = get_the_ID();
            $model_slug = get_post_field('post_name', $post_id);

            // Get tags from model's videos
            $video_tags = [];
            if (function_exists('tmw_get_videos_for_model')) {
                $videos = tmw_get_videos_for_model($model_slug, -1);
                if (!empty($videos)) {
                    foreach ($videos as $video) {
                        $tags = wp_get_post_terms($video->ID, 'post_tag');
                        if (!is_wp_error($tags)) {
                            foreach ($tags as $tag) {
                                $video_tags[$tag->term_id] = $tag;
                            }
                        }
                    }
                }
            }

            // Sort alphabetically
            if (!empty($video_tags)) {
                uasort($video_tags, function($a, $b) {
                    return strcasecmp($a->name, $b->name);
                });
            }

            // Pass data to template
            set_query_var('tmw_model_tags_data', array_values($video_tags));
            set_query_var('tmw_model_tags_count', count($video_tags));

            get_template_part('template-parts/content', 'model');

            // Cleanup
            set_query_var('tmw_model_tags_data', []);
            set_query_var('tmw_model_tags_count', null);

        endwhile;
        ?>

        <?php get_template_part('partials/featured-models-block'); ?>

    </main>
</div>

<?php
get_sidebar();
get_footer();
