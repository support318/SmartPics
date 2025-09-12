<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe\Action;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Subscription_Product_Remover;

/**
 * Class Remove_Product_From_Subscription
 *
 * @pacakge Uncanny_Automator_Pro
 */
class Remove_Product_From_Subscription extends Action {

	/**
	 * Setup the action configuration
	 *
	 * @return mixed|void
	 */
	protected function setup_action() {

		// Set basic configurations.
		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WCS_REMOVE_PRODUCT' );
		$this->set_action_meta( 'WCS_PRODUCTS' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );

		// Set action sentence.
		$this->set_sentence(
			sprintf(
				/* translators: Action - WooCommerce Subscription */
				esc_html_x(
					"Remove {{a subscription product:%1\$s}} from the user's {{subscription:%2\$s}}",
					'Woo Subscription',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				'SUBSCRIPTION_ID:' . $this->get_action_meta()
			)
		);

		// Set readable sentence
		$this->set_readable_sentence(
			esc_html_x(
				"Remove {{a subscription product}} from the user's {{subscription}}",
				'Woo Subscription',
				'uncanny-automator-pro'
			)
		);
	}

	/**
	 * Define the Action's options
	 *
	 * @return array
	 */
	public function options() {

		$subscription_product = array(
			'input_type'  => 'select',
			'option_code' => $this->get_action_meta(),
			'label'       => esc_html_x( 'Subscription product', 'Woo Subscription', 'uncanny-automator-pro' ),
			'required'    => true,
			'options'     => array(),
			'ajax'        => array(
				'endpoint' => 'automator_select_all_wc_subscriptions',
				'event'    => 'on_load',
			),
		);

		$subscription_id = array(
			'input_type'  => 'int',
			'option_code' => 'SUBSCRIPTION_ID',
			'label'       => esc_html_x( 'Subscription ID', 'Woo Subscription', 'uncanny-automator-pro' ),
			'required'    => false,
			'description' => esc_html_x( 'Leave empty to remove from all active subscriptions.', 'Woo Subscription', 'uncanny-automator-pro' ),
		);

		return array(
			$subscription_product,
			$subscription_id,
		);
	}

	/**
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$product_id      = sanitize_text_field( $parsed[ $this->get_action_meta() ] );
		$subscription_id = sanitize_text_field( $parsed['SUBSCRIPTION_ID'] );

		$remover = new Subscription_Product_Remover();

		if ( ! empty( $subscription_id ) ) {
			return $remover->remove_from_specific_subscription( $subscription_id, $product_id );
		}

		return $remover->remove_from_all_active_subscriptions( $user_id, $product_id );
	}
}
