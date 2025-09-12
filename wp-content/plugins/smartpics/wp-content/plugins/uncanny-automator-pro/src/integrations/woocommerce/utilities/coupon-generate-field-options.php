<?php
namespace Uncanny_Automator_Pro\Integration\Woocommerce\Utilities;

use WC_Product_Query;

/**
 * Utility class for WooCommerce coupon field options.
 *
 * @package Uncanny_Automator_Pro\Integration\Woocommerce\Utilities
 */
class Coupon_Generate_Field_Options {

	/**
	 * Retrieve all WooCommerce products as options.
	 *
	 * @param array $default The default option to prepend (not cached).
	 * @return array Array of product options, including the default if provided.
	 */
	private static function get_all_woocommerce_products( $default = array() ) {

		// Define the cache key.
		$cache_key = 'woocommerce_product_options';

		// Attempt to retrieve cached data.
		$cached_options = Automator()->cache->get( $cache_key );
		if ( ! empty( $cached_options ) ) {
			return self::prepend_default( $cached_options, $default );
		}

		// Fetch products using WC_Product_Query.
		if ( ! class_exists( 'WC_Product_Query' ) ) {
			return $default; // Return the default option if the class doesn't exist.
		}

		$query = new WC_Product_Query(
			array(
				'limit'   => -1,
				'status'  => 'publish',
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		);

		$products = $query->get_products();

		// Map products to the required format.
		$options = array();
		foreach ( $products as $product ) {
			$options[] = array(
				'text'  => $product->get_name(),
				'value' => $product->get_id(),
			);
		}

		// Cache the results.
		Automator()->cache->set( $cache_key, $options );

		return self::prepend_default( $options, $default );
	}

	/**
	 * Retrieve all WooCommerce categories as options.
	 *
	 * @param array $default The default option to prepend (not cached).
	 * @return array Array of category options, including the default if provided.
	 */
	public static function get_all_woocommerce_categories( $default = array() ) {

		// Define the cache key.
		$cache_key = 'woocommerce_category_options';

		// Attempt to retrieve cached data.
		$cached_options = Automator()->cache->get( $cache_key );
		if ( ! empty( $cached_options ) ) {
			return self::prepend_default( $cached_options, $default );
		}

		// Fetch product categories.
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $default; // Return the default option if the taxonomy doesn't exist.
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		// Map categories to the required format.
		$options = array();
		foreach ( $categories as $category ) {
			$options[] = array(
				'text'  => $category->name,
				'value' => $category->term_id,
			);
		}

		// Cache the results.
		Automator()->cache->set( $cache_key, $options );

		return self::prepend_default( $options, $default );
	}

	/**
	 * Retrieve field options for coupon generation.
	 *
	 * @param string $action_meta Metadata for the action.
	 * @return array Array of field options for coupon generation.
	 */
	public static function get_fields( $action_meta ) {

		// Define reusable defaults.
		$defaults = array(
			'products'           => array(
				'text'  => _x( 'All products', 'Woo', 'uncanny-automator-pro' ),
				'value' => -1,
			),
			'exclude_products'   => array(
				'text'  => _x( 'Do not exclude any product', 'Woo', 'uncanny-automator-pro' ),
				'value' => -1,
			),
			'categories'         => array(
				'text'  => _x( 'All categories', 'Woo', 'uncanny-automator-pro' ),
				'value' => -1,
			),
			'exclude_categories' => array(
				'text'  => _x( 'Do not exclude any category', 'Woo', 'uncanny-automator-pro' ),
				'value' => -1,
			),
		);

		// Fetch options for select fields.
		$products           = self::get_all_woocommerce_products( $defaults['products'] );
		$exclude_products   = self::get_all_woocommerce_products( $defaults['exclude_products'] );
		$categories         = self::get_all_woocommerce_categories( $defaults['categories'] );
		$exclude_categories = self::get_all_woocommerce_categories( $defaults['exclude_categories'] );

		return array(
			array(
				'input_type'      => 'text',
				'option_code'     => 'COUPON_CODE',
				'label'           => _x( 'Coupon code', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'A random string of 8 characters will be generated if coupon code field is left empty.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => $action_meta,
				'label'           => _x( 'Description', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'select',
				'option_code'     => 'DISCOUNT_TYPE',
				'label'           => _x( 'Discount type', 'Woo', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => array(
					array(
						'text'  => _x( 'Percentage discount', 'Woo', 'uncanny-automator-pro' ),
						'value' => 'percent',
					),
					array(
						'text'  => _x( 'Fixed cart discount', 'Woo', 'uncanny-automator-pro' ),
						'value' => 'fixed_cart',
					),
					array(
						'text'  => _x( 'Fixed product discount', 'Woo', 'uncanny-automator-pro' ),
						'value' => 'fixed_product',
					),
				),
				'show_option_id'  => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'COUPON_AMOUNT',
				'label'           => _x( 'Coupon amount', 'Woo', 'uncanny-automator-pro' ),
				'required'        => true,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'checkbox',
				'option_code'     => 'ALLOW_FREE_SHIPPING',
				'label'           => _x( 'Allow free shipping', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'A free shipping method must be enabled in your shipping zone and set to require "a valid free shipping coupon".', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'COUPON_EXPIRY_DATE',
				'label'           => _x( 'Coupon expiry date', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Enter a number of days until expiry or a specific date in YYYY-MM-DD format. The coupon will expire at 00:00:00 on the expiry date.', 'Woo', 'uncanny-automator-pro' ),
				'placeholder'     => _x( 'YYYY-MM-DD', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'MINIMUM_SPEND',
				'label'           => _x( 'Minimum spend', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Set the minimum spend (subtotal) allowed to use the coupon.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'MAXIMUM_SPEND',
				'label'           => _x( 'Maximum spend', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Set the maximum spend (subtotal) allowed when using the coupon.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'checkbox',
				'option_code'     => 'IS_INDIVIDUAL_USE',
				'label'           => _x( 'Individual use only', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Ensure this coupon cannot be used with other coupons.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'checkbox',
				'option_code'     => 'EXCLUDE_SALE_ITEMS',
				'label'           => _x( 'Exclude sale items', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Ensure the coupon does not apply to sale items.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'select',
				'multiple'        => true,
				'option_code'     => 'PRODUCTS',
				'label'           => _x( 'Products', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Products eligible for the coupon.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'options'         => $products,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'select',
				'multiple'        => true,
				'option_code'     => 'EXCLUDE_PRODUCTS',
				'label'           => _x( 'Exclude products', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Products excluded from coupon application.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'options'         => $exclude_products,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'select',
				'multiple'        => true,
				'option_code'     => 'PRODUCT_CATEGORIES',
				'label'           => _x( 'Product categories', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Categories eligible for the coupon.', 'Woo', 'uncanny-automator-pro' ),
				'options'         => $categories,
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'select',
				'multiple'        => true,
				'option_code'     => 'EXCLUDE_CATEGORIES',
				'label'           => _x( 'Exclude categories', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Categories excluded from the coupon.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'options'         => $exclude_categories,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'ALLOWED_EMAILS',
				'label'           => _x( 'Allowed emails', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Comma-separated email addresses allowed to use the coupon.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'USAGE_LIMIT_PER_COUPON',
				'label'           => _x( 'Usage limit per coupon', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Maximum number of times the coupon can be used.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'LIMIT_USAGE_PER_ITEM',
				'label'           => _x( 'Limit usage to X items', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Max number of items eligible for the discount.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'LIMIT_USAGE_PER_USER',
				'label'           => _x( 'Usage limit per user', 'Woo', 'uncanny-automator-pro' ),
				'description'     => _x( 'Max uses per user based on email or user ID.', 'Woo', 'uncanny-automator-pro' ),
				'required'        => false,
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * Prepend the default option to an options array.
	 *
	 * @param array $options Existing options.
	 * @param array $default Default option to prepend.
	 * @return array Options array with the default prepended.
	 */
	private static function prepend_default( $options, $default ) {

		if ( empty( $default ) ) {
			return $options;
		}

		return array_merge( array( $default ), $options );

	}
}
