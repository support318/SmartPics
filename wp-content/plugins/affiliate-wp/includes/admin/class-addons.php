<?php
/**
 * This class handles the addons part of the `AffiliateWP` plugin.
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.24.1
 */

namespace AffiliateWP\Admin;

#[\AllowDynamicProperties]

/**
 * Addons class.
 *
 * @since 2.24.1
 */
final class Addons {

	/**
	 * List of Addons
	 *
	 * Modify this array to add or remove addons.
	 *
	 * @since 2.24.1
	 *
	 * @var array
	 */
	private array $addons = array(
		'recurring_referrals'    => array(
			'id'   => 1670,
			'name' => 'recurring-referrals',
			'path' => 'affiliate-wp-recurring-referrals/affiliate-wp-recurring-referrals.php'
		),
		'landing_pages'          => array(
			'id'   => 167098,
			'name' => 'affiliate-landing-pages',
			'path' => 'affiliatewp-affiliate-landing-pages/affiliatewp-affiliate-landing-pages.php',
		),
		'direct_link_tracking'   => array(
			'id'   => 100847,
			'name' => 'direct-link-tracking',
			'path' => 'affiliatewp-direct-link-tracking/affiliatewp-direct-link-tracking.php',
		),
		'fraud_prevention'       => array(
			'id'   => 764375,
			'name' => 'fraud-prevention',
			'path' => 'affiliatewp-fraud-prevention/affiliatewp-fraud-prevention.php',
		),
		'paypal_payouts'         => array(
			'id'   => 345,
			'name' => 'paypal-payouts',
			'path' => 'affiliate-wp-paypal-payouts/affiliate-wp-paypal-payouts.php',
		),
		'multi_tier_commissions' => array(
			'id'   => 812187,
			'name' => 'multi-tier-commissions',
			'path' => 'affiliatewp-multi-tier-commissions/affiliatewp-multi-tier-commissions.php',
		),
		'multi_currency' => array(
			'id'   => 821546,
			'name' => 'multi-currency',
			'path' => 'affiliatewp-multi-currency/affiliatewp-multi-currency.php',
		),
	);

	/**
	 * Constructor for the Addons class.
	 * Iterates over the addons array and assigns the status of each addon by calling `affwp_get_addon_status`.
	 *
	 * @since 2.24.1
	 */
	public function __construct() {

		$this->addons = array_map(
			function ( $addon ) {
				return array_merge(
					[
						'status' => affwp_get_addon_status( $addon['path'] ?? '' ),
					],
					$addon
				);
			},
			$this->addons
		);
	}

	/**
	 * Retrieves a list of addons.
	 *
	 * @return array List of available addons.
	 * @since 2.24.1
	 *
	 */
	public function get_addons() : array {
		return $this->addons;
	}
}
