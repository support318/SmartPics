<?php

namespace Uncanny_Automator_Pro;

/**
 * Class MASTERSTUDY_RESET_COURSE_PROGRESS
 *
 * @package Uncanny_Automator
 */
class MASTERSTUDY_RESET_COURSE_PROGRESS {

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
		$this->action_code = 'MSLMSRESETCOURSEPROGRESS';
		$this->action_meta = 'MSLMSCOURSE';
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
			'sentence'           => sprintf( esc_attr__( "Reset the user's progress in {{a course:%1\$s}}", 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - MasterStudy LMS */
			'select_option_name' => esc_attr__( "Reset the user's progress in {{a course}}", 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 3,
			'execution_function' => array( $this, 'reset_course' ),
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
				'options' => array(
					array(
						'option_code'              => $this->action_meta,
						'label'                    => esc_attr__( 'Course', 'uncanny-automator' ),
						'input_type'               => 'select',
						'required'                 => true,
						'options'                  => $options,
						'custom_value_description' => _x( 'Course ID', 'MasterStudy', 'uncanny-automator' ),
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
	public function reset_course( $user_id, $action_data, $recipe_id, $args ) {

		$course_id   = $action_data['meta'][ $this->action_meta ];
		$pro_helpers = new Masterstudy_Pro_Helpers( false );
		$curriculum  = $pro_helpers->pro_get_course_curriculum_materials( $course_id );

		// Bail.
		if ( empty( $curriculum ) ) {
			$action_data['complete_with_errors'] = true;
			$error                               = _x( 'Course does not have any curriculum to reset.', 'Masterstudy LMS - Reset course action', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error );
			return;
		}

		foreach ( $curriculum as $item ) {
			switch ( $item['post_type'] ) {
				case 'stm-lessons':
					\STM_LMS_User_Manager_Course_User::reset_lesson( $user_id, $course_id, $item['post_id'] );
					break;
				case 'stm-assignments':
					\STM_LMS_User_Manager_Course_User::reset_assignment( $user_id, $course_id, $item['post_id'] );
					break;
				case 'stm-quizzes':
					\STM_LMS_User_Manager_Course_User::reset_quiz( $user_id, $course_id, $item['post_id'] );
					break;
			}
		}

		\STM_LMS_Course::update_course_progress( $user_id, $course_id );

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}
}
