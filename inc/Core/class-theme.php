<?php
/**
 * Main theme class - bootstraps all components.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Core;

defined('ABSPATH') || exit;

/**
 * Theme bootstrap class.
 */
final class Theme {

    const VERSION = '5.0.0';

    private static $instance = null;

    public $path;
    public $url;

    private $components = [];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->path = get_stylesheet_directory();
        $this->url = get_stylesheet_directory_uri();

        $this->load_components();
        $this->init_hooks();
    }

    private function load_components() {
        // Core
        $this->components['assets'] = new Assets();
        $this->components['helpers'] = new Helpers();

        // Models system
        $this->components['model_cpt'] = new \TMW\Models\ModelCPT();
        $this->components['model_taxonomy'] = new \TMW\Models\ModelTaxonomy();
        $this->components['model_banner'] = new \TMW\Models\ModelBanner();
        $this->components['model_query'] = new \TMW\Models\ModelQuery();
        $this->components['model_frontend'] = new \TMW\Models\ModelFrontend();
        $this->components['model_sync'] = new \TMW\Models\ModelSync();

        // Categories
        $this->components['category_pages'] = new \TMW\Categories\CategoryPages();

        // Frontend
        $this->components['voting'] = new \TMW\Frontend\Voting();
        $this->components['flipboxes'] = new \TMW\Frontend\Flipboxes();
        $this->components['shortcodes'] = new \TMW\Frontend\Shortcodes();
        $this->components['slot_banner'] = new \TMW\Frontend\SlotBanner();

        // Admin only
        if (is_admin()) {
            $this->components['admin_metaboxes'] = new \TMW\Admin\Metaboxes();
            $this->components['admin_columns'] = new \TMW\Admin\Columns();
        }

        // Integrations
        $this->components['rankmath'] = new \TMW\Integrations\RankMath();
        $this->components['awempire'] = new \TMW\Integrations\AWEmpire();

        // Performance
        $this->components['performance'] = new \TMW\Performance\Optimizer();
    }

    private function init_hooks() {
        add_action('after_setup_theme', [$this, 'setup_theme']);
        add_action('after_switch_theme', [$this, 'flush_rewrite_rules']);
    }

    public function setup_theme() {
        add_theme_support('post-thumbnails');
        add_theme_support('title-tag');
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
        add_image_size('tmw-hero-desktop', 1035, 350, true);
        add_image_size('tmw-hero-mobile', 480, 200, true);
        add_image_size('tmw-flipbox', 300, 400, true);
    }

    public function flush_rewrite_rules() {
        flush_rewrite_rules();
    }

    public function get($name) {
        return $this->components[$name] ?? null;
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }
}
