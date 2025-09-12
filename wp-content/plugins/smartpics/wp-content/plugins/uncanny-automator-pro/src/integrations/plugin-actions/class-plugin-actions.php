<?php
namespace Uncanny_Automator_Pro\Integration;

use Uncanny_Automator\Integration;
use Uncanny_Automator_Pro\Integrations\Plugin_Actions\Triggers\Add_Action_Trigger;

/**
 *
 */
class Plugin_Actions_Integration extends Integration {

	/**
	 * Setups the Integration.
	 *
	 * @return void
	 */
	protected function setup() {

		// The unique integration code.
		$this->set_integration( 'ADD_ACTION' );
		// The integration name. You can translate if you want to.
		$this->set_name( 'Plugin Actions' );
		// The icon URL. Absolute URL path to the image file.
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . '/img/plugin-actions.svg' );

	}

	/**
	 * Load some hooks required.
	 *
	 * @return void
	 */
	public function load() {

		new Add_Action_Trigger( array() );

	}

}
