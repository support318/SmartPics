<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Armember_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class ARMEMBER_REMOVE_FROM_MEMBERSHIP_PLAN
 *
 * @package Uncanny_Automator_Pro
 */
class ARMEMBER_REMOVE_FROM_MEMBERSHIP_PLAN {

	use Recipe\Actions;

	/**
	 * @var \ARM_subscription_plans|\ARM_subscription_plans_Lite|string
	 */
	private $armember_subscription_class = '';

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
		}
		// If Pro version is active
		if ( defined( 'MEMBERSHIP_DIR_NAME' ) ) {
			$this->armember_subscription_class = new \ARM_subscription_plans();
		}
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_helpers( new Armember_Helpers() );
		$this->set_integration( 'ARMEMBER' );
		$this->set_action_code( 'ARM_REMOVE_TO_PLAN' );
		$this->set_action_meta( 'ARM_PLANS' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		/* translators: Action - ARMember */
		$this->set_sentence( sprintf( esc_attr__( 'Remove the user from {{a membership plan:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - ARMember */
		$this->set_readable_sentence( esc_attr__( 'Remove the user from {{a membership plan}}', 'uncanny-automator-pro' ) );

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

		$arm_subscription_plans = $this->armember_subscription_class;
		$old_plan_ids           = get_user_meta( $user_id, 'arm_user_plan_ids', true );

		if ( empty( $old_plan_ids ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$message                             = __( 'The user is not a member of any membership plan.', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		if ( ! empty( $old_plan_ids ) && ! in_array( $plan_id, $old_plan_ids, true ) ) {
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			$message                             = sprintf( __( 'The user is not a member of %s.', 'uncanny-automator-pro' ), $arm_subscription_plans->arm_get_plan_name_by_id( $plan_id ) );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $message );

			return;
		}

		$planData                       = get_user_meta( $user_id, 'arm_user_plan_' . $plan_id, true );
		$plan_detail                    = $planData['arm_current_plan_detail'];
		$planData['arm_cencelled_plan'] = 'yes';
		update_user_meta( $user_id, 'arm_user_plan_' . $plan_id, $planData );

		if ( ! empty( $plan_detail ) ) {
			$planObj = ! class_exists( '\ARM_Plan' ) ? new \ARM_Plan_Lite( 0 ) : new \ARM_Plan( 0 );
			$planObj->init( (object) $plan_detail );
		} else {
			$planObj = ! class_exists( '\ARM_Plan' ) ? new \ARM_Plan_Lite( $plan_id ) : new \ARM_Plan( $plan_id );
		}
		if ( $planObj->exists() && $planObj->is_recurring() ) {
			do_action( 'arm_cancel_subscription_gateway_action', $user_id, $plan_id );
		}

		$arm_subscription_plans->arm_add_membership_history( $user_id, $plan_id, 'cancel_subscription', array(), 'admin' );
		do_action( 'arm_cancel_subscription', $user_id, $plan_id );
		$arm_subscription_plans->arm_clear_user_plan_detail( $user_id, $plan_id );

		$user_future_plans = get_user_meta( $user_id, 'arm_user_future_plan_ids', true );
		$user_future_plans = ! empty( $user_future_plans ) ? $user_future_plans : array();

		if ( ! empty( $user_future_plans ) ) {
			if ( in_array( $plan_id, $user_future_plans ) ) {
				unset( $user_future_plans[ array_search( $plan_id, $user_future_plans ) ] );
				update_user_meta( $user_id, 'arm_user_future_plan_ids', array_values( $user_future_plans ) );
			}
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

}
