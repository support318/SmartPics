<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints;

/**
 * Class Fetch_Subscriptions
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints
 */
class Fetch_Subscriptions {

	/**
	 * Get the initial options for the variation dropdown
	 *
	 * @param string $field_id
	 *
	 * @return array
	 */
	protected function get_initial_options( $field_id ) {

		// Disable the 'Any' dropdown option for specific action codes.
		if ( in_array( $field_id, array( 'WOOVARIATIONSUBS', 'WCS_PRODUCTS', 'WC_EXTENDUSERSUBSCRIPTION_META' ), true ) ) {
			return array();
		}

		return array(
			array(
				'text'  => esc_html_x( 'Any subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => -1,
			),
		);
	}

	/**
	 * Endpoint handler
	 *
	 * @return void
	 */
	public function handle() {

		// Capability check is handled as well.
		Automator()->utilities->verify_nonce(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		try {
			$this->get_subscription_products();
		} catch ( \Exception $e ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Get all subscription products
	 *
	 * @return void
	 */
	private function get_subscription_products() {

		// Check if WooCommerce Subscriptions is active
		if ( ! class_exists( '\WC_Subscriptions' ) ) {
			throw new \Exception( esc_html_x( 'WooCommerce Subscriptions is not active', 'WooCommerce Subscription', 'uncanny-automator-pro' ) );
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'subscription', 'variable-subscription' ),
				),
			),
		);

		$products = get_posts( $args );

		$options = $this->get_initial_options( automator_filter_input( 'field_id', INPUT_POST ) );

		foreach ( $products as $product ) {
			$options[] = array(
				'text'  => $product->post_title,
				'value' => $product->ID,
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'options' => $options,
			)
		);
	}
}
