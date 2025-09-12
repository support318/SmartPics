<?php

namespace Uncanny_Automator_Pro;

/**
 * Class THRIVE_APPRENTICE_USER_HAS_ACCESS_TO_PRODUCT
 *
 * @package Uncanny_Automator_Pro
 */
class THRIVE_APPRENTICE_USER_HAS_ACCESS_TO_PRODUCT extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'THRIVE_APPRENTICE';
		$this->name         = esc_html_x( 'The user {{has/does not have}} access to {{a Thrive Apprentice product}}', 'Thrive Apprentice', 'uncanny-automator-pro' );
		$this->code         = 'USER_HAS_ACCESS_TO_PRODUCT';
		$this->dynamic_name = sprintf(
			/* translators: %1$s: Criteria, %2$s: Product */
			esc_html_x( 'The user {{has/does not have:%1$s}} access to {{a Thrive Apprentice product:%2$s}}', 'Thrive Apprentice', 'uncanny-automator-pro' ),
			'CRITERIA',
			'PRODUCT'
		);
		$this->is_pro        = true;
		$this->requires_user = true;
		$this->deprecated    = false;

		// Register AJAX hooks
		$this->register_hooks();
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {

		return array(
			$this->field->select_field_args(
				array(
					'option_code'           => 'CRITERIA',
					'label'                 => esc_html_x( 'Criteria', 'Thrive Apprentice', 'uncanny-automator-pro' ),
					'required'              => true,
					'options'               => array(
						array(
							'text'  => esc_html_x( 'has', 'Thrive Apprentice', 'uncanny-automator-pro' ),
							'value' => 'has',
						),
						array(
							'text'  => esc_html_x( 'does not have', 'Thrive Apprentice', 'uncanny-automator-pro' ),
							'value' => 'does-not-have',
						),
					),
					'supports_custom_value' => false,
				)
			),
			$this->field->select_field_args(
				array(
					'option_code'           => 'PRODUCT',
					'label'                 => esc_html_x( 'Thrive Apprentice product', 'Thrive Apprentice', 'uncanny-automator-pro' ),
					'required'              => true,
					'options'               => array(),
					'ajax'                  => array(
						'endpoint' => 'retrieve_thrive_apprentice_products',
						'event'    => 'on_load',
					),
					'supports_custom_value' => false,
				)
			),
		);
	}

	/**
	 * Evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 */
	public function evaluate_condition() {

		$criteria   = $this->get_parsed_option( 'CRITERIA' );
		$product_id = intval( $this->get_parsed_option( 'PRODUCT' ) );

		if ( empty( $criteria ) || empty( $product_id ) ) {
			throw new \Exception( esc_html_x( 'Invalid criteria or product ID', 'Thrive Apprentice', 'uncanny-automator-pro' ) );
		}

		// Check if Thrive Apprentice is active
		if ( ! $this->is_dependency_active() ) {
			throw new \Exception( esc_html_x( 'Thrive Apprentice is not active', 'Thrive Apprentice', 'uncanny-automator-pro' ) );
		}

		// Get the product object using Thrive's method
		if ( ! class_exists( '\TVA\Product' ) ) {
			throw new \Exception( esc_html_x( 'Thrive Apprentice Product class not found', 'Thrive Apprentice', 'uncanny-automator-pro' ) );
		}

		/** @var \TVA\Product $product */
		$product = new \TVA\Product( $product_id );

		if ( ! $product->get_id() ) {
			throw new \Exception( esc_html_x( 'Product not found', 'Thrive Apprentice', 'uncanny-automator-pro' ) );
		}

		// Check if user has access using Thrive's built-in method
		$users_with_access = $product->get_users_with_access();
		$has_access        = in_array( $this->user_id, $users_with_access, true );
		$condition_met     = false;

		if ( 'has' === $criteria ) {
			$condition_met = $has_access;
		} elseif ( 'does-not-have' === $criteria ) {
			$condition_met = ! $has_access;
		}

		// If the condition is not met, send an error message and mark the condition as failed.
		if ( false === $condition_met ) {
			$message = $this->generate_error_message();
			$this->condition_failed( $message );
		}

		// If the condition is met, do nothing.
	}

	/**
	 * Check if Thrive Apprentice dependencies are active
	 *
	 * @return bool
	 */
	protected function is_dependency_active() {
		return class_exists( '\TVA_Const', false );
	}

	/**
	 * Register AJAX hooks
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'wp_ajax_retrieve_thrive_apprentice_products', array( $this, 'retrieve_thrive_apprentice_products_handler' ) );
	}

	/**
	 * AJAX handler to retrieve Thrive Apprentice products
	 *
	 * @return void
	 */
	public function retrieve_thrive_apprentice_products_handler() {
		Automator()->utilities->verify_nonce();

		$options = array();

		if ( ! $this->is_dependency_active() ) {
			wp_send_json(
				array(
					'success' => false,
					'options' => $options,
				)
			);
			return;
		}

		// Use direct taxonomy query like other Thrive methods
		$products = get_terms(
			array(
				'taxonomy'   => 'tva_product',
				'hide_empty' => false,
			)
		);

		if ( ! empty( $products ) && ! is_wp_error( $products ) ) {
			foreach ( $products as $product ) {
				if ( $product instanceof \WP_Term ) {
					$options[] = array(
						'text'  => esc_attr( $product->name ),
						'value' => esc_attr( $product->term_id ),
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
	}

	/**
	 * Generate_error_message
	 *
	 * @return string
	 */
	public function generate_error_message() {

		$criteria = $this->get_option( 'CRITERIA_readable' );
		$product  = $this->get_option( 'PRODUCT_readable' );

		if ( 'has' === $this->get_parsed_option( 'CRITERIA' ) ) {
			// translators: %s: Product name
			return sprintf( esc_html_x( "User doesn't have access to: %s", 'Thrive Apprentice', 'uncanny-automator-pro' ), $product );
		} else {
			// translators: %s: Product name
			return sprintf( esc_html_x( "User has access to: %s", 'Thrive Apprentice', 'uncanny-automator-pro' ), $product );
		}
	}
}
