<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wpai_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Wpai_Pro_Tokens {
	public function __construct() {
		add_filter(
			'automator_wpai_save_common_triggers_tokens_for_import',
			function ( $codes, $data ) {
				$trigger_codes = array( 'WPAI_IMPORT_FAIL' );
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);
		add_filter(
			'automator_wpai_common_possible_tokens_for_import',
			function ( $codes, $data ) {
				$trigger_codes = array( 'WPAI_IMPORT_FAIL' );
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);
		add_filter(
			'automator_wpai_parse_common_tokens_related_import',
			function ( $codes, $data ) {
				$trigger_codes = array( 'WPAI_IMPORT_FAIL' );
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
