<?php
namespace Uncanny_Automator_Pro;

/**
 * Class Add_Konnectz_It_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Konnectz_It_Integration {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'KONNECTZ_IT';

	/**
	 * Only load this integration and its triggers and actions if the related plugin is active
	 *
	 * @param $status
	 * @param $plugin
	 *
	 * @return bool
	 */
	public function plugin_active( $status, $plugin ) {

		$status = true;

		return $status;

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
				'name'     => 'KONNECTZ_IT',
				'icon_svg' => \Uncanny_Automator\Utilities::get_integration_icon( 'konnectz-it-icon.svg' ),
			)
		);

	}

}
