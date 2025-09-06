<?php

use AffiliateWP\Affiliate_Area;
use AffiliateWP\Installation_Tools;

#[\AllowDynamicProperties]

/**
 * Shortcodes Bootstrap
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */
class Affiliate_WP_Shortcodes {

	public function __construct() {

		add_shortcode( 'affiliate_area',              array( $this, 'affiliate_area'         ) );
		add_shortcode( 'affiliate_login',             array( $this, 'affiliate_login'        ) );
		add_shortcode( 'affiliate_registration',      array( $this, 'affiliate_registration' ) );
		add_shortcode( 'affiliate_conversion_script', array( $this, 'conversion_script'      ) );
		add_shortcode( 'affiliate_referral_url',      array( $this, 'referral_url'           ) );
		add_shortcode( 'affiliate_content',           array( $this, 'affiliate_content'      ) );
		add_shortcode( 'non_affiliate_content',       array( $this, 'non_affiliate_content'  ) );
		add_shortcode( 'affiliate_creative',          array( $this, 'affiliate_creative'     ) );
		add_shortcode( 'affiliate_creatives',         array( $this, 'affiliate_creatives'    ) );
		add_shortcode( 'opt_in',                      array( $this, 'opt_in_form'            ) );
		add_shortcode( 'affiliate_coupons',           array( $this, 'affiliate_coupons'      ) );
	}

