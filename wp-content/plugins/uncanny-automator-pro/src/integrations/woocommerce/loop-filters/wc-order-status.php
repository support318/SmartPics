<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class WC_ORDER_STATUS
 *
 * @package Uncanny_Automator_Pro
 */
class WC_ORDER_STATUS extends Loop_Filter {

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->set_integration( 'WC' );
		$this->set_meta( 'WC_ORDER_STATUS' );
		$this->set_sentence( esc_html_x( 'An order {{has/does not have}} {{a status}}', 'WooCommerce', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: WooCommerce */
				esc_html_x( 'An order {{has/does not have:%1$s}} {{a status:%2$s}}', 'WooCommerce', 'uncanny-automator-pro' ),
				'CRITERIA',
				$this->get_meta()
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_loop_type( 'posts' );
		$this->set_entities( array( $this, 'retrieve_orders_with_specified_status' ) );
	}

	/**
	 * @return mixed[]
	 */
	public function load_options() {
		return array(
			$this->get_meta() => array(
				array(
					'option_code'           => 'CRITERIA',
					'type'                  => 'select',
					'supports_custom_value' => false,
					'label'                 => esc_html_x( 'Criteria', 'WooCommerce', 'uncanny-automator-pro' ),
					'options'               => array(
						array(
							'text'  => esc_html_x( 'has', 'WooCommerce', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'has', 'WooCommerce', 'uncanny-automator-pro' ),
						),
						array(
							'text'  => esc_html_x( 'does not have', 'WooCommerce', 'uncanny-automator-pro' ),
							'value' => esc_html_x( 'does-not-have', 'WooCommerce', 'uncanny-automator-pro' ),
						),
					),
				),
				array(
					'option_code'           => $this->get_meta(),
					'type'                  => 'select',
					'label'                 => esc_html_x( 'Order status', 'WooCommerce', 'uncanny-automator-pro' ),
					'options'               => $this->get_woo_order_statuses(),
					'supports_custom_value' => false,
				),
			),
		);

	}

	/**
	 * @return array[]
	 */
	private function get_woo_order_statuses() {
		if ( ! function_exists( 'wc_get_order_statuses' ) ) {
			return array();
		}
		$options     = array();
		$wc_statuses = wc_get_order_statuses();

		foreach ( $wc_statuses as $id => $text ) {
			$options[] = array(
				'value' => $id,
				'text'  => $text,
			);
		}

		return $options;
	}

	/**
	 * @param array{WC_ORDER_STATUS:string,CRITERIA:string} $fields
	 *
	 * @return array
	 */
	public function retrieve_orders_with_specified_status( $fields ) {
		$criteria     = $fields['CRITERIA'];
		$order_status = $fields['WC_ORDER_STATUS'];

		if ( empty( $criteria ) || empty( $order_status ) ) {
			return array();
		}

		if ( 'does-not-have' === $criteria ) {
			// Get all possible shop order statuses
			$all_statuses = array_keys( wc_get_order_statuses() );

			// Status to exclude, for example 'wc-completed'
			$exclude_status = $order_status;

			// Remove the status you don't want from the array
			if ( array_key_exists( $exclude_status, $all_statuses ) ) {
				unset( $all_statuses[ $exclude_status ] );
			}

			$order_status = $all_statuses;
		}

		$orders = wc_get_orders(
			array(
				'return' => 'ids', // We're only interested with IDs.
				'limit'  => 99999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'status' => $order_status,
			)
		);

		return ! empty( $orders ) ? $orders : array();
	}
}
