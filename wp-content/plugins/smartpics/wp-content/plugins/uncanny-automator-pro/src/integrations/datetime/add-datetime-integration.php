<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Add_Date_Time_Integration class
 *
 * @package Uncanny_Automator
 */
class Add_DateTime_Integration {

	use Recipe\Integrations;

	/**
	 * Construct.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	protected function setup() {
		$this->set_integration( 'DATETIME' );
		$this->set_name( 'Date and time' );
		$this->set_icon( 'img/date-time-icon.svg' );
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
	 * Integration dependencies.
	 *
	 * @return boolean True
	 */
	public function plugin_active() {
		return true;
	}

	/**
	 * @param $directory
	 * @param $path
	 *
	 * @return array|mixed
	 */
	public function add_integration_directory_func( $directory = array(), $path = '' ) {
		$directory[] = dirname( $path ) . '/helpers';
		$directory[] = dirname( $path ) . '/actions';
		//      $directory[] = dirname( $path ) . '/triggers';
		//      $directory[] = dirname( $path ) . '/tokens';
		$directory[] = dirname( $path ) . '/conditions';
		$directory[] = dirname( $path ) . '/img';

		return $directory;
	}
}
