<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator_Pro\Integrations\GravityForms\Utilities\Time_Handler;

/**
 * Class Uncanny_Automator_Pro\GF_SUBMIT_FORM
 *
 * @since 5.2
 * @package Uncanny_Automator_Pro
 *
 */
class GF_SUBMIT_FORM extends \Uncanny_Automator\Recipe\Action {

	/**
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'GF' );
		$this->set_action_code( 'GF_SUBMIT_FORM' );
		$this->set_action_meta( 'GFFORMS' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence(
			sprintf(
			/* translators: Action sentence */
				esc_attr_x(
					'Submit an entry for {{a form:%1$s}}',
					'Gravity Forms',
					'uncanny-automator'
				),
				$this->get_action_meta()
			)
		);

		// Sentence that appears in the trigger list drop down.
		$this->set_readable_sentence(
			esc_attr_x(
				'Submit an entry for {{a form}}',
				'Gravity Forms',
				'uncanny-automator'
			)
		);

		$this->set_buttons(
			array(
				array(
					'show_in'     => $this->get_action_meta(),
					'text'        => __( 'Get fields', 'uncanny-automator' ),
					'css_classes' => 'uap-btn uap-btn--red',
					'on_click'    => Gravity_Forms_Pro_Helpers::get_fields_js(),
					'modules'     => array( 'modal', 'markdown' ),
				),
			)
		);

		$this->set_action_tokens(
			array(
				'ENTRY_ID'  => array(
					'name' => __( 'Entry ID', 'uncanny-automator-pro' ),
				),
				'ENTRY_URL' => array(
					'name' => __( 'Entry URL', 'uncanny-automator-pro' ),
					'type' => 'url',
				),
			),
			$this->get_action_code()
		);

	}

	/**
	 * Options definitions.
	 *
	 * @return array
	 */
	public function options() {

		$forms_options = Automator()->helpers->recipe->field->select(
			array(
				'option_code' => $this->get_action_meta(),
				'label'       => __( 'Form', 'uncanny-automator-pro' ),
				'required'    => true,
				'options'     => Automator()->helpers->recipe->gravity_forms->options->get_forms_as_options( false ),
			)
		);

		$fields_repeater = array(
			'option_code'       => 'GF_FIELDS',
			'input_type'        => 'repeater',
			'relevant_tokens'   => array(),
			'label'             => __( 'Row', 'uncanny-automator' ),
			/* translators: 1. Button */
			'description'       => '',
			'required'          => true,
			'fields'            => array(
				array(
					'option_code' => 'GF_COLUMN_NAME',
					'label'       => __( 'Column', 'uncanny-automator' ),
					'input_type'  => 'text',
					'required'    => true,
					'read_only'   => true,
					'options'     => array(),
				),
				Automator()->helpers->recipe->field->text_field( 'GF_COLUMN_VALUE', __( 'Value', 'uncanny-automator' ), true, 'text', '', false ),
			),
			'add_row_button'    => __( 'Add pair', 'uncanny-automator' ),
			'remove_row_button' => __( 'Remove pair', 'uncanny-automator' ),
			'hide_actions'      => true,
		);

		return array( $forms_options, $fields_repeater );
	}

	/**
	 * Processes the action.
	 *
	 * @param int $user_id The user ID. Use this argument to passed the User ID instead of get_current_user_id().
	 * @param mixed[] $action_data The action data.
	 * @param int $recipe_id The recipe ID.
	 * @param mixed[] $args The args.
	 * @param mixed[] $parsed The parsed variables.
	 *
	 * @return bool True if the action is successful. Returns false, otherwise.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		if ( ! class_exists( '\GFAPI' ) ) {
			throw new \Exception( _x( 'Gravity forms is not installed or activated.', 'Gravity Forms', 'uncanny-automator' ) );
		}

		$form_id = isset( $parsed[ $this->get_action_meta() ] )
			? absint( $parsed[ $this->get_action_meta() ] ) :
			null;

		$field_values = json_decode( $action_data['meta']['GF_FIELDS'] );

		$input_values = Gravity_Forms_Pro_Helpers::format_input_values( $field_values, $recipe_id, $user_id, $args, $prefix = 'input_' );

		// Format time fields if present.
		$time_handler = new Time_Handler();
		$input_values = $time_handler->format_time_fields( $input_values, $form_id );

		$response = \GFAPI::submit_form( $form_id, $input_values );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		if ( ! $response['is_valid'] ) {
			$errors = implode( ', ', $response['validation_messages'] );
			throw new \Exception( _x( 'Form validation errors', 'Gravity Forms', 'uncanny-automator' ) . ': ' . $errors );
		}

		$this->hydrate_tokens(
			array(
				'ENTRY_ID'  => $response['entry_id'],
				'ENTRY_URL' => Gravity_Forms_Pro_Helpers::get_entry_url( $response['entry_id'], $form_id ),
			)
		);

		return true;
	}
}
