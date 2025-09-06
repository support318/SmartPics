<?php

use Uncanny_Automator_Pro\Loops\Loop\Background_Process\Lib\Auth;

/**
 * Returns incoming webhooks route's prefix.
 *
 * @return string
 */
function automator_pro_get_webhook_route_prefix() {
	return apply_filters( 'automator_pro_get_webhook_route_prefix', 'uap' );
}

/**
 * Only identify and add tokens IF it's edit recipe page
 * @return bool
 */
function automator_pro_do_identify_tokens() {
	if (
		isset( $_REQUEST['action'] ) && //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		(
			'heartbeat' === (string) sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) || //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'wp-remove-post-lock' === (string) sanitize_text_field( wp_unslash( $_REQUEST['action'] ) )  //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		)
	) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// if it's heartbeat, post lock actions bail
		return false;
	}

	if ( ! Automator()->helpers->recipe->is_edit_page() && ! Automator()->helpers->recipe->is_rest() ) {
		// If not automator edit page or rest call, bail
		return false;
	}

	return true;
}

/**
 * Retrieves the filter registry class.
 *
 * Filter registry class is used to load all filters for UI consumption.
 *
 * @return \Uncanny_Automator_Pro\Loops\Filter\Registry
 */
function automator_pro_loop_filters() {

	return \Uncanny_Automator_Pro\Loops\Filter\Registry::get_instance();

}

/**
 * automator_free_older_than
 *
 * Returns true if Automator Free is older than the $version
 *
 * @param mixed $version
 *
 * @return bool|int
 */
function automator_free_older_than( $version ) {

	if ( defined( 'AUTOMATOR_PLUGIN_VERSION' ) ) {
		return version_compare( AUTOMATOR_PLUGIN_VERSION, $version, '<' );
	}

	return false;
}

/**
 * Basic singleton function that returns the Auth object for loops.
 *
 * @return Auth
 */
function automator_pro_loop_auth_token() {

	static $instance = null;

	if ( $instance === null ) {
		$secret   = apply_filters( 'automator_pro_loop_process_secret', '' );
		$instance = new Auth( $secret );
	}

	return $instance;
}

/**
 * Wrapper for add_option and add_automator_option
 *
 * @param $option
 * @param $value
 * @param $autoload
 *
 * @return void
 */
function automator_pro_add_option( $option, $value, $autoload = true ) {
	if ( function_exists( 'automator_add_option' ) ) {
		automator_add_option( $option, $value, $autoload );

		return;
	}

	add_option( $option, $value, $autoload );
}

/**
 * @param $option
 *
 * @return bool
 */
function automator_pro_delete_option( $option ) {
	if ( function_exists( 'automator_delete_option' ) ) {
		return automator_delete_option( $option );
	}

	return delete_option( $option );
}

/**
 * @param $option
 * @param $default_value
 * @param $force
 *
 * @return false|mixed|null
 */
function automator_pro_get_option( $option, $default_value = false, $force = false ) {
	if ( function_exists( 'automator_get_option' ) ) {
		return automator_get_option( $option, $default_value, $force );
	}

	return get_option( $option, $default_value );
}

/**
 * @param $option
 * @param $value
 * @param $autoload
 *
 * @return bool
 */
function automator_pro_update_option( $option, $value, $autoload = true ) {
	if ( function_exists( 'automator_update_option' ) ) {
		return automator_update_option( $option, $value, $autoload );
	}

	return update_option( $option, $value, $autoload );
}
