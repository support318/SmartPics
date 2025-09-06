<?php

namespace Uncanny_Automator_Pro;

/**
 * Class RUN_CODE_CALL_FUNCTION_EVERYONE
 */
class RUN_CODE_CALL_FUNCTION_EVERYONE {
	use \Uncanny_Automator\Recipe\Actions;
	use Recipe\Action_Tokens;

	/**
	 * UOA_CALL_FUNCTION_EVERYONE constructor.
	 *
	 * @return void.
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'migrate_existing_actions_to_new' ), 999 );

		$this->wpautop = false;
		$this->setup_action();
	}

	/**
	 * Set-ups our action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'RUN_CODE' );
		$this->set_is_pro( true );
		$this->set_wpautop( false );
		$this->set_requires_user( false );
		$this->set_action_meta( 'UOA_CALL_FUNC_EVERYONE_META' );
		$this->set_action_code( 'UOA_CALL_FUNC_EVERYONE_CODE' );
		/* translators: Action - WordPress */
		$this->set_sentence( sprintf( esc_attr__( 'Call {{a custom function/method:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Call {{a custom function/method}}', 'uncanny-automator-pro' ) );
		$options_group = array(
			$this->get_action_meta() => array(
				array(
					'input_type'      => 'text',
					'option_code'     => $this->get_action_meta(),
					'required'        => true,
					'supports_tokens' => false,
					'label'           => esc_attr__( 'Function name', 'uncanny-automator-pro' ),
					'description'     => esc_attr__( 'The function must be available or registered before this Automator action. Pass the arguments by value in the "Pass variables" field below.', 'uncanny-automator-pro' ),
					'placeholder'     => esc_attr__( 'my_custom_function', 'uncanny-automator-pro' ),
				),
				array(
					'input_type'        => 'repeater',
					'relevant_tokens'   => array(),
					'option_code'       => 'FUNCTION_ARGS',
					'label'             => esc_attr__( 'Pass variables', 'uncanny-automator-pro' ),
					'description'       => __( '<strong>Arrays</strong> and <strong>Objects</strong> are not <strong>supported</strong> and will be treated as strings. Variables will be passed to the function in this exact order. Variables like <strong>null</strong>, <strong>[]</strong> and <strong>array()</strong> will be passed as null and empty arrays.', 'uncanny-automator' ),
					'required'          => false,
					'fields'            => array(
						array(
							'input_type'      => 'text',
							'option_code'     => 'VALUE',
							'label'           => esc_attr__( 'Value', 'uncanny-automator' ),
							'supports_tokens' => true,
							'required'        => false,
						),
					),
					/* translators: Non-personal infinitive verb */
					'add_row_button'    => esc_attr__( 'Add a variable', 'uncanny-automator' ),
					/* translators: Non-personal infinitive verb */
					'remove_row_button' => esc_attr__( 'Remove a variable', 'uncanny-automator' ),
				),
			),
		);

		$this->set_options_group( $options_group );
		$this->set_action_tokens(
			array(
				'RETURN_VALUE' => array(
					'name' => __( 'Function return value', 'uncanny-automator-pro' ),
					'type' => 'text',
				),
			),
			$this->action_code
		);
		$this->register_action();
	}


	/**
	 * Process our action.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$function_name = isset( $parsed[ $this->get_action_meta() ] ) ? $parsed[ $this->get_action_meta() ] : '';

		// Check if the function exists.
		if ( function_exists( $function_name ) ) {

			try {

				$function_args = $this->hydrate_field_values( $action_data, $user_id, $recipe_id, $args );

				// The parameters to be passed to the callback, as an indexed array.
				$callback_args = array();

				foreach ( $function_args as $function_arg ) {
					$callback_args[] = $this->parse( $function_arg );
				}

				// Run the function.
				$return_value = call_user_func_array( $function_name, $callback_args );
				// Give some filters.
				$return_value = apply_filters( 'automator_pro_call_a_custom_function_return_value', $return_value, $this );
				// Send the tokens.
				$this->hydrate_tokens(
					array(
						'RETURN_VALUE' => $return_value,
					)
				);

				Automator()->complete->action( $user_id, $action_data, $recipe_id );

			} catch ( \Exception $e ) {

				$action_data['complete_with_errors'] = true;
				Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getMessage() );

			}
		} else {

			// Log the error if the function does not exist.
			$action_data['complete_with_errors'] = true;

			$error = sprintf(
			/* translators: Function is not defined error message. */
				esc_html__(
					'The function/method (%s) you are trying to call is not found or not yet registered.',
					'uncanny-automator-pro'
				),
				$function_name
			);

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error );

		}

	}

	/**
	 * Parse the value.
	 *
	 * This function will replace null and empty arrays into real values.
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function parse( $value ) {
		$output = null;
		switch ( $value ) {
			case 'null':
				break;
			case 'array()':
			case '[]':
				$output = array();
				break;
			default:
				$output = $value;
				break;
		}
		return apply_filters( 'automator_do_action_parse_vars', $output, $value );
	}

	/**
	 * Manually hydrate the field values.
	 *
	 * @param array $action_data
	 * @param int $user_id
	 * @param int $recipe_id
	 * @param array $args
	 *
	 * @return array The list of the hydrated field's value.
	 */
	protected function hydrate_field_values( $action_data = array(), $user_id = 0, $recipe_id = null, $args = array() ) {

		// Retrieve the raw JSON-formatted field value.
		$repeater_fields_raw = json_decode( $action_data['meta']['FUNCTION_ARGS'], true );

		if ( null === $repeater_fields_raw ) {
			throw new \Exception(
				'An error has occured while parsing the repeater field: ' . esc_html( $action_data['meta']['FUNCTION_ARGS'] ),
				422
			);
		}

		// Individually parse the repeater fields value instead of inside the JSON.
		return array_map(
			function( $field_raw ) use ( $recipe_id, $user_id, $args ) {
				return Automator()->parse->text( $field_raw['VALUE'], $recipe_id, $user_id, $args );
			},
			$repeater_fields_raw
		);

	}

	/**
	 * Update the post meta from 'UOA_CALL_FUNC_CODE' to 'UOA_CALL_FUNC_EVERYONE_CODE'.
	 *
	 * @return void
	 */
	public function migrate_existing_actions_to_new() {

		$option_key = 'automator_uoa_call_function_everyone_migrate';

		if ( 'yes' === automator_pro_get_option( $option_key, 'no' ) ) {
			return;
		}

		global $wpdb;

		$existing_actions = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				AND meta_value = %s",
				'code',
				'UOA_CALL_FUNC_CODE'
			)
		);

		if ( empty( $existing_actions ) ) {
			automator_pro_update_option( $option_key, 'yes' );

			return;
		}

		foreach ( $existing_actions as $action_id ) {
			update_post_meta( $action_id, 'code', 'UOA_CALL_FUNC_EVERYONE_CODE' );
		}

		automator_pro_update_option( $option_key, 'yes' );

	}
}
