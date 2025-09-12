<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class LD_UNENROLLFROMALLCOURSES_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class LD_UNENROLLFROMALLCOURSES_GROUP {

	use Recipe\Actions;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'LD' );
		$this->set_action_code( 'UNENROLLGROUPCOURSES_CODE' );
		$this->set_action_meta( 'UNENROLLGROUPCOURSES_META' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );
		/* translators: Action - LearnDash */
		$this->set_sentence( sprintf( esc_attr__( 'Unenroll the user from all courses associated with {{a group:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		/* translators: Action - LearnDash */
		$this->set_readable_sentence( esc_attr__( 'Unenroll the user from all courses associated with {{a group}}', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * callback function for action options
	 *
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array( Automator()->helpers->recipe->learndash->options->pro->all_ld_groups_with_hierarchy( __( 'Group', 'uncanny-automator' ), $this->get_action_meta() ) ),
			)
		);
	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$group = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';
		//		Validate LearnDash group
		$valid_group = learndash_validate_groups( array( $group ) );
		if ( empty( $valid_group ) ) {
			/* translators: Group ID - LearnDash */
			$this->return_error_message( $user_id, $action_data, $recipe_id, sprintf( __( 'A group matching (%s) was not found', 'uncanny-automator-pro' ), $group ) );

			return;
		}

		$this->unenroll_from_group_courses( $group, $user_id );

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * unenroll_from_group_courses()
	 *
	 * @param $group_id
	 * @param $user_id
	 *
	 * @return void
	 */
	public function unenroll_from_group_courses( $group_id, $user_id ) {
		$courses = learndash_group_enrolled_courses( $group_id );
		foreach ( $courses as $course_id ) {
			//Un-enroll from all courses
			ld_update_course_access( $user_id, $course_id, true );
		}
	}

	/**
	 * return_error_message()
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $message
	 *
	 * @return void
	 */
	public function return_error_message( $user_id, $action_data, $recipe_id, $message ) {
		$action_data['complete_with_errors'] = true;
		$action_data['do-nothing']           = true;

		return Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );
	}
}
