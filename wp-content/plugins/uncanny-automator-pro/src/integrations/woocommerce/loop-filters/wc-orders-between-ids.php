<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class WC_ORDERS_BETWEEN_IDS
 *
 * @package Uncanny_Automator_Pro
 */
class WC_ORDERS_BETWEEN_IDS extends Loop_Filter {

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->set_integration( 'WC' );
		$this->set_meta( 'WC_ORDERS_BETWEEN_IDS' );
		$this->set_sentence( esc_html_x( 'An order ID is between {{a start ID}} and {{end ID}}', 'WooCommerce', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: WooCommerce */
				esc_html_x( 'An order ID is between {{a start ID:%1$s}} and {{end ID:%2$s}}', 'WooCommerce', 'uncanny-automator-pro' ),
				'START_ID',
				'END_ID'
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_loop_type( 'posts' );
		$this->set_entities( array( $this, 'retrieve_orders_within_range' ) );
	}

	/**
	 * @return mixed[]
	 */
	public function load_options() {
		return array(
			$this->get_meta() => array(
				array(
					'option_code'           => 'START_ID',
					'type'                  => 'int',
					'supports_custom_value' => false,
					'label'                 => esc_html_x( 'Start ID', 'WooCommerce', 'uncanny-automator-pro' ),
				),
				array(
					'option_code'           => 'END_ID',
					'type'                  => 'int',
					'label'                 => esc_html_x( 'End ID', 'WooCommerce', 'uncanny-automator-pro' ),
					'supports_custom_value' => false,
				),
			),
		);

	}

	/**
	 * @param array{WC_ORDERS_BETWEEN_IDS:string,CRITERIA:string} $fields
	 *
	 * @return array
	 */
	public function retrieve_orders_within_range( $fields ) {
		$start_id = $fields['START_ID'];
		$end_id   = $fields['END_ID'];

		if ( empty( $start_id ) || empty( $end_id ) ) {
			return array();
		}

		global $wpdb;
		$order_ids = array();

		$orders = $wpdb->get_results(
			$wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order' AND `ID` BETWEEN %d AND %d", $start_id, $end_id ),
			ARRAY_A
		);

		foreach ( $orders as $order ) {
			$order_ids[] = $order['ID'];
		}

		return $order_ids;
	}
}
