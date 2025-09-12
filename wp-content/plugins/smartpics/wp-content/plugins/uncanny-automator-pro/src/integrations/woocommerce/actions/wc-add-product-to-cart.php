<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WC_ADD_PRODUCT_TO_CART
 *
 * @package Uncanny_Automator\Recipe
 */
class WC_ADD_PRODUCT_TO_CART extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @var string
	 */
	protected $action_code;

	/**
	 * @var string
	 */
	protected $action_meta;

	/**
	 * Define the action properties.
	 */
	public function setup_action() {
		$this->set_integration( 'WC' );
		$this->set_action_code( 'WC_ADD_PRODUCT_TO_CART' );
		$this->set_action_meta( 'WC_PRODUCT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		// translators: $1: Product ID
		$this->set_sentence( sprintf( esc_attr_x( 'Add {{a product:%1$s}} to the cart', 'Woocommerce', 'uncanny-automator' ), $this->action_meta ) );
		$this->set_readable_sentence( esc_attr_x( 'Add {{a product}} to the cart', 'Woocommerce', 'uncanny-automator' ) );
	}

	/**
	 * Define the action options.
	 *
	 * @return array
	 */
	public function options() {
		$products          = Automator()->helpers->recipe->woocommerce->options->pro->all_wc_products( null, 'WC_PRODUCTS', false, false );
		$formatted_options = array();

		foreach ( $products['options'] as $value => $text ) {
			$formatted_options[] = array(
				'text'  => esc_attr_x( $text, 'WooCommerce', 'uncanny-automator-pro' ),
				'value' => $value,
			);
		}

		return array(
			array(
				'input_type'               => 'select',
				'option_code'              => 'WC_PRODUCTS',
				'label'                    => esc_attr_x( 'Product', 'Woocommerce', 'uncanny-automator-pro' ),
				'required'                 => true,
				'options'                  => $formatted_options,
				'relevant_tokens'          => array(),
				'supports_multiple_values' => true,
			),
		);
	}

	/**
	 * Process the action.
	 *
	 * @param int    $user_id
	 * @param array  $action_data
	 * @param int    $recipe_id
	 * @param array  $args
	 * @param array  $parsed
	 *
	 * @return bool
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$product_ids = isset( $parsed['WC_PRODUCTS'] ) ? json_decode( $parsed['WC_PRODUCTS'], true ) : array();

		if ( empty( $product_ids ) ) {
			$this->add_log_error( esc_html_x( 'No products selected.', 'Woocommerce', 'uncanny-automator' ) );
			return false;
		}

		// initialize session and cart if not initialized
		if ( is_null( WC()->session ) && method_exists( WC(), 'initialize_session' ) ) {
			WC()->initialize_session();
		}
		if ( is_null( WC()->cart ) && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		$results = array(
			'success' => array(),
			'failed'  => array(),
		);

		foreach ( $product_ids as $product_id ) {
			$product_id = absint( $product_id );

			if ( ! $product_id ) {
				$results['failed'][] = array(
					'id'     => $product_id,
					'reason' => esc_html_x( 'Invalid product ID.', 'Woocommerce', 'uncanny-automator' ),
				);
				continue;
			}

			$product = wc_get_product( $product_id );

			if (
				! $product ||
				! $product->is_purchasable() ||
				! $product->is_in_stock() ||
				$product->is_type( 'variable' )
			) {
				$results['failed'][] = array(
					'id'     => $product_id,
					'reason' => esc_html_x( 'Product is invalid, out of stock, or not purchasable.', 'Woocommerce', 'uncanny-automator' ),
				);
				continue;
			}

			if ( WC()->cart ) {
				$result = WC()->cart->add_to_cart( $product_id );
				if ( $result ) {
					$results['success'][] = $product_id;
				} else {
					$results['failed'][] = array(
						'id'     => $product_id,
						'reason' => esc_html_x( 'Failed to add product to cart.', 'Woocommerce', 'uncanny-automator' ),
					);
				}
			}
		}

		if ( empty( $results['success'] ) ) {
			$this->add_log_error( esc_html_x( 'Failed to add any products to cart.', 'Woocommerce', 'uncanny-automator' ) );
			return false;
		}

		return true;
	}
}
