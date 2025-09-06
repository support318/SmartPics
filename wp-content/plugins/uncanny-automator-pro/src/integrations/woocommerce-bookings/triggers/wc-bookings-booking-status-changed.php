<?php

namespace Uncanny_Automator_Pro\Integrations\WooCommerce_Bookings;

/**
 * Class WC_BOOKINGS_BOOKING_STATUS_CHANGED
 *
 * @package Uncanny_Automator_Pro
 */
class WC_BOOKINGS_BOOKING_STATUS_CHANGED extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WC_BOOKINGS' );
		$this->set_trigger_code( 'WC_BOOKINGS_STATUS_CHANGED' );
		$this->set_trigger_meta( 'WC_BOOKINGS_STATUS' );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'A booking status is changed to {{a specific status:%1$s}}', 'WooCommerce Bookings', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A booking status is changed to {{a specific status}}', 'WooCommerce Bookings', 'uncanny-automator-pro' ) );
		$this->add_action( 'woocommerce_booking_status_changed', 10, 4 );
	}

	/**
	 * @return array
	 */
	public function options() {
		return array(
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'     => $this->get_trigger_meta(),
					'label'           => esc_attr_x( 'Status', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
					'options'         => $this->helpers->get_booking_statuses( true ),
					'relevant_tokens' => array(),
				)
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		list( $previous_status, $new_status, $booking_id, $booking_obj ) = $hook_args;

		if ( $previous_status === $new_status ) {
			return false;
		}

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_status = $trigger['meta'][ $this->get_trigger_meta() ];

		return ( intval( '-1' ) === intval( $selected_status ) ) || ( $selected_status === $new_status );
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
		$common_tokens         = $this->helpers->wcb_booking_common_tokens();
		$booking_status_tokens = $this->helpers->wcb_booking_status_related_tokens();

		return array_merge( $tokens, $common_tokens, $booking_status_tokens );
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
		list( $previous_status, $new_status, $booking_id, $booking ) = $hook_args;

		$product              = get_wc_product_booking( $booking->get_product_id() );
		$booked_data          = $this->helpers->get_booked_details_token_value( $product, $booking );
		$order_token_values   = $this->helpers->get_wc_order_tokens( $booking->get_order_id(), $booking->get_product_id() );
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
			'WCB_BOOKING_OLD_STATUS' => $previous_status,
		);

		return array_merge( $order_token_values, $trigger_token_values );
	}

}
