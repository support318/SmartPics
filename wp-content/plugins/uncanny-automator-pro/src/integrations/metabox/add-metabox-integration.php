<?php
namespace Uncanny_Automator_Pro;

/**
 * Class Add_Metabox_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Metabox_Integration {

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

		$this->set_integration( 'METABOX' );

		$this->set_name( 'Metabox' );

		$this->set_icon( '/img/metabox-icon.svg' );

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

		// Dependencies: Metabox and Uncanny_Automator 4.3
		return function_exists( 'rwmb_get_object_fields' );

	}

}
