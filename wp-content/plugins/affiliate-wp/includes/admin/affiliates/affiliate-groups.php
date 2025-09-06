<?php
/**
 * Affiliate Groups
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Affiliates
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.13.0
 */

/*
 * Connectors
 * ================
 */

/**
 * Connector UI for Connecting Affiliates to Affiliate Groups.
 *
 * @since  2.13.0
 *
 * @return object Class Instance.
 */
function affwp_affiliate_groups_connector() {

	static $instance = null;

	if ( ! is_null( $instance ) ) {
		return $instance;
	}

	require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/affiliate-groups/affiliates/class-connector.php';

	// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Used to cache.
	return $instance = new \AffiliateWP\Admin\Groups\Affiliate_Groups\Affiliates\Connector( 'affiliate-groups' );
}
add_action( 'plugins_loaded', 'affwp_affiliate_groups_connector', 9 );

/*
 * Managers
 * ================
 */

/**
 * Manager UI for Creating/Editing Affiliate Groups.
 *
 * @since  2.13.0
 *
 * @return object Class Instance.
 */
function affwp_affiliate_groups_manager() {

	static $instance = null;

	if ( ! is_null( $instance ) ) {
		return $instance;
	}

	require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/affiliate-groups/class-management.php';

	// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Used to cache instance.
	return $instance = new \AffiliateWP\Admin\Groups\Affiliate_Groups\Management( 'affiliate-groups' );
}
add_action( 'plugins_loaded', 'affwp_affiliate_groups_manager', 10 );
