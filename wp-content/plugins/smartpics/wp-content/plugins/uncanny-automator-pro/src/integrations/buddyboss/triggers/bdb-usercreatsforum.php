<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class BDB_USERCREATSFORUM {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_USERCREATSFORUM';

	protected $helper;

	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDB_USERCREATSFORUM_META';

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
			esc_html__( 'A user creates a forum', 'uncanny-automator-pro' )
		);

		$this->set_readable_sentence(
			esc_html__( 'A user creates a forum', 'uncanny-automator-pro' )
		);

		$this->add_action( 'bbp_new_forum' );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->common_tokens() );

		}

		$this->set_action_args_count( 1 );

		$this->register_trigger();

	}

	public function validate_trigger( ...$args ) {

		list( $forum_data ) = $args[0];

		if ( isset( $forum_data['forum_id'] ) && 'forum' === get_post_type( $forum_data['forum_id'] ) ) {
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

		return $this->bdb_tokens->hydrate_tokens( $parsed, $args, $trigger );

	}

}
