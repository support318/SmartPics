<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class LD_ANON_REPAIRCOURSEPROGRESS
 *
 * @package Uncanny_Automator_Pro
 */
class LD_ANON_REPAIRCOURSEPROGRESS {
	use Recipe\Actions;

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
		$this->set_action_code( 'REPAIRDONECOURSES' );
		$this->set_action_meta( 'LDCOURSE' );
		$this->set_support_link( 'integration/learndash/' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );
		/* translators: Action - LearnDash */
		$this->set_sentence( sprintf( _x( 'Repair the progress of {{a completed course:%1$s}} for the user', 'LearnDash - Repair action', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		/* translators: Action - LearnDash */
		$this->set_readable_sentence( esc_attr_x( 'Repair the progress of {{a completed course}} for the user', 'LearnDash - Repair action', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * Load the options for this action.
	 *
	 * @return array[]
	 */
	public function load_options() {
		$ld_courses            = Automator()->helpers->recipe->learndash->all_ld_courses( null, $this->get_action_meta(), false );
		$ld_courses['options'] = array( '-1' => _x( 'All enrolled courses', 'LearnDash - Repair action', 'uncanny-automator-pro' ) ) + $ld_courses['options'];
		// Approved by UI/UX team.
		$ld_courses['description'] = sprintf(
			'<uo-alert type="warning" size="small" style="margin-top: 5px">%s<br /><br/><strong>%s</strong> %s</uo-alert>',
			_x( "This action will loop through lessons, topics and quizzes for the selected course and mark them as complete if the user has completed the course, ensuring a user's progress is shown as 100% complete.", 'LearnDash - Repair action', 'uncanny-automator-pro' ),
			_x( 'IMPORTANT:', 'LearnDash - Repair action', 'uncanny-automator-pro' ),
			_x( 'Completing incomplete lessons and topics may fire course completion again and update the course completion date.', 'LearnDash - Repair action', 'uncanny-automator-pro' )
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						$ld_courses,
					),
				),
			)
		);
	}

	/**
	 * Validation function when the action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$count              = 0;
		$selected_course_id = intval( $parsed[ $this->get_action_meta() ] );

		// Build Course Array to process.
		$courses = array();
		if ( intval( '-1' ) !== $selected_course_id ) {
			$courses[] = $selected_course_id;
		} else {
			$courses = learndash_user_get_enrolled_courses( $user_id );
		}

		// If no courses found, return.
		if ( empty( $courses ) ) {

			$action_data['complete_with_errors'] = true;
			$action_data['do-nothing']           = true;
			$error_message                       = esc_html_x( 'No courses found for the user.', 'LearnDash - Repair action', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;

		}

		// Initialize variables.
		$errors    = array();
		$completed = 0;

		// Loop through courses.
		foreach ( $courses as $course_id ) {

			// Course is not completed yet.
			if ( ! learndash_course_completed( $user_id, $course_id ) ) {
				$errors[ $course_id ] = 'course_not_completed';
				continue;
			}

			// Get Course Steps and Quizzes.
			$course_steps = learndash_get_course_steps( $course_id );
			$quiz_id_list = $this->get_course_quiz_list( $course_id, $user_id, $course_steps );

			// Steps are already completed.
			if ( $this->validate_course_progress( $user_id, $course_id, $quiz_id_list ) ) {
				$errors[ $course_id ] = 'course_steps_completed';
				continue;
			}

			// Mark All Quizzes Complete first.
			$this->mark_quizzes_complete( $user_id, $course_id, $quiz_id_list );

			// Mark Steps Complete.
			$this->mark_course_steps_complete( $user_id, $course_id, $course_steps );

			$completed ++;

			// Check if course completion behaviour needs to be processed.
			$process = apply_filters( 'automator_learndash_repair_course_progress', false, $course_id, $user_id );
			if ( $process ) {
				learndash_process_mark_complete( $user_id, $course_id );
			}
		}

		// If no courses were completed, return with errors.
		if ( empty( $completed ) ) {

			$action_data['complete_with_errors'] = true;
			$action_data['do-nothing']           = true;
			$error_message                       = esc_html_x( 'No courses progress was repaired.', 'LearnDash - Repair action', 'uncanny-automator-pro' );

			if ( ! empty( $errors ) ) {
				foreach ( $errors as $course_id => $error_key ) {
					$course_title = get_the_title( $course_id );
					switch ( $error_key ) {
						case 'course_not_completed':
							/* translators: Action - LearnDash %s is course name */
							$error_message .= ' ' . sprintf( esc_html_x( 'Course: %s not completed.', 'LearnDash - Repair action', 'uncanny-automator-pro' ), $course_title );
							break;
						case 'course_steps_completed':
							/* translators: Action - LearnDash %s is course name */
							$error_message .= ' ' . sprintf( esc_html_x( 'Course: %s steps already completed.', 'LearnDash - Repair action', 'uncanny-automator-pro' ), $course_title );
							break;
					}
				}
			}

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		// Complete action.
		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Get Course Quiz List.
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @param array $course_steps
	 *
	 * @return array
	 */
	public function get_course_quiz_list( $course_id, $user_id, $course_steps ) {

		// Build Quiz ID Array to process.
		$quizzes        = array();
		$course_quizzes = learndash_get_course_quiz_list( $course_id, $user_id );
		if ( ! empty( $course_quizzes ) ) {
			foreach ( $course_quizzes as $quiz ) {
				$quizzes[] = $quiz['id'];
			}
		}
		if ( ! empty( $course_steps ) ) {
			foreach ( $course_steps as $step_id ) {
				$step_quizzes = learndash_get_lesson_quiz_list( $step_id, $user_id, $course_id );
				if ( ! empty( $step_quizzes ) ) {
					foreach ( $step_quizzes as $step_quiz ) {
						$quizzes[] = $step_quiz['id'];
					}
				}
			}
		}

		return ! empty( $quizzes ) ? array_unique( $quizzes ) : array();
	}

	/**
	 * Validate if all course steps are completed.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param array $quiz_id_list
	 *
	 * @return bool
	 */
	public function validate_course_progress( $user_id, $course_id, $quiz_id_list ) {
		$completed_steps = (int) learndash_course_get_completed_steps( $user_id, $course_id );
		$total_steps     = (int) learndash_get_course_steps_count( $course_id );
		if ( $completed_steps !== $total_steps ) {
			return false;
		}

		if ( ! empty( $quiz_id_list ) ) {
			foreach ( $quiz_id_list as $quiz_id ) {
				if ( ! learndash_is_quiz_complete( $user_id, $quiz_id, $course_id ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Mark all course lessons and topics complete.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param array $course_steps
	 *
	 * @return void
	 */
	public function mark_course_steps_complete( $user_id, $course_id, $course_steps ) {
		if ( ! empty( $course_steps ) ) {
			foreach ( $course_steps as $step_id ) {
				if ( ! learndash_is_lesson_complete( $user_id, $step_id, $course_id ) ) {
					learndash_process_mark_complete( $user_id, $step_id, false, $course_id );
				}
			}
		}
	}

	/**
	 * Mark all course quizzes complete.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param array $quiz_id_list
	 *
	 * @return void
	 */
	public function mark_quizzes_complete( $user_id, $course_id, $quiz_id_list ) {

		// If no quizzes found, return.
		if ( empty( $quiz_id_list ) ) {
			return;
		}

		// Get User Quiz Progress.
		$usermeta      = get_user_meta( $user_id, '_sfwd-quizzes', true );
		$quiz_progress = empty( $usermeta ) ? array() : $usermeta;
		$updated       = false;

		// Loop through quizzes.
		foreach ( $quiz_id_list as $quiz_id ) {

			// Check if quiz is already completed.
			if ( learndash_is_quiz_complete( $user_id, $quiz_id, $course_id ) ) {
				continue;
			}

			$quiz_meta = get_post_meta( $quiz_id, '_sfwd-quiz', true );

			$quizdata = array(
				'quiz'             => $quiz_id,
				'score'            => 0,
				'count'            => 0,
				'pass'             => true,
				'rank'             => '-',
				'time'             => time(),
				'pro_quizid'       => $quiz_meta['sfwd-quiz_quiz_pro'],
				'course'           => $course_id,
				'points'           => 0,
				'total_points'     => 0,
				'percentage'       => 0,
				'timespent'        => 0,
				'has_graded'       => false,
				'statistic_ref_id' => 0,
				'm_edit_by'        => 9999999,  // Manual Edit By ID.
				'm_edit_time'      => time(),
			);

			$quiz_progress[] = $quizdata;

			// Then we add the quiz entry to the activity database.
			learndash_update_user_activity(
				array(
					'course_id'          => $course_id,
					'user_id'            => $user_id,
					'post_id'            => $quiz_id,
					'activity_type'      => 'quiz',
					'activity_action'    => 'insert',
					'activity_status'    => true,
					'activity_started'   => $quizdata['time'],
					'activity_completed' => $quizdata['time'],
					'activity_meta'      => $quizdata,
				)
			);

			$updated = true;
		}

		if ( ! empty( $quiz_progress ) && $updated ) {
			update_user_meta( $user_id, '_sfwd-quizzes', $quiz_progress );
		}

	}
}
