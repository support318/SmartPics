<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class Add_Jet_Crm_Integration
 * @package Uncanny_Automator_Pro
 */
class Add_Jet_Crm_Integration {


	use Recipe\Integrations;

	/**
	 * Add_Edd_Integration constructor.
	 */
	public function __construct() {
		 $this->setup();
	}

	/**
	 * Integration setup
	 */
	protected function setup() {
		$this->set_integration( 'JETCRM' );
		$this->set_name( 'Jetpack CRM' );
		$this->set_icon( 'jetpack-crm-icon.svg' );
		$this->set_icon_path( __DIR__ . '/img/' );
	}

	/**
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'ZeroBSCRM' );
	}

	/**
	 * @return string
	 */
	protected function get_icon_url() {
		 return plugins_url( $this->get_icon(), $this->get_icon_path() );
	}
}
