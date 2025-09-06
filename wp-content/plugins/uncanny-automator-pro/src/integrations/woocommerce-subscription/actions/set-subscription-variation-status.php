<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe\Action;
use Exception;

/**
 * Class Set_Subscription_Variation_Status
 *
 * @package Uncanny_Automator_Pro
 */
class Set_Subscription_Variation_Status extends Action {

	/**
	 * Register the action.
	 */
	protected function setup_action() {

		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WCVARIATIONSUBSCRIPIONS' );
		$this->set_action_meta( 'WOOVARIATIONSUBS' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				/* translators: 1: Variation token, 2: Variable subscription product token, 3: Status token */
				esc_html_x(
					"Set the user's subscription to {{a specific:%1\$s}} variation of {{a variable subscription product:%2\$s}} to {{a status:%3\$s}}",
					'WooCommerce Subscriptions',
					'uncanny-automator-pro'
				),
				'WOOVARIPRODUCT:' . $this->get_action_meta(),
				$this->get_action_meta(),
				'WCS_STATUS:' . $this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html_x(
				"Set the user's subscription to {{a specific}} variation of {{a variable subscription product}} to {{a status}}",
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
				'option_code'     => $this->get_action_meta(),
				'label'           => esc_attr_x( 'Subscriptions', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => array(),
				'ajax'            => array(
					'endpoint' => 'automator_select_all_wc_subscriptions',
					'event'    => 'on_load',
				),
				'options_show_id' => false,
			),
			array(
				'option_code'     => 'WOOVARIPRODUCT',
				'label'           => esc_attr_x( 'Variation', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => array(),
				'ajax'            => array(
					'endpoint'      => 'uncanny_automator_pro_fetch_variation_options',
					'event'         => 'parent_fields_change',
					'listen_fields' => array( $this->get_action_meta() ),
				),
				'options_show_id' => false,
			),
			array(
				'option_code'     => 'WCS_STATUS',
				'label'           => esc_attr_x( 'Status', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'input_type'      => 'select',
				'required'        => true,
				'options'         => array(),
				'ajax'            => array(
					'endpoint' => 'uncanny_automator_pro_fetch_woo_statuses',
					'event'    => 'on_load',
				),
				'options_show_id' => false,
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

		$product_id   = $this->validate_product_id( $parsed );
		$variation_id = $this->validate_variation_id( $parsed );
		$status       = $this->validate_status( $parsed );

		$subscriptions = $this->get_matching_subscriptions( $user_id, $product_id, $variation_id );

		if ( empty( $subscriptions ) ) {
			throw new Exception(
				sprintf(
					'No subscriptions found for product ID: %s, variation ID: %s.',
					esc_html( $product_id ),
					esc_html( $variation_id )
				)
			);
		}

		foreach ( $subscriptions as $subscription ) {
			try {
				$subscription->update_status( $status );
			} catch ( Exception $e ) {
				throw new Exception(
					sprintf(
						'Failed to update subscription ID %d: %s',
						esc_html( $subscription->get_id() ),
						esc_html( $e->getMessage() )
					)
				);
			}
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

		// Allow -1 for "any product" option
		if ( '-1' === $product_id || -1 === (int) $product_id ) {
			return -1;
		}

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

		if ( ! in_array( $product->get_type(), array( 'variable-subscription' ), true ) ) {
			throw new Exception(
				sprintf(
					'Product ID %s is not a variable subscription product. Found type: %s',
					esc_html( $product_id ),
					esc_html( $product->get_type() )
				)
			);
		}

		return (int) $product_id;
	}

	/**
	 * Validate variation ID from parsed tokens.
	 *
	 * @param array $parsed
	 * @return int
	 * @throws Exception
	 */
	protected function validate_variation_id( array $parsed ): int {

		$variation_id = $parsed['WOOVARIPRODUCT'] ?? 0;

		// Allow -1 for "any variation" option
		if ( '-1' === $variation_id || -1 === (int) $variation_id ) {
			return -1;
		}

		$variation_id = is_string( $variation_id ) ? trim( $variation_id ) : $variation_id;

		if ( ! is_numeric( $variation_id ) || (int) $variation_id <= 0 ) {
			throw new Exception( sprintf( 'Invalid variation ID: %s', esc_html( $variation_id ) ) );
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			throw new Exception( 'WooCommerce is not active or not available.' );
		}

		$variation = wc_get_product( (int) $variation_id );

		if ( ! $variation || 'subscription_variation' !== $variation->get_type() ) {
			throw new Exception( sprintf( 'Subscription variation not found: %s', esc_html( $variation_id ) ) );
		}

		return (int) $variation_id;
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
	 * Retrieve matching subscriptions for given product and variation IDs.
	 *
	 * @param int $user_id
	 * @param int $product_id
	 * @param int $variation_id
	 * @return array
	 * @throws Exception
	 */
	protected function get_matching_subscriptions( int $user_id, int $product_id, int $variation_id ): array {

		if ( ! function_exists( 'wcs_get_users_subscriptions' ) ) {
			throw new Exception( 'WooCommerce Subscriptions is not active or not available.' );
		}

		$all_subs = wcs_get_users_subscriptions( $user_id );

		if ( false === $all_subs ) {
			throw new Exception( sprintf( 'Failed to retrieve subscriptions for user ID: %s', esc_html( $user_id ) ) );
		}

		if ( empty( $all_subs ) ) {
			return array();
		}

		$matching = array();

		foreach ( $all_subs as $subscription ) {
			if ( ! is_object( $subscription ) || ! method_exists( $subscription, 'get_items' ) ) {
				continue;
			}

			$items = $subscription->get_items();
			if ( empty( $items ) || ! is_array( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
					continue;
				}

				if ( $this->is_matching_item( $item, $product_id, $variation_id ) ) {
					$matching[] = $subscription;
					break;
				}
			}
		}

		return array_unique( $matching, SORT_REGULAR );
	}

	/**
	 * Checks if subscription item matches product and variation IDs.
	 *
	 * @param object $item
	 * @param int    $product_id
	 * @param int    $variation_id
	 * @return bool
	 */
	protected function is_matching_item( $item, int $product_id, int $variation_id ): bool {

		$item_product_id   = (int) $item->get_product_id();
		$item_variation_id = (int) $item->get_variation_id();

		// Check if product matches (allow -1 for "any product")
		$matches_product = ( -1 === $product_id || $item_product_id === $product_id );

		// Check if variation matches (allow -1 for "any variation")
		$matches_variation = ( -1 === $variation_id || $item_variation_id === $variation_id );

		return $matches_product && $matches_variation;
	}
}
