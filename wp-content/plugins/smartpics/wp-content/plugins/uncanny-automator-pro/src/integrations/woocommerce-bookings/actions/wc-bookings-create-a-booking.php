<?php

namespace Uncanny_Automator_Pro\Integrations\WooCommerce_Bookings;

/**
 * Class WC_BOOKINGS_CREATE_A_BOOKING
 *
 * @package Uncanny_Automator_Pro
 */
class WC_BOOKINGS_CREATE_A_BOOKING extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'WC_BOOKINGS' );
		$this->set_action_code( 'WCB_CREATE_BOOKING' );
		$this->set_action_meta( 'WCB_PRODUCTS' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'Create {{a booking:%1$s}}', 'WooCommerce Bookings', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Create {{a booking}}', 'WooCommerce Bookings', 'uncanny-automator-pro' ) );
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
					'option_code'     => 'WC_USERS',
					'label'           => esc_attr_x( 'Customer', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
					'options'         => $this->helpers->get_all_users(),
					'relevant_tokens' => array(),
				)
			),
			Automator()->helpers->recipe->field->select_field_args(
				array(
					'option_code'     => $this->get_action_meta(),
					'label'           => esc_attr_x( 'Bookable product', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
					'options'         => $this->helpers->get_all_wc_bookable_products(),
					'relevant_tokens' => array(),
					'is_ajax'         => true,
					'target_field'    => 'WCB_RESOURCES',
					'endpoint'        => 'select_all_product_resources',
				)
			),
			Automator()->helpers->recipe->field->select_field_args(
				array(
					'option_code'           => 'WCB_RESOURCES',
					'options'               => array(),
					'required'              => false,
					'label'                 => esc_attr__( 'Resource', 'uncanny-automator-pro' ),
					//                  'ajax'                  => array(
					//                      'endpoint'               => 'select_all_product_resources',
					//                      'event'                  => 'parent_fields_change',
					//                      'listen_fields'          => array( 'WCB_PRODUCTS' ),
					//                  ),
					'supports_custom_value' => false,
					'relevant_tokens'       => array(),
				)
			),
			array(
				'option_code'     => 'WCB_BOOKING_DATE',
				'label'           => esc_attr_x( 'Date', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
				'relevant_tokens' => array(),
				'input_type'      => 'date',

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
			'WCB_BOOKING_ID'        => array(
				'name' => __( 'Booking ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'WCB_BOOKED_PRODUCTS'   => array(
				'name' => __( 'Booked product', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCB_BOOKING_STATUS'    => array(
				'name' => __( 'Booking status', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCB_BOOKING_DETAILS'   => array(
				'name' => __( 'Booking details', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCB_CUSTOMER_DETAILS'  => array(
				'name' => __( 'Customer details', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCB_BOOKING_DATE_TIME' => array(
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
		if ( ! function_exists( 'create_wc_booking' ) ) {
			$this->add_log_error( 'Function not found "create_wc_booking"' );

			return false;
		}

		$product_id  = sanitize_text_field( Automator()->parse->text( $action_data['meta'][ $this->get_action_meta() ], $recipe_id, $user_id, $args ) );
		$customer_id = sanitize_text_field( Automator()->parse->text( $action_data['meta']['WC_USERS'], $recipe_id, $user_id, $args ) );
		$resource_id = sanitize_text_field( Automator()->parse->text( $action_data['meta']['WCB_RESOURCES'], $recipe_id, $user_id, $args ) );
		$start_date  = sanitize_text_field( Automator()->parse->text( $action_data['meta']['WCB_BOOKING_DATE'], $recipe_id, $user_id, $args ) );

		if ( false === get_wc_product_booking( $product_id ) ) {
			$this->add_log_error( 'The given product ID is not a bookable product.' );

			return false;
		}

		$booking_details = array(
			'product_id'  => $product_id,
			'start_date'  => strtotime( $start_date ),
			'resource_id' => $resource_id,
			'user_id'     => $customer_id,
		);

		$new_booking = create_wc_booking( $product_id, $booking_details, 'unpaid' );
		if ( false === $new_booking ) {
			$this->add_log_error( 'We are unable to create a booking' );

			return false;
		}

		$product     = get_wc_product_booking( $product_id );
		$booked_data = $this->helpers->get_booked_details_token_value( $product, $new_booking );
		$customer    = $new_booking->get_customer();
		// Populate the custom token values
		$this->hydrate_tokens(
			array(
				'WCB_BOOKED_PRODUCTS'   => $new_booking->get_product()->get_title(),
				'WCB_BOOKING_STATUS'    => $new_booking->get_status(),
				'WCB_BOOKING_ID'        => $new_booking->get_id(),
				'WCB_BOOKING_DATE_TIME' => $new_booking->get_start_date(),
				'WCB_BOOKING_DETAILS'   => $booked_data,
				'WCB_CUSTOMER_DETAILS'  => esc_html( 'Name: ' . $customer->name . "\n Email: " . $customer->email . "\n User ID: " . $customer->user_id ),
			)
		);

		return true;
	}
}
