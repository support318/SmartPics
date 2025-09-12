<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WC_GET_ORDER_BY_ID
 *
 * @package Uncanny_Automator_Pro
 */
class WC_GET_ORDER_BY_ID extends \Uncanny_Automator\Recipe\Action {

	/**
	 * @return mixed|void
	 */
	protected function setup_action() {
		$this->set_integration( 'WC' );
		$this->set_action_code( 'WC_ORDER_DETAILS' );
		$this->set_action_meta( 'WC_ORDER_ID' );
		$this->set_is_pro( true );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_attr_x( 'Get order details by {{an order ID:%1$s}}', 'WooCommerce', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Get order details', 'WooCommerce', 'uncanny-automator-pro' ) );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		return array(
			array(
				'option_code'     => $this->get_action_meta(),
				'label'           => esc_attr_x( 'Order ID', 'WooCommerce', 'uncanny-automator-pro' ),
				'input_type'      => 'int',
				'required'        => true,
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function define_tokens() {
		$tokens            = Wc_Pro_Tokens::set_wc_order_tokens_for_actions();
		$additional_tokens = array(
			'ORDER_URL_ADMIN' => array(
				'name' => __( 'Order URL (admin)', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'ORDER_URL_USER'  => array(
				'name' => __( 'Order URL (user)', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
		);

		return array_merge( $tokens, $additional_tokens );
	}

	/**
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return bool
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$order_id = absint( sanitize_text_field( $parsed[ $this->get_action_meta() ] ) );
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			$this->add_log_error( sprintf( esc_attr_x( 'Order %d not found.', 'WooCommerce', 'uncanny-automator-pro' ), $order_id ) );

			return false;
		}
		$token_values       = Wc_Pro_Tokens::get_wc_order_tokens_parsed_for_actions( $order_id, $order );
		$order_token_values = array(
			'ORDER_URL_USER'  => $order->get_checkout_order_received_url(),
			'ORDER_URL_ADMIN' => admin_url(
				strtr( 'post.php?post=:id&action=edit', array( ':id' => absint( $order->get_id() ) ) )
			),
		);
		$this->hydrate_tokens( array_merge( $token_values, $order_token_values ) );

		return true;
	}
}
