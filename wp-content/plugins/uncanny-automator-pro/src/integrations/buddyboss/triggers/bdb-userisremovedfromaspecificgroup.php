<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class BDB_USERISREMOVEDFROMASPECIFICGROUP {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_USERISREMOVEDFROMASPECIFICGROUP';

	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDB_USERISREMOVEDFROMASPECIFICGROUP_META';

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
			sprintf( __( 'A user is removed from a {{specific type of:%1$s}} group', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);

		$this->set_readable_sentence(
			esc_html__( 'A user is removed from a {{specific type of}} group', 'uncanny-automator-pro' )
		);

		$this->add_action( 'groups_remove_member' );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->user_group_tokens() );

		}

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_args_count( 5 );

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

		list( $group_id, $user_id ) = $args[0];

		if ( $this->get_helper()->bdb_user_id_exists( absint( $user_id ) ) ) {
			return true;
		}

		return false;

	}

	public function prepare_to_run( $data ) {

		list( $group_id, $user_id ) = $data;

		// Set the user to complete with the one we are editing instead of current login user.
		if ( $this->get_helper()->bdb_user_id_exists( absint( $user_id ) ) ) {
			$this->set_user_id( absint( $user_id ) );
		}

		$this->set_conditional_trigger( true );

	}

	public function validate_conditions( $args ) {

		list( $group_id, $user_id ) = $args;

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

		return $this->bdb_tokens->hydrate_user_group_tokens( $parsed, $args, $trigger );

	}

}
