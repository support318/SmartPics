<?php

namespace Uncanny_Automator_Pro\Integrations\RafflePress;

/**
 * Class RAFFLEPRESS_ANON_REGISTERS_GIVEAWAY
 *
 * @package Uncanny_Automator_Pro
 */
class RAFFLEPRESS_ANON_ENTERS_GIVEAWAY_WITH_ACTION extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'RAFFLE_PRESS' );
		$this->set_trigger_code( 'ANON_ENTERS_GIVEAWAY_WITH_ACTION' );
		$this->set_trigger_meta( 'RP_GIVEAWAYS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		// Trigger sentence - RafflePress
		$this->set_sentence( sprintf( esc_attr_x( 'Someone enters {{a giveaway:%1$s}} with {{a specific action:%2$s}}', 'RafflePress', 'uncanny-automator-pro' ), $this->get_trigger_meta(), 'RP_ENTRY_ACTION:' . $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Someone enters {{a giveaway}} with {{a specific action}}', 'RafflePress', 'uncanny-automator-pro' ) );
		$this->add_action( 'rafflepress_post_entry_add', 90, 4 );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return array(
			'options_group' => array(
				$this->get_trigger_meta() => array(
					array(
						'option_code'     => $this->get_trigger_meta(),
						'label'           => esc_attr_x( 'Giveaway', 'RafflePress', 'uncanny-automator-pro' ),
						// Load the options from the helpers file
						'options'         => $this->helpers->get_all_rafflepress_giveaway(),
						'input_type'      => 'select',
						'required'        => true,
						'relevant_tokens' => array(),
						'is_ajax'         => true,
						'endpoint'        => 'select_all_entry_actions_by_giveaway_id',
						'fill_values_in'  => 'RP_ENTRY_ACTION',
					),
					array(
						'option_code'     => 'RP_ENTRY_ACTION',
						'label'           => esc_attr_x( 'Action', 'RafflePress', 'uncanny-automator-pro' ),
						'options'         => array(),
						'input_type'      => 'select',
						'relevant_tokens' => array(),
					),
				),
			),
		);
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ], $trigger['meta']['RP_ENTRY_ACTION'] ) ) {
			return false;
		}

		if ( ! isset( $hook_args[0]['giveaway_id'], $hook_args[0]['action_id'] ) ) {
			return false;
		}

		$selected_giveaway_id = intval( $trigger['meta'][ $this->get_trigger_meta() ] );
		$selected_action_id   = intval( $trigger['meta']['RP_ENTRY_ACTION'] );

		// Any giveaway or specific giveaway and any action or specific action
		if ( ( intval( '-1' ) === $selected_giveaway_id || intval( $hook_args[0]['giveaway_id'] ) === $selected_giveaway_id ) &&
			 ( intval( '-1' ) === $selected_action_id || intval( $hook_args[0]['action_id'] ) === $selected_action_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Define Tokens.
	 *
	 * @param array $tokens
	 * @param array $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$giveaway_tokens   = $this->helpers->rafflepress_common_tokens_for_giveaway();
		$contestant_tokens = $this->helpers->rafflepress_common_tokens_for_contestant();
		$action_tokens     = $this->helpers->rafflepress_common_tokens_for_entry_action();

		return array_merge( $tokens, $giveaway_tokens, $contestant_tokens, $action_tokens );
	}

	/**
	 * Hydrate Tokens.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		$giveaway_tokens   = array();
		$contestant_tokens = array();
		$action_tokens     = array();
		// Hydrate giveaways tokens.
		if ( ! empty( $hook_args[0]['giveaway_id'] ) ) {
			$giveaway_tokens = $this->helpers->hydrate_giveaway_tokens( $hook_args[0]['giveaway_id'] );
		}
		// Hydrate contestant tokens.
		if ( ! empty( $hook_args[0]['contestant_id'] ) ) {
			$contestant_tokens = $this->helpers->hydrate_contestant_tokens( $hook_args[0]['contestant_id'] );
		}
		// Hydrate action tokens.
		if ( ! empty( $hook_args[0]['entry_option_meta'] ) ) {
			$action_tokens = $this->helpers->hydrate_contestant_entry_action_tokens( $hook_args[0]['entry_option_meta'] );
		}

		return array_merge( $giveaway_tokens, $contestant_tokens, $action_tokens );
	}

}
