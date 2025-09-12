<?php
/**
 * Admin Notice
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2024, AwesomeMotive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.27.4
 * @author      Aubrey Portwood <aportwood@am.co>
 *
 * phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine
 */

namespace AffiliateWP\Notices;

use \Affiliate_WP_Admin_Notices;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Notice
 *
 * Use this class to create a notice in the admin.
 *
 * Example:
 *
 *    add_action( 'affiliatewp_admin_notices', function() {
 *       new Admin_Notice(
 *          'my_notice_id',
 *          __( 'My message', 'text-domain' ),
 *          'non-dismissible',
 *          'error',
 *          'administrator',
 *          'any'
 *       );
 *    } );
 *
 * @since 2.27.4
 */
final class Admin_Notice {

	/**
	 * Notice ID
	 *
	 * Set in __construct().
	 *
	 * @since 2.27.4
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Storage type for dismissal state
	 *
	 * @since 2.27.4
	 *
	 * @var string
	 */
	private string $storage_type;

	/**
	 * Construct Notice
	 *
	 * @since 2.27.4
	 *
	 * @param string $id                     A unique ID for the notice.
	 * @param string $message                The message your notice displays.
	 * @param string $dismissible            Set to `dismissible` (default) to make the notice dismissible, `non-dismissible` to make it unable to be dismissed.
	 * @param string $class                  The class to apply to the notice, e.g. `success`, `error`, `warning`, `info`, or `updated` (empty, the default, is a grey colored admin notice).
	 * @param string $capability             The capability required to see the notice (`manage_affiliates` by default).
	 * @param string $page                   Only show this notice on this page, set to `any` for any page in WordPress, `affiliatewp` for any AffiliateWP page
	 *                                       or any ?page value e.g. `affiliate-wp-affiliates` or `affiliate-wp-referrals` or any other custom value for ?page.
	 * @param array  $args                   Notice arguments to override: Note that for
	 *                                       `message`, `capability`, and `dismissible`, if you set
	 *                                       these values they will override the parameters by the same
	 *                                       names in this method.
	 *
	 * @throws \Exception If we cannot register the notice with AffiliateWP.
	 * @throws \Exception If you try and create an admin notice before the `affwp_plugins_loaded` hook has fired.
	 *
	 * @throws \InvalidArgumentException If you pass an in-correct value for `$dismissible`.
	 * @throws \InvalidArgumentException If `$page` is empty or not set to `any` or `affiliatewp`.
	 */
	public function __construct(
		string $id,
		string $message,
		string $dismissible = 'dismissible', // Can be overridden by $args.
		string $class = '', // Can be overridden by $args.
		string $capability = 'manage_affiliates', // Can be overridden by $args.
		string $page = 'any',
		array $args = []
	) {

		$this->id = $id;
		$this->storage_type = $args['storage_type'] ?? 'option'; // Default to wp_options for backward compatibility.

		if ( ! in_array( $this->storage_type, [ 'option', 'user_meta' ], true ) ) {
			throw new \InvalidArgumentException( 'storage_type must be either "option" or "user_meta"' );
		}

		if ( ! is_admin() ) {
			return; // Only create this in the admin.
		}

		if ( ! did_action( 'affwp_plugins_loaded' ) ) {
			throw new \Exception( 'Please do not create notices before the affwp_plugins_loaded action, consider the affiliatewp_admin_notices action for safe creation of notifications.' );
		}

		if ( ! in_array( $dismissible, [ 'dismissible', 'non-dismissible' ], true ) ) {
			throw new \InvalidArgumentException( 'Wrong value for $dismissible' );
		}

		if ( empty( $page ) ) {
			throw new \InvalidArgumentException( '$page cannot be empty, supply `any` for any admin page, `affiliatewp` for any AffiliateWP page, or specific page like `affiliate-wp-affiliates` for ?page=affiliate-wp-affiliates.' );
		}

		if ( in_array( $page, [ 'affiliatewp', 'affiliate-wp' ], true ) && ! affwp_is_admin_page() ) {
			return; // Not an AffiliateWP page.
		}

		if ( ! in_array( $page, [ 'any', 'affiliatewp', 'affiliate-wp' ], true ) && ! $this->is_page( $page ) ) {
			return; // Not a specific page, must be `any`.
		}

		if ( $dismissible && $this->is_dismissed() ) {
			return; // This notice was dismissed previously.
		}

		$notice = Affiliate_WP_Admin_Notices::get_registry_statically()->add_notice(
			$id,
			array_merge(
				[
					'capability'  => $capability,
					'class'       => $class,
					'dismissible' => ( 'dismissible' === $dismissible )
						? true
						: false,
					'message'     => $message,

					// This tells the registry to automatically show this kind of notice.
					'type'        => get_class( $this ),
				],
				$args
			)
		);

		if ( $dismissible ) {
			add_action( 'affwp_dismiss_notices_default', [ $this, 'on_dismiss_notice' ] );
		}

		if ( ! is_wp_error( $notice ) ) {
			return;
		}

		throw new \Exception( $notice->get_error_message(), $notice->get_error_code() );
	}

	/**
	 * When this notice is dismissed.
	 *
	 * @since 2.27.4
	 *
	 * @param string $notice_id The Notice ID being processed for dismissal.
	 */
	public function on_dismiss_notice( string $notice_id ) : void {

		if ( $notice_id !== $this->id ) {
			return; // The notice coming from this filter is not this notice.
		}

		if ( 'user_meta' === $this->storage_type ) {
			update_user_meta( get_current_user_id(), $this->get_dismissal_key(), 'dismissed' );
		} else {
			update_option( $this->get_dismissal_key(), 'dismissed', false );
		}
	}

	/**
	 * Has this notice been dismissed?
	 *
	 * @since 2.27.4
	 *
	 * @return bool True if it has been dismissed in the past.
	 */
	private function is_dismissed() : bool {
		if ( 'user_meta' === $this->storage_type ) {
			return 'dismissed' === get_user_meta( get_current_user_id(), $this->get_dismissal_key(), true );
		}

		return 'dismissed' === get_option( $this->get_dismissal_key() );
	}

	/**
	 * The option key for tracking dismissals.
	 *
	 * @since 2.27.4
	 *
	 * @return string
	 */
	private function get_dismissal_key() : string {

		// Convert dashes to underscores.
		return str_replace(
			'-',
			'_',

			// An option key formatted with no special characters, etc (dashes removed above).
			sanitize_title_with_dashes(
				"affiliatewp_admin_notice_{$this->id}_dismissed"
			)
		);
	}

	/**
	 * Are we currently viewing an Admin page?
	 *
	 * Uses ?page directly.
	 *
	 * @since 2.27.4
	 *
	 * @param string $page Set to a specific page, leave empty for any e.g. `affiliate-wp-affiliates`.
	 *
	 * @return bool
	 *
	 * @throws \Exception If you try and use this before the `wp_loaded` hook is ran, will throw this error.
	 */
	private function is_page( string $page = '' ) : bool {

		if ( did_action( 'wp_loaded' ) ) {
			throw new \Exception( 'You cannot check this before the wp_loaded hook has fired.' );
		}

		return filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW ) === $page;
	}
}
