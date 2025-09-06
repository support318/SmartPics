<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WC_ADD_A_NOTE_TO_ORDER
 *
 * @package Uncanny_Automator_Pro
 */
class WC_ADD_A_NOTE_TO_ORDER extends \Uncanny_Automator\Recipe\Action {
	/**
	 * @return mixed
	 */
	protected function setup_action() {
		$this->set_integration( 'WC' );
		$this->set_action_code( 'WC_NOTE_TO_ORDER' );
		$this->set_action_meta( 'WC_ORDER' );
		$this->set_is_pro( true );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_attr_x( 'Add a note to {{an order:%1$s}}', 'WooCommerce', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Add a note to {{an order}}', 'WooCommerce', 'uncanny-automator-pro' ) );
	}

	public function options() {
		return array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => $this->get_action_meta(),
					'label'       => __( 'Order ID', 'uncanny-automator-pro' ),
					'input_type'  => 'int',
				)
			),
			array(
				'input_type'      => 'select',
				'option_code'     => 'WC_ORDER_TYPE',
				'label'           => _x( 'Order type', 'WooCommerce', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => array(
					array(
						'text'  => esc_attr_x( 'Private note', 'WooCommerce', 'uncanny-automator-pro' ),
						'value' => 'private',
					),
					array(
						'text'  => esc_attr_x( 'Note to customer', 'WooCommerce', 'uncanny-automator-pro' ),
						'value' => 'customer',
					),
				),
				'relevant_tokens' => array(),
			),
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => 'WC_NOTES',
					'label'       => __( 'Order note', 'uncanny-automator-pro' ),
					'input_type'  => 'textarea',
				)
			),
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
		$order_id   = sanitize_text_field( $parsed[ $this->get_action_meta() ] );
		$order_type = sanitize_text_field( $parsed['WC_ORDER_TYPE'] );
		$notes      = sanitize_text_field( $parsed['WC_NOTES'] );

		$order = wc_get_order( absint( $order_id ) );

		if ( ! $order instanceof \WC_Order ) {
			$this->add_log_error( sprintf( esc_attr_x( 'Order not found: %d', 'WooCommerce', 'uncanny-automator-pro' ), $order_id ) );

			return false;
		}

		$is_customer_note = 1;
		if ( 'private' !== $order_type ) {
			$is_customer_note = 0;
		}

		$note_id = $order->add_order_note( $notes, $is_customer_note );

		if ( empty( $note_id ) ) {
			$this->add_log_error( esc_attr_x( 'Sorry, we were unable to add a note. Please try again later.', 'WooCommerce', 'uncanny-automator-pro' ) );

			return false;
		}

		return true;
	}
}
