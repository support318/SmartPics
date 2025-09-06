<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Exception;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Subscription_User_Service;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class WCS_HAS_USER_AN_ACTIVE_SUBSCRIPTION
 *
 * Do not change the class name. It is used for backward compatibility.
 *
 * @package Uncanny_Automator_Pro
 */
class WCS_HAS_USER_AN_ACTIVE_SUBSCRIPTION extends Loop_Filter {

	/**
	 * Setups the integrations
	 *
	 * @depends \WC_Subscriptions - See method is_dependency_active.
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function setup() {

		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );

		// Do not change the meta name. It is used for backward compatibility.
		$this->set_meta( 'WCS_HAS_USER_AN_ACTIVE_SUBSCRIPTION' );

		$this->set_sentence(
			esc_html_x(
				'The user {{has/does not have}} an active subscription of {{a product}}',
				'WooCommerce Subscription',
				'uncanny-automator-pro'
			)
		);

		$this->set_sentence_readable(
			sprintf(
				/* translators: Filter sentence */
				esc_html_x(
					'The user {{has/does not have:%1$s}} an active subscription of {{a product:%2$s}}',
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				),
				'CRITERIA',
				$this->get_meta()
			)
		);

		$this->set_fields( array( $this, 'load_options' ) );

		$this->set_entities( array( $this, 'retrieve_users_with_subscriptions' ) );
	}

	/**
	 * Check if dependency is active.
	 *
	 * @depends WC_Subscriptions
	 *
	 * @return bool True if dependency is active.
	 */
	protected function is_dependency_active() {
		return class_exists( 'WC_Subscriptions' );
	}

	/**
	 * Load options for the filter.
	 *
	 * @return mixed[] Array of options.
	 */
	public function load_options() {
		return array(
			$this->get_meta() => array(
				array(
					'option_code'           => 'CRITERIA',
					'type'                  => 'select',
					'supports_custom_value' => false,
					'label'                 => esc_html_x( 'Criteria', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					'options'               => array(
						array(
							'text'  => esc_html_x( 'has', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'has', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
						),
						array(
							'text'  => esc_html_x( 'does not have', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'does-not-have', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
						),
					),
				),
				array(
					'option_code'           => $this->get_meta(),
					'type'                  => 'select',
					'label'                 => esc_html_x( 'Subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
					'options'               => $this->get_subscription_products(),
					'supports_custom_value' => false,
				),
			),
		);
	}

	/**
	 * Get all subscription products.
	 *
	 * @return array[] Array of subscription products.
	 */
	private function get_subscription_products() {

		global $wpdb;

		$subscriptions = $wpdb->get_results(
			"SELECT posts.ID, posts.post_title 
			FROM $wpdb->posts as posts 
			INNER JOIN $wpdb->term_relationships as tr ON posts.ID = tr.object_id 
			INNER JOIN $wpdb->terms as t ON tr.term_taxonomy_id = t.term_id 
			WHERE t.slug IN ('subscription', 'variable-subscription')",
			ARRAY_A
		);

		$options = array(
			array(
				'value' => -1,
				'text'  => esc_attr_x( 'Any subscription', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
			),
		);

		foreach ( $subscriptions as $subscription ) {
			$options[] = array(
				'value' => $subscription['ID'],
				'text'  => esc_attr_x( $subscription['post_title'], 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
			);
		}

		return $options;
	}

	/**
	 * Retrieve users with subscriptions based on criteria.
	 *
	 * @param array{WCS_HAS_USER_AN_ACTIVE_SUBSCRIPTION:string,CRITERIA:string} $fields Filter fields.
	 *
	 * @return int[] Array of user IDs.
	 */
	public function retrieve_users_with_subscriptions( $fields ) {

		$criteria        = $fields['CRITERIA'];
		$subscription_id = $fields['WCS_HAS_USER_AN_ACTIVE_SUBSCRIPTION'];

		if ( empty( $criteria ) || empty( $subscription_id ) ) {
			throw new Exception( 'Invalid criteria or subscription ID' );
		}

		// Fetch all users with any active subscription.
		if ( -1 === intval( $subscription_id ) && 'has' === $criteria ) {
			return Subscription_User_Service::get_users_with_any_subscription();
		}

		// Fetch all users without any subscription.
		if ( -1 === intval( $subscription_id ) && 'does-not-have' === $criteria ) {
			return Subscription_User_Service::get_users_without_any_subscription();
		}

		// Fetch all users with an active subscription to the selected product.
		if ( 'has' === $criteria ) {
			return Subscription_User_Service::get_users_subscribed_to_product( $subscription_id );
		}

		// Fetch all users without an active subscription to the selected product.
		if ( 'does-not-have' === $criteria ) {
			return Subscription_User_Service::get_users_not_subscribed_to_product( $subscription_id );
		}

		throw new Exception( 'Invalid criteria or subscription ID' );
	}
}
