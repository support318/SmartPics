<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Armember_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class ARMEMBER_MEMBERSHIP_EXPIRED
 *
 * @package Uncanny_Automator
 */
class ARMEMBER_MEMBERSHIP_EXPIRED {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		//      if ( ! class_exists( 'Uncanny_Automator\Armember_Helpers' ) ) {
		//          return;
		//      }
		//      $this->setup_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_helper( new Armember_Helpers() );
		$this->set_integration( 'ARMEMBER' );
		$this->set_trigger_code( 'ARM_PLAN_EXPIRES' );
		$this->set_trigger_meta( 'ARM_ALL_PLANS' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_action_args_count( 2 );
		/* Translators: Trigger sentence - ARMember Lite - Membership Plugin */
		$this->set_sentence( sprintf( esc_html__( "A user's {{membership plan:%1\$s}} expires", 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		/* Translators: Trigger sentence - ARMember Lite - Membership Plugin */
		$this->set_readable_sentence( esc_html__( "A user's {{membership plan}} expires", 'uncanny-automator-pro' ) ); // Non-active state sentence to show
		$this->set_action_hook( 'arm_user_plan_status_action_eot' );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();
	}

	/**
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->get_helper()->get_all_plans(
						array(
							'option_code' => $this->get_trigger_meta(),
							'is_any'      => true,
						)
					),
				),
			)
		);

	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {

		list( $args, $plan_obj ) = $args[0];
		if ( isset( $args['action'] ) && 'eot' === $args['action'] ) {
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
		list( $args, $plan_obj ) = $args[0];

		// Find plan ID
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $args['plan_id'] ) )
					->format( array( 'intval' ) )
					->get();
	}

}
