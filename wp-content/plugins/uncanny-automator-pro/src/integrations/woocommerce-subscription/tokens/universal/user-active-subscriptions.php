<?php
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Loopable\Universal;

use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Services\Loopable\Universal_Loopable_Token;

/**
 * User_Active_Subscriptions
 *
 * @package Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Tokens\Loopable
 */
class User_Active_Subscriptions extends Universal_Loopable_Token {

	/**
	 * Register loopable token.
	 *
	 * @return void
	 */
	public function register_loopable_token() {

		$child_tokens = array(
			'PRODUCT_ID'              => array(
				'name'       => esc_html_x( 'Product ID', 'Woo', 'uncanny-automator' ),
				'token_type' => 'integer',
			),
			'PRODUCT_NAME'            => array(
				'name' => esc_html_x( 'Product name', 'Woo', 'uncanny-automator' ),
			),
			'PRODUCT_DESCRIPTION'     => array(
				'name'       => esc_html_x( 'Product description', 'Woo', 'uncanny-automator' ),
				'token_type' => 'integer',
			),
			'SUBSCRIPTION_PRICE'      => array(
				'name'       => esc_html_x( 'Subscription price', 'Woo', 'uncanny-automator' ),
				'token_type' => 'float',
			),
			'SUBSCRIPTION_START_DATE' => array(
				'name'       => esc_html_x( 'Subscription start date', 'Woo', 'uncanny-automator' ),
				'token_type' => 'text',
			),
			'SUBSCRIPTION_END_DATE'   => array(
				'name'       => esc_html_x( 'Subscription end date', 'Woo', 'uncanny-automator' ),
				'token_type' => 'text',
			),
		);

		$this->set_id( 'ACTIVE_SUBSCRIPTION' );
		$this->set_name( esc_html_x( "User's active subscriptions", 'Woo', 'uncanny-automator' ) );
		$this->set_log_identifier( '#{{PRODUCT_ID}} {{PRODUCT_NAME}}' );
		$this->set_child_tokens( $child_tokens );
	}

	/**
	 * Hydrate the tokens.
	 *
	 * @param mixed $args
	 * @return Loopable_Token_Collection
	 */
	public function hydrate_token_loopable( $args ) {

		$loopable = new Loopable_Token_Collection();

		$subscriptions = $this->get_user_subscriptions_details( absint( $args['user_id'] ?? 0 ) );

		// Bail if empty.
		if ( false === $subscriptions ) {
			return $loopable;
		}

		foreach ( $subscriptions as $subscription ) {
			$loopable->create_item(
				array(
					'PRODUCT_ID'              => $subscription['subscription_id'] ?? '',
					'PRODUCT_NAME'            => $subscription['name'] ?? '',
					'PRODUCT_DESCRIPTION'     => $subscription['description'] ?? '',
					'SUBSCRIPTION_PRICE'      => $subscription['price'] ?? '',
					'SUBSCRIPTION_START_DATE' => $subscription['start_date'] ?? '',
					'SUBSCRIPTION_END_DATE'   => $subscription['end_date'] ?? '',
				)
			);
		}

		return $loopable;
	}

	/**
	 * Retrieves specific subscription details for a user in WooCommerce.
	 *
	 * @param int $user_id The ID of the user whose subscriptions are being retrieved.
	 * @return array|false An array of subscription details or false on failure.
	 */
	public function get_user_subscriptions_details( $user_id ) {

		// Ensure WooCommerce Subscriptions functions are available.
		if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
			return false; // WooCommerce Subscriptions is not active or not available.
		}

		// Validate the user ID.
		if ( ! is_numeric( $user_id ) || $user_id <= 0 ) {
			return false; // Invalid user ID provided.
		}

		// Retrieve all subscriptions for the specified user ID.
		$subscriptions = wcs_get_users_subscriptions( $user_id );

		// Check if any subscriptions were found.
		if ( empty( $subscriptions ) ) {
			return false; // No subscriptions found for this user.
		}

		$subscription_data = array();

		// Loop through each subscription and gather relevant details.
		foreach ( $subscriptions as $subscription ) {

			// Ensure $subscription is a valid WC_Subscription object.
			if ( false === ( $subscription instanceof \WC_Subscription ) ) {
				continue; // Skip if the object is not a valid subscription.
			}

			// Get the subscription ID.
			$subscription_id = $subscription->get_id();

			// Initialize variables for subscription name, price, and description.
			$subscription_name        = '';
			$subscription_price       = 0;
			$subscription_description = '';

			// Get the items associated with the subscription.
			foreach ( $subscription->get_items() as $item_id => $item ) {
				$product = $item->get_product();

				if ( $product ) {
					$subscription_name        = $product->get_name();
					$subscription_price       = $product->get_price();
					$subscription_description = $product->get_description();
					break; // Assuming we only want the first item for details.
				}
			}

			$subscription_data[] = array(
				'subscription_id' => $subscription_id,
				'name'            => $subscription_name,
				'price'           => $subscription_price,
				'description'     => $subscription_description,
				'start_date'      => $subscription->get_date( 'start' ),
				'end_date'        => $subscription->get_date( 'end' ),
			);
		}

		// Return the subscription data or false if no valid subscriptions were processed.
		return ! empty( $subscription_data ) ? $subscription_data : false;
	}
}
