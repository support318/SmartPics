<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Flsupport_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Flsupport_Integration {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'FLSUPPORT';

	/**
	 * Add_Integration constructor.
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
	public function plugin_active() {
		return defined( 'FLUENT_SUPPORT_VERSION' );
	}

	/**
	 * Set the directories that the auto loader will run in
	 *
	 * @param $directory
	 *
	 * @return array
	 */
	public function add_integration_directory_func( $directory ) {

		$directory[] = dirname( __FILE__ ) . '/helpers';
		$directory[] = dirname( __FILE__ ) . '/tokens';
		$directory[] = dirname( __FILE__ ) . '/actions';
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
				'name'     => 'Fluent Support',
				'icon_svg' => plugin_dir_url( __FILE__ ) . 'img/fluent-support-icon.svg',
			)
		);

	}
}
