<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze;

use Exception;
use RuntimeException;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Csv_To_Json_Converter;
use WP_Error;
use WP_Post;
use WP_REST_Request;

/**
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze
 */
class Csv_Content {

	/**
	 * @param WP_Post $item
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public static function on_update_save_content( $item, $request ) {

		// Bail on invalid parameters.
		if ( ! $item instanceof WP_Post || ! $request instanceof WP_REST_Request ) {
			return;
		}

		// Bail if current request does not belong to Loopable CSV.
		if ( 'ACTION_LOOPABLE_CSV_META' !== $request->get_param( 'optionCode' ) ) {
			// But allow the trigger.
			if ( 'TRIGGER_LOOPABLE_CSV_META' !== $request->get_param( 'optionCode' ) ) {
				return;
			}
		}

		// Bail if current request does not belong to the current action.
		if ( absint( $item->ID ) !== absint( $request->get_param( 'itemId' ) ) ) {
			return;
		}

		$meta        = $request->get_param( 'optionValue' );
		$data_source = $meta['DATA_SOURCE'] ?? '';

		if ( 'link' === $data_source ) {
			self::analyze_linked_file( $meta, $item );
			return;
		}

		if ( 'paste' === $data_source ) {
			self::analyze_raw_text( $meta, $item );
		}

		if ( 'upload' === $data_source ) {
			self::analyze_uploaded_file( $meta, $item );
			return;
		}

	}

	/**
	 * @param mixed[] $meta
	 * @param WP_Post $item
	 *
	 * @return void
	 * @throws RuntimeException
	 */
	public static function analyze_raw_text( $meta, $item ) {

		$data      = $meta['DATA'] ?? '';
		$delimiter = $meta['DELIMITER'] ?? '';
		$skip_rows = $meta['SKIP_ROWS'] ?? 0;

		$delimiter = self::convert_delimiter( $delimiter );

		try {

			$converter = new Csv_To_Json_Converter();
			$converter = self::adjust_delimiter( $converter, $delimiter );

			$converter->load_from_text( $data );

			$converter->set_header_row( 0 );
			$converter->set_start_row( absint( $skip_rows ) + 1 );

			$contents = (array) json_decode( $converter->to_json(), true );

			// Finally, update the post meta with the contents.
			update_post_meta( $item->ID, 'LOOPABLE_CSV_DATA', $contents );

		} catch ( Exception $e ) {

			automator_log( $e->getMessage(), 'CSV error', true, 'csv-loopable' );

		}

	}

	/**
	 * Analyse the linked file or response.
	 *
	 * @param mixed[] $meta
	 * @param WP_Post $item
	 *
	 * @return void
	 */
	public static function analyze_linked_file( $meta, $item ) {

		$file_link = $meta['LINK'] ?? '';
		$delimiter = $meta['DELIMITER'] ?? '';
		$skip_rows = $meta['SKIP_ROWS'] ?? 0;

		$delimiter = self::convert_delimiter( $delimiter );

		try {

			$converter = new Csv_To_Json_Converter();
			$converter = self::adjust_delimiter( $converter, $delimiter );

			$converter->load_from_url( $file_link );

			$converter->set_header_row( 0 );
			$converter->set_start_row( absint( $skip_rows ) + 1 );

			$contents = (array) json_decode( $converter->to_json(), true );

			// Finally, update the post meta with the contents.
			update_post_meta( $item->ID, 'LOOPABLE_CSV_CONTENT_FROM_LINK', $contents );

		} catch ( Exception $e ) {

			automator_log( $e->getMessage(), 'CSV error', true, 'csv-loopable' );

			return;
		}

	}

	/**
	 * Analyses uploaded file.
	 *
	 * @param mixed[] $meta
	 * @param WP_Post $item
	 *
	 * @return void
	 */
	public static function analyze_uploaded_file( $meta, $item ) {

		$file       = $meta['FILE'] ?? '';
		$delimiter  = $meta['DELIMITER'] ?? '';
		$skip_rows  = $meta['SKIP_ROWS'] ?? '';
		$has_header = $meta['HAS_HEADER'] ?? '';

		$delimiter = self::convert_delimiter( $delimiter );

		$file_decoded = (array) json_decode( $file, true );

		// Bail if the decoded file array returns and empty array or if the URL is not set from the first file.
		if ( empty( $file_decoded ) || ! isset( $file_decoded[0]['url'] ) ) {
			return;
		}

		$path = self::get_attachment_path_from_url( $file_decoded[0]['url'] );

		// Bail if path can't be found.
		if ( false === $path ) {
			return;
		}

		try {

			$converter = new Csv_To_Json_Converter();
			$converter = self::adjust_delimiter( $converter, $delimiter );

			$converter->load_from_file_path( $path );

			$converter->set_header_row( 0 );
			$converter->set_start_row( absint( $skip_rows ) + 1 );

			$contents = (array) json_decode( $converter->to_json(), true );

			update_post_meta( $item->ID, 'LOOPABLE_CSV_CONTENT', $contents );

		} catch ( Exception $e ) {
			automator_log( $e->getMessage(), 'CSV error', true, 'csv-loopable' );
		}

	}

	/**
	 * @param string $file_url
	 *
	 * @return string|false
	 */
	public static function get_attachment_path_from_url( string $file_url ) {

		// Get the attachment ID from the file URL.
		$attachment_id = attachment_url_to_postid( $file_url );

		// If an attachment is found, get its file path.
		if ( $attachment_id ) {
			$file_path = get_attached_file( $attachment_id );
			return $file_path;
		}

		// If no attachment is found, return false.
		return false;

	}

	/**
	 * Returns the content of a remote file.
	 *
	 * @param mixed $url
	 * @return Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze\WP_Error|array|WP_Error|string
	 */
	public static function get_remote_url_contents( $url ) {

		// Make sure the URL is valid.
		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', 'The URL provided is not valid.' );
		}

		// Use wp_remote_get to fetch the content.
		$response = wp_remote_get( $url );

		// Check if the request returned a valid response.
		if ( is_wp_error( $response ) ) {
			return $response; // Return the error if something went wrong.
		}

		// Check for the HTTP status code to make sure the request was successful.
		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return new WP_Error( 'invalid_response', 'The request did not return a valid response.' );
		}

		// Get the body of the response.
		$body = wp_remote_retrieve_body( $response );

		return $body;
	}


	/**
	 * @param string $delimiter
	 *
	 * @return string
	 */
	public static function convert_delimiter( $delimiter ) {

		if ( ! is_string( $delimiter ) ) {
			$delimiter = 'auto';
		}

		$item = array(
			'auto'      => 'auto',
			'pipe'      => '|',
			'semicolon' => ';',
			'new-tab'   => '\r',
		);

		return $item[ $delimiter ] ?? 'auto';

	}

	/**
	 *
	 * @param Csv_To_Json_Converter $converter
	 * @param string $delimiter
	 *
	 * @return Csv_To_Json_Converter
	 */
	public static function adjust_delimiter( Csv_To_Json_Converter $converter, $delimiter ) {

		if ( ! is_string( $delimiter ) ) {
			return $converter;
		}

		if ( 'auto' === $delimiter ) {
			$converter->set_auto_detect_delimiter( true );
			return $converter;
		}

		$converter->set_delimiter( $delimiter );

		return $converter;

	}


}
