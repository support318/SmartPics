<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class Add_Ws_Form_Lite_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Ws_Form_Lite_Integration {

	use Recipe\Integrations;

	/**
	 * Add_Edd_Integration constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 *
	 */
	protected function setup() {
		$this->set_integration( 'WSFORMLITE' );
		$this->set_name( 'WS Form' );
		$this->set_icon( 'ws-form-icon.svg' );
		$this->set_icon_path( __DIR__ . '/img/' );
	}

	/**
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'WS_Form' );
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
