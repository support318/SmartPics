<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class LD_MARK_QUIZ_COMPLETE
 *
 * @package Uncanny_Automator_Pro
 */
class LD_MARK_QUIZ_COMPLETE {

	use Recipe\Actions;
	use Recipe\Action_Tokens;

	/**
	 * Set up Automator action constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 *
	 * @return void
	 */
	public function setup_action() {
		$this->set_integration( 'LD' );
		$this->set_action_code( 'MARKQUIZCOMPLETE' );
		$this->set_action_meta( 'LDQUIZ' );
		$this->set_support_link( 'integration/learndash/' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );
		/* translators: Action - LearnDash */
		$this->set_sentence( sprintf( _x( 'Mark {{a quiz:%1$s}} complete for the user', 'LearnDash - Mark quiz complete action', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		/* translators: Action - LearnDash */
		$this->set_readable_sentence( esc_attr_x( 'Mark {{a quiz}} complete for the user', 'LearnDash - Mark quiz complete action', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );

		// Set Action tokens.
		$this->set_action_tokens( Ld_Pro_Tokens::get_common_quiz_action_tokens( $this->get_action_meta() ), $this->get_action_code() );
		$this->register_action();
	}

	/**
	 * Load Options for the select field
	 *
	 * @return array
	 */
	public function load_options() {

		// Query args for courses.
		$args = array(
			'post_type'      => 'sfwd-courses',
			'posts_per_page' => 999, //phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$trigger_meta = $this->get_action_meta();

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$trigger_meta => array(
						// Course Selector.
						Automator()->helpers->recipe->field->select_field_ajax(
							'LDCOURSE',
							__( 'Course', 'uncanny-automator' ),
							Automator()->helpers->recipe->options->wp_query( $args ),
							'', //default
							'', //placeholder
							false, //supports tokens
							true, //is ajax
							array(
								'target_field' => 'LDSTEP',
								'endpoint'     => 'select_lessontopic_from_course',
							)
						),
						// Lesson/Topic Selector.
						Automator()->helpers->recipe->field->select_field_ajax(
							'LDSTEP',
							__( 'Lesson/Topic', 'uncanny-automator-pro' ),
							array(),
							'', //default
							'', //placeholder
							false, //supports tokens
							true, //is ajax
							array(
								'target_field' => $trigger_meta,
								'endpoint'     => 'select_specific_quiz_from_course_lessontopic',
							)
						),
						// Quiz Selector.
						Automator()->helpers->recipe->field->select(
							array(
								'option_code' => $trigger_meta,
								'label'       => esc_attr__( 'Quiz', 'uncanny-automator' ),
								'options'     => array(),
							)
						),
					),
				),
			)
		);
	}

	/**
	 * Process function when the action is hit.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$course_id = isset( $parsed['LDCOURSE'] ) ? intval( $parsed['LDCOURSE'] ) : 0;
		$step_id   = isset( $parsed['LDSTEP'] ) ? intval( $parsed['LDSTEP'] ) : 0;
		$quiz_id   = isset( $parsed[ $this->get_action_meta() ] ) ? intval( $parsed[ $this->get_action_meta() ] ) : 0;

		// No Course ID.
		if ( empty( $course_id ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = _x( 'No course selected.', 'LearnDash - Mark quiz not complete action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		// No Step ID.
		if ( empty( $step_id ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = _x( 'No lesson or topic selected.', 'LearnDash - Mark quiz not complete action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		// No Quiz ID.
		if ( empty( $quiz_id ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = _x( 'No quiz selected.', 'LearnDash - Mark quiz complete action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		$course_progress = Learndash_Pro_Helpers::get_user_current_course_progress( $user_id, $course_id );
		$course_updates  = $this->course_progress_updates( $course_progress, $quiz_id, $step_id );

		// If the course progress is empty, then there is nothing to update.
		if ( empty( $course_updates ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$error_message                       = _x( 'Lesson/Topic not associated with quiz.', 'LearnDash - Mark quiz not complete action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		$update = array(
			'course' => array(
				$course_id => $course_updates,
			),
			'quiz'   => array(
				$course_id => array(
					$quiz_id => 1,
				),
			),
		);

		// Update the user progress.
		$updated_course_ids = learndash_process_user_course_progress_update( $user_id, $update );

		// Hydrate Action Tokens.
		$this->hydrate_tokens( Ld_Pro_Tokens::hydrate_common_quiz_action_tokens( $quiz_id, $course_id, $user_id, $this->get_action_meta(), $step_id ) );

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Update course progress array.
	 *
	 * @param array $course_progress
	 * @param int $quiz_id
	 * @param int $step_id
	 *
	 * @return array
	 */
	function course_progress_updates( $course_progress, $quiz_id, $step_id ) {

		$updates = $course_progress['course'];
		$lessons = $course_progress['lessons'];

		$quiz_step_ids = Learndash_Pro_Helpers::quiz_step_ids( $lessons, $quiz_id );
		$lesson_id     = $quiz_step_ids['lesson'];
		$topic_id      = $quiz_step_ids['topic'];

		// Validate if the step ID needs to be enforced.
		if ( $step_id > 0 ) {
			// Need to confirm the Quiz ID belongs to the step.
			if ( ! in_array( $step_id, $quiz_step_ids, true ) ) {
				return array();
			}
		}

		// Check if topic should be completed.
		if ( ! empty( $topic_id ) ) {
			$lessons[ $lesson_id ]['topics'][ $topic_id ]['quizzes'][ $quiz_id ] = 1;
			if ( $this->all_step_quizzes_completed( $lessons[ $lesson_id ]['topics'][ $topic_id ] ) ) {
				$updates['topics'][ $lesson_id ][ $topic_id ]                 = 1;
				$lessons[ $lesson_id ]['topics'][ $topic_id ]['is_completed'] = 1;
			}
		}

		// Check if lesson should be completed.
		if ( $this->all_lesson_steps_completed( $lessons[ $lesson_id ], $quiz_id, $topic_id ) ) {
			// REVIEW - should we also check for assignments?
			$updates['lessons'][ $lesson_id ] = 1;
		}

		return $updates;
	}

	/**
	 * Check if all quizzes in a step are completed.
	 *
	 * @param array $step
	 *
	 * @return bool
	 */
	function all_step_quizzes_completed( $step, $complete_quiz_id = 0 ) {
		foreach ( $step['quizzes'] as $quiz_id => $status ) {
			if ( empty( $status ) ) {
				if ( ! empty( $complete_quiz_id ) ) {
					if ( $quiz_id !== $complete_quiz_id ) {
						return false;
					}
				} else {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Check if all lesson steps are completed.
	 *
	 * @param array $lesson
	 * @param int $quiz_id
	 *
	 * @return bool
	 */
	function all_lesson_steps_completed( $lesson, $quiz_id, $quiz_topic_id ) {

		if ( ! empty( $lesson['topics'] ) ) {
			// Check all topic quizzes.
			foreach ( $lesson['topics'] as $topic_id => $topic ) {
				if ( ! $this->all_step_quizzes_completed( $topic, $quiz_id ) ) {
					return false;
				}
			}

			// Check all topics.
			foreach ( $lesson['topics'] as $topic_id => $topic ) {
				if ( empty( $topic['is_completed'] ) && $topic_id !== $quiz_topic_id ) {
					return false;
				}
			}
		}

		// Check all lesson quizzes.
		if ( ! empty( $lesson['quizzes'] ) ) {
			if ( ! $this->all_step_quizzes_completed( $lesson, $quiz_id ) ) {
				return false;
			}
		}

		return true;
	}

}
