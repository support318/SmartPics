<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class BP_USER_REJECTS_FRIENDSHIP_REQUEST
 *
 * @package Uncanny_Automator_Pro
 */
class BP_USER_REJECTS_FRIENDSHIP_REQUEST {
	use Recipe\Triggers;

	protected $bp_tokens = null;

	public function __construct() {
		$this->set_helper( new \Uncanny_Automator_Pro\Buddypress_Pro_Helpers() );
		$this->bp_tokens = new \Uncanny_Automator_Pro\Bp_Pro_Tokens();
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'BP' );
		$this->set_trigger_code( 'BP_REQUEST_REJECTED' );
		$this->set_trigger_meta( 'REQUEST_REJECTED' );
		$this->set_is_pro( true );
		/* Translators: Trigger sentence - BuddyPress */
		$this->set_sentence( esc_html__( 'A user rejects a friendship request', 'uncanny-automator-pro' ) );
		/* Non-active state sentence to show */
		$this->set_readable_sentence( esc_html__( 'A user rejects a friendship request', 'uncanny-automator-pro' ) );
		$this->set_action_hook( 'friends_friendship_rejected' );
		if ( null !== $this->bp_tokens ) {
			$this->set_tokens( ( new Bp_Pro_Tokens( false ) )->user_friendship_tokens() );
		}
		$this->set_action_args_count( 2 );
		$this->register_trigger();
	}

	/**
	 * Validate the trigger.
	 *
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		list( $id, $friendship_object ) = $args[0];
		if ( is_object( $friendship_object ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		list( $id, $friendship_object ) = $data;
		// Set the user to complete with the one we are editing instead of current login user.
		if ( is_object( $friendship_object ) ) {
			$this->set_user_id( absint( $friendship_object->friend_user_id ) );
		}
		$this->set_conditional_trigger( false );
	}

	/**
	 * Method parse_additional_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {
		return $this->bp_tokens->hydrate_user_friendship_tokens( $parsed, $args, $trigger );
	}
}
