<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class WC_ORDERS_BETWEEN_DATES
 *
 * @package Uncanny_Automator_Pro
 */
class WC_ORDERS_BETWEEN_DATES extends Loop_Filter {

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->set_integration( 'WC' );
		$this->set_meta( 'WC_ORDERS_BETWEEN_DATES' );
		$this->set_sentence( esc_html_x( 'An order placed between {{start date}} and {{end date}}', 'WooCommerce', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: WooCommerce */
				esc_html_x( 'An order placed between {{start date:%1$s}} and {{end date:%2$s}}', 'WooCommerce', 'uncanny-automator-pro' ),
				'START_DATE',
				'END_DATE'
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
					'option_code'           => 'START_DATE',
					'type'                  => 'date',
					'supports_custom_value' => false,
					'label'                 => esc_html_x( 'Start date', 'WooCommerce', 'uncanny-automator-pro' ),
				),
				array(
					'option_code'           => 'END_DATE',
					'type'                  => 'date',
					'label'                 => esc_html_x( 'End date', 'WooCommerce', 'uncanny-automator-pro' ),
					'supports_custom_value' => false,
				),
			),
		);

	}

	/**
	 * @param array{WC_ORDERS_BETWEEN_DATES:string,CRITERIA:string} $fields
	 *
	 * @return array
	 */
	public function retrieve_orders_within_range( $fields ) {
		$start_date = $fields['START_DATE'];
		$end_date   = $fields['END_DATE'];

		if ( empty( $start_date ) || empty( $end_date ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'date_created' => "{$start_date}...{$end_date}",
				'return'       => 'ids',
			)
		);

		return ! empty( $orders ) ? $orders : array();
	}
}
