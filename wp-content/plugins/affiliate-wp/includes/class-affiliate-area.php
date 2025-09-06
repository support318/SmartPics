<?php
/**
 * AffiliateWP Affiliate Area
 *
 * @package   AffiliateWP
 * @copyright Copyright (c) 2024, Awesome Motive, Inc
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     2.25.0
 * @author    Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP;

#[\AllowDynamicProperties]

/**
 * Affiliate Area class
 *
 * Used to handle Affiliate Area generic tasks.
 *
 * @since 2.25.0
 */
final class Affiliate_Area {

	/**
	 * Instance of the class
	 *
	 * @var self|null
	 * @since 2.25.0
	 */
	private static ?self $instance = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', [ $this, 'redirect_to_registration_page_when_non_affiliate_is_logged_in' ] );
	}

	/**
	 * Gets the instance of the class
	 *
	 * @since 2.25.0
	 *
	 * @return self|null The class instance, null otherwise.
	 */
	public static function get_instance() : ?self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Redirect user to the Registration page in case is an authenticated non-affiliate.
	 *
	 * @since 2.25.1
	 *
	 * @return void
	 */
	public function redirect_to_registration_page_when_non_affiliate_is_logged_in() : void {

		if ( ! is_user_logged_in() || affwp_is_affiliate() ) {
			return; // Bail if the user is not logged or is affiliate already.
		}

		$affiliate_area_page_id = affwp_get_affiliate_area_page_id();
		$registration_page_id   = affiliatewp_get_affiliate_registration_page_id();

		if ( $affiliate_area_page_id === $registration_page_id ) {
			return; // Bail since the registration page is the same as the Affiliate Area page.
		}

		if ( ! is_page( $affiliate_area_page_id ) || empty( $registration_page_id ) ) {
			return; // Bail because if is not the Affiliate Area page or if the registration page doesn't exist.
		}

		// Redirect to the registration page when logged in as a non-affiliate.
		wp_safe_redirect(
			get_permalink( $registration_page_id )
		);

		exit;
	}

	/**
	 * Retrieve the message used for non-authorized users when trying to access the Affiliate Area.
	 *
	 * @since 2.25.0
	 *
	 * @return string The message.
	 */
	public function get_unauthorized_access_message() : string {

		$login_page_id = affiliatewp_get_affiliate_login_page_id();
		$page_status   = get_post_status( $login_page_id );

		return sprintf(
			'<p class="affwp-notice">%s</p>',
			'publish' === $page_status || ( 'draft' === $page_status && current_user_can( 'edit_pages' ) )
				? wp_kses(
					sprintf(
					/* translators: %s is the login page URL */
						__( 'You\'re unauthorized to view this page. Please <a href="%s">log in</a> and try again.', 'affiliate-wp' ),
						get_permalink( $login_page_id )
					),
					affwp_kses()
				)
				: esc_html__( 'You\'re unauthorized to view this page. Please log in and try again.', 'affiliate-wp' )
		);
	}

	/**
	 * Checks if the login page is the same as the affiliate area page.
	 *
	 * @since 2.25.0
	 *
	 * @return bool Whether the site is using the legacy affiliate area for user login.
	 */
	public function is_affiliate_area_the_login_page() : bool {
		return affiliatewp_get_affiliate_login_page_id() === affwp_get_affiliate_area_page_id();
	}

	/**
	 * Checks if the registration page is the same as the affiliate area page.
	 *
	 * This check helps determine if the site is using the legacy registration flow.
	 * In the legacy flow, the registration and affiliate area are combined into one page.
	 *
	 * A significant difference between the new and old flows is that in the legacy flow,
	 * the user is automatically logged in after submitting the registration form,
	 * whereas in the new flow, a message is shown on the same page instead.
	 *
	 * @since 2.25.0
	 *
	 * @return bool Whether the site is using the legacy affiliate area for registration.
	 */
	public function is_affiliate_area_the_register_page() : bool {

		affiliatewp_deprecate_function(
			__METHOD__,
			__( 'This method will be removed in the near future.', 'affiliate-wp' ),
			'2.25.1'
		);

		return affiliatewp_get_affiliate_registration_page_id() === affwp_get_affiliate_area_page_id();
	}
}
