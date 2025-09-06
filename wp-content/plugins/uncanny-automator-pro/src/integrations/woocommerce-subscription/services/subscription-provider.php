<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services;

use WC_Subscription;
use WC_Product_Variation;

class Subscription_Provider {

	/**
	 * The subscription object.
	 *
	 * @var WC_Subscription
	 */
	protected $subscription;

	/**
	 * Constructor.
	 *
	 * @param WC_Subscription $subscription The subscription object.
	 */
	public function __construct( WC_Subscription $subscription ) {
		$this->subscription = $subscription;
	}

	/**
	 * Get the first variation from a subscription.
	 *
	 * @param WC_Subscription $subscription The subscription object.
	 *
	 * @return false|array{
	 *   variation_id: int,
	 *   variation_name: string,
	 *   parent_id: int,
	 *   parent_name: string,
	 *   attributes: array<string,mixed>,
	 *   formatted_attributes: string
	 * } Empty array if no variation found or function not available.
	 */
	public function get_subscription_variation() {

		if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'wc_get_formatted_variation' ) ) {
			return false;
		}

		// Loop through items and bail as soon as we find a valid variation
		foreach ( $this->subscription->get_items() as $item ) {

			$variation_id = (int) $item->get_variation_id();

			if ( ! $variation_id ) {
				continue;
			}

			$variation = wc_get_product( $variation_id );
			if ( ! ( $variation instanceof WC_Product_Variation ) ) {
				continue;
			}

			return array(
				'variation_id'         => $variation_id,
				'variation_name'       => $variation->get_name(),
				'parent_id'            => $variation->get_parent_id(),
				'parent_name'          => get_the_title( $variation->get_parent_id() ),
				'attributes'           => $variation->get_attributes(),
				'formatted_attributes' => wc_get_formatted_variation( $variation, true, true, false ),
			);
		}

		// No variation found
		return array();
	}
}
