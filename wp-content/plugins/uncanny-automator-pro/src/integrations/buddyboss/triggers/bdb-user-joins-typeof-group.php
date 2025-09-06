<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class BDB_USER_JOINS_TYPEOF_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_USER_JOINS_TYPEOF_GROUP {

	use Recipe\Triggers;

	/**
	 * @var Bdb_Pro_Tokens|null
	 */
	protected $bdb_tokens = null;

	/**
	 *
	 */
	public function __construct() {
		$this->bdb_tokens = new \Uncanny_Automator_Pro\Bdb_Pro_Tokens( false );
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'BDB' );
		$this->set_trigger_code( 'BDB_USER_JOINS_GROUP_TYPE' );
		$this->set_trigger_meta( 'BDB_USER_JOINS_GROUP_TYPE_META' );
		$this->set_is_pro( true );
		/* Translators: Trigger sentence - BuddyBoss */
		$this->set_sentence( sprintf( esc_attr_x( 'A user joins a {{specific type of:%1$s}} group', 'BuddyBoss', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_html_x( 'A user joins a {{specific type of}} group', 'BuddyBoss', 'uncanny-automator-pro' ) );
		$this->set_action_hook( 'groups_join_group' );
		$this->set_action_args_count( 2 );
		if ( null !== $this->bdb_tokens ) {
			$this->set_tokens( ( new Bdb_Pro_Tokens( false ) )->user_group_tokens() );
		}
		$this->set_options_callback( array( $this, 'load_options' ) );
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

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		list( $group_id, $user_id ) = $args[0];
		if ( get_user_by( 'ID', absint( $user_id ) ) ) {
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
		list( $group_id, $user_id ) = $data;

		// Set the user to complete with the one we are editing instead of current login user.
		if ( get_user_by( 'ID', absint( $user_id ) ) ) {
			$this->set_user_id( absint( $user_id ) );
		}

		$this->set_conditional_trigger( true );
	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	public function validate_conditions( $args ) {
		list( $group_id, $user_id ) = $args;

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( bp_groups_get_group_type( absint( $group_id ), true ) ) )
					->format( array( 'trim' ) )
					->get();
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
