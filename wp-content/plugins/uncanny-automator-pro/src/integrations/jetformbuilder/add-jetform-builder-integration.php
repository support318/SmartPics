<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

class Add_Jetform_Builder_Integration {

	use Recipe\Integrations;

	public function __construct() {

		$this->setup();

	}

	protected function setup() {

		$this->set_integration( 'JET_FORM_BUILDER' );

		$this->set_name( 'JetFormBuilder' );

		$this->set_icon( 'jetformbuilder-icon.svg' );

		$this->set_icon_path( __DIR__ . '/img/' );

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
	 * Method plugin_active
	 *
	 * @return bool
	 */
	public function plugin_active() {

		return function_exists( 'jet_form_builder_init' );

	}

}
