<?php
namespace Uncanny_Automator_Pro;

/**
 * Class Mailerlite_Helpers
 *
 * @package Uncanny_Automator
 */
class Add_Mailerlite_Integration {

	use \Uncanny_Automator\Recipe\Integrations;

	public function __construct() {

		$this->setup();

	}

	/**
	 * Method setup.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'MAILERLITE' );

		$this->set_name( 'MailerLite' );

		$this->set_icon( '/img/mailerlite-icon.svg' );

		$this->set_icon_path( __DIR__ . '/img/' );

		$this->set_connected( $this->get_is_connected() );

		$this->set_settings_url( automator_get_premium_integrations_settings_url( 'mailerlite' ) );

	}

	/**
	 * Determines whether the user is connected or not connected.
	 *
	 * @return bool
	 */
	public function get_is_connected() {

		return '' !== automator_pro_get_option( 'automator_mailerlite_api_token', '' )
			&& '' !== automator_pro_get_option( 'automator_mailerlite_client', '' );

	}

	/**
	 * Method plugin_active
	 *
	 * @return bool True if constant `AUTOMATOR_PLUGIN_VERSION` is >= 4.8. Otherwise, false.
	 */
	public function plugin_active() {

		return version_compare( AUTOMATOR_PLUGIN_VERSION, '4.8', '>=' );

	}

	/**
	 * Method get_icon_url.
	 *
	 * @return string
	 */
	protected function get_icon_url() {

		return plugins_url( $this->get_icon(), $this->get_icon_path() );

	}

}
