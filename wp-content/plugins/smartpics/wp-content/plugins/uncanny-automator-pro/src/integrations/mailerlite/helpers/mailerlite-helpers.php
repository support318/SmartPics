<?php
namespace Uncanny_Automator_Pro;

use Exception;

/**
 * Class Mailerlite_Helpers
 *
 * @package Uncanny_Automator
 */
class Mailerlite_Helpers {

	protected $mailerlite_settings = null;

	const TRANSIENT_GROUPS = 'automator_mailerlite_groups_items';

	const API_URL = 'https://connect.mailerlite.com/api';

	const API_TOKEN = 'automator_mailerlite_api_token';

	const CLIENT = 'automator_mailerlite_client';

	protected $http = array();

	/**
	 * The classic API URL.
	 *
	 * Using classic API URL as a fallback. The fetch groups in their V2 API doesn't work properly.
	 *
	 * @var string
	 */
	const CLASSIC_API_URL = 'https://api.mailerlite.com/api/v2';

	protected $use_classic_api = false;

	public function __construct( $load_action_hooks = true ) {

		if ( $load_action_hooks ) {

			$this->load_action_hooks();

		}

		$this->mailerlite_settings = new Mailerlite_Settings( $this );

	}


	/**
	 * Fetches all MailerLite Groups.
	 *
	 * @return array The list of MailerLite Groups.
	 */
	public function fetch_groups() {

		$groups_response = get_transient( self::TRANSIENT_GROUPS );

		$group_fields = array();

		if ( ! $this->is_connected() ) {

			return array(
				'Error 403' => 'MailerLite is not connected',
			);

		}

		if ( false === $groups_response ) {

			try {

				$groups_response = $this->http( 'GET' )->request( 'groups', array() );

				set_transient( self::TRANSIENT_GROUPS, $groups_response, 5 * MINUTE_IN_SECONDS );

			} catch ( \Exception $e ) {

				return array(
					'Error ' . $e->getCode() => $e->getMessage(),
				);

			}
		}

		$groups_response = (array) json_decode( $groups_response );

		if ( isset( $groups_response['data'] ) ) {

			foreach ( $groups_response['data'] as $group ) {

				$group_fields[ '_' . $group->id ] = $group->name;

			}
		}

		return $group_fields;

	}

	/**
	 * Sets use_classic_api property to true.
	 *
	 * @return self
	 */
	public function with_classic_api() {

		$this->use_classic_api = true;

		return $this;

	}

	/**
	 * Loads action hooks.
	 *
	 * @return void
	 */
	private function load_action_hooks() {
		// Action hooks here.
		add_action( 'wp_ajax_automator_mailerlite_disconnect', array( $this, 'disconnect' ), 10 );
		add_action( 'wp_ajax_automator_mailerlite_fetch_custom_fields', array( $this, 'fetch_custom_fields' ), 10 );
	}

	/**
	 * Disconnectes the user.
	 *
	 * @return void
	 */
	public function disconnect() {

		// Check if admin and nonce is valid.
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( automator_filter_input( 'nonce' ), 'automator_mailerlite_nonce' ) ) {
			http_response_code( 403 );
			die;
		}

		automator_pro_delete_option( $this->mailerlite_settings->get_client_key() );

