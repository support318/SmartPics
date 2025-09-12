<?php

namespace Uncanny_Automator_Pro\Integrations\Everest_Forms;

use Uncanny_Automator\Integrations\Everest_Forms\Everest_Forms_Helpers;

/**
 * Class Everest_Forms_Integration
 * @package Uncanny_Automator
 */
class Everest_Forms_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Must use function in new integration to setup all required values
	 *
	 * @return mixed
	 */
	protected function setup() {
		if ( ! class_exists( 'Uncanny_Automator\Integrations\Everest_Forms\Everest_Forms_Helpers' ) ) {
			return false;
		}
		$this->helpers = new Everest_Forms_Helpers();
		$this->set_integration( 'EVEREST_FORMS' );
		$this->set_name( 'Everest Forms' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/everest-forms-icon.svg' );
	}

	/**
	 * @return void
	 */
	protected function load() {
		// Load triggers
		new USER_SUBMITS_FORM_WITH_SPECIFIC_FIELD_VALUE( $this->helpers );
		new ANON_FORM_SUBMITTED_WITH_SPECIFIC_FIELD_VALUE( $this->helpers );

		// Load ajax methods
		add_action( 'wp_ajax_get_all_evf_fields_by_form_id', array( $this->helpers, 'get_all_evf_fields_by_form_id' ) );
	}

	/**
	 * @return bool|mixed
	 */
	public function plugin_active() {
		return class_exists( 'EverestForms' );
	}
}
