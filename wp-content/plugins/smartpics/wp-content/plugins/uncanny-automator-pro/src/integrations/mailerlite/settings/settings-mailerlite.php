<?php
namespace Uncanny_Automator_Pro;

/**
 * Mailerlite_Settings
 *
 * @since 4.7
 */
class Mailerlite_Settings {

	/**
	 * Imports Premium_Integration Traits from Uncanny_Automator.
	 */
	use \Uncanny_Automator\Settings\Premium_Integrations;

	/**
	 * MailerLite Helper.
	 *
	 * @var \Uncanny_Automator_Pro\Mailerlite_Helpers
	 */
	protected $helper = null;

	/**
	 * The token used for communicating with MailerLite.
	 *
	 * @var string
	 */
	private $api_token = '';

	/**
	 * The option key of the token from the DB.
	 *
	 * @var string
	 */
	const API_TOKEN = 'automator_mailerlite_api_token';

	/**
	 * The option key of the client from the DB.
	 *
	 * @var string
	 */
	const CLIENT = 'automator_mailerlite_client';

	/**
	 * Setup settings page.
	 *
	 * @param $helper Instance of MailerLite helper class.
	 *
	 * @return void
	 */
	public function __construct( $helper = null ) {

		add_filter( 'sanitize_option_' . self::API_TOKEN, array( $this, 'validate_api_token' ), 10, 3 );

		$this->helper = $helper;

		$this->setup_settings();

	}

	/**
	 * Retrieves the MailerLite's settings option key.
	 *
	 * @return string The option key.
	 */
	public function get_client_key() {

		return self::CLIENT;

	}

	/**
	 * Retrieves token option key from DB.
	 *
	 * @return string The option key.
	 */
	public function get_token_key() {

		return self::API_TOKEN;

	}

	/**
	 * Validates the token value from settings.
	 *
	 * @param string $token The token.
	 * @param string $option_name The name of the option.
	 * @param string $original_input The name of the original input.
	 *
	 * @return boolean|mixed False if invalid. Otherwise, the response from MailerLite.
	 */
	public function validate_api_token( $token, $option_name, $original_input ) {

		$cache_key = $option_name . '_validated';

		// Prevents duplicate.
		if ( wp_cache_get( $cache_key ) ) {
			return $token;
		}

		$connection_response = $this->with_api_token( $token )->test_connection();

		if ( is_wp_error( $connection_response ) ) {

			add_settings_error(
				'automator_mailerlite_connection_alerts',
				__( 'Authentication error', 'uncanny-automator-pro' ),
				$connection_response->get_error_code() . ' &mdash; ' . $connection_response->get_error_message(),
				'error'
			);

			Automator()->cache->set( $cache_key, true );

			return false;

		}

		$body = json_decode( wp_remote_retrieve_body( $connection_response ), true );

		$status_code = wp_remote_retrieve_response_code( $connection_response );

		if ( 200 !== $status_code ) {

			add_settings_error(
				'automator_mailerlite_connection_alerts',
				__( 'Authentication error', 'uncanny-automator-pro' ),
				$body['message'],
				'error'
			);

			wp_cache_set( $cache_key, true );

			return false;

		}

		$this->update_client( $body );

		add_settings_error(
			'automator_mailerlite_connection_alerts',
			__( 'Your account has been connected successfully!', 'uncanny-automator-pro' ),
			'',
			'success'
		);

		Automator()->cache->set( $cache_key, true );

		return $token;

	}

	/**
	 * Sets the api_token property.
	 *
	 * @param string $token The token.
	 *
	 * @return self
	 */
	public function with_api_token( $token ) {

		$this->api_token = $token;

		return $this;

	}

	/**
	 * Updates the client.
	 *
	 * @param mixed $client The client. Usually a response from MailerLite.
	 *
	 * @return void
	 */
	protected function update_client( $client = '' ) {

		automator_pro_update_option( self::CLIENT, $client, false );

	}

	/**
	 * Determines whether the connection can be established with MailerLite.
	 *
	 * @return mixed The response from MailerLite API.
	 */
	protected function test_connection() {

		$payload = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_token,
			),
		);

		$response = wp_remote_get(
			// Use /me endpoint to test the connection.
			'https://connect.mailerlite.com/api/account',
			$payload
		);

		return $response;

	}

	/**
	 * Set settings basic properties.
	 *
	 * @return void
	 */
	protected function set_properties() {

		$this->register_option( self::API_TOKEN );

		$this->set_id( 'mailerlite' );

		$this->set_icon( 'MAILERLITE' );

		$this->set_name( 'MailerLite' );

		$this->set_status( $this->helper->is_connected() ? 'success' : '' );

	}


	/**
	 * Settings page output.
	 *
	 * @return void
	 */
	public function output() {

		$vars = array(
			'knb_link'       => automator_utm_parameters( 'https://automatorplugin.com/knowledge-base/mailerlite/', 'settings', 'mailerlite-kb_article' ),
			'api_token'      => automator_pro_get_option( self::API_TOKEN, '' ),
			'alerts'         => (array) get_settings_errors( 'automator_mailerlite_connection_alerts' ),
			'client'         => $this->helper->get_client(),
			'is_connected'   => $this->helper->is_connected(),
			'disconnect_url' => $this->helper->get_disconnect_url(),
		);

		include_once 'view-mailerlite.php';

	}


}
