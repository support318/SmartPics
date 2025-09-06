<?php
//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services;

use WC_Order;
use WC_Product;
use WC_Order_Item_Shipping;
use WC_Subscriptions_Product;

// Import WooCommerce functions
use function wc_create_order;
use function wc_get_product;
use function wc_format_coupon_code;
use function wcs_create_subscription;

class Order_Builder {

	/**
	 * Parsed field values from Order_Fields
	 *
	 * @var array<string,mixed>
	 */
	private $field_values;

	/**
	 * Constructor
	 *
	 * @param array $parsed Parsed field values from Order_Fields
	 */
	public function __construct( array $parsed ) {

		$this->field_values = $parsed;
	}

	/**
	 * Create the base order
	 *
	 * @return \WC_Order
	 * @throws \Exception If order creation fails
	 */
	private function create_order() {

		$user_id = $this->resolve_user_id();
		$order   = wc_create_order( array( 'customer_id' => $user_id ) );

		if ( is_wp_error( $order ) ) {
			throw new \Exception(
				sprintf(
					'Failed to create order: %s',
					esc_html( $order->get_error_message() )
				)
			);
		}

		return $order;
	}

	/**
	 * Resolve user ID from email
	 *
	 * @return int
	 * @throws \Exception If user email is invalid or user doesn't exist
	 */
	private function resolve_user_id() {

		if ( ! isset( $this->field_values['WCEMAIL'] ) || empty( $this->field_values['WCEMAIL'] ) ) {
			throw new \Exception( 'Billing email is required to create an order' );
		}

		$user_id = email_exists( $this->field_values['WCEMAIL'] );
		if ( ! $user_id ) {
			throw new \Exception( 'User with billing email ' . esc_html( $this->field_values['WCEMAIL'] ) . ' does not exist' );
		}

		return $user_id;
	}

	/**
	 * Validate required fields
	 *
	 * @throws \Exception If required fields are missing
	 */
	private function validate_required_fields() {

		$required_fields = array(
			'WCEMAIL'      => 'Billing email',
			'WCFIRST_NAME' => 'First name',
			'WCLAST_NAME'  => 'Last name',
			'WCCOUNTRY'    => 'Country',
		);

		foreach ( $required_fields as $field => $label ) {
			if ( ! isset( $this->field_values[ $field ] ) || empty( $this->field_values[ $field ] ) ) {
				throw new \Exception( sprintf( '%s is required', esc_html( $label ) ) );
			}
		}
	}

	/**
	 * Validate product
	 *
	 * @param int $product_id Product ID
	 * @param int $quantity Quantity
	 * @return \WC_Product
	 * @throws \Exception If product is invalid
	 */
	private function validate_product( $product_id, $quantity ) {

		$wc_product = wc_get_product( $product_id );

		if ( ! $wc_product instanceof \WC_Product ) {
			throw new \Exception( sprintf( 'Product %d not found', esc_html( $product_id ) ) );
		}

		if ( $quantity < 1 ) {
			throw new \Exception( sprintf( 'Invalid quantity for product %d', esc_html( $product_id ) ) );
		}

		if ( $this->is_subscription_order() && ! $this->is_subscription_product( $product_id ) ) {
			throw new \Exception( sprintf( 'Product %d is not a subscription product', esc_html( $product_id ) ) );
		}

		return $wc_product;
	}

	/**
	 * Build and create the WooCommerce order
	 *
	 * @return \WC_Order The created order
	 * @throws \Exception If order creation fails
	 */
	public function build_order() {

		$this->validate_required_fields();

		// Create the order
		$order = $this->create_order();

		// Add products
		$this->add_products( $order );

		// Set billing address
		$this->set_billing_address( $order );

		// Set shipping address
		$this->set_shipping_address( $order );

		// Set payment method if needed
		if ( $this->has_payment_method() ) {
			$this->set_payment_method( $order );
		}

		// Add shipping if needed
		if ( $this->has_shipping() ) {
			$this->add_shipping( $order );
		}

		// Add coupon if needed
		if ( $this->has_coupon() ) {
			$this->add_coupon( $order );
		}

		// Add order note if exists
		if ( $this->has_order_note() ) {
			$this->add_order_note( $order );
		}

		// Calculate totals and save
		$order->calculate_totals();

		// Set order status if specified
		if ( $this->has_order_status() ) {
			$this->set_order_status( $order );
		}

		// Handle subscription if needed
		if ( $this->is_subscription_order() ) {
			$this->create_subscription( $order );
		}

		$order->save();

		return $order;
	}

