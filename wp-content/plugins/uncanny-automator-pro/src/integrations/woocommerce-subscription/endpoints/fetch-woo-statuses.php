<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints;

/**
 * Class Fetch_Woo_Statuses
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints
 */
class Fetch_Woo_Statuses {

	/**
	 * Endpoint handler
	 *
	 * @return void
	 */
	public function handle() {

		// Capability check is handled as well.
		Automator()->utilities->verify_nonce(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		try {
			$this->get_subscription_statuses();
		} catch ( \Exception $e ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Get all subscription statuses
	 *
	 * @return void
	 */
	private function get_subscription_statuses() {
		// Check if WooCommerce Subscriptions is active
		if ( ! class_exists( '\WC_Subscriptions' ) ) {
			throw new \Exception( esc_html_x( 'WooCommerce Subscriptions is not active', 'WooCommerce Subscription', 'uncanny-automator-pro' ) );
		}

		$options = array();

		// Add "Any" option
		$options[] = array(
			'text'  => esc_html_x( 'Any status', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'value' => -1,
		);

		// Get subscription statuses if the function exists
		if ( function_exists( 'wcs_get_subscription_statuses' ) ) {
			$wc_statuses = wcs_get_subscription_statuses();

			foreach ( $wc_statuses as $status_key => $status_name ) {
				$options[] = array(
					'text'  => $status_name,
					'value' => $status_key,
				);
			}
		} else {
			// Fallback to common subscription statuses if the function doesn't exist
			$statuses = array(
				'wc-active'    => esc_html_x( 'Active', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'wc-cancelled' => esc_html_x( 'Cancelled', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'wc-expired'   => esc_html_x( 'Expired', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'wc-on-hold'   => esc_html_x( 'On hold', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'wc-pending'   => esc_html_x( 'Pending', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			);

			foreach ( $statuses as $status_key => $status_name ) {
				$options[] = array(
					'text'  => $status_name,
					'value' => $status_key,
				);
			}
		}

		wp_send_json(
			array(
				'success' => true,
				'options' => $options,
			)
		);
	}
}
