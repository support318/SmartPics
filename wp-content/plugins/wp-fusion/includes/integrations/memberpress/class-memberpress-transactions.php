<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles transaction-related functionality.
 *
 * @since 3.45.0
 */
class WPF_MemberPress_Transactions {

	/**
	 * When processing multiple transactions for a single user, only push the meta once.
	 *
	 * @var array Pushed meta user IDs.
	 *
	 * @since 3.42.13
	 */
	public $pushed_meta_ids = array();

	/**
	 * Get things started.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		add_action( 'mepr-signup', array( $this, 'transaction_complete' ) );                                    // It is used for processing the signup form before the logic progresses on to 'the_content'.
		add_action( 'mepr-event-non-recurring-transaction-completed', array( $this, 'transaction_complete' ) ); // No idea. See https://secure.helpscout.net/conversation/1963242594/22854/.
		add_action( 'mepr-txn-status-complete', array( $this, 'transaction_complete' ) );                       // Called after completed payment.
		add_action( 'mepr-txn-status-refunded', array( $this, 'transaction_refunded' ) );                       // Refunds.
		add_action( 'mepr-txn-status-pending', array( $this, 'transaction_pending' ) );                         // Pending.
		add_action( 'mepr-txn-transition-status', array( $this, 'sync_transaction_status' ), 10, 3 );           // Sync the transaction status.

		// Recurring transcation stuff.
		add_action( 'mepr-event-recurring-transaction-failed', array( $this, 'recurring_transaction_failed' ) );
		add_action( 'mepr-event-recurring-transaction-completed', array( $this, 'recurring_transaction_completed' ) );
		add_action( 'mepr-transaction-expired', array( $this, 'transaction_expired' ), 20 ); // 20 so MP can set the subscription status and update the meta in MeprMembersCtrl::update_txn_meta().
	}


	/**
	 * Triggered when payment for membership / product is complete (for one-time or free billing).
	 *
	 * @since 2.9.1
	 *
	 * @param MeprTransaction $txn The transaction.
	 */
	public function transaction_complete( $event ) {

		// The mepr-signup hook passes a transaction already.
		if ( is_a( $event, 'MeprTransaction' ) ) {
			$txn = $event;
		} else {
			$txn = $event->get_data();
		}

		// When someone switches between two free (lifetime) memberships, the original transaction triggers a mepr-txn-status-complete
		// with an expiration date of yesterday. If we don't quit here, the details of the expiring transaction are synced instead of
		// the new one.

		// We *do* want to sync data for fallback transactions, as these are triggered when a member falls back to a previous membership in a group.

		if ( 'complete' !== $txn->status || $txn->is_expired() ) {
			return;
		}

		if ( $txn->get_meta( 'wpf_complete', true ) ) {
			return; // was already processed.
		}

		// Lock it so we don't run two at the same time.
		$txn->update_meta( 'wpf_complete', 'pending' );

		// Logger.
		wpf_log( 'info', $txn->user_id, 'New MemberPress transaction <a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ) . '" target="_blank">#' . $txn->id . '</a> (' . current_action() . ')' );

		//
		// Get meta fields
		//

		$payment_method = $txn->payment_method();
		$product_id     = $txn->product_id;
		$member         = new MeprUser( $txn->user_id );

		$update_data = array(
			'mepr_membership_level'  => html_entity_decode( get_the_title( $product_id ) ),
			'mepr_reg_date'          => $txn->created_at,
			'mepr_payment_method'    => ! empty( $payment_method ) ? $payment_method->name : '',
			'mepr_transaction_total' => $txn->total,
			'mepr_membership_status' => wp_fusion()->integrations->memberpress->get_membership_status( $txn->user_id ),
			'mepr_total_spent'       => $member->total_spent,
		);

		// The subscription total can be different from the transaction total in cases of discounts, trials, etc.
		$subscription = $txn->subscription();

		if ( false !== $subscription ) {
			$update_data['mepr_sub_total'] = $subscription->total;
		}

		// Add expiration only if applicable
		if ( strtotime( $txn->expires_at ) >= 0 && 'subscription_confirmation' !== $txn->txn_type ) {
			$update_data['mepr_expiration'] = $txn->expires_at;
		}

		// Coupons
		if ( ! empty( $txn->coupon_id ) ) {
			$update_data['mepr_coupon'] = get_the_title( $txn->coupon_id );
		}

		// Push all meta as well to get any updated custom field values during upgrades, if it's not a new user.
		if ( ! did_action( 'wpf_user_created' ) ) {
			$user_meta   = wp_fusion()->user->get_user_meta( $txn->user_id );
			$update_data = array_merge( $user_meta, $update_data );
		}

		// Corporate account data.
		$corporate_account = get_user_meta( $txn->user_id, 'mpca_corporate_account_id', true );

		if ( ! empty( $corporate_account ) ) {

			$corporate_user = MPCA_Corporate_Account::get_one( $corporate_account );

			if ( $corporate_user ) {
				$corporate_user_id                          = $corporate_user->user_id;
				$corporate_wp_user                          = get_user_by( 'id', $corporate_user_id );
				$update_data['mepr_corporate_parent_email'] = $corporate_wp_user->user_email;
			}
		}

		if ( ! in_array( $txn->user_id, $this->pushed_meta_ids, true ) ) {

			// Only push the meta once per session per user (i.e. with order bumps).

			if ( ! wpf_get_contact_id( $txn->user_id ) ) {
				wp_fusion()->user->user_register( $txn->user_id, $update_data );
			} else {
				wp_fusion()->user->push_user_meta( $txn->user_id, $update_data );
			}

			$this->pushed_meta_ids[] = $txn->user_id;

		}

		//
		// Remove any tags from previous levels where Remove Tags is checked
		//

		$remove_tags = array();

		$user = new MeprUser( $txn->user_id );

		$transactions = $user->transactions();

		if ( ! empty( $transactions ) ) {

			$product_ids = array();

			foreach ( $transactions as $transaction ) {

				// Don't run on this one

				if ( $transaction->id === $txn->id || $transaction->product_id === $txn->product_id ) {
					continue;
				}

				// Don't remove any tags if the user is still subscribed (via concurrent memberships)

				if ( $user->is_already_subscribed_to( $transaction->product_id ) ) {
					continue;
				}

				// Don't need to do it more than once per product

				if ( in_array( $transaction->product_id, $product_ids ) ) {
					continue;
				}

				$product_ids[] = $transaction->product_id;

				$settings = get_post_meta( $transaction->product_id, 'wpf-settings-memberpress', true );

				if ( empty( $settings ) || empty( $settings['remove_tags'] ) || empty( $settings['apply_tags_registration'] ) ) {
					continue;
				}

				// If "remove tags" is checked and we're no longer at that level, remove them.

				wpf_log(
					'info',
					$txn->user_id,
					sprintf(
						// translators: %1$s: Membership level name with link
						esc_html__( 'User is no longer at level %1$s and Remove Tags is checked on that level. Removing tags.', 'wp-fusion' ),
						'<a href="' . esc_url( admin_url( 'post.php?post=' . $transaction->product_id . '&action=edit' ) ) . '">' . esc_html( get_the_title( $transaction->product_id ) ) . '</a>'
					)
				);

				$remove_tags = array_merge( $remove_tags, $settings['apply_tags_registration'] );

			}
		}

		//
		// Update tags based on the product purchased
		//

		$apply_tags = array();

		$remove_tags = array();

		$settings = get_post_meta( $txn->product_id, 'wpf-settings-memberpress', true );

		if ( ! empty( $settings ) ) {

			if ( ! empty( $settings['apply_tags_registration'] ) ) {
				$apply_tags = array_merge( $apply_tags, $settings['apply_tags_registration'] );
			}

			if ( ! empty( $settings['tag_link'] ) ) {
				$apply_tags = array_merge( $apply_tags, $settings['tag_link'] );
			}

			if ( ! empty( $settings['apply_tags_payment_failed'] ) ) {

				// Remove any failed tags.
				$remove_tags = array_merge( $remove_tags, $settings['apply_tags_payment_failed'] );

			}

			if ( ! empty( $settings['apply_tags_expired'] ) ) {

				// Remove any failed tags.
				$remove_tags = array_merge( $remove_tags, $settings['apply_tags_expired'] );

			}

			if ( ! empty( $settings['apply_tags_pending'] ) ) {

				// Remove any pending tags.
				$remove_tags = array_merge( $remove_tags, $settings['apply_tags_pending'] );

			}

			// If this transaction was against a subscription that had a trial, and is no longer in a trial, consider it "converted"
			$subscription = $txn->subscription();

			if ( false !== $subscription && true == $subscription->trial ) {

				// Figure out if it's the first real payment

				$first_payment = false;

				if ( $subscription->trial_amount > 0.00 && $subscription->txn_count == 2 ) {
					$first_payment = true;
				} elseif ( $subscription->trial_amount == 0.00 && $subscription->txn_count == 1 ) {
					$first_payment = true;
				}

				if ( true == $first_payment && ! empty( $settings['apply_tags_converted'] ) ) {

					$apply_tags = array_merge( $apply_tags, $settings['apply_tags_converted'] );

				}
			}
		}

		// Coupons
		if ( ! empty( $txn->coupon_id ) ) {

			$coupon_settings = get_post_meta( $txn->coupon_id, 'wpf-settings', true );

			if ( ! empty( $coupon_settings ) && ! empty( $coupon_settings['apply_tags_coupon'] ) ) {
				$apply_tags = array_merge( $apply_tags, $coupon_settings['apply_tags_coupon'] );
			}
		}

		// Corporate accounts

		if ( ! empty( $corporate_account ) && ! empty( $settings['apply_tags_corporate_accounts'] ) ) {
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_corporate_accounts'] );
		}

		// Make sure we aren't removing tags that are being applied
		$remove_tags = array_diff( $remove_tags, $apply_tags );

		// Prevent looping when tags are applied
		remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );

		wp_fusion()->user->remove_tags( $remove_tags, $txn->user_id );

		wp_fusion()->user->apply_tags( $apply_tags, $txn->user_id );

		add_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );

