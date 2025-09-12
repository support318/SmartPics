<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BP_SENDPRIVATEMESSAGE
 *
 * @package Uncanny_Automator_Pro
 */
class BP_SENDPRIVATEMESSAGE {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BP';

	private $action_code;
	private $action_meta;

	/**
	 * SetAutomatorTriggers constructor.
	 */
	public function __construct() {
		$this->action_code = 'BPSENDPRIVATEMESSAGE';
		$this->action_meta = 'BPSUBJECT';

		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddypress/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyPress */
			'sentence'           => sprintf( esc_attr__( 'Send {{a private message:%1$s}} to the user', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyPress */
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
						Automator()->helpers->recipe->buddypress->options->all_buddypress_users( esc_attr__( 'Sender user', 'uncanny-automator-pro' ), 'BPFROMUSER' ),
						Automator()->helpers->recipe->field->text_field( 'BPSUBJECT', esc_attr__( 'Message subject', 'uncanny-automator-pro' ), true, 'text', '', false ),
						Automator()->helpers->recipe->field->text_field( 'BPMESSAGE', esc_attr__( 'Message content', 'uncanny-automator-pro' ), true, 'textarea' ),
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
	 * @since 1.1
	 * @return void
	 *
	 */
	public function add_post_message( $user_id, $action_data, $recipe_id, $args ) {

		$sender_id       = $action_data['meta']['BPFROMUSER'];
		$subject         = Automator()->parse->text( $action_data['meta']['BPSUBJECT'], $recipe_id, $user_id, $args );
		$subject         = do_shortcode( $subject );
		$message_content = $action_data['meta']['BPMESSAGE'];
		$message_content = Automator()->parse->text( $message_content, $recipe_id, $user_id, $args );
		$message_content = do_shortcode( $message_content );

		// Attempt to send the message.
		if ( function_exists( 'messages_new_message' ) ) {
			$send = messages_new_message(
				array(
					'sender_id'  => $sender_id,
					'recipients' => array( $user_id ),
					'subject'    => $subject,
					'content'    => $message_content,
					'error_type' => 'wp_error',
				)
			);

			if ( is_wp_error( $send ) ) {
				Automator()->complete_action( $user_id, $action_data, $recipe_id, __( $send->get_error_message() ) );
			} else {
				Automator()->complete_action( $user_id, $action_data, $recipe_id );
			}
		} else {
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'BuddyPress message module is not active.', 'uncanny-automator-pro' ) );
		}
	}

}
