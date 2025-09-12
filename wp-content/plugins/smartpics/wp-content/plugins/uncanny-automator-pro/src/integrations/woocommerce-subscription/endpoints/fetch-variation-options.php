<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints;

/**
 * Class Fetch_Variation_Options
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Endpoints
 */
class Fetch_Variation_Options {

	/**
	 * Get the initial options for the variation dropdown
	 *
	 * @param string $field_id
	 *
	 * @return array
	 */
	protected function get_initial_options( $field_id ) {

		return array(
			array(
				'text'  => esc_html_x( 'Any variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => -1,
			),
		);
	}

	/**
	 * Endpoint handler
	 *
	 * @return void
	 */
	public function handle() {

		// Capability check is handled as well.
		Automator()->utilities->verify_nonce(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		try {

			$options = $this->get_initial_options( automator_filter_input( 'field_id', INPUT_POST ) );

			$variable_subscription = $this->resolve_from_request();

			if ( ! empty( $variable_subscription ) ) {
				$args = array(
					'post_type'      => 'product_variation',
					'post_parent'    => $variable_subscription,
					'posts_per_page' => -1,
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'post_status'    => 'publish',
				);

				$variations = get_posts( $args );

				if ( ! empty( $variations ) ) {
					foreach ( $variations as $variation ) {
						$options[] = array(
							'value' => $variation->ID,
							'text'  => ! empty( $variation->post_excerpt ) ? $variation->post_excerpt : $variation->post_title,
						);
					}
				}
			}

			wp_send_json(
				array(
					'success' => true,
					'options' => $options,
				)
			);

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
	 * Resolve the variable subscription from the request.
	 *
	 * Backwards compatibity adapter.
	 *
	 * @return int
	 */
	private function resolve_from_request() {

		$values = automator_filter_input_array( 'values', INPUT_POST );

		$from_common_trigger         = intval( $values['WOOSUBSCRIPTIONS'] ?? 0 );
		$from_woo_vari_product       = intval( $values['WOOVARIPRODUCT'] ?? 0 );
		$from_variation_action       = intval( $values['WOOVARIATIONSUBS'] ?? 0 );
		$from_variable_action        = intval( $values['WCS_PRODUCTS'] ?? 0 );
		$from_extend_variable_action = intval( $values['WC_EXTENDUSERVARIATIONSUBSCRIPTION_META'] ?? 0 );

		// For backwards compatibility (WOOVARIPRODUCT).
		if ( ! empty( $from_woo_vari_product ) ) {
			// Use the common trigger if it exists.
			if ( ! empty( $from_common_trigger ) ) {
				return $from_common_trigger;
			}

			if ( ! empty( $from_variation_action ) ) {
				return $from_variation_action;
			}

			return $from_woo_vari_product;
		}

		// For backwards compatibility (WC_EXTENDUSERVARIATIONSUBSCRIPTION_META).
		if ( ! empty( $from_extend_variable_action ) ) {
			return $from_extend_variable_action;
		}

		// For backwards compatibility (WCS_PRODUCTS).
		if ( ! empty( $from_variable_action ) ) {
			return $from_variable_action;
		}

		// For backwards compatibility (WOOVARIATIONSUBS).
		if ( ! empty( $from_variation_action ) ) {
			return $from_variation_action;
		}

		// If the variation is selected from the Woo variation trigger, return the variation ID.
		if ( empty( $from_common_trigger ) && ! empty( $from_woo_vari_product ) ) {

			// This is convoluted, but we have to preserve the values from action codes.
			$from_variation_action = intval( $values['WOOVARIATIONSUBS'] ?? 0 );

			if ( ! empty( $from_variation_action ) ) {
				return $from_variation_action;
			}

			return $from_woo_vari_product;
		}

		return $from_common_trigger;
	}
}
