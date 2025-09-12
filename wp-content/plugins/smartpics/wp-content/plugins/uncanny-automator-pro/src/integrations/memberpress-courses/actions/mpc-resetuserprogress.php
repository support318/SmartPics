<?php

namespace Uncanny_Automator_Pro;

use memberpress\courses\models as models;

/**
 * Class MPC_RESETUSERPROGRESS
 *
 * @package Uncanny_Automator_Pro
 */
class MPC_RESETUSERPROGRESS {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'MPC';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'MPCRESETUSERPROGRESS';
		$this->action_meta = 'MPCCOURSERESET';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/memberpress-courses/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - MemberPress */
			'sentence'           => sprintf( __( 'Reset the user\'s progress in {{a course:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - MemberPress */
			'select_option_name' => __( 'Reset the user\'s progress in {{a course}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'reset_progress' ),
			'options_callback'   => array( $this, 'load_options' ),
		);
		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->memberpress_courses->all_mp_courses( null, 'MPCCOURSERESET', false ),
					),
				),
			)
		);
	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function reset_progress( $user_id, $action_data, $recipe_id, $args ) {

		$course_id       = $action_data['meta'][ $this->action_meta ];
		$user_progresses = (array) models\UserProgress::find_all_by_user_and_course( $user_id, $course_id );

		if ( count( $user_progresses ) == 0 ) {
			return;
		}

		// reset quiz attempts
		$course          = new models\Course( $course_id );
		$course_sections = (array) $course->sections();
		if ( ! empty( $course_sections ) ) {
			foreach ( $course_sections as $section ) {
				$section_lessons = $section->lessons();
				if ( ! empty( $section_lessons ) ) {
					foreach ( $section_lessons as $lesson ) {
						if ( $lesson instanceof models\Quiz ) {
							$attempts = models\Attempt::get_all( '', '', array( 'quiz_id' => $lesson->ID ) );
							if ( is_array( $attempts ) && ! empty( $attempts ) ) {
								foreach ( $attempts as $attempt ) {
									$attempt->destroy();
								}
							}
						}
					}
				}
			}
		}

		foreach ( $user_progresses as $user_progress ) {
			$user_progress->destroy();
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}

}
