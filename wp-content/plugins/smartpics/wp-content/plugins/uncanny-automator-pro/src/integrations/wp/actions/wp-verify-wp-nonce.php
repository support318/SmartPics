<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class RUN_CODE_VERIFY_WP_NONCE
 *
 * @package Uncanny_Automator_Pro
 */
class WP_VERIFY_WP_NONCE {

	use Recipe\Actions;
	use Recipe\Action_Tokens;
	use Recipe\Log_Properties;

	/**
	 * RUN_CODE_VERIFY_WP_NONCE constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Setup the action's details.
	 */
	protected function setup_action() {
		$this->set_integration( 'WP' );
		$this->set_is_pro( true );
		$this->set_action_code( 'VERIFY_WP_NONCE' );
		$this->set_action_meta( 'VERIFY_WP_NONCE_META' );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_html__( 'Verify a {{nonce:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_html__( 'Verify a {{nonce}}', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'VERIFICATION_RESULT' => array(
					'name' => esc_html__( 'Nonce verification result', 'uncanny-automator-pro' ),
					'type' => 'text',
				),
			),
			$this->get_action_code()
		);
		$this->register_action();
	}

	/**
	 * Define the options for this action.
	 *
	 * @return array
	 */
	public function load_options() {
		return array(
			'options_group' => array(
				$this->get_action_meta() => array(
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'NONCE_TO_VERIFY',
							'label'       => esc_html__( 'Nonce to verify', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Enter the nonce you wish to verify.', 'uncanny-automator-pro' ),
							'required'    => true,
							'input_type'  => 'text',
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'NONCE_ACTION_NAME',
							'label'       => esc_html__( 'Nonce action name', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Enter the action name associated with the nonce.', 'uncanny-automator-pro' ),
							'required'    => true,
							'input_type'  => 'text',
						)
					),
				),
			),
		);
	}

	/**
	 * Process the action to verify a WordPress nonce.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param array $parsed
	 *
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$nonce_to_verify     = sanitize_text_field( $parsed['NONCE_TO_VERIFY'] );
		$nonce_action_name   = sanitize_text_field( $parsed['NONCE_ACTION_NAME'] );
		$verification_result = 'failed'; // Default to failed
		$error_msg           = '';
		try {
			// Attempt to verify the nonce
			if ( wp_verify_nonce( $nonce_to_verify, $nonce_action_name ) ) {
				// Nonce verification successful
				$verification_result = 'success';
			}

			// Set a token with the result of the nonce verification
			$this->hydrate_tokens(
				array(
					'VERIFICATION_RESULT' => $verification_result,
				)
			);

			// Optionally, set log properties based on the verification result
			$this->set_log_properties(
				array(
					'type'  => 'text',
					'label' => __( 'Nonce verification', 'uncanny-automator-pro' ),
					'value' => 'success' === $verification_result ? __( 'Successful', 'uncanny-automator-pro' ) : __( 'Failed', 'uncanny-automator-pro' ),
				)
			);

			if ( 'failed' === $verification_result ) {
				$action_data['complete_with_notice'] = true;
				$error_msg                           = __( 'Nonce verification failed.', 'uncanny-automator-pro' );
			}

			// Complete the action with the result
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_msg );
		} catch ( \Exception $e ) {
			throw new \Exception( sprintf( __( 'Error verifying nonce: %s', 'uncanny-automator-pro' ), $e->getMessage() ) );
		}
	}
}
