<?php

namespace Uncanny_Automator_Pro\Integrations\Fluent_Booking;

use Uncanny_Automator\Integration;
use Uncanny_Automator\Integrations\Fluent_Booking\Fluent_Booking_Helpers;

/**
 * Class Fluent_Booking_Integration
 *
 * @pacakge Uncanny_Automator_Pro
 */
class Fluent_Booking_Integration extends Integration {

	/**
	 * Must use function in new integration to setup all required values
	 *
	 * @return mixed
	 */
	protected function setup() {
		if ( ! class_exists( 'Uncanny_Automator\Integrations\Fluent_Booking\Fluent_Booking_Helpers' ) ) {
			return false;
		}
		$this->helpers = new Fluent_Booking_Helpers();
		$this->set_integration( 'FLUENT_BOOKING' );
		$this->set_name( 'FluentBooking' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/fluentbooking-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {
		// Load triggers.
		new FLUENT_BOOKING_ONE_TO_ONE_MEETING_SCHEDULED_WITH_HOST( $this->helpers );
		new FLUENT_BOOKING_GROUP_MEETING_SCHEDULED_WITH_HOST( $this->helpers );

	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return defined( 'FLUENT_BOOKING_VERSION' );
	}
}
