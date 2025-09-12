<?php
/**
 * Referral Rate Filters
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.13.0
 */

/**
 * Overide the rate based on affiliate's affiliate group settings.
 *
 * @since 2.13.0
 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
 *                  loading priority.
 *
 * @param int|float $rate         The rate that is going to be used.
 * @param int       $affiliate_id The Affiliate's ID.
 * @param string    $type         The rate type.
 * @param string    $reference    Reference.
 *
 * @return int|float The rate for calculation.
 */
function affwp_maybe_override_affiliate_group_rate( $rate, $affiliate_id, string $type, string $reference ) {

	affwp_register_connectables();

	$affiliate_group_id = affwp_get_affiliate_group_id( intval( $affiliate_id ) );

	if ( ! is_numeric( $affiliate_group_id ) || intval( $affiliate_group_id ) <= 0 ) {
		return $rate; // No affiliate group ID for this affiliate.
	}

	// The meta will tell us if this has potential overrides.
	$group_meta = affiliate_wp()->groups->get_group_meta( $affiliate_group_id );

	if ( ! is_array( $group_meta ) ) {
		return $rate; // This shouldn't happen, but fail gracefully.
	}

	if ( ! isset( $group_meta['rate'] ) ) {
		return $rate; // Not custom rate.
	}

	if ( ! isset( $group_meta['rate-type'] ) || ! is_string( $group_meta['rate-type'] ) ) {
		return $rate; // You have to have a rate type set to use rate.
	}

	return 'percentage' === $group_meta['rate-type'] && ! is_float( $group_meta['rate'] )

		// Make sure and convert non-float percentage type rates.
		? $group_meta['rate'] / 100

		// Leave alone.
		: $group_meta['rate'];
}
add_filter( 'affwp_get_affiliate_rate', 'affwp_maybe_override_affiliate_group_rate', -9999, 4 );

/**
 * Override the rate type based on the affiliate's affiliate group setting.
 *
 * @since 2.13.0
 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
 *                  loading priority.
 *
 * @param string $type         The rate type that will be used.
 * @param int    $affiliate_id Affiliate's ID.
 *
 * @return string
 */
function affwp_maybe_override_affiliate_group_rate_type( string $type, $affiliate_id ) : string {

	affwp_register_connectables();

	$affiliate_group_id = affwp_get_affiliate_group_id( intval( $affiliate_id ) );

	if ( ! is_numeric( $affiliate_group_id ) || intval( $affiliate_group_id ) <= 0 ) {
		return $type; // No affiliate group ID for this affiliate.
	}

	// The meta will tell us if this has potential overrides.
	$group_meta = affiliate_wp()->groups->get_group_meta( $affiliate_group_id );

	if ( ! is_array( $group_meta ) ) {
		return $type; // This shouldn't happen, but fail gracefully.
	}

	if ( ! isset( $group_meta['rate-type'] ) ) {
		return $type; // No rate type set, use normal rate type.
	}

	if ( ! isset( $group_meta['rate'] ) ) {
		return $type; // You have to have a rate set to use the rate type.
	}

	if ( ! is_string( $group_meta['rate-type'] ) ) {
		return $type; // Rate type must be a valid string, how did we store a non-string?
	}

	// Prefer the rate set in the affiliate group.
	return $group_meta['rate-type'];
}
add_filter( 'affwp_get_affiliate_rate_type', 'affwp_maybe_override_affiliate_group_rate_type', -9999, 2 );

/**
 * Override the flat rate basis based on the affiliate's affiliate group setting.
 *
 * @since 2.13.0
 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
 *                  loading priority.
 *
 * @param string $type         The flat basis type.
 * @param int    $affiliate_id The affiliate's ID.
 *
 * @return string
 */
function affwp_maybe_override_affiliate_group_flat_rate_basis( string $type, int $affiliate_id ) : string {

	affwp_register_connectables();

	$affiliate_group_id = affwp_get_affiliate_group_id( intval( $affiliate_id ) );

	if ( ! is_numeric( $affiliate_group_id ) || intval( $affiliate_group_id ) <= 0 ) {
		return $type; // No affiliate group ID for this affiliate.
	}

	// The meta will tell us if this has potential overrides.
	$group_meta = affiliate_wp()->groups->get_group_meta( $affiliate_group_id );

	if ( ! is_array( $group_meta ) ) {
		return $type; // This shouldn't happen, but fail gracefully.
	}

	if ( ! isset( $group_meta['flat-rate-basis'] ) ) {
		return $type;
	}

	if ( ! isset( $group_meta['rate'] ) ) {
		return $type; // You have to have a rate set to use flat rate basis.
	}

	if ( ! isset( $group_meta['rate-type'] ) || 'flat' !== $group_meta['rate-type'] ) {
		return $type; // You have to have a rate type set, and it has to be flat to use flat rate basis.
	}

	if ( 'per-order' === trim( $group_meta['flat-rate-basis'] ) ) {
		return 'per_order'; // This is what the setting is called in the settings API.
	}

	return 'per_product';
}
add_filter( 'affwp_get_affiliate_flat_rate_basis', 'affwp_maybe_override_affiliate_group_flat_rate_basis', 10, 2 );


/**
 * Maybe warn about overriding rate settings.
 *
 * @since 2.13.0
 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
 *               loading priority.
 *
 * @param mixed            $value     The value shown in the table.
 * @param \Affwp\Affiliate $affiliate The Affiliate object.
 *
 * @return string
 */
function affwp_affiliate_table_rate_warn_affiliate_group_overrides( $value, \Affwp\Affiliate $affiliate ) {

	affwp_register_connectables();

	if ( ! affwp_affiliate_has_affiliate_group_overrides( $affiliate->affiliate_id ) ) {
		return $value; // No affiliate group overrides, just use what they have.
	}

	// Warn the user that the rate presented might be overriden by the affiliate group.
	return sprintf(
		'%1$s%2$s',
		$value,
		affwp_icon_tooltip(
			__( 'This affiliate is in an affiliate group that may have a custom rate, rate type, and/or flat rate basis setting that may be adjusting this value.', 'affiliate-wp' ),
			'warning',
			false // Return.
		)
	);
}
add_filter( 'affwp_affiliate_table_rate', 'affwp_affiliate_table_rate_warn_affiliate_group_overrides', -9999, 2 );
