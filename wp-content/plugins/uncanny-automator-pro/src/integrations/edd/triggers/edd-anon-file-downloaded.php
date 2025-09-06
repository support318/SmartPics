<?php

namespace Uncanny_Automator_Pro;

/**
 * Class EDD_ANON_FILE_DOWNLOADED
 *
 * @package Uncanny_Automator_Pro
 */
class EDD_ANON_FILE_DOWNLOADED extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->set_integration( 'EDD' );
		$this->set_trigger_code( 'EDD_FILE_DOWNLOADED' );
		$this->set_trigger_meta( 'EDD_PRODUCTS' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( '{{A file:%1$s}} is downloaded', 'Easy Digital Downloads', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{A file}} is downloaded', 'Easy Digital Downloads', 'uncanny-automator-pro' ) );
		$this->add_action( 'edd_process_verified_download', 10, 4 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		$options = Automator()->helpers->recipe->options->edd->all_edd_downloads( '', $this->get_trigger_meta() );

		$all_subscription_products = array();
		foreach ( $options['options'] as $key => $option ) {
			$all_subscription_products[] = array(
				'text'  => $option,
				'value' => $key,
			);
		}

		return array(
			array(
				'input_type'      => 'select',
				'option_code'     => $this->get_trigger_meta(),
				'label'           => _x( 'Download', 'Easy Digital Downloads', 'uncanny-automator-pro' ),
				'required'        => true,
				'options'         => $all_subscription_products,
				'relevant_tokens' => array(),
			),
		);
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_product_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$download_id         = $hook_args[0];

		if ( intval( '-1' ) !== intval( $selected_product_id ) && absint( $selected_product_id ) !== absint( $download_id ) ) {
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
				'tokenName' => __( 'Download ID', 'uncanny-automator' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'DOWNLOAD_NAME',
				'tokenName' => __( 'Download name', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'DOWNLOAD_QTY',
				'tokenName' => __( 'Download quantity', 'uncanny-automator' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'DOWNLOAD_SUBTOTAL',
				'tokenName' => __( 'Download subtotal', 'uncanny-automator' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'DOWNLOAD_TAX',
				'tokenName' => __( 'Download tax', 'uncanny-automator' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'DOWNLOAD_PRICE',
				'tokenName' => __( 'Download price', 'uncanny-automator' ),
				'tokenType' => 'int',
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
		$download_id = $hook_args[0];

		$cart_items = edd_get_payment_meta_cart_details( $hook_args[2] );
		foreach ( $cart_items as $item ) {
			if ( absint( $item['id'] ) === absint( $download_id ) ) {

				return array(
					'DOWNLOAD_NAME'     => $item['name'],
					'DOWNLOAD_ID'       => $download_id,
					'DOWNLOAD_QTY'      => $item['quantity'],
					'DOWNLOAD_SUBTOTAL' => edd_currency_filter( edd_format_amount( $item['subtotal'] ) ),
					'DOWNLOAD_TAX'      => edd_currency_filter( edd_format_amount( $item['tax'] ) ),
					'DOWNLOAD_PRICE'    => edd_currency_filter( edd_format_amount( $item['price'] ) ),
				);
			}
		}
	}
}
