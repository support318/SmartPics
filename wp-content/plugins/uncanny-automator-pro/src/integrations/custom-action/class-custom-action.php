<?php
namespace Uncanny_Automator_Pro\Integration;

use Uncanny_Automator\Integration;
use Uncanny_Automator_Pro\Integration\Custom_Action\Actions\Run_Do_Action;

/**
 *
 */
class Custom_Action_Integration extends Integration {

	/**
	 * Setups the Integration.
	 *
	 * @return void
	 */
	protected function setup() {

		// The unique integration code.
		$this->set_integration( 'DO_ACTION' );
		// The integration name. You can translate if you want to.
		$this->set_name( 'Custom Action' );
		// The icon URL. Absolute URL path to the image file.
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . '/img/custom-action-icon.svg' );

	}

	/**
	 * Load some hooks required.
	 *
	 * @return void
	 */
	public function load() {

		new Run_Do_Action( array() );

	}

}
