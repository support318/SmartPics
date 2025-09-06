<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Uncanny_Automator_Pro\GF_DELETE_ENTRY
 *
 * @since 5.2
 * @package Uncanny_Automator_Pro
 *
 */
class GF_DELETE_ENTRY extends \Uncanny_Automator\Recipe\Action {

	/**
	 * Setups the action basic properties like Integration, Sentence, etc.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'GF' );
		$this->set_action_code( 'GF_DELETE_ENTRY' );
		$this->set_action_meta( 'GF_DELETE_ENTRY_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence(
			sprintf(
			/* translators: Action sentence */
				esc_attr_x(
					'Delete the entry that matches {{an entry ID:%1$s}}',
					'Gravity Forms',
					'uncanny-automator'
				),
				$this->get_action_meta()
			)
		);

		// Sentence that appears in the trigger list drop down.
		$this->set_readable_sentence(
			esc_attr_x(
				'Delete the entry that matches {{an entry ID}}',
				'Gravity Forms',
				'uncanny-automator'
			)
		);

	}

	/**
	 * Options definitions.
	 *
	 * @return array
	 */
	public function options() {

		// The "Redirect title" field.
		$field_entry_id = array(
			'input_type'  => 'text',
			'option_code' => $this->get_action_meta(),
			'label'       => _x( 'Entry ID', 'Gravity Forms', 'uncanny-automator' ),
			'required'    => true,
		);

		return array( $field_entry_id );

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

		$entry_id = isset( $parsed[ $this->get_action_meta() ] )
			? absint( $parsed[ $this->get_action_meta() ] ) :
			null;

		if ( ! class_exists( '\GFAPI' ) ) {
			$this->add_log_error( 'Gravity forms is not installed or activated.' );

			return false;
		}

		// Validate if entry exists!
		$entry = \GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			$this->add_log_error( $entry->get_error_message() );

			return false;
		}

		$result = \GFAPI::delete_entry( $entry_id );

		if ( is_wp_error( $result ) ) {
			$this->add_log_error( $result->get_error_message() );

			return false;
		}

		return true;

	}

}
