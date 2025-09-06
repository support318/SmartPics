<?php
/**
 * Admin Functions
 *
 * @since       2.14.0
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.15.0
 */

/**
 * Require the connector class file.
 *
 * @since 2.15.0
 *
 * @return void
 */
function affwp_admin_require_connector() {
    require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/class-connector.php';
}

/**
 * Upgrade link used within the various admin pages.
 *
 * @since 2.14.0
 *
 * @param string $medium  URL parameter: utm_medium.
 * @param string $content URL parameter: utm_content.
 *
 * @return string
 */
function affwp_admin_upgrade_link( string $medium = 'link', string $content = '' ) : string {

	$upgrade = affwp_utm_link(
		add_query_arg(
			'license_key',
			sanitize_text_field( affiliate_wp()->settings->get_license_key() ),
			'https://affiliatewp.com/pricing/'
		),
		/**
		 * Modify upgrade medium link.
		 *
		 * @since 2.14.0
		 *
		 * @param string $upgrade Upgrade medium link.
		 */
		apply_filters( 'affwp_upgrade_link_medium', $medium ),
		$content
	);

	/**
	 * Modify upgrade link.
	 *
	 * @since 2.14.0
	 *
	 * @param string $upgrade Upgrade links.
	 */
	return apply_filters( 'affwp_upgrade_link', $upgrade );
}

/**
 * Add UTM tags to a link that allows detecting traffic sources for our or partners' websites.
 *
 * @since 2.14.0
 *
 * @param string $link    Link to which you need to add UTM tags.
 * @param string $medium  The page or location description. Check your current page and try to find
 *                        and use an already existing medium for links otherwise, use a page name.
 * @param string $content The feature's name, the button's content, the link's text, or something
 *                        else that describes the element that contains the link.
 * @param string $term    Additional information for the content that makes the link more unique.
 *
 * @return string
 */
function affwp_utm_link( string $link, string $medium, string $content = '', string $term = '' ) : string {

	return add_query_arg(
		array_filter(
			array(
				'utm_campaign' => 'plugin',
				'utm_source'   => str_starts_with( $link, 'https://affiliatewp.com' )
					? 'WordPress'
					: 'affwpplugin',
				'utm_medium'   => rawurlencode( $medium ),
				'utm_content'  => rawurlencode( $content ),
				'utm_term'     => rawurlencode( $term ),
				'utm_locale'   => affwp_sanitize_key( get_locale() ),
			)
		),
		$link
	);
}

/**
 * Sanitize key, primarily used for looking up options.
 *
 * @since 2.14.0
 *
 * @param string $key Key name.
 *
 * @return string
 */
function affwp_sanitize_key( string $key = '' ) : string {
	return preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );
}
