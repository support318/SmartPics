<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles subscription-related functionality.
 *
 * A subscription purchase creates an active subscription and a 'complete' transaction.
 * A subscription purchase with a trial creates a pending subscription and a 'pending' transaction.
 * An offline payment creates an active subscription but a 'pending' transaction.
 * A one-off purchase creates a 'complete' transaction and no subscription.
 * A offline gateway purchase with "Admin Must Manually Complete Transactions" enabled creates an active subscription but a pending transaction.
 * "Subscription is linked to Stripe, transaction is linked to membership".
 *
 * @since 3.45.0
 */
class WPF_MemberPress_Subscriptions {

	/**
	 * Get things started.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		// Subscription status changes
		add_action( 'mepr_subscription_transition_status', array( $this, 'subscription_status_changed' ), 10, 3 );

		// Upgrades and downgrades
		add_action( 'mepr-upgraded-sub', array( $this, 'upgraded_subscription' ), 10, 2 );
		add_action( 'mepr-downgraded-sub', array( $this, 'downgraded_subscription' ), 10, 2 );
	}

	/**
	 * Sends relevant data from a subscription to the connected CRM.
	 *
	 * @since unknown
	 *
	 * @param MeprSubscription $subscription The subscription object.
	 */
	public function sync_subscription_fields( $subscription ) {

		$member = new MeprUser( $subscription->user_id );

		$payment_method = $subscription->payment_method();

		// Update data.
		$update_data = array(
			'mepr_reg_date'          => $subscription->created_at,
			'mepr_payment_method'    => $payment_method->name,
			'mepr_membership_level'  => html_entity_decode( get_the_title( $subscription->product_id ) ),
			'mepr_expiration'        => ! empty( $subscription->expires_at ) ? $subscription->expires_at : null,
			'mepr_sub_status'        => $subscription->status,
			'mepr_sub_total'         => $subscription->total,
			'mepr_membership_status' => wp_fusion()->integrations->memberpress->get_membership_status( $subscription->user_id ),
			'mepr_total_spent'       => $member->total_spent,
		);

		$product = new MeprProduct( $subscription->product_id );

		$update_data['sub_product_name'] = $product->post_title;

		// Sync trial duration and expiration.
		if ( $subscription->trial ) {
			$update_data['mepr_trial_duration'] = $subscription->trial_days;
			$update_data['mepr_expiration']     = gmdate( 'c', strtotime( $subscription->created_at ) + MeprUtils::days( $subscription->trial_days ) );
		}

		// Coupon used
		if ( ! empty( $subscription->coupon_id ) ) {
			$update_data['mepr_coupon'] = get_the_title( $subscription->coupon_id );
		}

		// Push all meta as well to get any updated custom field values during upgrades, if it's not a new user.
		if ( ! did_action( 'wpf_user_created' ) && $subscription->is_upgrade() ) {
			$user_meta   = wp_fusion()->user->get_user_meta( $subscription->user_id );
			$update_data = array_merge( $user_meta, $update_data );
		}

		// Product-specific fields
		$fields = wp_fusion()->settings->get( 'contact_fields' );

		foreach ( $fields as $key => $field ) {

			// Check if this is a product-specific field for this product
			if ( 0 === strpos( $key, 'mepr_' ) && false !== strpos( $key, '_' . $subscription->product_id ) ) {

				// Get the base field name without the product ID
				$base_field = str_replace( '_' . $subscription->product_id, '', $key );

				// If we have data for this field in the global fields, copy it to the product-specific field
				if ( isset( $update_data[ $base_field ] ) ) {
					$update_data[ $key ] = $update_data[ $base_field ];
				}
			}
		}

		$update_data = apply_filters( 'wpf_memberpress_subscription_sync_fields', $update_data, $subscription );

		wp_fusion()->user->push_user_meta( $subscription->user_id, $update_data );
	}

