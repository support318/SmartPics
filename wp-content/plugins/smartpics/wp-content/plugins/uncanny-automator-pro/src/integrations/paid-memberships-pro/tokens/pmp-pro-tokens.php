<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Pmp_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Pmp_Pro_Tokens {

	/**
	 * __construct
	 */
	public function __construct() {
		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_pmp_pro_tokens' ), 20, 6 );
	}

	/**
	 * Saves the token data into `uap_trigger_log_meta`.
	 *
	 * @param mixed $args
	 * @param mixed $trigger
	 *
	 * @return void
	 */
	public function save_token_data( $args, $trigger ) {
		if ( ! isset( $args['trigger_args'] ) || ! isset( $args['entry_args']['code'] ) ) {
			return;
		}

		$trigger_code = $args['entry_args']['code'];

		if ( 'ASSIGN_LEVEL_CODE' === $trigger_code ) {

			// @see <https://www.paidmembershipspro.com/hook/pmpro_after_change_membership_level/>
			list( $level_id,  $user_id,  $cancel_level ) = $args['trigger_args'];

			$trigger_log_entry = $args['trigger_entry'];

			if ( ! empty( $level_id ) ) {
				Automator()->db->token->save( 'pmp_level_id', $level_id, $trigger_log_entry );
			}

			// Support for Trigger specific User ID.
			Automator()->db->token->save( 'pmp_user_id', $user_id, $trigger_log_entry );

		}

	}

	/**
	 * Parses the token values.
	 *
	 * @param mixed $value
	 * @param mixed $pieces
	 * @param mixed $recipe_id
	 * @param mixed $trigger_data
	 * @param mixed $user_id
	 * @param mixed $replace_args
	 *
	 * @return void
	 */
	public function parse_pmp_pro_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		$codes    = array( 'ASSIGN_LEVEL_CODE' );
		$to_match = $pieces[1];

		if ( ! in_array( $to_match, $codes, true ) ) {
			return $value;
		}

		$level_id = Automator()->db->token->get( 'pmp_level_id', $replace_args );
		$level    = pmpro_getLevel( $level_id );

		switch ( $pieces[2] ) {
			case 'ASSIGN_LEVEL_META':
				$value = $level->name;
				break;
			case 'ASSIGN_LEVEL_META_ID':
				$value = absint( $level_id );
				break;
			case 'ASSIGN_LEVEL_META_USER_ID':
				$value = absint( Automator()->db->token->get( 'pmp_user_id', $replace_args ) );
				break;
		}

		return $value;

	}
}
