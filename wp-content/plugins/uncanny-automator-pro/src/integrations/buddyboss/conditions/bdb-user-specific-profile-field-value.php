<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_USER_SPECIFIC_PROFILE_FIELD_VALUE
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_USER_SPECIFIC_PROFILE_FIELD_VALUE extends \Uncanny_Automator_Pro\Action_Condition {

	public function define_condition() {
		$this->integration   = 'BDB';
		$this->name          = __( 'The user {{has}} {{a value}} in {{an Xprofile field}}', 'uncanny-automator-pro' );
		$this->code          = 'BDB_USER_FIELD_VALUE';
		$this->dynamic_name  = sprintf( esc_html__( 'The user {{has:%1$s}} {{a value:%3$s}} in {{an Xprofile field:%2$s}}', 'uncanny-automator-pro' ), 'CONDITION', 'BDB_PROFILE_FIELD', 'BDB_FIELD_VALUE' );
		$this->requires_user = true;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {

		return array(
			$this->field->select(
				array(
					'option_code'            => 'CONDITION',
					'label'                  => esc_html__( 'Condition', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'supports_custom_value'  => false,
					'required'               => true,
					'options_show_id'        => false,
					'options'                => array(
						array(
							'value' => 'has',
							'text'  => esc_html__( 'has', 'uncanny-automator-pro' ),
						),
						array(
							'value' => 'has_not',
							'text'  => esc_html__( 'does not have', 'uncanny-automator-pro' ),
						),
					),
				)
			),
			$this->field->select_field_args(
				array(
					'option_code'            => 'BDB_PROFILE_FIELD',
					'label'                  => esc_html__( 'Field', 'uncanny-automator-pro' ),
					'required'               => true,
					'options'                => $this->bdb_fields_options(),
					'supports_custom_value'  => true,
					'show_label_in_sentence' => false,
				)
			),
			$this->field->text(
				array(
					'option_code'            => 'BDB_FIELD_VALUE',
					'label'                  => esc_html__( 'Value', 'uncanny-automator-pro' ),
					'required'               => false,
					'show_label_in_sentence' => false,
				)
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function bdb_fields_options() {
		$all_fields = Automator()->helpers->recipe->buddyboss->pro->list_base_profile_fields( null, 'BDB_PROFILE_FIELD' );
		$options    = array();
		foreach ( $all_fields['options'] as $id => $field ) {
			$options[] = array(
				'value' => $id,
				'text'  => $field,
			);

		}

		return $options;
	}


	/**
	 * Evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 */
	public function evaluate_condition() {
		$condition  = $this->get_option( 'CONDITION' );
		$field      = $this->get_parsed_option( 'BDB_PROFILE_FIELD' );
		$value      = $this->get_parsed_option( 'BDB_FIELD_VALUE' );
		$field_name = $this->get_option( 'BDB_PROFILE_FIELD_readable' );

		$user_xprofile_field_value = xprofile_get_field_data( $field, $this->user_id );

		if ( 'has' === $condition && false === $this->check_field_value( $user_xprofile_field_value, $value ) ) {
			$log_error = sprintf( __( 'The user does not have "%2$s" in "%1$s"', 'uncanny-automator-pro' ), $field_name, $value );
			$this->condition_failed( $log_error );
		}
		if ( 'has_not' === $condition && true === $this->check_field_value( $user_xprofile_field_value, $value ) ) {
			$log_error = sprintf( __( 'The user has "%2$s" in "%1$s"', 'uncanny-automator-pro' ), $field_name, $value );
			$this->condition_failed( $log_error );
		}

	}

	/**
	 * @param $user_xprofile_field_value
	 * @param $value
	 *
	 * @return bool
	 */
	public function check_field_value( $user_xprofile_field_value, $value ) {
		if ( is_array( $user_xprofile_field_value ) ) {
			if ( in_array( $value, $user_xprofile_field_value, true ) ) {
				return true;
			}
		} else {
			if ( $user_xprofile_field_value === $value ) {
				return true;
			}
		}

		return false;
	}
}
