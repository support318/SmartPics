<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Uncanny_Automator_Pro;

/**
 * Class Open_AI_Helpers
 *
 * @package Uncanny_Automator
 */
class Open_AI_Pro_Helpers {

	/**
	 * The OpenAI API URL.
	 *
	 * @var string With trailing slash.
	 */
	const API_URL = 'https://api.openai.com/v1/';

	/**
	 * The option key stored in the options table.
	 *
	 * @var string The option key.
	 */
	const OPTION_KEY = 'automator_open_ai_secret';

	/**
	 * The HTTP Response.
	 *
	 * @var WP_HTTP_Response|WP_Error Instance of WP_HTTP_Response after a successful request. An instance WP_Error when there are errors.
	 */
	protected $response = null;

	/**
	 * Determine whether the user is connected or not.
	 *
	 * @return bool True if there is an option key. Otherwise, false.
	 */
	public function is_connected() {

		return ! empty( automator_pro_get_option( self::OPTION_KEY, false ) );

	}

	/**
	 * Sends direct request to OpenAI completions endpoint.
	 *
	 * @param array $args The payload arguments.
	 *
	 * @return self
	 */
	public function send_request( $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'body' => array(),
			)
		);

		$payload = apply_filters(
			'automator_openai_send_request_payload',
			array(
				'headers'     => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . automator_pro_get_option( self::OPTION_KEY, '' ),
				),
				'body'        => wp_json_encode( $args['body'] ),
				'method'      => 'POST',
				'data_format' => 'body',
				'timeout'     => 60,
			)
		);

		$this->response = wp_remote_post(
			self::API_URL . 'completions',
			$payload
		);

		return $this;

	}

	/**
	 * Determines whether the response is Ok.
	 *
	 * Throws Exception when its not.
	 *
	 * @throws \Exception An approriate error message.
	 *
	 * @return self
	 */
	public function check_response() {

		if ( is_wp_error( $this->response ) ) {
			throw new \Exception( $this->response->get_error_message(), $this->response->get_error_code() );
		}

		$response_code = wp_remote_retrieve_response_code( $this->response );
		$response_body = wp_json_encode( wp_remote_retrieve_body( $this->response ) );

		if ( 200 !== $response_code ) {
			throw new \Exception( 'Request to OpenAI returned with status: ' . $response_code . ' ' . $response_body, $response_code );
		}

		return $this;
	}

	/**
	 * Retrieves the json_decoded response of a request.
	 *
	 * @return array|false The json_decoded response. Returns false if the response is invalid json.
	 */
	public function get_response_body() {

		return json_decode( wp_remote_retrieve_body( $this->response ), true );

	}

}
