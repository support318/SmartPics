<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class BDB_USERISBANNEDFROMASPECIFICTYPEOFGROUP {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'BDB_USERISBANNEDFROMASPECIFICTYPEOFGROUP';

	protected $helper;

	protected $bdb_tokens = null;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'BDB_USERISBANNEDFROMASPECIFICTYPEOFGROUP_META';

	public function __construct() {
		if ( class_exists( '\Uncanny_Automator_Pro\Bdb_Pro_Tokens' ) && class_exists( '\Uncanny_Automator_Pro\Buddyboss_Pro_Helpers' ) && function_exists( 'bp_disable_group_type_creation' ) && true === bp_disable_group_type_creation() ) {

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

		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( 'A user is banned from a {{specific type of:%1$s}} group', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );

		$this->set_readable_sentence(
			esc_html__( 'A user is banned from a {{specific type of}} group', 'uncanny-automator-pro' )
		);

		$this->add_action( 'groups_ban_member' );
		$this->set_action_priority( 12 );
		$this->set_action_args_count( 2 );

		if ( null !== $this->bdb_tokens ) {

			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->banned_user_tokens() );

		}

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_trigger();

	}

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

		if ( false === \BP_Groups_Member::check_is_banned( $user_id, $group_id ) ) {
			return false;
		}

		return true;
	}

	public function prepare_to_run( $data ) {

		// Set the user to complete with the one we are editing instead of current login user.
		$this->set_user_id( absint( $data[1] ) );

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

		return $this->bdb_tokens->banned_user_tokens_hydrate_tokens( $parsed, $args, $trigger );

	}

	/**
	 * Check email subject against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {

		list( $group_id ) = $args[0];

		$this->actual_where_values = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.
		$group_type                = '';

		if ( ! function_exists( 'bp_groups_get_group_type' ) ) {
			return array();
		}
		$group_type = bp_groups_get_group_type( absint( $group_id ) );

		// Find the group type
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $group_type ) )
					->format( array( 'trim' ) )
					->get();

	}

}
