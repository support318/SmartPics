<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Qr_Code_Integration
 *
 * @package Uncanny_Automator
 */
class Add_Qr_Code_Integration {

	use \Uncanny_Automator\Recipe\Integrations;

	/**
	 *
	 */
	public function __construct() {

		// A patch for magic triggers.
		$this->setup();
	}

	/**
	 * Method setup.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'AUTOMATOR_QR_CODE' );

		$this->set_name( 'QR Code' );

		$this->set_icon( '/img/qr-code-icon.svg' );

		$this->set_icon_path( __DIR__ . '/img/' );
	}

	/**
	 * Method plugin_active
	 *
	 * Checks if the server is running PHP version 7.4 or higher.
	 *
	 * @return bool True if PHP version is 7.4 or higher, false otherwise.
	 */
	public function plugin_active() {
		// Check if the current PHP version is 7.4 or higher
		if ( version_compare( PHP_VERSION, '7.4', '>=' ) ) {
			return true;
		}

		return false;
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
