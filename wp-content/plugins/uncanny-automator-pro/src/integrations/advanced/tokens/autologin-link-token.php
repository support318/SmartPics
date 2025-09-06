<?php
namespace Uncanny_Automator_Pro\Integrations\Advanced;

use Uncanny_Automator\Tokens\Universal_Token;

class Autologin_Link_Token extends Universal_Token {

	/**
	 * @var array
	 */
	public $error = null;

	/**
	 * setup
	 *
	 * @return void
	 */
	public function setup() {
		$this->integration   = 'ADVANCED';
		$this->id            = 'AUTOLOGIN_LINK';
		$this->name          = esc_attr_x( 'Automatic login link', 'Token', 'uncanny-automator' );
		$this->requires_user = true;
		$this->type          = 'int';
		$this->cacheable     = true;

		add_action( 'login_init', array( $this, 'login_page_init' ) );
	}

	/**
	 * parse_integration_token
	 *
	 */
	public function parse_integration_token( $return, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		$user_is_admin_or_editor = user_can( $user_id, 'editor' ) || user_can( $user_id, 'administrator' );

		$disable_security_check = apply_filters( 'automator_pro_auto_login_link_disable_security_check', false, $user_id );

		if ( false === $disable_security_check && $user_is_admin_or_editor ) {
			return esc_attr__( 'For security reasons, automatic login links cannot be generated for Administrator or Editor users.', 'uncanny-automator-pro' );
		}

		$unix_day = 24 * 60 * 60;

		$days_expired_in = 7;

		$days_expired_in = apply_filters_deprecated(
			'AUTOLOGINLINK_expires_in',
			array(
				$days_expired_in,
				get_user_by( 'ID', $user_id ),
			),
			'4.3',
			'automator_pro_auto_login_link_expires_in'
		); //phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

		$days_expired_in = apply_filters( 'automator_pro_auto_login_link_expires_in', $days_expired_in, $user_id ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.NotLowercase

		$hash = $this->generate_magic_hash();

		update_user_meta( $user_id, $hash, time() + ( $unix_day * $days_expired_in ) );

		return add_query_arg( 'ua_login', $hash, wp_login_url() );
	}

	/**
	 * @param bool $length
	 * @param string $separator
	 *
	 * @return string
	 */
	private function generate_magic_hash( $length = false, $separator = '-' ) {

		if ( ! is_array( $length ) || is_array( $length ) && empty( $length ) ) {
			$length = array( 8, 4, 8, 8, 4, 8 );
		}

		$hash = '';

		foreach ( $length as $key => $string_length ) {

			if ( $key > 0 ) {
				$hash .= $separator;
			}

			$hash .= $this->s4generator( $string_length );
		}

		return $hash;
	}

	/**
	 * @param $length
	 *
	 * @return string
	 */
	private function s4generator( $length ) {

		$token         = '';
		$code_alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$max           = strlen( $code_alphabet );

		for ( $i = 0; $i < $length; $i ++ ) {
			$token .= $code_alphabet[ $this->crypto_rand_secure( 0, $max - 1 ) ];
		}

		return $token;
	}

	/**
	 * @param $min
	 * @param $max
	 *
	 * @return int
	 */
	private function crypto_rand_secure( $min, $max ) {

		$range = $max - $min;

		if ( $range < 1 ) {
			return $min;
		}

		$log    = ceil( log( $range, 2 ) );
		$bytes  = (int) ( $log / 8 ) + 1;
		$bits   = (int) $log + 1;
		$filter = (int) ( 1 << $bits ) - 1;

		do {
			$rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
			$rnd = $rnd & $filter;
		} while ( $rnd > $range );

		return $min + $rnd;
	}

	/**
	 * @return void|WP_Error
	 */
	public function login_page_init() {

		if ( ! automator_filter_has_var( 'ua_login' ) ) {
			return;
		}

		$hash = (string) automator_filter_input( 'ua_login' );

		global $wpdb;

		$results = $wpdb->get_row(
			$wpdb->prepare( "SELECT user_id, meta_value AS expiry FROM $wpdb->usermeta WHERE meta_key = %s", $hash )
		);


		if ( empty( $results ) ) {
			return $this->add_error( 'hash_not_found', esc_attr__( 'The auto login link is incorrect.', 'uncanny-automator-pro' ) );
		}

		if ( time() > absint( $results->expiry ) ) {
			$this->delete_hash( absint( $results->user_id ), $hash );	
			return $this->add_error( 'hash_expired', esc_attr__( 'The auto login link has expired.', 'uncanny-automator-pro' ) );
		}

		$user = get_user_by( 'ID', $results->user_id );

		if ( ! $user instanceof \WP_User ) {
			$this->delete_hash( absint( $results->user_id ), $hash );	
			return $this->add_error( 'user_not_found', esc_attr__( 'The user does not exist.', 'uncanny-automator-pro' ) );
		}

		$this->delete_hash( absint( $user->ID ), $hash );

		$this->log_in_and_redirect( $user );
	}
	
	/**
	 * log_in_and_redirect
	 *
	 */
	private function log_in_and_redirect( $user ) {

		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );

		do_action( 'uap_auto_login_link_success' );

		$url = admin_url( 'profile.php' );

		if ( automator_filter_has_var( 'redirect_to' ) ) {
			$url = automator_filter_input( 'redirect_to' );
		} 

		wp_safe_redirect( apply_filters( 'uap_auto_login_link_success_redirect', $url, $user ) );
		
		exit();
	}
	
	/**
	 * add_error
	 *
	 */
	private function add_error( $code, $message ) {

		$this->error = array(
			'code'    => $code,
			'message' => $message,
		);

		add_filter(
			'wp_login_errors',
			function ( $errors, $redirect_to ) {
				return new \WP_Error( $this->error['code'], $this->error['message'] );
			},
			20,
			2
		);

		return null;
	}
	
	/**
	 * delete_hash
	 *
	 */
	private function delete_hash( $user_id, $hash ) {
		delete_user_meta( $user_id, $hash );
	}
}
