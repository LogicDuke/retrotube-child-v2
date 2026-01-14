<?php
if (!defined('ABSPATH')) {
    exit;
}

register_shutdown_function(function () {
    if (function_exists('is_admin') && is_admin()) {
        return;
    }

    $error = error_get_last();
    if (!$error || !isset($error['type'])) {
        return;
    }

    $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($error['type'], $fatal_types, true)) {
        return;
    }

    $message = isset($error['message']) ? $error['message'] : '';
    $file = isset($error['file']) ? $error['file'] : '';
    $line = isset($error['line']) ? $error['line'] : '';

    error_log('[TMW-FATAL] message=' . $message . ' file=' . $file . ' line=' . $line);
});