	/**
	 * Subscription Status Changed
	 * Triggered when a subscription status is changed. Applies and removes tags based off of the status.
	 *
	 * @since 3.43.1 Added support for removing tags from corporate sub-accounts.
	 *
	 * @param string           $old_status The old status.
	 * @param string           $new_status The new status.
	 * @param MeprSubscription $subscription The subscription.
	 */
	public function subscription_status_changed( $old_status, $new_status, $subscription ) {

		// Sometimes during registration a subscription status change is triggered when there is no
		// change (i.e. from Active to Active). We can ignore these.
		//
		// NB: Until v3.40.54, this was running on new Pending subscriptions. That is now handled
		// by $this->transaction_pending().

		if ( $old_status === $new_status ) {
			return;
		}

		if ( 'pending' === $old_status && 'cancelled' === $new_status ) {
			return; // failed initial transactions (i.e. a Stripe card decline).
		}

		// Get subscription data.
		$data = $subscription->get_values();

		wpf_log(
			'info',
			$data['user_id'],
			sprintf(
				// translators: 1: MemberPress subscription ID, 2: MemberPress subscription product, 3: Old status, 4: New status.
				esc_html__( 'Memberpress subscription %1$s to %2$s status changed from %3$s to %4$s.', 'wp-fusion' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=memberpress-subscriptions&action=edit&id=' . $subscription->id ) ) . '">#' . esc_html( $subscription->id ) . '</a>',
				'<a href="' . esc_url( admin_url( 'post.php?post=' . $data['product_id'] . '&action=edit' ) ) . '">' . esc_html( get_the_title( $data['product_id'] ) ) . '</a>',
				'<strong>' . esc_html( ucwords( $old_status ) ) . '</strong>',
				'<strong>' . esc_html( ucwords( $new_status ) ) . '</strong>'
			)
		);

		// Get WPF settings
		$settings = get_post_meta( $data['product_id'], 'wpf-settings-memberpress', true );

		$defaults = array(
			'apply_tags_registration'   => array(),
			'apply_tags_pending'        => array(),
			'remove_tags'               => false,
			'tag_link'                  => array(),
			'apply_tags_suspended'      => array(),
			'apply_tags_cancelled'      => array(),
			'apply_tags_expired'        => array(),
			'apply_tags_payment_failed' => array(),
			'apply_tags_resumed'        => array(),
			'apply_tags_trial'          => array(),
			'apply_tags_converted'      => array(),
		);

		$settings = wp_parse_args( $settings, $defaults );

		$apply_tags  = array();
		$remove_tags = array();

		// Pending subscriptions.
		if ( 'pending' === $new_status ) {
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_pending'] );
		}

		// New subscriptions.
		if ( 'active' === $new_status ) {

			$payment_method = $subscription->payment_method();

			if ( $payment_method instanceof MeprArtificialGateway && $payment_method->settings->manually_complete ) {

				// This happens when using the offline payment gateway, and "Admin Must Manually Complete Transactions" is enabled,
				// an active subscription is created, but the user has no transaction, therefore the user is not "subscribed" to
				// the product.

				wpf_log( 'notice', $data['user_id'], 'Subscription status was changed to active, but the transaction requires admin approval. No tags will be applied.' );
				return;
			}

			// Apply tags.
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_registration'], $settings['tag_link'] );

			// Coupon used
			if ( ! empty( $subscription->coupon_id ) ) {
				$coupon_settings = (array) get_post_meta( $subscription->coupon_id, 'wpf-settings', true );

				if ( ! empty( $coupon_settings['apply_tags_coupon'] ) ) {
					$apply_tags = array_merge( $apply_tags, $coupon_settings['apply_tags_coupon'] );
				}
			}

			// Remove cancelled / expired tags.
			$remove_tags = array_merge( $remove_tags, $settings['apply_tags_cancelled'], $settings['apply_tags_expired'], $settings['apply_tags_suspended'], $settings['apply_tags_pending'] );

			$this->sync_subscription_fields( $subscription );

		}

		// Other status changes.
		if ( $subscription->is_expired() && ! in_array( $new_status, array( 'active', 'pending' ) ) ) {

			// Expired subscription.
			$remove_tags = array_merge( $remove_tags, $settings['tag_link'] );
			$apply_tags  = array_merge( $apply_tags, $settings['apply_tags_expired'] );

			if ( ! empty( $settings['remove_tags'] ) ) {
				$remove_tags = array_merge( $remove_tags, $settings['apply_tags_registration'] );
			}
		}

		if ( 'cancelled' === $new_status ) {

			// Cancelled subscription.
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_cancelled'] );

		} elseif ( 'suspended' === $new_status ) {

			// Paused / suspended subscription.
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_suspended'] );

		} elseif ( 'active' === $new_status && 'suspended' === $old_status ) {

			// Reactivated subscription.
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_resumed'] );

		} elseif ( $subscription->in_trial() ) {

			// If is in a trial and isn't cancelled / expired.
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_trial'] );

		}

		// We don't want to remove any tags from plans that the user is still subscribed to (@since 3.41.21).

		$active_subscription_tags    = array();
		$cancelled_subscription_tags = array();
		$mepr_user                   = new MeprUser( $data['user_id'] );
		$active_subscriptions        = $mepr_user->subscriptions();

		foreach ( $active_subscriptions as $active_subscription ) {

			if ( ! is_a( $active_subscription, 'MeprSubscription' ) ) {
				continue;
			}

			if ( $active_subscription->id === $subscription->id || 'active' !== $active_subscription->status ) {
				continue;
			}

			$settings = get_post_meta( $active_subscription->product_id, 'wpf-settings-memberpress', true );

			if ( empty( $settings ) ) {
				continue;
			}

			if ( ! empty( $settings['apply_tags_registration'] ) ) {

				$diff = array_intersect( $remove_tags, $settings['apply_tags_registration'] );

				if ( $diff ) {

					wpf_log(
						'notice',
						$data['user_id'],
						'Memberpress subscription <a href="' . admin_url( 'post.php?post=' . $subscription->product_id . '&action=edit' ) . '" target="_blank">#' . $subscription->product_id . '</a> status changed to <strong>' . $new_status . '</strong>, but user still has another active subscription to membership <a href="' . admin_url( 'post.php?post=' . $active_subscription->product_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $active_subscription->product_id ) . '</a>, so the tag(s) <strong>' . implode( ', ', array_map( 'wpf_get_tag_label', $diff ) ) . '</strong> will not be removed.'
					);

				}

				$active_subscription_tags = array_merge( $active_subscription_tags, $settings['apply_tags_registration'] );

			}

			// Also don't apply cancelled tags if they are still active on another subscription.
			if ( ! empty( $settings['apply_tags_cancelled'] ) ) {
				$cancelled_subscription_tags = array_merge( $cancelled_subscription_tags, $settings['apply_tags_cancelled'] );
			}
		}

		if ( 'active' !== $new_status ) {

			// This was synced for the active status above.

			$update_data = array(
				'mepr_sub_status'        => $new_status,
				'mepr_membership_status' => wp_fusion()->integrations->memberpress->get_membership_status( $data['user_id'] ),
			);

			// Product-specific fields.
			$update_data[ 'mepr_sub_status_' . $data['product_id'] ]        = $new_status;
			$update_data[ 'mepr_membership_status_' . $data['product_id'] ] = $update_data['mepr_membership_status'];

			wp_fusion()->user->push_user_meta( $data['user_id'], $update_data );

		}

		$remove_tags = array_diff( $remove_tags, $active_subscription_tags );
		$apply_tags  = array_diff( $apply_tags, $cancelled_subscription_tags );

		// Prevent looping when tags are modified
		remove_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );

		// Remove any tags
		if ( ! empty( $remove_tags ) ) {
			wp_fusion()->user->remove_tags( $remove_tags, $data['user_id'] );
		}

		// Apply any tags
		if ( ! empty( $apply_tags ) ) {
			wp_fusion()->user->apply_tags( $apply_tags, $data['user_id'] );
		}

		add_action( 'wpf_tags_modified', array( wp_fusion()->integrations->memberpress, 'add_to_membership' ), 10, 2 );
	}


