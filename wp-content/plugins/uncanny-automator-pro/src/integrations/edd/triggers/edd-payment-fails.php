<?php

namespace Uncanny_Automator_Pro;

use EDD_Payment;
use Uncanny_Automator\Recipe\Trigger;

/**
 * Class EDD_PAYMENT_FAILS
 *
 * @pacakge Uncanny_Automator_Pro
 */
class EDD_PAYMENT_FAILS extends Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'EDD' );
		$this->set_trigger_code( 'EDD_PAYMENT_FAILS' );
		$this->set_trigger_meta( 'EDD_PAYMENTS' );
		$this->set_is_pro( true );
		$this->set_sentence( esc_attr_x( 'A payment fails', 'Easy Digital Downloads', 'uncanny-automator-pro' ) );
		$this->set_readable_sentence( esc_attr_x( 'A payment fails', 'Easy Digital Downloads', 'uncanny-automator-pro' ) );
		$this->add_action( 'edd_before_payment_status_change', 99, 3 );
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $hook_args[0], $hook_args[1] ) ) {
			return false;
		}

		if ( ! is_numeric( $hook_args[0] ) ) {
			return false;
		}

		$status = $hook_args[1];

		if ( 'failed' !== $status ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $trigger
	 * @param $tokens
	 *
	 * @return array|array[]
	 */
	public function define_tokens( $trigger, $tokens ) {
		$trigger_tokens = array(
			array(
				'tokenId'   => 'DOWNLOAD_ID',
				'tokenName' => __( 'Download ID', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'DOWNLOAD_NAME',
				'tokenName' => __( 'Download name', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'DOWNLOAD_QTY',
				'tokenName' => __( 'Download quantity', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'DOWNLOAD_SUBTOTAL',
				'tokenName' => __( 'Download subtotal', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'DOWNLOAD_TAX',
				'tokenName' => __( 'Download tax', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'DOWNLOAD_PRICE',
				'tokenName' => __( 'Download price', 'uncanny-automator-pro' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'PAYMENT_GATEWAY',
				'tokenName' => __( 'Payment gateway', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'PAYMENT_CURRENCY',
				'tokenName' => __( 'Payment currency', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);

		return array_merge( $tokens, $trigger_tokens );
	}

	/**
	 * hydrate_tokens
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		$payment_id = $hook_args[0];
		$payment    = new EDD_Payment( $payment_id );

		if ( ! $payment instanceof EDD_Payment ) {
			return array(
				'DOWNLOAD_NAME'     => '',
				'DOWNLOAD_ID'       => '',
				'DOWNLOAD_QTY'      => '',
				'DOWNLOAD_SUBTOTAL' => '',
				'DOWNLOAD_TAX'      => '',
				'DOWNLOAD_PRICE'    => '',
				'PAYMENT_GATEWAY'   => '',
				'PAYMENT_CURRENCY'  => '',
			);
		}

		$cart_items = edd_get_payment_meta_cart_details( $payment_id );
		$item       = array_shift( $cart_items );

		return array(
			'DOWNLOAD_NAME'     => $item['name'],
			'DOWNLOAD_ID'       => $item['id'],
			'DOWNLOAD_QTY'      => $item['quantity'],
			'DOWNLOAD_SUBTOTAL' => edd_currency_filter( edd_format_amount( $item['subtotal'] ) ),
			'DOWNLOAD_TAX'      => edd_currency_filter( edd_format_amount( $item['tax'] ) ),
			'DOWNLOAD_PRICE'    => edd_currency_filter( edd_format_amount( $item['price'] ) ),
			'PAYMENT_GATEWAY'   => $payment->gateway,
			'PAYMENT_CURRENCY'  => $payment->currency,
		);
	}
}
