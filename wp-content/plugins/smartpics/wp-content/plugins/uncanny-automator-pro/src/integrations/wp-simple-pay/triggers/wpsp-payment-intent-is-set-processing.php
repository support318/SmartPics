<?php


namespace Uncanny_Automator_Pro;

use SimplePay\Core\Abstracts\Form;
use SimplePay\Vendor\Stripe\Customer;
use SimplePay\Vendor\Stripe\Event;
use SimplePay\Vendor\Stripe\PaymentIntent;
use Uncanny_Automator\Recipe\Trigger;
use Uncanny_Automator\Wpsp_Tokens;

/**
 * Class WPSP_PAYMENT_FOR_FORM_PARTIALLY_REFUNDED
 * @package Uncanny_Automator_Pro
 */
class WPSP_PAYMENT_INTENT_IS_SET_PROCESSING extends Trigger {

	/**
	 * @return mixed|void
	 */
	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'WPSIMPLEPAY' );
		$this->set_trigger_code( 'WPSP_PAYMENT_SET_TO_PROCESSING' );
		$this->set_trigger_meta( 'WPSPFORMS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		$this->set_sentence(
			// translators: %s is a WP Simple Pay form
			sprintf( esc_attr_x( 'A payment intent for {{a form:%1$s}} is set to processing', 'WP Simple Pay', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		$this->set_readable_sentence( esc_attr_x( 'A payment intent for {{a form}} is set to processing', 'WP Simple Pay', 'uncanny-automator-pro' ) );
		$this->add_action( 'simpay_webhook_payment_intent_processing', 10, 3 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$all_products = Automator()->helpers->recipe->wp_simple_pay->options->list_wp_simpay_forms(
			null,
			$this->get_trigger_meta(),
			array(
				'is_any' => true,
			)
		);
		$options      = array();
		foreach ( $all_products['options'] as $k => $option ) {
			$options[] = array(
				'text'  => $option,
				'value' => $k,
			);
		}

		return array(
			array(
				'input_type'  => 'select',
				'option_code' => $this->get_trigger_meta(),
				'label'       => esc_html_x( 'Form', 'WP Simple Pay', 'uncanny-automator-pro' ),
				'required'    => true,
				'options'     => $options,
			),
		);
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		if ( ! isset( $hook_args[0], $hook_args[1] ) ) {
			return false;
		}

		/** @var Event $event */
		/** @var PaymentIntent $payment_intent */
		/** @var Form $form */
		list( $event, $payment_intent, $form ) = $hook_args;

		$selected_form_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$form_id          = $form->id;

		if ( ! isset( $form_id ) ) {
			return false;
		}

		/** @var Customer $customer */
		$customer      = $payment_intent->customer;
		$billing_email = $customer->email;
		if ( is_email( $billing_email ) ) {
			$user_id = false === email_exists( $billing_email ) ? 0 : email_exists( $billing_email );
			$this->set_user_id( $user_id );
		}

		// Any form or specific form
		return ( intval( '-1' ) === intval( $selected_form_id ) || absint( $selected_form_id ) === absint( $form_id ) );
	}

	/**
	 * Define Tokens.
	 *
	 * @param array $tokens
	 * @param array $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$trigger_tokens      = new Wpsp_Tokens();
		$custom_field_tokens = $trigger_tokens->get_custom_field_tokens( $trigger['meta'][ $this->get_trigger_meta() ], $tokens, $this->get_trigger_code() );
		$trigger_tokens      = array(
			array(
				'tokenId'   => 'BILLING_NAME',
				'tokenName' => esc_html_x( 'Billing name', 'Wp Simple Pay', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'BILLING_EMAIL',
				'tokenName' => esc_html_x( 'Billing email', 'Wp Simple Pay', 'uncanny-automator' ),
				'tokenType' => 'email',
			),
			array(
				'tokenId'   => 'BILLING_TELEPHONE',
				'tokenName' => esc_html_x( 'Billing phone', 'Wp Simple Pay', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'BILLING_STREET_ADDRESS',
				'tokenName' => esc_html_x( 'Billing address', 'Wp Simple Pay', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'PRICE_OPTION',
				'tokenName' => esc_html_x( 'Price option', 'Wp Simple Pay', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'QUANTITY_PURCHASED',
				'tokenName' => esc_html_x( 'Quantity', 'Wp Simple Pay', 'uncanny-automator' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'AMOUNT_PAID',
				'tokenName' => esc_html_x( 'Amount paid', 'Wp Simple Pay', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
		);

		return array_merge( $trigger_tokens, $custom_field_tokens );
	}

	/**
	 * Hydrate Tokens.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		/** @var Event $event */
		/** @var PaymentIntent $payment_intent */
		/** @var Form $form */
		list( $event, $payment_intent, $form ) = $hook_args;
		/** @var Customer $customer */
		$customer = $payment_intent->customer;
		$defaults = wp_list_pluck( $this->define_tokens( $trigger, array() ), 'tokenId' );
		$metadata = $payment_intent->metadata->toArray();

		$trigger_token_values = array(
			'WPSPFORMSUBSCRIPTION'   => $form->company_name,
			'BILLING_NAME'           => $customer->name,
			'BILLING_EMAIL'          => $customer->email,
			'BILLING_TELEPHONE'      => $customer->phone,
			'BILLING_STREET_ADDRESS' => $customer->address,
			'PRICE_OPTION'           => Automator()->helpers->recipe->wp_simple_pay->options->get_price_option_value( $payment_intent->metadata->simpay_price_instances, $form->id ),
			'QUANTITY_PURCHASED'     => $payment_intent->metadata->simpay_quantity,
			'AMOUNT_PAID'            => simpay_format_currency( $payment_intent->amount ),
		);
		foreach ( $metadata as $metadata_key => $value ) {
			if ( in_array( $metadata_key, $defaults, true ) ) {
				$trigger_token_values[ $metadata_key ] = $value;
			}
		}

		return $trigger_token_values;
	}
}
