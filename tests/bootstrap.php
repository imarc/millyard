<?php

/**
 * Bootstrap file for running tests.
 *
 * This file is used to set up the testing environment for the Millyard WordPress toolkit.
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Set up Brain Monkey for WordPress function mocking
\Brain\Monkey\setUp();

// Define WordPress constants if not already defined
if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (! defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

if (! defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}

if (! defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Mock WordPress functions that are commonly used
\Brain\Monkey\Functions\when('wp_get_theme')->justReturn((object)[
    'get_template_directory' => '/tmp/theme',
    'get_stylesheet_directory' => '/tmp/theme',
]);

\Brain\Monkey\Functions\when('get_theme_file_path')->alias(function ($path = '') {
    return '/tmp/theme/' . ltrim($path, '/');
});

\Brain\Monkey\Functions\when('get_theme_file_uri')->alias(function ($path = '') {
    return 'http://example.com/wp-content/themes/theme/' . ltrim($path, '/');
});

\Brain\Monkey\Functions\when('wp_get_environment_type')->justReturn('testing');

// Mock common WordPress functions
\Brain\Monkey\Functions\when('wp_parse_args')->alias(function ($args, $defaults) {
    if (is_object($args)) {
        $args = get_object_vars($args);
    }

    if (! is_array($args)) {
        $args = [];
    }

    return array_merge($defaults, $args);
});

\Brain\Monkey\Functions\when('wp_create_nonce')->alias(function ($action = -1) {
    return 'test_nonce_' . $action;
});

// Mock session_id function (requires patchwork.json configuration)
\Brain\Monkey\Functions\when('session_id')->justReturn('test_session_id');

// Mock WordPress action/filter functions
\Brain\Monkey\Functions\when('do_action')->justReturn(null);
\Brain\Monkey\Functions\when('add_action')->justReturn(true);
\Brain\Monkey\Functions\when('add_filter')->justReturn(true);
\Brain\Monkey\Functions\when('apply_filters')->returnArg(1);

// Clean up after each test
register_shutdown_function(function () {
    \Brain\Monkey\tearDown();
});
