<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe\Action;

/**
 * Class Cancel_Subscription_Variation_Specific
 *
 * @package Uncanny_Automator_Pro
 */
class Cancel_Subscription_Variation_Specific extends Action {

	/**
	 * Define and register the action by pushing it into the Automator object Cancel the user's subscription to {a product}
	 */
	public function setup_action() {

		$this->set_action_code( 'WCVARIATIONSUBCANCELLED' );
		$this->set_action_meta( 'WOOVARIATIONSUBS' );
		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				// translators: %1$s: Product, %2$s: Variation
				esc_attr_x(
					"Cancel the user's subscription to {{a specific variation:%1\$s}} of {{a variable subscription variation:%2\$s}}",
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				),
				'WOOVARIPRODUCT:' . $this->get_action_meta(),
				$this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			esc_attr_x(
				"Cancel the user's subscription to {{a specific variation}} of {{a variable subscription variation}}",
				'WooCommerce Subscription',
				'uncanny-automator-pro'
			)
		);
	}
	/**
	 * Options.
	 *
	 * @return mixed
	 */
	public function options() {

		$product = array(
			'input_type'  => 'select',
			'option_code' => 'WOOVARIPRODUCT',
			'label'       => esc_attr_x( 'Product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint' => 'automator_select_all_wc_subscriptions',
				'event'    => 'on_load',
			),
		);

		$variation = array(
			'input_type'  => 'select',
			'option_code' => $this->action_meta,
			'label'       => esc_attr_x( 'Variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint'      => 'uncanny_automator_pro_fetch_variation_options',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( 'WOOVARIPRODUCT' ),
			),
		);

		return array(
			$product,
			$variation,
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$subscriptions = wcs_get_users_subscriptions( $user_id );
		$variation_id  = $action_data['meta'][ $this->action_meta ];

		if ( empty( $subscriptions ) ) {
			throw new \Exception( 'No subscription is associated with the user' );
		}

		$subscription = $this->get_matching_subscription( $subscriptions, $variation_id );

		if ( ! $subscription ) {
			throw new \Exception( 'No active subscription found.' );
		}

		$subscription->update_status( 'cancelled' );

		return true;
	}

	/**
	 * Find matching subscription for the given variation ID
	 *
	 * @param array $subscriptions Array of WC_Subscription objects
	 * @param int   $variation_id Variation ID to match
	 * @return \WC_Subscription|null The matching subscription or null if not found
	 */
	private function get_matching_subscription( $subscriptions, $variation_id ) {

		foreach ( $subscriptions as $subscription ) {

			if ( ! $subscription->has_status( array( 'active' ) ) || ! $subscription->can_be_updated_to( 'cancelled' ) ) {
				continue;
			}

			$items = $subscription->get_items();

			foreach ( $items as $item ) {
				if ( -1 === intval( $variation_id ) || absint( $item->get_product_id() ) === absint( $variation_id ) ) {
					return $subscription;
				}
			}
		}

		return null;
	}
}
