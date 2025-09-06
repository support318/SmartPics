<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Add_Generator_Integration
 *
 * @package Uncanny_Automator
 */
class Add_Generator_Integration {

	use \Uncanny_Automator\Recipe\Integrations;

	/**
	 *
	 */
	public function __construct() {

		// A patch for magic triggers.
		add_action( 'automator_recipe_trigger_created', array( $this, 'generator_meta_add' ), 10, 3 );

		$this->setup();
	}

	/**
	 * Method setup.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->set_integration( 'AUTOMATOR_GENERATOR' );

		$this->set_name( 'Generator' );

		$this->set_icon( '/img/generator-icon.svg' );

		$this->set_icon_path( __DIR__ . '/img/' );
	}

	/**
	 * Method plugin_active
	 *
	 * @return bool
	 */
	public function plugin_active() {

		return true;
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
	 * @param $trigger_id
	 * @param $item_code
	 * @param \WP_REST_Request $request
	 */
	public function generator_meta_add( $trigger_id, $item_code, $request ) {

		if ( 'GENERATE_RAND_STR_META' === (string) $item_code ) {
			update_post_meta( $trigger_id, $item_code, $trigger_id );
		}
	}
}
