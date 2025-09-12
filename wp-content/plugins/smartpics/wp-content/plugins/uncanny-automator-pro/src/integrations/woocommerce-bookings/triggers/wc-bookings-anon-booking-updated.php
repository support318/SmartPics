<?php

namespace Uncanny_Automator_Pro\Integrations\WooCommerce_Bookings;

class WC_BOOKINGS_ANON_BOOKING_UPDATED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WC_BOOKINGS' );
		$this->set_trigger_code( 'WC_BOOKINGS_BOOKING_UPDATED' );
		$this->set_trigger_meta( 'WC_BOOKINGS_UPDATED' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( esc_attr_x( 'A booking is updated', 'WooCommerce Bookings', 'uncanny-automator-pro' ) );
		$this->set_readable_sentence( esc_attr_x( 'A booking is updated', 'WooCommerce Bookings', 'uncanny-automator-pro' ) );
		$this->add_action( 'woocommerce_booking_process_meta', 10, 1 );
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		return isset( $hook_args[0] );
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
		$order_link_token = array(
			array(
				'tokenId'   => 'WCB_BOOKING_ORDER_LINK',
				'tokenName' => __( 'Order link', 'uncanny-automator-pro' ),
				'tokenType' => 'url',
			),
		);
		$common_tokens    = $this->helpers->wcb_booking_common_tokens();

		return array_merge( $tokens, $order_link_token, $common_tokens );
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
		$booking_id           = $hook_args[0];
		$booking              = get_wc_booking( $booking_id );
		$product              = get_wc_product_booking( $booking->get_product_id() );
		$booked_data          = $this->helpers->get_booked_details_token_value( $product, $booking );
		$order_token_values   = $this->helpers->get_wc_order_tokens( $booking->get_order_id(), $booking->get_product_id() );
		$order                = wc_get_order( $booking->get_order_id() );
		$trigger_token_values = array(
			'WCB_BOOKING_START'      => $booking->get_start_date(),
			'WCB_BOOKING_END'        => $booking->get_end_date(),
			'WCB_BOOKING_ORDER_ID'   => $booking->get_order_id(),
			'WCB_BOOKING_ID'         => $booking_id,
			'WCB_CUSTOMER_EMAIL'     => $booking->get_customer()->email,
			'WCB_CUSTOMER_NAME'      => $booking->get_customer()->name,
			'WCB_PRODUCT_URL'        => get_permalink( $booking->get_product_id() ),
			'WCB_PRODUCT_TITLE'      => $booking->get_product()->get_title(),
			'WCB_PRODUCT_DETAILS'    => $booked_data,
			'WCB_PRODUCT_PRICE'      => wc_price( $booking->get_cost() ),
			'WCB_BOOKING_STATUS'     => $booking->get_status(),
			'WCB_BOOKING_ORDER_LINK' => $order->get_edit_order_url(),
		);

		return array_merge( $order_token_values, $trigger_token_values );
	}
}
