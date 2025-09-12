<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Learndash_Handle_Hooks
 *
 * @package Uncanny_Automator_Pro
 */
class Learndash_Handle_Hooks {

	/**
	 * Learndash_Handle_Hooks constructor.
	 */
	public function __construct() {

		add_action( 'ldadvquiz_answered', array( $this, 'ld_quiz_question_answered' ), 99, 3 );
	}

	/**
	 * @param $results
	 * @param \WpProQuiz_Model_Quiz $quiz
	 * @param $question_models
	 *
	 * @return void
	 */
	public function ld_quiz_question_answered( $results, $quiz, $question_models ) {
		if ( empty( $results ) ) {
			return;
		}
		$quiz_id   = $quiz->getPostId();
		$questions = array();
		/** @var \WpProQuiz_Model_Question $question_model */
		foreach ( $question_models as $question_model ) {
			$questions[ $question_model->getId() ] = $question_model->getQuestionPostId();
		}
		foreach ( $results as $q_id => $result ) {
			$correct     = isset( $result['c'] ) ? boolval( $result['c'] ) : false;
			$question_id = isset( $questions[ $q_id ] ) ? absint( $questions[ $q_id ] ) : 0;
			do_action( 'automator_learndash_quiz_question_answered', $correct, $question_id, $quiz_id );
		}
	}
}

new Learndash_Handle_Hooks();
