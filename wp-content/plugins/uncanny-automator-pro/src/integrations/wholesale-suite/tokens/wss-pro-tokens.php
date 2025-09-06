<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wss_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Wss_Pro_Tokens {

	public function __construct() {
		add_filter(
			'automator_wholesale_suite_validate_common_lead_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSS_LEAD_APPROVED',
					'WSS_LEAD_REJECTED',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);

		add_filter(
			'automator_wholesale_suite_save_lead_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSS_LEAD_APPROVED',
					'WSS_LEAD_REJECTED',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);

		add_filter(
			'automator_wholesale_suite_parse_lead_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSS_LEAD_APPROVED',
					'WSS_LEAD_REJECTED',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);

		add_filter(
			'automator_wholesale_suite_validate_common_order_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSS_ORDER_PLACED',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);

		add_filter(
			'automator_wholesale_suite_save_order_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSS_ORDER_PLACED',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);

		add_filter(
			'automator_wholesale_suite_parse_order_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSS_ORDER_PLACED',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);
	}
}
