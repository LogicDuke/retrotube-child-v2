<?php
/**
 * Model sync - keep taxonomy and CPT in sync.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Models;

defined('ABSPATH') || exit;

class ModelSync {

    public function __construct() {
        add_action('created_models', [$this, 'sync_term_to_post'], 10, 2);
        add_action('edited_models', [$this, 'sync_term_to_post'], 10, 2);
        add_action('save_post_model', [$this, 'sync_post_to_term'], 20);
        add_action('save_post', [$this, 'sync_actors_models'], 20);
    }

    public function sync_term_to_post($term_id, $tt_id) {
        $term = get_term($term_id, ModelTaxonomy::TAXONOMY);
        if (!$term || is_wp_error($term)) return;

        // Find matching CPT post
        $model = get_page_by_path($term->slug, OBJECT, ModelCPT::POST_TYPE);
        
        if (!$model) {
            // Create new model post
            wp_insert_post([
                'post_type'   => ModelCPT::POST_TYPE,
                'post_title'  => $term->name,
                'post_name'   => $term->slug,
                'post_status' => 'publish',
                'meta_input'  => ['_tmw_linked_term_id' => $term_id],
            ]);
        }
    }

    public function sync_post_to_term($post_id) {
        if (wp_is_post_revision($post_id)) return;
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== ModelCPT::POST_TYPE) return;

        // Find or create matching term
        $term = get_term_by('slug', $post->post_name, ModelTaxonomy::TAXONOMY);
        
        if (!$term) {
            wp_insert_term($post->post_title, ModelTaxonomy::TAXONOMY, [
                'slug' => $post->post_name,
            ]);
        }
    }

    public function sync_actors_models($post_id) {
        if (wp_is_post_revision($post_id)) return;

        $post_type = get_post_type($post_id);
        if (!in_array($post_type, ['post', 'video'], true)) return;

        if (!taxonomy_exists('actors') || !taxonomy_exists(ModelTaxonomy::TAXONOMY)) return;

        // Get terms from both taxonomies
        $actors = wp_get_post_terms($post_id, 'actors', ['fields' => 'slugs']);
        $models = wp_get_post_terms($post_id, ModelTaxonomy::TAXONOMY, ['fields' => 'slugs']);

        // Sync actors to models
        if (!empty($actors) && !is_wp_error($actors)) {
            foreach ($actors as $slug) {
                $term = get_term_by('slug', $slug, ModelTaxonomy::TAXONOMY);
                if (!$term) {
                    $actor = get_term_by('slug', $slug, 'actors');
                    if ($actor) {
                        wp_insert_term($actor->name, ModelTaxonomy::TAXONOMY, ['slug' => $slug]);
                    }
                }
            }
            wp_set_post_terms($post_id, $actors, ModelTaxonomy::TAXONOMY, true);
        }
    }
}
