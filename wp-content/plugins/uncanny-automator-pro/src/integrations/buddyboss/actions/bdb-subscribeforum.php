<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_SUBSCRIBEFORUM
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_SUBSCRIBEFORUM extends \Uncanny_Automator\Recipe\Action {

	/**
	 * Setup action
	 *
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->set_integration( 'BDB' );
		$this->set_action_code( 'BDBSUBSCRIBEFORUM' );
		$this->set_action_meta( 'BDBSUBSCRIBE' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );
		$this->set_sentence(
			sprintf(
				// translators: %1$s - BuddyBoss forum name.
				esc_attr_x( 'Subscribe the user to {{a forum:%1$s}}', 'BuddyBoss', 'uncanny-automator-pro' ),
				$this->get_action_meta()
			)
		);
		$this->set_readable_sentence( esc_attr_x( 'Subscribe the user to {{a forum}}', 'BuddyBoss', 'uncanny-automator-pro' ) );
		$this->set_author( Automator()->get_author_name() );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'integration/buddyboss/' ) );
	}

	/**
	 * Define the Action's options
	 *
	 * @return void
	 */
	public function options() {
		return array(
			Automator()->helpers->recipe->buddyboss->options->pro->list_buddyboss_forums(
				null,
				$this->get_action_meta(),
				array(
					'support_custom'  => true,
					'format_options'  => true,
					'relevant_tokens' => array(),
				),
				true
			),
		);
	}

	/**
	 * Define tokens.
	 *
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
	 * Subscribe to BuddyBoss Forum
	 *
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param       $parsed
	 *
	 * @return void
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		if ( bbp_is_subscriptions_active() === false ) {
			return;
		}

		$helper = Automator()->helpers->recipe->buddyboss->options->pro;

		// Get the forum(s) value.
		$forums = sanitize_text_field( $this->get_parsed_meta_value( $this->get_action_meta(), '' ) );
		$forums = $helper->normalize_multiselect_forum_ids( $forums );

		// No forums provided.
		if ( empty( $forums ) ) {
			$this->add_log_error( esc_attr_x( 'Please enter a valid forum ID.', 'BuddyBoss', 'uncanny-automator-pro' ) );
			return false;
		}

		// Collect errors.
		$errors = array(
			'forums'  => array(),
			'access'  => array(),
			'success' => array(),
		);

		// Validate forum IDs.
		$validated = $helper->validate_forum_ids( $forums );
		$forums    = $validated['valid'];
		$invalid   = $validated['invalid'];

		// All are invalid.
		if ( empty( $forums ) ) {
			$this->add_log_error( esc_attr_x( 'All provided forum IDs are invalid.', 'BuddyBoss', 'uncanny-automator-pro' ) );
			return false;
		}

		// Add any invalid forum IDs to errors.
		$errors['forums'] = $invalid;

		// Validate user is not already subscribed to all valid forums.
		$validated = $helper->validate_user_forum_subscriptions( $user_id, $forums );
		$forums    = $validated['not-subscribed'];

		// Add invalid subscription forum IDs to errors.
		$errors['access'] = $validated['subscribed'];

		// User is already subscribed to all forums.
		if ( empty( $forums ) ) {
			$this->generate_error_message( $errors );
			return false;
		}

		// Subscribe user to forums.
		$success = false;
		foreach ( $forums as $forum_id ) {
			// Add unsuccessful forum IDs to errors.
			$subscribed = bbp_add_user_subscription( $user_id, $forum_id );
			if ( false === $subscribed ) {
				$errors['success'][] = $forum_id;
			} else {
				$success = true;
			}
			// Do additional subscriptions actions
			do_action( 'bbp_subscriptions_handler', $subscribed, $user_id, $forum_id, 'bbp_subscribe' );
		}

		// Generate any error messages ( ignored if no errors ).
		$error = $this->generate_error_message( $errors );

		// Return false if none were successful.
		if ( ! $success ) {
			return false;
		}

		// Hydrate tokens.
		$this->hydrate_tokens(
			array(
				'FORUM_ID'    => implode( ', ', $forums ),
				'FORUM_TITLE' => implode( ', ', array_map( 'bbp_get_forum_title', $forums ) ),
			)
		);

		// Have errors but also have success.
		if ( $error && $success ) {
			$this->set_complete_with_notice( true );
			return null;
		}

		// All good.
		return true;
	}

	/**
	 * Generate error message
	 *
	 * @param array $errors
	 *
	 * @return bool - false if no errors
	 */
	private function generate_error_message( $errors ) {
		$messages = array();

		if ( ! empty( $errors['forums'] ) ) {
			$messages[] = sprintf(
				esc_attr_x( 'Invalid forum IDs provided : %s', 'BuddyBoss', 'uncanny-automator-pro' ),
				implode( ', ', $errors['forums'] )
			);
		}

		if ( ! empty( $errors['access'] ) ) {
			$messages[] = sprintf(
				esc_attr_x( 'The user is already subscribed to the forum(s) : %s', 'BuddyBoss', 'uncanny-automator-pro' ),
				implode( ', ', $errors['access'] )
			);
		}

		if ( ! empty( $errors['success'] ) ) {
			$messages[] = sprintf(
				esc_attr_x( 'There was a problem subscribing the user to the forum(s) : %s', 'BuddyBoss', 'uncanny-automator-pro' ),
				implode( ', ', $errors['success'] )
			);
		}

		if ( ! empty( $messages ) ) {
			$this->add_log_error( implode( ' ', $messages ) );
			return true;
		}

		return false;
	}

}
