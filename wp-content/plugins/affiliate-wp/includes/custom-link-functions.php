<?php
/**
 * Custom Link Functions
 *
 * @package    AffiliateWP
 * @subpackage Core
 * @copyright  Copyright (c) 2023, Awesome Motive, Inc
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.14.0
 */

use AffWP\CustomLink;

/**
 * Retrieves the custom link object.
 *
 * @since 2.14.0
 *
 * @param int|CustomLink|null $custom_link Custom Link ID or object.
 * @return CustomLink|false Custom Link object, otherwise false.
 */
function affwp_get_custom_link( $custom_link = null ) {

	if ( is_object( $custom_link ) && isset( $custom_link->custom_link_id ) ) {
		return affiliate_wp()->custom_links->get_object( $custom_link->custom_link_id );
	}

	if ( is_numeric( $custom_link ) ) {
		return affiliate_wp()->custom_links->get_object( absint( $custom_link ) );
	}

	return false;
}

/**
 * Adds a new custom_link to the database.
 *
 * @since 2.14.0
 *
 * @param array $data Custom Link data.
 * @return int|false ID of the newly-created custom_link, otherwise false.
 */
function affwp_add_custom_link( array $data = array() ) {

	$custom_link_id = affiliate_wp()->custom_links->insert(
		array(
			'affiliate_id' => ! empty( $data['affiliate_id'] ) && affwp_get_affiliate( absint( $data['affiliate_id'] ) )
				? absint( $data['affiliate_id'] )
				: null,
			'link'         => ! empty( $data['link'] )
				? esc_url_raw(
					affwp_prepare_custom_link( $data['link'] )
				)
				: '',
			'campaign'     => ! empty( $data['campaign'] )
				? affwp_sanitize_campaign_field( $data['campaign'] )
				: '',
		)
	);

	if ( ! empty( $custom_link_id ) ) {
		return $custom_link_id;
	}

	return false;
}

/**
 * Updates a custom link.
 *
 * @since 2.14.0
 *
 * @param array $data Custom Link data.
 * @return bool True if the custom_link was updated, otherwise false.
 */
function affwp_update_custom_link( array $data = array() ) : bool {

	if ( empty( $data['custom_link_id'] ) ) {
		return false;
	}

	$custom_link = affwp_get_custom_link( absint( $data['custom_link_id'] ) );

	if ( ! is_a( $custom_link, 'AffWP\CustomLink' ) ) {
		return false; // It is not a Custom Link object.
	}

	if ( affiliate_wp()->custom_links->update(
		$custom_link->ID,
		array(
			'affiliate_id' => isset( $data['affiliate_id'] ) && affwp_get_affiliate( absint( $data['affiliate_id'] ) )
				? absint( $data['affiliate_id'] )
				: null,
			'link'         => ! empty( $data['link'] )
				? esc_url_raw(
					affwp_prepare_custom_link( $data['link'] )
				)
				: '',
			'campaign'     => ! empty( $data['campaign'] )
				? affwp_sanitize_campaign_field( $data['campaign'] )
				: '',
		),
		'',
		'custom_link'
	) ) {
		return true;
	}

	return false;

}

/**
 * Remove whitespaces from campaign name.
 *
 * @since 2.14.0
 *
 * @param string $campaign The campaign name.
 * @return string
 */
function affwp_sanitize_campaign_field( string $campaign ) : string {
	return preg_replace(
		'/\s+/',
		'',
		sanitize_text_field( $campaign )
	);
}

/**
 * Remove the ref var and campaign references from URL and prepare for database.
 *
 * @since 2.14.0
 *
 * @param string $url The url to be prepared.
 * @return string Url without the ref var and campaign references.
 */
function affwp_prepare_custom_link( string $url ) : string {

	$referral_var = affiliate_wp()->tracking->get_referral_var();

	// Remove the referral var.
	$link = remove_query_arg( array( $referral_var, 'campaign' ), $url );

	// Fallback for pretty permalinks.
	if ( str_contains( $link, $referral_var ) ) {
		return preg_replace( '/(\/' . $referral_var . ')[\/](\w\-*)+/', '', $link );
	}

	return $link;
}
