<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe\Action;
use Exception;

/**
 * Class Set_Subscription_Status
 *
 * @package Uncanny_Automator_Pro
 */
class Set_Subscription_Status extends Action {

	/**
	 * Register the action.
	 */
	protected function setup_action() {

		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WCVARIATIONSUBSCRIPION' );
		$this->set_action_meta( 'WOOVARIATIONSUBS' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				/* translators: 1: Subscription product token, 2: Status token */
				esc_html_x(
					"Set the user's subscription of {{a subscription product:%1\$s}} to {{a status:%2\$s}}",
					'WooCommerce Subscriptions',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				'WCS_STATUS:' . $this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x(
				"Set the user's subscription of {{a subscription product}} to {{a status}}",
				'WooCommerce Subscriptions',
				'uncanny-automator-pro'
			)
		);
	}

	/**
	 * Action options for UI.
	 *
	 * @return array[]
	 */
	public function options() {

		return array(
			array(
				'option_code' => $this->get_action_meta(),
				'label'       => esc_html_x( 'Subscription product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'input_type'  => 'select',
				'required'    => true,
				'options'     => array(),
				'ajax'        => array(
					'endpoint' => 'automator_select_all_wc_subscriptions',
					'event'    => 'on_load',
				),
			),
			array(
				'option_code' => 'WCS_STATUS',
				'label'       => esc_html_x( 'Status', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'input_type'  => 'select',
				'required'    => true,
				'options'     => $this->get_statuses(),
			),
		);
	}

	/**
	 * Process the action when triggered.
	 *
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param array $parsed
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$product_id = $this->validate_product_id( $parsed );
		$status     = $this->validate_status( $parsed );

		$subscriptions = $this->get_matching_subscriptions( $user_id, $product_id );

		if ( empty( $subscriptions ) ) {
			throw new Exception(
				sprintf( 'No subscriptions found containing product ID: %s.', esc_html( $product_id ) )
			);
		}

		$failed_updates     = array();
		$successful_updates = 0;

		foreach ( $subscriptions as $subscription ) {
			try {
				// Check if status transition is valid before attempting update
				if ( ! $subscription->can_be_updated_to( $status ) ) {
					$failed_updates[] = sprintf(
						'Subscription ID %d: Cannot transition from "%s" to "%s"',
						$subscription->get_id(),
						$subscription->get_status(),
						$status
					);
					continue;
				}

				$subscription->update_status( $status );
				++$successful_updates;
			} catch ( Exception $e ) {
				$failed_updates[] = sprintf( 'Subscription ID %d: %s', $subscription->get_id(), $e->getMessage() );
			}
		}

		// If some failed, provide detailed error information
		if ( ! empty( $failed_updates ) ) {
			$error_message = sprintf(
				'Failed to update %d of %d subscriptions. Errors: %s',
				count( $failed_updates ),
				count( $subscriptions ),
				implode( '; ', $failed_updates )
			);
			throw new Exception( esc_html( $error_message ) );
		}

		return true;
	}

	/**
	 * Validate product ID from parsed tokens.
	 *
	 * @param array $parsed
	 * @return int
	 * @throws Exception
	 */
	protected function validate_product_id( array $parsed ): int {

		$product_id = $parsed[ $this->get_action_meta() ] ?? 0;

		// Sanitize input before validation
		$product_id = is_string( $product_id ) ? trim( $product_id ) : $product_id;

		if ( ! is_numeric( $product_id ) || (int) $product_id <= 0 ) {
			throw new Exception( sprintf( 'Invalid subscription product ID: %s', esc_html( $product_id ) ) );
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			throw new Exception( 'WooCommerce is not active or not available.' );
		}

		$product = wc_get_product( (int) $product_id );

		if ( ! $product ) {
			throw new Exception( sprintf( 'Subscription product not found: %s', esc_html( $product_id ) ) );
		}

		if ( ! in_array( $product->get_type(), array( 'subscription', 'variable-subscription' ), true ) ) {
			throw new Exception(
				sprintf(
					'Product ID %s is not a subscription product. Found type: %s',
					esc_html( $product_id ),
					esc_html( $product->get_type() )
				)
			);
		}

		return (int) $product_id;
	}

	/**
	 * Validate subscription status from parsed tokens.
	 *
	 * @param array $parsed
	 * @return string
	 * @throws Exception
	 */
	protected function validate_status( array $parsed ): string {

		$status = $parsed['WCS_STATUS'] ?? '';

		if ( empty( $status ) ) {
			throw new Exception( 'Status is required.' );
		}

		if ( ! function_exists( 'wcs_get_subscription_statuses' ) ) {
			throw new Exception( 'WooCommerce Subscriptions is not active or not available.' );
		}

		$available    = wcs_get_subscription_statuses();
		$clean_status = str_replace( 'wc-', '', $status );
		$prefixed     = 'wc-' . $clean_status;

		// More robust status checking
		if ( isset( $available[ $status ] ) ) {
			return $status;
		} elseif ( isset( $available[ $clean_status ] ) ) {
			return $clean_status;
		} elseif ( isset( $available[ $prefixed ] ) ) {
			return $prefixed;
		}

		throw new Exception( sprintf( 'Invalid status: %s', esc_html( $status ) ) );
	}

	/**
	 * Retrieve matching subscriptions for a given product ID.
	 *
	 * @param int $user_id
	 * @param int $product_id
	 * @return array
	 * @throws Exception
	 */
	protected function get_matching_subscriptions( int $user_id, int $product_id ): array {

		if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
			throw new Exception( 'WooCommerce Subscriptions is not active or not available.' );
		}

		$all_subs = wcs_get_users_subscriptions( $user_id );

		// wcs_get_users_subscriptions() can return false on error
		if ( false === $all_subs ) {
			throw new Exception( sprintf( 'Failed to retrieve subscriptions for user ID: %s', esc_html( $user_id ) ) );
		}

		if ( empty( $all_subs ) ) {
			return array();
		}

		$matching = array();

		foreach ( $all_subs as $subscription ) {
			// Validate subscription object
			if ( ! is_object( $subscription ) || ! method_exists( $subscription, 'get_items' ) ) {
				continue;
			}

			$items = $subscription->get_items();
			if ( empty( $items ) || ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				// Validate item object
				if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
					continue;
				}

				$item_product_id   = (int) $item->get_product_id();
				$item_variation_id = (int) $item->get_variation_id();

				// Direct product match
				$product_match = $item_product_id === $product_id;

				// Variation match
				$variation_match = $item_variation_id === $product_id && $item_variation_id > 0;

				// Parent product match (if searching for parent but subscription has variation)
				$parent_match = false;
				if ( $item_variation_id > 0 && $item_product_id === $product_id ) {
					$parent_match = true;
				}

				if ( $product_match || $variation_match || $parent_match ) {
					$matching[] = $subscription;
					break;
				}
			}
		}

		// Remove duplicates that could occur if subscription has multiple matching items
		return array_unique( $matching, SORT_REGULAR );
	}

	/**
	 * Returns valid WooCommerce Subscription statuses.
	 *
	 * @return array
	 */
	protected function get_statuses(): array {

		$options = array();

		if ( function_exists( 'wcs_get_subscription_statuses' ) ) {
			foreach ( wcs_get_subscription_statuses() as $key => $label ) {
				$options[] = array(
					'value' => $key,
					'text'  => $label,
				);
			}
		}

		return $options;
	}
}
