<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Advads_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Advads_Pro_Tokens {

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {

		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_advads_pro_tokens' ), 22, 6 );
		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 22, 2 );

		add_filter(
			'automator_advanced_ads_validate_trigger_meta_pieces_common',
			function ( $codes, $data ) {
				$codes[] = 'AD_STATUS_CHANGED_CODE';

				return $codes;
			},
			20,
			2
		);
	}

	/**
	 * save_token_data
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

		if ( 'AD_STATUS_CHANGED_CODE' === $args['entry_args']['code'] ) {
			list( $new_status, $old_status, $ad ) = $args['trigger_args'];
			$trigger_log_entry                    = $args['trigger_entry'];
			if ( ! empty( $ad ) ) {
				$ad = new \Advanced_Ads_Ad( $ad->ID );
				Automator()->db->token->save( 'save_ad', maybe_serialize( $ad ), $trigger_log_entry );
				Automator()->db->token->save( 'AD_OLD_STATUS', maybe_serialize( $old_status ), $trigger_log_entry );
			}
		}
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string
	 */
	public function parse_advads_pro_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		$trigger_meta_validations = apply_filters(
			'automator_advanced_ads_pro_validate_trigger_meta_pieces_common',
			array( 'AD_STATUS_CHANGED_CODE' ),
			array(
				'pieces'       => $pieces,
				'recipe_id'    => $recipe_id,
				'trigger_data' => $trigger_data,
				'user_id'      => $user_id,
				'replace_args' => $replace_args,
			)
		);

		if ( ! array_intersect( $trigger_meta_validations, $pieces ) ) {
			return $value;
		}

		$to_replace = $pieces[2];

		switch ( $to_replace ) {
			case 'AD_OLD_STATUS':
				$value = maybe_unserialize( Automator()->db->token->get( 'AD_OLD_STATUS', $replace_args ) );
				break;
		}

		return $value;
	}
}
