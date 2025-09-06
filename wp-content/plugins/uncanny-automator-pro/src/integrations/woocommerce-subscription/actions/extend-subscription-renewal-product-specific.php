<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Subscription_Extender;

/**
 * Class Extend_Subscription_Renewal_Product_Specific
 *
 * @package Uncanny_Automator_Pro
 */
class Extend_Subscription_Renewal_Product_Specific extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {

		// Basic action configuration.
		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WCS_NEXT_DATE_EXTENDED' );
		$this->set_action_meta( 'WCS_PRODUCTS' );

		// Action properties.
		$this->set_is_pro( true );
		$this->set_requires_user( true );

		// Set sentence with tokens.
		$this->set_sentence(
			sprintf(
				// translators: %1$s: Product meta, %2$s: Number of days meta
				esc_html_x(
					"Extend the user's next subscription renewal date to {{a specific product:%1\$s}} by {{a number of days:%2\$s}}",
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				'NUMBER_OF_DAYS:' . $this->get_action_meta()
			)
		);

		// Set human-readable sentence.
		$this->set_readable_sentence(
			esc_html_x(
				"Extend the user's next subscription renewal date to {{a specific product}} by {{a number of days}}",
				'WooCommerce Subscription',
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

		$subscription_products = array(
			'option_code'     => $this->get_action_meta(),
			'input_type'      => 'select',
			'label'           => esc_html_x( 'Subscription product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'        => true,
			'options'         => array(),
			'relevant_tokens' => array(),
			'options_show_id' => false,
			'ajax'            => array(
				'endpoint' => 'automator_select_all_wc_subscriptions',
				'event'    => 'on_load',
			),
		);

		$number_of_days = array(
			'option_code'     => 'NUMBER_OF_DAYS',
			'label'           => esc_html_x( 'Days', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'input_type'      => 'int',
			'required'        => true,
			'relevant_tokens' => array(),
		);

		return array(
			$subscription_products,
			$number_of_days,
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

		$product_id = $parsed[ $this->get_action_meta() ] ?? 0;
		$days       = $parsed['NUMBER_OF_DAYS'] ?? 0;

		$extender = new Subscription_Extender();
		$extender->as_subscription()->extend( $user_id, $product_id, $days );

		return true;
	}
}
