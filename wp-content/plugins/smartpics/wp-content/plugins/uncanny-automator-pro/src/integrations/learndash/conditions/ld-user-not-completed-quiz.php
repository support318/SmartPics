<?php

namespace Uncanny_Automator_Pro;

/**
 * Class LD_USER_NOT_COMPLETED_QUIZ
 *
 * @package Uncanny_Automator_Pro
 */
class LD_USER_NOT_COMPLETED_QUIZ extends Action_Condition {

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'LD';
		/*translators: Token */
		$this->name = __( 'The user has not completed {{a quiz}}', 'uncanny-automator-pro' );
		$this->code = 'NOT_COMPLETED_A_QUIZ';
		// translators: A token matches a value
		$this->dynamic_name  = sprintf( esc_html__( 'The user has not completed {{a quiz:%1$s}}', 'uncanny-automator-pro' ), 'QUIZ' );
		$this->is_pro        = true;
		$this->requires_user = true;
	}

	/**
	 * Fields
	 *
	 * @return array
	 */
	public function fields() {

		$courses_field_args = array(
			'option_code'           => 'QUIZ',
			'label'                 => esc_html__( 'Quiz', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->ld_quiz_options(),
			'supports_custom_value' => true,
		);

		return array(
			// Quiz field
			$this->field->select_field_args( $courses_field_args ),
		);
	}

	/**
	 * @return array[]
	 */
	public function ld_quiz_options() {

		$quizzes = Automator()->helpers->recipe->learndash->all_ld_quiz();
		if ( empty( $quizzes['options'] ) ) {
			return array();
		}

		return $this->normalize_quiz_options( $quizzes['options'] );
	}

	/**
	 * @param $options
	 *
	 * @return array
	 */
	public function normalize_quiz_options( $options ) {
		$ld_quiz = array();
		foreach ( $options as $quiz_id => $quiz_title ) {
			if ( intval( '-1' ) === $quiz_id ) {
				continue;
			}
			$ld_quiz[] = array(
				'value' => $quiz_id,
				'text'  => $quiz_title,
			);
		}

		return $ld_quiz;
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$parsed_quiz   = $this->get_parsed_option( 'QUIZ' );
		$quiz_progress = learndash_user_get_quiz_progress( $this->user_id, $parsed_quiz );
		// if empty, user has not attempted
		if ( ! empty( $quiz_progress ) ) {
			$message = __( 'User has completed the quiz ', 'uncanny-automator-pro' ) . $this->get_option( 'QUIZ_readable' );
			$this->condition_failed( $message );
		}
	}

}
