<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_SENDPRIVATEMESSAGETOUSER
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_SENDPRIVATEMESSAGETOUSER {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BDB';

	private $action_code;
	private $action_meta;

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->action_code = 'BDBSENDPRIVATEMESSAGETOUSER';
		$this->action_meta = 'BDBGROUPS';

		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddyboss/' ),
			'is_pro'             => true,
			'requires_user'      => false,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyBoss */
			'sentence'           => sprintf( esc_attr__( 'Send {{a private message:%1$s}} to {{a specific user:%2$s}}', 'uncanny-automator-pro' ), $this->action_meta, 'BDBTOUSER' ),
			/* translators: Action - BuddyBoss */
			'select_option_name' => esc_attr__( 'Send {{a private message}} to {{a specific user}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'send_message_to_users' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * Load options for the action UI.
	 *
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->buddyboss->options->all_buddyboss_users( esc_attr__( 'Sender user', 'uncanny-automator-pro' ), 'BDBFROMUSER' ),
						Automator()->helpers->recipe->field->text_field( 'SENDPMBDBSUBJECT', esc_attr__( 'Message subject', 'uncanny-automator-pro' ), true, 'text', '', false ),
						Automator()->helpers->recipe->field->text_field( $this->action_meta . 'CONTENT', esc_attr__( 'Message content', 'uncanny-automator-pro' ), true, 'textarea' ),
					),
					'BDBTOUSER'        => array(
						Automator()->helpers->recipe->buddyboss->options->all_buddyboss_users( esc_attr__( 'Receiver user', 'uncanny-automator-pro' ), 'BDBTOUSER' ),
					),
				),
			)
		);
	}

	/**
	 * Send a private message
	 *
	 * @param string $user_id
	 * @param array $action_data
	 * @param string $recipe_id
	 *
	 * @return void
	 *
	 * @since 1.1
	 */
	public function send_message_to_users( $user_id, $action_data, $recipe_id, $args ) {

		$sender_id       = Automator()->parse->text( $action_data['meta']['BDBFROMUSER'], $recipe_id, $user_id, $args );
		$to_user         = Automator()->parse->text( $action_data['meta']['BDBTOUSER'], $recipe_id, $user_id, $args );
		$subject         = Automator()->parse->text( $action_data['meta']['SENDPMBDBSUBJECT'], $recipe_id, $user_id, $args );
		$subject         = do_shortcode( $subject );
		$message_content = $action_data['meta'][ $this->action_meta . 'CONTENT' ];
		$message_content = Automator()->parse->text( $message_content, $recipe_id, $user_id, $args );
		$message_content = do_shortcode( $message_content );

		if ( absint( $sender_id ) === absint( $to_user ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'Sender and receiver should not be the same user.', 'uncanny-automator-pro' ) );

			return;
		}

		if ( empty( $sender_id ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( "Sender doesn't exists", 'uncanny-automator-pro' ) );

			return;
		}

		if ( empty( $to_user ) ) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( "Receiver doesn't exists", 'uncanny-automator-pro' ) );

			return;
		}

		if ( function_exists( 'messages_new_message' ) ) {

			// Attempt to send the message.
			$msg = array(
				'sender_id'  => $sender_id,
				'recipients' => $to_user,
				'subject'    => $subject,
				'content'    => $message_content,
				'error_type' => 'wp_error',
			);

			$send = $this->get_helper()->send_message_to_users( $msg, $to_user );

			if ( is_wp_error( $send ) ) {
				$messages = $send->get_error_messages();
				$err      = array();
				if ( $messages ) {
					foreach ( $messages as $msg ) {
						$err[] = $msg;
					}
				}
				$action_data['complete_with_errors'] = true;
				Automator()->complete_action( $user_id, $action_data, $recipe_id, join( ', ', $err ) );
			} else {
				Automator()->complete_action( $user_id, $action_data, $recipe_id );
			}
		} else {
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'BuddyBoss message module is not active.', 'uncanny-automator-pro' ) );
		}

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
