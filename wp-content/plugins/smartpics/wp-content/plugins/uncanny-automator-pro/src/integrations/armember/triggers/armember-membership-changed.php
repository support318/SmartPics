<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class ARMEMBER_MEMBERSHIP_CHANGED
 *
 * @package Uncanny_Automator_Pro
 */
class ARMEMBER_MEMBERSHIP_CHANGED {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		//      $this->setup_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'ARMEMBER' );
		$this->set_trigger_code( 'ARM_PLAN_CHANGED' );
		$this->set_trigger_meta( 'ARM_ALL_PLANS' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_action_args_count( 2 );
		/* Translators: Trigger sentence - ARMember Lite - Membership Plugin */
		$this->set_sentence( esc_html__( "A user's membership plan is changed", 'uncanny-automator-pro' ) );
		/* Translators: Trigger sentence - ARMember Lite - Membership Plugin */
		$this->set_readable_sentence( esc_html__( "A user's membership plan is changed", 'uncanny-automator-pro' ) ); // Non-active state sentence to show
		$this->set_action_hook( 'arm_after_user_plan_change' );
		$this->register_trigger();
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		list( $user_id, $plan_id ) = $args[0];
		if ( isset( $user_id ) ) {
			return true;
		}

		return false;

	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Check plan_id against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $user_id, $plan_id ) = $args[0];

		// Find plan ID
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $plan_id ) )
					->format( array( 'intval' ) )
					->get();
	}

}