		automator_pro_delete_option( $this->mailerlite_settings->get_token_key() );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'   => 'uo-recipe',
					'page'        => 'uncanny-automator-config',
					'tab'         => 'premium-integrations',
					'integration' => 'mailerlite',
				),
				admin_url( 'edit.php' )
			)
		);

		die;

	}

	/**
	 * Callback method to "wp_ajax_automator_mailerlite_fetch_custom_fields" action.
	 *
	 * @return void
	 */
	public function fetch_custom_fields() {

		$rows = array();

		try {

			$response         = $this->http( 'GET' )->request( 'fields', array() );
			$response_decoded = (array) json_decode( $response, true );

			$custom_fields = (array) $response_decoded['data'] ?? array();

			foreach ( $custom_fields as $field ) {

				//	elog( $field );
				$rows[] = array(
					'FIELD_ID'   => $field['key'],
					'FIELD_NAME' => '',
				);

			}
		} catch ( Exception $e ) {

			wp_send_json(
				array(
					'success' => false,
					'error'   => esc_html_x( 'An error has occured while fetching data: ', $e->getMessage() ),
				)
			);

		}

		$response = array(
			'success' => true,
			'rows'    => $rows,
		);

		wp_send_json( $response );

	}

	/**
	 * Retrieves the disconnect URL.
	 *
	 * @return string The disconnect URL.
	 */
	public function get_disconnect_url() {

		return add_query_arg(
			array(
				'action' => 'automator_mailerlite_disconnect',
				'nonce'  => wp_create_nonce( 'automator_mailerlite_nonce' ),
			),
			admin_url( 'admin-ajax.php' )
		);

	}

	/**
	 * Retrieves the MailerLite Client.
	 *
	 * @return bool|array False if client is not connected. Otherwise, the Client in assoc array format.
	 */
	public function get_client() {

		return automator_pro_get_option( self::CLIENT, false );

	}

	/**
	 * Determines whether the user is connect.
	 *
	 * @return boolean
	 */
	public function is_connected() {

		return ! empty( $this->get_client() );

	}

	/**
	 * Prepares HTTP Client.
	 *
	 * @param string $method The HTTP Method e.g. 'PUT', 'DELETE', 'GET', 'POST'.
	 * @param string $content_type The type of content to send.
	 *
	 * @return self
	 */
	public function http( $method = '', $content_type = 'application/json' ) {

		if ( empty( $method ) ) {
			throw new \Exception( 'HTTP Request Method cannot be empty', 500 );
		}

		$this->http['method'] = $method;

		$this->http['Content-Type'] = $content_type;

		return $this;

	}

	/**
	 * Sends a request to specified endpoint along with specified request parameters.
	 *
	 * @param string $endpoint The endpoint.
	 * @param array $params The parameter to send.
	 *
	 * @throws Exception
	 *
	 * @return mixed
	 */
	public function request( $endpoint = '', $params = array() ) {

		$default = array(
			'headers' => array(
				'Content-Type' => $this->http['Content-Type'],
				'Connection'   => 'keep-alive',
				'User-Agent'   => function_exists( 'curl_version' ) && isset( curl_version()['version'] ) ? 'curl/' . curl_version()['version'] : $_SERVER['HTTP_USER_AGENT'],
			),
			'body'    => array(),
			'timeout' => 30,
			'method'  => $this->http['method'],
		);

		// Use bearer token if not using classic api.
		if ( ! $this->use_classic_api ) {
			$default['headers']['Authorization'] = 'Bearer ' . automator_pro_get_option( self::API_TOKEN, '' );
		}

		// Use classic authentication if using classic API.
		if ( $this->use_classic_api ) {
			$default['headers']['X-MailerLite-ApiKey'] = automator_pro_get_option( self::API_TOKEN, '' );
		}

		$args = wp_parse_args( $params, $default );

		$response = wp_remote_request(
			$this->get_endpoint_url( $endpoint ),
			$args
		);

		if ( is_wp_error( $response ) ) {

			throw new \Exception( $response->get_error_message(), $response->get_error_code() );

		}

		$response_code = wp_remote_retrieve_response_code( $response );

		$body = wp_remote_retrieve_body( $response );

		if ( false === ( $response_code >= 200 && $response_code <= 299 ) ) {

			$body = json_decode( $body, true );

			$error = isset( $body['error']['message'] ) ? $body['error']['message'] : wp_json_encode( $body );

			if ( 'null' === $error ) {
				$error = 'MailerLite API has returned an empty response.';
			}

			throw new \Exception( $error, $response_code );

		}

		return $body;

	}

	/**
	 * Retrieves the endpoint URL.
	 *
	 * @param string $endpoint The endpoint.
	 *
	 * @return string The MailerLite API URL and Endpoint.
	 */
	private function get_endpoint_url( $endpoint = '' ) {

		$api_url = $this->use_classic_api ? self::CLASSIC_API_URL : self::API_URL;

		return trailingslashit( $api_url ) . $endpoint;

	}

}
