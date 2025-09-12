<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Uotc_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Add_Uotc_Integration {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'UOTC';

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
	public function plugin_active( $status, $code ) {
		return defined( 'LEARNDASH_VERSION' ) && defined( 'UNCANNY_REPORTING_VERSION' );
	}


	/**
	 * Set the directories that the auto loader will run in
	 *
	 * @param $directory
	 *
	 * @return array
	 */
	public function add_integration_directory_func( $directory ) {

		$directory[] = dirname( __FILE__ ) . '/conditions';

		return $directory;
	}

	/**
	 * Register the integration by pushing it into the global automator object
	 */
	public function add_integration_func() {

		Automator()->register->integration(
			'UOA',
			array(
				'name'     => 'Tin canny Reporting',
				'icon_svg' => \Uncanny_Automator_Pro\Utilities::get_integration_icon( 'uncanny-owl-icon.svg' ),
			)
		);
	}
}
