<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe\Action;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Subscription_Extender;

/**
 * Class Extend_Subscription_Renewal
 *
 * @package Uncanny_Automator_Pro
 */
class Extend_Subscription_Renewal extends Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {

		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WCS_NEXT_DATE_EXTENDED_SV' );
		$this->set_action_meta( 'WCS_VARIATIONS' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );

		$this->set_sentence(
			sprintf(
				// translators: %1$s: Variation meta, %2$s: Product meta, %3$s: Number of days meta
				esc_attr_x(
					"Extend the user's next subscription renewal date to {{a specific product variation:%1\$s}} of {{a specific product:%2\$s}} by {{a number of days:%3\$s}}",
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				'WCS_PRODUCTS:' . $this->get_action_meta(),
				'NUMBER_OF_DAYS:' . $this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			esc_attr_x(
				"Extend the user's next subscription renewal date to {{a specific product variation}} of {{a specific product}} by {{a number of days}}",
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

		$variable_subscription = array(
			'input_type'      => 'select',
			'option_code'     => 'WCS_PRODUCTS',
			'label'           => esc_html_x( 'Variable subscription', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'        => true,
			'options'         => array(),
			'relevant_tokens' => array(),
			'ajax'            => array(
				'endpoint' => 'uncanny_automator_pro_fetch_variations',
				'event'    => 'on_load',
			),
		);

		$variation = array(
			'input_type'      => 'select',
			'option_code'     => $this->get_action_meta(),
			'label'           => esc_html_x( 'Variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'        => true,
			'options'         => array(),
			'relevant_tokens' => array(),
			'ajax'            => array(
				'endpoint'      => 'uncanny_automator_pro_fetch_variation_options',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( 'WCS_PRODUCTS' ),
			),
		);

		$number_of_days = array(
			'input_type'      => 'int',
			'option_code'     => 'NUMBER_OF_DAYS',
			'label'           => esc_html_x( 'Days', 'Woocommerce Subscription', 'uncanny-automator-pro' ),
			'required'        => true,
			'relevant_tokens' => array(),
		);

		return array(
			$variable_subscription,
			$variation,
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
		$extender->as_variation()->extend( $user_id, $product_id, $days );

		return true;
	}
}
