<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class BDB_USERSUSPENDED {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_USERSUSPENDED';

	protected $helper;

	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDB_USERSUSPENDED_META';

	public function __construct() {

		if ( class_exists( '\Uncanny_Automator_Pro\Bdb_Pro_Tokens' ) && class_exists( '\Uncanny_Automator_Pro\Buddyboss_Pro_Helpers' ) ) {

			$this->set_helper( new \Uncanny_Automator_Pro\Buddyboss_Pro_Helpers( false ) );

			$this->bdb_tokens = new \Uncanny_Automator_Pro\Bdb_Pro_Tokens( false );

			$this->setup_trigger();

		}

	}

	public function set_helper( $helper ) {

		$this->helper = $helper;

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
			esc_html__( 'A user is suspended', 'uncanny-automator-pro' )
		);

		$this->set_readable_sentence(
			esc_html__( 'A user is suspended', 'uncanny-automator-pro' )
		);

		$this->add_action( 'bp_suspend_hide_user' );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->user_tokens() );

		}

		$this->set_action_args_count( 2 );

		$this->register_trigger();

	}

	public function validate_trigger( ...$args ) {

		list( $member_id, $hide_sitewide ) = $args[0];

		if ( $this->helper->bdb_user_id_exists( $member_id ) === true ) {
			return true;
		}

		return false;

	}

	public function prepare_to_run( $data ) {

		// Set the user to complete with the one we are editing instead of current login user.
		if ( isset( $data[0] ) ) {
			$this->set_user_id( absint( $data[0] ) );
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

		return $this->bdb_tokens->hydrate_user_tokens( $parsed, $args, $trigger );

	}

}
