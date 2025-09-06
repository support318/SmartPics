<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens;

/**
 * Class Order_Tokens
 *
 * Handles all order-related tokens for WooCommerce Subscriptions
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens
 */
class Order_Tokens {

	/**
	 * Order tokens
	 *
	 * @var array
	 */
	protected $order_tokens = array();

	/**
	 * Token identifier
	 *
	 * Some triggers have WOOSUBSCRIPTIONS as the token identifier.
	 * While others have their own unique identifier.
	 *
	 * @var string
	 */
	protected $token_identifier = 'WOOSUBSCRIPTIONS';

	/**
	 * Constructor
	 */
	public function __construct( $token_identifier = 'WOOSUBSCRIPTIONS' ) {

		// Set the token identifier.
		$this->token_identifier = $token_identifier;

		// Billing tokens
		$this->order_tokens[] = array(
			'tokenId'   => 'billing_first_name',
			'tokenName' => esc_html_x( 'Billing first name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_last_name',
			'tokenName' => esc_html_x( 'Billing last name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_company',
			'tokenName' => esc_html_x( 'Billing company', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_country',
			'tokenName' => esc_html_x( 'Billing country', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_country_name',
			'tokenName' => esc_html_x( 'Billing country (full name)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_address_1',
			'tokenName' => esc_html_x( 'Billing address line 1', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_address_2',
			'tokenName' => esc_html_x( 'Billing address line 2', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_city',
			'tokenName' => esc_html_x( 'Billing city', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_state',
			'tokenName' => esc_html_x( 'Billing state', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_state_name',
			'tokenName' => esc_html_x( 'Billing state (full name)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_postcode',
			'tokenName' => esc_html_x( 'Billing postcode', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_phone',
			'tokenName' => esc_html_x( 'Billing phone', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'billing_email',
			'tokenName' => esc_html_x( 'Billing email', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'email',
		);

		// Shipping tokens
		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_first_name',
			'tokenName' => esc_html_x( 'Shipping first name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_last_name',
			'tokenName' => esc_html_x( 'Shipping last name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_company',
			'tokenName' => esc_html_x( 'Shipping company', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_country',
			'tokenName' => esc_html_x( 'Shipping country', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_country_name',
			'tokenName' => esc_html_x( 'Shipping country (full name)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_address_1',
			'tokenName' => esc_html_x( 'Shipping address line 1', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_address_2',
			'tokenName' => esc_html_x( 'Shipping address line 2', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_city',
			'tokenName' => esc_html_x( 'Shipping city', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_state',
			'tokenName' => esc_html_x( 'Shipping state', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_state_name',
			'tokenName' => esc_html_x( 'Shipping state (full name)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_postcode',
			'tokenName' => esc_html_x( 'Shipping postcode', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		// Order details tokens
		$this->order_tokens[] = array(
			'tokenId'   => 'order_date',
			'tokenName' => esc_html_x( 'Order date', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'date',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_id',
			'tokenName' => esc_html_x( 'Order ID', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_comments',
			'tokenName' => esc_html_x( 'Order comments', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_total',
			'tokenName' => esc_html_x( 'Order total', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_total_raw',
			'tokenName' => esc_html_x( 'Order total (unformatted)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'float',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_status',
			'tokenName' => esc_html_x( 'Order status', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_subtotal',
			'tokenName' => esc_html_x( 'Order subtotal', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_subtotal_raw',
			'tokenName' => esc_html_x( 'Order subtotal (unformatted)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'float',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_tax',
			'tokenName' => esc_html_x( 'Order tax', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_tax_raw',
			'tokenName' => esc_html_x( 'Order tax (unformatted)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'float',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_discounts',
			'tokenName' => esc_html_x( 'Order discounts', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_discounts_raw',
			'tokenName' => esc_html_x( 'Order discounts (unformatted)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'float',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_coupons',
			'tokenName' => esc_html_x( 'Order coupons', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_products',
			'tokenName' => esc_html_x( 'Order products', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_products_qty',
			'tokenName' => esc_html_x( 'Order products and quantity', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'payment_method',
			'tokenName' => esc_html_x( 'Payment method', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_qty',
			'tokenName' => esc_html_x( 'Order quantity', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'int',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_products_links',
			'tokenName' => esc_html_x( 'Order products links', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_summary',
			'tokenName' => esc_html_x( 'Order summary', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'shipping_method',
			'tokenName' => esc_html_x( 'Shipping method', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_fees',
			'tokenName' => esc_html_x( 'Order fee', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_fees_raw',
			'tokenName' => esc_html_x( 'Order fee (unformatted)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'float',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_shipping',
			'tokenName' => esc_html_x( 'Shipping fee', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'order_shipping_raw',
			'tokenName' => esc_html_x( 'Shipping fee (unformatted)', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'float',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'user_total_spend',
			'tokenName' => esc_html_x( "User's total spend", 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		$this->order_tokens[] = array(
			'tokenId'   => 'user_total_spend_raw',
			'tokenName' => esc_html_x( "User's total spend (unformatted)", 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'tokenType' => 'float',
		);

		// Set the token identifier for each token.
		// Backwards compatibility.
		foreach ( $this->order_tokens as $key => $token ) {
			$this->order_tokens[ $key ]['tokenIdentifier'] = $this->token_identifier;
		}
	}

	/**
	 * Get tokens
	 *
	 * @return array
	 */
	public function get_tokens() {
		return $this->order_tokens;
	}

	/**
	 * Parse order tokens
	 *
	 * @param string    $token_id The token identifier
	 * @param \WC_Order $order The order object
	 *
	 * @return mixed The token value
	 */
	public function parse_token( $token_id, $order ) {

		// If the order is a numeric value, get the order object.
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		// If the order is not a valid order object, return an empty string.
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return '';
		}

		$token_value = '';

		try {
			switch ( $token_id ) {
				// Billing tokens
				case 'billing_first_name':
					$token_value = $order->get_billing_first_name();
					break;

				case 'billing_last_name':
					$token_value = $order->get_billing_last_name();
					break;

				case 'billing_company':
					$token_value = $order->get_billing_company();
					break;

				case 'billing_country':
					$token_value = $order->get_billing_country();
					break;

				case 'billing_country_name':
					$token_value = $this->get_country_name( $order->get_billing_country() );
					break;

				case 'billing_address_1':
					$token_value = $order->get_billing_address_1();
					break;

				case 'billing_address_2':
					$token_value = $order->get_billing_address_2();
					break;

				case 'billing_city':
					$token_value = $order->get_billing_city();
					break;

				case 'billing_state':
					$token_value = $order->get_billing_state();
					break;

				case 'billing_state_name':
					$token_value = $this->get_state_name( $order->get_billing_country(), $order->get_billing_state() );
					break;

				case 'billing_postcode':
					$token_value = $order->get_billing_postcode();
					break;

				case 'billing_phone':
					$token_value = $order->get_billing_phone();
					break;

				case 'billing_email':
					$token_value = $order->get_billing_email();
					break;

				// Shipping tokens
				case 'shipping_first_name':
					$token_value = $order->get_shipping_first_name();
					break;

				case 'shipping_last_name':
					$token_value = $order->get_shipping_last_name();
					break;

				case 'shipping_company':
					$token_value = $order->get_shipping_company();
					break;

				case 'shipping_country':
					$token_value = $order->get_shipping_country();
					break;

				case 'shipping_country_name':
					$token_value = $this->get_country_name( $order->get_shipping_country() );
					break;

				case 'shipping_address_1':
					$token_value = $order->get_shipping_address_1();
					break;

				case 'shipping_address_2':
					$token_value = $order->get_shipping_address_2();
					break;

				case 'shipping_city':
					$token_value = $order->get_shipping_city();
					break;

				case 'shipping_state':
					$token_value = $order->get_shipping_state();
					break;

				case 'shipping_state_name':
					$token_value = $this->get_state_name( $order->get_shipping_country(), $order->get_shipping_state() );
					break;

				case 'shipping_postcode':
					$token_value = $order->get_shipping_postcode();
					break;

				// Order tokens
				case 'order_date':
					$token_value = $order->get_date_created()->date_i18n( wc_date_format() );
					break;

				case 'order_id':
					$token_value = $order->get_id();
					break;

				case 'order_comments':
					$token_value = $order->get_customer_note();
					break;

				case 'order_total':
					$token_value = $order->get_formatted_order_total();
					break;

				case 'order_total_raw':
					$token_value = $order->get_total();
					break;

				case 'order_status':
					$token_value = wc_get_order_status_name( $order->get_status() );
					break;

				case 'order_subtotal':
					$token_value = wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) );
					break;

				case 'order_subtotal_raw':
					$token_value = $order->get_subtotal();
					break;

				case 'order_tax':
					$token_value = wc_price( $order->get_total_tax(), array( 'currency' => $order->get_currency() ) );
					break;

				case 'order_tax_raw':
					$token_value = $order->get_total_tax();
					break;

				case 'order_discounts':
					$token_value = wc_price( $order->get_total_discount(), array( 'currency' => $order->get_currency() ) );
					break;

				case 'order_discounts_raw':
					$token_value = $order->get_total_discount();
					break;

				case 'order_coupons':
					$token_value = implode( ', ', $order->get_coupon_codes() );
					break;

				case 'order_products':
					$token_value = $this->get_products_list( $order );
					break;

				case 'order_products_qty':
					$token_value = $this->get_products_with_qty( $order );
					break;

				case 'payment_method':
					$token_value = $order->get_payment_method_title();
					break;

				case 'order_qty':
					$token_value = $order->get_item_count();
					break;

				case 'order_products_links':
					$token_value = $this->get_products_links( $order );
					break;

				case 'order_summary':
					$token_value = $this->get_order_summary( $order );
					break;

				case 'shipping_method':
					$token_value = $order->get_shipping_method();
					break;

				case 'order_fees':
					$token_value = wc_price( $order->get_total_fees(), array( 'currency' => $order->get_currency() ) );
					break;

				case 'order_fees_raw':
					$token_value = $order->get_total_fees();
					break;

				case 'order_shipping':
					$token_value = wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) );
					break;

				case 'order_shipping_raw':
					$token_value = $order->get_shipping_total();
					break;

				case 'user_total_spend':
					$token_value = $this->get_user_total_spend( $order->get_user_id(), true );
					break;

				case 'user_total_spend_raw':
					$token_value = $this->get_user_total_spend( $order->get_user_id(), false );
					break;
			}
		} catch ( \Exception $e ) {
			automator_log(
				'WooCommerce Order Token Error',
				$e->getMessage(),
				true,
				'wc-order-tokens'
			);
		}

		return $this->format_value_with_na( $token_value );
	}

	/**
	 * Get country full name
	 *
	 * @param string $country_code
	 * @return string
	 */
	private function get_country_name( $country_code ) {
		$countries = WC()->countries->get_countries();
		return isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : $country_code;
	}

	/**
	 * Get state full name
	 *
	 * @param string $country_code
	 * @param string $state_code
	 * @return string
	 */
	private function get_state_name( $country_code, $state_code ) {
		$states = WC()->countries->get_states( $country_code );
		return isset( $states[ $state_code ] ) ? $states[ $state_code ] : $state_code;
	}

	/**
	 * Get products list
	 *
	 * @param \WC_Order $order
	 * @return string
	 */
	private function get_products_list( $order ) {
		$items = $order->get_items();
		$names = array();

		foreach ( $items as $item ) {
			$names[] = $item->get_name();
		}

		return implode( ', ', $names );
	}

	/**
	 * Get products with quantities
	 *
	 * @param \WC_Order $order
	 * @return string
	 */
	private function get_products_with_qty( $order ) {
		$items    = $order->get_items();
		$products = array();

		foreach ( $items as $item ) {
			$products[] = sprintf( '%s × %d', $item->get_name(), $item->get_quantity() );
		}

		return implode( ', ', $products );
	}

	/**
	 * Get products links
	 *
	 * @param \WC_Order $order
	 * @return string
	 */
	private function get_products_links( $order ) {
		$items = $order->get_items();
		$links = array();

		foreach ( $items as $item ) {
			$product = $item->get_product();
			if ( $product ) {
				$links[] = sprintf( '<a href="%s">%s</a>', $product->get_permalink(), $item->get_name() );
			}
		}

		return implode( ', ', $links );
	}

	/**
	 * Get order summary
	 *
	 * @param \WC_Order $order
	 * @return string
	 */
	private function get_order_summary( $order ) {
		ob_start();
		wc_get_template(
			'emails/email-order-details.php',
			array(
				'order'         => $order,
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => '',
			)
		);
		return ob_get_clean();
	}

	/**
	 * Get user total spend
	 *
	 * @param int  $user_id
	 * @param bool $formatted
	 * @return string|float
	 */
	private function get_user_total_spend( $user_id, $formatted = true ) {
		$customer    = new \WC_Customer( $user_id );
		$total_spent = $customer->get_total_spent();

		return $formatted ? wc_price( $total_spent ) : $total_spent;
	}

	/**
	 * Format the value as N/A if it is empty
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function format_value_with_na( $value ) {
		if ( '' === $value || null === $value ) {
			return esc_html_x( 'N/A', 'WooCommerce Order Token', 'uncanny-automator-pro' );
		}

		return $value;
	}
}
