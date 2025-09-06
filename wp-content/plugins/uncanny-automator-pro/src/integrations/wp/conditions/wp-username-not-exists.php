<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USERNAME_NOT_EXISTS
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USERNAME_NOT_EXISTS extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'WP';
		$this->name         = __( 'A user with {{a username}} does not exist on the site', 'uncanny-automator-pro' );
		$this->code         = 'USERNAME_NOT_EXISTS';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( 'A user with {{a username:%1$s}} does not exist on the site', 'uncanny-automator-pro' ),
			'USER_LOGIN'
		);
		$this->requires_user = false;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {

		return array(
			$this->field->text(
				array(
					'option_code' => 'USER_LOGIN',
					'label'       => esc_html__( 'Username', 'uncanny-automator-pro' ),
					'required'    => true,
				)
			),
		);
	}

	/**
	 * Evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$username = $this->get_parsed_option( 'USER_LOGIN' );
		if ( empty( $username ) ) {
			/* translators: Username */
			$message = sprintf( __( 'Invalid username provided (%1$s)', 'uncanny-automator-pro' ), $username );
			$this->condition_failed( $message );

			return;
		}
		$condition_met = username_exists( $username );
		// If the conditions is not met, send an error message and mark the condition as failed.
		if ( is_numeric( $condition_met ) ) {
			/* translators: Email address */
			$message = sprintf( __( 'The username (%s) exists on the site', 'uncanny-automator-pro' ), $username );
			$this->condition_failed( $message );
		}
	}
}
