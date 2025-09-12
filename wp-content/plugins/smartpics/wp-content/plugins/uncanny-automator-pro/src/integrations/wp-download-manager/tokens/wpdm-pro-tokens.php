<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wpdm_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Wpdm_Pro_Tokens {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter(
			'automator_wpdm_validate_trigger_meta_pieces_common',
			function ( $codes, $data ) {
				$trigger_codes = array( 'USER_DOWNLOADS_FILE_CODE' );
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
