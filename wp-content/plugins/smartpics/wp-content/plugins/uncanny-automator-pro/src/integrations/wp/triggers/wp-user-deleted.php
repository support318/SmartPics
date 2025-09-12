<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WP_USER_DELETED
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USER_DELETED {

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
		$this->set_integration( 'WP' );
		$this->set_trigger_code( 'WPDELETEUSER' );
		$this->set_trigger_meta( 'WPUSERS' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		/* Translators: Trigger sentence */
		$this->set_sentence( esc_html__( 'A user is deleted', 'uncanny-automator-pro' ) );
		/* Translators: Trigger sentence */
		$this->set_readable_sentence( esc_html__( 'A user is deleted', 'uncanny-automator-pro' ) ); // Non-active state sentence to show
		$this->set_action_hook( 'deleted_user' );
		$this->set_action_args_count( 3 );
		$this->register_trigger();
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		$is_valid                          = false;
		list( $user_id, $reassign, $user ) = array_shift( $args );

		if ( isset( $user_id ) && is_object( $user ) ) {
			$is_valid = true;
		}
		$this->set_user_id( $user_id );

		return $is_valid;

	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( false );
	}

}
