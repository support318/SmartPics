<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Armember_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Armember_Pro_Tokens {

	protected $pro_codes;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'automator_maybe_trigger_armember_tokens', array( $this, 'armember_possible_tokens' ), 20, 2 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_armember_tokens' ), 20, 6 );
		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );

		$this->pro_codes = array( 'ARM_PLAN_EXPIRES', 'ARM_PLAN_CHANGED' );

		add_filter(
			'automator_armember_validate_common_tokens_trigger_code',
			function ( $codes, $data ) {
				return array_merge( $codes, $this->pro_codes );
			},
			20,
			2
		);

		add_filter(
			'automator_armember_validate_common_trigger_codes',
			function ( $codes, $data ) {
				$codes[] = 'ARM_PLAN_CHANGED';

				return $codes;
			},
			20,
			2
		);

		add_filter(
			'automator_armember_parse_common_trigger_code',
			function ( $codes, $data ) {
				return array_merge( $codes, $this->pro_codes );
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

		$trigger_meta_validations = apply_filters(
			'automator_pro_armember_validate_common_trigger_codes',
			array( 'ARM_PLAN_EXPIRES' ),
			$args
		);

		if ( in_array( $args['entry_args']['code'], $trigger_meta_validations ) ) {
			$trigger_log_entry = $args['trigger_entry'];
			if ( isset( $args['trigger_args'][0], $args['trigger_args'][1] ) ) {
				Automator()->db->token->save( 'save_user_id', $args['trigger_args'][0]['user_id'], $trigger_log_entry );
				Automator()->db->token->save( 'save_plan_id', $args['trigger_args'][0]['plan_id'], $trigger_log_entry );
			}
		}
	}


	/**
	 * Affiliate possible tokens.
	 *
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|mixed|\string[][]
	 */
	public function armember_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$trigger_meta_validations = apply_filters(
			'automator_armember_validate_common_tokens_trigger_code',
			array( 'ARM_PLAN_EXPIRES' ),
			$args
		);

		if ( in_array( $trigger_code, $trigger_meta_validations, true ) ) {

			$fields = array(
				array(
					'tokenId'         => 'ARM_PLAN_EXPIRY_DATE',
					'tokenName'       => __( 'Expiration date', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
			);

			$tokens = array_merge( $tokens, $fields );
		}

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
	public function parse_armember_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		$trigger_meta_validations = apply_filters(
			'automator_armember_pro_parse_common_trigger_code',
			array( 'ARM_PLAN_EXPIRES' ),
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
		$member_id  = Automator()->db->token->get( 'save_user_id', $replace_args );
		$plan_id    = Automator()->db->token->get( 'save_plan_id', $replace_args );

		switch ( $to_replace ) {
			case 'ARM_PLAN_EXPIRY_DATE':
				$get_plan_details = get_user_meta( $member_id, 'arm_user_plan_' . $plan_id, true );
				$value            = date( 'F j, Y', strtotime( $get_plan_details->arm_expire_plan ) );
				break;
		}

		return $value;
	}

}
