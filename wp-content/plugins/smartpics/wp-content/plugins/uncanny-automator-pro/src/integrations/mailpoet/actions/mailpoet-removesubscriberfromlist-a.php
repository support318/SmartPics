<?php

namespace Uncanny_Automator_Pro;

/**
 * Class MAILPOET_REMOVESUBSCRIBERFROMLIST_A
 *
 * @package Uncanny_Automator_Pro
 */
class MAILPOET_REMOVESUBSCRIBERFROMLIST_A {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'MAILPOET';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'UNSUBSCRIBEFROMLIST';
		$this->action_meta = 'MAILPOETLISTS';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name( $this->action_code ),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/mailpoet/' ),
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			'is_pro'             => true,
			'requires_user'      => false,
			/* translators: Action - MailPoet */
			'sentence'           => sprintf( esc_attr__( 'Remove {{a subscriber:%1$s}} from {{a list:%2$s}}', 'uncanny-automator-pro' ), 'REMOVESUBSCRIBER', $this->action_meta ),
			/* translators: Action - MailPoet */
			'select_option_name' => esc_attr__( 'Remove {{a subscriber}} from {{a list}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array(
				$this,
				'mailpoet_remove_subscriber_to_list',
			),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$mailpoet  = \MailPoet\API\API::MP( 'v1' );
		$all_lists = $mailpoet->getLists();

		foreach ( $all_lists as $list ) {
			$options[ $list['id'] ] = $list['name'];
		}

		$subscriber_status = array(
			'subscribed'   => 'Subscribed',
			'unconfirmed'  => 'Unconfirmed',
			'unsubscribed' => 'Unsubscribed',
			'inactive'     => 'Inactive',
			'bounced'      => 'Bounced',
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->mailpoet->options->get_all_mailpoet_subscribers( esc_attr__( 'Subscriber', 'uncanny-automator-pro' ), 'REMOVESUBSCRIBER' ),
					Automator()->helpers->recipe->mailpoet->options->get_all_mailpoet_lists(
						esc_attr__( 'List', 'uncanny-automator-pro' ),
						$this->action_meta,
						array( 'all_include' => true )
					),
				),
			)
		);
	}


	/**
	 * Validation function when the action is hit.
	 *
	 * @param string $user_id user id.
	 * @param array $action_data action data.
	 * @param string $recipe_id recipe id.
	 * @param array $args arguments.
	 */
	public function mailpoet_remove_subscriber_to_list( $user_id, $action_data, $recipe_id, $args ) {

		if ( ! class_exists( '\MailPoet\API\API' ) ) {
			$error_message = 'The class \MailPoet\API\API does not exist';
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		try {

			$list_ids = array();

			$list_id       = Automator()->parse->text( $action_data['meta'][ $this->action_meta ], $recipe_id, $user_id, $args );
			$subscriber_id = Automator()->parse->text( $action_data['meta']['REMOVESUBSCRIBER'], $recipe_id, $user_id, $args );
			$mailpoet      = \MailPoet\API\API::MP( 'v1' );
			$subscriber    = $mailpoet->getSubscriber( $subscriber_id );
			$subscriptions = $subscriber['subscriptions'];

			if ( ! empty( $subscriptions ) && $list_id == 'all' ) {
				foreach ( $subscriptions as $subscription ) {
					$list_ids[] = $subscription['segment_id'];
				}
			} else {
				$list_ids[] = $list_id;
			}

			$mailpoet->unsubscribeFromLists( $subscriber_id, $list_ids );
			Automator()->complete_action( $user_id, $action_data, $recipe_id );

		} catch ( \MailPoet\API\MP\v1\APIException $e ) {
			$error_message                       = $e->getMessage();
			$recipe_log_id                       = $action_data['recipe_log_id'];
			$args['do-nothing']                  = true;
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error_message, $recipe_log_id, $args );

			return;
		}
	}

}
