<?php
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Fields;

class Order_Fields {

	/*************************
	 * MAIN OPTIONS METHOD
	 *************************/

	/**
	 * This method is used to list all input options for the action/triggers.
	 *
	 * @param $payment_method
	 * @return array
	 */
	public function get_options( $payment_method = false ) {

		$options_array = array(

			// Product and Order Fields
			$this->get_products_field_repeater(),
			$this->get_order_status_field(),
			$this->get_payment_gateway_field(),
			$this->get_order_note_field(),
			$this->get_coupon_field(),
			$this->get_order_type_field(),

			// Billing Fields
			$this->get_billing_first_name_field(),
			$this->get_billing_last_name_field(),
			$this->get_billing_email_field(),
			$this->get_billing_company_field(),
			$this->get_billing_phone_field(),
			$this->get_billing_address_1_field(),
			$this->get_billing_address_2_field(),
			$this->get_billing_postal_code_field(),
			$this->get_billing_city_field(),
			$this->get_billing_state_field(),
			$this->get_billing_countries_field(),

			// Shipping Fields
			$this->get_default_shipping_info_field(),
			$this->get_shipping_method_field(),
			$this->get_shipping_cost_field(),
			$this->get_shipping_first_name_field(),
			$this->get_shipping_last_name_field(),
			$this->get_shipping_email_field(),
			$this->get_shipping_company_field(),
			$this->get_shipping_phone_field(),
			$this->get_shipping_address_1_field(),
			$this->get_shipping_address_2_field(),
			$this->get_shipping_postal_code_field(),
			$this->get_shipping_city_field(),
			$this->get_shipping_state_field(),
			$this->get_shipping_countries_field(),
		);

		if ( false === $payment_method ) {
			unset( $options_array[2] );
		}

		return $options_array;
	}

	/************************
	 * CORE/HELPER METHODS
	 ************************/

