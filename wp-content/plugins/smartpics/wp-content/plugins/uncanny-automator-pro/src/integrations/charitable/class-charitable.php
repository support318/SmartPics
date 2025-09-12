<?php

namespace Uncanny_Automator_Pro\Integrations\Charitable;

/**
 * Class Charitable_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Charitable_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'CHARITABLE' );
		$this->set_name( 'Charitable' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/charitable-icon.svg' );

	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	protected function load() {

		// Triggers.
		new ANON_CHARITABLE_CAMPAIGN_DONATION_AMOUNT( $this );
		// Check for Charitable Recurring Add On.
		if ( class_exists( 'Charitable_Recurring' ) ) {
			// Recurring Triggers.
			new ANON_CHARITABLE_RECURRING_CAMPAIGN_DONATION_CANCELLED( $this );
			new ANON_CHARITABLE_RECURRING_CAMPAIGN_DONATION_MADE( $this );
		}

		// Actions.
		new ANON_CHARITABLE_ADD_DONATION_LOG_ENTRY( $this );

	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'Charitable' );
	}

	/**
	 * Helper Class Instance.
	 *
	 * @return CHARITABLE_HELPERS_PRO
	 */
	public function helpers() {
		static $helper = null;
		if ( is_null( $helper ) ) {
			$helper = new CHARITABLE_HELPERS_PRO();
		}

		return $helper;
	}

}
