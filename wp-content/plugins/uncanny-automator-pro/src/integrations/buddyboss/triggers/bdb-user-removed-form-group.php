<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class BDB_USER_REMOVED_FORM_GROUP
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_USER_REMOVED_FORM_GROUP {

	use Recipe\Triggers;

	protected $bdb_tokens = null;

	/**
	 * Trigger construct function
	 */
	public function __construct() {
		$this->bdb_tokens = new Bdb_Pro_Tokens( false );
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'BDB' );
		$this->set_trigger_code( 'BDB_USER_REMOVED_FROM_GROUP' );
		$this->set_trigger_meta( 'BDB_GROUPS' );
		$this->set_is_pro( true );
		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_attr_x( 'A user is removed from {{a group:%1$s}}', 'BuddyBoss', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_html_x( 'A user is removed from {{a group}}', 'BuddyBoss', 'uncanny-automator-pro' ) );
		$this->set_action_hook( 'groups_remove_member' );
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
					Automator()->helpers->recipe->buddyboss->options->all_buddyboss_groups(
						_x( 'Group', 'BuddyBoss', 'uncanny-automator-pro' ),
						$this->get_trigger_meta(),
						array( 'uo_include_any' => true )
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
					->match( array( absint( $group_id ) ) )
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
