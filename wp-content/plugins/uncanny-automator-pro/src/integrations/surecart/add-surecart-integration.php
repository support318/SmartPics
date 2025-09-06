<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class Add_Surecart_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Surecart_Integration {

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

		$this->set_integration( 'SURECART' );

		$this->set_name( 'SureCart' );

		$this->set_icon( __DIR__ . '/img/surecart-icon.svg' );
	}

	/**
	 * automator_free_requirement_met
	 *
	 * @return bool
	 */
	public function automator_free_requirement_met() {

		if ( ! defined( 'AUTOMATOR_PLUGIN_VERSION' ) || version_compare( AUTOMATOR_PLUGIN_VERSION, '4.5', '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Only load this integration and its triggers and actions if the related plugin is active
	 *
	 * @param $status
	 * @param $plugin
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return defined( 'SURECART_PLUGIN_FILE' ) && $this->automator_free_requirement_met();
	}

}