	/**
	 * Renders the affiliate area.
	 *
	 * @param string|array $atts    Unused.
	 * @param string|null  $content Unused.
	 *
	 * @return string Affiliate area content.
	 * @since 1.0
	 * @since 2.25.0 Changed the behavior of the shortcode depending on the Affiliate pages migration status.
	 */
	public function affiliate_area( $atts, $content = null ) : string {

		// See https://github.com/AffiliateWP/AffiliateWP/issues/867.
		if ( is_admin() && ( ! wp_doing_ajax() ) ) {
			return '';
		}

		affwp_enqueue_script( 'affwp-frontend', 'affiliate_area' );

		// For users that are not authenticated and are using a separate page for login, show the new message.
		if ( ! is_user_logged_in() && ! Affiliate_Area::get_instance()->is_affiliate_area_the_login_page() ) {
			return Affiliate_Area::get_instance()->get_unauthorized_access_message();
		}

		ob_start();

		// This will check if the user is logged and it is also an affiliate, if so, shows the dashboard.
		if ( affwp_is_affiliate() ) {

			affiliate_wp()->templates->get_template_part( 'dashboard' );

			return ob_get_clean();
		}

		/*
		 * The code below applies to the following conditions:
		 * 1) Non-authenticated user visits a non-migrated site.
		 * 2) Authenticated user visits the page independent if it was migrated or not.
		 *
		 * This has been kept mainly due to backwards compatibility with non-migrated sites.
		 * However, we also have the situation where we want to show the registration form
		 * if the user is a non-affiliate, so it is still valid for any sites, independent if
		 * they migrated or not.
		 */

		/**
		 * Filters the display of the registration form
		 *
		 * @since 2.0
		 *
		 * @param bool $show Whether to show the registration form. Default true.
		 */
		$show_registration = apply_filters( 'affwp_affiliate_area_show_registration', true );

		if ( true === $show_registration && affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {
			affiliate_wp()->templates->get_template_part( 'register' );
		} else {
			affiliate_wp()->templates->get_template_part( 'no', 'access' );
		}

		/**
		 * Filters the display of the login form
		 *
		 * @since 2.0
		 *
		 * @param bool $show Whether to show the login form. Default true.
		 */
		$show_login = apply_filters( 'affwp_affiliate_area_show_login', true );

		if ( true === $show_login && ! is_user_logged_in() ) {
			affiliate_wp()->templates->get_template_part( 'login' );
		}

		return ob_get_clean();
	}

	/**
	 * Renders the affiliate login form.
	 *
	 * @since 1.1
	 * @since 2.15.0 Sanitization of shortcode attributes.
	 * @param string|array $atts {
	 *     Optional. Shortcode arguments.
	 *
	 *     @type string $redirect Url to redirect user after login.
	 * }
	 * @param string|null  $content Shortcode contents (unused).
	 *
	 * @return string Login form html.
	 */
	public function affiliate_login( $atts, $content = null ) : string {

		// Nothing to display if user is already logged in.
		if ( is_user_logged_in() ) {
			return '';
		}

		$atts = array_merge(
			// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
			$atts = shortcode_atts(
				array(
					'redirect' => '',
				),
				$atts,
				'affiliate_login'
			),
			array(
				'redirect' => empty( $atts['redirect'] ) ? '' : sanitize_text_field( $atts['redirect'] ),
			)
		);

		// Empty redirect, use the default login url.
		if ( empty( $atts['redirect'] ) ) {
			return affiliate_wp()->login->login_form( affiliate_wp()->login->get_login_url() );
		}

		return affiliate_wp()->login->login_form(
			$this->get_redirect_url( $atts['redirect'] )
		);
	}

	/**
	 *  Renders the affiliate registration form.
	 *
	 * @param string|array $atts {
	 *     Optional. Shortcode arguments.
	 *
	 *     @type string $redirect Url to redirect user after registration.
	 * }
	 * @param string|null  $content Shortcode contents (unused).
	 *
	 * @return string Register form html.
	 * @since 1.1
	 */
	public function affiliate_registration( $atts, $content = null ): string {

		if ( affwp_is_affiliate() || ! affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {
			return '';
		}

		affwp_enqueue_script( 'affwp-frontend', 'affiliate_registration' );

		$atts = array_merge(
			// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
			$atts = shortcode_atts(
				array(
					'redirect' => '',
				),
				$atts,
				'affiliate_registration'
			),
			array(
				'redirect' => empty( $atts['redirect'] ) ? '' : sanitize_text_field( $atts['redirect'] ),
			)
		);

		// Empty redirect, use the default login url.
		if ( empty( $atts['redirect'] ) ) {
			return affiliate_wp()->register->register_form( affiliate_wp()->login->get_login_url() );
		}

		return affiliate_wp()->register->register_form(
			$this->get_redirect_url( $atts['redirect'] )
		);
	}

	/**
	 *  Outputs a generic conversion script for custom referral tracking.
	 *
	 * @param string|array $atts {
	 *     Optional. Shortcode arguments.
	 *
	 *     @type string $amount      Total purchase amount.
	 *     @type string $description A description logged with the referral.
	 *     @type string $context     A context for the referral.
	 *     @type string $reference   A unique reference variable for the affiliate.
	 *     @type string $status      The status to give the referral. By default, referrals created with
	 *                               this shortcode will be set to “pending”.
	 *                               Valid options are: “pending”, “unpaid”, “paid”, and “rejected”.
	 *     @type string $type        The type of referral. By default, a referral will have a type of “sale”.
	 *                               Valid options are “sale”, “lead” and “opt-in”.
	 * }
	 * @param string|null  $content Shortcode contents (unused).
	 *
	 * @return string Conversion script.
	 * @since 1.0
	 * @since AFFWPN Added logic to check for 'status' and 'type' values from $_REQUEST and use them as defaults if provided,
	 *               overriding the shortcode attributes.
	 */
	public function conversion_script( $atts, $content = null ) : string {

		if (
			is_admin() ||
			( defined( 'REST_REQUEST' ) && REST_REQUEST )
		) {
			return '';
		}

		wp_enqueue_script(
			'jquery-cookie',
			sprintf(
				'%sassets/js/jquery.cookie%s.js',
				AFFILIATEWP_PLUGIN_URL,
				defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min'
			),
			array( 'jquery' ),
			'1.4.0',
			false
		);

		wp_localize_script(
			'jquery-cookie',
			'affwp_scripts',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);

		ob_start();

		affiliate_wp()->tracking->conversion_script(
			array_merge(
				// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				$atts = shortcode_atts(
					[
						'amount'      => '',
						'description' => '',
						'reference'   => '',
						'context'     => '',
						'campaign'    => '',
						'status'      => 'pending',
						'type'        => 'sale',
					],
					$atts,
					'affwp_conversion_script'
				),
				[
					'amount'      => sanitize_text_field( $atts['amount'] ),
					'description' => sanitize_text_field( $atts['description'] ),
					'reference'   => sanitize_text_field( $atts['reference'] ),
					'context'     => sanitize_text_field( $atts['context'] ),
					'campaign'    => sanitize_text_field( $atts['campaign'] ),
					'status'      => in_array( $atts['status'], array( 'pending', 'unpaid', 'paid', 'rejected' ), true )
										? sanitize_text_field( $_REQUEST['status'] ?? $atts['status'] )
										: 'pending',
					'type'        => in_array( $atts['type'], array( 'sale', 'lead', 'opt-in' ), true )
										? sanitize_text_field( $_REQUEST['type'] ?? $atts['type'] )
										: 'sale',
				]
			)
		);

		return ob_get_clean();

	}

	/**
	 * Outputs the referral URL for the current affiliate
	 *
	 * @param string|array $atts {
	 *     Optional. Shortcode arguments.
	 *
	 *     @type string $url    A custom referral url.
	 *     @type string $format Force a URL format, can be either "id" or "username".
	 *                          If left empty will inherit “Default Referral Format”
	 *                          option from the Affiliates → Settings page.
	 *     @type string $pretty Force pretty URLs. Use "yes" or "no" as values.
	 *                          If left empty will inherit the value from
	 *                          “Pretty Affiliate URLs" from the Affiliates → Settings page
	 * }
	 * @param string|null  $content Shortcode contents (unused).
	 *
	 * @return string
	 * @since 1.0.1
	 */
	public function referral_url( $atts, $content = null ) : string {

		if ( ! ( affwp_is_affiliate() && affwp_is_active_affiliate() ) ) {
			return '';
		}

		$atts = array_merge(
			// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
			$atts = shortcode_atts(
				array(
					'url'    => '',
					'format' => '',
					'pretty' => '',
				),
				$atts,
				'affiliate_referral_url'
			),
			array(
				'url'    => empty( $content )
								? esc_url_raw( $atts['url'] )
								: esc_url_raw( $content ),
				'format' => in_array( $atts['format'], array( 'id', 'username' ), true )
								? sanitize_text_field( $atts['format'] )
								: '',
				'pretty' => in_array( $atts['pretty'], array( 'yes', 'no' ), true )
								? sanitize_text_field( $atts['pretty'] )
								: '',
			)
		);

		return affwp_get_affiliate_referral_url(
			array(
				'format'   => $atts['format'],
				'base_url' => empty( $atts['url'] )
								? affiliate_wp()->tracking->get_current_page_url()
								: $atts['url'],
				'pretty'   => empty( $atts['pretty'] )
								? ''
								: affwp_string_to_bool( $atts['pretty'] ),
			)
		);
	}

	/**
	 * Affiliate content shortcode.
	 *
	 * Renders the content if the current user is an affiliate.
	 *
	 * @param string|array $atts Shortcode attributes (unused).
	 * @param null|string  $content Content to output.
	 * @return string HTML content.
	 *
	 * @since  1.0.4
	 */
	public function affiliate_content( $atts, $content = null ): string {

		if ( ! ( affwp_is_affiliate() && affwp_is_active_affiliate() ) ) {
			return '';
		}

		return do_shortcode( $content );
	}

	/**
	 * Non Affiliate content shortcode.
	 *
	 * Renders the content if the current user is not an affiliate.
	 *
	 * @param string|array $atts Shortcode attributes (unused).
	 * @param null|string  $content Content to output.
	 * @return string HTML content.
	 *
	 * @since  1.1
	 */
	public function non_affiliate_content( $atts, $content = null ) : string {

		if ( affwp_is_affiliate() && affwp_is_active_affiliate() ) {
			return '';
		}

		return do_shortcode( $content );
	}

	/**
	 * Affiliate creative shortcode.
	 *
	 * Allows you to show a specific creative from Affiliates → Creatives on any page.
	 *
	 * @param string|array $atts {
	 *     Optional. Shortcode arguments.
	 *
	 *     @type string|int $id          ID of the creative.
	 *     @type string|int $image_id    ID of image from media library if not using creatives section.
	 *     @type string     $image_link  External URL if image is hosted off-site.
	 *     @type string     $link        Where the banner links to.
	 *     @type string     $text        Text shown in alt/title tags.
	 *     @type string     $description Description for creative.
	 *     @type string     $preview     Display an image/text preview above HTML code. Accept "yes" or "no".
	 * }
	 * @param string|null  $content Shortcode contents (unused).
	 * @param array  $atts    Shortcode atttributes.
	 *
	 * @since  1.1.4
	 *
	 * @return string
	 * @since  1.1.4
	 *
	 * @since 2.26.2 Fixed issue causing frontend not to save when no creative is selected.
	 */
	public function affiliate_creative( $atts, $content = null ) {

		$default = is_string( $content )
			? $content
			: '';

		if ( ! is_array( $atts ) ) {
			return $default;
		}

		if ( ! ( affwp_is_affiliate() && affwp_is_active_affiliate() ) ) {
			return $default;
		}

		if ( ! affiliate_wp()->creative->groups->affiliate_can_access( $atts['id'] ?? 0 ) ) {
			return $default;
		}

		$creative = affiliate_wp()->creative->affiliate_creative(
			array_merge(
			// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				$atts = shortcode_atts(
					array(
						'id'          => '',
						'image_id'    => '',
						'image_link'  => '',
						'link'        => '',
						'text'        => '',
						'description' => '',
						'preview'     => 'yes',
					),
					$atts,
					'affiliate_creative'
				),
				array(
					'id'          => absint( $atts['id'] ),
					'image_id'    => absint( $atts['image_id'] ),
					'image_link'  => esc_url_raw( $atts['image_link'] ),
					'link'        => esc_url_raw( $atts['link'] ),
					'text'        => sanitize_text_field( $atts['text'] ),
					'description' => sanitize_text_field( $atts['description'] ),
					'preview'     => in_array( $atts['preview'], array( 'yes', 'no' ), true )
						? sanitize_text_field( $atts['preview'] )
						: 'yes',
				)
			)
		);

		return ! is_string( $creative ) ? '' : do_shortcode( $creative );
	}


