<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wcm_Tokens;
use WC_Order;

/**
 * Class Wcm_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Wcm_Pro_Tokens extends Wcm_Tokens {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WCMEMBERSHIPS';

	/**
	 * Wcm_Pro_Tokens constructor.
	 */
	public function __construct() {
		add_filter(
			'automator_maybe_trigger_wcmemberships_wcm_status_changed_tokens',
			array(
				$this,
				'wcm_get_possible_order_tokens',
			),
			20,
			2
		);
		add_filter( 'automator_maybe_parse_token', array( $this, 'wcm_parse_pro_tokens' ), 20, 6 );
	}

	/**
	 * @param $tokens
	 * @param $args
	 * @param $type
	 *
	 * @return array
	 */
	public function wcm_get_possible_order_tokens( $tokens = array(), $args = array(), $type = 'order' ) {
		$tokens       = $this->wcm_possible_order_tokens( $tokens, $args );
		$trigger_meta = $args['meta'];
		if ( ! empty( $tokens ) ) {
			foreach ( $tokens as $k => $order_token ) {
				$tokens[ $k ]['tokenIdentifier'] = $trigger_meta;
			}
		}
		Automator()->utilities->remove_duplicate_token_ids( $tokens );

		return $tokens;

	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return string|null
	 */
	public function wcm_parse_pro_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		$to_match = array(
			'WCMMEMBERSHIPPLAN',
			'WCMPLANORDERID',
			'WCMUSERACCESSEXPIRED',
			'WCMUSERACCESSCANCELLED',
		);

		if ( $pieces ) {
			if ( array_intersect( $to_match, $pieces ) ) {
				$value = $this->replace_values( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args );
			}
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return array|string|null
	 */
	public function replace_values( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( empty( $pieces ) || empty( $trigger_data ) || empty( $replace_args ) ) {
			return $value;
		}

		$parse = $pieces[2];

		// Plan tokens.
		$plan_tokens = array( 'WCMMEMBERSHIPPLANPOSTID', 'WCMMEMBERSHIPPLAN' );
		if ( in_array( $parse, $plan_tokens, true ) ) {
			$plan_id = Automator()->db->token->get( 'WCMMEMBERSHIPPLANPOSTID', $replace_args );
			if ( ! empty( $plan_id ) ) {
				$value = 'WCMMEMBERSHIPPLAN' === $parse ? get_the_title( $plan_id ) : $plan_id;
			}

			return $value;
		}

		// User membership token.
		if ( 'WCMMEMBERSHIPPOSTID' === $parse ) {
			$membership_post_id = Automator()->db->token->get( 'WCMMEMBERSHIPPOSTID', $replace_args );
			if ( ! empty( $membership_post_id ) ) {
				return $membership_post_id;
			}

			return $value;
		}

		// Order tokens.
		$order_id = Automator()->db->token->get( 'WCMPLANORDERID', $replace_args );

		if ( empty( $order_id ) ) {
			return $value;
		}

		return $this->parse_order_tokens( $order_id, $parse );
	}

	/**
	 * @param $order_id
	 * @param $parse
	 *
	 * @return mixed|string|null
	 */
	public function parse_order_tokens( $order_id, $parse ) {
		$order = wc_get_order( $order_id );
		$value = '';
		if ( ! $order instanceof WC_Order ) {
			return $value;
		}
		switch ( $parse ) {
			case 'order_id':
				$value = $order_id;
				break;
			case 'billing_first_name':
				$value = $order->get_billing_first_name();
				break;
			case 'billing_last_name':
				$value = $order->get_billing_last_name();
				break;
			case 'billing_company':
				$value = $order->get_billing_company();
				break;
			case 'billing_country':
				$value = $order->get_billing_country();
				break;
			case 'billing_address_1':
				$value = $order->get_billing_address_1();
				break;
			case 'billing_address_2':
				$value = $order->get_billing_address_2();
				break;
			case 'billing_city':
				$value = $order->get_billing_city();
				break;
			case 'billing_state':
				$value = $order->get_billing_state();
				break;
			case 'billing_postcode':
				$value = $order->get_billing_postcode();
				break;
			case 'billing_phone':
				$value = $order->get_billing_phone();
				break;
			case 'billing_email':
				$value = $order->get_billing_email();
				break;
			case 'shipping_first_name':
				$value = $order->get_shipping_first_name();
				break;
			case 'shipping_last_name':
				$value = $order->get_shipping_last_name();
				break;
			case 'shipping_company':
				$value = $order->get_shipping_company();
				break;
			case 'shipping_country':
				$value = $order->get_shipping_country();
				break;
			case 'shipping_address_1':
				$value = $order->get_shipping_address_1();
				break;
			case 'shipping_address_2':
				$value = $order->get_shipping_address_2();
				break;
			case 'shipping_city':
				$value = $order->get_shipping_city();
				break;
			case 'shipping_state':
				$value = $order->get_shipping_state();
				break;
			case 'shipping_postcode':
				$value = $order->get_shipping_postcode();
				break;
			case 'shipping_phone':
				$value = get_post_meta( $order_id, 'shipping_phone', true );
				break;
			case 'order_comments':
				$comments = $order->get_customer_note();
				if ( is_array( $comments ) && ! empty( $comments ) ) {
					$value = '<ul>';
					$value .= '<li>' . implode( '</li><li>', $comments ) . '</li>';
					$value .= '</ul>';
				} else {
					$value = ! empty( $comments ) ? $comments : '';
				}
				break;
			case 'order_status':
				$value = $order->get_status();
				break;
			case 'order_total':
				$value = wc_price( $order->get_total() );
				break;
			case 'order_subtotal':
				$value = wc_price( $order->get_subtotal() );
				break;
			case 'order_tax':
				$value = wc_price( $order->get_total_tax() );
				break;
			case 'order_discounts':
				$value = wc_price( $order->get_discount_total() * - 1 );
				break;
			case 'order_coupons':
				$coupons = $order->get_coupon_codes();
				if ( is_array( $coupons ) ) {
					$value = '<ul>';
					$value .= '<li>' . implode( '</li><li>', $coupons ) . '</li>';
					$value .= '</ul>';
				} else {
					$value = $coupons;
				}

				break;
			case 'order_products':
				$items = $order->get_items();
				if ( $items ) {
					$value = '<ul>';
					/** @var \WC_Order_Item_Product $item */
					foreach ( $items as $item ) {
						$product = $item->get_product();
						$value   .= '<li>' . $product->get_title() . '</li>';
					}
					$value .= '</ul>';
				}

				break;
			case 'order_products_qty':
				$items = $order->get_items();
				if ( $items ) {
					$value = '<ul>';
					/** @var \WC_Order_Item_Product $item */
					foreach ( $items as $item ) {
						$product = $item->get_product();
						$value   .= '<li>' . $product->get_title() . ' x ' . $item->get_quantity() . '</li>';
					}
					$value .= '</ul>';
				}

				break;
			case 'order_products_links':
				$items = $order->get_items();
				if ( $items ) {
					$value = '<ul>';
					/** @var \WC_Order_Item_Product $item */
					foreach ( $items as $item ) {
						$product = $item->get_product();
						$value   .= '<li><a href="' . $product->get_permalink() . '">' . $product->get_title() . '</a></li>';
					}
					$value .= '</ul>';
				}

				break;
			case 'payment_method':
				$value = $order->get_payment_method_title();
				break;
		}

		return $value;
	}
}
