<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_SENDNOTIFICATIONTOALLGROUPMEMBERS
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_SENDNOTIFICATIONTOALLGROUPMEMBERS {

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
		$this->action_code = 'BDBSENDNOTIFICATIONTOALLGROUPMEMBERS';
		$this->action_meta = 'BDBGROUPS';

		$this->define_action();

		// Registering custom component
		add_filter(
			'bp_notifications_get_registered_components',
			array(
				$this,
				'uo_bdb_component',
			),
			10,
			2
		);

		// BDB notification content
		add_filter(
			'bp_notifications_get_notifications_for_user',
			array(
				$this,
				'uo_bdb_notification_content',
			),
			100,
			8
		);
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
			'sentence'           => sprintf( esc_attr__( 'Send a notification to all members of {{a group:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyBoss */
			'select_option_name' => esc_attr__( 'Send a notification to all members of {{a group}}', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'send_notification_to_members' ),
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
						Automator()->helpers->recipe->buddyboss->options->all_buddyboss_groups( esc_attr__( 'Group', 'uncanny-automator-pro' ), 'BDBGROUPS' ),
						Automator()->helpers->recipe->field->text_field( 'BDBNOTIFICATIONCONTENT', esc_attr__( 'Notification content', 'uncanny-automator-pro' ), true, 'textarea' ),
						Automator()->helpers->recipe->field->text_field( 'BDBNOTIFICATIONLINK', esc_attr__( 'Notification link', 'uncanny-automator-pro' ), true, 'text', '', false ),
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
	 */
	public function send_notification_to_members( $user_id, $action_data, $recipe_id, $args ) {

		$sender_id            = $action_data['meta']['BDBFROMUSER'];
		$group_id             = $action_data['meta']['BDBGROUPS'];
		$notification_content = Automator()->parse->text( $action_data['meta']['BDBNOTIFICATIONCONTENT'], $recipe_id, $user_id, $args );
		$notification_content = apply_filters( 'automator_action_send_notification_to_members', do_shortcode( $notification_content ), $this );
		$notification_link    = Automator()->parse->text( $action_data['meta']['BDBNOTIFICATIONLINK'], $recipe_id, $user_id, $args );
		$notification_link    = do_shortcode( $notification_link );
		$members_ids          = array();

		if ( function_exists( 'groups_get_group_members' ) ) {
			$members = groups_get_group_members(
				array(
					'group_id'       => $group_id,
					'per_page'       => 999999,
					'type'           => 'last_joined',
					'exclude_banned' => true,
				)
			);

			if ( isset( $members['members'] ) ) {

				if ( function_exists( 'bp_notifications_add_notification' ) ) {

					foreach ( $members['members'] as $member ) {
						$notification_id = '';
						$notification_id = bp_notifications_add_notification(
							array(
								'user_id'           => $member->ID,
								'item_id'           => $action_data['ID'],
								'secondary_item_id' => $user_id,
								'component_name'    => 'uncanny-automator',
								'component_action'  => 'uncannyautomator_bdb_notification',
								'date_notified'     => bp_core_current_time(),
								'is_new'            => 1,
								'allow_duplicate'   => true,
							)
						);
						// Adding meta for notification display on front-end
						bp_notifications_update_meta( $notification_id, 'uo_notification_content', $notification_content );
						bp_notifications_update_meta( $notification_id, 'uo_notification_link', $notification_link );
					}

					Automator()->complete_action( $user_id, $action_data, $recipe_id );
				}
			}
		} else {
			Automator()->complete_action( $user_id, $action_data, $recipe_id, __( 'BuddyBoss message module is not active.', 'uncanny-automator-pro' ) );
		}

	}

	/**
	 * Filters active components with registered notifications callbacks.
	 *
	 * @param array $component_names Array of registered component names.
	 * @param array $active_components Array of active components.
	 *
	 * @since BuddyPress 1.9.1
	 */
	public function uo_bdb_component( $component_names, $active_components ) {

		$component_names = ! is_array( $component_names ) ? array() : $component_names;
		array_push( $component_names, 'uncanny-automator' );

		return $component_names;
	}

	/**
	 * Filters the notification content for notifications created by plugins.
	 * If your plugin extends the {@link BP_Component} class, you should use
	 * the
	 * 'notification_callback' parameter in your extended
	 * {@link BP_Component::setup_globals()} method instead.
	 *
	 * @param string $content Component action. Deprecated. Do not do checks
	 *     against this! Use the 6th parameter instead -
	 *     $component_action_name.
	 * @param int $item_id Notification item ID.
	 * @param int $secondary_item_id Notification secondary item ID.
	 * @param int $action_item_count Number of notifications with the same
	 *     action.
	 * @param string $format Format of return. Either 'string' or 'object'.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name Notification component ID.
	 * @param int $id Notification ID.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 2.6.0 Added $component_action_name, $component_name,
	 *     $id as parameters.
	 *
	 * @return string|array If $format is 'string', return a string of the
	 *     notification content. If $format is 'object', return an array
	 *     formatted like: array( 'text' => 'CONTENT', 'link' => 'LINK' )
	 */
	public function uo_bdb_notification_content( $content, $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $id ) {

		if ( $component_action_name === 'uncannyautomator_bdb_notification' ) {

			$notification_content = bp_notifications_get_meta( $id, 'uo_notification_content' );
			$notification_link    = bp_notifications_get_meta( $id, 'uo_notification_link' );
			if ( ! empty( $notification_link ) ) {
				if ( 'string' == $format ) {
					$notification_content = '<a href="' . $notification_link . '">' . $notification_content . '</a>';
				}
			}

			if ( 'string' == $format ) {
				return $notification_content;
			} elseif ( 'object' == $format ) {
				return array(
					'text' => $notification_content,
					'link' => $notification_link,
				);
			}
		}

		return $content;
	}

}
