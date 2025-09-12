<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wpsp_Tokens
 *
 * @package Uncanny_Automator
 */
class Wpsp_Pro_Tokens extends \Uncanny_Automator\Wpsp_Tokens {

	/**
	 * __construct
	 */
	public function __construct() {
		add_filter(
			'automator_maybe_trigger_wpsimplepay_wpspsubscriptionrenewed_tokens',
			array(
				$this,
				'wpsp_possible_tokens',
			),
			30,
			2
		);
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wpsp_id_tokens' ), 9200, 6 );
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
	public function parse_wpsp_id_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( empty( $pieces ) ) {
			return $value;
		}

		$trigger_metas = array(
			'WPSPFORMS_ID',
			'WPSPFORMSUBSCRIPTION_ID',
		);

		if ( ! array_intersect( $trigger_metas, $pieces ) ) {
			return $value;
		}

		$meta_key = $pieces[2];
		// Form title
		if ( 'WPSPFORMS_ID' === $meta_key || 'WPSPFORMSUBSCRIPTION_ID' === $meta_key ) {
			return Automator()->db->token->get( $meta_key, $replace_args );
		}

		return $value;
	}
}
