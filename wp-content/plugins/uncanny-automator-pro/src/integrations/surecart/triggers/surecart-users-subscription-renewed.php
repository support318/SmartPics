<?php

namespace Uncanny_Automator_Pro;

/**
 * Class SURECART_USERS_SUBSCRIPTION_RENEWED
 *
 * @package Uncanny_Automator
 */
class SURECART_USERS_SUBSCRIPTION_RENEWED extends \Uncanny_Automator\Recipe\Trigger {

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
	private $purchase;

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
		$this->set_trigger_code( 'USER_SUBSCRIPTION_RENEWED' );
		$this->set_trigger_meta( 'PRODUCT' );

		$this->set_support_link( $this->helpers->support_link( $this->trigger_code ) );

		$this->set_is_login_required( false );

		/* Translators: Product name */
		$this->set_sentence( sprintf( 'A user renews a subscription to {{a product:%1$s}}', $this->get_trigger_meta() ) );

		$this->set_readable_sentence( 'A user renews a subscription to {{a product}}' );

		$this->add_action( 'surecart/subscription_renewed', 10, 2 );

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
			$this->surecart_tokens->subscription_tokens(),
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

		list( $subscription, $event ) = $hook_args;

		// Find the right WP user
		$customer_id = $event->data->object->customer;

		$user = \SureCart\Models\User::findByCustomerId( $customer_id );

		if ( false === $user ) {
			return false;
		}

		$this->set_user_id( $user->ID );

		// Check the subscription product
		$product_id = $trigger['meta'][ $this->get_trigger_meta() ];

		if ( '-1' === $product_id ) {
			return true;
		}

		$this->purchase = \SureCart\Models\Purchase::with( array( 'purchase.product' ) )->find( $subscription->purchase );

		if ( $product_id === $this->purchase->product->id ) {
			return true;
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

		list( $subscription, $event ) = $hook_args;

		$subscription_info = \SureCart\Models\Subscription::with( array( 'purchase', 'purchase.initial_order', 'order.checkout', 'purchase.product', 'price', 'payment_method' ) )->find( $subscription->id );

		$trigger_tokens = array(
			'PRODUCT' => $subscription_info->purchase->product->name,
		);

		$common_tokens = $this->surecart_tokens->hydrate_common_tokens();

		$subscription_tokens = $this->surecart_tokens->hydrate_subscription_tokens( $subscription_info );

		return $trigger_tokens + $common_tokens + $subscription_tokens;
	}
}
