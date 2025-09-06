<?php

namespace Uncanny_Automator_Pro\Integrations\RafflePress;

use Uncanny_Automator\Integrations\RafflePress\Rafflepress_Helpers;

/**
 * Class Rafflepress_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Rafflepress_Pro_Helpers extends Rafflepress_Helpers {

	/**
	 * ajax callback to get actions by giveaway ID
	 *
	 * @return void
	 */
	public function select_all_entry_actions_by_giveaway_id() {
		Automator()->utilities->ajax_auth_check();
		$fields = array();

		if ( ! automator_filter_has_var( 'value', INPUT_POST ) ) {
			echo wp_json_encode( $fields );
			die();
		}

		$fields[] = array(
			'value' => '-1',
			'text'  => __( 'Any action', 'uncanny-automator-pro' ),
		);

		$giveaway_id = (int) automator_filter_input( 'value', INPUT_POST );

		if ( intval( '-1' ) !== $giveaway_id ) {
			global $wpdb;
			$giveaways = $wpdb->get_results( "SELECT settings FROM {$wpdb->prefix}rafflepress_giveaways WHERE deleted_at is null ORDER BY name ASC", ARRAY_A );
			foreach ( $giveaways as $giveaway ) {
				$settings      = json_decode( $giveaway['settings'] );
				$entry_options = $settings->entry_options;
				foreach ( $entry_options as $entry_option ) {
					$fields[] = array(
						'value' => $entry_option->id,
						'text'  => $entry_option->name,
					);
				}
			}
		}

		echo wp_json_encode( $fields );

		die();
	}

	/**
	 * Get common tokens for entry action
	 *
	 * @return array[]
	 */
	public function rafflepress_common_tokens_for_entry_action() {

		return array(
			// Action tokens
			array(
				'tokenId'   => 'ACTION_NAME',
				'tokenName' => __( 'Action', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);

	}

	/**
	 * Parse action token value
	 *
	 * @return array
	 */
	public function hydrate_contestant_entry_action_tokens( $entry_action_meta ) {
		// Generate array of empty default values.
		$defaults              = wp_list_pluck( $this->rafflepress_common_tokens_for_entry_action(), 'tokenId' );
		$tokens                = array_fill_keys( $defaults, '' );
		$tokens['ACTION_NAME'] = $entry_action_meta->name;

		return $tokens;
	}

}
