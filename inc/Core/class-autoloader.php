<?php
/**
 * PSR-4 style autoloader for theme classes.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Core;

defined('ABSPATH') || exit;

/**
 * Autoloader class.
 */
final class Autoloader {

    /**
     * Namespace prefix.
     *
     * @var string
     */
    private static $prefix = 'TMW\\';

    /**
     * Base directory for classes.
     *
     * @var string
     */
    private static $base_dir;

    /**
     * Register the autoloader.
     *
     * @param string $base_dir Base directory path.
     */
    public static function register($base_dir) {
        self::$base_dir = trailingslashit($base_dir);
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload a class.
     *
     * @param string $class Full class name.
     */
    public static function autoload($class) {
        $len = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $len) !== 0) {
            return;
        }

        $relative = substr($class, $len);
        $path = str_replace('\\', '/', $relative);
        $parts = explode('/', $path);
        $classname = array_pop($parts);
        
        // Convert CamelCase to kebab-case
        $filename = preg_replace('/([a-z])([A-Z])/', '$1-$2', $classname);
        $filename = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $filename);
        $filename = 'class-' . strtolower($filename) . '.php';

        $file = self::$base_dir . implode('/', $parts) . '/' . $filename;

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