	/**
	 * Affiliate creatives shortcode.
	 *
	 * Shows all the creatives from Affiliates -> Creatives.
	 *
	 * @param array $atts    Shortcode attributes.
	 * @param null  $content Default content.
	 *
	 * @since 1.1.4
	 *
	 * @return string
	 */
	public function affiliate_creatives( $atts, $content = null ) {

		$default = is_string( $content )
			? $content
			: '';

		if ( ! ( affwp_is_affiliate() && affwp_is_active_affiliate() ) ) {
			return $default;
		}

		return do_shortcode(
			affiliate_wp()->creative->affiliate_creatives(
				array_merge(
				// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
					$atts = shortcode_atts(
						array(
							'preview' => 'yes',
							'number'  => 20,
						),
						$atts,
						'affiliate_creatives'
					),
					array(
						'preview' => in_array( $atts['preview'], array( 'yes', 'no' ), true )
							? sanitize_text_field( $atts['preview'] )
							: 'yes',
						'number'  => absint( $atts['number'] ),
					)
				)
			)
		);
	}

	/**
	 *  Renders the opt-in.
	 *
	 * @param string|array $atts {
	 *     Optional. Shortcode arguments.
	 *
	 *     @type string $redirect Url to redirect the user.
	 * }
	 * @param string|null  $content Shortcode contents (unused).
	 *
	 * @return string
	 * @since 2.2
	 */
	public function opt_in_form( $atts, $content = null ) : string {

		$atts = array_merge(
			// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
			$atts = shortcode_atts(
				array(
					'redirect' => '',
				),
				$atts,
				'opt_in'
			),
			array(
				'redirect' => empty( $atts['redirect'] ) ? '' : sanitize_text_field( $atts['redirect'] ),
			)
		);

		return affiliate_wp()->integrations->opt_in->form(
			$this->get_redirect_url( $atts['redirect'] )
		);
	}

