<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe\Action;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Subscription_Extender;

/**
 * Class Extend_Subscription_Variation
 *
 * @package Uncanny_Automator_Pro
 */
class Extend_Subscription_Variation extends Action {


	/**
	 * Define and register the action by pushing it into the Automator object
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Set integration details.
		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WC_EXTENDUSERVARIATIONSUBSCRIPTION' );
		$this->set_action_meta( 'WC_EXTENDUSERVARIATIONSUBSCRIPTION_META' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				/* translators: Action - WooCommerce Subscription */
				esc_html_x(
					"Extend a user's subscription to {{a specific product variation:%1\$s}} of {{a specific product:%2\$s}} by {{a number of:%3\$s}} {{days:%4\$s}}",
					'WooCommerce Subscriptions',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				'WOOVARIPRODUCT:' . $this->get_action_meta(),
				$this->get_action_meta() . '_NO_OF:' . $this->get_action_meta(),
				$this->get_action_meta() . '_DURATION:' . $this->get_action_meta()
			)
		);

		$this->set_readable_sentence(
			/* translators: Action - WooCommerce Subscription */
			esc_html_x(
				"Extend a user's subscription to {{a specific product variation}} of {{a specific product}} by {{a number of}} {{days}}",
				'WooCommerce Subscriptions',
				'uncanny-automator-pro'
			)
		);

		// Define action tokens
		$action_tokens = array(
			'PRODUCT_TITLE'     => array(
				'name' => esc_html_x( 'Product title', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			),

			'PRODUCT_ID'        => array(
				'name' => esc_html_x( 'Product ID', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'type' => 'int',
			),

			'PRODUCT_URL'       => array(
				'name' => esc_html_x( 'Product URL', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'type' => 'url',
			),

			'PRODUCT_THUMB_URL' => array(
				'name' => esc_html_x( 'Product featured image URL', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'type' => 'url',
			),

			'PRODUCT_THUMB_ID'  => array(
				'name' => esc_html_x( 'Product featured image ID', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
		);

		$this->set_action_tokens( $action_tokens, $this->action_code );
	}

	/**
	 * Define the action options.
	 *
	 * @return array
	 */
	public function options() {

		$variable_subscription_product = array(
			'input_type'      => 'select',
			'option_code'     => $this->get_action_meta(),
			'label'           => esc_html_x( 'Variable subscription product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'options'         => array(),
			'options_show_id' => false,
			'required'        => true,
			'ajax'            => array(
				'endpoint' => 'uncanny_automator_pro_fetch_variations',
				'event'    => 'on_load',
			),

		);

		$variation = array(
			'input_type'      => 'select',
			'option_code'     => 'WOOVARIPRODUCT',
			'label'           => esc_html_x( 'Variation', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'        => true,
			'options'         => array(),
			'options_show_id' => false,
			'ajax'            => array(
				'endpoint'      => 'uncanny_automator_pro_fetch_variation_options',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( $this->get_action_meta() ),
			),
		);

		$length = array(
			'input_type'      => 'select',
			'option_code'     => $this->get_action_meta() . '_DURATION',
			'label'           => esc_html_x( 'Length', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'        => true,
			'options'         => $this->get_duration_options(),
			'options_show_id' => false,
		);

		$number = array(
			'input_type'  => 'int',
			'option_code' => $this->get_action_meta() . '_NO_OF',
			'label'       => esc_html_x( 'Number', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'required'    => true,
		);

		return array(
			$variable_subscription_product,
			$variation,
			$length,
			$number,
		);
	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$product_id    = $parsed['WOOVARIPRODUCT'] ?? 0;
		$duration_type = $parsed[ $this->get_action_meta() . '_DURATION' ] ?? 'day';
		$number        = $parsed[ $this->get_action_meta() . '_NO_OF' ] ?? 0;

		$subscription_extender = new Subscription_Extender();

		$subscription_extender->as_variation();
		$subscription_extender->set_duration_type( $duration_type );
		$subscription_extender->extend( $user_id, $product_id, $number );

		$this->hydrate_tokens(
			array(
				'PRODUCT_TITLE'     => get_the_title( absint( $product_id ) ),
				'PRODUCT_ID'        => absint( $product_id ),
				'PRODUCT_URL'       => get_the_permalink( absint( $product_id ) ),
				'PRODUCT_THUMB_URL' => get_the_post_thumbnail_url( absint( $product_id ) ),
				'PRODUCT_THUMB_ID'  => get_post_thumbnail_id( absint( $product_id ) ),
			)
		);

		return true;
	}

	/**
	 * Get duration options
	 *
	 * @return array
	 */
	private function get_duration_options() {

		return array(
			array(
				'text'  => esc_html_x( 'Day(s)', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => 'day',
			),
			array(
				'text'  => esc_html_x( 'Week(s)', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => 'week',
			),
			array(
				'text'  => esc_html_x( 'Month(s)', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
				'value' => 'month',
			),
		);
	}
}
