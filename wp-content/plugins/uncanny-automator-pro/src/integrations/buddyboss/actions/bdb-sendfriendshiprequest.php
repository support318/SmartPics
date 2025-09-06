<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_SENDFRIENDSHIPREQUEST
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_SENDFRIENDSHIPREQUEST {

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
		$this->action_code = 'BDBSENDFRIENDSHIPREQUEST';
		$this->action_meta = 'BDBUSERS';

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
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyBoss */
			'sentence'           => sprintf( esc_attr__( 'Send a friendship request to {{a user:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyBoss */
			'select_option_name' => esc_attr__( 'Send a friendship request to {{a user}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'bdb_send_friendship_request' ),
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
				'options' => array(
					Automator()->helpers->recipe->buddyboss->options->all_buddyboss_users(),
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
	public function bdb_send_friendship_request( $user_id, $action_data, $recipe_id, $args ) {

		$friend_userid = $action_data['meta'][ $this->action_meta ];

		if ( function_exists( 'friends_add_friend' ) ) {
			$send = friends_add_friend( $user_id, $friend_userid );
			if ( $send === false ) {
				$action_data['complete_with_errors'] = true;
				Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'We are unable to send friendship request to selected user.', 'uncanny-automator-pro' ) );
			} else {
				Automator()->complete_action( $user_id, $action_data, $recipe_id );
			}
		} else {
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'BuddyBoss connection module is not active.', 'uncanny-automator-pro' ) );
		}
	}

}
