<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class WC_ADD_PRODUCT_TO_ORDER {
	use Recipe\Actions;
	use Recipe\Action_Tokens;

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
		$this->set_action_code( 'WC_PRODUCT_TO_ORDER' );
		$this->set_action_meta( 'WC_ORDER' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		/* translators: Action - WooCommerce */
		$this->set_sentence( sprintf( esc_attr__( 'Add {{a product:%1$s}} to {{an order:%2$s}}', 'uncanny-automator-pro' ), 'WC_PRODUCTS:' . $this->get_action_meta(), $this->get_action_meta() ) );
		/* translators: Action - WooCommerce */
		$this->set_readable_sentence( esc_attr__( 'Add {{a product}} to {{an order}}', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens( Wc_Pro_Tokens::get_product_added_to_order_action_tokens(), $this->action_code );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {
		$options = array(
			'options_group' => array(
				$this->get_action_meta() => array(
					Automator()->helpers->recipe->woocommerce->options->pro->all_wc_products( null, 'WC_PRODUCTS', false, false ),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => $this->get_action_meta(),
							'label'       => esc_attr_x( 'Order ID', 'WooCommerce', 'uncanny-automator-pro' ),
						)
					),
					Automator()->helpers->recipe->field->float(
						array(
							'option_code' => 'WC_PRODUCT_PRICE',
							'label'       => esc_attr_x( 'Price', 'WooCommerce', 'uncanny-automator-pro' ),
							'description' => esc_attr_x( 'Leave Price empty to use the default product price', 'WooCommerce', 'uncanny-automator-pro' ),
							'required'    => false,
						)
					),
					Automator()->helpers->recipe->field->int(
						array(
							'option_code' => 'WC_PRODUCT_QTY',
							'label'       => esc_attr_x( 'Quantity', 'WooCommerce', 'uncanny-automator-pro' ),
							'default'     => 1,
							'required'    => false,
						)
					),
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
		// Get product ID & order ID
		$product_id = isset( $parsed['WC_PRODUCTS'] ) ? sanitize_text_field( $parsed['WC_PRODUCTS'] ) : 0;
		$order_id   = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : 0;
		$qty        = ! empty( $parsed['WC_PRODUCT_QTY'] ) ? (int) $parsed['WC_PRODUCT_QTY'] : 1;

		$order = wc_get_order( $order_id );

		// No order found
		if ( ! $order instanceof \WC_Order ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = sprintf( esc_html_x( 'The order #%d is not found.', 'WooCommerce', 'uncanny-automator-pro' ), $order_id );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = sprintf( esc_html_x( 'The product #%d is not found.', 'WooCommerce', 'uncanny-automator-pro' ), $product_id );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		// Initialize $price with the default product price
		$price = $product->get_price();

		// Check if $parsed['WC_PRODUCT_PRICE'] is set and is not an empty string
		if ( '' !== $parsed['WC_PRODUCT_PRICE'] ) {
			// Explicit check for string '0'
			if ( '0' === $parsed['WC_PRODUCT_PRICE'] || 0 === $parsed['WC_PRODUCT_PRICE'] ) {
				$price = 0;
			} else {
				// If not '0', use the provided value
				$price = $parsed['WC_PRODUCT_PRICE'];
			}
		}

		$product_status = $product->get_status();
		if ( 'publish' !== $product_status ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = sprintf( esc_html_x( 'The product %s is not published.', 'WooCommerce', 'uncanny-automator-pro' ), $product->get_title() );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$args = array(
			'subtotal' => $price,
			'total'    => $price,
		);

		$order->add_product( $product, $qty, $args );
		$this->hydrate_tokens( Wc_Pro_Tokens::hydrate_product_added_to_order_tokens( $order, $product_id ) );
		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}
}
