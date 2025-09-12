<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WM_APPROVE_MEMBERSHIP_LEVEL
 *
 * @package Uncanny_Automator_Pro
 */
class WM_APPROVE_MEMBERSHIP_LEVEL {
	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {

		$this->setup_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'WISHLISTMEMBER' );
		$this->set_trigger_code( 'APPROVELEVEL' );
		$this->set_trigger_meta( 'WMMEMBERSHIPLEVELS' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( 'A user is approved for {{a membership level:%1$s}}', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		/* Translators: Trigger sentence */
		$this->set_readable_sentence( esc_html__( 'A user is approved for {{a membership level}}', 'uncanny-automator-pro' ) ); // Non-active state sentence to show
		$this->add_action( 'wishlistmember_approve_user_levels' );
		$this->set_action_args_count( 2 );
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
					Automator()->helpers->recipe->wishlist_member->options->wm_get_all_membership_levels( null, $this->get_trigger_meta(), array( 'any' => true ) ),
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
		$is_valid = false;

		list( $user_id, $levels ) = array_shift( $args );

		if ( isset( $user_id ) && is_array( $levels ) ) {
			$is_valid = true;
		}

		return $is_valid;

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
	 * Check Level ID against the trigger meta
	 *
	 * @param $args
	 */
	public function trigger_conditions( $args ) {
		$levels = $args[1];
		// Support "Any level" option
		$this->do_find_any( true );
		// FInd the tag in trigger meta
		$this->do_find_this( $this->get_trigger_meta() );
		$this->do_find_in( $levels[0] );
	}
}
