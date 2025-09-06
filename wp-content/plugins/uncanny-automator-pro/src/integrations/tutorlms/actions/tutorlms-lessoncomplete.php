<?php
/**
 * Contains Lesson Completion action.
 *
 * @since 2.3.0
 * @version 2.3.0
 *
 * @package Uncanny_Automator_Pro
 */

namespace Uncanny_Automator_Pro;

use function tutils;

/**
 * Lesson Completion Action.
 *
 * @since 2.3.0
 */
class TUTORLMS_LESSONCOMPLETE {

	/**
	 * Integration code
	 *
	 * @var string
	 * @since 2.3.0
	 */
	public static $integration = 'TUTORLMS';

	/**
	 * Action Code
	 *
	 * @var string
	 * @since 2.3.0
	 */
	private $action_code;

	/**
	 * Action Meta
	 *
	 * @var string
	 * @since 2.3.0
	 */
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		$this->action_code = 'TUTORLMSLESSONCOMPLETE';
		$this->action_meta = 'TUTORLMSLESSON';
		$this->define_action();
	}

	/**
	 * Register the action.
	 *
	 * @since 2.3.0
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/tutor-lms/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - TutorLMS */
			'sentence'           => sprintf( __( 'Mark {{a lesson:%1$s}} complete for the user', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - TutorLMS */
			'select_option_name' => __( 'Mark {{a lesson}} complete for the user', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'complete' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$args = array(
			'post_type'      => tutor()->course_post_type,
			'posts_per_page' => 999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$courses = Automator()->helpers->recipe->options->wp_query( $args, false, esc_attr__( 'Any course', 'uncanny-automator' ) );

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->field->select_field_ajax(
							'TUTORLMSCOURSE',
							__( 'Course', 'uncanny-automator' ),
							$courses,
							'',
							'',
							false,
							true,
							array(
								'target_field' => $this->action_meta,
								'endpoint'     => 'select_lesson_from_course_MARKLESSONCOMPLETED',
							)
						),
						Automator()->helpers->recipe->field->select_field( $this->action_meta, __( 'Lesson', 'uncanny-automator' ) ),
					),
				),
			)
		);
	}

	/**
	 * Completes the Lesson Completion Action.
	 *
	 * @param int $user_id User ID
	 * @param array $action_data Action information
	 * @param int $recipe_id ID of the recipe
	 *
	 * @since 2.3.0
	 */
	public function complete( $user_id, $action_data, $recipe_id, $args ) {

		$lesson_id = $action_data['meta'][ $this->action_meta ];

		$course_id = absint( $action_data['meta']['TUTORLMSCOURSE'] );

		if ( -1 === intval( $lesson_id ) ) {
			// Support all operation.
			$this->mark_all_lessons_complete( $user_id, $course_id );

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

			return;

		}

		// Otherwise, simply complete it.
		tutils()->mark_lesson_complete( $lesson_id, $user_id );

		// Finally, wrap up the proceedings!
		Automator()->complete->action( $user_id, $action_data, $recipe_id );

	}

	/**
	 * Iterates through all course' lesson and mark them as complete.
	 *
	 * @since 4.5
	 * @return void
	 */
	protected function mark_all_lessons_complete( $user_id, $course_id ) {

		$lesson_ids = tutor_utils()->get_course_content_ids_by( tutor()->lesson_post_type, tutor()->course_post_type, $course_id );

		foreach ( $lesson_ids as $lesson_id ) {
			tutils()->mark_lesson_complete( $lesson_id, $user_id );
		}

		return;

	}

}
