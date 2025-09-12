<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator\Recipe\Action;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Subscription_Extender;

/**
 * Class Extend_Subscription
 *
 * @package Uncanny_Automator_Pro
 */
class Extend_Subscription extends Action {

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {

		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WC_EXTENDUSERSUBSCRIPTION' );
		$this->set_action_meta( 'WC_EXTENDUSERSUBSCRIPTION_META' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				/* translators: 1. Product 2. Number 3. Duration type (days/weeks/months) */
				esc_html_x(
					"Extend a user's subscription to {{a specific product:%1\$s}} by {{a number of:%2\$s}} {{duration type:%3\$s}}",
					'WooCommerce Subscription',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				$this->get_action_meta() . '_NO_OF:' . $this->get_action_meta(),
				$this->get_action_meta() . '_DURATION:' . $this->get_action_meta()
			)
		);

		/* translators: Action - WooCommerce Subscription */
		$this->set_readable_sentence(
			esc_html_x(
				"Extend a user's subscription to {{a specific product}} by {{a number of}} {{duration type}}",
				'WooCommerce Subscription',
				'uncanny-automator-pro'
			)
		);

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_action_tokens(
			array(
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
			),
			$this->action_code
		);
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function options() {

		$subscription_product = array(
			'input_type'      => 'select',
			'option_code'     => $this->get_action_meta(),
			'label'           => esc_html_x( 'Product', 'WooCommerce Subscription', 'uncanny-automator-pro' ),
			'options'         => array(),
			'options_show_id' => false,
			'required'        => true,
			'ajax'            => array(
				'endpoint' => 'automator_select_all_wc_subscriptions',
				'event'    => 'on_load',
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
			$subscription_product,
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

		$product_id = $parsed[ $this->get_action_meta() ];
		$duration   = $parsed[ $this->get_action_meta() . '_NO_OF' ] ?? 0;
		$unit       = $parsed[ $this->get_action_meta() . '_DURATION' ] ?? 'day';

		$subscription_extender = ( new Subscription_Extender() )
			->as_subscription()
			->set_duration_type( $unit )
			->extend( $user_id, $product_id, $duration );

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
