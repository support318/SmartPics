<?php

namespace Uncanny_Automator_Pro;

use Groundhogg\Contact;

/**
 * Class GROUNDHOGG_ANON_TOKENS
 *
 * @package Uncanny_Automator_Pro
 */
class GROUNDHOGG_ANON_TOKENS {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'GH';

	public function __construct() {

		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 22, 2 );

		add_filter(
			'automator_maybe_trigger_gh_anonghtagapplied_tokens',
			array(
				$this,
				'gh_anon_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_maybe_trigger_gh_anonghtagremoved_tokens',
			array(
				$this,
				'gh_anon_possible_tokens',
			),
			20,
			2
		);

		add_filter( 'automator_maybe_parse_token', array( $this, 'gh_parse_tokens' ), 20, 6 );
	}

	/**
	 * @param $args
	 * @param $trigger
	 *
	 * @return void
	 */
	public function save_token_data( $args, $trigger ) {
		if ( ! isset( $args['trigger_args'] ) || ! isset( $args['entry_args']['code'] ) ) {
			return;
		}

		if ( 'ANON_GH_CONTACT_NOTE' === $args['entry_args']['code'] ) {
			list( $contact_id_or_obj, $note, $contact_obj ) = $args['trigger_args'];
			if ( is_object( $contact_id_or_obj ) ) {
				$contact_id = $contact_id_or_obj->data['object_id'];
				$note       = $contact_id_or_obj->data['content'];
			} else {
				$contact_id = $contact_id_or_obj;
			}
			$trigger_log_entry = $args['trigger_entry'];
			if ( ! empty( $contact_id ) && ! empty( $note ) ) {
				Automator()->db->token->save( 'contact_id', $contact_id, $trigger_log_entry );
				Automator()->db->token->save( 'note', $note, $trigger_log_entry );
			}
		}
	}

	/**
	 * Only load this integration and its triggers and actions if the related plugin is active
	 *
	 * @param $status
	 * @param $plugin
	 *
	 * @return bool
	 */
	public function plugin_active( $status, $plugin ) {
		if ( self::$integration === $plugin ) {
			if ( defined( 'GROUNDHOGG_VERSION' ) ) {
				$status = true;
			} else {
				$status = false;
			}
		}

		return $status;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	function gh_anon_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}
		$trigger_meta = $args['meta'];
		$new_tokens   = array(
			array(
				'tokenId'         => 'CONTACT_EMAIL',
				'tokenName'       => __( 'Contact email', 'uncanny-automator' ),
				'tokenType'       => 'email',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'CONTACT_FIRST_NAME',
				'tokenName'       => __( 'Contact first name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'CONTACT_LAST_NAME',
				'tokenName'       => __( 'Contact last name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'CONTACT_ID',
				'tokenName'       => __( 'Contact ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);
		$tokens       = array_merge( $tokens, $new_tokens );

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
	 * @return string
	 */
	public function gh_parse_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		$piece_one = 'ANONGHTAGAPPLIED';
		$piece_two = 'ANONGHTAGREMOVED';
		global $wpdb;
		if ( $pieces ) {
			if ( in_array( $piece_one, $pieces ) || in_array( $piece_two, $pieces ) ) {
				if ( $trigger_data ) {
					foreach ( $trigger_data as $trigger ) {
						$trigger_id = $trigger['ID'];
						$meta_field = $pieces[2];
						$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}uap_trigger_log_meta WHERE meta_key = %s AND automator_trigger_id = %d ORDER BY ID DESC LIMIT 0,1", $meta_field, $trigger_id ) );
						if ( ! empty( $meta_value ) ) {
							$meta_value = maybe_unserialize( $meta_value );
						}
						if ( is_array( $meta_value ) ) {
							$value = join( ', ', $meta_value );
						} else {
							$value = $meta_value;
						}
					}
				}
			}
		}

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		$trigger_meta_validations = apply_filters(
			'automator_groundhogg_pro_validate_trigger_meta_pieces_common',
			array( 'ANON_GH_CONTACT_NOTE' ),
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
		$contact_id = Automator()->db->token->get( 'contact_id', $replace_args );
		$contact    = new Contact( $contact_id );
		$note       = Automator()->db->token->get( 'note', $replace_args );
		switch ( $to_replace ) {
			case 'GH_CONTACT_FNAME':
				$value = $contact->get_first_name();
				break;
			case 'GH_CONTACT_LNAME':
				$value = $contact->get_last_name();
				break;
			case 'GH_CONTACT_EMAIL':
				$value = $contact->get_email();
				break;
			case 'GH_CONTACT_ID':
				$value = $contact_id;
				break;
			case 'GH_CONTACT_NOTE':
				$value = $note;
				break;
		}

		return $value;
	}

}
