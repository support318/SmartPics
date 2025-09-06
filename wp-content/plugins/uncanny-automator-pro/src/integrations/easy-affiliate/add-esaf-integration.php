<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Esaf_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Esaf_Integration {
	use \Uncanny_Automator\Recipe\Integrations;

	/**
	 * Add_Edd_Integration constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup method
	 *
	 * @return void
	 */
	protected function setup() {
		$this->set_integration( 'ESAF' );
		$this->set_name( 'Easy Affiliate' );
		$this->set_icon( 'easy-affiliate-icon.svg' );
		$this->set_icon_path( __DIR__ . '/img/' );
		$this->set_plugin_file_path( 'easy-affiliate/easy-affiliate.php' );
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
	 * @return bool
	 */
	public function plugin_active() {
		return defined( 'ESAF_PLUGIN_SLUG' );
	}
}
