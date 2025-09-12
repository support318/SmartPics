<?php

namespace Uncanny_Automator_Pro;

use GFAPI;
use Uncanny_Automator\Recipe\Action;

/**
 * Class GF_DELETE_ALL_ENTRIES_FOR_SPECIFIC_USER
 *
 * @pacakge Uncanny_Automator_Pro
 */
class GF_DELETE_ALL_ENTRIES_FOR_SPECIFIC_USER extends Action {

	/**
	 * @return mixed
	 */
	protected function setup_action() {
		$this->set_integration( 'GF' );
		$this->set_action_code( 'GF_DELETE_ALL_ENTRIES' );
		$this->set_action_meta( 'GF_FORM' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );
		/* translators: Action sentence */
		$this->set_sentence( sprintf( esc_attr_x( 'Delete all {{form:%1$s}} entries for {{a specific user:%2$s}}', 'Gravity Forms', 'uncanny-automator-pro' ), $this->get_action_meta(), 'SPECIFIC_USER:' . $this->get_action_meta() ) );
		// Sentence that appears in the trigger list drop down.
		$this->set_readable_sentence( esc_attr_x( 'Delete all {{form}} entries for {{a specific user}}', 'Gravity Forms', 'uncanny-automator-pro' ) );
	}

	/**
	 * Options definitions.
	 *
	 * @return array
	 */
	public function options() {
		$options = array();
		$forms   = Automator()->helpers->recipe->gravity_forms->options->list_gravity_forms();
		foreach ( $forms['options'] as $k => $form ) {
			$options[] = array(
				'value' => $k,
				'text'  => $form,
			);
		}

		return array(
			array(
				'option_code'     => $this->get_action_meta(),
				'label'           => __( 'Form', 'uncanny-automator-pro' ),
				'options'         => $options,
				'relevant_tokens' => array(),
				'input_type'      => 'select',
				'required'        => true,
			),
			array(
				'input_type'      => 'text',
				'option_code'     => 'SPECIFIC_USER',
				'label'           => _x( 'User', 'Gravity Forms', 'uncanny-automator-pro' ),
				'description'     => __( 'Accepts User ID, email or username', 'uncanny-automator-pro' ),
				'relevant_tokens' => array(),
				'required'        => true,
			),
		);
	}

	/**
	 * Processes the action.
	 *
	 * @param int $user_id The user ID. Use this argument to passed the User ID instead of
	 *                             get_current_user_id().
	 * @param mixed[] $action_data The action data.
	 * @param int $recipe_id The recipe ID.
	 * @param mixed[] $args The args.
	 * @param mixed[] $parsed The parsed variables.
	 *
	 * @return bool True if the action is successful. Returns false, otherwise.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		if ( ! class_exists( '\GFAPI' ) ) {
			$this->add_log_error( 'Gravity Forms is not installed or activated.' );

			return false;
		}

		$user_info = isset( $parsed['SPECIFIC_USER'] ) ? sanitize_text_field( wp_strip_all_tags( $parsed['SPECIFIC_USER'] ) ) : 0;
		$user_id   = $this->get_user( $user_info );

		$form_id = isset( $parsed[ $this->get_action_meta() ] ) ? absint( $parsed[ $this->get_action_meta() ] ) : 0;
		// Validate if entry exists!
		$form = GFAPI::get_form( $form_id );
		if ( is_wp_error( $form ) ) {
			$this->add_log_error( $form->get_error_message() );

			return false;
		}

		// Set up search criteria to filter by user ID & from ID
		$search_criteria = array(
			'field_filters' => array(
				array(
					'key'   => 'created_by',
					'value' => $user_id,
				),
			),
		);

		// Retrieve the entries
		$entries = GFAPI::get_entries( $form_id, $search_criteria );

		if ( empty( $entries ) ) {
			$this->add_log_error( sprintf( 'No entries found for the specified user (%s).', $user_info ) );

			return false;
		}

		// Check for errors
		if ( is_wp_error( $entries ) ) {
			$this->add_log_error( $entries->get_error_message() );

			return false;
		}

		$is_deleted = 0;
		// Delete each entry
		foreach ( $entries as $entry ) {
			$delete_entry = GFAPI::delete_entry( $entry['id'] );
			if ( ! is_wp_error( $delete_entry ) ) {
				$is_deleted ++;
			}
		}

		if ( count( $entries ) !== $is_deleted ) {
			$this->add_log_error( sprintf( '%s out of %s entries are deleted.', $is_deleted, count( $entries ) ) );
		}

		return true;
	}

	/**
	 * @param $user_info
	 *
	 * @return false|\WP_User
	 */
	public function get_user( $user_info ) {
		if ( is_numeric( $user_info ) ) {
			return absint( $user_info );
		}

		$maybe_user_id = email_exists( $user_info );
		if ( false !== $maybe_user_id ) {
			return absint( $maybe_user_id );
		}

		$maybe_user_id = username_exists( $user_info );
		if ( false !== $maybe_user_id ) {
			return absint( $maybe_user_id );
		}

		return absint( $user_info );
	}
}
