<?php

namespace Uncanny_Automator_Pro\Integrations\EDD_SL;

use Uncanny_Automator\Integrations\Edd_SL\Edd_Sl_Helpers;

/**
 * Class Edd_Software_Licensing_Integration;
 *
 * @package Uncanny_Automator
 */
class Edd_Software_Licensing_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {
		if ( ! class_exists( 'Uncanny_Automator\Integrations\Edd_SL\Edd_Sl_Helpers' ) ) {
			return;
		}
		$this->helpers = new Edd_Sl_Helpers();
		$this->set_integration( 'EDD_SL' );
		$this->set_name( 'EDD - Software Licensing' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/easy-digital-downloads-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {
		// Load conditions.
		new EDD_SL_CONDITION_ACTIVE_LICENSE_FOR_DOWNLOAD();
	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'EDD_SL_Requirements_Check' );
	}

}
