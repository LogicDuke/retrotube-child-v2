<?php
/**
 * AWEmpire/LiveJasmin integration.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Integrations;

defined('ABSPATH') || exit;

class AWEmpire {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'tmw_awempire_feed';
        add_action('after_setup_theme', [$this, 'register_functions']);
    }

    public function register_functions() {
        $self = $this;
        if (!function_exists('tmw_aw_find_by_candidates')) {
            $GLOBALS['tmw_awempire'] = $self;
            function tmw_aw_find_by_candidates($candidates) {
                global $tmw_awempire;
                return isset($tmw_awempire) ? $tmw_awempire->find_by_candidates($candidates) : null;
            }
        }
        if (!function_exists('tmw_pick_banner_from_feed_row')) {
            function tmw_pick_banner_from_feed_row($row) {
                return AWEmpire::get_banner_from_row($row);
            }
        }
    }

    public function table_exists() {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name)) === $this->table_name;
    }

    public function find_by_candidates($candidates) {
        if (!$this->table_exists() || empty($candidates)) return null;

        global $wpdb;
        $candidates = array_filter(array_map('trim', (array) $candidates));
        if (empty($candidates)) return null;

        $placeholders = implode(',', array_fill(0, count($candidates), '%s'));
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE display_name IN ($placeholders) OR performer_id IN ($placeholders) LIMIT 1",
            array_merge($candidates, $candidates)
        ));
    }

    public static function get_banner_from_row($row) {
        if (!is_object($row)) return '';
        $fields = ['profile_picture_url', 'image_url', 'preview_url'];
        foreach ($fields as $field) {
            if (!empty($row->$field) && filter_var($row->$field, FILTER_VALIDATE_URL)) {
                return $row->$field;
            }
        }
        return '';
    }

    public function get_model($name) {
        return $this->find_by_candidates([$name]);
    }

    public function is_available() {
        return $this->table_exists();
    }
}
