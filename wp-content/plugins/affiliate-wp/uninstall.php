<?php
/**
 * Uninstall AffiliateWP
 *
 * @package     AffiliateWP
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load required classes.
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/add-ons.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-addons.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/settings/class-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-capabilities.php';

global $wp_roles, $wpdb;

if ( ! function_exists( 'affwp_get_sites' ) ) :

	/**
	 * Get all the sites in a multisite install.
	 *
	 * @since 2.9.6
	 *
	 * @return array
	 */
	function affwp_get_sites() {

		if ( ! is_multisite() ) {
			return array();
		}

		if ( true === version_compare( $GLOBALS['wp_version'], '4.6', '<' ) ) {

			// phpcs:ignore WordPress.WP.DeprecatedFunctions.wp_get_sitesFound -- For backwards compat.
			return wp_list_pluck( 'blog_id', wp_get_sites() );
		}

		return get_sites( array( 'fields' => 'ids' ) );
	}
endif;

$affiliate_wp_settings = new Affiliate_WP_Settings;

if ( $affiliate_wp_settings->get( 'uninstall_on_delete' ) ) {

	// Remove the affiliate area page.
	wp_delete_post( $affiliate_wp_settings->get( 'affiliates_page' ) );
	wp_delete_post( $affiliate_wp_settings->get( 'affiliates_login_page' ) );
	wp_delete_post( $affiliate_wp_settings->get( 'affiliates_registration_page' ) );

	// Remove all capabilities and roles.
	$caps = new Affiliate_WP_Capabilities;
	$caps->remove_caps();

	if ( is_multisite() ) {

		// Remove all database tables.
		foreach ( affwp_get_sites() as $site_id ) {

			switch_to_blog( $site_id );

			affiliate_wp_uninstall_tables();
			// Note, schedules are removed whether or not uninstall_on_delete is set, see below.

			restore_current_blog();

		}
	} else {

		affiliate_wp_uninstall_tables();

	}

	// Delete AI usage logging data.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'affiliatewp_ai_%';" );
}

/**
 * Uninstalls all database tables created by AffiliateWP.
 *
 * @since 2.1.1
 *
 * @global \wpdb $wpdb WordPress database abstraction layer.
 */
function affiliate_wp_uninstall_tables() {
	global $wpdb;

	/**
	 * Filter the tables we delete on uninstall.
	 *
	 * @since 2.12.0
	 *
	 * @var [type]
	 */
	$db_segments = array(
		'affiliate_wp_affiliates',
		'affiliate_wp_affiliatemeta',
		'affiliate_wp_campaigns',
		'affiliate_wp_coupons',
		'affiliate_wp_creatives',
		'affiliate_wp_customers',
		'affiliate_wp_customermeta',
		'affiliate_wp_payouts',
		'affiliate_wp_referrals',
		'affiliate_wp_referralmeta',
		'affiliate_wp_rest_consumers',
		'affiliate_wp_sales',
		'affiliate_wp_visits',
		'affiliate_wp_notifications',
		'affiliate_wp_connections',
		'affiliate_wp_groups',
	);

	// Remove all affwp_ options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'affwp\_%';" );

	// Remove all affwp_ metadata from postmeta table.
	$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'affwp\_%';" );

	foreach ( $db_segments as $segment ) {

		// Table.
		$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . $segment );

		// Options.
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%$segment%'" );
	}

	$wpdb->query( "DROP VIEW IF EXISTS {$wpdb->prefix}affiliate_wp_campaigns" );
}
