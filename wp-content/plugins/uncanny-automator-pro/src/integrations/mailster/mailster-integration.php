<?php

namespace Uncanny_Automator_Pro\Integrations\Mailster;

/**
 * Class Mailster_Integration
 * @package Uncanny_Automator_Pro
 */
class Mailster_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Must use function in new integration to setup all required values
	 *
	 * @return mixed
	 */
	protected function setup() {
		if ( ! class_exists( 'Uncanny_Automator\Integrations\Mailster\Mailster_Helpers' ) ) {
			return false;
		}

		$this->helpers = new \Uncanny_Automator\Integrations\Mailster\Mailster_Helpers();
		$this->set_integration( 'MAILSTER' );
		$this->set_name( 'Mailster' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/mailster-icon.svg' );
	}

	/**
	 * Pass plugin path, i.e., uncanny-automator/uncanny-automator.php to check if plugin is active. By default it
	 * returns true for an integration.
	 *
	 * @return mixed|bool
	 */
	public function plugin_active() {
		return class_exists( 'Mailster' );
	}

	/**
	 * Load.
	 */
	protected function load() {
		//      load triggers
		new MAILSTER_SUBSCRIBER_REMOVED_FROM_LIST( $this->helpers );
		new MAILSTER_SUBSCRIBER_CLICKS_LINK( $this->helpers );
		new MAILSTER_SUBSCRIBER_OPENS_EMAIL( $this->helpers );
		//      load actions
		new MAILSTER_REMOVE_SUBSCRIBER_FROM_LIST( $this->helpers );
	}
}
