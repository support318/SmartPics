<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class BP_USER_CREATES_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class BP_USER_CREATES_GROUP {

	use Recipe\Triggers;

	protected $bp_tokens = null;

	public function __construct() {
		$this->bp_tokens = new Bp_Pro_Tokens( false );
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'BP' );
		$this->set_trigger_code( 'BP_USER_CREATES_GROUP' );
		$this->set_trigger_meta( 'BP_GROUPS' );
		$this->set_is_pro( true );
		$this->set_sentence(
		/* Translators: Trigger sentence */
			esc_attr__( 'A user creates a group', 'uncanny-automator-pro' )
		);
		$this->set_readable_sentence( esc_html__( 'A user creates a group', 'uncanny-automator-pro' ) );
		$this->set_action_hook( 'groups_create_group' );
		if ( null !== $this->bp_tokens ) {
			$this->set_tokens( ( new Bp_Pro_Tokens( false ) )->user_group_tokens( $this->get_trigger_code() ) );
		}
		$this->set_action_args_count( 3 );
		$this->register_trigger();
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		list( $group_id ) = $args[0];
		if ( isset( $group_id ) ) {
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
		return $this->bp_tokens->hydrate_user_group_tokens( $parsed, $args, $trigger );
	}

}
