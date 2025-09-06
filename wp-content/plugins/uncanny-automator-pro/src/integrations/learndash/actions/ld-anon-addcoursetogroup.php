<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Learndash_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class  LD_ANON_ADDCOURSETOGROUP
 *
 * @package Uncanny_Automator_Pro
 */
class LD_ANON_ADDCOURSETOGROUP {

	use Recipe\Actions;

	protected $helper;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const ACTION_CODE = 'LD_ANON_ADDCOURSETOGROUP';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const ACTION_META = 'LD_ANON_ADDCOURSETOGROUP_META';

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'Uncanny_Automator\Learndash_Helpers' ) ) {
			return;
		}
		if ( version_compare( AUTOMATOR_PLUGIN_VERSION, '4.8', '<' ) ) {
			return false;
		}
		$this->setup_action();
		$this->helper = new Learndash_Helpers();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'LD' );
		$this->set_action_code( self::ACTION_CODE );
		$this->set_action_meta( self::ACTION_META );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - Learndash */
		$this->set_sentence( sprintf( esc_attr__( 'Add {{a course:%1$s}} to {{a group:%2$s}}', 'uncanny-automator-pro' ), $this->get_action_meta(), $this->get_action_meta() . '_GROUP' ) );
		/* translators: Action - Learndash */
		$this->set_readable_sentence( esc_attr__( 'Add {{a course}} to {{a group}}', 'uncanny-automator-pro' ) );
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
					$this->helper->all_ld_groups( null, $this->get_action_meta() . '_GROUP', false, false, true, true, true ),
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
		$groups    = isset( $parsed[ $this->get_action_meta() . '_GROUP' ] ) ? json_decode( sanitize_text_field( $parsed[ $this->get_action_meta() . '_GROUP' ] ) ) : '';

		// Handle custom value token.
		if ( ! is_array( $groups ) && ! empty( $groups ) ) {
			$groups = array( $groups );
		}
		if ( ! empty( $groups ) && is_array( $groups ) ) {
			$groups = array_map( 'intval', $groups );
		}

		// Validate $course ID and $groups.
		if ( empty( $course_id ) || empty( $groups ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = esc_html_x( 'Please select at least one group and a course to perform this action.', 'LearnDash', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return null;
		}

		// Validate $course ID.
		if ( ! $this->validate_id( $course_id, 'sfwd-courses' ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = sprintf(
				/* translators: %d: Course ID */
				esc_html_x( 'Invalid Course ID: %d', 'LearnDash', 'uncanny-automator-pro' ),
				$course_id
			);
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return null;
		}

		// Remove any invalid group IDs and save them to their own array.
		$invalid_groups = array();
		foreach ( $groups as $key => $group_id ) {
			if ( ! $this->validate_id( $group_id, 'groups' ) ) {
				$invalid_groups[] = $group_id;
				unset( $groups[ $key ] );
			}
		}

		// Bail if all groups are invalid.
		if ( empty( $groups ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = $this->get_invalid_group_error( $invalid_groups );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return null;
		}

		// Process valid groups.
		foreach ( $groups as $group_id ) {
			$group_enrolled = get_post_meta( absint( $course_id ), 'learndash_group_enrolled_' . $group_id, true );
			if ( empty( $group_enrolled ) ) {
				// Review this should actually return true or false.
				ld_update_course_group_access( $course_id, $group_id );
			}
		}

		// Log any invalid groups.
		if ( ! empty( $invalid_groups ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = $this->get_invalid_group_error( $invalid_groups );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
			return null;
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Get invalid group error message.
	 *
	 * @param array $invalid_groups
	 *
	 * @return string
	 */
	private function get_invalid_group_error( $invalid_groups ) {
		if ( 1 === count( $invalid_groups ) ) {
			return sprintf(
				/* translators: %d: Group ID */
				esc_html_x( 'Invalid Group ID: %d', 'LearnDash', 'uncanny-automator-pro' ),
				$invalid_groups[0]
			);
		}

		return sprintf(
			/* translators: %s: Invalid group IDs */
			esc_html_x( 'The following group IDs are invalid: %s', 'LearnDash', 'uncanny-automator-pro' ),
			implode( ', ', $invalid_groups )
		);
	}

	/**
	 * Validate IDs are of the correct post type.
	 *
	 * @param int    $id
	 * @param string $post_type
	 *
	 * @return bool
	 */
	private function validate_id( $id, $post_type ) {
		return get_post_type( absint( $id ) ) === $post_type;
	}

}
