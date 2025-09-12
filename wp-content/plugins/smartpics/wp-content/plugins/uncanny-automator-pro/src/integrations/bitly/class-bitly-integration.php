<?php

namespace Uncanny_Automator_Pro\Integrations\Bitly;

/**
 * Class Bitly_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Bitly_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {
		$this->set_integration( 'WP_BITLY' );
		$this->set_name( 'WP Bitly (Deprecated)' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/bitly-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {
		// Load triggers/actions
		new BITLY_SHORTEN_A_LINK();
	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'Wp_Bitly' );
	}

}
