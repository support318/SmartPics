<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Recipe\Log_Properties;

/**
 * Class UOA_GENERATE_WP_NONCE
 *
 * @package Uncanny_Automator_Pro
 */
class UOA_GENERATE_WP_NONCE {

	use Recipe\Actions;
	use Recipe\Action_Tokens;
	use Log_Properties;

	/**
	 * UOA_GENERATE_WP_NONCE constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Setup the action's details.
	 */
	protected function setup_action() {
		$this->set_integration( 'AUTOMATOR_GENERATOR' );
		$this->set_is_pro( true );
		$this->set_action_meta( 'GENERATE_WP_NONCE_META' );
		$this->set_action_code( 'GENERATE_WP_NONCE' );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_html__( 'Generate a {{nonce:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_html__( 'Generate a {{nonce}}', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'GENERATED_WP_NONCE' => array(
					'name' => esc_html__( 'Generated nonce', 'uncanny-automator-pro' ),
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
			'options' => array(
				Automator()->helpers->recipe->field->text(
					array(
						'option_code' => $this->get_action_meta(),
						'label'       => esc_html__( 'Nonce action name', 'uncanny-automator-pro' ),
						'description' => '',
						'required'    => true,
						'input_type'  => 'text',
					)
				),
			),
		);
	}


	/**
	 * Process the action to generate a WordPress nonce.
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
		$nonce_action = sanitize_text_field( $parsed[ $this->get_action_meta() ] );

		try {
			$nonce = wp_create_nonce( $nonce_action );

			$this->hydrate_tokens(
				array(
					'GENERATED_WP_NONCE' => $nonce,
				)
			);

			$this->set_log_properties(
				array(
					'type'  => 'code',
					'label' => __( 'Nonce', 'uncanny-automator-pro' ),
					'value' => $nonce,
				)
			);

			Automator()->complete->action( $user_id, $action_data, $recipe_id );
		} catch ( \Exception $e ) {
			throw new \Exception( sprintf( __( 'Error generating nonce: %s', 'uncanny-automator-pro' ), $e->getMessage() ) );
		}
	}
}
