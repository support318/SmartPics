<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Actions;

use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Fields\Order_Fields;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Services\Order_Builder;

/**
 * Class WC_CREATESUBSCRIPTIONORDER
 *
 * @package Uncanny_Automator_Pro
 */
class Create_Subscription_Order_With_Payment_Gateway extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed
	 */
	protected function setup_action() {

		$this->set_integration( 'WOOCOMMERCE_SUBSCRIPTION' );
		$this->set_action_code( 'WCCREATEORDERFORSUBSCRIPTIONWITHPG' );
		$this->set_action_meta( 'CREATEORDERFORSUBSCRIPTIONWITHPG' );
		$this->set_is_pro( true );
		$this->set_requires_user( true );
		// translators: %1$s is the action meta
		$this->set_sentence( sprintf( esc_attr_x( 'Create a subscription order with {{a product:%1$s}} with a payment method', 'Woocommerce Subscription', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Create a subscription order with {{a product}} with a payment method', 'Woocommerce Subscription', 'uncanny-automator-pro' ) );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$has_payment_method = true;
		return ( new Order_Fields() )->get_options( $has_payment_method );
	}

	/**
	 * @param int   $user_id
	 * @param array $action_data
	 * @param int   $recipe_id
	 * @param array $args
	 * @param $parsed
	 *
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$order = new Order_Builder( $parsed );
		$order = $order->build_order();

		return true;
	}
}
