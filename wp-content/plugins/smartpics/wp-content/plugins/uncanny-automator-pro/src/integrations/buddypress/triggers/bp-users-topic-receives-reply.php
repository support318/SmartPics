<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class BP_USERS_TOPIC_RECEIVES_REPLY
 *
 * @package Uncanny_Automator_Pro
 */
class BP_USERS_TOPIC_RECEIVES_REPLY {

	use Recipe\Triggers;

	public $bp_tokens = null;

	public function __construct() {
		$this->bp_tokens = new Bp_Pro_Tokens( false );
		$this->set_helper( new Buddypress_Pro_Helpers() );
		if ( class_exists( 'bbPress' ) ) {
			$this->setup_trigger();
		}
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'BP' );
		$this->set_trigger_code( 'BP_REPLY_IN_FORUM' );
		$this->set_trigger_meta( 'BP_FORUMS' );
		$this->set_is_pro( true );
		$this->set_sentence(
		/* Translators: Trigger sentence - BuddyPress */
			sprintf( __( "A user's topic in {{a forum:%1\$s}} receives a reply", 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		$this->set_readable_sentence(
			esc_html__( "A user's topic in {{a forum}} receives a reply", 'uncanny-automator-pro' )
		);
		$this->set_action_hook( 'bbp_new_reply' );
		if ( null !== $this->bp_tokens ) {
			$this->set_tokens( ( new Bp_Pro_Tokens( false ) )->user_forum_topic_tokens() );
		}
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_args_count( 7 );
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
					$this->get_helper()->list_buddypress_forums(
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
		list( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author_id ) = $args[0];
		if ( 0 !== $reply_id && ! empty( $reply_id ) ) {
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
		list( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author_id ) = $data;
		// Set the user to complete with the one we are editing instead of current login user.
		if ( 0 !== $reply_author_id && ! empty( $reply_author_id ) ) {
			$this->set_user_id( absint( $reply_author_id ) );
		}
		$this->set_conditional_trigger( true );
	}

	/**
	 * @param $args
	 *
	 * @return mixed
	 */
	public function validate_conditions( $args ) {
		list( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author_id ) = $args;

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( absint( $forum_id ) ) )
					->format( array( 'trim' ) )->get();

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

		return $this->bp_tokens->hydrate_user_forum_topic_tokens( $parsed, $args, $trigger );

	}

}
