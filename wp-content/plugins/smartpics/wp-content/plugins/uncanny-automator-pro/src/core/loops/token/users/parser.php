<?php

namespace Uncanny_Automator_Pro\Loops\Token\Users;

use Uncanny_Automator_Pro\Loops\Token\Text_Parseable;
use WP_User;

/**
 * User token parser.
 *
 * @since 5.3
 *
 * @package Uncanny_Automator_Pro\Loops\Token
 */
final class Parser extends Text_Parseable {

	/**
	 * The regexp pattern.
	 *
	 * @var string $pattern
	 */
	protected $pattern = '/{{TOKEN_EXTENDED:LOOP_TOKEN:\d+:USERS:[^}]+}}/';

	/**
	 * Parses the token.
	 *
	 * @param int $entity_id
	 * @param string $extracted_token
	 *
	 * @return string
	 */
	public function parse( $entity_id, $extracted_token, $args ) {

		$user = $this->get_user( $entity_id );

		// Make sure $wp_user is a valid user entity before proceeding.
		if ( ! $user instanceof WP_User ) {
			return '';
		}

		return $this->get_user_property( $user, $extracted_token );
	}

	/**
	 * Retrieves the user from the run time cache or from the database.
	 *
	 * @param int $user_id
	 *
	 * @return WP_user|false
	 */
	private function get_user( $user_id ) {

		$tag   = $this->parser_filter_tag . '_' . $user_id;
		$group = $this->parser_filter_tag . '_group';

		$user_entity_cached = wp_cache_get( $tag, $group, true );

		if ( false !== $user_entity_cached && $user_entity_cached instanceof WP_User ) {
			return $user_entity_cached;
		}

		$user_entity = get_user_by( 'ID', $user_id );

		wp_cache_set( $tag, $user_entity, $group );

		return $user_entity;
	}

	/**
	 * Retrieves the actual token value from the token identifier.
	 *
	 * @param WP_user $user
	 * @param string $token_id
	 *
	 * @return string
	 */
	private function get_user_property( WP_user $user, $token_id ) {

		$token_id_lowered = strtolower( $token_id );

		$token_parser_callbacks = array(
			'user_id'                 => $user->ID,
			'user_username'           => $user->user_login,
			'user_email'              => $user->user_email,
			'user_display_name'       => $user->user_nicename,
			'user_first_name'         => $user->user_firstname,
			'user_last_name'          => $user->user_lastname,
			'user_role'               => implode( ',', $user->roles ),
			'user_reset_password_url' => Automator()->parse->reset_password_url_token( $user->ID ),
			'user_locale'             => function ( WP_user $user ) {
				return get_user_locale( $user->ID );
			},
		);

		$callback = isset( $token_parser_callbacks[ $token_id_lowered ] )
			? $token_parser_callbacks[ $token_id_lowered ] : null;

		if ( is_null( $callback ) ) {
			return '';
		}

		if ( is_callable( $callback ) ) {
			$retval = call_user_func_array(
				$callback,
				array( $user )
			);
			return is_scalar( $retval ) ? (string) $retval : '';
		}

		return $callback;
	}
}
