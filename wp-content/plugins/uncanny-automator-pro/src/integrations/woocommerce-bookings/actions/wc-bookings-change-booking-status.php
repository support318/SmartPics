<?php

namespace Uncanny_Automator_Pro\Integrations\WooCommerce_Bookings;

/**
 * Class WC_BOOKINGS_CHANGE_BOOKING_STATUS
 *
 * @package Uncanny_Automator_Pro
 */
class WC_BOOKINGS_CHANGE_BOOKING_STATUS extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WC_BOOKINGS' );
		$this->set_action_code( 'WCB_CHANGE_STATUS' );
		$this->set_action_meta( 'WCB_STATUSES' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'Change {{booking:%1$s}} to {{a specific status:%2$s}}', 'WooCommerce Bookings', 'uncanny-automator-pro' ), 'WC_ALL_BOOKINGS:' . $this->get_action_meta(), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Change {{booking}} to {{a specific status}}', 'WooCommerce Bookings', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return array[]
	 */
	public function options() {

		return array(
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'     => 'WC_ALL_BOOKINGS',
					'label'           => esc_attr_x( 'Booking', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
					'options'         => $this->helpers->get_all_wc_bookings(),
					'relevant_tokens' => array(),
				)
			),
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'           => $this->get_action_meta(),
					'label'                 => esc_attr_x( 'Status', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
					'options'               => $this->helpers->get_booking_statuses(),
					'relevant_tokens'       => array(),
					'supports_custom_value' => false,
				)
			),
		);
	}

	/**
	 * define_tokens
	 *
	 * @return array
	 */
	public function define_tokens() {
		return array(
			'WCB_BOOKING_ID'         => array(
				'name' => __( 'Booking ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'WCB_ORDER_ID'           => array(
				'name' => __( 'Order ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'WCB_BOOKING_ORDER_LINK' => array(
				'name' => __( 'Link to order', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'WCB_BOOKED_PRODUCTS'    => array(
				'name' => __( 'Booked product', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCB_BOOKING_STATUS'     => array(
				'name' => __( 'Booking status', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCB_BOOKING_DETAILS'    => array(
				'name' => __( 'Booking details', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCB_CUSTOMER_DETAILS'   => array(
				'name' => __( 'Customer details', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCB_CREATED_DATE'       => array(
				'name' => __( 'Date created', 'uncanny-automator-pro' ),
				'type' => 'date',
			),
			'WCB_BOOKING_DATE_TIME'  => array(
				'name' => __( 'Booking date and time', 'uncanny-automator-pro' ),
				'type' => 'date',
			),
		);

	}

	/**
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$status     = sanitize_text_field( Automator()->parse->text( $action_data['meta'][ $this->get_action_meta() ], $recipe_id, $user_id, $args ) );
		$booking_id = sanitize_text_field( Automator()->parse->text( $action_data['meta']['WC_ALL_BOOKINGS'], $recipe_id, $user_id, $args ) );

		if ( false === get_wc_booking( $booking_id ) ) {
			$this->add_log_error( 'Invalid product ID provided.' );

			return false;
		}

		$booking = get_wc_booking( $booking_id );

		if ( $booking->get_status() === $status ) {
			$this->add_log_error( sprintf( 'Booking ID: %d is already set to "%s" status', $booking_id, $status ) );

			return false;
		}

		$booking->set_status( $status );
		$booking->save( true );

		$order       = wc_get_order( $booking->get_order_id() );
		$product     = get_wc_product_booking( $booking->get_product_id() );
		$booked_data = $this->helpers->get_booked_details_token_value( $product, $booking );
		$customer    = $booking->get_customer();
		// Populate the custom token values
		$this->hydrate_tokens(
			array(
				'WCB_BOOKING_ORDER_LINK' => $order->get_edit_order_url(),
				'WCB_BOOKED_PRODUCTS'    => $booking->get_product()->get_title(),
				'WCB_BOOKING_STATUS'     => $booking->get_status(),
				'WCB_BOOKING_ID'         => $booking->get_id(),
				'WCB_ORDER_ID'           => $booking->get_order_id(),
				'WCB_CREATED_DATE'       => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $booking->get_date_created() ),
				'WCB_BOOKING_DATE_TIME'  => $booking->get_start_date(),
				'WCB_BOOKING_DETAILS'    => $booked_data,
				'WCB_CUSTOMER_DETAILS'   => esc_html( 'Name: ' . $customer->name . "\n Email: " . $customer->email . "\n User ID: " . $customer->user_id ),
			)
		);

		return true;
	}

}
