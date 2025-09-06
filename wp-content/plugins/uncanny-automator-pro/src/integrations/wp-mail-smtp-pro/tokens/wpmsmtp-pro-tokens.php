<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wpmsmtp_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Wpmsmtp_Pro_Tokens {
	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter(
			'automator_maybe_trigger_wpmailsmtppro_string_in_url_code_tokens',
			array(
				$this,
				'wp_mail_smtp_url_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_maybe_trigger_wpmailsmtppro_string_and_subject_text_code_tokens',
			array(
				$this,
				'wp_mail_smtp_url_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_wp_mail_smtp_validate_trigger_meta_pieces',
			function ( $codes, $data ) {
				$trigger_codes = array( 'STRING_IN_URL_CODE', 'STRING_AND_SUBJECT_TEXT_CODE' );
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);

		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wp_mail_smtp_tokens' ), 2123, 6 );
	}

	/**
	 * WP MAIl SMTP possible tokens.
	 *
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|mixed|\string[][]
	 */
	public function wp_mail_smtp_url_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$fields = array(
			array(
				'tokenId'         => 'EMAIL_URL_CLICKED',
				'tokenName'       => __( 'URL clicked', 'uncanny-automator' ),
				'tokenType'       => 'url',
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
	public function parse_wp_mail_smtp_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		$trigger_meta_validations = apply_filters(
			'automator_wp_mail_smtp_validate_trigger_meta_pieces',
			array( 'STRING_IN_URL_CODE' ),
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

		$to_replace       = $pieces[2];
		$tracking_details = Automator()->db->token->get( 'tracking_data', $replace_args );
		$tracking_details = maybe_unserialize( $tracking_details );
		$email_log_id     = $tracking_details['email_log_id'];
		$url_clicked      = $tracking_details['url'];

		switch ( $to_replace ) {
			case 'EMAIL_URL_CLICKED':
				$value = urldecode( $url_clicked );
				break;
		}

		return $value;
	}
}
