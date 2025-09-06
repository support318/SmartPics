<?php
// Automatically generated file. Do not edit manually!

/**
 * Description: AffiliateWP & Addons Activator Description
 * Author: Tomi
 * Version: 1.0.0
 * Author URI: https://babia.to/members/tomi500.20887/
 * Plugin URI: https://babia.to/download/    TODO
 * Network: true
 **/

// If this file is called directly, abort executing.
defined( 'ABSPATH' ) or exit;

define( 'PN_B40560_MODE_INIT', 1 );

if ( in_array( 'affiliate-wp-activator/affiliate-wp-activator.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ) {
	// plugin active load it.
	if ( @include_once trailingslashit( WP_PLUGIN_DIR ) . 'affiliate-wp-activator/affiliate-wp-activator.php' ) {
		define( 'PN_B40560_MODE', 1 );
	}
} elseif ( __DIR__ === WPMU_PLUGIN_DIR ) {
	// delete self because the plugin is disabled without removing mu loader
	@wp_delete_file( __FILE__ );
}

