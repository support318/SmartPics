<?php

namespace Uncanny_Automator_Pro\Integrations\Mailster;

/**
 * Class MAILSTER_ADD_SUBSCRIBER_TO_LIST
 * @package Uncanny_Automator_Pro
 */
class MAILSTER_REMOVE_SUBSCRIBER_FROM_LIST extends \Uncanny_Automator\Recipe\Action {

	protected $helpers;

	/**
	 * @return mixed
	 */
	protected function setup_action() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'MAILSTER' );
		$this->set_action_code( 'SUBSCRIBER_REMOVED_FROM_LIST' );
		$this->set_action_meta( 'MAILSTER_SUBSCRIBER_LIST' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		// translators: Mailster - Add subscriber to list
		$this->set_sentence( sprintf( esc_attr_x( 'Remove a subscriber from {{a Mailster list:%1$s}}', 'Mailster', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Remove a subscriber from {{a Mailster list}}', 'Mailster', 'uncanny-automator-pro' ) );
	}

	/**
	 * @return array[]
	 */
	public function define_tokens() {
		return array(
			'MAILSTER_SUBSCRIBER_LIST' => array(
				'name' => esc_html_x( 'List', 'Mailster', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'input_type'     => 'email',
				'option_code'    => 'MAILSTER_SUBSCRIBER_EMAIL',
				'required'       => true,
				'supports_token' => true,
				'label'          => esc_html_x( 'Subscriber email', 'Mailster', 'uncanny-automator-pro' ),
			),
			array(
				'input_type'            => 'select',
				'option_code'           => $this->get_action_meta(),
				'label'                 => esc_html_x( 'List', 'Mailster', 'uncanny-automator-pro' ),
				'required'              => true,
				'options'               => $this->helpers->get_all_mailster_lists(),
				'supports_custom_value' => true,
				'relevant_tokens'       => array(),
			),
		);
	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return mixed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$email   = isset( $parsed['MAILSTER_SUBSCRIBER_EMAIL'] ) ? sanitize_email( $parsed['MAILSTER_SUBSCRIBER_EMAIL'] ) : '';
		$list_id = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';
		$list    = mailster( 'lists' )->get( $list_id );
		if ( empty( $list ) ) {
			// translators: Mailster - The list(ID) does not exists
			$this->add_log_error( sprintf( esc_attr_x( 'The list(%s) does not exists.', 'Mailster', 'uncanny-automator-pro' ), $list_id ) );

			return false;
		}

		if ( ! mailster_is_email( $email ) ) {
			// translators: Mailster - The email is invalid
			$this->add_log_error( esc_attr_x( 'The email is invalid', 'Mailster', 'uncanny-automator-pro' ) );

			return false;
		}

		$subscriber = mailster_get_subscriber( $email, 'email' );

		if ( ! is_object( $subscriber ) ) {
			// translators: Mailster - The subscriber(email) is not found
			$this->add_log_error( sprintf( esc_attr_x( 'The subscriber (%1$s) is not found.', 'Mailster', 'uncanny-automator-pro' ), $subscriber->email ) );

			return false;
		}

		$current_lists = mailster( 'subscribers' )->get_lists( $subscriber->ID, true );

		if ( ! empty( $current_lists ) ) {
			if ( ! in_array( $list_id, $current_lists, true ) ) {
				// translators: Mailster - The subscriber(email) is not subscribed to the list(ID)
				$this->add_log_error( sprintf( esc_attr_x( 'The subscriber (%1$s) is not subscribed to the list (%2$s).', 'Mailster', 'uncanny-automator-pro' ), $subscriber->email, $list->name ) );

				return false;
			}
		}

		mailster( 'subscribers' )->unassign_lists( $subscriber->ID, array( $list_id ) );
		$this->hydrate_tokens( array( 'MAILSTER_SUBSCRIBER_LIST' => $list->name ) );

		return true;
	}
}
