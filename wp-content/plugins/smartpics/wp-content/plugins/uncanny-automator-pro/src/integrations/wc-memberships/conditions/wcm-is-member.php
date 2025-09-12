<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WCM_IS_MEMBER
 *
 * @package Uncanny_Automator_Pro
 */
class WCM_IS_MEMBER extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'WCMEMBERSHIPS';
		/* translators: Token */
		$this->name         = __( 'The user has an active membership of {{a specific plan}}', 'uncanny-automator-pro' );
		$this->code         = 'IS_MEMBER';
		$this->dynamic_name = sprintf(
			/* translators: A token matches a value */
			esc_html__( 'The user has an active membership of {{a specific plan:%1$s}}', 'uncanny-automator-pro' ),
			'MEMBERSHIP'
		);
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
		if ( empty( $is_member ) ) {
			if ( $membership_id < 0 ) {
				// Check for Any Active memberships.
				$message = __( 'User does not have any membership plans.', 'uncanny-automator-pro' );
			} else {
				// Check for specific membership.
				$message = sprintf(
					/* translators: Readable Option name */
					__( 'User is not a member of %s', 'uncanny-automator-pro' ),
					$this->get_option( 'MEMBERSHIP_readable' )
				);
			}

			$this->condition_failed( $message );
		}
	}

	/**
	 * Method get_helpers
	 *
	 * @return Wc_Memberships_Pro_Helpers
	 */
	public function get_helpers() {
		static $wc_memberships_pro_helpers = null;
		if ( null === $wc_memberships_pro_helpers ) {
			$wc_memberships_pro_helpers = new Wc_Memberships_Pro_Helpers();
		}
		return $wc_memberships_pro_helpers;
	}

}
