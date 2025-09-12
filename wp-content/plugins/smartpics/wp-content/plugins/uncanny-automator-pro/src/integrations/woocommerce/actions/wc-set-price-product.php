<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WC_SET_PRICE_PRODUCT
 *
 * @package Uncanny_Automator_Pro
 */
class WC_SET_PRICE_PRODUCT {
	use Recipe\Actions;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'WC' );
		$this->set_action_code( 'WCSETPRICEPRODUCT' );
		$this->set_action_meta( 'WOOPRODUCT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - WooCommerce */
		$this->set_sentence( sprintf( esc_attr__( 'Change the price of {{a specific product:%1$s}} to {{a new price:%2$s}}', 'uncanny-automator-pro' ), $this->get_action_meta(), 'WCS_NEW_PRICE' ) );

		/* translators: Action - WooCommerce */
		$this->set_readable_sentence( esc_attr__( 'Change the price of {{a specific product}} to {{a new price}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		$options = array(
			'options' => array(
				Automator()->helpers->recipe->woocommerce->options->all_wc_products( esc_attr__( 'Product', 'uncanny-automator-pro' ) ),
				Automator()->helpers->recipe->field->float(
					array(
						'option_code'     => 'WCS_NEW_PRICE',
						'label'           => esc_attr__( 'Price', 'uncanny-automator-pro' ),
						'placeholder'     => esc_attr__( 'Example: 99.99', 'uncanny-automator-pro' ),
						'required'        => true,
						'input_type'      => 'float',
						'supports_tokens' => true,
					)
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		// Get product ID
		$product_id = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( wp_unslash( $parsed[ $this->get_action_meta() ] ) ) : 0;
		$product_id = (int) $product_id;

		if ( ! $product_id ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = __( 'Invalid product.', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		// get the product
		$product = wc_get_product( $product_id );

		// No product found
		if ( false === $product ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = __( "Product doesn't exist", 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		$new_price = Automator()->parse->text( str_replace( 'wc-', '', $action_data['meta']['WCS_NEW_PRICE'] ), $recipe_id, $user_id, $args );

		if ( empty( $new_price ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = __( 'Price is missing.', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		$new_price = floatval( $new_price );

		if ( $new_price < 0 ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = __( 'Invalid price.', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		$product->set_price( $new_price );
		$product->set_regular_price( $new_price );
		$product->save();

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}
}
