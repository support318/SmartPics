<?php
/**
 * Creative Categories
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Creatives
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.15.0
 */

/*
 * Connectors
 * =====================
 */

/**
 * Connector UI for Connecting Creatives to Creative Categories.
 *
 * @since  2.12.0
 *
 * @return object Class instance.
 */
function affwp_creative_category_connector() {

	static $instance = null;

	if ( ! is_null( $instance ) ) {
		return $instance;
	}

	require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/creative-categories/class-connector.php';

	// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Used to cache instance.
	return $instance = new \AffiliateWP\Admin\Groups\Creative_Categories\Connector( 'creative-categories' );
}
add_action( 'plugins_loaded', 'affwp_creative_category_connector', 9 );

/*
 * Managers
 * =======================
 */

/**
 * Manager UI for Creative Categories
 *
 * @since  2.12.0
 *
 * @return object Class instance.
 */
function affwp_creative_category_manager() {

	static $instance = null;

	if ( ! is_null( $instance ) ) {
		return $instance;
	}

	require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/creative-categories/class-management.php';

	// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Used to cache instance.
	return $instance = new \AffiliateWP\Admin\Groups\Creative_Categories\Management( 'creative-categories' );
}
add_action( 'plugins_loaded', 'affwp_creative_category_manager', 10 );

/*
 * Customization's
 * =======================
 *
 * These customization's couldn't be added to the connector class
 * directly.
 */

/**
 * Change the position of creative categories to before the description (Edit).
 *
 * @since 2.15.0
 *
 * @param string $filter  The normal filter placement.
 * @param mixed  $context The context (Connector class).
 *
 * @return string New filter position.
 */
function affwp_change_creative_category_selector_position_edit( $filter, $context ) {

	if ( 'creative-categories' !== ( $context->get_connector_id() ?? null ) ) {
		return $filter;
	}

	return 'affwp_edit_before_description';
}
add_filter( 'affwp_filter_hook_name_affwp_edit_creative_bottom', 'affwp_change_creative_category_selector_position_edit', 10, 2 );

/**
 * Change the position of creative categories to before the description (New/Add).
 *
 * @since 2.15.0
 *
 * @param string $filter  The normal filter placement.
 * @param mixed  $context The context (Connector class).
 *
 * @return string New filter position.
 */
function affwp_change_creative_category_selector_position_new( $filter, $context ) {

	if ( 'creative-categories' !== ( $context->get_connector_id() ?? null ) ) {
		return $filter;
	}

	return 'affwp_new_before_description';
}
add_filter( 'affwp_filter_hook_name_affwp_new_creative_bottom', 'affwp_change_creative_category_selector_position_new', 10, 2 );
