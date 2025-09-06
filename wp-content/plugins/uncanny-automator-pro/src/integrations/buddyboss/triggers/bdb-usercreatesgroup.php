<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class BDB_USERCREATESGROUP {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_USERCREATESGROUP';

	protected $helper;

	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDB_USERCREATESGROUP_META';

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

		$this->set_sentence(
		/* Translators: Trigger sentence */
			esc_attr__( 'A user creates a group', 'uncanny-automator-pro' )
		);

		$this->set_readable_sentence(
			esc_html__( 'A user creates a group', 'uncanny-automator-pro' )
		);

		$this->add_action( 'groups_create_group' );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->user_creates_group_tokens() );

		}

		$this->set_action_args_count( 3 );

		$this->register_trigger();

	}

	public function validate_trigger( ...$args ) {

		list( $group_id ) = $args[0];

		if ( isset( $group_id ) ) {
			return true;
		}

		return false;

	}

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

		return $this->bdb_tokens->hydrate_user_creates_group_tokens( $parsed, $args, $trigger );

	}

}
