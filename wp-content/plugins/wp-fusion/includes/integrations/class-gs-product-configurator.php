<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Gravity Forms Product Configurator
 *
 * @since 3.44.14
 */
class WPF_GS_Product_Configurator extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.44.14
	 * @var string $slug
	 */

	public $slug = 'gs-product-configurator';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.44.14
	 * @var string $name
	 */
	public $name = 'Gravity Forms Product Configurator';

	/**
	 * Gets things started.
	 *
	 * @since 3.44.14
	 */
	public function init() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 12, 3 );

		// 9 for it to run before the process of the feed
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'remove_gforms_feed_processing' ), 9, 3 );
	}

	/**
	 * Remove WP Fusion Gravity Forms feed processing during add to cart
	 *
	 * @since 3.44.14
	 *
	 * @param bool $valid      Whether the product is valid.
	 * @param int  $product_id The product ID.
	 * @param int  $quantity   The quantity.
	 * @return bool Whether the product is valid.
	 */
	public function remove_gforms_feed_processing( $valid, $product_id, $quantity ) {

		add_filter( 'wpf_gforms_skip_feed_processing', '__return_true' );

		return $valid;
	}

	/**
	 * WooCommerce order status changed
	 *
	 * @since 3.44.14
	 *
	 * @param int    $order_id Order ID.
	 * @param string $previous_status Previous order status.
	 * @param string $next_status Next order status.
	 */
	public function order_status_changed( $order_id, $previous_status, $next_status ) {

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			return;
		}

		/**
		 * @var \WC_Order_Item $order_item
		 */
		foreach ( $order->get_items() as $order_item ) {
			$item    = \GS_Product_Configurator\WC_Order_Item::from( $order_item );
			$entries = $item->get_entries();
			foreach ( $entries as $entry ) {
				$this->update_entry( $entry, $order, $next_status );
			}
		}

		// Remove the action to prevent duplicates
		remove_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 12, 3 );
	}

	/**
	 * Update gravity form entry
	 *
	 * @since 3.44.14
	 *
	 * @param array     $entry Entry data.
	 * @param \WC_Order $order Order data.
	 * @param string    $next_status Next order status.
	 */
	public function update_entry( $entry, $order, $next_status ) {

		$wpf_status = gform_get_meta( $entry['id'], 'wpf_complete' );

		if ( (int) $wpf_status !== 0 ) {
			return;
		}

		$integration = new WPF_GForms_Integration();

		$feeds = $integration->get_active_feeds( $entry['form_id'] );
		$form  = GFAPI::get_form( $entry['form_id'] );

		$email_address = $order->get_billing_email();
		if ( $email_address == '' ) {
			return;
		}

		$entry['wpf_order_email'] = $email_address;

		foreach ( $feeds as $feed ) {
			if ( $integration->is_feed_condition_met( $feed, $form, $entry ) ) {
				$feed['meta']['wpf_fields']['wpf_order_email'] = array(
					'crm_field' => 'email',
					'type'      => 'email',
				);
				$integration->process_feed( $feed, $entry, $form );
			}
		}
	}
}

new WPF_GS_Product_Configurator();
