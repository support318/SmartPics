<?php

namespace Uncanny_Automator_Pro;

/**
 * Studiocart Tokens file
 */
class Studiocart_Pro_Tokens {

	/**
	 * Studiocart_Tokens Constructor
	 */
	public function __construct() {
		add_filter( 'automator_maybe_trigger_studiocart_tokens', array( $this, 'studiocart_possible_tokens' ), 21, 2 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'studiocart_token' ), 999999, 6 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'studiocart_token_order_guest' ), 999999, 6 );
	}

	/**
	 * Studio cart tokens trigger specific.
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function studiocart_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_meta = $args['meta'];

		if ( 'STUDIOCARTORDERGUEST' === $trigger_meta ) {
			$fields = array();

			$fields[] = array(
				'tokenId'         => 'purchaser_first_name',
				'tokenName'       => __( 'Purchaser first name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'purchaser_last_name',
				'tokenName'       => __( 'Purchaser last name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'purchaser_email',
				'tokenName'       => __( 'Purchaser email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'product_title',
				'tokenName'       => __( 'Product title', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'product_id',
				'tokenName'       => __( 'Product ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'product_url',
				'tokenName'       => __( 'Product URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_address',
				'tokenName'       => __( 'Billing address', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_city',
				'tokenName'       => __( 'Billing city', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_state',
				'tokenName'       => __( 'Billing state', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_postcode',
				'tokenName'       => __( 'Billing postcode', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_phone',
				'tokenName'       => __( 'Billing phone', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'order_id',
				'tokenName'       => __( 'Order ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'order_amount',
				'tokenName'       => __( 'Order amount', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'payment_option_label',
				'tokenName'       => __( 'Payment option label', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			if ( ! empty( $fields ) ) {
				$tokens = array_merge( $tokens, $fields );
			}
		}

		if ( 'STUDIOCARTSUBS' === $trigger_meta ) {
			$fields = array();

			$fields[] = array(
				'tokenId'         => 'product_title',
				'tokenName'       => __( 'Product title', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'product_id',
				'tokenName'       => __( 'Product ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'product_url',
				'tokenName'       => __( 'Product URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_address',
				'tokenName'       => __( 'Billing address', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_city',
				'tokenName'       => __( 'Billing city', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_state',
				'tokenName'       => __( 'Billing state', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_postcode',
				'tokenName'       => __( 'Billing postcode', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'billing_phone',
				'tokenName'       => __( 'Billing phone', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'order_id',
				'tokenName'       => __( 'Order ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'subscription_id',
				'tokenName'       => __( 'Subscription ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'studiocart_subscription_id',
				'tokenName'       => __( 'Studiocart  subscription ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'sub_amount',
				'tokenName'       => __( 'Subscription amount', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'sub_interval',
				'tokenName'       => __( 'Subscription interval', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'sub_status',
				'tokenName'       => __( 'Subscription status', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'sub_payment_terms_plain',
				'tokenName'       => __( 'Subscription payment terms', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'sub_next_payment_date',
				'tokenName'       => __( 'Subscription next payment date', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'sub_cancel_date',
				'tokenName'       => __( 'Subscription cancelation date', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			$fields[] = array(
				'tokenId'         => 'payment_option_label',
				'tokenName'       => __( 'Payment option label', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			);

			if ( ! empty( $fields ) ) {
				$tokens = array_merge( $tokens, $fields );
			}
		}

		return $tokens;
	}

	/**
	 * Parse the token.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return null|string
	 */
	public function studiocart_token_order_guest( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		$piece = 'STUDIOCARTORDERGUEST';
		if ( empty( $pieces ) ) {
			return $value;
		}

		if ( ! in_array( $piece, $pieces, true ) ) {
			return $value;
		}

		$trigger_id   = $pieces[0];
		$trigger_meta = $pieces[1];
		$parse        = $pieces[2];

		foreach ( $trigger_data as $trigger ) {
			if ( ! is_array( $trigger ) || empty( $trigger ) ) {
				continue;
			}

			if ( key_exists( $trigger_meta, $trigger['meta'] ) || ( isset( $trigger['meta']['code'] ) && $trigger_meta === $trigger['meta']['code'] ) ) {
				$trigger_id     = $trigger['ID'];
				$trigger_log_id = $replace_args['trigger_log_id'];
				$order_id       = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'sc_order_id', $trigger_id, $trigger_log_id, $user_id );

				if ( ! empty( $order_id ) ) {
					$order = sc_setup_order( $order_id, true );
					if ( ! empty( $order ) ) {
						switch ( $parse ) {
							case 'order_id':
								$value = $order['ID'];
								break;
							case 'order_amount':
								$value = $order['amount'];
								break;
							case 'billing_phone':
								$value = $this->get_order_field_value( $order, 'phone' );
								break;
							case 'billing_postcode':
								$value = $this->get_order_field_value( $order, 'zip' );
								break;
							case 'billing_state':
								$value = $this->get_order_field_value( $order, 'state' );
								break;
							case 'billing_city':
								$value = $this->get_order_field_value( $order, 'city' );
								break;
							case 'billing_address':
								$address1 = $this->get_order_field_value( $order, 'address1' );
								$address2 = $this->get_order_field_value( $order, 'address2' );
								$address  = '';
								if ( '' !== (string) $address1 ) {
									$address .= $address1;
								}

								if ( '' !== (string) $address2 ) {
									$address .= ' ' . $address2;
								}

								$value = $address;
								break;
							case 'product_url':
								$value = get_the_permalink( $order['product_id'] );
								break;
							case 'product_id':
								$value = $order['product_id'];
								break;
							case 'product_title':
								$value = get_the_title( $order['product_id'] );
								break;
							case 'payment_option_label':
								$scorder = new \ScrtOrder( $order_id );
								$value   = $scorder->item_name;
								break;
							case 'purchaser_first_name':
								$value = $this->get_order_field_value( $order, 'first_name' );
							case 'purchaser_last_name':
								$value = $this->get_order_field_value( $order, 'last_name' );
							case 'purchaser_email':
								$value = $this->get_order_field_value( $order, 'email' );
								break;
							default:
								$token        = $parse;
								$token_pieces = $pieces;
								$value        = apply_filters( 'automator_studiocart_order_token_parser', $value, $token, $token_pieces, $order );
						}
					}
				}
			}
		}

		return $value;
	}

	/**
	 * Parse the token.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return null|string
	 */
	public function studiocart_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		$piece = 'STUDIOCARTSUBS';
		if ( empty( $pieces ) ) {
			return $value;
		}

		if ( ! in_array( $piece, $pieces, true ) ) {
			return $value;
		}

		$trigger_id   = $pieces[0];
		$trigger_meta = $pieces[1];
		$parse        = $pieces[2];

		foreach ( $trigger_data as $trigger ) {
			if ( ! is_array( $trigger ) || empty( $trigger ) ) {
				continue;
			}

			if ( key_exists( $trigger_meta, $trigger['meta'] ) || ( isset( $trigger['meta']['code'] ) && $trigger_meta === $trigger['meta']['code'] ) ) {
				$trigger_id     = $trigger['ID'];
				$trigger_log_id = $replace_args['trigger_log_id'];
				$order_id       = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'sc_order_id', $trigger_id, $trigger_log_id, $user_id );

				$sub_id = Automator()->helpers->recipe->get_form_data_from_trigger_meta( 'sc_sub_id', $trigger_id, $trigger_log_id, $user_id );

				if ( ! empty( $order_id ) && ! empty( $sub_id ) ) {
					$sub   = sc_setup_order( $sub_id, true );
					$order = sc_setup_order( $order_id, true );
					if ( ! empty( $order ) ) {
						switch ( $parse ) {
							case 'order_id':
								$value = $order['ID'];
								break;
							case 'order_amount':
								$value = $order['amount'];
								break;
							case 'billing_phone':
								$value = $this->get_order_field_value( $order, 'phone' );
								break;
							case 'billing_postcode':
								$value = $this->get_order_field_value( $order, 'zip' );
								break;
							case 'billing_state':
								$value = $this->get_order_field_value( $order, 'state' );
								break;
							case 'billing_city':
								$value = $this->get_order_field_value( $order, 'city' );
								break;
							case 'billing_address':
								$address1 = $this->get_order_field_value( $order, 'address1' );
								$address2 = $this->get_order_field_value( $order, 'address2' );
								$address  = '';
								if ( '' !== (string) $address1 ) {
									$address .= $address1;
								}

								if ( '' !== (string) $address2 ) {
									$address .= ' ' . $address2;
								}

								$value = $address;
								break;
							case 'product_url':
								$value = get_the_permalink( $order['product_id'] );
								break;
							case 'product_id':
								$value = $order['product_id'];
								break;
							case 'product_title':
								$value = get_the_title( $order['product_id'] );
								break;
							case 'payment_option_label':
								$scorder = new \ScrtOrder( $order_id );
								$value   = $scorder->item_name;
								break;
							case 'subscription_id':
								$value = $sub_id;
								break;
							case 'studiocart_subscription_id':
								$value = $this->get_order_field_value( $sub, 'subscription_id' );
								break;
							case 'sub_amount':
								$value = $this->get_order_field_value( $sub, 'sub_amount' );
								break;
							case 'sub_interval':
								$value = $this->get_order_field_value( $sub, 'sub_interval' );
								break;
							case 'sub_status':
								$value = $this->get_order_field_value( $sub, 'sub_status' );
								break;
							case 'sub_payment_terms_plain':
								$value = html_entity_decode( $this->get_order_field_value( $sub, 'sub_payment_terms_plain' ), ENT_QUOTES, 'UTF-8' );
								break;
							case 'sub_next_payment_date':
								$value = $this->get_order_field_value( $sub, 'sub_next_bill_date' );
								if ( ! empty( $value ) ) {
									$value = date_i18n( get_option( 'date_format' ), $value );
								}

								$value = apply_filters( 'automator_studiocart_sub_next_bill_date', $value, $sub );
								break;
							case 'sub_cancel_date':
								$value = $this->get_order_field_value( $sub, 'cancel_date' );
								if ( ! empty( $value ) ) {
									$value = date_i18n( get_option( 'date_format' ), $value );
								}

								$value = apply_filters( 'automator_studiocart_sub_cancel_date', $value, $sub );
								break;
							default:
								$token        = $parse;
								$token_pieces = $pieces;
								$value        = apply_filters( 'automator_studiocart_order_token_parser', $value, $token, $token_pieces, $sub );
						}
					}
				}
			}
		}

		return $value;
	}

	private function get_order_field_value( $order, $field ) {
		$value = '';
		if ( isset( $order[ $field ] ) ) {
			$value = $order[ $field ];
		}
		return $value;
	}
}

