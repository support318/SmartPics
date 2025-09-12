<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Bp_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Bp_Integration {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BP';

	/**
	 * Add_Integration constructor.
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'migrate_bpprofile_to_bpprofiledata' ) );

		// Add middle hook to resolve the position of the arguments.
		add_action( 'groups_join_group', array( $this, 'resolve_group_join_group_args_once' ), 10, 2 );
	}

	/**
	 * Only load this integration and its triggers and actions if the related plugin is active
	 *
	 * @param $status
	 * @param $code
	 *
	 * @return bool
	 */
	public function plugin_active( $status, $code ) {

		if ( self::$integration === $code ) {
			if ( class_exists( 'BuddyPress' ) ) {
				$status = true;
			} else {
				$status = false;
			}
		}

		return $status;
	}

	/**
	 * Set the directories that the auto loader will run in
	 *
	 * @param $directory
	 *
	 * @return array
	 */
	public function add_integration_directory_func( $directory ) {

		$directory[] = dirname( __FILE__ ) . '/helpers';
		$directory[] = dirname( __FILE__ ) . '/actions';
		$directory[] = dirname( __FILE__ ) . '/triggers';
		$directory[] = dirname( __FILE__ ) . '/tokens';
		$directory[] = dirname( __FILE__ ) . '/loop-filters';

		return $directory;
	}

	/**
	 * Register the integration by pushing it into the global automator object
	 */
	public function add_integration_func() {

		Automator()->register->integration(
			self::$integration,
			array(
				'name'        => 'BuddyPress',
				'icon_16'     => \Uncanny_Automator\Utilities::get_integration_icon( 'integration-buddypress-icon-16.png' ),
				'icon_32'     => \Uncanny_Automator\Utilities::get_integration_icon( 'integration-buddypress-icon-32.png' ),
				'icon_64'     => \Uncanny_Automator\Utilities::get_integration_icon( 'integration-buddypress-icon-64.png' ),
				'logo'        => \Uncanny_Automator\Utilities::get_integration_icon( 'integration-buddypress.png' ),
				'logo_retina' => \Uncanny_Automator\Utilities::get_integration_icon( 'integration-buddypress@2x.png' ),
			)
		);
	}

	/**
	 * @return void
	 */
	public function migrate_bpprofile_to_bpprofiledata() {
		if ( 'yes' === automator_pro_get_option( 'automator_bp_profile_trigger_moved' ) ) {
			return;
		}

		global $wpdb;
		$current_triggers = $wpdb->get_results( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'code' && meta_value= 'BPSETUSERPROFILEDATA'" );
		if ( empty( $current_triggers ) ) {
			automator_pro_update_option( 'automator_bp_profile_trigger_moved', 'yes', false );

			return;
		}
		foreach ( $current_triggers as $t ) {
			$trigger_id = $t->post_id;
			$sentence   = maybe_serialize( 'Set the user\'s Xprofile {{data:BPPROFILE}}' );
			update_post_meta( $trigger_id, 'sentence', $sentence );
		}

		automator_pro_update_option( 'automator_bp_profile_trigger_moved', 'yes', false );

	}

	/**
	 * Resolves `group_join_group` with position of arguments.
	 *
	 * Ensures it runs once per run-time per group and user.
	 *
	 * @param int $user_id The user ID.
	 * @param int $group_id The group ID.
	 *
	 * @return void
	 */
	public function resolve_group_join_group_args_once( $group_id = 0, $user_id = 0 ) {

		// Track the users who have joined the group.
		global $resolve_automator_groups_join_group;

		// Initialize the array if it doesn't exist.
		if ( ! is_array( $resolve_automator_groups_join_group ) ) {
			$resolve_automator_groups_join_group = array();
		}

		// Initialize the group array if it doesn't exist.
		if ( ! isset( $resolve_automator_groups_join_group[ $group_id ] ) ) {
			$resolve_automator_groups_join_group[ $group_id ] = array();
		}

		// If the action hasn't been run for the group and user yet, run it.
		if ( ! in_array( $user_id, $resolve_automator_groups_join_group[ $group_id ] ) ) {
			do_action( 'automator_groups_join_group', $user_id, $group_id );
			$resolve_automator_groups_join_group[ $group_id ][] = $user_id;
		}

	}
}
