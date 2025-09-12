<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe\Action;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Subscription_Product_Remover;

/**
 * Class Remove_Product_From_Variation_Subscription
 *
 * @package Uncanny_Automator_Pro
 */
class Remove_Product_From_Variation_Subscription extends Action {

	/**
	 * Setup the action configuration
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Set basic action properties.
		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WCS_REMOVE_VARIATION' );
		$this->set_action_meta( 'WCS_VARIATION' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );

		// Set the sentence with placeholders.
		$this->set_sentence(
			sprintf(
				// translators: %1$s: Variation meta, %2$s: Product meta, %3$s: Subscription ID meta
				esc_html_x(
					"Remove {{a variation:%1\$s}} of {{a subscription product:%2\$s}} from the user's {{subscription:%3\$s}}",
					'Woo Subscription',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				'WCS_PRODUCTS:' . $this->get_action_meta(),
				'SUBSCRIPTION_ID:' . $this->get_action_meta()
			)
		);

		// Set human readable sentence.
		$this->set_readable_sentence(
			esc_html_x(
				"Remove {{a variation}} of {{a subscription product}} from the user's {{subscription}}",
				'Woo Subscription',
				'uncanny-automator-pro'
			)
		);

		$this->define_action_tokens();
	}

	/**
	 * Get the action fields configuration
	 *
	 * @return array
	 */
	public function options() {

		// Variable subscription field
		$variable_subscription = array(
			'input_type'  => 'select',
			'option_code' => 'WCS_PRODUCTS',
			'label'       => esc_html_x( 'Variable subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint' => 'uncanny_automator_pro_fetch_variations',
				'event'    => 'on_load',
			),
		);

		// Variation selection field
		$variation = array(
			'input_type'  => 'select',
			'option_code' => $this->get_action_meta(),
			'label'       => esc_html_x( 'Variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint'      => 'uncanny_automator_pro_fetch_variation_options',
				'event'         => 'parent_fields_change',
				'listen_fields' => array(
					'WCS_PRODUCTS',
				),
			),
		);

		// Subscription ID field
		$subscription_id = Automator()->helpers->recipe->field->int(
			array(
				'option_code' => 'SUBSCRIPTION_ID',
				'label'       => esc_html_x( 'Subscription ID', 'Woo Subscription', 'uncanny-automator-pro' ),
				'description' => esc_html_x( 'Leave blank to remove from all active subscriptions.', 'Woo Subscription', 'uncanny-automator-pro' ),
				'required'    => false,
			)
		);

		return array(
			$variable_subscription,
			$variation,
			$subscription_id,
		);
	}
	/**
	 * Define action tokens.
	 *
	 * @return mixed
	 */
	public function define_action_tokens() {

		return array(
			'WC_SUBSCRIPTION_ID'               => array(
				'name' => esc_html_x( 'Subscription ID(s)', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			$this->get_action_meta() . '_NAME' => array(
				'name' => esc_html_x( 'Variation name', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'WCS_PRODUCTS_NAME'                => array(
				'name' => esc_html_x( 'Variable subscription name', 'WooCommerce Subscriptions', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * Process the action of removing a variation from subscription(s)
	 *
	 * @param int   $user_id      The user ID
	 * @param array $action_data  Action data
	 * @param int   $recipe_id    Recipe ID
	 * @param array $args         Arguments
	 * @param array $parsed       Parsed data
	 *
	 * @throws \Exception If removal fails
	 * @return bool
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$variation_id    = sanitize_text_field( $parsed[ $this->get_action_meta() ] );
		$subscription_id = sanitize_text_field( $parsed['SUBSCRIPTION_ID'] );

		$remover = new Subscription_Product_Remover();

		if ( ! empty( $subscription_id ) ) {
			$remover->remove_from_specific_subscription( $subscription_id, $variation_id );
		} else {
			$remover->remove_from_all_active_subscriptions( $user_id, $variation_id );
		}

		// Hydrate tokens after successful removal
		$this->hydrate_tokens(
			array(
				'WC_SUBSCRIPTION_ID'               => $subscription_id,
				'SUBSCRIPTION_ID'                  => $subscription_id ?? '',
				$this->get_action_meta() . '_NAME' => $parsed[ $this->get_action_meta() . '_readable' ],
				'WCS_PRODUCTS_NAME'                => $parsed['WCS_PRODUCTS_readable'],
			)
		);

		return true;
	}
}
