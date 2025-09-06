<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WPF_REMOVE_USER_GROUP
 *
 * @package Uncanny_Automator
 */
class WPF_REMOVE_USER_GROUP {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WPFORO';

	private $action_code;
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'WPFOROREMOVEUSERGROUP';
		$this->action_meta = 'FOROGROUP';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/wpforo/' ),
			'integration'        => self::$integration,
			'is_pro'             => true,
			'code'               => $this->action_code,
			/* translators: Action - wpForo */
			'sentence'           => sprintf( esc_attr_x( 'Remove the user from {{a group:%1$s}}', 'wpForo', 'uncanny-automator' ), $this->action_meta ),
			/* translators: Action - wpForo */
			'select_option_name' => esc_attr_x( 'Remove the user from {{a group}}', 'wpForo', 'uncanny-automator' ),
			'priority'           => 10,
			'accepted_args'      => 3,
			'execution_function' => array( $this, 'remove_user_from_group' ),

			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {

		$usergroups = WPF()->usergroup->get_usergroups();

		$group_options = array();
		foreach ( $usergroups as $key => $group ) {
			$group_options[ $group['groupid'] ] = $group['name'];
		}

		$option = array(
			'option_code' => 'FOROGROUP',
			'label'       => esc_attr__( 'User groups', 'uncanny-automator' ),
			'input_type'  => 'select',
			'required'    => true,
			'options'     => $group_options,
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$option,
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function remove_user_from_group( $user_id, $action_data, $recipe_id, $args ) {

		$group_id = absint( $action_data['meta'][ $this->action_meta ] );

		// Set DRY error action data.
		$error_action_data                         = $action_data;
		$error_action_data['do-nothing']           = true;
		$error_action_data['complete_with_errors'] = true;

		// Check if group ID is empty.
		if ( empty( $group_id ) ) {
			$error_msg = __( 'Invalid group ID provided.', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $error_action_data, $recipe_id, $error_msg );
			return;
		}

		// Role syncing is enabled.
		if ( $this->should_sync_role() ) {
			$error_msg = __( 'User role cannot be set when Role Syncing is on', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $error_action_data, $recipe_id, $error_msg );
			return;
		}

		// Get user group ID - returns WP_Error if method not found.
		$user_group_id = $this->get_user_group_id( $user_id );
		if ( is_wp_error( $user_group_id ) ) {
			Automator()->complete_action( $user_id, $error_action_data, $recipe_id, $user_group_id->get_error_message() );
			return;
		}

		// Check if group ID doesn't match specified group.
		if ( $group_id !== $user_group_id ) {
			$error_msg = __( 'User is not a member of the specified group', 'uncanny-automator-pro' );
			Automator()->complete_action( $user_id, $error_action_data, $recipe_id, $error_msg );
			return;
		}

		// Perform the update query to set the user's group to the default group.
		$default_group = absint( WPF()->usergroup->default_groupid );
		$sql           = 'UPDATE `' . WPF()->tables->profiles . '` SET `groupid` = %d WHERE `userid` = %d';

		// Success - reset user cache and complete action.
		if ( false !== WPF()->db->query( WPF()->db->prepare( $sql, $default_group, $user_id ) ) ) {
			WPF()->member->reset( $user_id );
			Automator()->complete_action( $user_id, $action_data, $recipe_id );
			return;
		}

		// Database Error
		$error_msg = __( 'There was a DB error while removing the user from the group', 'uncanny-automator-pro' );
		Automator()->complete_action( $user_id, $error_action_data, $recipe_id, $error_msg );
	}

	/**
	 * Get user group ID
	 * - check for changed function name in wpForo 2.0.3
	 *
	 * @param int $user_id
	 *
	 * @return mixed - Group ID results or WP_Error
	 */
	private function get_user_group_id( $user_id ) {

		// Check if new method exists.
		if ( method_exists( WPF()->member, 'get_groupid' ) ) {
			return absint( WPF()->member->get_groupid( $user_id ) );
		}

		// Check if old method exists.
		if ( method_exists( WPF()->member, 'get_usergroup' ) ) {
			return absint( WPF()->member->get_usergroup( $user_id ) );
		}

		return new \WP_Error(
			'wpforo_member_class_method_not_found',
			__( 'Method to find user group ID not found in wpForo member class', 'uncanny-automator-pro' )
		);
	}

	/**
	 * Check if the Pro role synch is enabled
	 *
	 * @return bool
	 */
	private function should_sync_role() {
		// Function migrated in wpForo 2.0.3
		if ( function_exists( 'wpforo_feature' ) ) {
			return wpforo_feature( 'role-synch' );
		}
		return wpforo_setting( 'authorization ', 'role_synch' );
	}

}
