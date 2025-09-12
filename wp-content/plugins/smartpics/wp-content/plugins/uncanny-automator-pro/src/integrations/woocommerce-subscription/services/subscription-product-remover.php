<?php
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services;

use WC_Subscription;
use Exception;

/**
 * Handles removal of products from WooCommerce subscriptions
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services
 * @since 6.7+
 */
class Subscription_Product_Remover {

	/**
	 * Remove product from a specific subscription
	 *
	 * @param int $subscription_id The subscription ID
	 * @param int $product_id The product ID to remove
	 *
	 * @throws Exception If subscription is invalid or product removal fails
	 * @return bool True if product was removed
	 */
	public function remove_from_specific_subscription( $subscription_id, $product_id ) {

		$subscription = $this->get_subscription( $subscription_id );
		$this->validate_product( $product_id );

		return $this->remove_product_from_subscription( $subscription, $product_id );
	}

	/**
	 * Remove product from all active subscriptions for a user
	 *
	 * @param int $user_id The user ID
	 * @param int $product_id The product ID to remove
	 *
	 * @throws Exception If no active subscriptions or product removal fails
	 * @return bool True if product was removed from any subscription
	 */
	public function remove_from_all_active_subscriptions( $user_id, $product_id ) {

		$this->validate_product( $product_id );

		$subscriptions = $this->get_active_subscriptions( $user_id, $product_id );

		foreach ( $subscriptions as $subscription ) {
			$this->remove_product_from_subscription( $subscription, $product_id );
		}

		return true;
	}

	/**
	 * Get and validate a subscription by ID
	 *
	 * @param int $subscription_id The subscription ID
	 *
	 * @throws Exception If subscription is invalid
	 * @return WC_Subscription
	 */
	private function get_subscription( $subscription_id ) {

		$subscription = wcs_get_subscription( absint( $subscription_id ) );

		if ( ! $subscription instanceof WC_Subscription ) {
			throw new Exception(
				esc_html_x(
					'Invalid subscription ID provided.',
					'Woo Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		return $subscription;
	}

	/**
	 * Get all active subscriptions for a user containing specific product
	 *
	 * @param int $user_id The user ID
	 * @param int $product_id The product ID
	 *
	 * @throws Exception If no active subscriptions found
	 * @return array Array of WC_Subscription objects
	 */
	private function get_active_subscriptions( $user_id, $product_id ) {

		$subscriptions = wcs_get_subscriptions(
			array(
				'subscriptions_per_page' => -1,
				'orderby'                => 'start_date',
				'order'                  => 'DESC',
				'customer_id'            => $user_id,
				'product_id'             => absint( $product_id ),
				'subscription_status'    => array( 'active' ),
				'meta_query_relation'    => 'AND',
			)
		);

		if ( empty( $subscriptions ) ) {
			throw new Exception(
				esc_html_x(
					'No active subscriptions found for this user.',
					'Woo Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		return $subscriptions;
	}

	/**
	 * Validate if product is subscription type
	 *
	 * @param int $product_id The product ID to validate
	 *
	 * @throws Exception If product is not a subscription
	 * @return void
	 */
	private function validate_product( $product_id ) {

		$product = wc_get_product( absint( $product_id ) );

		if ( ! $product ) {
			throw new Exception(
				esc_html_x(
					'Product not found.',
					'Woo Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		// Check for all possible subscription product types
		$subscription_types = array( 'subscription', 'subscription_variation', 'variable-subscription', 'variation' );

		if ( ! in_array( $product->get_type(), $subscription_types, true ) ) {
			throw new Exception(
				esc_html_x(
					'The provided product is not a valid subscription product.',
					'Woo Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		// Additional validation for variation products
		if ( 'variation' === $product->get_type() ) {
			$parent_product = wc_get_product( $product->get_parent_id() );
			if ( ! $parent_product || ! $parent_product->is_type( 'variable-subscription' ) ) {
				throw new Exception(
					esc_html_x(
						'The provided variation is not from a subscription product.',
						'Woo Subscription',
						'uncanny-automator-pro'
					)
				);
			}
		}
	}

	/**
	 * Validate subscription has the product and items
	 *
	 * @param WC_Subscription $subscription The subscription to validate
	 * @param int             $product_id   The product ID to check
	 *
	 * @throws Exception If subscription validation fails
	 * @return void
	 */
	private function validate_subscription_has_product( $subscription, $product_id ) {

		// Verify product exists in subscription before proceeding
		if ( ! $subscription->has_product( $product_id ) ) {
			throw new Exception(
				esc_html_x(
					'The subscription does not contain the provided product.',
					'Woo Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		// Get subscription items and verify they exist
		$subscription_items = $subscription->get_items();
		if ( empty( $subscription_items ) ) {
			throw new Exception(
				esc_html_x(
					'The subscription has no items.',
					'Woo Subscription',
					'uncanny-automator-pro'
				)
			);
		}
	}

	/**
	 * Removes a product from a WooCommerce subscription
	 *
	 * @param WC_Subscription $subscription The subscription object
	 * @param int             $product_id   Product ID to remove
	 *
	 * @throws Exception If product cannot be removed or doesn't exist in subscription
	 * @return bool True if product is removed
	 */
	public function remove_product_from_subscription( $subscription, $product_id ) {

		// Cast product ID to integer for strict comparison
		$product_id = (int) $product_id;

		// Validate subscription has product and items
		$this->validate_subscription_has_product( $subscription, $product_id );

		// Pause subscription during modification
		$subscription->update_status( 'on-hold' );

		// Locate and remove the specified product
		foreach ( $subscription->get_items() as $item_id => $item ) {

			$product = $item->get_product();

			if ( $product && (int) $product->get_id() === $product_id ) {

				$subscription->remove_item( $item_id );
				$subscription->calculate_totals();
				$subscription->save();

				// Reactivate the subscription after successful modification
				$subscription->update_status( 'active' );
				return true;

			}
		}

		// Reactivate subscription as product wasn't found (shouldn't happen due to has_product check)
		$subscription->update_status( 'active' );

		throw new Exception(
			esc_html_x(
				'Failed to remove product from subscription.',
				'Woo Subscription',
				'uncanny-automator-pro'
			)
		);
	}
}
