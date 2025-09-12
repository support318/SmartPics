<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_EMAIL_CONTAINS_DOMAIN
 *
 * @package Uncanny_Automator_Pro
 */
class WP_EMAIL_CONTAINS_DOMAIN extends Action_Condition {

	public function define_condition() {
		$this->integration   = 'WP';
		$this->name          = esc_attr_x( "The user's email address matches {{a specific domain}}", 'WordPress', 'uncanny-automator-pro' );
		$this->code          = 'USER_EMAIL_CONTAINS_DOMAIN';
		$this->dynamic_name  = sprintf( esc_html_x( "The user's email address matches {{a specific domain:%s}}", 'WordPress', 'uncanny-automator-pro' ), 'EMAIL_DOMAIN' );
		$this->requires_user = true;
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
					'option_code'            => 'EMAIL_DOMAIN',
					'label'                  => esc_html_x( 'Email domain', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => true,
					'description'            => esc_html__( 'Enter domain only, e.g. gmail.com', 'uncanny-automator-pro' ),
					'placeholder'            => esc_html__( 'Enter domain', 'uncanny-automator-pro' ),

					'input_type'             => 'text',
					'required'               => true,
				)
			),
		);
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 */
	public function evaluate_condition() {
		$domain     = mb_strtolower( $this->get_parsed_option( 'EMAIL_DOMAIN' ) );
		$user_data  = get_userdata( $this->user_id );
		$user_email = mb_strtolower( $user_data->user_email );
		if ( false === strpos( $user_email, $domain ) ) {
			$log_error = sprintf( __( "User email %1\$s doesn't contain the domain %2\$s", 'uncanny-automator-pro' ), $user_email, $domain );
			$this->condition_failed( $log_error );

		}
	}

}
