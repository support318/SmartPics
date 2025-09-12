<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles batch operations functionality.
 *
 * @since 3.45.0
 */
class WPF_MemberPress_Batch {

	/**
	 * Get things started.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		// Batch operations
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_memberpress_init', array( $this, 'batch_init_subscriptions' ) );
		add_action( 'wpf_batch_memberpress', array( $this, 'batch_step_subscriptions' ) );

		add_filter( 'wpf_batch_memberpress_transactions_init', array( $this, 'batch_init_transactions' ) );
		add_action( 'wpf_batch_memberpress_transactions', array( wp_fusion()->integrations->memberpress->transactions, 'sync_transaction_fields' ) );

		add_filter( 'wpf_batch_memberpress_memberships_init', array( $this, 'batch_init_memberships' ) );
		add_action( 'wpf_batch_memberpress_memberships', array( $this, 'batch_step_memberships' ) );
	}

	/**
	 * Adds Memberpress checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */
	public function export_options( $options ) {
		$options['memberpress'] = array(
			'label'   => 'MemberPress subscriptions meta',
			'title'   => 'subscriptions',
			'tooltip' => __( 'Syncs the registration date, expiration date, and membership level name for all existing MemberPress subscriptions. Does not modify tags or create new contact records.', 'wp-fusion' ),
		);

		$options['memberpress_transactions'] = array(
			'label'   => 'MemberPress transactions meta',
			'title'   => 'transactions',
			'tooltip' => __( 'Syncs the registration date, expiration date, payment method, and membership level name for all existing MemberPress transactions. Does not modify tags or create new contact records.', 'wp-fusion' ),
		);

		// Corporate accounts addon
		$corporate_account_text = '';
		if ( defined( 'MPCA_PLUGIN_NAME' ) ) {
			$corporate_account_text = __( ', Also applies tags to any corporate sub-account members based on their parent account and the settings configured on the parent membership product.', 'wp-fusion' );
		}

		$options['memberpress_memberships'] = array(
			'label'   => 'MemberPress memberships statuses',
			'title'   => 'memberships',
			'tooltip' => __( 'Updates the tags for all members based on their current membership status. Does not create new contact records' . ( ! empty( $corporate_account_text ) ? $corporate_account_text : '.' ) . '', 'wp-fusion' ),
		);

		return $options;
	}

	/**
	 * Gets subscription IDs to be processed.
	 *
	 * @return array Subscription IDs.
	 */
	public function batch_init_subscriptions() {
		$subscriptions_db = MeprSubscription::get_all();
		$subscriptions    = array();

		foreach ( $subscriptions_db as $subscription ) {
			$subscriptions[] = $subscription->id;
		}

		return $subscriptions;
	}

	/**
	 * Processes member actions in batches.
	 *
	 * @param int $subscription_id Subscription ID.
	 */
	public function batch_step_subscriptions( $subscription_id ) {
		$subscription = new MeprSubscription( $subscription_id );

		$data = array(
			'mepr_reg_date'         => $subscription->created_at,
			'mepr_expiration'       => $subscription->expires_at,
			'mepr_membership_level' => html_entity_decode( get_the_title( $subscription->product_id ) ),
			'mepr_sub_total'        => $subscription->total,
			'mepr_sub_status'       => $subscription->status,
		);

		if ( ! empty( $subscription->user_id ) ) {
			wp_fusion()->user->push_user_meta( $subscription->user_id, $data );
		}
	}

	/**
	 * Gets total transaction IDs to be processed.
	 *
	 * @return array Transaction IDs.
	 */
	public function batch_init_transactions() {
		$transactions_db = MeprTransaction::get_all();
		$transactions    = array();

		foreach ( $transactions_db as $transaction ) {
			if ( 'complete' != $transaction->status || empty( $transaction->user_id ) ) {
				continue;
			}

			$transactions[] = $transaction->id;
		}

		return $transactions;
	}

	/**
	 * Gets total members to be processed.
	 *
	 * @return array Members IDs.
	 */
	public function batch_init_memberships() {
		$members = MeprUser::all( 'ids' );
		return $members;
	}

	/**
	 * Processes member actions in batches.
	 *
	 * @param int $member_id Member ID.
	 */
	public function batch_step_memberships( $member_id ) {

		$member      = new MeprUser( $member_id );
		$product_ids = $member->current_and_prior_subscriptions();

		// Subscriptions.
		$subscriptions = $member->subscriptions();

		// Get products from transactions.

		$transactions = $member->transactions();

		$product_ids = array_merge( $product_ids, wp_list_pluck( $transactions, 'product_id' ) );
		$product_ids = array_unique( $product_ids );

		if ( empty( $product_ids ) ) {
			return;
		}

		$apply_tags = array();

		foreach ( $product_ids as $product_id ) {

			$settings = get_post_meta( $product_id, 'wpf-settings-memberpress', true );

			if ( $member->is_already_subscribed_to( $product_id ) ) {

				if ( ! empty( $settings['apply_tags_registration'] ) ) {
					$apply_tags = array_merge( $apply_tags, $settings['apply_tags_registration'] );
				}

				if ( ! empty( $settings['tag_link'] ) ) {
					$apply_tags = array_merge( $apply_tags, $settings['tag_link'] );
				}

				if ( ! empty( $settings['apply_tags_expired'] ) ) {
					wp_fusion()->user->remove_tags( $settings['apply_tags_expired'], $member_id );
				}

				// Corporate accounts
				$corporate_account = get_user_meta( $member->ID, 'mpca_corporate_account_id', true );

				if ( ! empty( $corporate_account ) && ! empty( $settings['apply_tags_corporate_accounts'] ) ) {
					$apply_tags = array_merge( $apply_tags, $settings['apply_tags_corporate_accounts'] );
				}
			} else {

				if ( ! empty( $settings['remove_tags'] ) ) {
					wp_fusion()->user->remove_tags( $settings['apply_tags_registration'], $member_id );
				}

				// Maybe apply tags based on status.

				foreach ( $subscriptions as $subscription ) {

					if ( ! is_a( $subscription, 'MeprSubscription' ) ) {
						continue;
					}

					if ( $subscription->product_id === $product_id ) {

						if ( ! empty( $settings['apply_tags_cancelled'] ) && $subscription->is_cancelled() ) {

							$apply_tags = array_merge( $apply_tags, $settings['apply_tags_cancelled'] );

						} elseif ( ! empty( $settings['apply_tags_expired'] ) && $subscription->is_expired() ) {

							$apply_tags = array_merge( $apply_tags, $settings['apply_tags_expired'] );

						} elseif ( ! empty( $settings['apply_tags_trial'] ) && $subscription->in_trial() ) {

							$apply_tags = array_merge( $apply_tags, $settings['apply_tags_trial'] );

						}
					}
				}

				// Let's get expired transactions too.

				foreach ( $transactions as $transaction ) {

					if ( ! isset( $transaction->expires_at ) || 0 === intval( $transaction->expires_at ) ) {
						continue;
					}

					if ( $transaction->product_id === $product_id ) {

						if ( ! empty( $settings['apply_tags_expired'] ) && strtotime( $transaction->expires_at ) <= time() ) {
							$apply_tags = array_merge( $apply_tags, $settings['apply_tags_expired'] );
						}
					}
				}
			}
		}

		if ( ! empty( $apply_tags ) ) {

			wp_fusion()->user->apply_tags( $apply_tags, $member_id );

		}
	}
}