	/**
	 * Subscription upgraded.
	 *
	 * Runs when an existing subscription is upgraded to a new subscription.
	 * Does not run if a trial or free transaction is upgraded to a
	 * subscription.
	 *
	 * @since 3.35.8
	 *
	 * @param string           $type   The subscription type (recurring or
	 *                                 single).
	 * @param MeprSubscription $sub    The subscription object.
	 */
	public function upgraded_subscription( $type, $sub ) {

		$settings = get_post_meta( $sub->product_id, 'wpf-settings-memberpress', true );

		if ( empty( $settings ) ) {
			return;
		}

		if ( ! empty( $settings['apply_tags_upgraded'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_upgraded'], $sub->user_id );
		}

		// Remove any tags from previous levels where Remove Tags is checked.

		$remove_tags = $this->get_remove_tags_from_prior_transactions( $sub->user_id, $sub->product_id );

		if ( ! empty( $remove_tags ) ) {
			wp_fusion()->user->remove_tags( $remove_tags, $sub->user_id );
		}
	}

	/**
	 * Subscription downgraded.
	 *
	 * Runs when an existing subscription is downgraded to a new subscription.
	 *
	 * @since 3.35.8
	 *
	 * @param string           $type   The subscription type (recurring or
	 *                                 single).
	 * @param MeprSubscription $sub    The subscription object.
	 */
	public function downgraded_subscription( $type, $sub ) {

		$settings = get_post_meta( $sub->product_id, 'wpf-settings-memberpress', true );

		if ( empty( $settings ) ) {
			return;
		}

		if ( ! empty( $settings['apply_tags_downgraded'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_downgraded'], $sub->user_id );
		}

		// Remove any tags from previous levels where Remove Tags is checked.

		$remove_tags = $this->get_remove_tags_from_prior_transactions( $sub->user_id, $sub->product_id );

		if ( ! empty( $remove_tags ) ) {
			wp_fusion()->user->remove_tags( $remove_tags, $sub->user_id );
		}
	}

	/**
	 * Runs during upgrades / downgrades, gets any tags to remove from previous
	 * transactions where Remove Tags is checked on the membership product.
	 *
	 * @since 3.40.51
	 *
	 * @param int $user_id The user ID.
	 * @param int $product_id The product ID.
	 * @return array The tags to remove.
	 */
	public function get_remove_tags_from_prior_transactions( $user_id, $product_id ) {

		$remove_tags = array();

		$user = new MeprUser( $user_id );

		$transactions = $user->transactions();

		if ( ! empty( $transactions ) ) {

			$product_ids = array();

			foreach ( $transactions as $transaction ) {

				// Don't run on this one.

				if ( $transaction->product_id === $product_id || 'pending' === $transaction->status ) {
					continue;
				}

				// Don't remove any tags if the user is still subscribed (via concurrent memberships).

				if ( $user->is_already_subscribed_to( $transaction->product_id ) ) {
					continue;
				}

				// Don't need to do it more than once per product.

				if ( in_array( $transaction->product_id, $product_ids ) ) {
					continue;
				}

				$product_ids[] = $transaction->product_id;

				$settings = get_post_meta( $transaction->product_id, 'wpf-settings-memberpress', true );

				if ( empty( $settings ) || empty( $settings['remove_tags'] ) ) {
					continue;
				}

				// If "remove tags" is checked and we're no longer at that level, remove them.

				$remove_tags = array_merge( $remove_tags, $settings['apply_tags_registration'] );

			}
		}

		return $remove_tags;
	}
}
