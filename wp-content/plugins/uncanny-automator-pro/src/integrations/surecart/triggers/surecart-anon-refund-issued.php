<?php

namespace Uncanny_Automator_Pro;

/**
 * Class SURECART_ANON_REFUND_ISSUED
 *
 * @package Uncanny_Automator
 */
class SURECART_ANON_REFUND_ISSUED extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @var SureCart_Pro_Tokens_New_Framework
	 */
	public $surecart_tokens;

	/**
	 * @var SureCart_Helpers
	 */
	public $helpers;

	/**
	 *
	 */
	private $checkout;

	/**
	 *
	 */
	private $charge;

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		if ( automator_free_older_than( '5.6' ) ) {
			return;
		}

		$this->helpers         = new SureCart_Pro_Helpers();
		$this->surecart_tokens = new SureCart_Pro_Tokens_New_Framework();
		$this->set_integration( 'SURECART' );
		$this->set_trigger_code( 'ANON_REFUND_ISSUED' );

		$this->set_support_link( $this->helpers->support_link( $this->trigger_code ) );

		$this->set_trigger_type( 'anonymous' );

		$this->set_trigger_meta( 'PRODUCT' );

		/* Translators: Product name */
		$this->set_sentence( sprintf( 'A refund for {{a product:%1$s}} is issued to a customer', $this->get_trigger_meta() ) );

		$this->set_readable_sentence( 'A refund for {{a product}} is issued to a customer' );

		$this->add_action( 'surecart/refund_succeeded', 10, 2 );

		$this->set_is_pro( true );
	}

	/**
	 * Method options
	 *
	 * @return void
	 */
	public function options() {
		return array( $this->helpers->get_products_dropdown() );
	}

	/**
	 * define_tokens
	 *
	 * @param  array $trigger
	 * @param  array $tokens
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {

		$output = array_merge(
			$tokens,
			$this->surecart_tokens->common_tokens(),
			$this->surecart_tokens->order_tokens(),
			$this->surecart_tokens->billing_tokens()
		);

		return $output;
	}


	/**
	 * validate
	 *
	 * @param  array $trigger
	 * @param  array $hook_args
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		$product_id = $trigger['meta'][ $this->get_trigger_meta() ];

		list( $refund ) = $hook_args;

		$this->charge = \SureCart\Models\Charge::with( array( 'checkout', 'checkout.order', 'checkout.purchases', 'purchase.product' ) )->find( $refund->charge );

		$this->checkout = $this->charge->checkout;

		if ( '-1' === $product_id ) {
			return true;
		}

		foreach ( $this->checkout->purchases->data as $purchase_data ) {

			$product = $purchase_data->product;

			if ( $product->id === $product_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Method hydrate_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		list( $refund ) = $hook_args;

		$product_ids = array();

		foreach ( $this->checkout->purchases->data as $purchase_data ) {

			$product = $purchase_data->product;

			$product_ids[] = $product->id;
		}

		$trigger_tokens = array(
			'PRODUCT' => implode( ', ', $product_ids ),
		);

		$common_tokens = $this->surecart_tokens->hydrate_common_tokens();

		$billing_tokens = $this->surecart_tokens->hydrate_billing_tokens( $this->checkout );

		$order_tokens = $this->surecart_tokens->hydrate_order_tokens( $this->checkout );

		return $trigger_tokens + $common_tokens + $order_tokens + $billing_tokens;
	}
}
