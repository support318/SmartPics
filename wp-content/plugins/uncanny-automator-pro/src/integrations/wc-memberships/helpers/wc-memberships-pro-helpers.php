<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wc_Memberships_Helpers;
use WC_Memberships_User_Membership;
use WC_Order;

/**
 * Class Wc_Memberships_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Wc_Memberships_Pro_Helpers extends Wc_Memberships_Helpers {

	/**
	 * @var Wc_Memberships_Helpers
	 */
	public $options;
	/**
	 * @var bool
	 */
	public $load_options = true;

	/**
	 * @var bool
	 */
	public $pro;

	/**
	 * Wc_Memberships_Pro_Helpers constructor.
	 */
	public function __construct() {

		$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
	}

	/**
	 * @param Wc_Memberships_Pro_Helpers $pro
	 */
	public function setPro( Wc_Memberships_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}

	/**
	 * Get Membership Select Condition field args.
	 *
	 * @param string $option_code - The option code identifier.
	 *
	 * @return array
	 */
	public function get_membership_condition_field_args( $option_code ) {
		return array(
			'option_code'           => $option_code,
			'label'                 => esc_html__( 'Plan', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_membership_condition_options(),
			'supports_custom_value' => true,
		);
	}

	/**
	 * Get the membership condition options
	 *
	 * @return array
	 */
	public function get_membership_condition_options() {

		if ( ! function_exists( 'wc_memberships_get_membership_plans' ) ) {
			return array();
		}

		static $condition_options = null;
		if ( ! is_null( $condition_options ) ) {
			return $condition_options;
		}

		$args              = array(
			'orderby' => 'title',
			'order'   => 'ASC',
		);
		$memberships       = wc_memberships_get_membership_plans( $args );
		$condition_options = array();

		if ( ! empty( $memberships ) ) {
			$condition_options[] = array(
				'value' => - 1,
				'text'  => __( 'Any plan', 'uncanny-automator-pro' ),
			);

			foreach ( $memberships as $membership ) {
				$condition_options[] = array(
					'value' => $membership->id,
					'text'  => $membership->name,
				);
			}
		}

		return $condition_options;
	}

	/**
	 * Evalue the condition
	 *
	 * @param $membership_id - WP_Post ID of the membership plan
	 * @param $user_id       - WP_User ID
	 *
	 * @return bool
	 */
	public function evaluate_condition_check( $membership_id, $user_id ) {

		// Check for Any Active memberships.
		if ( $membership_id < 0 ) {
			$active_memberships = wc_memberships_get_user_active_memberships( $user_id );

			return ! empty( $active_memberships );
		}

		// Check for specific membership.
		$cache     = true;
		$is_member = wc_memberships_is_user_active_member( $user_id, $membership_id, $cache );

		return $is_member;
	}

	/**
	 * Returns the array of all membership statuses
	 *
	 * @return array[]
	 */
	public function get_all_membership_statuses() {
		$statuses = wc_memberships_get_user_membership_statuses();
		$options  = array(
			array(
				'text'  => esc_attr_x( 'Any status', 'Woo Membership', 'uncanny-automator' ),
				'value' => '-1',
			),
		);
		foreach ( $statuses as $status => $labels ) {
			$options[] = array(
				'text'  => $labels['label'],
				'value' => $status,
			);
		}

		return $options;
	}

	/**
	 * @param $membership_id
	 *
	 * @return array
	 */
	public function parse_order_tokens( $user_membership ) {
		if ( ! $user_membership instanceof WC_Memberships_User_Membership ) {
			return array();
		}
		$order_id = $user_membership->get_order_id();
		$order    = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			$ordered_products       = array();
			$ordered_products_links = array();
			$ordered_products_qty   = array();
			$comments               = $order->get_customer_note();
			if ( is_array( $comments ) ) {
				$comments = join( ' | ', $comments );
			}

			$coupons = $order->get_coupon_codes();
			$coupons = join( ', ', $coupons );

			$items = $order->get_items();
			if ( $items ) {
				/** @var \WC_Order_Item_Product $item */
				foreach ( $items as $item ) {
					$product                  = $item->get_product();
					$ordered_products[]       = $product->get_title();
					$ordered_products_qty[]   = $product->get_title() . ' x ' . $item->get_quantity();
					$ordered_products_links[] = '<a href="' . $product->get_permalink() . '">' . $product->get_title() . '</a>';
				}
			}
			$ordered_products       = join( ' | ', $ordered_products );
			$ordered_products_qty   = join( ' | ', $ordered_products_qty );
			$ordered_products_links = join( ' | ', $ordered_products_links );

			return array(
				'order_id'             => $order_id,
				'billing_first_name'   => $order->get_billing_first_name(),
				'billing_last_name'    => $order->get_billing_last_name(),
				'billing_company'      => $order->get_billing_company(),
				'billing_country'      => $order->get_billing_country(),
				'billing_address_1'    => $order->get_billing_address_1(),
				'billing_address_2'    => $order->get_billing_address_2(),
				'billing_city'         => $order->get_billing_city(),
				'billing_state'        => $order->get_billing_state(),
				'billing_postcode'     => $order->get_billing_postcode(),
				'billing_phone'        => $order->get_billing_phone(),
				'billing_email'        => $order->get_billing_email(),
				'shipping_first_name'  => $order->get_shipping_first_name(),
				'shipping_last_name'   => $order->get_shipping_last_name(),
				'shipping_company'     => $order->get_shipping_company(),
				'shipping_country'     => $order->get_shipping_country(),
				'shipping_address_1'   => $order->get_shipping_address_1(),
				'shipping_address_2'   => $order->get_shipping_address_2(),
				'shipping_city'        => $order->get_shipping_city(),
				'shipping_state'       => $order->get_shipping_state(),
				'shipping_postcode'    => $order->get_shipping_postcode(),
				'order_comments'       => $comments,
				'order_status'         => $order->get_status(),
				'order_total'          => wp_strip_all_tags( wc_price( $order->get_total() ) ),
				'order_subtotal'       => wp_strip_all_tags( wc_price( $order->get_subtotal() ) ),
				'order_tax'            => wp_strip_all_tags( wc_price( $order->get_total_tax() ) ),
				'order_discounts'      => wp_strip_all_tags( wc_price( $order->get_discount_total() * - 1 ) ),
				'order_coupons'        => $coupons,
				'order_products'       => $ordered_products,
				'order_products_qty'   => $ordered_products_qty,
				'order_products_links' => $ordered_products_links,
				'payment_method'       => $order->get_payment_method_title(),
			);
		}
	}

}
