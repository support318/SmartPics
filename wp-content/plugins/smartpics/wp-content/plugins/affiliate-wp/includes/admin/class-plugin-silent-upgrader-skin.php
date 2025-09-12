<?php
/**
 * Admin: Plugin Silent Upgrader Skin
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2022, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.5
 */

/** \WP_Upgrader_Skin class */
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';

#[\AllowDynamicProperties]

/**
 * Skin for on-the-fly addon installations.
 *
 * @since 2.9.5
 */
class Affiliate_WP_Silent_Upgrader_Skin extends \WP_Upgrader_Skin {

	/**
	 * Empty out the header of its HTML content and only check to see if it has
	 * been performed or not.
	 *
	 * @since 2.9.5
	 */
	public function header() {}

	/**
	 * Empty out the footer of its HTML contents.
	 *
	 * @since 2.9.5
	 */
	public function footer() {}

	/**
	 * Instead of outputting HTML for errors, just return them.
	 * Ajax request will end with `wp_send_json_error`.
	 *
	 * @since 2.9.5
	 *
	 * @param array $errors Array of errors with the installation process.
	 */
	public function error( $errors ) {

		if ( ! empty( $errors ) && wp_doing_ajax() ) {
			wp_send_json_error( $errors );
		}

		return $errors;
	}

	/**
	 * Empty out feedback message about the upgrade.
	 *
	 * @since 2.9.5
	 *
	 * @param string $string  Feedback message.
	 * @param mixed  ...$args Optional text replacements.
	 */
	public function feedback( $string, ...$args ) {}

	/**
	 * Empty out JavaScript output that calls function to decrement the update counts.
	 *
	 * @since 2.9.5
	 *
	 * @param string $type Type of update count to decrement.
	 */
	public function decrement_update_count( $type ) {}
}
