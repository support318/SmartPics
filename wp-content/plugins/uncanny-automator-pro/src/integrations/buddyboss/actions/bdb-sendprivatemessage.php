<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_SENDPRIVATEMESSAGE
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_SENDPRIVATEMESSAGE {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BDB';

	private $action_code = 'BDBSENDPRIVATEMESSAGE';

	private $action_meta = 'BDBSUBJECT';

	public function __construct() {

		$this->define_action();
	}

	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddyboss/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyBoss */
			'sentence'           => sprintf( esc_attr__( 'Send {{a private message:%1$s}} to the user', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyBoss */
			'select_option_name' => esc_attr__( 'Send {{a private message}} to the user', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'add_post_message' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->buddyboss->options->all_buddyboss_users( esc_attr__( 'Sender user', 'uncanny-automator-pro' ), 'BDBFROMUSER' ),
						Automator()->helpers->recipe->field->text_field( 'BDBSUBJECT', esc_attr__( 'Message subject', 'uncanny-automator-pro' ), true, 'text', '', false ),
						Automator()->helpers->recipe->field->text_field( 'BDBMESSAGE', esc_attr__( 'Message content', 'uncanny-automator-pro' ), true, 'textarea' ),
					),
				),
			)
		);
	}

	/**
	 * Send a private message
	 *
	 * @param integer $user_id
	 * @param array $action_data
	 * @param integer $recipe_id
	 *
	 * @return void
	 *
	 * @since 1.1
	 */
	public function add_post_message( $user_id, $action_data, $recipe_id, $args ) {

		// Bail early.
		if ( ! function_exists( 'messages_new_message' ) ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'BuddyBoss message module is not active.', 'uncanny-automator-pro' ) );

			return;

		}

		$sender_id = $action_data['meta']['BDBFROMUSER'];

		// Subject.
		$subject = Automator()->parse->text( $action_data['meta']['BDBSUBJECT'], $recipe_id, $user_id, $args );

		// Message content.
		$message_content = Automator()->parse->text( $action_data['meta']['BDBMESSAGE'], $recipe_id, $user_id, $args );

		// Message arguments.
		$args = array(
			'sender_id'  => absint( $sender_id ),
			'recipients' => array( $user_id ),
			'subject'    => do_shortcode( $subject ),
			'content'    => do_shortcode( $message_content ),
			'error_type' => 'wp_error',
		);

		$send = $this->get_helper()->send_message_to_users( $args, $user_id );

		if ( is_wp_error( $send ) ) {

			$error_message = $send->get_error_messages();

			if ( is_array( $error_message ) ) {

				$error_message = implode( ',', array_values( $error_message ) );

			}

			$action_data['complete_with_errors'] = true;

			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;

		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );

	}

	/**
	 * Get Helper Class.
	 *
	 * @return Buddyboss_Pro_Helpers
	 */
	public function get_helper() {
		static $helper = null;
		if ( is_null( $helper ) ) {
			$helper = new Buddyboss_Pro_Helpers( false );
		}
		return $helper;
	}

}
