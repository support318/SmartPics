<?php
namespace Uncanny_Automator_Pro;

/**
 * Class Action_Tokens_Helpers
 *
 * @since 5.0
 */
class Action_Tokens_Helpers {

	/**
	 * Registers the callback method 'update_action_token_record' for 'automator_pro_async_action_after_run_execution' action hook.
	 *
	 * @return void
	 */
	public function __construct() {

		/**
		 * When the action token is created during automator_action_created, there will be no value at first.
		 * Because the action has to execute from Action Scheduler plugin. Filter 'automator_pro_async_action_after_run_execution'
		 * is in Pro to make sure we hook at the right time to update the tokens record.
		 *
		 * @since 5.0
		 **/
		add_action( 'automator_pro_async_action_after_run_execution', array( $this, 'update_action_token_record' ), 10, 1 );

	}

	/**
	 * Updates the action token record from uap_action_log_meta table.
	 *
	 * @param mixed[] $action The action arguments.
	 *
	 * @since 5.0
	 *
	 * @return int|false — The number of rows updated, or false on error.
	 */
	public function update_action_token_record( $action = array() ) {

		// Bail if empty.
		if ( empty( $action ) ) {
			return false;
		}

		$action_id     = isset( $action['action_data']['ID'] ) ? $action['action_data']['ID'] : null;
		$action_log_id = isset( $action['action_data']['action_log_id'] ) ? $action['action_data']['action_log_id'] : null;

		// Bail if either of $action_id or $action_log_id has a falsy value.
		if ( empty( $action_id ) || empty( $action_log_id ) ) {
			return false;
		}

		global $wpdb;

		return Automator()->db->update(
			$wpdb->prefix . 'uap_action_log_meta',
			array(
				// @see Uncanny_Automator\Recipe\Action_Tokens::hydrate_tokens.
				'meta_value' => apply_filters( 'automator_action_tokens_hydrate_tokens', '', $this ),
			),
			array(
				'meta_key'                => 'action_tokens',
				'automator_action_id'     => $action_id,
				'automator_action_log_id' => $action_log_id,
				'user_id'                 => $action['user_id'],
			),
			array(
				'%s', // Update format.
			),
			array(
				'%s', // String 'meta_key'.
				'%d', // Integer 'automator_action_id'.
				'%d', // Integer 'automator_action_log_id'.
				'%d', // Integer 'user_id'
			)
		);

	}

}
