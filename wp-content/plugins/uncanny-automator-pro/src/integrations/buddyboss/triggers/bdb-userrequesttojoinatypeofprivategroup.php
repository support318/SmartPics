<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class BDB_USERREQUESTTOJOINATYPEOFPRIVATEGROUP {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_USERREQUESTTOJOINATYPEOFPRIVATEGROUP';

	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDB_USERREQUESTTOJOINATYPEOFPRIVATEGROUP_META';

	public function __construct() {

		if ( version_compare( AUTOMATOR_PLUGIN_VERSION, '4.7', '<' ) ) {
			return false;
		}

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

		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf( __( 'A user requests to join a {{specific type of:%1$s}} private group', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);

		$this->set_readable_sentence(
			esc_html__( 'A user requests to join a {{specific type of}} private group', 'uncanny-automator-pro' )
		);

		$this->add_action( 'groups_membership_requested' );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->user_private_group_tokens() );

		}

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_args_count( 4 );

		$this->register_trigger();

	}


	/**
	 * Load options
	 *
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->buddyboss->pro->get_groups_types(
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

		list( $user_id, $admins, $group_id, $req_id ) = $args[0];

		if ( $this->helper->bdb_user_id_exists( absint( $user_id ) ) && 0 < count( $this->helper->bdb_get_group_types() ) ) {
			return true;
		}

		return false;

	}

	public function prepare_to_run( $data ) {

		list( $user_id, $admins, $group_id, $req_id ) = $data;

		// Set the user to complete with the one we are editing instead of current login user.
		if ( $this->helper->bdb_user_id_exists( absint( $user_id ) ) ) {
			$this->set_user_id( absint( $user_id ) );
		}

		$this->set_conditional_trigger( true );

	}

	public function validate_conditions( $args ) {
		list( $user_id, $admins, $group_id, $req_id ) = $args;
		return $this->find_all( $this->trigger_recipes() )->where( array( $this->get_trigger_meta() ) )->match( array( bp_groups_get_group_type( absint( $group_id ), true ) ) )->format( array( 'trim' ) )->get();
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

		return $this->bdb_tokens->hydrate_user_private_group_tokens( $parsed, $args, $trigger );

	}

}
