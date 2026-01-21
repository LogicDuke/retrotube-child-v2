<?php
/**
 * Helper functions and utilities.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Core;

defined('ABSPATH') || exit;

class Helpers {

    public function __construct() {
        // Helpers are statically available
    }

    public static function get_attachment_id_from_url($url) {
        if (empty($url)) return 0;

        $cache_key = 'tmw_att_' . md5($url);
        $cached = wp_cache_get($cache_key, 'tmw');
        if ($cached !== false) return (int) $cached;

        global $wpdb;
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment' LIMIT 1",
            $url
        ));

        if (!$attachment_id) {
            $filename = basename(parse_url($url, PHP_URL_PATH));
            $attachment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
                '%' . $wpdb->esc_like($filename)
            ));
        }

        $attachment_id = (int) $attachment_id;
        wp_cache_set($cache_key, $attachment_id, 'tmw', HOUR_IN_SECONDS);
        return $attachment_id;
    }

    public static function get_image_dimensions($url, $default_w = 1035, $default_h = 350) {
        $id = self::get_attachment_id_from_url($url);
        if ($id) {
            $meta = wp_get_attachment_metadata($id);
            if (isset($meta['width'], $meta['height'])) {
                return ['width' => (int) $meta['width'], 'height' => (int) $meta['height']];
            }
        }
        return ['width' => $default_w, 'height' => $default_h];
    }

    public static function render_accordion($args = []) {
        $defaults = [
            'content_html' => '',
            'lines' => 3,
            'collapsed' => true,
            'class' => '',
        ];
        $args = wp_parse_args($args, $defaults);
        $content = trim($args['content_html']);
        if (empty($content)) return '';

        $id = uniqid('tmw-accordion-');
        $collapsed = $args['collapsed'] ? ' tmw-accordion-collapsed' : '';

        ob_start();
        ?>
        <div class="tmw-accordion<?php echo $args['class'] ? ' ' . esc_attr($args['class']) : ''; ?>">
            <div id="<?php echo esc_attr($id); ?>" class="tmw-accordion-content<?php echo $collapsed; ?>" data-tmw-accordion-lines="<?php echo (int) $args['lines']; ?>">
                <?php echo $content; ?>
            </div>
            <div class="tmw-accordion-toggle-wrap">
                <a class="tmw-accordion-toggle" href="javascript:void(0);" data-tmw-accordion-toggle aria-controls="<?php echo esc_attr($id); ?>" aria-expanded="<?php echo $args['collapsed'] ? 'false' : 'true'; ?>" data-readmore-text="<?php esc_attr_e('Read more', 'flavor'); ?>" data-close-text="<?php esc_attr_e('Close', 'flavor'); ?>">
                    <span class="tmw-accordion-text"><?php esc_html_e('Read more', 'flavor'); ?></span>
                    <i class="fa fa-chevron-down"></i>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function format_number($num) {
        $num = (int) $num;
        if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
        if ($num >= 1000) return round($num / 1000, 1) . 'K';
        return (string) $num;
    }
}
