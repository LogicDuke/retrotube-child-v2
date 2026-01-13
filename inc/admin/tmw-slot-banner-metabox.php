<?php
/**
 * TMW Slot Banner Metabox - Bulletproof Version
 * Works with both Classic Editor and Gutenberg Block Editor
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register meta for REST API (required for Gutenberg)
add_action('init', function () {
    $args = [
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'auth_callback'     => function () {
            return current_user_can('edit_posts');
        },
    ];

    register_post_meta('model', '_tmw_slot_enabled', array_merge($args, [
        'sanitize_callback' => function ($v) {
            return $v === '1' ? '1' : '';
        },
    ]));

    register_post_meta('model', '_tmw_slot_mode', array_merge($args, [
        'sanitize_callback' => function ($v) {
            return in_array($v, ['widget', 'shortcode']) ? $v : 'shortcode';
        },
        'default'           => 'shortcode',
    ]));

    register_post_meta('model', '_tmw_slot_shortcode', array_merge($args, [
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => '[tmw_slot_machine]',
    ]));
});

// Add metabox
add_action('add_meta_boxes', function () {
    add_meta_box(
        'tmw-slot-banner',
        __('Slot Banner', 'retrotube-child'),
        'tmw_render_slot_banner_metabox',
        'model',
        'side',
        'default'
    );
});

function tmw_render_slot_banner_metabox($post)
{
    if (!$post || $post->post_type !== 'model') {
        return;
    }

    $enabled = get_post_meta($post->ID, '_tmw_slot_enabled', true) === '1';
    $mode = get_post_meta($post->ID, '_tmw_slot_mode', true);
    $shortcode = get_post_meta($post->ID, '_tmw_slot_shortcode', true);

    // Smart defaults
    if (!in_array($mode, ['widget', 'shortcode'])) {
        $mode = 'shortcode';
    }
    if ($shortcode === '') {
        $shortcode = '[tmw_slot_machine]';
    }

    wp_nonce_field('tmw_slot_banner_save', 'tmw_slot_banner_nonce');
    ?>
    <input type="hidden" name="tmw_slot_metabox_present" value="1" />

    <p>
        <label>
            <input type="checkbox" name="tmw_slot_enabled" value="1" <?php checked($enabled); ?> />
            <?php esc_html_e('Enable slot banner on this model page', 'retrotube-child'); ?>
        </label>
    </p>

    <p style="margin-top:10px;">
        <strong><?php esc_html_e('Banner source', 'retrotube-child'); ?></strong><br />
        <label style="display:block; margin-top:6px;">
            <input type="radio" name="tmw_slot_mode" value="widget" <?php checked($mode, 'widget'); ?> />
            <?php esc_html_e('Use Global Widget Area', 'retrotube-child'); ?>
        </label>
        <label style="display:block; margin-top:6px;">
            <input type="radio" name="tmw_slot_mode" value="shortcode" <?php checked($mode, 'shortcode'); ?> />
            <?php esc_html_e('Use Custom Shortcode', 'retrotube-child'); ?>
        </label>
    </p>

    <p style="margin-top:10px;">
        <label for="tmw_slot_shortcode" style="font-weight:600;">
            <?php esc_html_e('Shortcode:', 'retrotube-child'); ?>
        </label>
        <textarea id="tmw_slot_shortcode" name="tmw_slot_shortcode"
                  style="width:100%; min-height:60px;"><?php echo esc_textarea($shortcode); ?></textarea>
    </p>

    <p class="description">
        <?php esc_html_e('Default: [tmw_slot_machine]', 'retrotube-child'); ?>
    </p>
    <?php
}

// Classic Editor save
add_action('save_post_model', function ($post_id) {
    // Skip if not from our metabox
    if (!isset($_POST['tmw_slot_metabox_present'])) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    if (!isset($_POST['tmw_slot_banner_nonce']) ||
        !wp_verify_nonce($_POST['tmw_slot_banner_nonce'], 'tmw_slot_banner_save')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save enabled
    $enabled = isset($_POST['tmw_slot_enabled']) && $_POST['tmw_slot_enabled'] === '1';
    update_post_meta($post_id, '_tmw_slot_enabled', $enabled ? '1' : '');

    // Save mode (default to shortcode)
    $mode = isset($_POST['tmw_slot_mode']) ? sanitize_text_field($_POST['tmw_slot_mode']) : 'shortcode';
    if (!in_array($mode, ['widget', 'shortcode'])) {
        $mode = 'shortcode';
    }
    update_post_meta($post_id, '_tmw_slot_mode', $mode);

    // Save shortcode (default to [tmw_slot_machine])
    $shortcode = isset($_POST['tmw_slot_shortcode']) ? sanitize_textarea_field($_POST['tmw_slot_shortcode']) : '';
    if (trim($shortcode) === '') {
        $shortcode = '[tmw_slot_machine]';
    }
    update_post_meta($post_id, '_tmw_slot_shortcode', trim($shortcode));

    if (defined('TMW_DEBUG') && TMW_DEBUG) {
        error_log('[TMW-SLOT-SAVE] post_id=' . $post_id . ' enabled=' . ($enabled ? '1' : '0') . ' mode=' . $mode . ' shortcode=' . $shortcode);
    }
}, 10, 1);

// Gutenberg Block Editor save via REST API
add_action('rest_after_insert_model', function ($post, $request) {
    $meta = $request->get_param('meta');
    if (!is_array($meta)) {
        return;
    }

    $post_id = $post->ID;

    if (array_key_exists('_tmw_slot_enabled', $meta)) {
        update_post_meta($post_id, '_tmw_slot_enabled', $meta['_tmw_slot_enabled'] === '1' ? '1' : '');
    }

    if (array_key_exists('_tmw_slot_mode', $meta)) {
        $mode = in_array($meta['_tmw_slot_mode'], ['widget', 'shortcode']) ? $meta['_tmw_slot_mode'] : 'shortcode';
        update_post_meta($post_id, '_tmw_slot_mode', $mode);
    }

    if (array_key_exists('_tmw_slot_shortcode', $meta)) {
        $sc = trim(sanitize_textarea_field($meta['_tmw_slot_shortcode']));
        if ($sc === '') {
            $sc = '[tmw_slot_machine]';
        }
        update_post_meta($post_id, '_tmw_slot_shortcode', $sc);
    }

    if (defined('TMW_DEBUG') && TMW_DEBUG) {
        error_log('[TMW-SLOT-REST] post_id=' . $post_id . ' meta updated via REST API');
    }
}, 10, 2);

// Enqueue JS for Gutenberg metabox sync
add_action('enqueue_block_editor_assets', function () {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'model') {
        return;
    }

    $js_path = get_stylesheet_directory() . '/js/tmw-slot-metabox-sync.js';
    if (!file_exists($js_path)) {
        return;
    }

    wp_enqueue_script(
        'tmw-slot-metabox-sync',
        get_stylesheet_directory_uri() . '/js/tmw-slot-metabox-sync.js',
        ['wp-data', 'wp-api-fetch', 'wp-element'],
        filemtime($js_path),
        true
    );
});
