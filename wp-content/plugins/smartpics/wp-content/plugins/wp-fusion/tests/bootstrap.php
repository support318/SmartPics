<?php
/**
 * WP Fusion Test Suite Bootstrap
 */

// Use existing WordPress installation
define( 'ABSPATH', dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/' );

// Load WordPress
require_once ABSPATH . 'wp-load.php';

// Load test framework
require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/src/TestCases/TestCasePHPUnitGte8.php';

// Load our plugin
require_once dirname( __DIR__ ) . '/wp-fusion.php';
