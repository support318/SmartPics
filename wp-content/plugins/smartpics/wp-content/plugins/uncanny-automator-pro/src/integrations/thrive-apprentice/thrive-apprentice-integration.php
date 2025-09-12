<?php
namespace Uncanny_Automator_Pro\Integrations\Thrive_Apprentice;

/**
 * Class Thrive_Apprentice_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Thrive_Apprentice_Integration extends \Uncanny_Automator\Integration {


	/**
	 * Sets up Thrive Apprentice integration.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->helpers = new \Uncanny_Automator\Integrations\Thrive_Apprentice\Thrive_Apprentice_Helpers();

		$this->set_integration( 'THRIVE_APPRENTICE' );
		$this->set_name( 'Thrive Apprentice' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/thrive-apprentice-icon.svg' );
	}

	/**
	 * Bootstrap actions, triggers, settings page, etc.
	 *
	 * @return void
	 */
	public function load() {

		//triggers
		new THRIVE_APPRENTICE_USER_COURSE_ACCESS_TRIED( $this->helpers );
		new THRIVE_APPRENTICE_USER_COURSE_LESSON_STARTED( $this->helpers );
		new THRIVE_APPRENTICE_USER_COURSE_MODULE_STARTED( $this->helpers );
		new THRIVE_APPRENTICE_USER_COURSE_PROGRESSED( $this->helpers );
		new THRIVE_APPRENTICE_USER_COURSE_STARTED( $this->helpers );
		new THRIVE_APPRENTICE_USER_PURCHASED( $this->helpers );

		//actions
		new THRIVE_APPRENTICE_PRODUCT_USER_ACCESS_GRANT( $this->helpers );
		new THRIVE_APPRENTICE_PRODUCT_USER_ACCESS_REMOVE( $this->helpers );

		//loop filters
		new \Uncanny_Automator_Pro\Loop_Filters\USER_HAS_ACCESS_TO_PRODUCT();

		//conditions
		new \Uncanny_Automator_Pro\THRIVE_APPRENTICE_USER_HAS_ACCESS_TO_PRODUCT();
	}

	/**
	 * Determines whether the integration should be loaded or not.
	 *
	 * Checks whether an existing dependency condition is satisfied.
	 *
	 * @return bool Returns true if Thrive Apprentice is active. Returns false, otherwise.
	 */
	public function plugin_active() {

		// Check if Automator is at least 6.7.0.
		$is_dependency_ready = defined( 'AUTOMATOR_PLUGIN_VERSION' )
			&& version_compare( AUTOMATOR_PLUGIN_VERSION, '6.7.0', '>=' );

		// Check for Thrive Apprentice classes and functions
		$thrive_active = class_exists( '\TVA_Const', false );

		return $is_dependency_ready && $thrive_active;
	}
}
