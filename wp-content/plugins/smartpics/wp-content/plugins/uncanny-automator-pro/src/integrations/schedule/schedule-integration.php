<?php

namespace Uncanny_Automator_Pro\Integrations\Schedule;

use Uncanny_Automator\Integration;
use Uncanny_Automator_Pro\Integrations\Schedule\Helpers\Schedule_Helpers;

/**
 *
 */
class Schedule_Integration extends Integration {
	/**
	 * @return void
	 */
	protected function setup() {
		$this->helpers = new Schedule_Helpers();
		$this->set_integration( 'SCHEDULE' );
		$this->set_name( 'Schedule' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/schedule-icon.svg' );
		$this->register_hooks();
		$this->admin_only();
	}

	/**
	 * @return void
	 */
	public function load() {
		// Instantiate the trigger
		new Trigger_Specific_Date( $this->helpers );
		new Recurring_Trigger( $this->helpers );
		new Recurring_Weekday( $this->helpers );
	}

	/**
	 * @return void
	 */
	public function register_hooks() {
		// Maybe schedule the trigger
		add_action(
			'automator_recipe_status_updated',
			array(
				$this->helpers,
				'schedule_recipe',
			),
			10,
			4
		);

		// Maybe update the schedule
		add_action(
			'automator_recipe_option_updated',
			array(
				$this->helpers,
				'trigger_recipe_option_updated',
			),
			10,
			6
		);

		// Store the schedule ID
		add_action(
			'action_scheduler_stored_action',
			array( $this->helpers, 'store_schedule_id' ),
			10,
			1
		);

		// Delete the schedule ID
		add_action(
			'action_scheduler_completed_action',
			array( $this->helpers, 'delete_schedule_id' ),
			10,
			1
		);

		// Delete the schedule ID
		add_action(
			'action_scheduler_canceled_action',
			array( $this->helpers, 'delete_schedule_id' ),
			10,
			1
		);

		// Unschedule triggers when bulk editing
		add_action( 'save_post', array( $this->helpers, 'unschedule_on_bulk_edit' ), 10, 2 );
	}

	/**
	 * @return void
	 */
	private function admin_only() {
		if ( ! current_user_can( 'manage_options' ) && ! is_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		include_once __DIR__ . '/admin/admin-helper.php';
		new Admin_Helper();
	}
}
