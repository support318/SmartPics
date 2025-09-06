<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Armember_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class ARMEMBER_ADD_TO_MEMBERSHIP_PLAN
 *
 * @package Uncanny_Automator_Pro
 */
class ARMEMBER_ADD_TO_MEMBERSHIP_PLAN {

	use Recipe\Actions;

	/**
	 * @var \ARM_subscription_plans|\ARM_subscription_plans_Lite|string
	 */
	private $armember_subscription_class = '';
	/**
	 * @var \ARM_members|\ARM_members_Lite
	 */
	private $arm_member;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'Uncanny_Automator\Armember_Helpers' ) ) {
			return;
		}
		// If LITE version is active
		if ( defined( 'MEMBERSHIPLITE_DIR_NAME' ) && ! defined( 'MEMBERSHIP_DIR_NAME' ) ) {
			$this->armember_subscription_class = new \ARM_subscription_plans_Lite();
			$this->arm_member                  = new \ARM_members_Lite();
		}
		// If Pro version is active
		if ( defined( 'MEMBERSHIP_DIR_NAME' ) ) {
			$this->armember_subscription_class = new \ARM_subscription_plans();
			$this->arm_member                  = new \ARM_members();
		}
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_helpers( new Armember_Helpers() );
		$this->set_integration( 'ARMEMBER' );
		$this->set_action_code( 'ARM_ADD_TO_PLAN' );
		$this->set_action_meta( 'ARM_PLANS' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		/* translators: Action - ARMember */
		$this->set_sentence( sprintf( esc_attr__( 'Add the user to {{a membership plan:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - ARMember */
		$this->set_readable_sentence( esc_attr__( 'Add the user to {{a membership plan}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->get_helpers()->get_all_plans(
						array(
							'option_code'           => $this->get_action_meta(),
							'supports_custom_value' => true,
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
		$plan_id = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';

		if ( empty( $plan_id ) ) {
			$action_data['complete_with_errors'] = true;
			$message                             = __( 'Plan does not exist.', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		do_action( 'arm_before_update_user_subscription', $user_id, $plan_id );

		$this->arm_member->arm_manual_update_user_data( $user_id, $plan_id );
		$this->armember_subscription_class->arm_update_user_subscription( $user_id, $plan_id, 'Automator', false );

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

}
