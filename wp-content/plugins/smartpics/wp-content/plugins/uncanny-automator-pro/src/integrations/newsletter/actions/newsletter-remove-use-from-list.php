<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class NEWSLETTER_REMOVE_USE_FROM_LIST
 *
 * @package Uncanny_Automator_Pro
 */
class NEWSLETTER_REMOVE_USE_FROM_LIST {

	use Recipe\Actions;
	use Recipe\Action_Tokens;

	/**
	 * Class constructor. Setups the action.
	 *
	 * @return void.
	 */
	public function __construct() {
		// Setup our action.
		$this->setup_action();
	}

	/**
	 * Setups our new action.
	 *
	 * @return void.
	 */
	protected function setup_action() {
		$this->set_integration( 'NEWSLETTER' );
		$this->set_action_code( 'NEWSLETTER_REMOVE_USER' );
		$this->set_action_meta( 'NEWSLETTER_LISTS' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );
		/* translators: Action - Newsletter */
		$this->set_sentence( sprintf( esc_attr__( 'Remove the user from {{a list:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		/* translators: Action - Newsletter */
		$this->set_readable_sentence( esc_attr__( 'Remove the user from {{a list}}', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'NL_LISTS'   => array(
					'name' => __( 'List(s)', 'uncanny-automator-pro' ),
					'type' => 'text',
				),
				'USER_EMAIL' => array(
					'name' => __( 'User email', 'uncanny-automator-pro' ),
					'type' => 'email',
				),
			),
			$this->get_action_code()
		);
		$this->register_action();

	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {
		$all_lists = $this->get_newsletter_list();
		$all_lists = array( '-1' => __( 'All lists', 'uncanny-automator-pro' ) ) + $all_lists;

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					array(
						'option_code'              => $this->get_action_meta(),
						/* translators: Email field */
						'label'                    => esc_attr__( 'List(s)', 'uncanny-automator-pro' ),
						'required'                 => true,
						'input_type'               => 'select',
						'supports_multiple_values' => true,
						'supports_custom_value'    => false,
						'relevant_tokens'          => array(),
						'options'                  => $all_lists,
					),
				),
			)
		);

	}

	/**
	 * Implement the process_action method.
	 *
	 * @return void.
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		// Just bail out if no user id.
		if ( empty( $user_id ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, __( 'User not found.', 'uncanny-automator-pro' ) );

			return;
		}
		// We convert literal array string to actual array.
		// Example: ["list_1, "list_2"] will be converted to array().
		$recipe_selected_list = trim(
			str_replace(
				array(
					'"',
					"'",
					'[',
					']',
				),
				'',
				sanitize_text_field( $parsed[ $this->get_action_meta() ] )
			)
		);

		if ( intval( '-1' ) === intval( $recipe_selected_list ) ) {
			$list_ids = array();
			$lists    = $this->get_newsletter_list();
			foreach ( $lists as $list => $name ) {
				$list_ids[] = $list;
			}
			$recipe_selected_list = join( ',', $list_ids );
		}
		// Actual conversion after trimming and removal of invalid characters.
		$recipe_selected_list_array = explode( ',', $recipe_selected_list );

		// Trim whatever spaces left to string.
		array_walk(
			$recipe_selected_list_array,
			function ( &$value ) {
				$value = trim( $value );
			}
		);
		$user       = get_user_by( 'ID', $user_id );
		$newsletter = \Newsletter::instance();
		//Check if the user is a "Subscriber" of the Newsletter plugin
		$subscriber_user = $newsletter->get_user( $user->user_email );
		if ( empty( $subscriber_user ) ) {
			// It's not, pass email and name
			$subscriber_user = (object) array(
				'wp_user_id' => $user_id,
				'email'      => $user->user_email,
				'name'       => sprintf( '%s %s', $user->first_name, $user->last_name ),
			);
		}

		$all_lists               = $this->get_newsletter_list();
		$removed_from_list_names = array();
		$get_subscribed_lists    = $this->get_subscribed_lists( $subscriber_user, $all_lists );
		if ( empty( $get_subscribed_lists ) ) {
			$this->complete_with_error_message( $user_id, $action_data, $recipe_id, esc_html__( 'The user is not subscribed to any list.', 'uncanny-automator-pro' ) );

			return;
		}
		if ( ! empty( $get_subscribed_lists ) && ! array_intersect( $get_subscribed_lists, $recipe_selected_list_array ) ) {
			$this->complete_with_error_message( $user_id, $action_data, $recipe_id, esc_html__( 'The user is not subscribed to the selected list(s).', 'uncanny-automator-pro' ) );

			return;
		}

		// Set value of list to 1 to add the user. 0 to remove.
		// Can also use true of false.
		foreach ( $recipe_selected_list_array as $list ) {
			if ( $subscriber_user->$list ) {
				$subscriber_user->$list = 0;
			}
			$removed_from_list_names[] = $all_lists[ $list ];
		}

		// Actually save the record.
		$subscriber = $newsletter->save_user( $subscriber_user );

		// Useful error logging when for some reason the newsletter instance has failed to save the user.
		if ( ! $subscriber ) {
			$this->complete_with_error_message( $user_id, $action_data, $recipe_id, esc_html__( 'Failed to remove the user from the list.', 'uncanny-automator-pro' ) );

			return;
		}

		$this->hydrate_tokens(
			array(
				'NL_LISTS'   => join( ', ', $removed_from_list_names ),
				'USER_EMAIL' => $user->user_email,
			)
		);

		// Otherwise, complete the action successfully.
		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Get the newsletter lists.
	 *
	 * @return $lists array The collection of list.
	 */
	private function get_newsletter_list() {

		$lists = array();

		if ( class_exists( '\Newsletter' ) ) {
			$newsletter_lists = \Newsletter::instance()->get_lists();
			if ( ! empty( $newsletter_lists ) ) {
				foreach ( $newsletter_lists as $list ) {
					$list_id           = sprintf( 'list_%d', $list->id );
					$lists[ $list_id ] = $list->name;
				}
			}
		}

		return $lists;
	}

	/**
	 * function to get the subscribed lists
	 *
	 * @param $subscriber_user
	 * @param $all_lists
	 *
	 * @return array
	 */
	private function get_subscribed_lists( $subscriber_user, $all_lists ) {
		$subscribed_lists = array();
		foreach ( $all_lists as $list_id => $list ) {
			if ( $subscriber_user->$list_id ) {
				$subscribed_lists[] = $list_id;
			}
		}

		return $subscribed_lists;
	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $error_message
	 *
	 * @return void
	 */
	private function complete_with_error_message( $user_id, $action_data, $recipe_id, $error_message ) {
		$action_data['complete_with_errors'] = true;
		Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );
	}

}