	/**
	 * @param $country_name
	 * @return mixed
	 */
	public function get_countries() {

		if ( ! class_exists( '\WC_Countries' ) ) {
			return array();
		}

		$countries = ( new \WC_Countries() )->get_countries();

		$options = array(
			array(
				'text'  => esc_html_x( 'Select country/region', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => '',
			),
		);

		foreach ( $countries as $country_code => $country_name ) {

			if ( ! empty( $country_name ) && ! empty( $country_code ) ) {
				$options[] = array(
					'text'  => $country_name,
					'value' => $country_code,
				);
			}
		}

		return apply_filters( 'uap_option_all_wc_get_countries', $options );
	}

	/**
	 * Method get_coupons.
	 * Iterate through coupons api and return the coupons.
	 *
	 * @return array coupons
	 */
	public function get_coupons() {

		$coupon_posts = get_posts(
			array(
				'posts_per_page' => -1,
				'orderby'        => 'name',
				'order'          => 'asc',
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
			)
		);

		$options = array(
			array(
				'text'  => esc_html_x( 'Select coupon', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => '',
			),
		);

		foreach ( $coupon_posts as $coupon_post ) {
			$options[] = array(
				'text'  => $coupon_post->post_title,
				'value' => $coupon_post->post_title,
			);
		}

		return apply_filters( 'automator_woocommerce_get_coupons', $options, $this );
	}

	/**
	 * Get all shipping methods in formatted array structure
	 *
	 * @return array
	 */
	public function get_shipping_methods() {
		$formatted_methods = array_merge(
			$this->get_default_options(),
			$this->get_zone_shipping_options(),
			$this->get_standard_shipping_options()
		);

		return apply_filters(
			'automator_woocommerce_get_shipping_methods_order_fields',
			$formatted_methods,
			$this
		);
	}

	/**
	 * Get default shipping options including select and manual options
	 *
	 * @return array
	 */
	private function get_default_options() {
		return array(
			array(
				'text'  => esc_html_x( 'Select shipping method', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => '-1',
			),
			array(
				'text'  => esc_html_x( 'Manual', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => '0',
			),
		);
	}

	/**
	 * Get shipping options from all zones
	 *
	 * @return array
	 */
	private function get_zone_shipping_options() {
		$formatted_methods = array();
		$zones             = $this->get_shipping_zones();

		foreach ( $zones as $zone ) {
			$zone_methods      = $this->get_enabled_zone_methods( $zone );
			$formatted_methods = array_merge( $formatted_methods, $zone_methods );
		}

		return $formatted_methods;
	}

	/**
	 * Get enabled shipping methods for a specific zone
	 *
	 * @param array $zone Shipping zone data
	 *
	 * @return array
	 */
	private function get_enabled_zone_methods( $zone ) {
		$zone_methods = array();
		$zone_name    = $zone['zone_name'];

		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'yes' !== $method->enabled ) {
				continue;
			}

			$zone_methods[] = array(
				'text'  => $this->format_zone_method_text( $zone_name, $method ),
				'value' => $method->instance_id,
			);
		}

		return $zone_methods;
	}

	/**
	 * Format the display text for zone shipping methods
	 *
	 * @param string $zone_name Zone name
	 * @param object $method    Shipping method object
	 *
	 * @return string
	 */
	private function format_zone_method_text( $zone_name, $method ) {
		return sprintf(
			'%s - %s (%s)',
			$zone_name,
			$method->title,
			$method->method_title
		);
	}

	/**
	 * Get standard shipping options (Free shipping and Flat rate)
	 *
	 * @return array
	 */
	private function get_standard_shipping_options() {
		$standard_methods = array(
			'free_shipping' => esc_html_x( 'Free shipping', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'flat_rate'     => esc_html_x( 'Flat rate', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
		);

		$formatted_methods = array();

		foreach ( $standard_methods as $method_id => $method_name ) {
			$formatted_methods[] = array(
				'text'  => $method_name,
				'value' => $method_id,
			);
		}

		return $formatted_methods;
	}

	/*************************
	 * PRODUCT RELATED METHODS
	 *************************/

	/**
	 * Get list of WooCommerce products
	 *
	 * @param string $product_type Type of product to fetch (default: 'simple')
	 * @return array List of products in [value => id, text => title] format
	 */
	public function get_products_list() {
		$query_args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$products = Automator()->helpers->recipe->options->wp_query( $query_args, false );

		if ( empty( $products ) || ! is_array( $products ) ) {
			return array();
		}

		$products_list = array_map(
			function ( $product_id, $product_title ) {
				return array(
					'value' => $product_id,
					'text'  => esc_html_x( $product_title, 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				);
			},
			array_keys( $products ),
			$products
		);

		return apply_filters( 'uap_option_all_wc_products_list', $products_list );
	}

	/**
	 * Get products field repeater.
	 *
	 * @return array
	 */
	protected function get_products_field_repeater(): array {
		return array(
			'option_code'       => 'WC_PRODUCTS_FIELDS',
			'input_type'        => 'repeater',
			'relevant_tokens'   => array(),
			'label'             => esc_html_x( 'Order items', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'required'          => true,
			'fields'            => array(
				array(
					'option_code' => 'WC_PRODUCT_ID',
					'label'       => esc_html_x( 'Product', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
					'input_type'  => 'select',
					'required'    => true,
					'read_only'   => false,
					'options'     => $this->get_products_list(),
				),
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => 'WC_PRODUCT_QTY',
						'label'       => esc_html_x( 'Quantity', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
						'input_type'  => 'text',
						'tokens'      => true,
					)
				),
			),
			'add_row_button'    => esc_html_x( 'Add product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'remove_row_button' => esc_html_x( 'Remove product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'hide_actions'      => false,
		);
	}

	/*************************
	 * ORDER RELATED METHODS
	 *************************/

	/**
	 * @param string $label
	 * @param string $option_code
	 * @return mixed
	 */
	public function get_order_status_field( $label = null, $option_code = 'WCORDERSTATUS' ) {
		if ( ! $label ) {
			$label = 'Status';
		}

		$options = array_map(
			function ( $text, $value ) {
				return array(
					'value' => $value,
					'text'  => $text,
				);
			},
			wc_get_order_statuses(),
			array_keys( wc_get_order_statuses() )
		);

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options_show_id' => false,
			'options'         => array_values( $options ),
			'default_value'   => 'wc-completed',
		);

		return apply_filters( 'uap_option_woocommerce_pro_statuses', $option );
	}

	/**
	 * Get order note field configuration
	 *
	 * @return array
	 */
	protected function get_order_note_field(): array {
		return array(
			'option_code'     => 'WCORDERNOTE',
			'label'           => esc_html_x( 'Order note', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get order type field configuration
	 *
	 * @return array
	 */
	protected function get_order_type_field(): array {
		return array(
			'option_code'     => 'WCORDER_TYPE',
			'label'           => esc_html_x( 'Order type', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => false,
			'required'        => false,
			'read_only'       => true,
			'default_value'   => 'subscription',
		);
	}

	/**
	 * @param string $option_code
	 * @param string $label
	 * @return array
	 */
	protected function get_coupon_field( string $option_code = 'WC_COUPONS', string $label = '' ): array {

		if ( empty( $label ) ) {
			$label = esc_html_x( 'Coupon', 'WooCommerce Subscription', 'uncanny-automator-pro' );
		}

		return array(
			'option_code'           => $option_code,
			'label'                 => esc_attr( $label ),
			'input_type'            => 'select',
			'supports_tokens'       => true,
			'supports_custom_value' => true,
			'required'              => false,
			'options'               => $this->get_coupons(),
		);
	}

		/*************************
		 * BILLING FIELDS METHODS
		 *************************/

	/**
	 * Get billing first name field configuration
	 *
	 * @return array
	 */
	protected function get_billing_first_name_field(): array {
		return array(
			'option_code'     => 'WCFIRST_NAME',
			'label'           => esc_html_x( 'Billing first name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => '',
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => true,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing last name field configuration
	 *
	 * @return array
	 */
	protected function get_billing_last_name_field(): array {
		return array(
			'option_code'     => 'WCLAST_NAME',
			'label'           => esc_html_x( 'Billing last name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => '',
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => true,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing email field configuration
	 *
	 * @return array
	 */
	protected function get_billing_email_field(): array {
		return array(
			'option_code'     => 'WCEMAIL',
			'label'           => esc_html_x( 'Billing email', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => esc_html_x( '* The order will be linked to the user that matches the Billing email entered above.', 'Woocommerce Subscription', 'uncanny-automator' ),
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => true,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing company field configuration
	 *
	 * @return array
	 */
	protected function get_billing_company_field(): array {
		return array(
			'option_code'     => 'WCCOMPANYNAME',
			'label'           => esc_html_x( 'Billing company name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => '',
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing phone field configuration
	 *
	 * @return array
	 */
	protected function get_billing_phone_field(): array {
		return array(
			'option_code'     => 'WCPHONE',
			'label'           => esc_html_x( 'Billing phone number', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => '',
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing address 1 field configuration
	 *
	 * @return array
	 */
	protected function get_billing_address_1_field(): array {
		return array(
			'option_code'     => 'WCADDRESSONE',
			'label'           => esc_html_x( 'Billing address 1', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => '',
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing address 2 field configuration
	 *
	 * @return array
	 */
	protected function get_billing_address_2_field(): array {
		return array(
			'option_code'     => 'WCADDRESSTWO',
			'label'           => esc_html_x( 'Billing address 2', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => '',
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing postal code field configuration
	 *
	 * @return array
	 */
	protected function get_billing_postal_code_field(): array {
		return array(
			'option_code'     => 'WCPOSTALCODE',
			'label'           => esc_html_x( 'Billing zip/postal code', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => '',
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing city field configuration
	 *
	 * @return array
	 */
	protected function get_billing_city_field(): array {
		return array(
			'option_code'     => 'WCCITY',
			'label'           => esc_html_x( 'Billing city', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => '',
			'placeholder'     => '',
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing state field configuration
	 *
	 * @return array
	 */
	protected function get_billing_state_field(): array {
		return array(
			'option_code'     => 'WCSTATE',
			'label'           => esc_html_x( 'Billing state/province', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => esc_html_x( 'Enter the two-letter state or province abbreviation.', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get billing countries field configuration
	 *
	 * @return array
	 */
	protected function get_billing_countries_field(): array {

		return array(
			'option_code'           => 'WCCOUNTRY',
			'label'                 => esc_html_x( 'Billing country/region', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'supports_tokens'       => true,
			'supports_custom_value' => true,
			'required'              => false,
			'options'               => $this->get_countries(),
		);
	}

	/*************************
	 * SHIPPING FIELDS METHODS
	 *************************/
	/**
	 * Get default shipping info field configuration
	 *
	 * @return array
	 */
	protected function get_default_shipping_info_field(): array {
		return array(
			'option_code'     => 'WCDETAILS',
			'label'           => esc_attr_x( 'Use the same info for shipping?', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'supports_tokens' => false,
			'required'        => false,
			'options'         => array(
				array(
					'text'  => esc_html_x( 'Yes', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					'value' => 'YES',
				),
				array(
					'text'  => esc_html_x( 'No', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					'value' => 'NO',
				),
			),
		);
	}

	/**
	 * @return array
	 */
	protected function get_shipping_method_field(): array {
		return array(
			'option_code'           => 'WC_SHP_METHOD',
			'label'                 => esc_html_x( 'Shipping method', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'supports_tokens'       => true,
			'supports_custom_value' => true,
			'required'              => false,
			'options'               => $this->get_shipping_methods(),
		);
	}

	/**
	 * Get shipping cost field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_cost_field(): array {
		return array(
			'option_code'     => 'WC_SHP_COST',
			'label'           => esc_html_x( 'Shipping cost', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => esc_html_x( 'Enter 0 for no shipping cost or leave it empty to use the default value set for the shipping method.', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping first name field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_first_name_field(): array {
		return array(
			'option_code'     => 'WC_SHP_FIRST_NAME',
			'label'           => esc_html_x( 'Shipping first name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping last name field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_last_name_field(): array {
		return array(
			'option_code'     => 'WC_SHP_LAST_NAME',
			'label'           => esc_html_x( 'Shipping last name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping email field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_email_field(): array {
		return array(
			'option_code'     => 'WC_SHP_EMAIL',
			'label'           => esc_html_x( 'Shipping email', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping company field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_company_field(): array {
		return array(
			'option_code'     => 'WC_SHP_COMPANYNAME',
			'label'           => esc_html_x( 'Shipping company name', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping phone field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_phone_field(): array {
		return array(
			'option_code'     => 'WC_SHP_PHONE',
			'label'           => esc_html_x( 'Shipping phone number', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping address 1 field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_address_1_field(): array {
		return array(
			'option_code'     => 'WC_SHP_ADDRESSONE',
			'label'           => esc_html_x( 'Shipping address 1', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping address 2 field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_address_2_field(): array {
		return array(
			'option_code'     => 'WC_SHP_ADDRESSTWO',
			'label'           => esc_html_x( 'Shipping address 2', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping postal code field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_postal_code_field(): array {
		return array(
			'option_code'     => 'WC_SHP_POSTALCODE',
			'label'           => esc_html_x( 'Shipping zip/postal code', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping city field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_city_field(): array {
		return array(
			'option_code'     => 'WC_SHP_CITY',
			'label'           => esc_html_x( 'Shipping city', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping state field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_state_field(): array {
		return array(
			'option_code'     => 'WC_SHP_STATE',
			'label'           => esc_html_x( 'Shipping state/province', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'description'     => esc_html_x( 'Enter the two-letter state or province abbreviation.', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'text',
			'supports_tokens' => true,
			'required'        => false,
			'default_value'   => '',
		);
	}

	/**
	 * Get shipping countries field configuration
	 *
	 * @return array
	 */
	protected function get_shipping_countries_field(): array {
		return array(
			'option_code'           => 'WC_SHP_COUNTRY',
			'label'                 => esc_html_x( 'Shipping country/region', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'supports_tokens'       => true,
			'supports_custom_value' => true,
			'required'              => false,
			'options'               => $this->get_countries(),
		);
	}

	/*************************
	 * PAYMENT RELATED METHODS
	 *************************/

	/**
	 * Get WooCommerce payment gateways field configuration
	 *
	 * @param string|null $label Custom field label
	 * @param string      $option_code Field option code
	 * @param array       $args Additional arguments
	 * @param bool        $include_any_option Include "Any payment method" option
	 *
	 * @return array Field configuration
	 */
	public function get_payment_gateway_field(
		$label = null,
		$option_code = 'WOOPAYMENTGATEWAY',
		array $args = array(),
		$include_any_option = true
	) {
		$label = $label ?: esc_html_x( 'Payment method', 'WooCommerce Subscription', 'uncanny-automator-pro' ); // phpcs:ignore Universal.Operators.DisallowShortTernary.Found

		$field_config = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'description'              => $args['description'] ?? false,
			'input_type'               => 'select',
			'required'                 => $args['required'] ?? true,
			'options'                  => $this->get_payment_gateway_options( $include_any_option ),
			'supports_multiple_values' => false,
			'options_show_id'          => false,
		);

		return apply_filters( 'uap_option_all_wc_payment_gateways', $field_config );
	}

	/**
	 * Get available payment gateway options
	 *
	 * @param bool $include_any_option Include "Any payment method" option
	 *
	 * @return array[] Array of options with text/value pairs
	 */
	private function get_payment_gateway_options( $include_any_option = true ) {
		$options = array();

		if ( $include_any_option ) {
			$options[] = array(
				'text'  => esc_html_x( 'Any payment method', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => '-1',
			);
		}

		$gateways = WC()->payment_gateways->payment_gateways();

		foreach ( $gateways as $gateway ) {
			if ( 'yes' !== $gateway->enabled ) {
				continue;
			}

			$title = $gateway->title ?: sprintf( // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
				// translators: %s is the gateway ID
				esc_html_x( 'ID: %s (no title)', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				$gateway->id
			);

			$options[] = array(
				'text'  => $title,
				'value' => $gateway->id,
			);
		}

		return $options;
	}

	/**
	 * Method get_shipping_zones.
	 *
	 * Retrieve all shipping zones.
	 *
	 * @return array The shippong zones.
	 */
	public function get_shipping_zones() {

		// Bail if '\WC_Shipping_Zones' is not available.
		if ( ! class_exists( '\WC_Shipping_Zones' ) ) {

			return array();

		}

		$delivery_zones = \WC_Shipping_Zones::get_zones();

		if ( ! empty( $delivery_zones ) ) {

			return $delivery_zones;

		}

		return array();
	}

	/**
	 * Get all available shipping methods with their details
	 *
	 * @return array
	 */
	public function get_available_shipping_methods() {
		$methods = $this->get_default_shipping_methods();
		$zones   = $this->get_shipping_zones();

		foreach ( $zones as $zone ) {
			$zone_methods = $this->get_zone_shipping_methods( $zone );
			$methods      = array_merge( $methods, $zone_methods );
		}

		$methods = array_merge( $methods, $this->get_standard_shipping_methods() );

		return apply_filters( 'automator_woocommerce_get_shipping_methods', $methods );
	}

	/**
	 * Get default shipping methods
	 *
	 * @return array
	 */
	private function get_default_shipping_methods() {
		return array(
			'-1' => array(
				'label'   => esc_html_x( 'Select shipping method', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
				'details' => array(),
			),
			'0'  => array(
				'label'   => esc_html_x( 'Manual', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
				'details' => array(
					'title' => esc_html_x( 'Manual', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
					'id'    => 0,
					'cost'  => 0,
				),
			),
		);
	}

	/**
	 * Get shipping methods for a specific zone
	 *
	 * @param array $zone Shipping zone data
	 *
	 * @return array
	 */
	private function get_zone_shipping_methods( $zone ) {
		$methods = array();

		foreach ( $zone['shipping_methods'] as $method ) {
			if ( 'yes' !== $method->enabled ) {
				continue;
			}

			$instance_id             = $method->instance_id;
			$methods[ $instance_id ] = array(
				'label'   => sprintf(
					'%s - %s (%s)',
					$zone['zone_name'],
					$method->title,
					$method->method_title
				),
				'details' => array(
					'title' => $method->title,
					'id'    => $method->id,
					'cost'  => isset( $method->cost ) ? $method->cost : 0,
				),
			);
		}

		return $methods;
	}

	/**
	 * Get standard shipping methods (Free shipping and Flat rate)
	 *
	 * @return array
	 */
	private function get_standard_shipping_methods() {
		return array(
			'free_shipping' => array(
				'label'   => esc_html_x( 'Free shipping', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
				'details' => array(
					'title' => esc_html_x( 'Free shipping', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
					'id'    => 'free_shipping',
					'cost'  => 0,
				),
			),
			'flat_rate'     => array(
				'label'   => esc_html_x( 'Flat rate', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
				'details' => array(
					'title' => esc_html_x( 'Flat rate', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
					'id'    => 'flat_rate',
					'cost'  => 0,
				),
			),
		);
	}
}