		$txn->update_meta( 'wpf_complete', current_time( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Syncs any enabled fields for a transaction.
	 *
	 * @since 3.41.46
	 *
	 * @param int $transaction_id The transaction ID.
	 */
	public function sync_transaction_fields( $transaction_id ) {

		$txn = new MeprTransaction( $transaction_id );

		if ( ! $txn->user_id ) {
			return;
		}

		$member = new MeprUser( $txn->user_id );

		// Global fields
		$update_data = array(
			'mepr_membership_level'   => html_entity_decode( get_the_title( $txn->product_id ) ),
			'mepr_reg_date'           => $txn->created_at,
			'mepr_transaction_total'  => $txn->total,
			'mepr_transaction_status' => $txn->status,
			'mepr_transaction_number' => $txn->trans_num,
			'mepr_membership_status'  => wp_fusion()->integrations->memberpress->get_membership_status( $txn->user_id ),
			'mepr_total_spent'        => $member->total_spent,
		);

		$payment_method = $txn->payment_method();

		if ( $payment_method ) {
			$update_data['mepr_payment_method'] = $payment_method->name;
		}

		// Add expiration only if applicable
		if ( strtotime( $txn->expires_at ) >= 0 && 'subscription_confirmation' !== $txn->txn_type ) {
			$update_data['mepr_expiration'] = $txn->expires_at;
		}

		// Coupons
		if ( ! empty( $txn->coupon_id ) ) {
			$update_data['mepr_coupon'] = get_the_title( $txn->coupon_id );
		}

		// Product-specific fields
		$fields = wp_fusion()->settings->get( 'contact_fields' );

		foreach ( $fields as $key => $field ) {

			if ( 0 === strpos( $key, 'mepr_' ) && false !== strpos( $key, '_' . $txn->product_id ) ) {

				// Get the base field name without the product ID
				$base_field = str_replace( '_' . $txn->product_id, '', $key );

				// If we have data for this field in the global fields, copy it to the product-specific field
				if ( isset( $update_data[ $base_field ] ) ) {
					$update_data[ $key ] = $update_data[ $base_field ];
				}
			}
		}

		wp_fusion()->user->push_user_meta( $txn->user_id, $update_data );
	}


	/**
	 * Syncs the current transaction fields for a user.
	 *
	 * @since 3.44.15
	 *
	 * @param int $user_id The user ID.
	 */
	public function sync_current_transaction_fields_for_user( $user_id ) {

		// The last parameter excludes expired transactions.
		$transactions = MeprTransaction::get_all_complete_by_user_id( $user_id, 't.created_at DESC', 1, false, true );

		if ( ! empty( $transactions ) ) {
			$this->sync_transaction_fields( $transactions[0]->id );
		}
	}

	/**
	 * Apply refunded tags.
	 *
	 * @since unknown
	 *
	 * @param MeprTransaction $txn The transaction.
	 */
	public function transaction_refunded( $txn ) {

		$settings = get_post_meta( $txn->product_id, 'wpf-settings-memberpress', true );

		remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ) );

		if ( ! empty( $settings['tag_link'] ) ) {
			wp_fusion()->user->remove_tags( $settings['tag_link'], $txn->user_id );
		}

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_refunded'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_refunded'], $txn->user_id );
		}

		add_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );
	}

	/**
	 * Apply pending tags.
	 *
	 * @since 3.40.11
	 *
	 * @param MeprTransaction $txn The transaction.
	 */
	public function transaction_pending( $txn ) {

		$settings = get_post_meta( $txn->product_id, 'wpf-settings-memberpress', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_pending'] ) ) {

			remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ) );

			wp_fusion()->user->apply_tags( $settings['apply_tags_pending'], $txn->user_id );

			add_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );

		}
	}

	/**
	 * Sync transaction statuses when they're changed.
	 *
	 * @since 3.41.43
	 *
	 * @param string          $old_status The old status.
	 * @param string          $new_status The new status.
	 * @param MeprTransaction $txn The transaction.
	 */
	public function sync_transaction_status( $old_status, $new_status, $txn ) {

		if ( $old_status === $new_status ) {
			return;
		}

		wpf_log( 'info', $txn->user_id, 'MemberPress transaction <a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ) . '" target="_blank">#' . $txn->id . '</a> status changed from <strong>' . $old_status . '</strong> to <strong>' . $new_status . '</strong>.' );

		if ( 'failed' !== $new_status ) {
			// Don't sync the details of the failed transaction, we want to keep any current transaction details instead.
			$this->sync_transaction_fields( $txn->id );
		}
	}


	/**
	 * Applies tags when a recurring transaction fails.
	 *
	 * @param MeprEvent $event The event.
	 */
	public function recurring_transaction_failed( $event ) {

		$txn = $event->get_data();

		$settings = get_post_meta( $txn->product_id, 'wpf-settings-memberpress', true );

		remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ) );

		// A payment failure removes them from the membership so we need to prevent the linked tag from re-enrolling them

		if ( ! empty( $settings['tag_link'] ) ) {
			wp_fusion()->user->remove_tags( $settings['tag_link'], $txn->user_id );
		}

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_payment_failed'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_payment_failed'], $txn->user_id );
		}

		add_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );
	}

	/**
	 * Removes tags when a recurring transaction is complete
	 *
	 * @param MeprEvent $event The event.
	 */
	public function recurring_transaction_completed( $event ) {

		$txn = $event->get_data();

		$settings = get_post_meta( $txn->product_id, 'wpf-settings-memberpress', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_payment_failed'] ) ) {
			wp_fusion()->user->remove_tags( $settings['apply_tags_payment_failed'], $txn->user_id );
		}

		if ( ! empty( $settings['apply_tags_expired'] ) ) {
			wp_fusion()->user->remove_tags( $settings['apply_tags_expired'], $txn->user_id );
		}
	}

	/**
	 * Apply expired tags.
	 *
	 * @since 3.22.3
	 * @since 3.40.28 Moved from mepr-event-transaction-expired to mepr-txn-expired hook.
	 *
	 * @param MeprTransaction $txn The expiring transaction.
	 */
	public function transaction_expired( $txn ) {

		$subscription = $txn->subscription();

		if ( strtotime( $txn->expires_at ) <= time() && ( empty( $subscription ) || $subscription->is_expired() ) ) {

			$settings = get_post_meta( $txn->product_id, 'wpf-settings-memberpress', true );

			if ( empty( $settings ) ) {
				return;
			}

			// Extra check to see if the user might have a separate active transaction to the same product.

			$member = new MeprUser( $txn->user_id );

			if ( $member->is_already_subscribed_to( $txn->product_id ) ) {

				wpf_log(
					'notice',
					$txn->user_id,
					'Transaction <a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ) . '" target="_blank">#' . $txn->id . '</a> expired for product <a href="' . get_edit_post_link( $txn->product_id ) . '" target="_blank">' . get_the_title( $txn->product_id ) . '</a>, but user still has another active subscription to the same product, so the status change will be ignored.'
				);

				return;

			}

			wpf_log( 'info', $txn->user_id, 'Transaction <a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ) . '" target="_blank">#' . $txn->id . '</a> expired for product <a href="' . get_edit_post_link( $txn->product_id ) . '" target="_blank">' . get_the_title( $txn->product_id ) . '</a>.', array( 'source' => 'memberpress' ) );

			remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );

			if ( ! empty( $settings['tag_link'] ) ) {
				wp_fusion()->user->remove_tags( $settings['tag_link'], $txn->user_id );
			}

			if ( ! empty( $settings['remove_tags'] ) ) {
				wp_fusion()->user->remove_tags( $settings['apply_tags_registration'], $txn->user_id );
			}

			if ( ! empty( $settings['apply_tags_expired'] ) ) {
				wp_fusion()->user->apply_tags( $settings['apply_tags_expired'], $txn->user_id );
			}

			add_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );

			// When a transaction expires, the member falls back to the previous membership,
			// but MemberPress doesn't have a hook or event for that unless it's in a "grouped"
			// membership, in which a "fallback" transaction is created, and "mepr-txn-status-complete"
			// is triggered, which applies the new tags and syncs the fields (see apply_tags_checkout()).

			// For non-grouped memberships, MemberPress doesn't create a fallback transaction,
			// or trigger an action, it just runs MeprUser::member_col_memberships() which
			// recalculates the "memberships" and "inactive_memberships" columns in mepr_members.
			// To apply tags based on the new membership, we need to run the same logic manually.

			$product = $txn->product();
			$group   = $product->group();

			// Only run if product doesn't belong to a group.

			if ( false === $group ) {

				$this->sync_current_transaction_fields_for_user( $txn->user_id );
				wp_fusion()->integrations->memberpress->apply_tags_for_active_memberships( $txn->user_id );

			}
		}
	}
}
