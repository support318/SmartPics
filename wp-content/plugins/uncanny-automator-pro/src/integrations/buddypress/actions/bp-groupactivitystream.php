<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BP_GROUPACTIVITYSTREAM
 *
 * @package Uncanny_Automator_Pro
 */
class BP_GROUPACTIVITYSTREAM {

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
		$this->action_code = 'BPGROUPACTIVITYSTREAM';
		$this->action_meta = 'BPGROUPS';

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
			'requires_user'      => false,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			'sentence'           => sprintf(
				// translators: %s is a BuddyPress group
				esc_attr_x( 'Add a post to the activity stream of {{a group:%1$s}}', 'Buddypress', 'uncanny-automator-pro' ),
				$this->action_meta
			),
			'select_option_name' => esc_attr_x( 'Add a post to the activity stream of {{a group}}', 'Buddypress', 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'add_post_stream' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		$groups_argu = array(
			'uo_include_any' => true,
			'uo_any_label'   => esc_attr_x( 'All groups', 'Buddypress', 'uncanny-automator' ),
			'status'         => array( 'public', 'private', 'hidden' ),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->action_meta => array(
						Automator()->helpers->recipe->buddypress->all_buddypress_groups( null, $this->action_meta, $groups_argu ),
						Automator()->helpers->recipe->buddypress->pro->get_bp_activity_actions_list( 'BPACTION', esc_attr_x( 'Activity action', 'BuddyPress', 'uncanny-automator-pro' ) ),
						Automator()->helpers->recipe->buddypress->all_buddypress_users(
							esc_attr_x( 'Author', 'BuddyPress', 'uncanny-automator-pro' ),
							'BPAUTHOR',
							array(
								'uo_include_any' => true,
								'uo_any_label'   => esc_attr_x( 'User that completes the triggers', 'Buddypress', 'uncanny-automator-pro' ),
							)
						),
						Automator()->helpers->recipe->field->text_field( 'BPACTIONLINK', esc_attr_x( 'Activity action link', 'Buddypress', 'uncanny-automator-pro' ), true, 'url', '', false ),
						Automator()->helpers->recipe->field->text_field( 'BPCONTENT', esc_attr_x( 'Activity content', 'Buddypress', 'uncanny-automator-pro' ), true, 'textarea' ),
					),
				),
			)
		);
	}

	/**
	 * Remove from BP Groups
	 *
	 * @param string $user_id
	 * @param array $action_data
	 * @param string $recipe_id
	 *
	 * @return void
	 * @since 1.1
	 */
	public function add_post_stream( $user_id, $action_data, $recipe_id, $args ) {

		$group_id       = $action_data['meta']['BPGROUPS'];
		$action         = Automator()->parse->text( $action_data['meta']['BPACTION'], $recipe_id, $user_id, $args );
		$action         = do_shortcode( $action );
		$action_link    = Automator()->parse->text( $action_data['meta']['BPACTIONLINK'], $recipe_id, $user_id, $args );
		$action_link    = do_shortcode( $action_link );
		$action_content = $action_data['meta']['BPCONTENT'];
		$action_content = Automator()->parse->text( $action_content, $recipe_id, $user_id, $args );
		$action_content = do_shortcode( $action_content );
		$action_author  = $user_id;

		if ( isset( $action_data['meta']['BPAUTHOR'] ) ) {
			$action_author = Automator()->parse->text( $action_data['meta']['BPAUTHOR'], $recipe_id, $user_id, $args );

			if ( '-1' === intval( $action_author ) ) {
				$action_author = $user_id;
			}
		}

		if ( empty( $group_id ) ) {
			return false;
		}
		$activity     = false;
		$activity_ids = array();
		if ( '-1' === $group_id ) {
			global $wpdb;
			$statuses   = array( 'public', 'private', 'hidden' );
			$in_str_arr = array_fill( 0, count( $statuses ), '%s' );
			$in_str     = join( ',', $in_str_arr );
			$group_qry  = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_groups WHERE status IN ($in_str)", $statuses ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$results    = $wpdb->get_results( $group_qry ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $results ) {
				foreach ( $results as $result ) {
					$hide_sitewide = false;
					if ( in_array(
						$result->status,
						array(
							'private',
							'hidden',
						),
						true
					) ) {
						$hide_sitewide = true;
					}
					$activity = bp_activity_add(
						array(
							'action'        => $action,
							'content'       => $action_content,
							'primary_link'  => $action_link,
							'component'     => 'groups',
							'item_id'       => $result->id,
							'type'          => 'activity_update',
							'user_id'       => $action_author,
							'hide_sitewide' => $hide_sitewide,
							'error_type'    => 'wp_error',
						)
					);
					if ( is_wp_error( $activity ) ) {
						break;
					}
					if ( ! $activity ) {
						break;
					}
					$activity_ids[] = $activity;
				}
			}
		} else {
			global $wpdb;
			$group_qry = "SELECT * FROM {$wpdb->prefix}bp_groups WHERE id = {$group_id}";
			$results   = $wpdb->get_results( $group_qry ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $results ) {
				foreach ( $results as $result ) {
					$hide_sitewide = false;
					if ( in_array(
						$result->status,
						array(
							'private',
							'hidden',
						),
						true
					) ) {
						$hide_sitewide = true;
					}
					$activity = bp_activity_add(
						array(
							'action'        => $action,
							'content'       => $action_content,
							'primary_link'  => $action_link,
							'component'     => 'groups',
							'item_id'       => $result->id,
							'type'          => 'activity_update',
							'user_id'       => $action_author,
							'hide_sitewide' => $hide_sitewide,
							'error_type'    => 'wp_error',
						)
					);
					if ( is_wp_error( $activity ) ) {
						break;
					}
					if ( ! $activity ) {
						break;
					}
					$activity_ids[] = $activity;
				}
			}
		}

		if ( ! $activity ) {
			$activity = new \WP_Error( 'bp_activity_add', esc_html_x( 'There was an error posting to the activity stream.', 'BuddyBoss', 'uncanny-automator-pro' ) );
		}

		if ( is_wp_error( $activity ) ) {
			$action_data['complete_with_errors'] = true;
			$action_data['do-nothing']           = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $activity->get_error_message() );

			return;
		}

		// Check if action was triggered.
		if ( empty( did_action( 'bp_groups_posted_update' ) ) && ! empty( $activity_ids ) ) {
			foreach ( $activity_ids as $activity_id ) {
				do_action( 'bp_groups_posted_update', $action_content, $action_author, $group_id, $activity_id );
			}
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}
}
