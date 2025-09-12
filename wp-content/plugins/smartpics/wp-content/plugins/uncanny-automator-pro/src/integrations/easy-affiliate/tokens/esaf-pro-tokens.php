<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Esaf_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Esaf_Pro_Tokens {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		//      add_filter(
		//          'automator_maybe_trigger_esaf_payout_made_code_tokens',
		//          array(
		//              $this,
		//              'esaf_commission_possible_tokens',
		//          ),
		//          20,
		//          2
		//      );
		add_filter(
			'automator_maybe_trigger_esaf_payout_made_meta_tokens',
			array(
				$this,
				'esaf_payout_possible_tokens',
			),
			20,
			2
		);

		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_esaf_tokens' ), 21, 6 );

		add_filter(
			'automator_easy_affiliate_validate_trigger_meta_pieces',
			function ( $codes, $data ) {
				$codes[] = 'PAYOUT_MADE_CODE';

				return $codes;
			},
			20,
			2
		);
	}

	/**
	 * Affiliate commission possible tokens.
	 *
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|mixed|\string[][]
	 */
	public function esaf_payout_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$fields = array(
			array(
				'tokenId'         => 'PAYOUT_AMOUNT',
				'tokenName'       => __( 'Payout amount', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'PAYOUT_METHOD',
				'tokenName'       => __( 'Payout method', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'PAYOUT_DATE',
				'tokenName'       => __( 'Payout date', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * parse_tokens
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
	public function parse_esaf_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		$tokens     = array( 'PAYOUT_AMOUNT', 'PAYOUT_METHOD', 'PAYOUT_DATE' );
		$to_replace = $pieces[2];
		if ( ! in_array( $to_replace, $tokens, true ) ) {
			return $value;
		}

		$event_data = Automator()->db->token->get( 'event_data', $replace_args );
		$data       = maybe_unserialize( $event_data );

		switch ( $to_replace ) {
			case 'PAYOUT_AMOUNT':
				$value = $data->amount;
				break;
			case 'PAYOUT_METHOD':
				$value = $data->payout_method;
				break;
			case 'PAYOUT_DATE':
				$value = $data->created_at;
				break;
		}

		return $value;
	}

}
