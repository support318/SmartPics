<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Advanced_Ads_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Advanced_Ads_Integration {


	use \Uncanny_Automator\Recipe\Integrations;

	/**
	 * Add_Advanced_Ads_Integration constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 *
	 */
	protected function setup() {
		$this->set_integration( 'ADVADS' );
		$this->set_name( 'Advanced Ads' );
		$this->set_icon( 'advanced-a-d-s-icon.svg' );
		$this->set_icon_path( __DIR__ . '/img/' );
		$this->set_plugin_file_path( 'advanced-ads/advanced-ads.php' );
	}

	/**
	 * @return bool
	 */
	public function plugin_active() {
		return class_exists( 'Advanced_Ads' );
	}

	/**
	 * Method get_icon_url.
	 *
	 * @return string
	 */
	protected function get_icon_url() {

		return plugins_url( $this->get_icon(), $this->get_icon_path() );

	}

}
