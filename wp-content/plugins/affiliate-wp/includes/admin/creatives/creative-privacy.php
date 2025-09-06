<?php
/**
 * Creative Privacy
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Creatives
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.15.0
 */

/*
 * Connectors
 * ====================
 */

/**
 * Creative Privacy: Connector UI for Connecting Creatives to Affiliate Groups.
 *
 * @since  2.13.0
 *
 * @return object Class instance.
 */
function affwp_creatives_to_affiliate_groups_privacy_connector() {

	static $instance = null;

	if ( ! is_null( $instance ) ) {
		return $instance;
	}

	require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/creatives/creative-privacy/affiliate-groups/class-connector.php';

	// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Used to cache.
	return $instance = new \AffiliateWP\Admin\Creatives\Creative_Privacy\Affiliate_Groups\Connector( 'privacy:connect-creatives-to-affiliate-groups' );
}
add_action( 'plugins_loaded', 'affwp_creatives_to_affiliate_groups_privacy_connector', 9 );

/**
 * Creative Privacy: Connector UI for Connecting Creatives to Affiliates (Privacy).
 *
 * @since  2.12.0
 *
 * @return object Class instance.
 */
function affwp_affiliate_to_creative_privacy_connector() {

	static $instance = null;

	if ( ! is_null( $instance ) ) {
		return $instance;
	}

	require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/creatives/creative-privacy/affiliates/class-connector.php';

	// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Used to cache instance.
	return $instance = new \AffiliateWP\Admin\Creatives\Creative_Privacy\Affiliates\Connector( 'privacy:creative-to-affiliate' );
}
add_action( 'plugins_loaded', 'affwp_affiliate_to_creative_privacy_connector', 9 );

/*
 * Customization's
 * =======================
 *
 * These customization's couldn't be added to the connector class
 * directly.
 */

/**
 * Change the position of affiliate groups (privacy) to before the status (Edit).
 *
 * @since 2.15.0
 *
 * @param string $filter  The normal filter placement.
 * @param mixed  $context The context (Connector class).
 *
 * @return string New filter position.
 */
function affwp_change_privacy_affiliate_groups_selector_position_edit( $filter, $context ) {

	if ( 'privacy:connect-creatives-to-affiliate-groups' !== ( $context->get_connector_id() ?? null ) ) {
		return $filter;
	}

	return 'affwp_edit_creative_before_status';
}
add_filter( 'affwp_filter_hook_name_affwp_edit_creative_bottom', 'affwp_change_privacy_affiliate_groups_selector_position_edit', 10, 2 );

/**
 * Change the position of affiliate groups (privacy) to before the status (New/Add).
 *
 * @since 2.15.0
 *
 * @param string $filter  The normal filter placement.
 * @param mixed  $context The context (Connector class).
 *
 * @return string New filter position.
 */
function affwp_change_privacy_affiliate_groups_selector_position_new( $filter, $context ) {

	if ( 'privacy:connect-creatives-to-affiliate-groups' !== ( $context->get_connector_id() ?? null ) ) {
		return $filter;
	}

	return 'affwp_new_creative_before_status';
}
add_filter( 'affwp_filter_hook_name_affwp_new_creative_bottom', 'affwp_change_privacy_affiliate_groups_selector_position_new', 10, 2 );

/**
 * Change the position of Affiliates (Privacy) Selector (Edit).
 *
 * @since 2.15.0
 *
 * @param string $filter The normal position filter.
 * @param object $context The context (connector).
 *
 * @return string New position filter.
 */
function affwp_change_privacy_affiliates_selector_position_edit( $filter, $context ) {

	if ( 'privacy:creative-to-affiliate' !== ( $context->get_connector_id() ?? null ) ) {
		return $filter;
	}

	return 'affwp_edit_creative_before_status';
}
add_filter( 'affwp_filter_hook_name_affwp_edit_creative_bottom', 'affwp_change_privacy_affiliates_selector_position_edit', 10, 2 );

/**
 * Change the position of Affiliates (Privacy) Selector (New/Add).
 *
 * @since 2.15.0
 *
 * @param string $filter The normal position filter.
 * @param object $context The context (connector).
 *
 * @return string New position filter.
 */
function affwp_change_privacy_affiliates_selector_position_new( $filter, $context ) {

	if ( 'privacy:creative-to-affiliate' !== ( $context->get_connector_id() ?? null ) ) {
		return $filter;
	}

	return 'affwp_new_creative_before_status';
}
add_filter( 'affwp_filter_hook_name_affwp_new_creative_bottom', 'affwp_change_privacy_affiliates_selector_position_new', 10, 2 );

/*
 * Scripts
 * ================
 */

function affwp_enqueue_creative_privacy_scripts() {

	if ( ! affwp_creatives_to_affiliate_groups_privacy_connector()->is_current_connectable_add_edit_page( 'creative' ) ) {
		return;
	}

	wp_enqueue_script(
		'affwp-creative-privacy',
		plugins_url(
			'assets/js/creative-privacy.js',
			AFFILIATEWP_PLUGIN_FILE
		),
		array(
			'jquery',
		),
		AFFILIATEWP_VERSION,
		false
	);
}
add_action( 'admin_enqueue_scripts', 'affwp_enqueue_creative_privacy_scripts' );