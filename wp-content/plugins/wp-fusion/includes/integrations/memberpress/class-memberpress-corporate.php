<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles corporate accounts functionality.
 *
 * @since 3.45.0
 */
class WPF_MemberPress_Corporate {

	/**
	 * Get things started.
	 *
	 * @since 3.45.0
	 */
	public function __construct() {
		// Corporate Accounts addon
		add_action( 'mepr-txn-status-complete', array( $this, 'corporate_accounts_tagging' ) );
		add_action( 'mepr_subscription_transition_status', array( $this, 'remove_corporate_account_tags' ), 10, 3 );
	}

	/**
	 * Apply tags for corporate / sub-accounts.
	 *
	 * @param MeprTransaction $txn The transaction.
	 */
	public function corporate_accounts_tagging( $txn ) {
		if ( 'sub_account' === $txn->txn_type ) {
			$settings = get_post_meta( $txn->product_id, 'wpf-settings-memberpress', true );

			if ( ! empty( $settings['apply_tags_corporate_accounts'] ) ) {
				wp_fusion()->user->apply_tags( $settings['apply_tags_corporate_accounts'], $txn->user_id );
			}
		}
	}

	/**
	 * Removes tags from corporate sub-accounts when parent account is cancelled.
	 *
	 * @since 3.43.1
	 *
	 * @param string           $old_status The old status.
	 * @param string           $new_status The new status.
	 * @param MeprSubscription $subscription The subscription.
	 */
	public function remove_corporate_account_tags( $old_status, $new_status, $subscription ) {

		if ( 'cancelled' !== $new_status ) {
			return;
		}

		$settings = (array) get_post_meta( $subscription->product_id, 'wpf-settings-memberpress', true );

		if ( empty( $settings['remove_tags_corporate_accounts'] ) ) {
			return;
		}

		$account = MPCA_Corporate_Account::find_corporate_account_by_obj( $subscription );

		if ( empty( $account ) ) {
			return;
		}

		$sub_accounts = $account->sub_users();

		if ( empty( $sub_accounts ) ) {
			return;
		}

		// Prevent looping while we modify tags.
		remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );

		// Remove tags from the sub accounts.
		foreach ( $sub_accounts as $sub_account ) {
			wp_fusion()->user->remove_tags( $settings['apply_tags_corporate_accounts'], $sub_account->ID );
		}
	}
}
