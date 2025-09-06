<?php

namespace Uncanny_Automator_Pro\Integration\Woocommerce\Utilities;

use WC_Coupon;
use WC_DateTime;
use LogicException;
use InvalidArgumentException;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Woo_Coupon_Generator
 *
 * Handles the generation of WooCommerce coupons.
 *
 * @package Uncanny_Automator_Pro\Integration\Woocommerce\Utilities
 */
class Woo_Coupon_Generator {

	/**
	 * Generate a WooCommerce coupon.
	 *
	 * This method orchestrates the coupon creation process by sanitizing,
	 * validating, and creating a WooCommerce coupon.
	 *
	 * @param array $args User-provided coupon parameters.
	 * @return int Coupon ID.
	 * @throws LogicException If WooCommerce is inactive or data is invalid.
	 * @throws InvalidArgumentException If critical data is missing or invalid.
	 */
	public function generate( $args ) {
		// Ensure WooCommerce is active.
		if ( ! class_exists( 'WC_Coupon' ) ) {
			throw new LogicException( 'WooCommerce is not active. Please ensure WooCommerce is installed and activated.' );
		}

		// Sanitize the input data.
		$data = $this->sanitize_data( $args );

		// Validate the sanitized data.
		$this->validate_data( $data );

		// Create and save the coupon.
		return $this->create_coupon( $data );
	}

	/**
	 * Sanitize input data and apply default values.
	 *
	 * This ensures all required keys are present and assigns default values where applicable.
	 * If the 'code' is empty, a random 8-character code will be generated.
	 *
	 * @param array $args User-provided arguments.
	 * @return array Sanitized data with guaranteed keys and defaults.
	 */
	private function sanitize_data( $args ) {
		$defaults = array(
			'code'                   => '', // Code will be generated if empty.
			'description'            => '',
			'discount_type'          => 'fixed_cart',
			'amount'                 => '0',
			'free_shipping'          => false,
			'expiry_date'            => '',
			'minimum_spend'          => '',
			'maximum_spend'          => '',
			'individual_use'         => false,
			'exclude_sale_items'     => false,
			'product_ids'            => array(),
			'exclude_product_ids'    => array(),
			'product_categories'     => array(),
			'exclude_categories'     => array(),
			'email_restrictions'     => array(),
			'usage_limit'            => '',
			'limit_usage_to_x_items' => '',
			'usage_limit_per_user'   => '',
		);

		// Merge user input with defaults.
		$data = wp_parse_args( $args, $defaults );

		// Generate a random code if 'code' is empty.
		if ( empty( $data['code'] ) ) {
			$data['code'] = $this->generate_random_code( 8 );
		}

		return $data;
	}

	/**
	 * Validate sanitized coupon data.
	 *
	 * Checks the required keys and ensures that values are valid.
	 * For example, the amount must be a positive number.
	 *
	 * @param array $data Sanitized coupon data.
	 * @throws InvalidArgumentException If data is invalid.
	 * @throws LogicException If required data is missing.
	 */
	private function validate_data( $data ) {
		// Validate essential fields.
		if ( empty( $data['code'] ) ) {
			throw new LogicException( 'The coupon code is required.' );
		}

		if ( empty( $data['discount_type'] ) ) {
			throw new LogicException( 'The discount type is required.' );
		}

		if ( ! is_numeric( $data['amount'] ) || $data['amount'] <= 0 ) {
			throw new InvalidArgumentException( 'The amount must be a positive number.' );
		}
	}

	/**
	 * Create a WooCommerce coupon.
	 *
	 * Sets the sanitized and validated data on the WooCommerce coupon object and saves it.
	 *
	 * @param array $data Sanitized and validated coupon data.
	 * @return int Coupon ID.
	 * @throws LogicException If the coupon cannot be created.
	 */
	private function create_coupon( $data ) {
		$coupon = new WC_Coupon();

		// Set properties using sanitized and validated data.
		$coupon->set_code( $data['code'] );
		$coupon->set_description( $data['description'] );
		$coupon->set_discount_type( $data['discount_type'] );
		$coupon->set_amount( $data['amount'] );
		$coupon->set_free_shipping( $data['free_shipping'] );
		$coupon->set_date_expires( $this->parse_expiry_date( $data['expiry_date'] ) );
		$coupon->set_minimum_amount( $data['minimum_spend'] );
		$coupon->set_maximum_amount( $data['maximum_spend'] );
		$coupon->set_individual_use( $data['individual_use'] );
		$coupon->set_exclude_sale_items( $data['exclude_sale_items'] );
		$coupon->set_product_ids( $data['product_ids'] );
		$coupon->set_excluded_product_ids( $data['exclude_product_ids'] );
		$coupon->set_product_categories( $data['product_categories'] );
		$coupon->set_excluded_product_categories( $data['exclude_categories'] );
		$coupon->set_email_restrictions( $data['email_restrictions'] );
		$coupon->set_usage_limit( $data['usage_limit'] );
		$coupon->set_limit_usage_to_x_items( $data['limit_usage_to_x_items'] );
		$coupon->set_usage_limit_per_user( $data['usage_limit_per_user'] );

		// Save the coupon and return its ID.
		$coupon_id = $coupon->save();

		if ( ! $coupon_id ) {
			throw new LogicException( 'Failed to save the WooCommerce coupon.' );
		}

		return $coupon_id;
	}

	/**
	 * Parse the expiry date.
	 *
	 * Converts an expiry date or number of days into a WooCommerce-compatible date object.
	 *
	 * @param string $expiry_date Expiry date or number of days.
	 * @return WC_DateTime|null Parsed date or null if invalid.
	 */
	private function parse_expiry_date( $expiry_date ) {
		if ( is_numeric( $expiry_date ) ) {
			return new WC_DateTime( '@' . strtotime( '+' . (int) $expiry_date . ' days', current_time( 'timestamp' ) ) );
		}

		if ( strtotime( $expiry_date ) ) {
			return new WC_DateTime( '@' . strtotime( $expiry_date ) );
		}

		return null;
	}

	/**
	 * Generate a random coupon code.
	 *
	 * Creates a random alphanumeric string of the specified length.
	 *
	 * @param int $length Length of the random string.
	 * @return string Generated random string.
	 */
	private function generate_random_code( $length ) {
		$characters        = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$characters_length = strlen( $characters );
		$random_string     = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ rand( 0, $characters_length - 1 ) ];
		}

		return $random_string;
	}
}
