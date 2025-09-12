<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Storage;

/**
 * This class is responsible for storing and retrieving subscriptions.
 *
 * Tightly coupled with the WooCommerce Subscription plugin and WooCommerce.
 */
class Subscription_Storage {

	/**
	 * Format the subscriptions as options
	 *
	 * @todo Delete this method once all subscription components are updated.
	 *
	 * @param array $subscriptions
	 * @return array
	 */
	private function to_automator_options( $subscriptions ) {

		$options = array();

		foreach ( $subscriptions as $product ) {
			$title = $product->get_name()
			? $product->get_name()
			: sprintf(
				/* translators: ID of the Product */
				esc_html_x( 'ID: %s (no title)', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				$product->get_id()
			);

			$options[] = array(
				'value' => $product->get_id(),
				'text'  => $title,
			);
		}

		return $options;
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return array
	 */
	public function get_subscriptions() {

		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$subscriptions = wc_get_products(
			array(
				'type'  => array( 'subscription', 'variable-subscription' ),
				'limit' => 99999,
			)
		);

		if ( empty( $subscriptions ) || ! is_array( $subscriptions ) ) {
			return array();
		}

		return $this->to_automator_options( (array) $subscriptions );
	}

	/**
	 * Get the product IDs of the subscription.
	 *
	 * @param \WC_Subscription $subscription The subscription.
	 * @return array
	 */
	public function get_subscription_product_ids( \WC_Subscription $subscription ) {

		$subscription_product_ids = array();

		foreach ( $subscription->get_items() as $item ) {
			$subscription_product_ids[] = $item->get_product_id();
		}

		return $subscription_product_ids;
	}
}
