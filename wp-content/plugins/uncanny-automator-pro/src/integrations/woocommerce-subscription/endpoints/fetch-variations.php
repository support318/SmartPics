<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints;

/**
 * Class Fetch_Variations
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints
 */
class Fetch_Variations {

	/**
	 * Field ID
	 *
	 * The ID of the field that is fetching the data.
	 *
	 * @var string
	 */
	protected $field_id;

	/**
	 * Get the initial options
	 *
	 * @return array
	 */
	public function get_initial_options() {

		if ( in_array( $this->field_id, array( 'WCS_PRODUCTS', 'WC_EXTENDUSERVARIATIONSUBSCRIPTION_META' ), true ) ) {
			return array();
		}

		return array(
			array(
				'text'  => esc_html_x( 'Any variable subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
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

		$this->field_id = automator_filter_input( 'field_id', INPUT_POST );

		try {

			$this->get_variable_subscriptions();

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
	 * Get all variable subscription products
	 *
	 * @return void
	 */
	private function get_variable_subscriptions() {

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => array( 'variable-subscription' ),
				),
			),
		);

		$products = get_posts( $args );

		$options = $this->get_initial_options();

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
