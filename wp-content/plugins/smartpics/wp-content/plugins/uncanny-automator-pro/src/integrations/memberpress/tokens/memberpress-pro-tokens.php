<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Memberpress_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Memberpress_Pro_Tokens {

	/**
	 * Memberpress_Pro_Tokens constructor.
	 */
	public function __construct() {

		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_memberpress_tokens' ), 22, 6 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_memberpress_ca_tokens' ), 20, 6 );
		// token store
		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );

		add_filter(
			'automator_maybe_trigger_mp_txn_status_code_tokens',
			array(
				$this,
				'mp_txn_possible_tokens',
			),
			20,
			2
		);
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]
	 */
	public function mp_txn_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$fields = array(
			array(
				'tokenId'         => 'TXN_OLD_STATUS',
				'tokenName'       => __( 'Previous status', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'TXN_ID',
				'tokenName'       => __( 'Transaction ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
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
	 * @deprecated Use Free instead
	 */
	public function parse_memberpress_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( ! $pieces ) {
			return $value;
		}
		$matches = array(
			'MPPRODUCT',
			'MPPRODUCT_ID',
			'MPPRODUCT_URL',
			'TXN_STATUS_CODE',
			'MP_COUPON',
			'MP_COUPON_TYPE',
			'MP_COUPON_AMOUNT',
		);

		$mepr_options = \MeprOptions::fetch();
		if ( $mepr_options->show_fname_lname ) {
			$matches[] = 'first_name';
			$matches[] = 'last_name';
		}

		if ( $mepr_options->show_address_fields && ! empty( $mepr_options->address_fields ) ) {
			foreach ( $mepr_options->address_fields as $address_field ) {
				$matches[] = $address_field->field_key;
			}
		}

		$custom_fields = $mepr_options->custom_fields;
		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $_field ) {
				$matches[] = $_field->field_key;
			}
		}

		if ( ! array_intersect( $matches, $pieces ) ) {
			return $value;
		}

		if ( empty( $trigger_data ) ) {
			return $value;
		}

		if ( ! isset( $pieces[2] ) ) {
			return $value;
		}
		foreach ( $trigger_data as $trigger ) {
			// all memberpress values will be saved in usermeta.
			$trigger_id     = absint( $trigger['ID'] );
			$trigger_log_id = absint( $replace_args['trigger_log_id'] );
			$parse_tokens   = array(
				'trigger_id'     => $trigger_id,
				'trigger_log_id' => $trigger_log_id,
				'user_id'        => $user_id,
			);

			$meta_key   = 'MPPRODUCT';
			$product_id = Automator()->db->trigger->get_token_meta( $meta_key, $parse_tokens );
			if ( empty( $product_id ) ) {
				continue;
			}
			switch ( $pieces[2] ) {
				case 'MPPRODUCT':
					$value = get_the_title( $product_id );
					break;
				case 'MPPRODUCT_ID':
					$value = absint( $product_id );
					break;
				case 'MPPRODUCT_URL':
					$value = get_the_permalink( $product_id );
					break;
				case 'TXN_STATUS_META':
					$value = Automator()->db->token->get( 'TXN_STATUS_META', $replace_args );
					break;
				case 'TXN_OLD_STATUS':
					$value = Automator()->db->token->get( 'TXN_OLD_STATUS', $replace_args );
					break;
				case 'TXN_ID':
					$value = Automator()->db->token->get( 'TXN_ID', $replace_args );
					break;
				case 'MP_COUPON':
					$value = Automator()->db->token->get( 'MP_COUPON', $replace_args );
					break;
				case 'MP_COUPON_TYPE':
					$value = Automator()->db->token->get( 'MP_COUPON_TYPE', $replace_args );
					break;
				case 'MP_COUPON_AMOUNT':
					$value = Automator()->db->token->get( 'MP_COUPON_AMOUNT', $replace_args );
					break;
				default:
					$user_id = Automator()->db->token->get( 'user_id', $replace_args );
					$value   = get_user_meta( $user_id, $pieces[2], true );
					break;
			}
		}

		return $value;
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
	 * @deprecated Use Free instead
	 */
	public function parse_memberpress_ca_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( empty( $pieces ) ) {
			return $value;
		}

		if ( in_array( 'MPCAPARENTACC', $pieces ) || in_array( 'MPCAADDSUBACC', $pieces ) || in_array( 'MPCAREMOVESUBACC', $pieces ) ) {
			if ( $trigger_data ) {
				foreach ( $trigger_data as $trigger ) {
					$trigger_id     = $trigger['ID'];
					$trigger_log_id = $replace_args['trigger_log_id'];
					$meta_key       = $pieces[2];
					$meta_value     = Automator()->helpers->recipe->get_form_data_from_trigger_meta( $meta_key, $trigger_id, $trigger_log_id, $user_id );
					if ( ! empty( $meta_value ) ) {
						$value = maybe_unserialize( $meta_value );
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param      $meta_key
	 * @param      $trigger_id
	 * @param      $trigger_log_id
	 * @param null $user_id
	 *
	 * @return mixed|string
	 */
	public function get_trigger_log_meta_value( $meta_key, $trigger_id, $trigger_log_id, $user_id = null ) {
		if ( empty( $meta_key ) || empty( $trigger_id ) || empty( $trigger_log_id ) ) {
			return '';
		}
		global $wpdb;
		$qry        = $wpdb->prepare(
			"SELECT meta_value
														FROM {$wpdb->prefix}uap_trigger_log_meta
														WHERE 1 = 1
														AND user_id = %d
														AND meta_key = %s
														AND automator_trigger_id = %d
														AND automator_trigger_log_id = %d
														LIMIT 0,1",
			$user_id,
			$meta_key,
			$trigger_id,
			$trigger_log_id
		);
		$meta_value = $wpdb->get_var( $qry );
		if ( ! empty( $meta_value ) ) {
			return maybe_unserialize( $meta_value );
		}

		return '';
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

		$trigger_meta = $args['entry_args']['meta'];
		$trigger_code = $args['entry_args']['code'];

		if ( $trigger_code === 'TXN_STATUS_CODE' ) {
			list( $old_status, $status, $txn ) = $args['trigger_args'];
			$trigger_log_entry                 = $args['trigger_entry'];
			if ( ! empty( $txn ) && is_object( $txn ) ) {
				Automator()->db->token->save( 'MPPRODUCT', $txn->product_id, $trigger_log_entry );
				Automator()->db->token->save( $trigger_meta, $status, $trigger_log_entry );
				Automator()->db->token->save( 'TXN_OLD_STATUS', $old_status, $trigger_log_entry );
				Automator()->db->token->save( 'TXN_ID', $txn->trans_num, $trigger_log_entry );
				Automator()->db->token->save( 'user_id', $txn->user_id, $trigger_log_entry );
			}
		}

		if ( 'MP_PAYMENT_FAILS' === $trigger_code ) {
			list( $event )     = $args['trigger_args'];
			$trigger_log_entry = $args['trigger_entry'];
			if ( ! empty( $event ) && $event instanceof \MeprEvent ) {
				$transaction = $event->get_data();
				$user        = $transaction->user();
				Automator()->db->token->save( 'MPPRODUCT', $transaction->rec->product_id, $trigger_log_entry );
				Automator()->db->token->save( 'user_id', $user->rec->ID, $trigger_log_entry );
			}
		}

		if ( 'MP_COUPON_REDEEMED' === $trigger_code ) {
			list( $event )     = $args['trigger_args'];
			$trigger_log_entry = $args['trigger_entry'];
			if ( ! empty( $event ) && ( $event instanceof \MeprEvent || $event instanceof \MeprTransaction ) ) {
				$transaction = $event instanceof \MeprTransaction ? $event : $event->get_data();
				$coupon      = $transaction->coupon();
				Automator()->db->token->save( 'MPPRODUCT', $transaction->rec->product_id, $trigger_log_entry );
				Automator()->db->token->save( 'MP_COUPON', $coupon->rec->post_title, $trigger_log_entry );
				Automator()->db->token->save( 'MP_COUPON_TYPE', $coupon->rec->discount_type, $trigger_log_entry );
				Automator()->db->token->save( 'MP_COUPON_AMOUNT', $coupon->rec->discount_amount, $trigger_log_entry );
			}
		}

	}
}
