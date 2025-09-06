<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USER_COMMENT_NUMBER_COUNT
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USER_COMMENT_NUMBER_COUNT extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'WP';
		$this->name         = __( 'The user has posted {{a specific number of}} approved comments', 'uncanny-automator-pro' );
		$this->code         = 'USER_COMMENT_NUMBER_COUNT';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( 'The user has posted {{greater than or equal to:%1$s}} {{a specific number of:%2$s}} approved comments', 'uncanny-automator-pro' ),
			'COMMENT_CONDITION',
			'COMMENT_COUNT'
		);
		$this->requires_user = true;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {

		return array(
			$this->field->select_field_args(
				array(
					'option_code'           => 'COMMENT_CONDITION',
					'label'                 => esc_html__( 'Condition', 'uncanny-automator-pro' ),
					'required'              => true,
					'options'               => array(
						array(
							'value' => '>=',
							'text'  => __( 'greater than or equal to', 'uncanny-automator-pro' ),
						),
						array(
							'value' => '<=',
							'text'  => __( 'less than or equal to', 'uncanny-automator-pro' ),
						),
					),
					'supports_custom_value' => false,
					'options_show_id'       => false,
				)
			),
			$this->field->text(
				array(
					'option_code' => 'COMMENT_COUNT',
					'label'       => esc_html__( 'Comments', 'uncanny-automator-pro' ),
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

		global $wpdb;

		$comment_count = absint( $this->get_parsed_option( 'COMMENT_COUNT' ) );
		$condition     = $this->get_parsed_option( 'COMMENT_CONDITION' );
		$user          = get_user_by( 'id', $this->user_id );

		// Bail early if user is not found.
		if ( empty( $user ) ) {
			/* Translators: Condition failed sentence */
			$this->condition_failed( sprintf( __( 'Cannot find user with ID: %d', 'uncanny-automator-pro' ), absint( $this->user_id ) ) );

			return;
		}

		$num_comments = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->comments WHERE comment_author_email = %s AND comment_approved = %s AND comment_type LIKE %s",
				$user->user_email,
				1, // <-- Only count approved comments
				'comment'
			)
		);
		switch ( $condition ) {
			case '>=':
				if ( $num_comments < $comment_count ) {
					$message = sprintf(
					/* Translators: Condition failed message */
						_n(
							"The user's total number of comment is less than %s",
							"The user's total number of comments are less than %s",
							absint( $comment_count )
						),
						number_format_i18n( $comment_count )
					);
					$this->condition_failed( $message );

					return;
				}
				break;
			case '<=':
				if ( $num_comments > $comment_count ) {
					$message = sprintf(
					/* Translators: Condition failed message */
						_n(
							"The user's total number of comment is greater than %s",
							"The user's total number of comments are greater than %s",
							absint( $comment_count )
						),
						number_format_i18n( $comment_count )
					);
					$this->condition_failed( $message );

					return;
				}
				break;
		}
	}
}
