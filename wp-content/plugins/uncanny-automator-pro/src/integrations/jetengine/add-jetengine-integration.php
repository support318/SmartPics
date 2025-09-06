<?php
namespace Uncanny_Automator_Pro;

/**
 * Class Add_Jetengine_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Jetengine_Integration {

	use \Uncanny_Automator\Recipe\Integrations;

	/**
	 * Class construct
	 */
	public function __construct() {

		$this->setup();

	}

	/**
	 * Setup method.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'JETENGINE' );

		$this->set_name( 'JetEngine' );

		$this->set_icon( '/img/jetengine-icon.svg' );

		$this->set_icon_path( __DIR__ . '/img/' );

	}

	/**
	 * Method get_icon_url.
	 *
	 * @return string
	 */
	protected function get_icon_url() {

		return plugins_url( $this->get_icon(), $this->get_icon_path() );

	}

	/**
	 * Checks if plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {

		return class_exists( '\Jet_Engine' );

	}

}
