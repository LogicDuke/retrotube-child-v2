<?php
/**
 * Voting system - likes/dislikes for posts and models.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Frontend;

defined('ABSPATH') || exit;

class Voting {

    public function __construct() {
        add_action('wp_ajax_tmw_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_tmw_vote', [$this, 'handle_vote']);
        add_action('after_setup_theme', [$this, 'register_functions']);
    }

    public function register_functions() {
        if (!function_exists('tmw_get_post_like_link')) {
            function tmw_get_post_like_link($post_id = 0) { return Voting::render_buttons($post_id); }
        }
        if (!function_exists('tmw_get_model_views')) {
            function tmw_get_model_views($post_id) { return Voting::get_views($post_id); }
        }
        if (!function_exists('tmw_get_model_likes')) {
            function tmw_get_model_likes($post_id) { return Voting::get_likes($post_id); }
        }
        if (!function_exists('tmw_get_model_dislikes')) {
            function tmw_get_model_dislikes($post_id) { return Voting::get_dislikes($post_id); }
        }
        if (!function_exists('tmw_get_model_rating_percent')) {
            function tmw_get_model_rating_percent($post_id) { return Voting::get_rating_percent($post_id); }
        }
        // Backward compatibility
        if (!function_exists('tmw_get_post_views_count')) {
            function tmw_get_post_views_count($post_id) { return Voting::get_views($post_id); }
        }
        if (!function_exists('tmw_get_post_likes_count')) {
            function tmw_get_post_likes_count($post_id) { return Voting::get_likes($post_id); }
        }
        if (!function_exists('tmw_get_post_dislikes_count')) {
            function tmw_get_post_dislikes_count($post_id) { return Voting::get_dislikes($post_id); }
        }
        if (!function_exists('tmw_get_post_like_rate')) {
            function tmw_get_post_like_rate($post_id) { return Voting::get_rating_percent($post_id); }
        }
    }

    public function handle_vote() {
        check_ajax_referer('tmw_voting_nonce', 'nonce');
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $action = isset($_POST['vote_action']) ? sanitize_key($_POST['vote_action']) : '';

        if (!$post_id || !in_array($action, ['like', 'dislike'], true)) {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        $cookie_key = 'tmw_voted_' . $post_id;
        if (isset($_COOKIE[$cookie_key])) {
            wp_send_json_error(['message' => 'Already voted']);
        }

        $meta_key = $action === 'like' ? 'likes_count' : 'dislikes_count';
        $current = (int) get_post_meta($post_id, $meta_key, true);
        update_post_meta($post_id, $meta_key, $current + 1);
        setcookie($cookie_key, $action, time() + (30 * DAY_IN_SECONDS), '/');

        wp_send_json_success([
            'likes'    => self::get_likes($post_id),
            'dislikes' => self::get_dislikes($post_id),
            'rating'   => self::get_rating_percent($post_id),
        ]);
    }

    public static function get_views($post_id) { return (int) get_post_meta($post_id, 'post_views_count', true); }
    public static function get_likes($post_id) { return (int) get_post_meta($post_id, 'likes_count', true); }
    public static function get_dislikes($post_id) { return (int) get_post_meta($post_id, 'dislikes_count', true); }

    public static function get_rating_percent($post_id) {
        $likes = self::get_likes($post_id);
        $dislikes = self::get_dislikes($post_id);
        $total = $likes + $dislikes;
        return $total === 0 ? 0.0 : round(($likes / $total) * 100, 1);
    }

    public static function render_buttons($post_id = 0) {
        if (!$post_id) $post_id = get_the_ID();
        $likes = self::get_likes($post_id);
        $dislikes = self::get_dislikes($post_id);
        $voted = isset($_COOKIE['tmw_voted_' . $post_id]);
        $disabled = $voted ? ' disabled' : '';

        return sprintf(
            '<span class="tmw-voting" data-post-id="%d">
                <button type="button" class="tmw-vote-btn tmw-like%s" data-action="like"%s><i class="fa fa-thumbs-up"></i> <span class="count">%d</span></button>
                <button type="button" class="tmw-vote-btn tmw-dislike%s" data-action="dislike"%s><i class="fa fa-thumbs-down fa-flip-horizontal"></i> <span class="count">%d</span></button>
            </span>',
            $post_id, $disabled, $disabled, $likes, $disabled, $disabled, $dislikes
        );
    }
}
