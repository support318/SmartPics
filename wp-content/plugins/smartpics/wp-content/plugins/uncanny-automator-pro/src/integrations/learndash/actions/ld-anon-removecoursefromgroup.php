<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Learndash_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class  LD_ANON_REMOVECOURSEFROMGROUP
 *
 * @package Uncanny_Automator_Pro
 */
class LD_ANON_REMOVECOURSEFROMGROUP {

	use Recipe\Actions;

	protected $helper;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const ACTION_CODE = 'LD_ANON_REMOVECOURSEFROMGROUP';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const ACTION_META = 'LD_ANON_REMOVECOURSEFROMGROUP_META';

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
		$this->set_sentence( sprintf( esc_attr__( 'Remove {{a course:%1$s}} from {{a group:%2$s}}', 'uncanny-automator-pro' ), $this->get_action_meta(), $this->get_action_meta() . '_GROUP' ) );
		/* translators: Action - Learndash */
		$this->set_readable_sentence( esc_attr__( 'Remove {{a course}} from {{a group}}', 'uncanny-automator-pro' ) );
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
					$this->helper->all_ld_groups( null, $this->get_action_meta() . '_GROUP', false, false, true ),
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
		$groups    = isset( $parsed[ $this->get_action_meta() . '_GROUP' ] ) ? array_map( 'intval', json_decode( sanitize_text_field( $parsed[ $this->get_action_meta() . '_GROUP' ] ) ) ) : '';

		if ( empty( $course_id ) || empty( $groups ) ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = esc_html__( 'Please select at least one group and a course to perform this action.', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
		}

		foreach ( $groups as $group_id ) {
			$group_enrolled = get_post_meta( $course_id, 'learndash_group_enrolled_' . $group_id, true );
			if ( $group_enrolled ) {
				ld_update_course_group_access( $course_id, $group_id, true );
			}
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

}
