<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Learndash_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class  LD_EXTEND_USER_COURSE_ACCESS
 *
 * @package Uncanny_Automator_Pro
 */
class LD_EXTEND_USER_COURSE_ACCESS {

	use Recipe\Actions;

	protected $helper;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const ACTION_CODE = 'LD_EXTEND_USER_COURSE_ACCESS';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const ACTION_META = 'LD_EXTEND_USER_COURSE_ACCESS_META';

	/**
	 * Set up Automator action constructor.
	 *
	 * @return void
	 */
	public function __construct() {

		if ( ! class_exists( 'Uncanny_Automator\Learndash_Helpers' ) ) {
			return;
		}

		// Check if function exists ( introduced in LD 4.8.0 )
		if ( ! function_exists( 'learndash_course_extend_user_access' ) ) {
			return;
		}

		$this->setup_action();
		$this->helper = new Learndash_Helpers();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 *
	 * @return void
	 */
	protected function setup_action() {
		$this->set_integration( 'LD' );
		$this->set_action_code( self::ACTION_CODE );
		$this->set_action_meta( self::ACTION_META );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				/* translators: %1$s is the course title, %2$d is the number of days */
				esc_attr_x( "Extend the user's access to {{a course:%1\$s}} by {{a number of:%2\$s}} days", 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' ),
				$this->get_action_meta(),
				$this->get_action_meta() . '_DAYS'
			)
		);
		$this->set_readable_sentence( esc_attr_x( "Extend the user's access to {{a course}} by {{a number of}} days", 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_action();
	}

	/**
	 * Load_options
	 *
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->helper->all_ld_courses( null, $this->get_action_meta(), false ),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => $this->get_action_meta() . '_DAYS',
							'label'       => esc_attr_x( 'Days', 'uncanny-automator-pro' ),
							'description' => esc_attr_x( "Enter the number of days to extend the user's access to the course.", 'uncanny-automator-pro' ),
							'default'     => 1,
							'min_number'  => 1,
							'tokens'      => false,
						)
					),
				),
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
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$course_id = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';
		$days      = isset( $parsed[ $this->get_action_meta() . '_DAYS' ] ) ? absint( $parsed[ $this->get_action_meta() . '_DAYS' ] ) : 1;

		// No course ID.
		if ( empty( $course_id ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = esc_html_x( 'Please select at least one course to perform this action.', 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		// No days.
		if ( empty( $days ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = esc_html_x( "Please enter the number of days to extend the user's access to the course.", 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		// Not a course post type
		if ( learndash_get_post_type_slug( 'course' ) !== get_post_type( $course_id ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = esc_html_x( 'The course does not exist.', 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		// Check if user has access to the course.
		$has_access = sfwd_lms_has_access_fn( $course_id, $user_id );

		// No access.
		if ( ! $has_access ) {
			// Enroll the user or return error.
			if ( false === $this->enroll_user_to_course( $user_id, $course_id ) ) {
				$action_data['complete_with_errors'] = true;
				$error_message                       = esc_html_x( 'Error enabling user course enrollment.', 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' );
				Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
				return;
			}
		}

		// Check for access group ID.
		$access_by_group_id = $this->user_course_access_from_group_id( $user_id, $course_id );
		if ( ! empty( $access_by_group_id ) ) {
			// Bail if access is from a group.
			$action_data['complete_with_errors'] = true;
			$error_message                       = esc_html_x( 'Action does not support extending access granted by groups.', 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		// Check if the course is using expire access.
		$expire_access = learndash_get_setting( $course_id, 'expire_access' );

		// No course expire access.
		if ( empty( $expire_access ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = esc_html_x( 'The course is not using expire access.', 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return;
		}

		// Extend the user course expiration.
		$extended_timestamp = $this->set_user_course_expiration( $user_id, $course_id, $days );

		// Log the extended timestamp.
		Automator()->helpers->recipe->set_log_properties(
			array(
				array(
					'type'       => 'string',
					'label'      => esc_html_x( 'Extended access', 'LearnDash - Action : Extend user course access', 'uncanny-automator-pro' ),
					'value'      => learndash_adjust_date_time_display( $extended_timestamp ),
					'attributes' => array(),
				),
			)
		);

		// Complete the action.
		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Enroll the user to the course.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return bool
	 */
	private function enroll_user_to_course( $user_id, $course_id ) {

		// Check if access was expired.
		if ( ld_course_access_expired( $course_id, $user_id ) ) {
			// Delete the course expired meta.
			delete_user_meta( $user_id, 'learndash_course_expired_' . $course_id );
		}

		// Enroll the user.
		return ld_update_course_access( $user_id, $course_id, false );
	}

	/**
	 * Extend the user course expiration.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @param int $days
	 *
	 * @return int - The new expiration date.
	 */
	private function set_user_course_expiration( $user_id, $course_id, $days ) {

		// Get the date it will expire.
		$access_expires = ld_course_access_expires_on( $course_id, $user_id );

		// No expiration date so we'll set it to now.
		$access_expires      = empty( $access_expires ) ? time() : $access_expires;
		$new_expiration_date = strtotime( '+' . $days . ' days', $access_expires );

		// Set extended access.
		learndash_course_extend_user_access( $course_id, array( $user_id ), $new_expiration_date );

		return $new_expiration_date;
	}

	/**
	 * Attempt to extract user group ID that granted course access.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return int - Group ID that granted access or 0 if not found.
	 */
	private function user_course_access_from_group_id( $user_id, $course_id ) {

		if ( learndash_user_group_enrolled_to_course( $user_id, $course_id ) ) {
			$user           = get_user_by( 'ID', $user_id );
			$user_group_ids = learndash_get_users_group_ids( $user_id );
			foreach ( $user_group_ids as $group_id ) {
				if ( ! learndash_group_has_course( $group_id, $course_id ) ) {
					continue;
				}
				$group_product = \LearnDash\Core\Models\Product::find( $group_id );
				if ( $group_product && $group_product->user_has_access( $user ) ) {
					return $group_id;
				}
			}
		}

		return 0;
	}

}
