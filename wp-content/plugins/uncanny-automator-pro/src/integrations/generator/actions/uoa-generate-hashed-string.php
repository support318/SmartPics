<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Recipe\Log_Properties;

/**
 * Class UOA_GENERATE_HASHED_STRING
 *
 * @package Uncanny_Automator_Pro
 */
class UOA_GENERATE_HASHED_STRING {

	use Recipe\Actions;
	use Recipe\Action_Tokens;
	use Log_Properties;

	/**
	 * UOA_GENERATE_HASHED_STRING constructor.
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
		$this->set_action_meta( 'GENERATE_HASH_STR' );
		$this->set_action_code( 'GENERATE_HASH_STR_META' );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_html__( 'Generate a {{hash:%1$s}}', 'uncanny-automator-pro' ), 'HASH_FUNCTION:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_html__( 'Generate a {{hash}}', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'HASHED_STRING' => array(
					'name' => _x( 'Hashed string', 'Uncanny Automator', 'uncanny-automator-pro' ),
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
		$hash_functions = hash_algos();
		$hash_options   = array();
		foreach ( $hash_functions as $function ) {
			$hash_options[] = array(
				'value' => $function,
				'text'  => strtoupper( $function ),
			);
		}

		return array(
			'options_group' => array(
				$this->get_action_meta() => array(
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => 'HASH_FUNCTION',
							'label'       => esc_html__( 'Hash function', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Select the hash function to use.', 'uncanny-automator-pro' ),
							'required'    => true,
							'options'     => $hash_options,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'STRING_TO_HASH',
							'label'       => esc_html__( 'String to hash', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Enter the string you want to hash.', 'uncanny-automator-pro' ),
							'required'    => true,
							'input_type'  => 'text',
						)
					),
				),
			),
		);
	}

	/**
	 * Process the action to generate a hashed string.
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
		$hash_function  = $parsed['HASH_FUNCTION'];
		$string_to_hash = $parsed['STRING_TO_HASH'];

		try {
			$hashed_string = hash( $hash_function, $string_to_hash );

			$this->hydrate_tokens(
				array(
					'HASHED_STRING' => $hashed_string,
				)
			);

			// Set log properties.
			$this->set_log_properties(
				array(
					'type'       => 'code',
					'label'      => __( 'Hashed string', 'uncanny-automator-pro' ),
					'value'      => $hashed_string,
					'attributes' => array(
						'code_language' => 'code',
					),
				)
			);

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

		} catch ( \Exception $e ) {
			// Log the error and possibly handle it further, such as marking the action as failed
			throw new \Exception( sprintf( __( 'Error generating hash: %s', 'uncanny-automator-pro' ), $e->getMessage() ) );
		}
	}
}
