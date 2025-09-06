<?php
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services;

/**
 * Subscription_User_Service
 *
 * A helper class to fetch user IDs based on their WooCommerce Subscription status.
 *
 * Requires WooCommerce Subscriptions to be active.
 */

class Subscription_User_Service {

	/**
	 * Get user IDs of all customers subscribed to a specific subscription product.
	 *
	 * @param int $product_id The subscription product ID.
	 * @return int[] Array of user IDs who have at least one active (or any-status) subscription to $product_id.
	 */
	public static function get_users_subscribed_to_product( int $product_id ): array {

		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return array();
		}

		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return array();
		}

		// 1. Get all subscription IDs that include the specified product.
		$subscription_ids = wcs_get_subscriptions_for_product( $product_id );

		if ( empty( $subscription_ids ) ) {
			return array();
		}

		$users = array();
		foreach ( $subscription_ids as $sub_id ) {
			$subscription = wcs_get_subscription( $sub_id );
			if ( is_a( $subscription, '\WC_Subscription' ) ) {
				$user_id = absint( $subscription->get_user_id() );
				if ( $user_id ) {
					$users[] = $user_id;
				}
			}
		}

		return array_values( array_unique( $users ) );
	}

	/**
	 * Get user IDs of all customers who have any subscription (any product).
	 *
	 * @return int[] Array of user IDs who have at least one subscription.
	 */
	public static function get_users_with_any_subscription(): array {

		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return array();
		}

		$users = array();

		// Fetch all subscription IDs (any status).
		$all_subscriptions = wcs_get_subscriptions(
			array(
				'subscription_status'    => array_keys( wcs_get_subscription_statuses() ),
				'subscriptions_per_page' => -1,
				'fields'                 => 'ids',
			)
		);

		if ( ! empty( $all_subscriptions ) ) {
			foreach ( $all_subscriptions as $sub_id ) {
				/** @var WC_Subscription */
				$subscription = wcs_get_subscription( $sub_id );
				if ( is_a( $subscription, '\WC_Subscription' ) ) {
					$user_id = absint( $subscription->get_user_id() );
					if ( $user_id ) {
						$users[] = $user_id;
					}
				}
			}
		}

		return array_values( array_unique( $users ) );
	}

	/**
	 * Get user IDs of all customers who are NOT subscribed to a specific subscription product.
	 *
	 * @param int $product_id The subscription product ID.
	 * @return int[] Array of user IDs who have no subscription to $product_id.
	 */
	public static function get_users_not_subscribed_to_product( int $product_id ): array {

		// Fetch users who are subscribed to $product_id.
		$subscribed_users = self::get_users_subscribed_to_product( $product_id );

		// Fetch all user IDs in the system.
		$all_users = get_users(
			array(
				'fields' => 'ID',
			)
		);

		if ( empty( $all_users ) ) {
			return array();
		}

		// Exclude subscribed users.
		if ( ! empty( $subscribed_users ) ) {
			$all_users = array_diff( $all_users, $subscribed_users );
		}

		return array_values( $all_users );
	}

	/**
	 * Get user IDs of all customers who do NOT have any subscription.
	 *
	 * @return int[] Array of user IDs with zero subscriptions.
	 */
	public static function get_users_without_any_subscription(): array {

		// Fetch users who have at least one subscription.
		$users_with_sub = self::get_users_with_any_subscription();

		// Fetch all user IDs in the system.
		$all_users = get_users(
			array(
				'fields' => 'ID',
			)
		);

		if ( empty( $all_users ) ) {
			return array();
		}

		// Exclude users who have subscriptions.
		if ( ! empty( $users_with_sub ) ) {
			$all_users = array_diff( $all_users, $users_with_sub );
		}

		return array_values( $all_users );
	}
}
