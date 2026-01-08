<?php
if (!defined('ABSPATH')) { exit; }

if (!defined('TMW_CHILD_NS')) {
    define('TMW_CHILD_NS', 'TMW\\Child');
}

// Master debug toggle (set to false for production)
if (!defined('TMW_DEBUG')) {
    define('TMW_DEBUG', defined('WP_DEBUG') && WP_DEBUG);
}

// Banner-specific debug (inherits from TMW_DEBUG)
if (!defined('TMW_BANNER_DEBUG')) {
    define('TMW_BANNER_DEBUG', TMW_DEBUG);
}

// Registration audit (inherits from TMW_DEBUG)
if (!defined('TMW_REG_AUDIT')) {
    define('TMW_REG_AUDIT', TMW_DEBUG);
}

define('TMW_CHILD_ASSETS', TMW_CHILD_URL . '/assets');
