<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze;

use Uncanny_Automator\Services\Loopable\Data_Integrations\Utils;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Backup;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Fields;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Iterable_Expression;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Backup as Loop_FilterBackup;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Fields as Loop_FilterFields;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter_Query;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Query;
use Uncanny_Automator_Pro\Utilities;
use WP_Error;
use WP_Post;
use WP_REST_Request;

/**
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze
 */
class Json_Content {

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

		// Bail if current request does not belong to Loopable JSON.
		if ( 'ACTION_LOOPABLE_JSON_META' !== $request->get_param( 'optionCode' ) ) {
			// But allow the trigger.
			if ( 'TRIGGER_LOOPABLE_JSON_META' !== $request->get_param( 'optionCode' ) ) {
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

		if ( 'upload' === $data_source ) {
			self::analyze_uploaded_file( $meta, $item );
			return;
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

		$remote_content = self::get_remote_url_contents( $file_link );

		if ( is_wp_error( $remote_content ) ) {
			return;
		}

		$contents = (array) json_decode( $remote_content, true );

		// Finally, update the post meta with the contents.
		update_post_meta( $item->ID, 'LOOPABLE_JSON_CONTENT_FROM_LINK', $contents );

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

		$file = $meta['FILE'] ?? '';

		$file_decoded = (array) json_decode( $file, true );

		$contents = self::extract_content_from_the_file_field( $file_decoded );

		$contents = json_decode( $contents, true );

		// Finally, update the post meta with the contents.
		update_post_meta( $item->ID, 'LOOPABLE_JSON_CONTENT', $contents );

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
	 * Extracts the URL from the uploaded file. Will return the contents of the first file.
	 *
	 * @param array $file The decoded file's field JSON.
	 *
	 * @return string
	 */
	public static function extract_content_from_the_file_field( array $file ) {

		// Bail if json decode returns empty array or the URL is not set from the first file.
		if ( empty( $file ) || ! isset( $file[0]['url'] ) ) {
			return;
		}

		$path = self::get_attachment_path_from_url( $file[0]['url'] );

		// Bail if path can't be found.
		if ( false === $path ) {
			return;
		}

		// This is not a remote file.
		return file_get_contents( $path ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	}

	/**
	 * Determine the root paths.
	 *
	 * @return void
	 */
	public static function determine_root_path() {

		$values      = automator_filter_input_array( 'values', INPUT_POST );
		$data_source = $values['DATA_SOURCE'] ?? '';
		$file        = $values['FILE'] ?? '';
		$file_link   = $values['LINK'] ?? '';
		$file_pasted = $values['DATA'] ?? '';

		if ( 'link' === $data_source ) {

			$contents = self::get_remote_url_contents( $file_link );

			if ( is_wp_error( $contents ) ) {
				wp_send_json(
					array(
						'success' => false,
						'error'   => $contents->get_error_message(),
					)
				);
			}
		}

		if ( 'upload' === $data_source ) {
			$contents = self::extract_content_from_the_file_field( (array) $file );
		}

		if ( 'paste' === $data_source ) {
			$contents = $file_pasted;
		}

		// Empty contents.
		if ( empty( $contents ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => __( 'The JSON file appears to be empty. Unable to proceed with decoding.', 'uncanny-automator-pro' ),
				)
			);
		}

		$contents = (array) json_decode( $contents, true );

		// Json Error.
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => sprintf(
						/* translators: JSON error details */
						__(
							'Failed to decode the JSON file content. Error details: %s',
							'uncanny-automator-pro'
						),
						json_last_error_msg()
					),
				)
			);
		}

		$array_paths = Utils::get_all_iterable_paths( $contents );

		$options = array(
			array(
				'text'  => '$.',
				'value' => '$.',
			),
		);

		foreach ( (array) $array_paths as $path ) {
			$options[] = array(
				'text'  => $path,
				'value' => $path,
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'options' => $options,
			)
		);
	}

}
