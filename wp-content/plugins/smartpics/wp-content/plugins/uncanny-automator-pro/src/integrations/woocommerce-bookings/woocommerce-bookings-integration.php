<?php

namespace Uncanny_Automator_Pro\Integrations\WooCommerce_Bookings;

/**
 * Class Woocommerce_Bookings_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Woocommerce_Bookings_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {
		if ( ! class_exists( '\Uncanny_Automator\Integrations\WooCommerce_Bookings\Wc_Bookings_Helpers' ) ) {
			return;
		}
		$this->helpers = new Wc_Bookings_Helpers_Pro();
		$this->set_integration( 'WC_BOOKINGS' );
		$this->set_name( 'Woo Bookings' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/woo-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {
		// Load triggers
		new WC_BOOKINGS_BOOKING_STATUS_CHANGED( $this->helpers );
		new WC_BOOKINGS_ANON_BOOKING_UPDATED( $this->helpers );

		// Load actions
		new WC_BOOKINGS_CHANGE_BOOKING_STATUS( $this->helpers );
		new WC_BOOKINGS_CREATE_A_BOOKING( $this->helpers );

		add_action( 'wp_ajax_select_all_product_resources', array( $this->helpers, 'select_all_product_resources' ) );
	}

	/**
	 * Check if WooCommerce and WooCommerce Bookings Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'WooCommerce' ) && class_exists( 'WC_Bookings' );
	}
}
