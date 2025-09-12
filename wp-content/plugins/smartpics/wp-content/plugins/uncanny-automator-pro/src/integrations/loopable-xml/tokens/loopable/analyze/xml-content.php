<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loopable_Xml\Tokens\Loopable\Analyze;

use Exception;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Xml_Parent_Xpath_Generator;
use Uncanny_Automator\Services\Loopable\Data_Integrations\Xml_To_Json_Converter;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Analyze\Json_Content;
use WP_Error;
use WP_Post;
use WP_REST_Request;

/**
 * @package Uncanny_Automator_Pro\Integrations\Loopable_Xml\Tokens\Loopable\Analyze
 */
class Xml_Content {

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

		// Bail if current request does not belong to Loopable XML.
		if ( 'ACTION_LOOPABLE_XML_META' !== $request->get_param( 'optionCode' ) ) {
			// But allow the trigger.
			if ( 'TRIGGER_LOOPABLE_XML_META' !== $request->get_param( 'optionCode' ) ) {
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
	 * @return void
	 */
	public static function determine_xml_root_paths() {

		Automator()->utilities->verify_nonce();

		$options = array();

		$request = automator_filter_input_array( 'values', INPUT_POST );

		$data_source = $request['DATA_SOURCE'] ?? '';
		$file        = $request['FILE'] ?? '';
		$link        = $request['LINK'] ?? '';

		if ( 'upload' === $data_source ) {
			$xml_content = Json_Content::extract_content_from_the_file_field( (array) $file );
		}

		if ( 'link' === $data_source ) {
			$xml_content = Json_Content::get_remote_url_contents( $link );
		}

		if ( is_wp_error( $xml_content ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => $xml_content->get_error_message(),
				)
			);
		}

		$generator    = new Xml_Parent_Xpath_Generator( $xml_content );
		$parent_paths = $generator->generate_parent_xpaths();

		if ( is_wp_error( $parent_paths ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => $parent_paths->get_error_message(),
				)
			);
		}

		foreach ( (array) $parent_paths as $path ) {
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
		$xpath     = $meta['XPATH'] ?? '';

		try {

			$converter = new Xml_To_Json_Converter();
			$converter->set_xpath( $xpath );

			$converter->load_from_url( $file_link );

			$contents = (array) json_decode( $converter->to_json(), true );

			// Finally, update the post meta with the contents.
			update_post_meta( $item->ID, 'LOOPABLE_XML_CONTENT_FROM_LINK', $contents );

		} catch ( Exception $e ) {

			delete_post_meta( $item->ID, 'LOOPABLE_XML_CONTENT_FROM_LINK' );

			automator_log( $e->getMessage(), 'XML error', true, 'xml-loopable' );

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

		$file  = $meta['FILE'] ?? '';
		$xpath = $meta['XPATH'] ?? '';

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

			$converter = new Xml_To_Json_Converter();
			$converter->load_from_file_path( $path );
			$converter->set_xpath( $xpath );

			$contents = (array) json_decode( $converter->to_json(), true );

			update_post_meta( $item->ID, 'LOOPABLE_XML_CONTENT', $contents );

		} catch ( Exception $e ) {

			delete_post_meta( $item->ID, 'LOOPABLE_XML_CONTENT' );

			automator_log( $e->getMessage(), 'XML error', true, 'xml-content' );
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
	 * @return Uncanny_Automator_Pro\Integrations\Loopable_Xml\Tokens\Loopable\Analyze\WP_Error|array|WP_Error|string
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



}
