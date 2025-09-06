<?php
/**
 * Contains Course Enrollment Trigger.
 *
 * @since 2.3.0
 *
 * @version 2.3.0
 * @package Uncanny_Automator_Pro
 */

namespace Uncanny_Automator_Pro;

defined( '\ABSPATH' ) || exit;

/**
 * Adds Course Enrollment as Trigger.
 *
 * @since 2.3.0
 */
class TUTORLMS_COURSEENROLLED {

	public static $integration = 'TUTORLMS';

	private $trigger_code;
	private $trigger_meta;

	/**
	 * Constructor.
	 *
	 * @since 2.3.0
	 */
	public function __construct() {
		$this->trigger_code = 'TUTORLMSCOURSEENROLLED';
		$this->trigger_meta = 'TUTORLMSCOURSE';

		// hook into automator.
		$this->define_trigger();
	}

	/**
	 * Registers Course Enrollment trigger.
	 *
	 * @since 2.3.0
	 */
	public function define_trigger() {

		// setup trigger configuration.
		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/tutor-lms/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Logged-in trigger - TutorLMS */
			'sentence'            => sprintf( __( 'A user is enrolled in {{a course:%1$s}} {{a number of:%2$s}} times', 'uncanny-automator-pro' ), $this->trigger_meta, 'NUMTIMES' ),
			/* translators: Logged-in trigger - TutorLMS */
			'select_option_name'  => __( 'A user is enrolled in {{a course}}', 'uncanny-automator-pro' ),
			'action'              => 'tutor_after_enroll',
			'priority'            => 10,
			'accepted_args'       => 2,
			'validation_function' => array( $this, 'enrolled' ),
			// very last call in WP, we need to make sure they viewed the page and didn't skip before is was fully viewable
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->tutorlms->options->all_tutorlms_courses( null, $this->trigger_meta, false, true ),
					Automator()->helpers->recipe->options->number_of_times(),
				),
			)
		);
	}

	/**
	 * Validates Trigger.
	 *
	 * @since 2.3.0
	 */
	public function enrolled( $course_id, $enrollment_id ) {

		$enrollment = get_post( $enrollment_id );

		// current user.
		$user_id = $enrollment->post_author;

		// trigger entry args.
		$args = array(
			'code'    => $this->trigger_code,
			'meta'    => $this->trigger_meta,
			'post_id' => $course_id,
			'user_id' => $user_id,
		);

		// run trigger.
		Automator()->maybe_add_trigger_entry( $args, true );
	}

}
