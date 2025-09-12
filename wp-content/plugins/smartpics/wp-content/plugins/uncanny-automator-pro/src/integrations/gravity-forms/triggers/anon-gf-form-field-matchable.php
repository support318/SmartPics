<?php
namespace Uncanny_Automator_Pro;

class ANON_GF_FORM_FIELD_MATCHABLE {

	use \Uncanny_Automator\Recipe\Triggers;

	const TRIGGER_CODE = 'ANON_GF_FORM_FIELD_MATCHABLE';

	const TRIGGER_META = 'ANON_GF_FORM_FIELD_MATCHABLE_META';

	public function __construct() {

		$this->setup_trigger();

	}

	/**
	 * Continue trigger process even for logged-in user.
	 *
	 * @return boolean True, always.
	 */
	public function do_continue_anon_trigger( ...$args ) {

		return true;

	}

	public function setup_trigger() {

		$this->set_integration( 'GF' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_is_pro( true );

		$this->set_is_login_required( false );

		$this->set_trigger_type( 'anonymous' );

		// The action hook to attach this trigger into.
		$this->add_action( 'gform_after_update_entry' );

		// The number of arguments that the action hook accepts.
		$this->set_action_args_count( 3 );

		$this->set_sentence(
			sprintf(
				/* Translators: Trigger sentence */
				esc_html__( '{{A specific field:%1$s}} in an entry for {{a form:%2$s}} is updated to {{a specific value:%3$s}}', 'uncanny-automator-pro' ),
				'FIELD:' . $this->get_trigger_meta(),
				$this->get_trigger_meta(), // FORM.
				'VALUE:' . $this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			/* Translators: Trigger sentence */
			esc_html__( '{{A specific field}} in an entry for {{a form}} is updated to {{a specific value}}', 'uncanny-automator-pro' )
		);

		// Set the options field group.
		$this->set_options_group(
			array(
				$this->get_trigger_meta() => $this->get_fields(),
			)
		);

		// Set new tokens.
		if ( class_exists( '\Uncanny_Automator\GF_COMMON_TOKENS' ) ) {

			$this->set_tokens( \Uncanny_Automator\GF_COMMON_TOKENS::get_common_tokens() );

		}

		// Register the trigger.
		$this->register_trigger();

	}

	/**
	 * Retrieves the fields to be used as option fields in the dropdown.
	 *
	 * @return array The dropdown option fields.
	 */
	private function get_fields() {

		$helper = new Gravity_Forms_Pro_Helpers();

		return array(
			array(
				'option_code'     => $this->get_trigger_meta(),
				'label'           => esc_attr__( 'Form', 'uncanny-automator-pro' ),
				'input_type'      => 'select',
				'required'        => true,
				'is_ajax'         => true,
				'endpoint'        => 'retrieve_fields_from_form_id',
				'fill_values_in'  => 'FIELD',
				'options'         => $helper->get_forms_as_option_fields(),
				'relevant_tokens' => array(),
			),
			array(
				'option_code' => 'FIELD',
				'label'       => esc_attr__( 'Field', 'uncanny-automator-pro' ),
				'input_type'  => 'select',
				'required'    => true,
			),
			array(
				'option_code' => 'VALUE',
				'label'       => esc_attr__( 'Value', 'uncanny-automator-pro' ),
				'input_type'  => 'text',
				'required'    => true,
			),
		);

	}

	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	/**
	 * Validates the trigger before processing.
	 *
	 * @return boolean False or true.
	 */
	public function validate_trigger( ...$args ) {

		list( $form, $entry_id ) = end( $args );

		return ! empty( $form ) && ! empty( $entry_id );

	}

	/**
	 * Validate conditions.
	 *
	 * @return array The matching recipes and triggers.
	 */
	protected function validate_conditions( ...$args ) {

		list( $form, $entry_id, $previous_values ) = end( $args );

		$recipes = $this->trigger_recipes();

		$matching_field = $this->get_entry_matching_field( $recipes, $entry_id, $previous_values );

		$matching_recipes_triggers = $this->find_all( $recipes )
			->where( array( $this->get_trigger_meta(), 'FIELD', 'VALUE' ) )
			->match( array( $form['id'], $matching_field['id'], $matching_field['value'] ) )
			->format( array( 'intval', 'trim', 'trim' ) )
			->get();

		return $matching_recipes_triggers;

	}

	/**
	 * Retrieves the matching field from entry compare to the one set in trigger option.
	 *
	 * @return array The matching fields with `id` and `value.
	 */
	private function get_entry_matching_field( $recipes, $entry_id, $previous_values ) {

		$matching_field_id = 0;

		$matching_field_value = null;

		if ( ! class_exists( '\GFAPI' ) ) {

			return array(
				'id'    => null,
				'value' => null,
			);

		}

		$updated_fields = $this->get_updated_fields( \GFAPI::get_entry( $entry_id ), $previous_values );

		$field_id = array_values( (array) current( Automator()->get->meta_from_recipes( $recipes, 'FIELD' ) ) );

		// Normalize field id.
		$field_id = end( $field_id ); // With string data type.

		if ( ! empty( $field_id ) ) {

			$matching_field_value = isset( $updated_fields[ $field_id ] ) ? $updated_fields[ $field_id ] : null;

			$matching_field_id = array_search( $matching_field_value, $updated_fields, true );

		}

		return array(
			'id'    => $matching_field_id,
			'value' => $matching_field_value,
		);

	}

	/**
	 * Retrieve the specific field that was updated.
	 *
	 * @return array The field that was updated.
	 */
	private function get_updated_fields( $current_fields_values, $previous_fields_values ) {

		$difference = array_diff_assoc(
			$current_fields_values,
			$previous_fields_values
		);

		return array_filter(
			$difference,
			function( $key ) {
				return 'date_updated' !== $key; // Exclude the date_updated key.
			},
			ARRAY_FILTER_USE_KEY
		);

	}

	/**
	 * Method parse_additional_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {

		if ( class_exists( '\Uncanny_Automator\GF_COMMON_TOKENS' ) ) {

			return \Uncanny_Automator\GF_COMMON_TOKENS::get_hydrated_common_tokens( $parsed, $args, $trigger )
			+ \Uncanny_Automator\GF_COMMON_TOKENS::get_hydrated_form_tokens( $parsed, $args, $trigger )
			+ $this->parse_relevant_tokens( $parsed, $args, $trigger );

		}

		return $parsed;

	}

	/**
	 * Parse relevant tokens.
	 *
	 * @todo Move to its token file.
	 */
	private function parse_relevant_tokens( $parsed, $args, $trigger ) {

		$opt_field_meta = Automator()->get->meta_from_recipes( $this->get_recipes(), 'FIELD' );
		$opt_value_meta = Automator()->get->meta_from_recipes( $this->get_recipes(), 'VALUE' );

		$field = '';
		$value = '';

		if ( isset( $opt_field_meta[ $args['trigger_entry']['recipe_id'] ][ $args['trigger_entry']['trigger_id'] ] ) ) {
			$field = $opt_field_meta[ $args['trigger_entry']['recipe_id'] ][ $args['trigger_entry']['trigger_id'] ];
		}

		if ( isset( $opt_value_meta[ $args['trigger_entry']['recipe_id'] ][ $args['trigger_entry']['trigger_id'] ] ) ) {
			$value = $opt_value_meta[ $args['trigger_entry']['recipe_id'] ][ $args['trigger_entry']['trigger_id'] ];
		}

		$hydrated_relevant_tokens = array(
			'FIELD' => $field,
			'VALUE' => $value,
		);

		return $parsed + $hydrated_relevant_tokens;

	}


}
