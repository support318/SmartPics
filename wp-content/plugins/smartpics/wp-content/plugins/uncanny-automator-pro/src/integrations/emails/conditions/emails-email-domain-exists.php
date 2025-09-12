<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EMAILS_EMAIL_DOMAIN_EXISTS
 *
 * @package Uncanny_Automator_Pro
 */
class EMAILS_EMAIL_DOMAIN_EXISTS extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'EMAILS';
		$this->name         = __( '{{An email address}} ends with {{a domain}}', 'uncanny-automator-pro' );
		$this->code         = 'EMAIL_DOMAIN_EXISTS';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( '{{An email address:%1$s}} ends with {{a domain:%2$s}}', 'uncanny-automator-pro' ),
			'EMAIL_ADDRESS',
			'DOMAIN_NAME'
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
			$this->field->text(
				array(
					'option_code' => 'DOMAIN_NAME',
					'label'       => esc_html__( 'Domain', 'uncanny-automator-pro' ),
					'required'    => true,
					'placeholder' => 'example.come',
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
		$domain_name   = $this->get_parsed_option( 'DOMAIN_NAME' );
		$condition_met = $this->match_domain( $email_address, $domain_name );
		// If the conditions is not met, send an error message and mark the condition as failed.
		if ( false === $condition_met ) {
			/* translators: Domain name */
			/* translators: Email address */
			$message = sprintf( __( 'The email address (%2$s) does not end with the domain (%1$s)', 'uncanny-automator-pro' ), $domain_name, $email_address );
			$this->condition_failed( $message );
		}
	}

	/**
	 * @param $email
	 * @param $domain_to_match
	 *
	 * @return false
	 */
	public function match_domain( $email, $domain_to_match ) {
		$raw    = explode( '@', $email );
		$domain = isset( $raw[1] ) ? $raw[1] : '';
		if ( empty( $domain ) ) {
			return false;
		}

		return strtolower( (string) $domain ) === strtolower( (string) $domain_to_match );
	}
}
