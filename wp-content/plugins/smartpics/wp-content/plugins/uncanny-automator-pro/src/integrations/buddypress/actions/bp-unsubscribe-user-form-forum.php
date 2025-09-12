<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BP_UNSUBSCRIBE_USER_FORM_FORUM
 *
 * @package Uncanny_Automator_Pro
 */
class BP_UNSUBSCRIBE_USER_FORM_FORUM extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->set_integration( 'BP' );
		$this->set_action_code( 'BP_UNSUBSCRIBE_FORUM' );
		$this->set_action_meta( 'BP_FORUMS' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );
		$this->set_sentence( sprintf( esc_attr_x( 'Unsubscribe the user from {{a forum:%1$s}}', 'BuddyPress', 'uncanny-automator-pro' ), $this->get_action_meta(), 'EXPIRATION_DATE:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Unsubscribe the user from {{a forum}}', 'BuddyPress', 'uncanny-automator-pro' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return void
	 */
	public function options() {
		$bp_options = Automator()->helpers->recipe->buddypress->options->pro->list_buddypress_forums( esc_attr__( 'Forum', 'uncanny-automator-pro' ), $this->get_action_meta(), array( 'uo_include_any' => false ) );
		$bp_forums  = array();
		foreach ( $bp_options['options'] as $key => $option ) {
			$bp_forums[] = array(
				'text'  => $option,
				'value' => $key,
			);
		}

		return array(
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'     => $this->get_action_meta(),
					'label'           => _x( 'Forum', 'BuddyPress', 'uncanny-automator-pro' ),
					'relevant_tokens' => array(),
					'options'         => $bp_forums,
				)
			),
		);

	}

	/**
	 * @return array[]
	 */
	public function define_tokens() {
		return array(
			'FORUM_ID'    => array(
				'name' => __( 'Forum ID', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'FORUM_TITLE' => array(
				'name' => __( 'Forum title', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$bp_forum = sanitize_text_field( $parsed[ $this->get_action_meta() ] );

		if ( false === bbp_is_forum( $bp_forum ) ) {
			$this->add_log_error( esc_attr_x( 'Please enter a valid forum ID.', 'BuddyPress', 'uncanny-automator-pro' ) );

			return false;
		}

		$is_subscribed_to_forum = bbp_is_user_subscribed( $user_id, $bp_forum );
		if ( false === $is_subscribed_to_forum ) {
			$this->add_log_error( esc_attr_x( 'The user is not subscribed to the specified forum.', 'BuddyPress', 'uncanny-automator-pro' ) );

			return null;
		}

		$success = bbp_remove_user_subscription( $user_id, $bp_forum );
		// Do additional subscriptions actions
		do_action( 'bbp_subscriptions_handler', $success, $user_id, $bp_forum, 'bbp_unsubscribe' );
		if ( false === $success ) {
			$this->add_log_error( esc_attr_x( 'There was a problem unsubscribing from the specified forum!', 'BuddyPress', 'uncanny-automator-pro' ) );

			return false;
		}

		$this->hydrate_tokens(
			array(
				'FORUM_ID'    => $bp_forum,
				'FORUM_TITLE' => bbp_get_forum_title( $bp_forum ),
			)
		);

		return true;
	}
}
