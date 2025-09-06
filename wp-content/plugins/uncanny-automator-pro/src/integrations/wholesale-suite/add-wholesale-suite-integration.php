<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class Add_Wholesale_Suite_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Wholesale_Suite_Integration {

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
		$this->set_integration( 'WHOLESALESUITE' );
		$this->set_name( 'Wholesale Suite' );
		$this->set_icon_path( __DIR__ . '/img/' );
		$this->set_icon( 'wholesale-suite.svg' );
	}

	/**
	 * Method plugin_active
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'WooCommerceWholeSalePrices' );
	}

	/**
	 * @return string
	 */
	protected function get_icon_url() {
		return plugins_url( $this->get_icon(), $this->get_icon_path() );
	}

}
