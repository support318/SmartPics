<?php

namespace Uncanny_Automator_Pro\Integrations\M4IS;

/**
 * Class Memberium_Integration
 *
 * @package Uncanny_Automator
 */
class M4IS_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {
		$this->set_integration( 'M4IS' );
		$this->set_name( 'Memberium for Keap' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/memberium-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {

		$helper = new M4IS_HELPERS_PRO();

		// Load actions.
		new M4IS_ADD_USER_MEMBERSHIP_LEVEL( $helper );
		new M4IS_REMOVE_USER_MEMBERSHIP_LEVEL( $helper );
		new M4IS_ADD_TAG_CONTACT( $helper );
		new M4IS_REMOVE_TAG_CONTACT( $helper );
		new M4IS_ADD_OR_REMOVE_CONTACT_TAGS( $helper );

		// Load conditions.
		new M4IS_USER_MEMBERSHIP_ACCESS();
	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return defined( 'MEMBERIUM_SKU' ) && strtolower( MEMBERIUM_SKU ) === 'm4is';
	}

}
