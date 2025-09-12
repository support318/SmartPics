<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services;

/**
 * Handles subscription extension operations
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services
 */
class Subscription_Extender {

	/**
	 * Whether the product is a variation
	 *
	 * @var bool
	 */
	private $is_variation = false;


	/**
	 * Duration type for extension
	 *
	 * @var string
	 */
	private $duration_type = 'day';


	/**
	 * Set product type as variation
	 *
	 * @return self
	 */
	public function as_variation() {
		$this->is_variation = true;
		return $this;
	}


	/**
	 * Set product type as normal subscription
	 *
	 * @return self
	 */
	public function as_subscription() {

		$this->is_variation = false;

		return $this;
	}


	/**
	 * Set the duration type
	 *
	 * @param string $type day|week|month|year
	 *
	 * @return self
	 * @throws \Exception
	 */
	public function set_duration_type( $type ) {

		$allowed_types = array( 'day', 'week', 'month', 'year' );

		if ( ! in_array( $type, $allowed_types, true ) ) {
			throw new \Exception(
				esc_html_x(
					'Invalid duration type. Allowed types are: day, week, month, year',
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		$this->duration_type = $type;

		return $this;
	}


	/**
	 * Extends subscription by specified duration
	 *
	 * @param int $user_id The user ID
	 * @param int $product_id The product ID (can be variation or normal product)
	 * @param int $duration Number of duration units to extend
	 *
	 * @throws \Exception When validation fails or subscription cannot be extended
	 * @return bool
	 */
	public function extend( $user_id, $product_id, $duration ) {
		$this->validate_product( $product_id );
		$this->validate_duration( $duration );

		$subscriptions = $this->get_active_subscriptions( $user_id, $product_id );

		foreach ( $subscriptions as $subscription ) {
			$this->process_extension( $subscription, $duration );
		}

		return true;
	}


	/**
	 * Validates the product
	 *
	 * @param int $product_id
	 *
	 * @throws \Exception
	 */
	private function validate_product( $product_id ) {
		$product = wc_get_product( absint( $product_id ) );

		if ( ! $product ) {
			throw new \Exception(
				esc_html_x(
					'Invalid product.',
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		if ( $this->is_variation && ! $product->is_type( 'variation' ) ) {
			throw new \Exception(
				esc_html_x(
					'The selected product is not a valid variable subscription product.',
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		// Check for all possible subscription product types
		$subscription_types = array( 'subscription', 'subscription_variation', 'variable-subscription' );

		if ( ! $this->is_variation && ! in_array( $product->get_type(), $subscription_types, true ) ) {
			throw new \Exception(
				esc_html_x(
					'The selected product is not a valid subscription product.',
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				)
			);
		}
	}


	/**
	 * Validates the duration
	 *
	 * @param int $duration
	 * @throws \Exception
	 */
	private function validate_duration( $duration ) {

		if ( absint( $duration ) <= 0 ) {
			throw new \Exception(
				esc_html_x(
					'Duration must be greater than 0',
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				)
			);
		}
	}


	/**
	 * Gets active subscriptions for user
	 *
	 * @param int $user_id
	 * @param int $product_id
	 *
	 * @throws \Exception
	 * @return array
	 */
	private function get_active_subscriptions( $user_id, $product_id ) {

		$subscriptions = wcs_get_subscriptions(
			array(
				'subscriptions_per_page' => -1,
				'orderby'                => 'start_date',
				'order'                  => 'DESC',
				'customer_id'            => $user_id,
				'product_id'             => $product_id,
				'subscription_status'    => array( 'active' ),
				'meta_query_relation'    => 'AND',
			)
		);

		if ( empty( $subscriptions ) ) {
			throw new \Exception(
				esc_html_x(
					'The user has no active subscriptions for this product.',
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				)
			);
		}

		return $subscriptions;
	}


	/**
	 * Processes the extension for a single subscription
	 *
	 * @param \WC_Subscription $subscription_list
	 * @param int              $duration
	 */
	private function process_extension( $subscription_list, $duration ) {

		$subscription = wcs_get_subscription( $subscription_list->get_id() );

		$next_payment = gmdate(
			'Y-m-d H:i:s',
			wcs_add_time( $duration, $this->duration_type, $subscription->get_time( 'next_payment' ) )
		);

		$subscription->update_dates( array( 'next_payment' => $next_payment ) );

		$this->add_extension_note( $subscription, $duration );
	}


	/**
	 * Adds note about the extension
	 *
	 * @param \WC_Subscription $subscription
	 * @param int              $duration
	 */
	private function add_extension_note( $subscription, $duration ) {

		$duration_label = $this->get_duration_label( $duration );

		$subscription->add_order_note(
			sprintf(
				// translators: %1$s: Duration label
				esc_html_x(
					'Subscription successfully extended by %1$s with Uncanny Automator.',
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				),
				$duration_label
			)
		);
	}


	/**
	 * Gets formatted duration label
	 *
	 * @param int $duration
	 *
	 * @return string
	 */
	private function get_duration_label( $duration ) {

		$labels = array(
			'day'   => _n( 'day', 'days', $duration, 'uncanny-automator-pro' ),
			'week'  => _n( 'week', 'weeks', $duration, 'uncanny-automator-pro' ),
			'month' => _n( 'month', 'months', $duration, 'uncanny-automator-pro' ),
			'year'  => _n( 'year', 'years', $duration, 'uncanny-automator-pro' ),
		);

		return sprintf( '%d %s', $duration, $labels[ $this->duration_type ] );
	}
}