	/**
	 * Add products to the order
	 *
	 * @param \WC_Order $order
	 * @throws \Exception If product addition fails
	 */
	private function add_products( \WC_Order $order ) {

		if ( ! isset( $this->field_values['WC_PRODUCTS_FIELDS'] ) ) {
			return;
		}

		$products = json_decode( $this->field_values['WC_PRODUCTS_FIELDS'] );
		if ( ! is_array( $products ) ) {
			throw new \Exception( 'Invalid products data' );
		}

		foreach ( $products as $product ) {
			if ( ! isset( $product->WC_PRODUCT_ID ) ) {
				continue;
			}

			$product_id = intval( $product->WC_PRODUCT_ID );
			$quantity   = isset( $product->WC_PRODUCT_QTY ) ? intval( $product->WC_PRODUCT_QTY ) : 1;

			$wc_product = $this->validate_product( $product_id, $quantity );
			$order->add_product( $wc_product, $quantity );
		}
	}

	/**
	 * Set billing address
	 *
	 * @param \WC_Order $order
	 */
	private function set_billing_address( \WC_Order $order ) {

		$address = array(
			'first_name' => $this->field_values['WCFIRST_NAME'] ?? '',
			'last_name'  => $this->field_values['WCLAST_NAME'] ?? '',
			'company'    => $this->field_values['WCCOMPANYNAME'] ?? '',
			'email'      => $this->field_values['WCEMAIL'] ?? '',
			'phone'      => $this->field_values['WCPHONE'] ?? '',
			'address_1'  => $this->field_values['WCADDRESSONE'] ?? '',
			'address_2'  => $this->field_values['WCADDRESSTWO'] ?? '',
			'city'       => $this->field_values['WCCITY'] ?? '',
			'state'      => $this->field_values['WCSTATE'] ?? '',
			'postcode'   => $this->field_values['WCPOSTALCODE'] ?? '',
			'country'    => $this->field_values['WCCOUNTRY'] ?? '',
		);

		$order->set_address( $address, 'billing' );
	}

	/**
	 * Set shipping address
	 *
	 * @param \WC_Order $order
	 */
	private function set_shipping_address( \WC_Order $order ) {

		// If shipping same as billing
		if ( isset( $this->field_values['WCDETAILS'] ) && 'YES' === $this->field_values['WCDETAILS'] ) {
			$order->set_address( $order->get_address( 'billing' ), 'shipping' );
			return;
		}

		$address = array(
			'first_name' => $this->field_values['WC_SHP_FIRST_NAME'] ?? '',
			'last_name'  => $this->field_values['WC_SHP_LAST_NAME'] ?? '',
			'company'    => $this->field_values['WC_SHP_COMPANYNAME'] ?? '',
			'address_1'  => $this->field_values['WC_SHP_ADDRESSONE'] ?? '',
			'address_2'  => $this->field_values['WC_SHP_ADDRESSTWO'] ?? '',
			'city'       => $this->field_values['WC_SHP_CITY'] ?? '',
			'state'      => $this->field_values['WC_SHP_STATE'] ?? '',
			'postcode'   => $this->field_values['WC_SHP_POSTALCODE'] ?? '',
			'country'    => $this->field_values['WC_SHP_COUNTRY'] ?? '',
		);

		$order->set_address( $address, 'shipping' );
	}

	/**
	 * Check if payment method is set
	 *
	 * @return bool
	 */
	private function has_payment_method() {

		return isset( $this->field_values['WOOPAYMENTGATEWAY'] )
			&& ! empty( $this->field_values['WOOPAYMENTGATEWAY'] );
	}

	/**
	 * Set payment method
	 *
	 * @param \WC_Order $order
	 */
	private function set_payment_method( \WC_Order $order ) {

		$order->set_payment_method( $this->field_values['WOOPAYMENTGATEWAY'] );
	}

	/**
	 * Check if shipping is needed
	 *
	 * @return bool
	 */
	private function has_shipping() {

		return isset( $this->field_values['WC_SHP_METHOD'] )
			&& ! empty( $this->field_values['WC_SHP_METHOD'] );
	}

	/**
	 * Add shipping to order
	 *
	 * @param \WC_Order $order
	 */
	private function add_shipping( \WC_Order $order ) {

		$shipping_method = $this->field_values['WC_SHP_METHOD'];
		$shipping_cost   = $this->field_values['WC_SHP_COST'] ?? 0;

		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_method_title( $shipping_method );
		$shipping_item->set_total( $shipping_cost );

		$order->add_item( $shipping_item );
	}

