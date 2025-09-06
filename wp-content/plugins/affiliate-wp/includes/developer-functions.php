<?php
/**
 * Functions meant for developers.
 *
 * @package     AffiliateWP
 * @subpackage  Functions/Developers
 * @copyright   Copyright (c) 2024, Awesome Motive, inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.20.0
 * @author      Aubrey Portwood <aportwood@am.co>
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- There can be spaces before a comment.

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trigger Error.
 *
 * Note, if `WP_DEBUG` is `false`, this error will not be triggered!
 *
 * @param string $function_name The name of the function or fully-qualified method.
 * @param string $message       The message you want to show.
 * @param int    $error_level   The error level (defaults to E_USER_NOTICE).
 */
function affiliatewp_trigger_error(
	string $function_name,
	string $message,
	int $error_level = E_USER_NOTICE
) {

	if ( ! WP_DEBUG ) {
		return;
	}

	if ( ! function_exists( 'wp_trigger_error' ) ) {

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error, WordPress.Security.EscapeOutput.OutputNotEscaped -- Fallback to trigger_error when the official WP function is not available.
		trigger_error( $message, $error_level );
		return;
	}

	wp_trigger_error( $function_name, $message, $error_level );
}

/**
 * Deprecate a function.
 *
 * This triggers an `E_USER_DEPRECATED` when `WP_DEBUG` is `true`.
 *
 * Message ends up being something like:
 *
 * "function_name(): (Since AffiliateWP Version 2.19.1) Use another function instead."
 *
 * Usage:
 *
 *     affiliatewp_deprecate_function( 'my_function', __( 'Use another_func() instead.', 'affiliate-wp' ), '2.19.1' );
 *
 * @since 2.20.0
 *
 * @param string $function_name The function name or fully-qualified method name.
 * @param string $message       The message to show (usually what function to use instead).
 * @param string $version       The version the function was deprecated.
 * @param int    $error_level   This lets you determine how harsh the warning is, defaults to an `E_USER_DEPRECATED`.
 * @param string $plugin        The plugin name (defaults to 'AffiliateWP').
 *
 * @throws \InvalidArgumentException If you do not specify a valid version.
 */
function affiliatewp_deprecate_function(
	string $function_name,
	string $message,
	string $version,
	int $error_level = E_USER_DEPRECATED,
	string $plugin = 'AffiliateWP'
) {

	affiliatewp_validate_version( $version );

	affiliatewp_trigger_error(
		$function_name,
		sprintf(
			'(%1$s) %2$s',
			sprintf(

				// Translators: %1$s is the plugin name and %2$s is the version number.
				__( 'Since %1$s Version %2$s', 'affiliate-wp' ),
				$plugin,
				$version
			),
			$message
		),
		$error_level
	);
}

/**
 * Deprecate a hook.
 *
 * This triggers an `E_USER_DEPRECATED` when `WP_DEBUG` is `true`.
 *
 * Message ends up being something like:
 *
 * "Hook <hook_name>: (Since AffiliateWP Version 2.19.1) Use another function instead."
 *
 * Usage:
 *
 *     affiliatewp_deprecate_hook( 'my_hook', __( 'This hook is now deprecated.', 'affiliate-wp' ), '2.19.1' );
 *
 * @since 2.24.2
 *
 * @param string $hook_name     The function name or fully-qualified method name.
 * @param string $message       The message to show (usually what function to use instead).
 * @param string $version       The version the function was deprecated.
 * @param int    $error_level   This lets you determine how harsh the warning is, defaults to an `E_USER_DEPRECATED`.
 * @param string $plugin        The plugin name (defaults to 'AffiliateWP').
 *
 * @throws \InvalidArgumentException If you do not specify a valid version.
 */
function affiliatewp_deprecate_hook(
	string $hook_name,
	string $message,
	string $version,
	int $error_level = E_USER_DEPRECATED,
	string $plugin = 'AffiliateWP'
) {

	affiliatewp_validate_version( $version );

	/* This is documented in wp-includes/functions.php */
	do_action( 'wp_trigger_error_run', $hook_name, $message, $error_level );

	$message = sprintf(
		'Hook %1$s: (Since %2$s Version %3$s) %4$s',
		$hook_name,
		$plugin,
		$version,
		$message
	);

	$message = wp_kses(
		$message,
		array(
			'a' => array(
				'href' => array(),
			),
			'br' => array(),
			'code' => array(),
			'em' => array(),
			'strong' => array(),
		),
		array( 'http', 'https' )
	);

	trigger_error( $message, $error_level );
}

/**
 * Validate a Version Number
 *
 * @param string  $version The version number.
 * @param boolean $error   When set to true (default), will trigger an error.
 *
 * @since 2.24.2
 *
 * @return boolean True if it's valid, false otherwise.
 */
function affiliatewp_validate_version( string $version, bool $error = true ) : bool {

	if ( ! empty( $version ) && version_compare( '0.0', $version, '<=' ) ) {
		return true; // Valid version.
	}

	if ( ! $error ) {
		return false; // Invalid.
	}

	// Show a notice about using the wrong version number, but continue.
	affiliatewp_trigger_error(
		__FUNCTION__,
		sprintf(

			// Translators: %s is the version string they passed.
			__('$version should be a valid SemVer version number, you passed %s, which does not appear to be valid.', 'affiliate-wp'),
			$version
		),
		E_USER_NOTICE
	);

	return false;
}
