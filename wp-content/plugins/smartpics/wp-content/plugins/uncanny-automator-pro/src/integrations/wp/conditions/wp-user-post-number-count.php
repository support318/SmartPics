<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USER_POST_NUMBER_COUNT
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USER_POST_NUMBER_COUNT extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'WP';
		$this->name         = __( 'The user has published {{a specific number of}} posts', 'uncanny-automator-pro' );
		$this->code         = 'USER_POST_NUMBER_COUNT';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( 'The user has published {{greater than or equal to:%1$s}} {{a specific number of:%2$s}} posts', 'uncanny-automator-pro' ),
			'POST_CONDITION',
			'POST_COUNT'
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
					'option_code'           => 'POST_CONDITION',
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
					'option_code' => 'POST_COUNT',
					'label'       => esc_html__( 'Posts', 'uncanny-automator-pro' ),
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

		$post_count = absint( $this->get_parsed_option( 'POST_COUNT' ) );
		$condition  = $this->get_parsed_option( 'POST_CONDITION' );

		$num_posts = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $wpdb->posts WHERE post_author = %d AND post_status = %s AND post_type LIKE %s",
				absint( $this->user_id ),
				'publish',
				'post'
			)
		);

		switch ( $condition ) {
			case '>=':
				if ( $num_posts < $post_count ) {
					$message = sprintf(
					/* Translators: Condition failed message */
						_n(
							"The user's total number of post is less than %s",
							"The user's total number of posts are less than %s",
							absint( $post_count )
						),
						number_format_i18n( $post_count )
					);
					$this->condition_failed( $message );
				}
				break;
			case '<=':
				if ( $num_posts > $post_count ) {
					$message = sprintf(
					/* Translators: Condition failed message */
						_n(
							"The user's total number of post is greater than %s",
							"The user's total number of posts are greater than %s",
							absint( $post_count )
						),
						number_format_i18n( $post_count )
					);
					$this->condition_failed( $message );
				}
				break;
		}
	}
}
