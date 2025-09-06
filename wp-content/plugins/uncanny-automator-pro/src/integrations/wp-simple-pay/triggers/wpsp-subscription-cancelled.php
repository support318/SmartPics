<?php

namespace Uncanny_Automator_Pro;

use SimplePay\Vendor\Stripe\Customer;
use SimplePay\Vendor\Stripe\Invoice;
use SimplePay\Vendor\Stripe\Subscription;
use Uncanny_Automator\Recipe\Trigger;
use Uncanny_Automator\Wpsp_Tokens;

/**
 * Class WPSP_SUBSCRIPTION_CANCELLED
 * @package Uncanny_Automator_Pro
 */
class WPSP_SUBSCRIPTION_CANCELLED extends Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'WPSIMPLEPAY' );
		$this->set_trigger_code( 'WPSP_SUBSCRIPTION_CANCELLED' );
		$this->set_trigger_meta( 'WPSPFORMSUBSCRIPTION' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		$this->set_sentence(
			// translators: %s is a WP Simple Pay form
			sprintf( esc_attr_x( 'A subscription for {{a form:%1$s}} is cancelled', 'WP Simple Pay', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		$this->set_readable_sentence( esc_attr_x( 'A subscription for {{a form}} is cancelled', 'WP Simple Pay', 'uncanny-automator-pro' ) );
		$this->add_action( 'simpay_webhook_subscription_cancel', 10, 2 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$all_products = Automator()->helpers->recipe->wp_simple_pay->options->list_wp_simpay_forms(
			null,
			$this->get_trigger_meta(),
			array(
				'is_any'          => true,
				'is_subscription' => true,
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

		list( $type, $object ) = $hook_args;

		$selected_form_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$form_id          = $object->metadata->simpay_form_id;

		if ( ! isset( $form_id ) ) {
			return false;
		}

		$billing_email = $object->customer->email;
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
			array(
				'tokenId'   => 'AMOUNT_DUE',
				'tokenName' => esc_html_x( 'Amount due', 'Wp Simple Pay', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'AMOUNT_REMAINING',
				'tokenName' => esc_html_x( 'Amount remaining', 'Wp Simple Pay', 'uncanny-automator' ),
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
		/** @var Subscription $object */
		list( $type, $object ) = $hook_args;
		/** @var Invoice $invoice */
		$invoice = $object->latest_invoice;
		/** @var Customer $customer */
		$customer = $object->customer;
		$defaults = wp_list_pluck( $this->define_tokens( $trigger, array() ), 'tokenId' );
		$metadata = $object->metadata->toArray();

		$trigger_token_values = array(
			'WPSPFORMSUBSCRIPTION'   => get_the_title( $object->metadata->simpay_form_id ),
			'BILLING_NAME'           => $customer->name,
			'BILLING_EMAIL'          => $customer->email,
			'BILLING_TELEPHONE'      => $customer->phone,
			'BILLING_STREET_ADDRESS' => $customer->address,
			'PRICE_OPTION'           => Automator()->helpers->recipe->wp_simple_pay->options->get_price_option_value( $object->metadata->simpay_price_instances, $object->metadata->simpay_form_id ),
			'QUANTITY_PURCHASED'     => $object->metadata->simpay_quantity,
			'AMOUNT_DUE'             => simpay_format_currency( $invoice->amount_due ),
			'AMOUNT_PAID'            => simpay_format_currency( $invoice->amount_paid ),
			'AMOUNT_REMAINING'       => simpay_format_currency( $invoice->amount_remaining ),
		);
		foreach ( $metadata as $metadata_key => $value ) {
			if ( in_array( $metadata_key, $defaults, true ) ) {
				$trigger_token_values[ $metadata_key ] = $value;
			}
		}

		return $trigger_token_values;
	}
}
