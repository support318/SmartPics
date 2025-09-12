<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Ws_Form_Lite_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Ws_Form_Lite_Pro_Tokens {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter(
			'automator_wsformlite_validate_common_trigger_tokens_save',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSFORM_FROM_FIELDVALUE',
					'WSFORM_ANON_FROM_FIELDVALUE',
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
			'automator_wsformlite_validate_common_possible_trigger_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSFORM_FROM_FIELDVALUE',
					'WSFORM_ANON_FROM_FIELDVALUE',
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
			'automator_wsformlite_parse_common_trigger_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'WSFORM_FROM_FIELDVALUE',
					'WSFORM_ANON_FROM_FIELDVALUE',
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

	/**
	 * Hydrate token method for Seats added trigger.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function wsform_field_specific_tokens( $parsed, $args, $trigger ) {

		return $parsed + array(
			'SPECIFIC_FIELD' => $this->get_trigger_option_selected_value( $args['trigger_entry']['trigger_to_match'], 'SPECIFIC_FIELD_readable' ),
			'SPECIFIC_VALUE' => $this->get_trigger_option_selected_value( $args['trigger_entry']['trigger_to_match'], 'SPECIFIC_VALUE' ),
		);

	}

	/**
	 * Directly fetches the value from db.
	 *
	 * @return string The field value.
	 */
	private function get_trigger_option_selected_value( $trigger_id = 0, $meta_key = '' ) {

		if ( empty( $trigger_id ) || empty( $meta_key ) ) {
			return null;
		}

		global $wpdb;

		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s LIMIT 1",
				$trigger_id,
				$meta_key
			)
		);

		if ( empty( $value ) ) {
			return null;
		}

		$value = maybe_unserialize( $value );
		// Check for File Uploads.
		if ( is_array( $value ) && ! empty( $value[0] ) && is_array( $value[0] ) && isset( $value[0]['size'] ) && isset( $value[0]['type'] ) ) {
			$value = $this->get_file_upload_value( $value );
		}

		return $value;
	}

	/**
	 * Get CSV string of file upload URLs.
	 *
	 * @param array $files
	 *
	 * @return string
	 */
	public function get_file_upload_value( $files ) {
		$value = '';
		foreach ( $files as $file ) {
			if ( isset( $file['attachment_id'] ) && ! empty( $file['attachment_id'] ) ) {
				// Get the URL if public.
				$url = wp_get_attachment_url( $file['attachment_id'] );
				if ( empty( $url ) ) {
					// Give the admin URL if not public.
					$url .= admin_url( 'upload.php?item=' . $file['attachment_id'] );
				}
				$value .= $url . ', ';
			}
		}

		// Remove the trailing comma and space.
		$value = ! empty( $value ) ? rtrim( $value, ', ' ) : '';

		// Allow 3rd parties to filter the value - Use same filter as Automator Free.
		return apply_filters( 'automator_wsformlite_file_token', $value, $files );
	}
}
