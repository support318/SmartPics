<?php
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services;

/**
 * Order Resolver
 *
 * Resolves the order for a subscription.
 */
class Order_Resolver {

	/**
	 * @var \WC_Subscription
	 */
	private $subscription;

	/**
	 * Constructor
	 *
	 * @param \WC_Subscription $subscription The subscription.
	 */
	public function __construct( $subscription ) {

		if ( ! is_a( $subscription, 'WC_Subscription' ) ) {
			throw new \InvalidArgumentException( 'Expected WC_Subscription object.' );
		}

		$this->subscription = $subscription;
	}

	/**
	 * Get the order
	 *
	 * @return \WC_Order|null
	 */
	public function get_order() {

		// Try to get order data in different ways.
		$order = null;

		// First try parent order.
		$parent_order = $this->subscription->get_parent();
		if ( $parent_order ) {
			$order = $parent_order;
		}

		// If no parent order, try last order
		if ( ! $order ) {
			$last_order = $this->subscription->get_last_order();
			if ( $last_order ) {
				$order = $last_order;
			}
		}

		// If still no order, try the subscription itself since it extends WC_Order
		if ( ! $order ) {
			$order = $this->subscription;
		}

		return $order;
	}
}
