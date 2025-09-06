<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EDD_USER_SUBSCRIPTION_EXPIRES
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_USER_SUBSCRIPTION_EXPIRES extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {

		if ( ! class_exists( 'EDD_Recurring' ) ) {
			return;
		}

		$this->set_integration( 'EDD' );
		$this->set_trigger_code( 'EDDR_SUBSCRIPTION_EXPIRES' );
		$this->set_trigger_meta( 'EDDR_PRODUCTS' );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( "A user's subscription to {{a download:%1\$s}} expires", 'Easy Digital Downloads - Recurring Payments', 'uncanny-automator' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( "A user's subscription to {{a download}} expires", 'Easy Digital Downloads - Recurring Payments', 'uncanny-automator' ) );
		$this->add_action( 'edd_subscription_expired', 10, 2 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$options = Automator()->helpers->recipe->options->edd->all_edd_downloads( '', $this->get_trigger_meta(), true, true, true );

		$all_subscription_products = array();
		foreach ( $options['options'] as $key => $option ) {
			$all_subscription_products[] = array(
				'text'  => $option,
				'value' => $key,
			);
		}

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => _x( 'Download', 'Easy Digital Downloads - Recurring Payments', 'uncanny-automator' ),
				'required'        => true,
				'options'         => $all_subscription_products,
				'relevant_tokens' => $options['relevant_tokens'],
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_product_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$subscription_object = $hook_args[1];

		if ( intval( '-1' ) !== intval( $selected_product_id ) && absint( $selected_product_id ) !== absint( $subscription_object->product_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * define_tokens
	 *
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {

		$tokens[] = array(
			'tokenId'   => 'EDDR_EXPIRATION_DATE',
			'tokenName' => _x( 'Expiration date', 'Easy Digital Downloads - Recurring Payments', 'uncanny-automator' ),
			'tokenType' => 'date',
		);

		return $tokens;
	}

	/**
	 * hydrate_tokens
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$subscription_object = $hook_args[1];

		if ( ! class_exists( '\EDD_Payment' ) ) {
			return;
		}

		$payment = new \EDD_Payment( $subscription_object->parent_payment_id );

		$token_values = array(
			'EDDR_EXPIRATION_DATE'          => date_i18n( get_option( 'date_format' ), strtotime( $subscription_object->expiration ) ),
			'EDDR_PRODUCTS_DISCOUNT_CODES'  => $payment->discounts,
			'EDDR_PRODUCTS'                 => get_the_title( $subscription_object->product_id ),
			'EDDR_PRODUCTS_ID'              => $subscription_object->product_id,
			'EDDR_PRODUCTS_URL'             => get_permalink( $subscription_object->product_id ),
			'EDDR_PRODUCTS_THUMB_ID'        => get_post_thumbnail_id( $subscription_object->product_id ),
			'EDDR_PRODUCTS_THUMB_URL'       => get_the_post_thumbnail_url( $subscription_object->product_id ),
			'EDDR_PRODUCTS_ORDER_DISCOUNTS' => number_format( $payment->discounted_amount, 2 ),
			'EDDR_PRODUCTS_ORDER_SUBTOTAL'  => number_format( $payment->subtotal, 2 ),
			'EDDR_PRODUCTS_ORDER_TAX'       => number_format( $payment->tax, 2 ),
			'EDDR_PRODUCTS_ORDER_TOTAL'     => number_format( $payment->total, 2 ),
			'EDDR_PRODUCTS_PAYMENT_METHOD'  => $payment->gateway,
		);

		return $token_values;
	}
}
