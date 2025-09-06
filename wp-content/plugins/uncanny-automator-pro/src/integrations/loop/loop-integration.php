<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Integrations\Loop;

/**
 * Loop_Integration
 *
 * @package Uncanny_Automator_Pro\Integrations\Loop
 */
class Loop_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setups the Integration.
	 *
	 * @return void
	 */
	protected function setup() {
		$this->set_integration( 'LOOP' );
		$this->set_name( 'Loop' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/loop-icon.svg' );
	}

	/**
	 * Loads actions and settings.
	 *
	 * @return void
	 */
	public function load() {
		new End_Loop();
	}
}