	/**
	 * Check if coupon is set
	 *
	 * @return bool
	 */
	private function has_coupon() {

		return isset( $this->field_values['WC_COUPONS'] )
			&& ! empty( $this->field_values['WC_COUPONS'] );
	}

	/**
	 * Add coupon to order
	 *
	 * @param \WC_Order $order
	 */
	private function add_coupon( \WC_Order $order ) {

		$order->apply_coupon( wc_format_coupon_code( $this->field_values['WC_COUPONS'] ) );
	}

	/**
	 * Check if order note exists
	 *
	 * @return bool
	 */
	private function has_order_note() {

		return isset( $this->field_values['WCORDERNOTE'] )
			&& ! empty( $this->field_values['WCORDERNOTE'] );
	}

	/**
	 * Add order note
	 *
	 * @param \WC_Order $order
	 */
	private function add_order_note( \WC_Order $order ) {

		$order->add_order_note( $this->field_values['WCORDERNOTE'] );
	}

	/**
	 * Check if order status is specified
	 *
	 * @return bool
	 */
	private function has_order_status() {

		return isset( $this->field_values['WCORDERSTATUS'] )
			&& ! empty( $this->field_values['WCORDERSTATUS'] );
	}

	/**
	 * Set order status
	 *
	 * @param \WC_Order $order
	 */
	private function set_order_status( \WC_Order $order ) {

		$order->update_status( $this->field_values['WCORDERSTATUS'] );
	}

	/**
	 * Check if this is a subscription order
	 *
	 * @return bool
	 */
	private function is_subscription_order() {

		return isset( $this->field_values['WCORDER_TYPE'] )
			&& 'subscription' === $this->field_values['WCORDER_TYPE'];
	}

	/**
	 * Create subscription for the order
	 *
	 * @param \WC_Order $order
	 * @throws \Exception If subscription creation fails
	 */
	private function create_subscription( \WC_Order $order ) {

		$products = json_decode( $this->field_values['WC_PRODUCTS_FIELDS'] );

		if ( ! is_array( $products ) ) {
			throw new \Exception( 'Invalid products data' );
		}

		foreach ( $products as $product ) {
			if ( ! isset( $product->WC_PRODUCT_ID ) ) {
				continue;
			}

			$this->create_single_subscription( $order, $product );
		}
	}

	/**
	 * Check if product is a subscription product
	 *
	 * @param int $product_id Product ID
	 * @return bool
	 */
	private function is_subscription_product( $product_id ) {

		return class_exists( 'WC_Subscriptions_Product' )
			&& WC_Subscriptions_Product::is_subscription( $product_id );
	}

	/**
	 * Get subscription dates
	 *
	 * @param int $product_id Product ID
	 * @return array
	 */
	private function get_subscription_dates( $product_id ) {

		$start_date = gmdate( 'Y-m-d H:i:s' );
		return array(
			'trial_end'    => WC_Subscriptions_Product::get_trial_expiration_date( $product_id, $start_date ),
			'next_payment' => WC_Subscriptions_Product::get_first_renewal_payment_date( $product_id, $start_date ),
			'end'          => WC_Subscriptions_Product::get_expiration_date( $product_id, $start_date ),
		);
	}

	/**
	 * Create a single subscription
	 *
	 * @param \WC_Order $order Order
	 * @param object    $product Product data
	 * @throws \Exception If subscription creation fails
	 */
	private function create_single_subscription( \WC_Order $order, $product ) {

		$product_id = intval( $product->WC_PRODUCT_ID );
		$quantity   = isset( $product->WC_PRODUCT_QTY ) ? intval( $product->WC_PRODUCT_QTY ) : 1;

		$wc_product = wc_get_product( $product_id );
		if ( ! $wc_product ) {
			throw new \Exception( sprintf( 'Product %d not found', esc_html( $product_id ) ) );
		}

		$subscription = wcs_create_subscription(
			array(
				'order_id'         => $order->get_id(),
				'status'           => 'pending',
				'billing_period'   => WC_Subscriptions_Product::get_period( $product_id ),
				'billing_interval' => WC_Subscriptions_Product::get_interval( $product_id ),
			)
		);

		if ( is_wp_error( $subscription ) ) {
			throw new \Exception(
				sprintf(
					'Failed to create subscription: %s',
					esc_html( $subscription->get_error_message() )
				)
			);
		}

		$subscription->add_product( $wc_product, $quantity );
		$subscription->update_dates( $this->get_subscription_dates( $product_id ) );

		if ( $this->has_order_note() ) {
			$subscription->add_order_note( $this->field_values['WCORDERNOTE'] );
		}

		$subscription->update_status( 'active' );
		$subscription->calculate_totals();
	}
}
