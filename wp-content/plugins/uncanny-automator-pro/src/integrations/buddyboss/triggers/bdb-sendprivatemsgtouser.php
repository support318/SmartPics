<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class BDB_SENDPRIVATEMSGTOUSER {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_SENDPRIVATEMSGTOUSER';

	protected $helper;

	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDB_SENDPRIVATEMSGTOUSER_META';

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

		$this->set_is_login_required( true );

		$this->set_is_pro( true );

		$this->set_action_args_count( 1 );

		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( 'A user sends a private message to {{a user:%1$s}}', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );

		$this->set_readable_sentence(
			esc_html__( 'A user sends a private message to {{a user}}', 'uncanny-automator-pro' )
		);

		$this->add_action( 'messages_message_sent' );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->send_msg_to_user_tokens() );

		}

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_trigger();

	}

	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->buddyboss->all_buddyboss_users(
						null,
						$this->get_trigger_meta(),
						array(
							'uo_include_any'  => true,
							'relevant_tokens' => array(),
						)
					),
				),
			)
		);

	}

	public function validate_trigger( ...$args ) {

		$data_object = ( is_object( $args[0] ) ) ? (array) $args[0] : $args[0];

		if ( isset( $data_object[0] ) && isset( $data_object[0]->id ) ) {
			return true;
		}

		return false;
	}

	public function prepare_to_run( $data ) {

		// Set the user to complete with the one we are editing instead of current login user.
		if ( isset( $data[1] ) ) {
			$this->set_user_id( absint( $data[1] ) );
		}

		$this->set_conditional_trigger( true );

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

		return $this->bdb_tokens->send_msg_to_usr_hydrate_tokens( $parsed, $args, $trigger );

	}

	/**
	 * Check email subject against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $message_object ) = $args[0];

		$this->actual_where_values = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		$rec_user_id = array_values( $message_object->recipients )[0]->user_id;

		// Find the receiver user id
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $rec_user_id ) )
					->format( array( 'intval' ) )
					->get();

	}

}
