<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Uncanny_Automator_Pro;

/**
 * Class Ameliabooking_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Ameliabooking_Pro_Helpers {

	/**
	 * Registers the hooks for the AmeliaBookingStatusUpdated and AmeliaBookingCanceled actions.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {

		// Register the hooks for the AmeliaBookingStatusUpdated and AmeliaBookingCanceled actions.
		add_action( 'AmeliaBookingStatusUpdated', array( $this, 'ameliabooking_status_updated' ), 10, 3 );
		add_action( 'AmeliaBookingCanceled', array( $this, 'ameliabooking_status_cancelled' ), 10, 3 );
	}

	/**
	 * Action hook for AmeliaBookingStatusUpdated.
	 *
	 * @param array $reservation Reservation data.
	 * @param array $bookings    Bookings data.
	 * @param mixed $container   Container object.
	 *
	 * @return void
	 */
	public function ameliabooking_status_updated( $reservation, $bookings, $container ) {

		// Delegate the action to the automator_ameliabooking_status_updated action hook.
		foreach ( $bookings as $booking ) {
			// Fires the trigger for each booking.
			do_action( 'automator_ameliabooking_status_updated', $reservation, $booking, $container );
		}
	}

	/**
	 * Action hook for AmeliaBookingCanceled.
	 *
	 * @param array $reservation Reservation data.
	 * @param array $bookings    Bookings data.
	 * @param mixed $container   Container object.
	 *
	 * @return void
	 */
	public function ameliabooking_status_cancelled( $reservation, $bookings, $container ) {

		// Delegate the action to the automator_ameliabooking_status_cancelled action hook.
		foreach ( $bookings as $booking ) {
			// Fires the trigger for each booking.
			do_action( 'automator_ameliabooking_status_cancelled', $reservation, $booking, $container );
		}
	}

	/**
	 * Get the WP User ID from the booking.
	 *
	 * @param array $booking   Booking data.
	 * @param mixed $container Container object.
	 *
	 * @return bool|int
	 */
	public static function get_single_booking_wp_user_id( $booking, $container ) {

		// Get the customer id.
		$customer_id = $booking['customerId'] ?? null;

		if ( ! $customer_id ) {
			return false;
		}

		// Get the customer data by customer id.
		$external_id = self::get_external_id( $customer_id, $container );

		// Check if external ID is WP User ID.
		if ( $external_id ) {
			$wp_user = get_user_by( 'ID', $external_id );
			return $wp_user->ID ?? false;
		}

		// Get the user data.
		$user_data  = self::get_customer_data( $customer_id, $container );
		$user_email = $user_data['email'] ?? null;
		$wp_user    = get_user_by( 'email', $user_email );

		return $wp_user->ID ?? false;
	}

	/**
	 * Get the WP User ID from the reservation.
	 *
	 * @param array $reservation Reservation data.
	 * @param mixed $container   Container object.
	 *
	 * @return bool|int
	 */
	public static function get_reservation_wp_user_id( $reservation, $container ) {

		$user_id = false;

		// Validate the reservation and container params.
		if ( ! is_array( $reservation ) ) {
			return $user_id;
		}

		// Get the customer id.
		$booking     = ! empty( $reservation['bookings'] ) ? $reservation['bookings'][0] : array();
		$customer_id = ! empty( $booking['customerId'] ) ? $booking['customerId'] : false;

		if ( ! $customer_id ) {
			return $user_id;
		}

		// Get the customer data by customer id.
		$external_id = self::get_external_id( $customer_id, $container );

		// Check if external ID is WP User ID.
		if ( $external_id ) {
			$wp_user = get_user_by( 'ID', $external_id );
			$user_id = ! empty( $wp_user ) ? $wp_user->ID : false;
		}

		return $user_id;
	}

	/**
	 * Get the external ID from the customer ID.
	 *
	 * @param int     $customer_id Customer ID.
	 * @param mixed   $container   Container object.
	 *
	 * @return bool|int
	 */
	public static function get_external_id( $customer_id, $container ) {

		$user_data   = self::get_customer_data( $customer_id, $container );
		$external_id = ! empty( $user_data['externalId'] ) ? $user_data['externalId'] : false;

		return $external_id;
	}

	/**
	 * Get the customer data by customer ID.
	 *
	 * @param int     $customer_id Customer ID.
	 * @param mixed   $container   Container object.
	 *
	 * @return array
	 */
	public static function get_customer_data( $customer_id, $container ) {

		if ( ! is_a( $container, 'AmeliaBooking\Infrastructure\Common\Container' ) ) {
			return array();
		}

		$user_repo = $container->get( 'domain.users.repository' );
		$user      = $user_repo->getById( (int) $customer_id );
		$user_data = ! empty( $user ) ? $user->toArray() : array();

		return $user_data;
	}
}
