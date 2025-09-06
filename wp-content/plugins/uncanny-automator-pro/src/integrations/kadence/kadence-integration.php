<?php

namespace Uncanny_Automator_Pro\Integrations\Kadence;

use Uncanny_Automator\Integration;
use Uncanny_Automator\Integrations\Kadence\Kadence_Helpers;

/**
 * Class Kadence_Integration
 *
 * @pacakge Uncanny_Automator
 */
class Kadence_Integration extends Integration {

	/**
	 * Must use function in new integration to setup all required values
	 *
	 * @return mixed
	 */
	protected function setup() {
		$this->set_integration( 'KADENCE' );
		$this->set_name( 'Kadence' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/kadence-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {
		// Load triggers.
		new KADENCE_USER_SUBMITTED_FORM_WITH_SPECIFIC_VALUE( $this->helpers );

		add_action( 'wp_ajax_get_all_form_fields', array( $this->helpers, 'get_all_form_fields' ) );
	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		if ( ! class_exists( 'Uncanny_Automator\Integrations\Kadence\Kadence_Helpers' ) ) {
			return false;
		}
		$this->helpers = new Kadence_Helpers();
		// get the current theme
		$theme = wp_get_theme();
		if ( ( 'Kadence' == $theme->name || 'Kadence' == $theme->parent_theme ) || defined( 'KADENCE_BLOCKS_VERSION' ) ) {
			return true;
		}

		return false;
	}
}
