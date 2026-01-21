<?php
/**
 * Retrotube Child Theme - Clean Architecture v5
 *
 * @package suspended-flavor-flavor
 * @version 5.0.0
 */

defined('ABSPATH') || exit;

// Theme constants
define('TMW_VERSION', '5.0.0');
define('TMW_PATH', get_stylesheet_directory());
define('TMW_URL', get_stylesheet_directory_uri());

// Load autoloader
require_once TMW_PATH . '/inc/Core/class-autoloader.php';
TMW\Core\Autoloader::register(TMW_PATH . '/inc/');

// Initialize theme
add_action('after_setup_theme', function () {
    TMW\Core\Theme::instance();
}, 5);

// Load parent theme textdomain
add_action('after_setup_theme', function () {
    load_theme_textdomain('flavor', TMW_PATH . '/languages');
}, 1);

/**
 * Global helper function to access theme instance.
 */
function tmw_theme() {
    return TMW\Core\Theme::instance();
}

/**
 * Backward compatibility - render accordion.
 */
if (!function_exists('tmw_render_accordion')) {
    function tmw_render_accordion($args = []) {
        return TMW\Core\Helpers::render_accordion($args);
    }
}

/**
 * Backward compatibility - render title bar.
 */
if (!function_exists('tmw_render_title_bar')) {
    function tmw_render_title_bar($title, $level = 1) {
        $tag = 'h' . max(1, min(6, (int) $level));
        return sprintf('<%1$s class="widget-title"><i class="fa fa-folder-open"></i>%2$s</%1$s>', $tag, esc_html($title));
    }
}

/**
 * Backward compatibility - sidebar layout wrapper.
 */
if (!function_exists('tmw_render_sidebar_layout')) {
    function tmw_render_sidebar_layout($context, $callback) {
        $class = function_exists('wpst_get_sidebar_position_class') ? wpst_get_sidebar_position_class() : 'with-sidebar-right';
        ?>
        <div id="primary" class="content-area <?php echo esc_attr($class); ?>">
            <main id="main" class="site-main <?php echo esc_attr($class); ?>" role="main">
                <?php call_user_func($callback); ?>
            </main>
        </div>
        <?php
        get_sidebar();
    }
}

/**
 * Backward compatibility - try parent template.
 */
if (!function_exists('tmw_try_parent_template')) {
    function tmw_try_parent_template($candidates) {
        $parent_dir = trailingslashit(get_template_directory());
        foreach ($candidates as $candidate) {
            $path = $parent_dir . ltrim($candidate, '/');
            if (file_exists($path)) {
                include $path;
                return true;
            }
        }
        return false;
    }
}

// Disable parent theme updates
add_filter('site_transient_update_themes', function ($value) {
    if (isset($value->response['retrotube'])) {
        unset($value->response['retrotube']);
    }
    return $value;
});
