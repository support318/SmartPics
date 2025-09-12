<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class BDB_USERSFRIENDSHIPACCEPTED
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_USERSFRIENDSHIPACCEPTED {
	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_USERSFRIENDSHIPACCEPTED';

	/**
	 * @var Bdb_Pro_Tokens|null
	 */
	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDBUSERS';

	/**
	 *
	 */
	public function __construct() {

		$this->set_helper( new \Uncanny_Automator_Pro\Buddyboss_Pro_Helpers( false ) );

		$this->bdb_tokens = new \Uncanny_Automator_Pro\Bdb_Pro_Tokens( false );

		$this->setup_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( 'BDB' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_is_pro( true );

		/* Translators: Trigger sentence */
		$this->set_sentence(
			esc_html__( "A user's friendship request is accepted", 'uncanny-automator-pro' )
		);

		$this->set_readable_sentence(
			esc_html__( "A user's friendship request is accepted", 'uncanny-automator-pro' )
		);

		$this->add_action( 'friends_friendship_accepted' );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->user_friendship_tokens() );

		}

		$this->set_action_args_count( 4 );

		$this->register_trigger();

	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {

		list( $id, $initiator_user_id, $friend_user_id, $friendship ) = $args[0];

		if ( ! empty( $initiator_user_id ) ) {
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

		list( $id, $initiator_user_id, $friend_user_id, $friendship ) = $data;

		// Set the user to complete with the one we are editing instead of current login user.
		//if ( is_object( $friendship_object ) ) {
		$this->set_user_id( $initiator_user_id );
		//}

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

		return $this->bdb_tokens->hydrate_users_friendship_tokens( $parsed, $args, $trigger );

	}
}
