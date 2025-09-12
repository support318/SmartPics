<?php

namespace Uncanny_Automator_Pro\Integrations\Wp_Admin;

/**
 * Class Wp_Admin_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Wp_Admin_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {
		$this->helpers = new Wp_Admin_Helpers();
		$this->set_integration( 'WP_ADMIN' );
		$this->set_name( 'WP Admin' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/wordpress-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {
		// Load triggers.
		new WP_ADMIN_WP_VERSION_UPDATED( $this->helpers );
		new WP_ADMIN_THEME_UPDATED( $this->helpers );
		new WP_ADMIN_PLUGIN_UPDATED( $this->helpers );
		new WP_ADMIN_PLUGIN_ACTIVATED_DEACTIVATED( $this->helpers );
		new WP_ADMIN_THEME_SWITCH( $this->helpers );

		// Load actions.
		// Load ajax methods.
	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return true;
	}
}
