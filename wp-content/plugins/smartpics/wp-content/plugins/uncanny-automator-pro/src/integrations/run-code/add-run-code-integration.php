<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Magic_Button_Integration
 *
 * @package Uncanny_Automator
 */
class Add_Run_Code_Integration {

	use \Uncanny_Automator\Recipe\Integrations;

	/**
	 *
	 */
	public function __construct() {

		$this->setup();
	}

	/**
	 * Method setup.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'RUN_CODE' );

		$this->set_name( 'Run Code' );

		$this->set_icon( '/img/run-code-icon.svg' );

		$this->set_icon_path( __DIR__ . '/img/' );
	}

	/**
	 * Method plugin_active
	 *
	 * @return bool
	 */
	public function plugin_active() {

		return true;
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
