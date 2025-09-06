<?php

namespace Uncanny_Automator_Pro\Integrations\WooCommerce_Bookings;

use Uncanny_Automator\Integrations\WooCommerce_Bookings\Wc_Bookings_Helpers;

/**
 * Class Wc_Bookings_Helpers_Pro
 *
 * @package Uncanny_Automator_Pro
 */
class Wc_Bookings_Helpers_Pro extends Wc_Bookings_Helpers {

	/**
	 * Booking status related tokens
	 *
	 * @return array[]
	 */
	public function wcb_booking_status_related_tokens() {
		return array(
			array(
				'tokenId'   => 'WCB_BOOKING_OLD_STATUS',
				'tokenName' => __( "Booking's previous status", 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
		);
	}

	/**
	 * WC_Bookings get all booking statuses
	 *
	 * @return array
	 */
	public function get_booking_statuses( $is_any = false ) {
		$statuses = array_unique( array_merge( get_wc_booking_statuses( null, true ), get_wc_booking_statuses( 'user', true ), get_wc_booking_statuses( 'cancel', true ) ) );
		$options  = array();
		if ( true === $is_any ) {
			$options[] = array(
				'value' => '-1',
				'text'  => esc_attr_x( 'Any status', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
			);
		}
		foreach ( $statuses as $status => $status_label ) {
			$options[] = array(
				'value' => $status,
				'text'  => $status_label,
			);
		}

		return $options;
	}

	/**
	 * WC_Bookings get all booking statuses
	 *
	 * @return array
	 */
	public function get_all_wc_bookings( $is_any = false ) {
		$args = array(
			'post_type'      => 'wc_booking',
			'post_status'    => 'any',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => 9999,
		);

		$bookings = get_posts( $args );
		$options  = array();
		if ( true === $is_any ) {
			$options[] = array(
				'value' => '-1',
				'text'  => esc_attr_x( 'Any booking', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
			);
		}
		foreach ( $bookings as $booking ) {
			$options[] = array(
				'value' => $booking->ID,
				'text'  => $booking->post_title,
			);
		}

		return $options;
	}

	/**
	 * WC_Bookings get all bookable products
	 *
	 * @return array
	 */
	public function get_all_wc_bookable_products( $is_any = false ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => 9999,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'booking',
				),
			),
		);

		$bookable_products = get_posts( $args );
		$options           = array();
		if ( true === $is_any ) {
			$options[] = array(
				'value' => '-1',
				'text'  => esc_attr_x( 'Any bookable product', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
			);
		}
		foreach ( $bookable_products as $bookable_product ) {
			$options[] = array(
				'value' => $bookable_product->ID,
				'text'  => $bookable_product->post_title,
			);
		}

		return $options;
	}

	public function select_all_product_resources() {
		// Nonce and post object validation
		Automator()->utilities->ajax_auth_check();
		$options = array();
		if ( ! automator_filter_has_var( 'value', INPUT_POST ) || empty( automator_filter_input( 'value', INPUT_POST ) ) ) {
			echo wp_json_encode( $options );
			die();
		}
		$product = get_wc_product_booking( automator_filter_input( 'value', INPUT_POST ) );
		if ( $product->has_resources() ) {
			$resources = $product->get_resources();
			foreach ( $resources as $resource ) {
				$title = $resource->get_title();
				if ( empty( $title ) ) {
					$title = sprintf( __( 'ID: %s (no title)', 'uncanny-automator-pro' ), $resource->get_id() );
				}
				$options[] = array(
					'value' => $resource->get_id(),
					'text'  => $title,
				);
			}
		}

		echo wp_json_encode( $options );
		die();
	}

	/**
	 * WC_Bookings get all users
	 *
	 * @return array
	 */
	public function get_all_users( $is_any = false ) {
		$all_users = get_users();
		$options   = array();
		if ( true === $is_any ) {
			$options[] = array(
				'value' => '-1',
				'text'  => esc_attr_x( 'Guest', 'WooCommerce Bookings', 'uncanny-automator-pro' ),
			);
		}
		foreach ( $all_users as $user ) {
			$options[] = array(
				'value' => $user->ID,
				'text'  => sprintf( esc_html( '%s (%s)' ), $user->display_name, $user->user_email ),
			);
		}

		return $options;
	}

}
