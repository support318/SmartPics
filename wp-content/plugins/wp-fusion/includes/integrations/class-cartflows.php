<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_CartFlows extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'cartflows';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'CartFlows';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/ecommerce/cartflows/';

	/**
	 * Gets things started.
	 *
	 * @since   1.0
	 * @return  void
	 */
	public function init() {

		add_action( 'init', array( $this, 'add_action' ) );

		add_action( 'wpf_woocommerce_payment_complete', array( $this, 'maybe_block_ecom_addon' ), 5, 2 );
		add_action( 'wpf_woocommerce_payment_complete', array( $this, 'maybe_sync_upsells' ), 10, 2 );
		add_filter( 'wpf_should_do_asynchronous_checkout', array( $this, 'should_do_asynchronous_checkout' ), 10, 2 );

		// Offer stuff.
		add_action( 'cartflows_offer_accepted', array( $this, 'offer_accepted' ), 10, 2 );
		add_action( 'cartflows_offer_rejected', array( $this, 'offer_rejected' ), 10, 2 );

		// Checkout tags.
		add_filter( 'wpf_woocommerce_apply_tags_checkout', array( $this, 'apply_tags_checkout' ), 10, 2 );

		// Admin settings.
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		// Cartflows admin settings (new UI).
		add_filter( 'cartflows_admin_upsell_step_meta_settings', array( $this, 'get_settings' ), 15, 2 );
		add_filter( 'cartflows_admin_downsell_step_meta_settings', array( $this, 'get_settings' ), 15, 2 );
		add_filter( 'cartflows_admin_checkout_step_meta_settings', array( $this, 'get_checkout_settings' ), 15, 2 );

		// Register the options.
		add_filter( 'cartflows_offer_meta_options', array( $this, 'offer_meta_options' ) );
		add_filter( 'cartflows_checkout_meta_options', array( $this, 'checkout_meta_options' ) );
	}


	/**
	 * Adds CartFlows order status trigger if enabled
	 *
	 * @access public
	 * @return void
	 */
	public function add_action() {

		if ( wpf_get_option( 'cartflows_main_order' ) ) {

			add_action( 'woocommerce_order_status_wcf-main-order', array( wp_fusion()->integrations->woocommerce, 'woocommerce_apply_tags_checkout' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'clear_wpf_complete' ), 5 );

		}
	}

	/**
	 * Don't run the ecommerce addon when the main order is complete
	 *
	 * @access public
	 * @return void
	 */
	public function maybe_block_ecom_addon( $order_id, $contact_id ) {

		$order  = wc_get_order( $order_id );
		$status = $order->get_status();

		// Ecom addon
		if ( function_exists( 'wp_fusion_ecommerce' ) && ! empty( wp_fusion_ecommerce()->integrations ) && 'wcf-main-order' == $status && wpf_get_option( 'cartflows_main_order' ) ) {

			remove_action( 'wpf_woocommerce_payment_complete', array( wp_fusion_ecommerce()->integrations->woocommerce, 'send_order_data' ), 10, 2 );

		}
	}

	/**
	 * If Asynchronous Checkout is enabled, this will trigger any upsell orders after the main order has been processed.
	 *
	 * @since 3.40.8
	 *
	 * @param int    $order_id   The order ID.
	 * @param string $contact_id The contact ID.
	 */
	public function maybe_sync_upsells( $order_id, $contact_id ) {

		if ( wpf_get_option( 'woo_async' ) ) {

			$order = wc_get_order( $order_id );

			$child_orders = $order->get_meta( '_cartflows_offer_child_orders' );

			if ( ! empty( $child_orders ) ) {

				foreach ( $child_orders as $child_order_id => $data ) {

					wp_fusion()->integrations->woocommerce->process_order( $child_order_id );

					if ( 'upsell' === $data['type'] ) {

						$order = wc_get_order( $child_order_id );

						$step_id = $order->get_meta( '_cartflows_offer_step_id' );

						$this->offer_accepted( $order, array(), $step_id );
					}
				}
			}
		}
	}

	/**
	 * Bypass async checkout on upsell and downsell orders.
	 *
	 * @since 3.44.12
	 *
	 * @param bool $async     Whether the checkout should be processed asynchronously.
	 * @param int  $order_id  The order ID.
	 * @return bool Whether the checkout should be processed asynchronously.
	 */
	public function should_do_asynchronous_checkout( $async, $order_id ) {

		if ( true === $async ) {

			$order = wc_get_order( $order_id );

			$offer_type = $order->get_meta( '_cartflows_offer_type' );

			if ( 'upsell' === $offer_type || 'downsell' === $offer_type ) {
				$async = false;
			}
		}

		return $async;
	}

	/**
	 * Clear the wpf_complete flag so the order can be processed again after the main checkout is complete
	 *
	 * @access public
	 * @return void
	 */
	public function clear_wpf_complete( $order_id ) {

		$order = wc_get_order( $order_id );

		$order->delete_meta_data( 'wpf_complete' );
		$order->save();
	}

	/**
	 * Offer accepted
	 *
	 * @access public
	 * @return void
	 */
	public function offer_accepted( $order, $offer_product, $step_id = false ) {

		if ( false === $step_id ) { // $step_id is false when triggered from the hook.
			$step_id = $offer_product['step_id'];
		}

		$setting = get_post_meta( $step_id, 'wpf-offer-accepted', true );

		if ( ! empty( $setting ) ) {

			if ( ! is_array( $setting ) ) { // the new CF UI doesn't save the tags as an array.
				$setting = array( $setting );
			}

			$user_id = $order->get_user_id();

			if ( ! empty( $user_id ) ) {

				wp_fusion()->user->apply_tags( $setting, $user_id );

			} else {

				$contact_id = $order->get_meta( WPF_CONTACT_ID_META_KEY );

				if ( ! empty( $contact_id ) ) {

					wpf_log( 'info', 0, 'Applying offer accepted tags to contact #' . $contact_id . ': ', array( 'tag_array' => $setting ) );

					wp_fusion()->crm->apply_tags( $setting, $contact_id );

				}
			}
		}
	}

	/**
	 * Offer rejected
	 *
	 * @access public
	 * @return void
	 */
	public function offer_rejected( $order, $offer_product ) {

		$setting = get_post_meta( $offer_product['step_id'], 'wpf-offer-rejected', true );

		if ( ! empty( $setting ) ) {

			if ( ! is_array( $setting ) ) { // the new CF UI doesn't save the tags as an array
				$setting = array( $setting );
			}

			$user_id = $order->get_user_id();

			if ( ! empty( $user_id ) ) {

				wp_fusion()->user->apply_tags( $setting, $user_id );

			} else {

				$contact_id = get_post_meta( $order->get_id(), WPF_CONTACT_ID_META_KEY, true );

				if ( ! empty( $contact_id ) ) {

					wpf_log( 'info', 0, 'Applying offer rejected tags to contact #' . $contact_id . ': ', array( 'tag_array' => $setting ) );

					wp_fusion()->crm->apply_tags( $setting, $contact_id );

				}
			}
		}
	}

	/**
	 * Apply tags on checkout based on the flow step.
	 *
	 * @since 3.40.55
	 *
	 * @param array    $apply_tags The tags to apply.
	 * @param WC_Order $order      The order object.
	 * @return array The tags to apply.
	 */
	public function apply_tags_checkout( $apply_tags, $order ) {

		$step_id = wcf()->utils->get_checkout_id_from_order( $order );

		if ( ! empty( $step_id ) ) {

			$setting = get_post_meta( $step_id, 'wpf-apply-tag', true );

			if ( ! empty( $setting ) ) {
				$apply_tags = array_merge( (array) $apply_tags, array( $setting ) );
			}
		}

		return $apply_tags;
	}


	/**
	 * Registers CartFlows settings
	 *
	 * @access  public
	 * @return  array Settings
	 */
	public function register_settings( $settings, $options ) {

		$settings['cartflows_header'] = array(
			'title'   => __( 'CartFlows Integration', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['cartflows_main_order'] = array(
			'title'   => __( 'Run on Main Order Accepted', 'wp-fusion' ),
			'desc'    => __( 'Runs WP Fusion post-checkout actions when the order status is Main Order Accepted instead of waiting for Completed.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		return $settings;
	}

	/**
	 * Adds CartFlows custom fields to Contact Fields list
	 *
	 * @access  public
	 * @return  array Meta fields
	 */
	public function add_meta_fields( $meta_fields ) {

		$args = array(
			'post_type' => 'cartflows_step',
			'fields'    => 'ids',
			'nopaging'  => true,
		);

		$steps = get_posts( $args );

		if ( ! empty( $steps ) ) {

			foreach ( $steps as $step_id ) {

				$fields = get_post_meta( $step_id, 'wcf_field_order_billing', true );

				if ( ! empty( $fields ) && is_array( $fields ) ) {

					$shipping_fields = get_post_meta( $step_id, 'wcf_field_order_shipping', true );

					if ( empty( $shipping_fields ) ) {
						$shipping_fields = array();
					}

					$fields = array_merge( $fields, $shipping_fields );

					foreach ( $fields as $key => $field ) {

						if ( ! isset( $meta_fields[ $key ] ) ) {

							if ( ! isset( $field['type'] ) ) {
								$field['type'] = 'text';
							}

							$meta_fields[ $key ] = array(
								'label' => $field['label'],
								'type'  => $field['type'],
								'group' => 'woocommerce',
							);

						}
					}
				}

				// Optin fields are prefixed with an underscore.

				$optin_fields = get_post_meta( $step_id, 'wcf-optin-fields-billing', true );

				if ( ! empty( $optin_fields ) ) {

					foreach ( $optin_fields as $key => $field ) {

						if ( ! isset( $meta_fields[ '_' . $key ] ) ) {

							$meta_fields[ '_' . $key ] = array(
								'label' => $field['label'],
								'type'  => isset( $field['type'] ) ? $field['type'] : 'text',
								'group' => 'woocommerce',
							);
						}
					}
				}
			}
		}

		return $meta_fields;
	}

	/**
	 * Get Formatted Tags
	 * Gets available tags in the format needed for the UI.
	 *
	 * @since 3.40.55
	 * @since 3.43.21 Fixed incorrect tag IDs with some CRMs.
	 */
	private function get_formatted_tags() {

		$options = array(
			array(
				'value' => '',
				'label' => __( 'Select a tag', 'wp-fusion' ),
			),
		);

		foreach ( wp_fusion()->settings->get_available_tags_flat() as $id => $label ) {

			// We need to convert the tag IDs to strings to prevent a possible overflow error in CartFlows' JS save logic.
			$options[] = array(
				'value' => strval( $id ),
				'label' => $label,
			);

		}

		return $options;
	}


	/**
	 * Register WPF settings (new UI)
	 *
	 * @since  3.37.0
	 *
	 * @param  array $settings settings.
	 * @param  int   $step_id  Post meta.
	 * @return array The settings.
	 */
	public function get_settings( $settings, $step_id ) {

		$accepted = get_post_meta( $step_id, 'wpf-offer-accepted', true );
		$rejected = get_post_meta( $step_id, 'wpf-offer-rejected', true );
		$options  = $this->get_formatted_tags();

		$settings['settings']['settings']['wp_fusion'] = array(
			'title'    => __( 'WP Fusion', 'wp-fusion' ),
			'slug'     => 'wp-fusion',
			'priority' => 20,
			'fields'   => array(
				'wpf-offer-accepted' => array(
					'type'    => 'select',
					'label'   => __( 'Apply Tag', 'wp-fusion' ) . ' - ' . __( 'Offer Accepted', 'wp-fusion' ),
					'name'    => 'wpf-offer-accepted',
					'options' => $options,
					'value'   => $accepted,
				),
				'wpf-offer-rejected' => array(
					'type'    => 'select',
					'label'   => __( 'Apply Tag', 'wp-fusion' ) . ' - ' . __( 'Offer Rejected', 'wp-fusion' ),
					'name'    => 'wpf-offer-rejected',
					'options' => $options,
					'value'   => $rejected,
				),
			),
		);

		return $settings;
	}

	/**
	 * Register WPF options
	 *
	 * @access  public
	 * @return  array Options
	 */
	public function offer_meta_options( $options ) {

		$options['wpf-offer-accepted'] = array(
			'default'  => array(),
			'sanitize' => 'FILTER_DEFAULT',
		);

		$options['wpf-offer-rejected'] = array(
			'default'  => array(),
			'sanitize' => 'FILTER_DEFAULT',
		);

		return $options;
	}

	/**
	 * Register WPF settings on Checkout steps.
	 *
	 * @since  3.40.55
	 *
	 * @param  array $settings The settings.
	 * @param  int   $step_id  The flow ID.
	 * @return array The settings.
	 */
	public function get_checkout_settings( $settings, $step_id ) {

		$settings['settings']['settings']['wp_fusion'] = array(
			'title'    => __( 'WP Fusion', 'wp-fusion' ),
			'slug'     => 'wp-fusion',
			'priority' => 20,
			'fields'   => array(
				'wpf-apply-tag' => array(
					'type'    => 'select',
					'label'   => sprintf( __( 'Apply %s', 'wp-fusion' ), wp_fusion()->crm->tag_type ) . ' - ' . __( 'Checkout Complete', 'wp-fusion' ),
					'name'    => 'wpf-apply-tag',
					'options' => $this->get_formatted_tags(),
					'value'   => get_post_meta( $step_id, 'wpf-apply-tag', true ),
					'tooltip' => sprintf( __( 'This %1$s will be applied in %2$s when this checkout step is completed.', 'wp-fusion' ), wp_fusion()->crm->tag_type, wp_fusion()->crm->name ),
				),
			),
		);

		return $settings;
	}

	/**
	 * Register WPF options (checkouts).
	 *
	 * @since  3.40.55
	 *
	 * @param array $options The options.
	 * @return array Options
	 */
	public function checkout_meta_options( $options ) {

		$options['wpf-apply-tag'] = array(
			'default'  => array(),
			'sanitize' => 'FILTER_DEFAULT',
		);

		return $options;
	}
}

new WPF_CartFlows();
