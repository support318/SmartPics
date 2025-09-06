<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USER_EMAIL_EXISTS
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USER_EMAIL_EXISTS extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'WP';
		$this->name         = __( 'A user with {{an email address}} exists on the site', 'uncanny-automator-pro' );
		$this->code         = 'USER_EMAIL_EXISTS';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( 'A user with {{an email address:%1$s}} exists on the site', 'uncanny-automator-pro' ),
			'EMAIL_ADDRESS'
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
					'option_code' => 'EMAIL_ADDRESS',
					'label'       => esc_html__( 'Email address', 'uncanny-automator-pro' ),
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

		$email_address = $this->get_parsed_option( 'EMAIL_ADDRESS' );
		if ( empty( $email_address ) || ! is_email( $email_address ) ) {
			/* translators: Email address */
			$message = sprintf( __( 'Invalid email provided (%1$s)', 'uncanny-automator-pro' ), $email_address );
			$this->condition_failed( $message );

			return;
		}
		$condition_met = email_exists( $email_address );
		// If the conditions is not met, send an error message and mark the condition as failed.
		if ( false === boolval( $condition_met ) ) {
			/* translators: Email address */
			$message = sprintf( __( 'The email (%s) does not exist on the site', 'uncanny-automator-pro' ), $email_address );
			$this->condition_failed( $message );
		}
	}
}
