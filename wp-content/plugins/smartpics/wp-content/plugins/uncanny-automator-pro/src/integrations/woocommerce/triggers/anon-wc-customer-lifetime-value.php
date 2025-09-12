<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_WC_CUSTOMER_LIFETIME_VALUE
 * @package Uncanny_Automator_Pro
 */
class ANON_WC_CUSTOMER_LIFETIME_VALUE extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {

		$this->set_integration( 'WC' );
		$this->set_trigger_code( 'ANON_WC_CUSTOMER_LIFETIME_VALUE' );
		$this->set_trigger_meta( 'THRESHOLD' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/woocommerce/' ) );
		$this->set_is_login_required( false );
		$this->set_sentence(
			// translators: %1$s is a number comparison, %2$s is a specific amount
			sprintf( esc_html_x( 'A customer makes a payment and their lifetime value is {{greater than, less than, or equal to:%1$s}} {{a specific amount:%2$s}}', 'WooCommerce', 'uncanny-automator-pro' ), 'COMPARISON:' . $this->get_trigger_meta(), $this->get_trigger_meta() )
		);
		$this->set_readable_sentence( esc_html_x( 'A customer makes a payment and their lifetime value is {{greater than, less than, or equal to}} {{a specific amount}}', 'WooCommerce', 'uncanny-automator-pro' ) );
		$this->add_action( 'woocommerce_payment_complete', 99, 1 );
	}

	/**
	 * @return array[]
	 */
	public function options() {

		$amount_conditions  = Automator()->helpers->recipe->field->less_or_greater_than();
		$comparison_options = array();
		foreach ( $amount_conditions['options'] as $value => $text ) {
			$comparison_options[] = array(
				'text'  => $text,
				'value' => $value,
			);
		}

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => 'COMPARISON',
				'label'           => esc_html_x( 'Comparison', 'WooCommerce', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $comparison_options,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => esc_html_x( 'Amount', 'WooCommerce', 'uncanny-automator-pro' ),
				'required'        => true,
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ], $trigger['meta']['COMPARISON'] ) ) {
			return false;
		}

		$threshold  = floatval( $trigger['meta'][ $this->get_trigger_meta() ] );
		$comparison = $trigger['meta']['COMPARISON'];

		$order_id       = $hook_args[0];
		$lifetime_value = Automator()->helpers->recipe->woocommerce->pro->get_raw_customer_lifetime_value( $order_id );
		$lifetime_value = floatval( $lifetime_value );

		if ( is_null( $lifetime_value ) ) {
			return false;
		}

		switch ( $comparison ) {
			case '=':
				if ( $lifetime_value !== $threshold ) {
					return false;
				}
				break;
			case '!=':
				if ( $lifetime_value === $threshold ) {
					return false;
				}
				break;
			case '<':
				if ( $lifetime_value >= $threshold ) {
					return false;
				}
				break;
			case '>':
				if ( $lifetime_value <= $threshold ) {
					return false;
				}
				break;
			case '>=':
				if ( $lifetime_value < $threshold ) {
					return false;
				}
				break;
			case '<=':
				if ( $lifetime_value > $threshold ) {
					return false;
				}
				break;
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

		// Customer-related tokens
		$tokens[] = array(
			'tokenId'   => 'WC_CUSTOMER_LIFETIME_VALUE',
			'tokenName' => esc_html_x( "Customer's lifetime value", 'WooCommerce', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$tokens[] = array(
			'tokenId'   => 'WC_CUSTOMER_LIFETIME_VALUE_RAW',
			'tokenName' => esc_html_x( "Customer's lifetime value (unformatted)", 'WooCommerce', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		// Billing-related tokens
		$billing_tokens = array(
			array(
				'tokenId'   => 'WC_BILLING_FIRST_NAME',
				'tokenName' => esc_html_x( 'Billing first name', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_LAST_NAME',
				'tokenName' => esc_html_x( 'Billing last name', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_COMPANY',
				'tokenName' => esc_html_x( 'Billing company', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_COUNTRY',
				'tokenName' => esc_html_x( 'Billing country', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_ADDRESS_1',
				'tokenName' => esc_html_x( 'Billing address line 1', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_ADDRESS_2',
				'tokenName' => esc_html_x( 'Billing address line 2', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_CITY',
				'tokenName' => esc_html_x( 'Billing city', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_STATE',
				'tokenName' => esc_html_x( 'Billing state', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_POSTCODE',
				'tokenName' => esc_html_x( 'Billing postcode', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_PHONE',
				'tokenName' => esc_html_x( 'Billing phone', 'WooCommerce', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'WC_BILLING_EMAIL',
				'tokenName' => esc_html_x( 'Billing email', 'WooCommerce', 'uncanny-automator-pro' ),
			),
		);

		foreach ( $billing_tokens as $billing_token ) {
			$tokens[] = array(
				'tokenId'   => $billing_token['tokenId'],
				'tokenName' => $billing_token['tokenName'],
				'tokenType' => 'text',
			);
		}

		return $tokens;
	}

	/**
	 * Hydrate_tokens
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$order_id = $hook_args[0];
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return array();
		}

		$lifetime_value = Automator()->helpers->recipe->woocommerce->pro->get_raw_customer_lifetime_value( $order_id );

		return array(
			'WC_CUSTOMER_LIFETIME_VALUE'     => wc_price( $lifetime_value ),
			'WC_CUSTOMER_LIFETIME_VALUE_RAW' => $lifetime_value,
			'WC_BILLING_FIRST_NAME'          => $order->get_billing_first_name(),
			'WC_BILLING_LAST_NAME'           => $order->get_billing_last_name(),
			'WC_BILLING_COMPANY'             => $order->get_billing_company(),
			'WC_BILLING_COUNTRY'             => $order->get_billing_country(),
			'WC_BILLING_ADDRESS_1'           => $order->get_billing_address_1(),
			'WC_BILLING_ADDRESS_2'           => $order->get_billing_address_2(),
			'WC_BILLING_CITY'                => $order->get_billing_city(),
			'WC_BILLING_STATE'               => $order->get_billing_state(),
			'WC_BILLING_POSTCODE'            => $order->get_billing_postcode(),
			'WC_BILLING_PHONE'               => $order->get_billing_phone(),
			'WC_BILLING_EMAIL'               => $order->get_billing_email(),
		);
	}
}
