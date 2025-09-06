<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator_Pro\Integrations\WPMU\Tokens\Loopable\Universal\Network_Sites;
use Uncanny_Automator_Pro\Integrations\WPMU\Tokens\Loopable\Universal\Network_Admins;

/**
 * Class Add_Wpmu_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Wpmu_Integration {

	use \Uncanny_Automator\Recipe\Integrations;

	/**
	 * Class construct
	 */
	public function __construct() {

		$this->setup();

	}

	/**
	 * Setup method.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'WPMU' );

		$this->set_name( 'WP Multisite' );

		$this->set_icon( '/img/wordpress-icon.svg' );

		$this->set_icon_path( __DIR__ . '/img/' );

		if ( method_exists( $this, 'set_loopable_tokens' ) ) {
			$this->set_loopable_tokens(
				array(
					'WP_NETWORK_ADMINS' => Network_Admins::class,
					'WP_NETWORK_SITES'  => Network_Sites::class,
				)
			);
		}

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
	 * Checks if plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return is_multisite();
	}
}
