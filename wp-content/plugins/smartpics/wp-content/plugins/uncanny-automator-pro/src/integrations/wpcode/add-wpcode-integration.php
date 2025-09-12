<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class Add_Wpcode_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Wpcode_Integration {

	use Recipe\Integrations;

	/**
	 * Add_Integration constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Integration Set-up.
	 */
	protected function setup() {
		$this->set_integration( 'WPCODE_IHAF' );
		$this->set_name( 'WPCode' );
		$this->set_icon_path( __DIR__ . '/img/' );
		$this->set_icon( '/img/wpcode-icon.svg' );
	}

	/**
	 * Method plugin_active
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'WPCode' ) || class_exists( 'WPCode_Premium' );
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
