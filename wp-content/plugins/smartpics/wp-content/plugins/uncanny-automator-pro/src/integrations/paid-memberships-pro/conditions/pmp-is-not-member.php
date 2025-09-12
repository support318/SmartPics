<?php

namespace Uncanny_Automator_Pro;

/**
 * Class PMP_IS_NOT_MEMBER
 *
 * @package Uncanny_Automator_Pro
 */
class PMP_IS_NOT_MEMBER extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'PMP';
		/* translators: Token */
		$this->name         = __( 'The user does not have an active membership of {{a specific level}}', 'uncanny-automator-pro' );
		$this->code         = 'IS_NOT_MEMBER';
		$this->dynamic_name = sprintf(
			/* translators: A token matches a value */
			esc_html__( 'The user does not have an active membership of {{a specific level:%1$s}}', 'uncanny-automator-pro' ),
			'MEMBERSHIP'
		);
		$this->is_pro        = true;
		$this->requires_user = true;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {

		$memberships_field_args = $this->get_helpers()->get_membership_condition_field_args( 'MEMBERSHIP' );

		return array(
			$this->field->select_field_args( $memberships_field_args ),
		);
	}

	/**
	 * Method evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 */
	public function evaluate_condition() {

		$membership_id = $this->get_parsed_option( 'MEMBERSHIP' );
		$is_member     = $this->get_helpers()->evaluate_condition_check( $membership_id, $this->user_id );

		// Condition failed.
		if ( ! empty( $is_member ) ) {
			// Generate message.
			if ( $membership_id < 0 ) {
				// Check for Any Active memberships.
				$message = __( 'User has membership level(s).', 'uncanny-automator-pro' );
			} else {
				// Check for specific membership.
				$message = sprintf(
					/* translators: Readable Option name */
					__( 'User is a member of %s', 'uncanny-automator-pro' ),
					$this->get_option( 'MEMBERSHIP_readable' )
				);
			}

			$this->condition_failed( $message );
		}
	}

	/**
	 * Method get_helpers
	 *
	 * @return Paid_Memberships_Pro_Pro_Helpers
	 */
	public function get_helpers() {
		static $paid_memberships_pro_pro_helpers = null;
		if ( null === $paid_memberships_pro_pro_helpers ) {
			$paid_memberships_pro_pro_helpers = new Paid_Memberships_Pro_Pro_Helpers();
		}

		return $paid_memberships_pro_pro_helpers;
	}

}
