<?php

namespace Uncanny_Automator_Pro;

/**
 * Class MASTERSTUDY_MARK_QUIZ_COMPLETE
 *
 * @package Uncanny_Automator
 */
class MASTERSTUDY_MARK_QUIZ_COMPLETE {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'MSLMS';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'MSLMSMARKQUIZCOMPLETE';
		$this->action_meta = 'MSLMSQUIZ';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/masterstudy-lms/' ),
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			'is_pro'             => true,
			/* translators: Action - MasterStudy LMS */
			'sentence'           => sprintf( esc_attr__( 'Mark {{a quiz:%1$s}} complete for the user', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - MasterStudy LMS */
			'select_option_name' => esc_attr__( 'Mark {{a quiz}} complete for the user', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 3,
			'execution_function' => array( $this, 'mark_quiz_complete' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$args = array(
			'post_type'      => 'stm-courses',
			'posts_per_page' => 999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$options = Automator()->helpers->recipe->options->wp_query( $args, false );

		return Automator()->utilities->keep_order_of_options(
			array(
				'options'       => array(),
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->field->select_field_ajax(
							'MSLMSCOURSE',
							esc_attr_x( 'Course', 'MasterStudy LMS', 'uncanny-automator' ),
							$options,
							'',
							'',
							false,
							true,
							array(
								'target_field' => $this->action_meta,
								'endpoint'     => 'select_mslms_quiz_from_course_QUIZ',
							)
						),
						Automator()->helpers->recipe->field->select_field( $this->action_meta, esc_attr__( 'Quiz', 'uncanny-automator' ), array(), false, false, false ),
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
	 */
	public function mark_quiz_complete( $user_id, $action_data, $recipe_id, $args ) {

		$course_id   = $action_data['meta']['MSLMSCOURSE'];
		$quiz_id     = $action_data['meta'][ $this->action_meta ];
		$pro_helpers = new Masterstudy_Pro_Helpers( false );
		$curriculum  = $pro_helpers->pro_get_course_curriculum_materials( $course_id );

		// Bail.
		if ( empty( $curriculum ) ) {
			$action_data['complete_with_errors'] = true;
			$error                               = _x( 'Course does not have any quizzes to complete.', 'Masterstudy LMS - Mark quiz complete action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error );
			return;
		}

		// Filter $curriculum to only include the quiz we want.
		$quizzes = array_filter(
			$curriculum,
			function ( $item ) use ( $quiz_id ) {
				return $item['post_id'] === absint( $quiz_id ) && $item['post_type'] === 'stm-quizzes';
			}
		);

		// Bail.
		if ( empty( $quizzes ) ) {
			$action_data['complete_with_errors'] = true;
			$error                               = _x( 'Course does not contain quiz to be marked complete.', 'Masterstudy LMS - Mark quiz complete action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error );
			return;
		}

		// Enroll the user in the course if they are not already enrolled.
		$pro_helpers->maybe_enroll_user_to_course( $user_id, $course_id );

		// Quiz is not already passed.
		if ( ! \STM_LMS_Quiz::quiz_passed( $quiz_id, $user_id ) ) {
			$progress  = 100;
			$status    = 'passed';
			$user_quiz = compact( 'user_id', 'course_id', 'quiz_id', 'progress', 'status' );
			stm_lms_add_user_quiz( $user_quiz );
			stm_lms_get_delete_user_quiz_time( $user_id, $quiz_id );

			\STM_LMS_Course::update_course_progress( $user_id, $course_id );

			$user_quiz['progress'] = round( $user_quiz['progress'], 1 );
			do_action( 'stm_lms_quiz_' . $status, $user_id, $quiz_id, $user_quiz['progress'] );
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

}
