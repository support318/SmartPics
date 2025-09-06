<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class BP_USER_LEAVES_FROM_GROUPTYPE
 *
 * @package Uncanny_Automator_Pro
 */
class BP_USER_LEAVES_FROM_GROUPTYPE {

	use Recipe\Triggers;

	protected $bp_tokens = null;

	public function __construct() {
		$this->bp_tokens = new Bp_Pro_Tokens( false );
		$this->set_helper( new Buddypress_Pro_Helpers() );
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'BP' );
		$this->set_trigger_code( 'BP_USER_LEAVES_GROUPTYPE' );
		$this->set_trigger_meta( 'BP_GROUP_TYPES' );
		$this->set_is_pro( true );
		$this->set_sentence(
		/* Translators: Trigger sentence - BuddyPress */
			sprintf( __( 'A user leaves a {{specific type of:%1$s}} group', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		$this->set_readable_sentence( esc_html__( 'A user leaves a {{specific type of}} group', 'uncanny-automator-pro' ) );
		$this->set_action_hook( 'groups_leave_group' );
		if ( null !== $this->bp_tokens ) {
			$this->set_tokens( ( new Bp_Pro_Tokens( false ) )->user_group_tokens() );
		}
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_args_count( 3 );
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
					$this->get_helper()->get_bp_group_types(
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

		list( $group_id, $user_id, $group ) = $args[0];

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

		list( $group_id, $user_id, $group ) = $data;

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

		list( $group_id, $user_id, $group ) = $args;

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

		return $this->bp_tokens->hydrate_user_group_tokens( $parsed, $args, $trigger );

	}

}
