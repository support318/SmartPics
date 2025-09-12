<?php
/**
 * Magic button related tokens class.
 */

namespace Uncanny_Automator_Pro;

/**
 * Class MAGIC_BUTTON_Magic_Button_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Magic_Button_Tokens {

	public function __construct() {

		add_filter(
			'automator_maybe_trigger_magic_button_wpmagicbutton_tokens',
			array(
				$this,
				'magic_button_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_maybe_trigger_magic_button_wpmagiclink_tokens',
			array(
				$this,
				'magic_button_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_maybe_trigger_magic_button_anonwpmagicbutton_tokens',
			array(
				$this,
				'magic_button_anon_possible_tokens',
			),
			20,
			2
		);
		add_filter(
			'automator_maybe_trigger_magic_button_anonwpmagiclink_tokens',
			array(
				$this,
				'magic_button_anon_possible_tokens',
			),
			20,
			2
		);

		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_magicbutton_token' ), 20, 6 );
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function magic_button_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}
		$trigger_meta = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'automator_button_post_id',
				'tokenName'       => __( 'Post ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'automator_button_post_title',
				'tokenName'       => __( 'Post title', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'automator_button_id',
				'tokenName'       => __( 'Button ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'automator_button_recipe_id',
				'tokenName'       => __( 'Recipe ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		/**
		 * Filter automator_pro_magic_{$type}_tokens - for adding custom tokens
		 *
		 * @param array $fields
		 * @param string $trigger_meta
		 * @param array $args
		 *
		 * @return array
		 */
		$type   = 'WPMAGICBUTTON' === $trigger_meta ? 'button' : 'link';
		$fields = apply_filters( "automator_pro_magic_{$type}_tokens", $fields, $trigger_meta, $args );

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
	 * @return mixed
	 */
	public function parse_magicbutton_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		$matching_pieces = array(
			'WPMAGICBUTTON',
			'WPMAGICLINK',
			'ANONWPMAGICBUTTON',
			'ANONWPMAGICLINK',
		);
		if ( ! $pieces ) {
			return $value;
		}
		if ( array_intersect( $pieces, $matching_pieces ) ) {

			if ( $trigger_data ) {
				foreach ( $trigger_data as $trigger ) {
					$trigger_id = $trigger['ID'];

					$meta_field = $pieces[2];

					$meta_value = $this->get_form_data_from_trigger_meta( $meta_field, $trigger_id );

					if ( is_array( $meta_value ) ) {
						$value = join( ', ', $meta_value );
					} else {
						$value = $meta_value;
					}
				}
			}
		}

		return $value;
	}

	/**
	 * @param $meta_key
	 * @param $trigger_id
	 *
	 * @return mixed|string
	 */
	public function get_form_data_from_trigger_meta( $meta_key, $trigger_id ) {
		global $wpdb;
		if ( empty( $meta_key ) || empty( $trigger_id ) ) {
			return '';
		}

		$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}uap_trigger_log_meta WHERE meta_key = %s AND automator_trigger_id = %d ORDER BY ID DESC LIMIT 0,1", $meta_key, $trigger_id ) );

		if ( ! empty( $meta_value ) ) {
			return maybe_unserialize( $meta_value );
		}

		return '';
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function magic_button_anon_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_meta = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'user_id',
				'tokenName'       => __( 'User ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'username',
				'tokenName'       => __( 'Username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'email',
				'tokenName'       => __( 'User email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'automator_button_post_id',
				'tokenName'       => __( 'Post ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'automator_button_post_title',
				'tokenName'       => __( 'Post title', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'automator_button_post_url',
				'tokenName'       => __( 'Post URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		/**
		 * Filter automator_pro_magic_{$type}_tokens - for adding custom tokens
		 *
		 * @param array $fields
		 * @param string $trigger_meta
		 * @param array $args
		 *
		 * @return array
		 */
		$type   = 'ANONWPMAGICBUTTON' === $trigger_meta ? 'button' : 'link';
		$fields = apply_filters( "automator_pro_magic_{$type}_tokens", $fields, $trigger_meta, $args );

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}
}
