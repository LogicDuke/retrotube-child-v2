<?php
/**
 * Admin metaboxes for models and banners.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Admin;

defined('ABSPATH') || exit;

use TMW\Models\ModelCPT;
use TMW\Models\ModelBanner;

class Metaboxes {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register']);
        add_action('save_post', [$this, 'save'], 10, 2);
    }

    public function register() {
        add_meta_box('tmw_banner_focal', __('Banner Position', 'flavor'), [$this, 'render_focal'], ModelCPT::POST_TYPE, 'side', 'default');
        add_meta_box('tmw_model_info', __('Model Information', 'flavor'), [$this, 'render_info'], ModelCPT::POST_TYPE, 'normal', 'high');
    }

    public function render_focal($post) {
        $focal_y = ModelBanner::get_focal_y($post->ID);
        $banner_url = ModelBanner::get_url($post->ID);
        wp_nonce_field('tmw_banner_focal', 'tmw_banner_focal_nonce');
        ?>
        <p>
            <label for="tmw_banner_focal_y">Vertical Position:</label>
            <input type="range" id="tmw_banner_focal_y" name="tmw_banner_focal_y" min="0" max="100" step="1" value="<?php echo esc_attr($focal_y); ?>">
            <span id="tmw_focal_value"><?php echo esc_html($focal_y); ?>%</span>
        </p>
        <?php if ($banner_url) : ?>
        <div class="tmw-banner-preview" style="margin-top:10px;max-height:150px;overflow:hidden;">
            <img src="<?php echo esc_url($banner_url); ?>" id="tmw_banner_preview_img" style="width:100%;object-fit:cover;object-position:50% <?php echo esc_attr($focal_y); ?>%;">
        </div>
        <?php else : ?>
        <p class="description">No banner image set.</p>
        <?php endif; ?>
        <script>jQuery(function($){$('#tmw_banner_focal_y').on('input',function(){var v=$(this).val();$('#tmw_focal_value').text(v+'%');$('#tmw_banner_preview_img').css('object-position','50% '+v+'%');});});</script>
        <?php
    }

    public function render_info($post) {
        $link = get_post_meta($post->ID, 'model_link', true);
        $link_label = get_post_meta($post->ID, 'model_link_label', true);
        $link_note = get_post_meta($post->ID, 'model_link_note', true);
        wp_nonce_field('tmw_model_info', 'tmw_model_info_nonce');
        ?>
        <table class="form-table">
            <tr><th><label for="tmw_model_link">Model Link URL</label></th>
            <td><input type="url" id="tmw_model_link" name="tmw_model_link" value="<?php echo esc_url($link); ?>" class="large-text"><p class="description">Link to model profile (e.g., cam site)</p></td></tr>
            <tr><th><label for="tmw_model_link_label">Link Button Label</label></th>
            <td><input type="text" id="tmw_model_link_label" name="tmw_model_link_label" value="<?php echo esc_attr($link_label); ?>" class="regular-text" placeholder="Watch Live"></td></tr>
            <tr><th><label for="tmw_model_link_note">Link Note</label></th>
            <td><input type="text" id="tmw_model_link_note" name="tmw_model_link_note" value="<?php echo esc_attr($link_note); ?>" class="large-text"></td></tr>
        </table>
        <?php
    }

    public function save($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== ModelCPT::POST_TYPE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['tmw_banner_focal_nonce']) && wp_verify_nonce($_POST['tmw_banner_focal_nonce'], 'tmw_banner_focal')) {
            if (isset($_POST['tmw_banner_focal_y'])) {
                update_post_meta($post_id, '_banner_focal_y', max(0, min(100, (float) $_POST['tmw_banner_focal_y'])));
            }
        }

        if (isset($_POST['tmw_model_info_nonce']) && wp_verify_nonce($_POST['tmw_model_info_nonce'], 'tmw_model_info')) {
            if (isset($_POST['tmw_model_link'])) update_post_meta($post_id, 'model_link', esc_url_raw($_POST['tmw_model_link']));
            if (isset($_POST['tmw_model_link_label'])) update_post_meta($post_id, 'model_link_label', sanitize_text_field($_POST['tmw_model_link_label']));
            if (isset($_POST['tmw_model_link_note'])) update_post_meta($post_id, 'model_link_note', sanitize_text_field($_POST['tmw_model_link_note']));
        }
    }
}
