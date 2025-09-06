<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LD_USER_NOT_COMPLETED_LESSON
 *
 * @package Uncanny_Automator_Pro
 */
class LD_USER_NOT_COMPLETED_LESSON extends Action_Condition {

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'LD';
		/*translators: Token */
		$this->name = __( 'The user has not completed {{a lesson}}', 'uncanny-automator-pro' );
		$this->code = 'NOT_COMPLETED_A_LESSON';
		// translators: A token matches a value
		$this->dynamic_name  = sprintf( esc_html__( 'The user has not completed {{a lesson:%1$s}}', 'uncanny-automator-pro' ), 'LESSON' );
		$this->is_pro        = true;
		$this->requires_user = true;
	}

	/**
	 * Fields
	 *
	 * @return array
	 */
	public function fields() {

		$lesson_field_args = array(
			'option_code'           => 'LESSON',
			'label'                 => esc_html__( 'Lesson', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->ld_lessons_options(),
			'supports_custom_value' => true,
		);

		return array(
			// Course field
			$this->field->select_field_args( $lesson_field_args ),
		);
	}

	/**
	 * Load options
	 *
	 * @return array[]
	 */
	public function ld_lessons_options() {

		$lessons = Automator()->helpers->recipe->learndash->all_ld_lessons();
		if ( empty( $lessons['options'] ) ) {
			return array();
		}

		return $this->normalize_lessons_options( $lessons['options'] );

	}

	/**
	 * @param $options
	 *
	 * @return array
	 */
	public function normalize_lessons_options( $options ) {
		$ld_lessons = array();
		foreach ( $options as $lesson_id => $lesson_title ) {
			if ( intval( '-1' ) === $lesson_id ) {
				continue;
			}
			$ld_lessons[] = array(
				'value' => $lesson_id,
				'text'  => $lesson_title,
			);
		}

		return $ld_lessons;
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$parsed_lesson = $this->get_parsed_option( 'LESSON' );

		$has_completed = ! learndash_is_lesson_complete( $this->user_id, $parsed_lesson );

		// Check if the user is enrolled in the course here
		if ( false === (bool) $has_completed ) {

			$message = __( 'User has completed lesson ', 'uncanny-automator-pro' ) . $this->get_option( 'LESSON_readable' );
			$this->condition_failed( $message );
		}
	}

}
