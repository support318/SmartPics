<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_USER_IS_MEMBER_OF_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_USER_IS_MEMBER_OF_GROUP extends \Uncanny_Automator_Pro\Action_Condition {

	public function define_condition() {
		$this->integration   = 'BDB';
		$this->name          = __( 'The user {{is}} a member of {{a group}}', 'uncanny-automator-pro' );
		$this->code          = 'BDB_USER_IN_GROUP';
		$this->dynamic_name  = sprintf( esc_html__( 'The user {{is:%1$s}} a member of {{a group:%2$s}}', 'uncanny-automator-pro' ), 'CONDITION', 'BDB_GROUP' );
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
					'required'               => true,
					'supports_custom_value'  => false,
					'options_show_id'        => false,
					'options'                => array(
						array(
							'value' => 'is',
							'text'  => esc_html__( 'is', 'uncanny-automator-pro' ),
						),
						array(
							'value' => 'is_not',
							'text'  => esc_html__( 'is not', 'uncanny-automator-pro' ),
						),
					),
				)
			),
			$this->field->select_field_args(
				array(
					'option_code'           => 'BDB_GROUP',
					'label'                 => esc_html__( 'Group', 'uncanny-automator-pro' ),
					'required'              => true,
					'options'               => $this->bdb_groups_options(),
					'supports_custom_value' => true,
				)
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function bdb_groups_options() {
		$all_fields = Automator()->helpers->recipe->buddyboss->all_buddyboss_groups( null, 'BDB_GROUP' );
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
		$condition   = $this->get_option( 'CONDITION' );
		$group       = $this->get_parsed_option( 'BDB_GROUP' );
		$group_name  = $this->get_option( 'BDB_GROUP_readable' );
		$user_groups = groups_get_user_groups( $this->user_id );

		if ( 'is' === $condition && ! in_array( $group, $user_groups['groups'], true ) ) {
			$log_error = sprintf( __( 'The user is not a member of a group: "%s"', 'uncanny-automator-pro' ), $group_name );
			$this->condition_failed( $log_error );
		}

		if ( 'is_not' === $condition && in_array( $group, $user_groups['groups'], true ) ) {
			$log_error = sprintf( __( 'The user is a member of a group: "%s"', 'uncanny-automator-pro' ), $group_name );
			$this->condition_failed( $log_error );
		}

	}
}
