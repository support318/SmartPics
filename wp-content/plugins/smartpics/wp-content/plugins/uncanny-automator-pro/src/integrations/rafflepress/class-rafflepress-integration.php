<?php

namespace Uncanny_Automator_Pro\Integrations\RafflePress;

/**
 * Class RafflePress_Integration
 *
 * @package Uncanny_Automator_Pro
 */
class Rafflepress_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {
		$this->set_integration( 'RAFFLE_PRESS' );
		$this->set_name( 'RafflePress' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/rafflepress-icon.svg' );
	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {

		if ( ! class_exists( 'Uncanny_Automator\Integrations\RafflePress\Rafflepress_Helpers' ) ) {
			return;
		}
		$this->helpers = new Rafflepress_Pro_Helpers();

		// Load triggers.
		new RAFFLEPRESS_ANON_ENTERS_GIVEAWAY_WITH_ACTION( $this->helpers );
		// Register an ajax endpoint for the get posts field
		add_action(
			'wp_ajax_select_all_entry_actions_by_giveaway_id',
			array(
				$this->helpers,
				'select_all_entry_actions_by_giveaway_id',
			)
		);
	}

	/**
	 * Check if Plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active() {
		return defined( 'RAFFLEPRESS_BUILD' ) || defined( 'RAFFLEPRESS_PRO_BUILD' );
	}
}
