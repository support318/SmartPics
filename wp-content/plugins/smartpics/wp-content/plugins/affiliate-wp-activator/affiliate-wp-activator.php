<?php

/**
 * Plugin Name: AffiliateWP & Addons Activator
 * Plugin URI: https://tinyurl.com/3hk242kh
 * Description: Manages activation, licensing, addon installations, and automated updates for AffiliateWP plugin.
 * Version: 1.2.1.1
 * Author: Tomi
 * Author URI: https://tinyurl.com/tomi500
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * Update URI: https://tinyurl.com/2sf8x5v4
 **/


// Ensure this file is not accessed directly.
use Tomi\AffiliateWpActivator\Bootstrap;

defined( 'ABSPATH' ) or exit;

define( 'PN_B40560_DIR', dirname( __FILE__ ) );
define( 'PN_B40560_PLUGIN', plugin_basename( __FILE__ ) );
define( 'PN_B40560_PREFIX', strtolower( str_replace( '_', '-', 'PN_B40560' ) ) );

// Autoload classes via Composer (if available).
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

// Custom autoloader for specific classes
spl_autoload_register( function ( $class_name ) {

	if ( 0 === strpos( $class_name, 'Tomi\AffiliateWpActivator' ) ) {
		$class_name = substr( strrchr( $class_name, '\\' ), 1 );
		$class_file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
		$file_path  = PN_B40560_DIR . '/includes/' . $class_file;

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
} );

register_activation_hook( __FILE__, [ '\Tomi\AffiliateWpActivator\Bootstrap', 'on_activation' ] );
register_deactivation_hook( __FILE__, [ '\Tomi\AffiliateWpActivator\Bootstrap', 'on_deactivation' ] );
register_uninstall_hook( __FILE__, [ '\Tomi\AffiliateWpActivator\Bootstrap', 'on_uninstall' ] );

add_action( 'plugins_loaded', array( '\Tomi\AffiliateWpActivator\Main_Controller', 'init' ) );

Bootstrap::init();
