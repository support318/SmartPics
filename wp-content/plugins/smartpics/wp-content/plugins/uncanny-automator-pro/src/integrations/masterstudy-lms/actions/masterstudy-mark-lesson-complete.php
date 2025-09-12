<?php

namespace Uncanny_Automator_Pro;

/**
 * Class MASTERSTUDY_MARK_LESSON_COMPLETE
 *
 * @package Uncanny_Automator
 */
class MASTERSTUDY_MARK_LESSON_COMPLETE {

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
		$this->action_code = 'MSLMSMARKLESSONCOMPLETE';
		$this->action_meta = 'MSLMSLESSON';
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
			'sentence'           => sprintf( esc_attr__( 'Mark {{a lesson:%1$s}} complete for the user', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - MasterStudy LMS */
			'select_option_name' => esc_attr__( 'Mark {{a lesson}} complete for the user', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 3,
			'execution_function' => array( $this, 'mark_lesson_complete' ),
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
								'endpoint'     => 'select_mslms_lesson_from_course_x',
							)
						),
						Automator()->helpers->recipe->field->select_field( $this->action_meta, esc_attr_x( 'Lesson', 'MasterStudy LMS', 'uncanny-automator' ), array(), false, false, false ),
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
	public function mark_lesson_complete( $user_id, $action_data, $recipe_id, $args ) {

		$course_id   = $action_data['meta']['MSLMSCOURSE'];
		$lesson_id   = $action_data['meta'][ $this->action_meta ];
		$pro_helpers = new Masterstudy_Pro_Helpers( false );
		$curriculum  = $pro_helpers->pro_get_course_curriculum_materials( $course_id );

		// Bail.
		if ( empty( $curriculum ) ) {
			$action_data['complete_with_errors'] = true;
			$error                               = _x( 'Course does not have any lessons to reset.', 'Masterstudy LMS - Reset Lesson action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error );
			return;
		}

		// Filter $curriculum to only include the lesson we want.
		$lessons = array_filter(
			$curriculum,
			function ( $item ) use ( $lesson_id ) {
				return $item['post_id'] === absint( $lesson_id ) && $item['post_type'] === 'stm-lessons';
			}
		);

		// Bail.
		if ( empty( $lessons ) ) {
			$action_data['complete_with_errors'] = true;
			$error                               = _x( 'Course does not contain lesson to be reset.', 'Masterstudy LMS - Reset Lesson action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error );
			return;
		}

		// Enroll the user in the course if they are not already enrolled.
		$pro_helpers->maybe_enroll_user_to_course( $user_id, $course_id );

		// Not completed.
		if ( ! \STM_LMS_Lesson::is_lesson_completed( $user_id, $course_id, $lesson_id ) ) {
			$end_time   = time();
			$start_time = get_user_meta( $user_id, "stm_lms_course_started_{$course_id}_{$lesson_id}", true );

			stm_lms_add_user_lesson( compact( 'user_id', 'course_id', 'lesson_id', 'start_time', 'end_time' ) );
			\STM_LMS_Course::update_course_progress( $user_id, $course_id );

			do_action( 'stm_lms_lesson_passed', $user_id, $lesson_id );

			delete_user_meta( $user_id, "stm_lms_course_started_{$course_id}_{$lesson_id}" );

			\STM_LMS_Course::update_course_progress( $user_id, $course_id );
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

}
