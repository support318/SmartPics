<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Thrivecart_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Thrivecart_Integration {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'THRIVECART';

	/**
	 * Add_Thrivecart_Integration constructor.
	 */
	public function __construct() {
	}

	/**
	 * Only load this integration and its triggers and actions if the related plugin is active
	 *
	 * @param $status
	 * @param $code
	 *
	 * @return bool
	 */
	public function plugin_active( $status, $code ) {
		return true;
	}

	/**
	 * Set the directories that the auto loader will run in
	 *
	 * @param $directory
	 *
	 * @return array
	 */
	public function add_integration_directory_func( $directory ) {

		$directory[] = dirname( __FILE__ ) . '/triggers';

		return $directory;
	}

	/**
	 * Register the integration by pushing it into the global automator object
	 */
	public function add_integration_func() {

		Automator()->register->integration(
			self::$integration,
			array(
				'name'     => 'ThriveCart',
				'icon_svg' => plugin_dir_url( __FILE__ ) . 'img/thrivecart-icon.svg',
			)
		);

	}
}