	/**
	 *  Affiliate coupons shortcode.
	 *
	 * @param string|array $atts    Unused.
	 * @param string|null  $content Unused.
	 *
	 * @return string
	 * @since 2.6
	 */
	public function affiliate_coupons( $atts, $content = null ) : string {

		if ( function_exists( 'affiliatewp_show_affiliate_coupons' ) ) {
			return '';
		}

		if ( ! ( affwp_is_affiliate() && affwp_is_active_affiliate() ) ) {
			return '';
		}

		ob_start();

		affiliate_wp()->templates->get_template_part( 'dashboard-tab', 'coupons' );

		return do_shortcode( ob_get_clean() );
	}

	/**
	 * Return a URL to redirect.
	 *
	 * Helper method to use with shortcodes which generate forms.
	 * Try to "guess" the URL to redirect the user to based on some acceptable values
	 * or based on a custom URL.
	 *
	 * @param string $url Accepts "current", "referrer" or a custom url.
	 *
	 * @return string Url to redirect.
	 */
	protected function get_redirect_url( string $url ) : string {

		if ( 'current' === $url ) {
			return '';
		}

		if ( 'referrer' === $url && ! empty( wp_get_referer() ) ) {
			return wp_get_referer();
		}

		return esc_url_raw( $url );
	}
}
new Affiliate_WP_Shortcodes();
