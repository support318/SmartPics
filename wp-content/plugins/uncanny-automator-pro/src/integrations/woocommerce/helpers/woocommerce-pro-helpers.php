<?php
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

namespace Uncanny_Automator_Pro;

use Exception;
use Uncanny_Automator\Woocommerce_Helpers;
use WC_Countries;
use WC_Order_Item_Shipping;
use WC_Product;
use WC_Shipping_Zones;
use WC_Subscriptions_Product;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Tags;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable\Product_Categories;
use Uncanny_Automator\Integrations\Woocommerce\Tokens\Trigger\Loopable\Order_Items;

/**
 * Class Woocommerce_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Woocommerce_Pro_Helpers extends Woocommerce_Helpers {

	/**
	 * @var array
	 */
	public $shipping_method_details = array();

	/**
	 * Woocommerce_Pro_Helpers constructor.
	 */
	public function __construct( $load_action_hook = true ) {

		if ( true === $load_action_hook ) {
			add_action(
				'wp_ajax_select_variations_from_WOOSELECTVARIATION',
				array(
					$this,
					'select_all_product_variations',
				)
			);

			add_action(
				'wp_ajax_select_all_terms_by_SELECTED_TAXONOMY',
				array(
					$this,
					'select_all_terms_by_taxonomy',
				)
			);
			add_action(
				'wp_ajax_select_variations_from_WOOSELECTVARIATION_with_any_option',
				array(
					$this,
					'select_all_product_variations_with_any',
				)
			);

			add_action(
				'wp_ajax_select_variations_WOOSELECTVARIATION_FROM_with_any_option',
				array(
					$this,
					'select_all_product_variations_from_to_to',
				)
			);

			add_filter(
				'uap_option_woocommerce_statuses',
				array(
					$this,
					'add_any_status_option',
				),
				99,
				3
			);
			add_filter(
				'uap_option_all_wc_countries',
				array(
					$this,
					'add_empty_option_to_dd',
				)
			);

			add_filter(
				'uap_option_woocommerce_statuses',
				array(
					$this,
					'remove_option_id_from_dropdown',
				)
			);

			add_filter( 'uap_option_all_wc_variable_products', array( $this, 'remove_rel_tokens_from_dd' ) );
			add_filter( 'automator_option_select_field', array( $this, 'remove_rel_tokens_from_dd' ) );

		}
	}

	/**
	 * @param $item_id
	 * @param $order_id
	 *
	 * @return array|\WC_Order_Item
	 */
	public static function get_order_item_by_id( $item_id, $order_id ) {
		$order = wc_get_order( $order_id );
		foreach ( $order->get_items() as $line_item_id => $line_item ) {
			if ( $item_id === $line_item_id ) {
				return $line_item;
			}
		}

		return array();
	}

	/**
	 * @param $trigger
	 * @param bool    $is_new
	 *
	 * @return mixed
	 */
	public static function add_loopable_tokens( $trigger, $is_new = false ) {
		if ( version_compare( AUTOMATOR_PLUGIN_VERSION, '5.10', '>=' ) ) {
			$loopable_tokens = array(
				'ORDER_ITEMS'        => Order_Items::class,
				'PRODUCT_TAGS'       => Product_Tags::class,
				'PRODUCT_CATEGORIES' => Product_Categories::class,
			);

			if ( $is_new ) {
				return $loopable_tokens;
			}

			$trigger['loopable_tokens'] = $loopable_tokens;
		}

		return $trigger;
	}

	/**
	 * @param $label
	 * @param $option_code
	 *
	 * @return array|mixed|null
	 */
	public function all_wc_products( $label = null, $option_code = 'WOOPRODUCT', $is_any = true, $relevant_tokens = true ) {

		if ( ! $label ) {
			$label = esc_attr_x( 'Product', 'Woocommerce', 'uncanny-automator' );
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$options        = Automator()->helpers->recipe->options->wp_query( $args, $is_any, esc_attr_x( 'Any product', 'Woocommerce', 'uncanny-automator' ) );
		$default_tokens = array();
		if ( true === $relevant_tokens ) {
			$default_tokens = array(
				$option_code                => esc_attr_x( 'Product title', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_ID'        => esc_attr_x( 'Product ID', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_URL'       => esc_attr_x( 'Product URL', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_THUMB_ID'  => esc_attr_x( 'Product featured image ID', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_THUMB_URL' => esc_attr_x( 'Product featured image URL', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_ORDER_QTY' => esc_attr_x( 'Product quantity', 'Woocommerce', 'uncanny-automator' ),
			);
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => $default_tokens,
		);

		return apply_filters( 'uap_option_all_wc_products', $option );
	}

	/**
	 * @param Woocommerce_Pro_Helpers $pro
	 */
	public function setPro( Woocommerce_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}

	/**
	 * Add empty option to WC countries dropdown.
	 *
	 * @param $options
	 *
	 * @return array
	 */
	public function add_empty_option_to_dd( $options ) {
		$empty_option = array( '' => esc_attr_x( 'Select country/region', 'Woocommerce', 'uncanny-automator-pro' ) );
		$options = $empty_option + $options;
		return $options;
	}

	/**
	 * @param $option
	 *
	 * @return mixed
	 */
	public function remove_rel_tokens_from_dd( $option ) {

		if ( isset( $option['option_code'] ) && ( 'ANON_WC_VARINVENTORYCHANGED_META' === $option['option_code'] || 'WC_WOOVARIPRODUCT' === $option['option_code'] ) ) {
			$option['relevant_tokens'] = array();
		}

		return $option;
	}

	/**
	 * Adding option to hide option id from dropdown.
	 *
	 * @param $options
	 *
	 * @return mixed
	 */
	public function remove_option_id_from_dropdown( $options ) {

		$options['options_show_id'] = false;

		return $options;
	}

		/**
	 * This method is used to list all input options for the action/triggers.
	 *
	 * @param $payment_method
	 *
	 * @return array
	 */
	public function load_options_input( $payment_method = false, $order_type = 'simple' ) {

		$defaults = array(
			'option_code'           => 'WCDETAILS',
			'label'                 => esc_attr__( 'Use the same info for shipping?', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'supports_tokens'       => false,
			'required'              => false,
			'default_value'         => null,
			'placeholder'           => esc_attr__( 'Use the same info for shipping?', 'uncanny-automator-pro' ),
			'supports_custom_value' => false,
			'options_show_id'       => false,
			'options'               => array(
				'YES' => __( 'Yes', 'uncanny-automator-pro' ),
				'NO'  => __( 'No', 'uncanny-automator-pro' ),
			),
		);

		$bl_countries = array(
			'option_code'           => 'WCCOUNTRY',
			'label'                 => esc_attr__( 'Billing country/region', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'supports_tokens'       => true,
			'required'              => false,
			'placeholder'           => esc_attr__( 'Billing country/region', 'uncanny-automator-pro' ),
			'supports_custom_value' => true,
			//'options_show_id'       => false,
			'options'               => $this->get_countries(),
		);

		$sp_countries = array(
			'option_code'           => 'WC_SHP_COUNTRY',
			'label'                 => esc_attr__( 'Shipping country/region', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'supports_tokens'       => true,
			'required'              => false,
			'placeholder'           => esc_attr__( 'Shipping country/region', 'uncanny-automator-pro' ),
			'supports_custom_value' => true,
			//'options_show_id'       => false,
			'options'               => $this->get_countries(),
		);

		$shipping_methods = array(
			'option_code'           => 'WC_SHP_METHOD',
			'label'                 => esc_attr__( 'Shipping method', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'supports_tokens'       => true,
			'supports_custom_value' => true,
			'required'              => false,
			//'options_show_id'       => false,
			'options'               => $this->get_shipping_methods(),
		);

		$coupons = array(
			'option_code'           => 'WC_COUPONS',
			'label'                 => esc_attr__( 'Coupon', 'uncanny-automator-pro' ),
			'input_type'            => 'select',
			'supports_tokens'       => true,
			'supports_custom_value' => true,
			'required'              => false,
			//'options_show_id'       => false,
			'options'               => $this->get_coupons(),
		);

		$options_array = array(
			array(
				'option_code'       => 'WC_PRODUCTS_FIELDS',
				'input_type'        => 'repeater',
				'relevant_tokens'   => array(),
				'label'             => __( 'Order items', 'uncanny-automator-pro' ),
				'required'          => true,
				'fields'            => array(
					array(
						'option_code' => 'WC_PRODUCT_ID',
						'label'       => __( 'Product', 'uncanny-automator-pro' ),
						'input_type'  => 'select',
						'required'    => true,
						'read_only'   => false,
						'options'     => $this->all_wc_products_list( $order_type ),
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'WC_PRODUCT_QTY',
							'label'       => __( 'Quantity', 'uncanny-automator-pro' ),
							'input_type'  => 'text',
							'tokens'      => true,
						)
					),
				),
				'add_row_button'    => __( 'Add product', 'uncanny-automator-pro' ),
				'remove_row_button' => __( 'Remove product', 'uncanny-automator-pro' ),
				'hide_actions'      => false,
			),
			$this->wc_order_statuses_pro( __( 'Order status', 'uncanny-automator-pro' ) ),
			Automator()->helpers->recipe->woocommerce->pro->all_wc_payment_gateways( __( 'Payment gateway', 'uncanny-automator-pro' ), 'WOOPAYMENTGATEWAY', array(), false ),
			Automator()->helpers->recipe->field->text_field( 'WCORDERNOTE', __( 'Order note', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->select( $coupons ),
			Automator()->helpers->recipe->field->text_field( 'WCFIRST_NAME', __( 'Billing first name', 'uncanny-automator-pro' ), true, 'text', '', true ),
			Automator()->helpers->recipe->field->text_field( 'WCLAST_NAME', __( 'Billing last name', 'uncanny-automator-pro' ), true, 'text', '', true ),
			Automator()->helpers->recipe->field->text_field( 'WCEMAIL', __( 'Billing email', 'uncanny-automator-pro' ), true, 'text', '', true, __( '* The order will be linked to the user that matches the Billing email entered above.', 'uncanny-automator' ) ),
			Automator()->helpers->recipe->field->text_field( 'WCCOMPANYNAME', __( 'Billing company name', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WCPHONE', __( 'Billing phone number', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WCADDRESSONE', __( 'Billing address 1', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WCADDRESSTWO', __( 'Billing address 2', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WCPOSTALCODE', __( 'Billing zip/postal code', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WCCITY', __( 'Billing city', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WCSTATE', __( 'Billing state/province', 'uncanny-automator-pro' ), true, 'text', '', false, __( 'Enter the two-letter state or province abbreviation.', 'uncanny-automator-pro' ) ),
			Automator()->helpers->recipe->field->select( $bl_countries ),
			Automator()->helpers->recipe->field->select( $defaults ),
			Automator()->helpers->recipe->field->select( $shipping_methods ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_COST', __( 'Shipping cost', 'uncanny-automator-pro' ), true, 'text', '', false, __( 'Enter 0 for no shipping cost or leave it empty to use the default value set for the shipping method.', 'uncanny-automator-pro' ) ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_FIRST_NAME', __( 'Shipping first name', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_LAST_NAME', __( 'Shipping last name', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_EMAIL', __( 'Shipping email', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_COMPANYNAME', __( 'Shipping company name', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_PHONE', __( 'Shipping phone number', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_ADDRESSONE', __( 'Shipping address 1', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_ADDRESSTWO', __( 'Shipping address 2', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_POSTALCODE', __( 'Shipping zip/postal code', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_CITY', __( 'Shipping city', 'uncanny-automator-pro' ), true, 'text', '', false ),
			Automator()->helpers->recipe->field->text_field( 'WC_SHP_STATE', __( 'Shipping state/province', 'uncanny-automator-pro' ), true, 'text', '', false, __( 'Enter the two-letter state or province abbreviation.', 'uncanny-automator-pro' ) ),
			Automator()->helpers->recipe->field->select( $sp_countries ),
		);

		//      if ( 'subscription' === $order_type ) {
		//          unset( $options_array[1] );
		//      }

		if ( false === $payment_method ) {
			unset( $options_array[2] );
		}

		return Automator()->utilities->keep_order_of_options( $options_array );
	}

	/**
	 * @param $country_name
	 *
	 * @return mixed
	 */
	public function get_countries() {

		$cnt = new WC_Countries();

		$options = $cnt->get_countries();

		return apply_filters( 'uap_option_all_wc_countries', $options );
	}

	/**
	 * Method get_shipping_methods.
	 *
	 * Iterate through shipping zones and return the shipping methods.
	 *
	 * @return array The shipping methods
	 */
	public function get_shipping_methods() {

		$methods = array();

		$methods['-1']                    = esc_html_x( 'Select shipping method', 'WooCommerce', 'uncanny-automator-pro' );
		$methods['0']                     = esc_html_x( 'Manual', 'WooCommerce', 'uncanny-automator-pro' );
		$this->shipping_method_details[0] = array(
			'title' => esc_html_x( 'Manual', 'WooCommerce', 'uncanny-automator-pro' ),
			'id'    => 0,
		);
		foreach ( $this->get_shipping_zones() as $zone ) {
			$zone_name = $zone['zone_name'];
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( 'yes' !== $method->enabled ) {
					continue;
				}
				$instance_id             = $method->instance_id;
				$method_title            = $method->method_title;
				$title                   = $method->title;
				$methods[ $instance_id ] = sprintf( '%s - %s (%s)', $zone_name, $title, $method_title );

				$this->shipping_method_details[ $instance_id ] = array(
					'title' => $title,
					'id'    => $method->id,
					'cost'  => isset( $method->cost ) ? $method->cost : 0,
				);
			}
		}
		$methods['free_shipping']                       = esc_html_x( 'Free shipping', 'WooCommerce', 'uncanny-automator-pro' );
		$this->shipping_method_details['free_shipping'] = array(
			'title' => esc_html_x( 'Free shipping', 'WooCommerce', 'uncanny-automator-pro' ),
			'id'    => 'free_shipping',
			'cost'  => 0,
		);
		$methods['flat_rate']                           = esc_html_x( 'Flat rate', 'WooCommerce', 'uncanny-automator-pro' );
		$this->shipping_method_details['flat_rate']     = array(
			'title' => esc_html_x( 'Flat rate', 'WooCommerce', 'uncanny-automator-pro' ),
			'id'    => 'flat_rate',
			'cost'  => 0,
		);

		return apply_filters( 'automator_woocommerce_get_shipping_methods', $methods, $this );
	}

	/**
	 * Method get_shipping_zones.
	 *
	 * Retrieve all shipping zones.
	 *
	 * @return array The shipping zones.
	 */
	public function get_shipping_zones() {

		// Bail if '\WC_Shipping_Zones' is not available.
		if ( ! class_exists( '\WC_Shipping_Zones' ) ) {

			return array();

		}

		$delivery_zones = WC_Shipping_Zones::get_zones();

		if ( ! empty( $delivery_zones ) ) {

			return $delivery_zones;

		}

		return array();

	}

	/**
	 * Method get_coupons.
	 *
	 * Iterate through coupons api and return the coupons.
	 *
	 * @return array coupons
	 */
	public function get_coupons() {

		$coupon_codes = array();

		$coupon_codes['0'] = __( 'Select coupon', 'uncanny-automator-pro' );

		$coupon_posts = get_posts(
			array(
				'posts_per_page' => 9999999,
				'orderby'        => 'name',
				'order'          => 'asc',
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
			)
		);

		foreach ( $coupon_posts as $coupon_post ) {
			$coupon_codes[ $coupon_post->post_title ] = $coupon_post->post_title;
		}

		return apply_filters( 'automator_woocommerce_get_coupons', $coupon_codes, $this );

	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function all_wc_products_list( $product_type = 'simple' ) {

		if ( 'subscription' === $product_type ) {

			$subscriptions = wc_get_products(
				array(
					'type'  => array( 'subscription', 'variable-subscription' ),
					'limit' => 99999,
				)
			);
			$products_list = array();
			$temp_array    = array();

			foreach ( $subscriptions as $product ) {
				$temp_array['value'] = $product->get_id();
				$temp_array['text']  = $product->get_name();
				array_push( $products_list, $temp_array );
			}
		} else {
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => 999999,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			);

			$options       = Automator()->helpers->recipe->options->wp_query( $args, false );
			$products_list = array();
			$temp_array    = array();
			if ( is_array( $options ) ) {
				foreach ( $options as $key => $value ) {
					$temp_array['value'] = $key;
					$temp_array['text']  = $value;
					array_push( $products_list, $temp_array );
				}
			}
		}

		return apply_filters( 'uap_option_all_wc_products_list', $products_list );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function wc_order_statuses_pro( $label = null, $option_code = 'WCORDERSTATUS' ) {

		if ( ! $label ) {
			$label = 'Status';
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options_show_id' => false,
			'options'         => wc_get_order_statuses(),
			'default_value'   => 'wc-completed',
		);

		return apply_filters( 'uap_option_woocommerce_pro_statuses', $option );
	}


	/**
	 * @param null   $label
	 * @param string $option_code
	 * @param array  $args
	 *
	 * @return array|mixed|void
	 */
	public function all_wc_payment_gateways( $label = null, $option_code = 'WOOPAYMENTGATEWAY', $args = array(), $is_any = true ) {

		$description = key_exists( 'description', $args ) ? $args['description'] : false;
		$required    = key_exists( 'required', $args ) ? $args['required'] : true;
		if ( ! $label ) {
			$label = esc_html_x( 'Payment method', 'WooCommerce', 'uncanny-automator-pro' );
		}

		$methods = WC()->payment_gateways->payment_gateways();

		$options = array();

		if ( true === $is_any ) {
			$options['-1'] = esc_html_x( 'Any payment method', 'WooCommerce', 'uncanny-automator-pro' );
		}

		foreach ( $methods as $method ) {
			if ( 'yes' === $method->enabled ) {
				$title = $method->title;
				if ( empty( $title ) ) {
					// translators: %1$s is the gateway ID
					$title = sprintf( esc_html_x( 'ID: %1$s (no title)', 'WooCommerce', 'uncanny-automator-pro' ), $method->id );
				}
				$options[ $method->id ] = $title;
			}
		}

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'description'              => $description,
			'input_type'               => 'select',
			'required'                 => $required,
			'options'                  => $options,
			'supports_multiple_values' => false,
			'options_show_id'          => false,
		);

		return apply_filters( 'uap_option_all_wc_payment_gateways', $option );
	}

	/**
	 * @param null   $label
	 * @param string $option_code
	 * @param array  $args
	 *
	 * @return mixed|void
	 */
	public function all_wc_variable_products( $label = null, $option_code = 'WOOVARIABLEPRODUCTS', $args = array() ) {

		if ( ! $label ) {
			$label = esc_html_x( 'Product', 'WooCommerce', 'uncanny-automator-pro' );
		}

		$token        = key_exists( 'token', $args ) ? $args['token'] : false;
		$is_ajax      = key_exists( 'is_ajax', $args ) ? $args['is_ajax'] : false;
		$target_field = key_exists( 'target_field', $args ) ? $args['target_field'] : '';
		$end_point    = key_exists( 'endpoint', $args ) ? $args['endpoint'] : '';

		$variable_prods = wc_get_products(
			array(
				'type'  => array( 'variable' ),
				'limit' => -1,
			)
		);
		$options        = array();
		$options['-1']  = esc_html_x( 'Any product', 'WooCommerce', 'uncanny-automator-pro' );
		if ( $variable_prods ) {
			foreach ( $variable_prods as $product ) {
				$title = $product->get_name();
				if ( empty( $title ) ) {
					// translators: %1$s is the product ID
					$title = sprintf( esc_html_x( 'ID: %1$s (no title)', 'WooCommerce', 'uncanny-automator-pro' ), $product->get_id() );
				}
				$options[ $product->get_id() ] = $title;
			}
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'supports_tokens' => $token,
			'is_ajax'         => $is_ajax,
			'fill_values_in'  => $target_field,
			'endpoint'        => $end_point,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code                => esc_html_x( 'Product title', 'WooCommerce', 'uncanny-automator-pro' ),
				$option_code . '_ID'        => esc_html_x( 'Product ID', 'WooCommerce', 'uncanny-automator-pro' ),
				$option_code . '_URL'       => esc_html_x( 'Product URL', 'WooCommerce', 'uncanny-automator-pro' ),
				$option_code . '_THUMB_ID'  => esc_html_x( 'Product featured image ID', 'WooCommerce', 'uncanny-automator-pro' ),
				$option_code . '_THUMB_URL' => esc_html_x( 'Product featured image URL', 'WooCommerce', 'uncanny-automator-pro' ),
			),
		);

		return apply_filters( 'uap_option_all_wc_variable_products', $option );
	}

	/**
	 * @param null   $label
	 * @param string $option_code
	 *
	 * @return mixed|void
	 */
	public function all_wc_product_categories( $label = null, $option_code = 'WOOPRODCAT', $args = array() ) {

		$supports_multiple_values = key_exists( 'supports_multiple_values', $args ) ? $args['supports_multiple_values'] : false;
		$description              = key_exists( 'description', $args ) ? $args['description'] : false;
		$required                 = key_exists( 'required', $args ) ? $args['required'] : true;
		if ( ! $label ) {
			$label = esc_html_x( 'Categories', 'WooCommerce', 'uncanny-automator-pro' );
		}

		global $wpdb;

		$categories = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT terms.term_id,terms.name FROM $wpdb->terms as terms
				LEFT JOIN $wpdb->term_taxonomy as rel ON (terms.term_id = rel.term_id)
				WHERE rel.taxonomy = %s
				ORDER BY terms.name",
				'product_cat'
			)
		);

		$options       = array();
		$options['-1'] = esc_html_x( 'Any category', 'WooCommerce', 'uncanny-automator-pro' );

		foreach ( $categories as $category ) {
			$title = $category->name;
			if ( empty( $title ) ) {
				// translators: %1$s is the category ID
				$title = sprintf( esc_html_x( 'ID: %1$s (no title)', 'WooCommerce', 'uncanny-automator-pro' ), $category->term_id );
			}
			$options[ $category->term_id ] = $title;
		}

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'description'              => $description,
			'input_type'               => 'select',
			'required'                 => $required,
			'options'                  => $options,
			'supports_multiple_values' => $supports_multiple_values,
			'relevant_tokens'          => array(
				$option_code          => esc_html_x( 'Category title', 'WooCommerce', 'uncanny-automator-pro' ),
				$option_code . '_ID'  => esc_html_x( 'Category ID', 'WooCommerce', 'uncanny-automator-pro' ),
				$option_code . '_URL' => esc_html_x( 'Category URL', 'WooCommerce', 'uncanny-automator-pro' ),
			),
		);

		// Support custom token value.
		if ( isset( $args['supports_custom_value'] ) && true === $args['supports_custom_value'] ) {
			$option['supports_custom_value']    = true;
			$option['custom_value_description'] = isset( $args['custom_value_description'] )
				? $args['custom_value_description']
				: esc_html_x( 'Product category ID', 'WooCommerce', 'uncanny-automator-pro' );
		}

		return apply_filters( 'uap_option_all_wc_product_categories', $option );
	}

	/**
	 * @param null   $label
	 * @param string $option_code
	 *
	 * @return mixed|void
	 */
	public function all_wc_product_tags( $label = null, $option_code = 'WOOPRODTAG' ) {

		if ( ! $label ) {
			$label = esc_html_x( 'Tags', 'WooCommerce', 'uncanny-automator-pro' );
		}

		global $wpdb;

		$tags = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT terms.term_id,terms.name FROM $wpdb->terms as terms
				LEFT JOIN $wpdb->term_taxonomy as rel ON (terms.term_id = rel.term_id)
				WHERE rel.taxonomy = %s
				ORDER BY terms.name",
				'product_tag'
			)
		);

		$options       = array();
		$options['-1'] = esc_html_x( 'Any tag', 'WooCommerce', 'uncanny-automator-pro' );

		foreach ( $tags as $tag ) {
			$title = $tag->name;
			if ( empty( $title ) ) {
				// translators: %1$s is the tag ID.
				$title = sprintf( esc_html_x( 'ID: %1$s (no title)', 'WooCommerce', 'uncanny-automator-pro' ), $tag->term_id );
			}
			$options[ $tag->term_id ] = $title;
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code          => esc_html_x( 'Tag title', 'WooCommerce', 'uncanny-automator-pro' ),
				$option_code . '_ID'  => esc_html_x( 'Tag ID', 'WooCommerce', 'uncanny-automator-pro' ),
				$option_code . '_URL' => esc_html_x( 'Tag URL', 'WooCommerce', 'uncanny-automator-pro' ),
			),
		);

		return apply_filters( 'uap_option_all_wc_product_tags', $option );
	}

	/**
	 * get all variations of selected variable product
	 */
	public function select_all_product_variations() {

		// Nonce and post object validation
		Automator()->utilities->verify_nonce();

		$fields = array();
		if ( automator_filter_has_var( 'value', INPUT_POST ) && ! empty( automator_filter_input( 'value', INPUT_POST ) ) ) {

			$args = array(
				'post_type'      => 'product_variation',
				'post_parent'    => absint( automator_filter_input( 'value', INPUT_POST ) ),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			);

			$options = get_posts( $args );
			if ( isset( $options ) && ! empty( $options ) ) {
				foreach ( $options as $option ) {
					$fields[] = array(
						'value' => $option->ID,
						'text'  => ! empty( $option->post_excerpt ) ? $option->post_excerpt : $option->post_title,
					);
				}
			} else {
				$fields[] = array(
					'value' => -1,
					'text'  => esc_html_x( 'Any variation', 'WooCommerce', 'uncanny-automator-pro' ),
				);
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * get all variations of selected variable product
	 */
	public function select_all_product_variations_with_any() {

		// Nonce and post object validation
		Automator()->utilities->verify_nonce();

		$fields = array();
		if ( automator_filter_has_var( 'value', INPUT_POST ) && ! empty( automator_filter_input( 'value', INPUT_POST ) ) ) {

			$fields[] = array(
				'value' => -1,
				'text'  => esc_html_x( 'Any variation', 'WooCommerce', 'uncanny-automator-pro' ),
			);

			$args = array(
				'post_type'      => 'product_variation',
				'post_parent'    => absint( automator_filter_input( 'value', INPUT_POST ) ),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			);

			$options = get_posts( $args );
			if ( isset( $options ) && ! empty( $options ) ) {
				foreach ( $options as $option ) {
					$fields[] = array(
						'value' => $option->ID,
						'text'  => ! empty( $option->post_excerpt ) ? $option->post_excerpt : $option->post_title,
					);
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * get all variations of selected variable product
	 */
	public function select_all_product_variations_from_to_to() {
		// Nonce and post object validation
		Automator()->utilities->ajax_auth_check();

		$fields = array();

		$fields[] = array(
			'value' => -1,
			'text'  => esc_html_x( 'Any variation', 'WooCommerce', 'uncanny-automator-pro' ),
		);
		if ( isset( $_POST['values']['WOOVARIPRODUCT'] ) && intval( '-1' ) !== intval( $_POST['values']['WOOVARIPRODUCT'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$args = array(
				'post_type'      => 'product_variation',
				//phpcs:ignore WordPress.Security.NonceVerification.Missing
				'post_parent'    => absint( sanitize_text_field( wp_unslash( $_POST['values']['WOOVARIPRODUCT'] ) ) ),
				//phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'posts_per_page' => 9999,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			);

			$options = get_posts( $args );
			if ( ! empty( $options ) ) {
				foreach ( $options as $option ) {
					if (
						//phpcs:ignore WordPress.Security.NonceVerification.Missing
						isset( $_POST['values']['WOOVARIPRODUCT_FROM'] ) &&
						//phpcs:ignore WordPress.Security.NonceVerification.Missing
						absint( $option->ID ) === absint( sanitize_text_field( wp_unslash( $_POST['values']['WOOVARIPRODUCT_FROM'] ) ) )
					) {
						continue;
					}
					$fields[] = array(
						'value' => $option->ID,
						'text'  => ! empty( $option->post_excerpt ) ? $option->post_excerpt : $option->post_title,
					);
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * @param string $code
	 *
	 * @return mixed|void
	 */
	public function get_woocommerce_trigger_conditions( $code = 'TRIGGERCOND' ) {
		$options = array(
			'option_code'     => $code,
			/* translators: Noun */
			'label'           => esc_attr_x( 'Trigger condition', 'Woocommerce', 'uncanny-automator-pro' ),
			'input_type'      => 'select',
			'required'        => true,
			'options_show_id' => false,
			'options'         => $this->get_trigger_condition_labels(),
		);

		return apply_filters( 'uap_option_woocommerce_trigger_conditions', $options );
	}

	/**
	 * Fetch labels for trigger conditions.
	 *
	 * @return array
	 * @since 2.10
	 */
	public function get_trigger_condition_labels() {
		/**
		 * Filters WooCommerce Integrations' trigger conditions.
		 *
		 * @param array $trigger_conditions An array of key-value pairs of action hook handle and human readable label.
		 */
		return apply_filters(
			'uap_wc_trigger_conditions',
			array(
				'woocommerce_payment_complete'       => esc_html_x( 'pays for', 'WooCommerce', 'uncanny-automator-pro' ),
				'woocommerce_order_status_completed' => esc_html_x( 'completes', 'WooCommerce', 'uncanny-automator-pro' ),
				'woocommerce_thankyou'               => esc_html_x( 'lands on a thank you page for', 'WooCommerce', 'uncanny-automator-pro' ),
			)
		);
	}

	/**
	 * @param string $code
	 *
	 * @return mixed|void
	 */
	public function get_woocommerce_order_item_trigger_conditions( $code = 'TRIGGERCOND' ) {
		$options = array(
			'option_code' => $code,
			/* translators: Noun */
			'label'       => esc_attr_x( 'Trigger condition', 'Woocommerce', 'uncanny-automator-pro' ),
			'input_type'  => 'select',
			'required'    => true,
			'options'     => $this->get_order_item_trigger_condition_labels(),
		);

		return apply_filters( 'uap_option_woocommerce_order_item_trigger_conditions', $options );
	}

	/**
	 * Fetch labels for trigger conditions.
	 *
	 * @return array
	 * @since 3.4
	 */
	public function get_order_item_trigger_condition_labels() {
		/**
		 * Filters WooCommerce Integrations' trigger conditions.
		 *
		 * @param array $trigger_conditions An array of key-value pairs of action hook handle and human readable label.
		 */
		return apply_filters(
			'uap_wc_order_item_trigger_conditions',
			array(
				'woocommerce_payment_complete'       => esc_html_x( 'paid for', 'WooCommerce', 'uncanny-automator-pro' ),
				'woocommerce_order_status_completed' => esc_html_x( 'completed', 'WooCommerce', 'uncanny-automator-pro' ),
				'woocommerce_thankyou'               => esc_html_x( 'thank you page visited', 'WooCommerce', 'uncanny-automator-pro' ),
			)
		);
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function all_wc_products_multiselect( $label = null, $option_code = 'WOOPRODUCT', $settings = array() ) {

		if ( ! $label ) {
			$label = esc_attr_x( 'Product', 'Woocommerce', 'uncanny-automator' );
		}
		$description = '';
		if ( isset( $settings['description'] ) ) {
			$description = $settings['description'];
		}

		$required = key_exists( 'required', $settings ) ? $settings['required'] : true;

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$options = Automator()->helpers->recipe->options->wp_query( $args, false, esc_attr_x( 'Any product', 'Woocommerce', 'uncanny-automator' ) );

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'description'              => $description,
			'input_type'               => 'select',
			'required'                 => $required,
			'options'                  => $options,
			'supports_multiple_values' => true,
			'relevant_tokens'          => array(
				$option_code                => esc_attr_x( 'Product title', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_ID'        => esc_attr_x( 'Product ID', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_URL'       => esc_attr_x( 'Product URL', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_THUMB_ID'  => esc_attr_x( 'Product featured image ID', 'Woocommerce', 'uncanny-automator' ),
				$option_code . '_THUMB_URL' => esc_attr_x( 'Product featured image URL', 'Woocommerce', 'uncanny-automator' ),
			),
		);

		// Support custom token value.
		if ( isset( $settings['supports_custom_value'] ) && true === $settings['supports_custom_value'] ) {
			$option['supports_custom_value']    = true;
			$option['custom_value_description'] = isset( $settings['custom_value_description'] )
				? $settings['custom_value_description']
				: esc_html_x( 'Product ID', 'WooCommerce', 'uncanny-automator-pro' );
		}

		return apply_filters( 'uap_option_all_wc_products', $option );
	}

	/**
	 * @param null   $label
	 * @param string $option_code
	 *
	 * @return mixed|void
	 */
	public function get_wcs_statuses( $label = null, $option_code = 'WOOSUBSCRIPTIONSTATUS', $is_any = true ) {
		if ( ! $label ) {
			$label = esc_attr_x( 'Status', 'Woocommerce', 'uncanny-automator' );
		}
		$statuses = wcs_get_subscription_statuses();
		$options  = array();

		if ( true === $is_any ) {
			$options['-1'] = esc_html_x( 'Any status', 'WooCommerce', 'uncanny-automator-pro' );
		}

		$options = $options + $statuses;
		$option  = array(
			'option_code' => $option_code,
			'label'       => $label,
			'input_type'  => 'select',
			'required'    => true,
			'options'     => $options,
		);

		return apply_filters( 'uap_option_all_wc_statuses', $option );
	}

	/**
	 * Method wc_create_order.
	 *
	 * @return mixed Returns void if the billing is missing. Otherwise boolean,
	 *     True if order is successfull. False for failure.
	 * @throws \WC_Data_Exception
	 */
	public function wc_create_order( $user_id, $action_data, $recipe_id, $args, $payment_method = false, $order_type = 'simple' ) {

		$products = json_decode( $action_data['meta']['WC_PRODUCTS_FIELDS'] );

		// if ( 'simple' === $order_type ) {
		$order_status = isset( $action_data['meta']['WCORDERSTATUS'] ) ? sanitize_text_field( $action_data['meta']['WCORDERSTATUS'] ) : 'wc-completed';
		// }

		$payment_gateways = '';

		if ( true === $payment_method ) {
			$payment_gateways = sanitize_text_field( $action_data['meta']['WOOPAYMENTGATEWAY'] );
		}

		$billing_first_name    = Automator()->parse->text( $action_data['meta']['WCFIRST_NAME'], $recipe_id, $user_id, $args );
		$billing_last_name     = Automator()->parse->text( $action_data['meta']['WCLAST_NAME'], $recipe_id, $user_id, $args );
		$billing_email         = Automator()->parse->text( $action_data['meta']['WCEMAIL'], $recipe_id, $user_id, $args );
		$billing_company_name  = Automator()->parse->text( $action_data['meta']['WCCOMPANYNAME'], $recipe_id, $user_id, $args );
		$billing_phone         = Automator()->parse->text( $action_data['meta']['WCPHONE'], $recipe_id, $user_id, $args );
		$billing_address_1     = Automator()->parse->text( $action_data['meta']['WCADDRESSONE'], $recipe_id, $user_id, $args );
		$billing_address_2     = Automator()->parse->text( $action_data['meta']['WCADDRESSTWO'], $recipe_id, $user_id, $args );
		$billing_pincode       = Automator()->parse->text( $action_data['meta']['WCPOSTALCODE'], $recipe_id, $user_id, $args );
		$billing_city          = Automator()->parse->text( $action_data['meta']['WCCITY'], $recipe_id, $user_id, $args );
		$billing_state         = Automator()->parse->text( $action_data['meta']['WCSTATE'], $recipe_id, $user_id, $args );
		$billing_country       = Automator()->parse->text( $action_data['meta']['WCCOUNTRY'], $recipe_id, $user_id, $args );
		$details_chk           = Automator()->parse->text( $action_data['meta']['WCDETAILS'], $recipe_id, $user_id, $args );
		$shipping_method       = Automator()->parse->text( $action_data['meta']['WC_SHP_METHOD'], $recipe_id, $user_id, $args );
		$shipping_cost         = Automator()->parse->text( $action_data['meta']['WC_SHP_COST'], $recipe_id, $user_id, $args );
		$shipping_first_name   = Automator()->parse->text( $action_data['meta']['WC_SHP_FIRST_NAME'], $recipe_id, $user_id, $args );
		$shipping_last_name    = Automator()->parse->text( $action_data['meta']['WC_SHP_LAST_NAME'], $recipe_id, $user_id, $args );
		$shipping_email        = Automator()->parse->text( $action_data['meta']['WC_SHP_EMAIL'], $recipe_id, $user_id, $args );
		$shipping_company_name = Automator()->parse->text( $action_data['meta']['WC_SHP_COMPANYNAME'], $recipe_id, $user_id, $args );
		$shipping_phone        = Automator()->parse->text( $action_data['meta']['WC_SHP_PHONE'], $recipe_id, $user_id, $args );
		$shipping_address_1    = Automator()->parse->text( $action_data['meta']['WC_SHP_ADDRESSONE'], $recipe_id, $user_id, $args );
		$shipping_address_2    = Automator()->parse->text( $action_data['meta']['WC_SHP_ADDRESSTWO'], $recipe_id, $user_id, $args );
		$shipping_pincode      = Automator()->parse->text( $action_data['meta']['WC_SHP_POSTALCODE'], $recipe_id, $user_id, $args );
		$shipping_city         = Automator()->parse->text( $action_data['meta']['WC_SHP_CITY'], $recipe_id, $user_id, $args );
		$shipping_state        = Automator()->parse->text( $action_data['meta']['WC_SHP_STATE'], $recipe_id, $user_id, $args );
		$shipping_country      = Automator()->parse->text( $action_data['meta']['WC_SHP_COUNTRY'], $recipe_id, $user_id, $args );
		$order_note            = Automator()->parse->text( $action_data['meta']['WCORDERNOTE'], $recipe_id, $user_id, $args );
		$coupon                = Automator()->parse->text( $action_data['meta']['WC_COUPONS'], $recipe_id, $user_id, $args );

		$billing_country  = $this->find_country( $billing_country );
		$billing_state    = $this->find_state( $billing_state, $billing_country );
		$shipping_country = $this->find_country( $shipping_country );
		$shipping_state   = $this->find_state( $shipping_state, $shipping_country );

		$address = array(
			'first_name' => $billing_first_name,
			'last_name'  => $billing_last_name,
			'company'    => $billing_company_name,
			'email'      => $billing_email,
			'phone'      => $billing_phone,
			'address_1'  => $billing_address_1,
			'address_2'  => $billing_address_2,
			'city'       => $billing_city,
			'state'      => $billing_state,
			'postcode'   => $billing_pincode,
			'country'    => $billing_country,
		);

		$shipping_address = array(
			'first_name' => $shipping_first_name,
			'last_name'  => $shipping_last_name,
			'company'    => $shipping_company_name,
			'email'      => $shipping_email,
			'phone'      => $shipping_phone,
			'address_1'  => $shipping_address_1,
			'address_2'  => $shipping_address_2,
			'city'       => $shipping_city,
			'state'      => $shipping_state,
			'postcode'   => $shipping_pincode,
			'country'    => $shipping_country,
		);

		// $username = $billing_email;

		$newly_created_user = false;
		// Create the user.
		$wc_user_id = email_exists( $billing_email );
		if ( false === $wc_user_id ) {
			$wc_user_id = username_exists( $billing_email );
		}
		if ( false === $wc_user_id ) {
			$wc_user_id = 0;
		}
		if ( is_numeric( $user_id ) && 0 !== $user_id ) {
			// If a user ID is already available, and
			// it's not 0, use that instead of the billing email
			$wc_user_id = $user_id;
		}

		// $user = get_user_by( 'id', $wc_user_id );

		// $wc_user_id = ( isset( $user->ID ) ) ? $user->ID : $wc_user_id;

		// Create the order.
		$order = wc_create_order( array( 'customer_id' => $wc_user_id ) );

		// Add some notes to the order.
		$order->add_order_note( $order_note );

		if ( is_array( $products ) ) {
			foreach ( $products as $product ) {
				if ( isset( $product->WC_PRODUCT_ID ) ) {//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$wc_product_id  = Automator()->parse->text( $product->WC_PRODUCT_ID, $recipe_id, $user_id, $args );//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$wc_product_qty = Automator()->parse->text( $product->WC_PRODUCT_QTY, $recipe_id, $user_id, $args );//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					if ( 'automator_custom_value' === $product->WC_PRODUCT_ID && isset( $product->WC_PRODUCT_ID_custom ) ) {//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$wc_product_id = Automator()->parse->text( $product->WC_PRODUCT_ID_custom, $recipe_id, $user_id, $args );//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
					if ( 'automator_custom_value' === $product->WC_PRODUCT_QTY && isset( $product->WC_PRODUCT_QTY_custom ) ) {//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$wc_product_qty = Automator()->parse->text( $product->WC_PRODUCT_QTY, $recipe_id, $user_id, $args );//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
					$wc_product = wc_get_product( $wc_product_id );
					if ( $wc_product instanceof WC_Product ) {
						$order->add_product( $wc_product, $wc_product_qty );
					}
				}
			}
		}

		$order->set_address( $address, 'billing' );

		// Update user's billing data.
		if ( true === $newly_created_user ) {
			update_user_meta( $wc_user_id, 'billing_address_1', $order->get_billing_address_1() );
			update_user_meta( $wc_user_id, 'billing_address_2', $order->get_billing_address_2() );
			update_user_meta( $wc_user_id, 'billing_city', $order->get_billing_city() );
			update_user_meta( $wc_user_id, 'billing_company', $order->get_billing_company() );
			update_user_meta( $wc_user_id, 'billing_country', $order->get_billing_country() );
			update_user_meta( $wc_user_id, 'billing_email', $order->get_billing_email() );
			update_user_meta( $wc_user_id, 'billing_first_name', $order->get_billing_first_name() );
			update_user_meta( $wc_user_id, 'billing_last_name', $order->get_billing_last_name() );
			update_user_meta( $wc_user_id, 'billing_phone', $order->get_billing_phone() );
			update_user_meta( $wc_user_id, 'billing_postcode', $order->get_billing_postcode() );
			update_user_meta( $wc_user_id, 'billing_state', $order->get_billing_state() );
		}

		// User selected 'Yes'. Use the same shipping address as billing.
		if ( 'YES' === $details_chk ) {

			$order->set_address( $address, 'shipping' );

			if ( true === $newly_created_user ) {
				// user's shipping data
				update_user_meta( $wc_user_id, 'shipping_address_1', $order->get_billing_address_1() );
				update_user_meta( $wc_user_id, 'shipping_address_2', $order->get_billing_address_2() );
				update_user_meta( $wc_user_id, 'shipping_city', $order->get_billing_city() );
				update_user_meta( $wc_user_id, 'shipping_company', $order->get_billing_company() );
				update_user_meta( $wc_user_id, 'shipping_country', $order->get_billing_country() );
				update_user_meta( $wc_user_id, 'shipping_email', $order->get_billing_email() );
				update_user_meta( $wc_user_id, 'shipping_first_name', $order->get_billing_first_name() );
				update_user_meta( $wc_user_id, 'shipping_last_name', $order->get_billing_last_name() );
				update_user_meta( $wc_user_id, 'shipping_phone', $order->get_billing_phone() );
				update_user_meta( $wc_user_id, 'shipping_postcode', $order->get_billing_postcode() );
				update_user_meta( $wc_user_id, 'shipping_state', $order->get_billing_state() );
			}
		} else {

			// Otherwise, get the shipping address.
			$order->set_address( $shipping_address, 'shipping' );

			if ( true === $newly_created_user ) {
				// user's shipping data
				update_user_meta( $wc_user_id, 'shipping_address_1', $order->get_shipping_address_1() );
				update_user_meta( $wc_user_id, 'shipping_address_2', $order->get_shipping_address_2() );
				update_user_meta( $wc_user_id, 'shipping_city', $order->get_shipping_city() );
				update_user_meta( $wc_user_id, 'shipping_company', $order->get_shipping_company() );
				update_user_meta( $wc_user_id, 'shipping_country', $order->get_shipping_country() );
				update_user_meta( $wc_user_id, 'shipping_first_name', $order->get_shipping_first_name() );
				update_user_meta( $wc_user_id, 'shipping_last_name', $order->get_shipping_last_name() );
				update_user_meta( $wc_user_id, 'shipping_method', $order->get_shipping_method() );
				update_user_meta( $wc_user_id, 'shipping_postcode', $order->get_shipping_postcode() );
				update_user_meta( $wc_user_id, 'shipping_state', $order->get_shipping_state() );
			}
		}

		if ( ! empty( $coupon ) ) {
			$order->apply_coupon( wc_format_coupon_code( $coupon ) );
		}

		if ( 'subscription' === $order_type ) {

			if ( is_array( $products ) ) {

				foreach ( $products as $product ) {

					if ( isset( $product->WC_PRODUCT_ID ) ) {

						$sub = wcs_create_subscription(
							array(
								'order_id'         => $order->get_id(),
								'status'           => 'pending',
								// Status should be initially set to pending to match how normal checkout process goes
								'billing_period'   => WC_Subscriptions_Product::get_period( intval( $product->WC_PRODUCT_ID ) ),
								'billing_interval' => WC_Subscriptions_Product::get_interval( intval( $product->WC_PRODUCT_ID ) ),
							)
						);

						if ( is_wp_error( $sub ) ) {
							// translators: %1$s is the error message
							$error_message                       = sprintf( esc_html_x( 'Failed to create a subscription. %1$s', 'WooCommerce', 'uncanny-automator-pro' ), $sub->get_error_message() );
							$action_data['do-nothing']           = true;
							$action_data['complete_with_errors'] = true;
							Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

							return;
						}

						$sub->add_product( wc_get_product( intval( $product->WC_PRODUCT_ID ) ), intval( $product->WC_PRODUCT_QTY ) );

						// Modeled after WC_Subscriptions_Cart::calculate_subscription_totals()
						$start_date = gmdate( 'Y-m-d H:i:s' );
						// Add product to subscription

						$dates = array(
							'trial_end'    => WC_Subscriptions_Product::get_trial_expiration_date( intval( $product->WC_PRODUCT_ID ), $start_date ),
							'next_payment' => WC_Subscriptions_Product::get_first_renewal_payment_date( intval( $product->WC_PRODUCT_ID ), $start_date ),
							'end'          => WC_Subscriptions_Product::get_expiration_date( intval( $product->WC_PRODUCT_ID ), $start_date ),
						);

						$sub->update_dates( $dates );
						$sub->add_order_note( $order_note );
						$sub->update_status( 'active' );
						$sub->calculate_totals();
					}
				}
			}

			// $order->update_status( 'completed' );
			// Also update subscription status to active from pending (and add note)
			$order->calculate_totals();
		}

		if ( true === $payment_method ) {
			$order->set_payment_method( $payment_gateways );
		}

		// if ( 'simple' === $order_type ) {
		$order->calculate_totals();
		$order->update_status( $order_status, $order_note, true );
		// }

		// Assign shipping method.
		if ( ! empty( $shipping_method ) ) {

			$shipping_method = $this->set_shipping_method( $shipping_method, $shipping_cost );

			if ( ! empty( $shipping_method ) ) {

				$order->add_item( $shipping_method );

				$order->calculate_totals();

			}
		}

		if ( '' === $billing_country ) {

			$action_data['do-nothing'] = true;

			$action_data['complete_with_errors'] = true;

			Automator()->complete_action( $user_id, $action_data, $recipe_id, 'Failed to create order. Billing country is required' );

			return;

		}

		$order->save();

		if ( ! is_wp_error( $order ) ) {

			$args = array(
				'action_data'    => $action_data,
				'recipe_id'      => $recipe_id,
				'args'           => $args,
				'payment_method' => $payment_method,
				'order_type'     => $order_type,
			);

			do_action( 'automator_pro_woocommerce_order_created', $order, $user_id, $args, $this );

			return $order;

		} else {

			return false;

		}
	}

	/**
	 * @param $country
	 *
	 * @return false|int|string
	 */
	public function find_country( $country ) {
		if ( 2 === strlen( $country ) ) {
			return strtoupper( $country );
		}
		if ( 'united states' === strtolower( $country ) ) {
			return 'US';
		}
		if ( 'united kingdom' === strtolower( $country ) ) {
			return 'UK';
		}
		$wc_countries_list = WC()->countries->countries;

		return array_search( ucfirst( $country ), $wc_countries_list, true );
	}

	/**
	 * @param $state
	 * @param $country
	 *
	 * @return string
	 */
	public function find_state( $state, $country ) {
		$wc_countries_list = WC()->countries->states;
		if ( ! isset( $wc_countries_list[ $country ] ) || 2 === strlen( $state ) ) {
			return $state;
		}

		$states_list = $wc_countries_list[ $country ];

		$state_search = array_search( ucfirst( $state ), $states_list, true );

		return 2 === strlen( $state_search ) ? strtoupper( $state_search ) : $state_search;
	}

	/**
	 * @param $instance_id
	 * @param $shipping_cost
	 *
	 * @return \WC_Order_Item_Shipping|null
	 */
	public function set_shipping_method( $instance_id, $shipping_cost = 0 ) {

		if ( ! class_exists( '\WC_Order_Item_Shipping' ) ) {

			return null;

		}
		if ( empty( $this->shipping_method_details ) ) {
			$this->get_shipping_methods();
		}
		$shipping_method = isset( $this->shipping_method_details[ $instance_id ] ) ? $this->shipping_method_details[ $instance_id ] : array();
		if ( empty( $shipping_method ) ) {

			return null;
		}

		$item = new WC_Order_Item_Shipping();
		if ( empty( $shipping_cost ) ) {
			$shipping_cost = isset( $shipping_method['cost'] ) ? $shipping_method['cost'] : 0;
		}
		try {

			$item->set_method_title( $shipping_method['title'] );
			$item->set_method_id( $shipping_method['id'] . ':' . $instance_id ); // set an existing Shipping method rate ID
			$item->set_total( $shipping_cost );

		} catch ( Exception $e ) {

			$item = null;

		}

		return $item;
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function wc_subscription_statuses( $label = null, $option_code = 'WCS_STATUS', $is_any = true ) {
		if ( ! $label ) {
			$label = esc_html_x( 'Status', 'WooCommerce', 'uncanny-automator-pro' );
		}

		$options = array();

		if ( true === $is_any ) {
			$options['-1'] = esc_html_x( 'Any status', 'WooCommerce', 'uncanny-automator-pro' );
		}

		$options = array(
			'wc-active'         => esc_html_x( 'Active', 'WooCommerce', 'uncanny-automator-pro' ),
			'wc-cancelled'      => esc_html_x( 'Cancelled', 'WooCommerce', 'uncanny-automator-pro' ),
			'wc-expired'        => esc_html_x( 'Expired', 'WooCommerce', 'uncanny-automator-pro' ),
			'wc-on-hold'        => esc_html_x( 'On hold', 'WooCommerce', 'uncanny-automator-pro' ),
			'wc-pending-cancel' => esc_html_x( 'Pending cancellation', 'WooCommerce', 'uncanny-automator-pro' ),
		);

		$option = array(
			'option_code' => $option_code,
			'label'       => $label,
			'input_type'  => 'select',
			'required'    => true,
			'options'     => $options,
		);

		return apply_filters( 'uap_option_woocommerce_statuses', $option );
	}

	/**
	 * @param $label
	 * @param $option_code
	 *
	 * @return mixed|void
	 */
	public function all_wc_coupons( $label = null, $option_code = 'WOOCOUPONS', $is_any = false ) {
		if ( ! $label ) {
			$label = esc_attr_x( 'Coupon', 'Woocommerce', 'uncanny-automator' );
		}

		$args = array(
			'post_type'      => 'shop_coupon',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$options = Automator()->helpers->recipe->options->wp_query( $args, $is_any, esc_attr_x( 'Any coupon', 'Woocommerce', 'uncanny-automator' ) );

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(),
		);

		return apply_filters( 'uap_option_all_wc_coupons', $option );
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function add_any_status_option( $options ) {
		if ( empty( $options ) ) {
			return $options;
		}

		if ( 'WCORDERSTATUS' !== $options['option_code'] ) {
			return $options;
		}

		$options['options'] = array( '-1' => esc_attr_x( 'Any status', 'Woocommerce', 'uncanny-automator-pro' ) ) + $options['options'];

		return $options;
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function wc_stock_statuses( $label = null, $option_code = 'WCPRODSTOCKSTATUS', $is_any = false ) {

		if ( ! $label ) {
			$label = esc_attr_x( 'Product stock status', 'Woocommerce', 'uncanny-automator-pro' );
		}

		$status = array();
		if ( true === $is_any ) {
			$status['-1'] = esc_html_x( 'Any status', 'WooCommerce', 'uncanny-automator-pro' );
		}

		$options = array(
			'onbackorder' => esc_attr_x( 'On backorder', 'Woocommerce', 'uncanny-automator-pro' ),
			'instock'     => esc_attr_x( 'In stock', 'Woocommerce', 'uncanny-automator-pro' ),
			'outofstock'  => esc_attr_x( 'Out of stock', 'Woocommerce', 'uncanny-automator-pro' ),
		);

		$status = $status + $options;

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $status,
			'relevant_tokens' => array(),
		);

		return apply_filters( 'uap_option_woocommerce_stock_statuses', $option );
	}

	/**
	 * Get Subscription Condition Select field args.
	 *
	 * @param string $option_code - The option code identifier.
	 *
	 * @return array
	 */
	public function get_subscription_condition_field_args( $option_code ) {
		return array(
			'option_code'           => $option_code,
			'label'                 => esc_html_x( 'Subscription product', 'Woocommerce', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_subscription_condition_select_options(),
			'supports_custom_value' => true,
		);
	}

	/**
	 * Get Subscription Condition Select Options.
	 *
	 * @return array
	 */
	public function get_subscription_condition_select_options() {

		// Get the cached options.
		static $condition_options = null;
		if ( ! is_null( $condition_options ) ) {
			return $condition_options;
		}
		$condition_options             = array();
		$all_woo_subscription_products = $this->all_wc_subscriptions();
		$options                       = isset( $all_woo_subscription_products['options'] ) ? $all_woo_subscription_products['options'] : array();
		if ( empty( $options ) ) {
			return $condition_options;
		}

		foreach ( $options as $id => $text ) {
			$condition_options[] = array(
				'value' => $id,
				'text'  => $text,
			);
		}

		return $condition_options;
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function all_wc_subscriptions( $label = null, $option_code = 'WOOSUBSCRIPTIONS', $is_any = true, $is_all = false, $relevant_tokens = true ) {
		return array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => array(),
			'relevant_tokens' => array(),
		);
	}

	/**
	 * Evaluate Subscription Product Conditions
	 *
	 * @param $product_id - WP_Post ID of the subscription product.
	 * @param $user_id - WP_User ID
	 *
	 * @return bool
	 */
	public function evaluate_subscription_product_condition( $product_id, $user_id ) {

		$active_statuses = apply_filters(
			'wcs_reports_active_statuses',
			array(
				'active',
				'pending-cancel',
				/*,'on-hold'*/
			)
		);

		// Check for any Subscription product.
		if ( intval( $product_id ) < 0 ) {
			$active_subscriptions = wcs_get_users_subscriptions( $user_id );

			return ! empty( $active_subscriptions );
		}

		// Check for Specific Subscription product.
		return wcs_user_has_subscription( $user_id, $product_id, $active_statuses );
	}

	/**
	 * @param        $label
	 * @param string $option_code
	 * @param array  $args
	 *
	 * @return array|mixed|null
	 */
	public function wc_taxonomies( $label = null, $option_code = 'WC_TAXONOMIES', $args = array() ) {

		if ( ! $label ) {
			$label = esc_attr_x( 'Taxonomy', 'Woocommerce', 'uncanny-automator-pro' );
		}

		$is_ajax      = key_exists( 'is_ajax', $args ) ? $args['is_ajax'] : false;
		$target_field = key_exists( 'target_field', $args ) ? $args['target_field'] : '';
		$end_point    = key_exists( 'endpoint', $args ) ? $args['endpoint'] : '';

		$pass_args = array(
			'public'      => true,
			'object_type' => array( 'product' ),
		);

		$output = 'object';

		$taxonomies = get_taxonomies( $pass_args, $output );

		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$options[ $taxonomy->name ] = esc_html( $taxonomy->labels->singular_name );
			}
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'is_ajax'         => $is_ajax,
			'fill_values_in'  => $target_field,
			'endpoint'        => $end_point,
			'relevant_tokens' => array(
				$option_code => esc_attr_x( 'Product taxonomy', 'Woocommerce', 'uncanny-automator-pro' ),
			),
		);

		return apply_filters( 'uap_option_wc_taxonomies', $option );
	}

	/**
	 * @return void
	 */
	public function select_all_terms_by_taxonomy() {
		// Nonce and post object validation
		Automator()->utilities->ajax_auth_check();
		$options[] = array(
			'value' => '-1',
			'text'  => esc_html_x( 'Any term', 'WooCommerce', 'uncanny-automator-pro' ),
		);
		if ( ! automator_filter_has_var( 'value', INPUT_POST ) || empty( automator_filter_input( 'value', INPUT_POST ) ) ) {
			echo wp_json_encode( $options );
			die();
		}
		$terms = get_terms(
			array(
				'taxonomy'   => automator_filter_input( 'value', INPUT_POST ),
				'hide_empty' => false,
			)
		);

		foreach ( $terms as $term ) {
			$title = $term->name;
			if ( empty( $title ) ) {
				// translators: %1$s is the term ID
				$title = sprintf( esc_html_x( 'ID: %1$s (no title)', 'WooCommerce', 'uncanny-automator-pro' ), $term->term_id );
			}
			$options[] = array(
				'value' => $term->term_id,
				'text'  => $title,
			);
		}

		echo wp_json_encode( $options );
		die();
	}

	/**
	 * @return array[]
	 */
	public function wc_common_product_tokens() {
		return array(
			array(
				'tokenId'   => 'WC_PRODUCT_ID',
				'tokenName' => esc_html_x( 'Product ID', 'Woocommerce', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'WC_PRODUCT_URL',
				'tokenName' => esc_html_x( 'Product URL', 'Woocommerce', 'uncanny-automator-pro' ),
				'tokenType' => 'url',
			),
			array(
				'tokenId'   => 'WC_PRODUCT_TITLE',
				'tokenName' => esc_html_x( 'Product title', 'Woocommerce', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'WC_PRODUCT_FEATURED_IMAGE_ID',
				'tokenName' => esc_html_x( 'Product featured image ID', 'Woocommerce', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'WC_PRODUCT_FEATURED_IMAGE_URL',
				'tokenName' => esc_html_x( 'Product featured image', 'Woocommerce', 'uncanny-automator-pro' ),
				'tokenType' => 'url',
			),
			array(
				'tokenId'   => 'WC_PRODUCT_SKU',
				'tokenName' => esc_html_x( 'Product SKU', 'Woocommerce', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'WC_PRODUCT_CATEGORIES',
				'tokenName' => esc_html_x( 'Product categories', 'Woocommerce', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'WC_PRODUCT_TAGS',
				'tokenName' => esc_html_x( 'Product tags', 'Woocommerce', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);
	}

	/**
	 * @param $product
	 *
	 * @return array
	 */
	public function wc_parse_common_product_tokens( $product, $type = 'variation' ) {
		$product_id         = $product->get_id();
		$featured_image_url = ( get_the_post_thumbnail_url( $product_id ) ) ? get_the_post_thumbnail_url( $product_id ) : '';
		$featured_image_id  = ( get_post_thumbnail_id( $product_id ) ) ? get_post_thumbnail_id( $product_id ) : '';
		if ( 'variation' === $type ) {
			$featured_image_id  = ( get_post_thumbnail_id( $product->get_id() ) ) ? get_post_thumbnail_id( $product->get_id() ) : get_post_thumbnail_id( $product->get_parent_id() );
			$featured_image_url = ( get_the_post_thumbnail_url( $product->get_id() ) ) ? get_the_post_thumbnail_url( $product->get_id() ) : get_the_post_thumbnail_url( $product->get_parent_id() );
			$product_id         = $product->get_parent_id();
		}

		$categories = wp_list_pluck( wp_get_post_terms( $product_id, 'product_cat' ), 'name', 'slug' );
		$tags       = wp_list_pluck( wp_get_post_terms( $product_id, 'product_tag' ), 'name', 'slug' );

		return array(
			'WC_PRODUCT_FEATURED_IMAGE_URL' => $featured_image_url,
			'WC_PRODUCT_FEATURED_IMAGE_ID'  => $featured_image_id,
			'WC_PRODUCT_URL'                => $product->get_permalink(),
			'WC_PRODUCT_ID'                 => $product->get_id(),
			'WC_PRODUCT_TITLE'              => $product->get_name(),
			'WC_PRODUCT_SKU'                => $product->get_sku(),
			'WC_PRODUCT_CATEGORIES'         => ( is_array( $categories ) ) ? implode( ', ', $categories ) : '',
			'WC_PRODUCT_TAGS'               => ( is_array( $tags ) ) ? implode( ', ', $tags ) : '',
		);
	}

	/**
	 * @return array
	 */
	public function get_all_product_types() {
		$product_types = wc_get_product_types();
		$options       = array();
		foreach ( $product_types as $value => $product_type ) {
			$options[] = array(
				'value' => $value,
				'text'  => $product_type,
			);
		}

		return $options;
	}

	/**
	 * @return array[]
	 */
	public function get_all_product_tax_class_options() {
		$shipping_classes = wc_get_product_tax_class_options();
		$options          = array(
			array(
				'value' => '-1',
				'text'  => 'No shipping class',
			),
		);
		foreach ( $shipping_classes as $value => $class ) {
			$options[] = array(
				'value' => $value,
				'text'  => $class,
			);
		}

		return $options;
	}

	/**
	 * @return array
	 */
	public function get_all_stock_options() {
		$stock_options = wc_get_product_stock_status_options();
		$options       = array();
		foreach ( $stock_options as $value => $class ) {
			$options[] = array(
				'value' => $value,
				'text'  => $class,
			);
		}

		return $options;
	}

	/**
	 * Get raw customer lifetime value.
	 *
	 * @param mixed $order_id The ID.
	 * @return mixed
	 */
	public function get_raw_customer_lifetime_value( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return 0;
		}

		$customer_id = $order->get_customer_id();

		if ( ! $customer_id ) {
			return 0; // Guest checkout, no registered customer
		}

		$customer = new \WC_Customer( $customer_id );

		return (float) $customer->get_total_spent();
	}
}
