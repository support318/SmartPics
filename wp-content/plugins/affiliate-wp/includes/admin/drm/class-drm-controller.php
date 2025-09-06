<?php
/**
 * AffiliateWP DRM.
 *
 * DRM implementation.
 *
 * @package    AffiliateWP
 * @subpackage AffiliateWP\Admin\DRM
 * @author     Darvin da Silveira <ddasilveira@awesomeomotive.com>
 * @copyright  Copyright (c) 2023, Awesome Motive, Inc
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.21.1
 */

namespace AffiliateWP\Admin\DRM;

use Affiliate_WP_Emails;
use AffWP\Admin\Notices_Registry;
use Affiliate_WP_Admin_Notices;
use AffWP\Core\License\License_Data;

/**
 * The main DRM class that controls all related behaviors.
 */
class DRM_Controller {

	/**
	 * Number of days after the last check that it takes to enter the Low level of warnings stage for unlicensed sites.
	 *
	 * @since 2.21.1
	 */
	const UNLICENSED_LOW_LEVEL_STARTS_AT = 14;

	/**
	 * Number of days after the last check that it takes to enter the Medium level of warnings stage for unlicensed sites.
	 *
	 * @since 2.21.1
	 */
	const UNLICENSED_MEDIUM_LEVEL_STARTS_AT = 21;

	/**
	 * Number of days after the last check that it takes to lock AffiliateWP features for unlicensed sites.
	 *
	 * @since 2.21.1
	 */
	const UNLICENSED_LOCKED_STARTS_AT = 30;

	/**
	 * Number of days after the last check that it takes to enter the Low level of warnings stage for sites with invalid licenses.
	 *
	 * @since 2.21.1
	 */
	const INVALID_LICENSE_MEDIUM_LEVEL_STARTS_AT = 7;

	/**
	 * Number of days after the last check that it takes to lock AffiliateWP features for sites with invalid licenses.
	 *
	 * @since 2.21.1
	 */
	const INVALID_LICENSE_LOCKED_STARTS_AT = 21;

	/**
	 * The amount of time that a notice should stay dismissed.
	 *
	 * @since 2.21.1
	 */
	const NOTICE_DISMISS_TIMEOUT = DAY_IN_SECONDS;

	/**
	 * License general info.
	 *
	 * @since 2.21.1
	 *
	 * @var array
	 */
	private array $license_info;

	/**
	 * The last saved DRM state.
	 *
	 * @since 2.21.1
	 *
	 * @var string
	 */
	private string $current_state;

	/**
	 * The DRM level the customer is.
	 *
	 * @since 2.21.1
	 *
	 * @var string
	 */
	private string $level;

	/**
	 * The DRM UTM links.
	 *
	 * @since 2.21.1
	 *
	 * @var array
	 */
	private array $links;

	/**
	 * Enable/disable in-plugin notifications.
	 *
	 * @since 2.21.1
	 *
	 * @var bool
	 */
	private bool $is_in_plugin_notifications_enabled = false;

	/**
	 * The License Data object.
	 *
	 * @since 2.21.1
	 *
	 * @var License_Data
	 */
	private License_Data $license_data;

	/**
	 * Stores notifications data, like:
	 * - Level
	 * - In-plugin sent
	 * - Email sent
	 *
	 * @since 2.21.1
	 *
	 * @var array
	 */
	private array $notifications = array();

	/**
	 * Constructor.
	 *
	 * @since 2.21.1
	 */
	public function __construct() {

		// Initiate all DRM hooks.
		$this->hooks();
	}

	/**
	 * Set all hooks.
	 *
	 * @since 2.21.1
	 */
	private function hooks() {

		// Main actions, everything starts and can end here.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'maybe_shutdown' ) );

		// Actions that changes the plugin behavior based on the DRM state and level.
		add_action( 'affwp_notices_registry_init', array( $this, 'register_notices' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_notices' ) );
		add_action( 'admin_menu', array( $this, 'prevent_admin_pages_access' ) );
		add_action( 'admin_menu', array( $this, 'deregister_submenus' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_body_class', array( $this, 'append_body_classes' ) );
		add_action( 'wp_ajax_affiliatewp_handle_license_form_submission', array( $this, 'handle_ajax_license_submission' ) );

		// Filters.
		add_filter( 'site_status_tests', array( $this, 'add_site_health_test' ) );
	}

	/**
	 * Initiate DRM.
	 *
	 * @since 2.21.1
	 */
	public function init() {

		// Restrict admin only.
		if ( ! is_admin() ) {
			return;
		}

		$this->license_data = new License_Data();

		// Store the results of license status and site activated flag.
		$this->license_info = array(
			'status'            => $this->license_data->check_status(),
			'is_site_activated' => $this->license_data->is_license_site_activated(),
		);

		// Load the last known DRM state.
		$this->current_state = $this->get_current_state();

		// Check for any updates in the current DRM state.
		$this->update_current_state();

		// Get the DRM level: active, initiated, low, med, locked.
		$this->level = $this->get_level();

		// Load UTM links.
		$this->links = $this->get_utm_links();

		// Try to notify the user if needed.
		$this->maybe_notify();
	}

	/**
	 * Remove any hooks that changes the behavior of the plugin if the license is fully activated.
	 *
	 * @since 2.21.1
	 */
	public function maybe_shutdown() {

		// Customer has an invalid, or it is unlicensed, DRM warnings or locks should stay.
		if ( 'active' !== $this->level ) {
			return;
		}

		// Remove actions.
		remove_action( 'affwp_notices_registry_init', array( $this, 'register_notices' ) );
		remove_action( 'admin_notices', array( $this, 'maybe_show_notices' ) );
		remove_action( 'admin_menu', array( $this, 'prevent_admin_pages_access' ) );
		remove_action( 'admin_menu', array( $this, 'deregister_submenus' ), 30 );
		remove_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		remove_action( 'admin_body_class', array( $this, 'append_body_classes' ) );
		remove_action( 'admin_init', array( $this, 'send_email' ), 30 );
	}

	/**
	 * Redirect the customer to the Affiliates admin page when locked.
	 *
	 * The best hook to run this method is with admin_menu, despite not being a method that is used to handle menus,
	 * we must run after wp_loaded hook because we are using the affwp_is_admin_page() function, which doesn't work properly
	 * if you try to call in earlier hooks.
	 * Considering this, admin_menu is the next hook in the order that is admin-only.
	 * Note: hooking into admin_init will also fail, because it runs after admin_menu and at this point the user will
	 * get an access error because the menu doesn't exist anymore.
	 *
	 * @since 2.21.1
	 */
	public function prevent_admin_pages_access() {

		if ( ! ( affwp_is_admin_page() && 'locked' === $this->level ) ) {
			return; // Don't do redirects if it's not locked and not on an AffiliateWP admin page.
		}

		if ( filter_input( INPUT_GET, 'page' ) === 'affiliate-wp-affiliates' ) {
			return; // Avoid multiple redirects.
		}

		wp_safe_redirect( affwp_admin_url( 'affiliates' ) );

		die();
	}

	/**
	 * Decide if notices should be shown to the user.
	 *
	 * @since 2.21.1
	 */
	public function maybe_show_notices() {

		if ( false !== get_transient( 'affwp_drm_notice' ) ) {
			return; // Do not show notices if the notice was temporarily dismissed already.
		}

		// Display notices to the customer.
		Affiliate_WP_Admin_Notices::show_notice( "{$this->current_state}_{$this->level}" );
	}

	/**
	 * Check if the customer needs to be notified via in-plugin notifications or email.
	 *
	 * @since 2.21.1
	 *
	 * @return void
	 */
	private function maybe_notify() {

		// Customer is using a fully activated license, nothing to notify.
		if ( 'active' === $this->level ) {
			return;
		}

		$this->notifications = get_option( 'affwp_drm_notifications', array() );

		$notification_key = "{$this->current_state}_{$this->level}";

		// Notify by the in-plugin notification API.
		if (
			$this->is_in_plugin_notifications_enabled &&
			! isset( $this->notifications[ $notification_key ]['inplugin'] )
		) {
			$this->add_in_plugin_notification();
		}

		// Notify by email.
		if ( ! isset( $this->notifications[ $notification_key ]['email'] ) ) {
			$this->schedule_email_notification();
		}
	}

	/**
	 * Add the in-plugin notification.
	 *
	 * @since 2.21.1
	 */
	private function add_in_plugin_notification() {

		$notifications = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/in-plugin-notifications.php';

		$notification_key = "{$this->current_state}_{$this->level}";

		if ( ! isset( $notifications[ $notification_key ] ) ) {
			return; // If no notification is registered, it means we should not notify.
		}

		// Add the notification to the DB.
		if ( ! affiliate_wp()->notifications->add( $notifications[ $notification_key ] ) ) {
			return; // Could not notify.
		}

		update_option(
			'affwp_drm_notifications',
			array_merge_recursive(
				$this->notifications,
				array(
					$notification_key => array(
						'inplugin' => strtotime( current_time( 'mysql' ) ),
					),
				)
			),
			false
		);
	}

	/**
	 * Schedule the email notification.
	 *
	 * @since 2.21.1
	 */
	private function schedule_email_notification() {
		add_action( 'admin_init', array( $this, 'send_email' ), 30 );
	}

	/**
	 * Notify by email.
	 *
	 * @since 2.21.1
	 */
	public function send_email() {

		$emails = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/emails.php';

		$notification_key = "{$this->current_state}_{$this->level}";

		if ( ! isset( $emails[ $notification_key ] ) ) {
			return; // If no email is registered, it means we should not notify.
		}

		// Send the email.
		if ( ! ( new Affiliate_WP_Emails() )->send(
			affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) ),
			$emails[ $notification_key ]['subject'],
			is_callable( $emails[ $notification_key ]['message'] )
				? call_user_func( $emails[ $notification_key ]['message'] )
				: $emails[ $notification_key ]['message']
		) ) {
			return; // Could not send the email.
		}

		update_option(
			'affwp_drm_notifications',
			array_merge_recursive(
				$this->notifications,
				array(
					$notification_key => array(
						'email' => strtotime( current_time( 'mysql' ) ),
					),
				)
			),
			false
		);
	}

	/**
	 * Deregister all AffiliateWP submenus, except by Affiliates, when site is locked out.
	 *
	 * This method is intended to run with the admin_menu hook, which is executed before, but also
	 * after the admin_init hook (our starting point), so additional checks are done to check if this
	 * class is fully loaded before trying to access the current_state property.
	 *
	 * @since 2.21.1
	 */
	public function deregister_submenus() {

		// Don't show any DRM notices or locks.
		if ( 'locked' !== $this->level ) {
			return;
		}

		global $submenu;

		array_map(
			function( $submenu ) {

				if ( 'affiliate-wp-affiliates' === $submenu[2] ) {
					return; // Keep this one accessible.
				}

				remove_submenu_page( 'affiliate-wp', $submenu[2] );
			},
			$submenu['affiliate-wp']
		);
	}

	/**
	 * Enqueue the DRM lock scripts.
	 *
	 * @since 2.21.1
	 */
	public function enqueue_scripts() {

		// No DRM locks.
		if ( 'locked' !== $this->level ) {
			return;
		}

		add_action( 'affiliatewp_admin_education_strings', array( $this, 'append_js_strings' ) );

		affiliate_wp()->scripts->enqueue(
			'affiliatewp-drm',
			array(
				'jquery-confirm',
				'affiliatewp-admin-education-core',
			),
			sprintf(
				'%1$sadmin-drm%2$s.js',
				affiliate_wp()->scripts->get_path(),
				affiliate_wp()->scripts->get_suffix(),
			)
		);
	}

	/**
	 * Handle the user attempt of activating the license within the blocking modal.
	 *
	 * @since 2.21.1
	 */
	public function handle_ajax_license_submission() {

		if ( ! wp_verify_nonce( filter_input( INPUT_POST, 'nonce' ), 'affiliatewp-education' ) ) {

			wp_send_json_error();

			die;
		}

		$status = $this->license_data->activation_status(
			sanitize_text_field( filter_input( INPUT_POST, 'license_key' ) ),
			true
		);

		if (
			false === $status['license_status'] &&
			( $status['affwp_notice'] ?? '' ) === 'license-http-failure'
		) {
			// If API call fails, set a transient so we can properly handle the customer's feedback.
			set_transient( 'affwp_drm_api_status', 'failed', 3 * HOUR_IN_SECONDS );

			// Send ajax messages.
			wp_send_json_success( $status );

			// Ensure it exits.
			die;
		}

		// It seems API is online again, we can remove the transient.
		delete_transient( 'affwp_drm_api_status' );

		wp_send_json_success( $status );

		die;
	}

	/**
	 * Append the JS strings to be used with the modal.
	 *
	 * @param array $js_strings The array of strings.
	 *
	 * @since 2.21.1
	 */
	public function append_js_strings( array $js_strings = array() ) : array {

		ob_start();

		// Check if customer is facing issues with our API.
		$api_last_status = get_transient( 'affwp_drm_api_status' );

		?>
		<p><?php esc_html_e( 'Your AffiliateWP license key is missing or is invalid. Without an active license key your front-end website is unaffected. However, you can no longer:', 'affiliate-wp' ); ?></p>
		<ul class="affwp-drm-locked-features">
			<li><?php esc_html_e( 'Disburse affiliate payouts', 'affiliate-wp' ); ?></li>
			<li><?php esc_html_e( 'Register &amp; manage affiliates', 'affiliate-wp' ); ?></li>
			<li><?php esc_html_e( 'Analyze performance data', 'affiliate-wp' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'This problem is easy to fix!', 'affiliate-wp' ); ?></p>
		<div class="jconfirm-buttons">
			<a class="btn btn-confirm" href="<?php echo esc_url( $this->get_utm_link( 'pricing' ) ); ?>" target="_blank"><?php esc_html_e( 'Buy or renew your license', 'affiliate-wp' ); ?></a>
		</div>
		<p><?php esc_html_e( 'If you have an existing license key, locate it on your', 'affiliate-wp' ); ?> <a target="_blank" href="<?php echo esc_url( $this->get_utm_link( 'account' ) ); ?>"><?php esc_html_e( 'Account Page', 'affiliate-wp' ); ?></a> <?php esc_html_e( 'and enter it below:', 'affiliate-wp' ); ?></p>
		<form id="affwp-drm-ajax-license-activation" autocomplete="off" class="jconfirm-buttons">
			<input
				name="license_key"
				required
				autocomplete="new-password"
				type="password"
				placeholder="<?php esc_attr_e( 'License Key', 'affiliate-wp' ); ?>"
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The function returns an escaped string.
				echo affiliatewp_tag_attr( 'value', 'failed' === $api_last_status ? $this->license_data->get_license_key() : '' );
				?>
			>
			<button type="submit" class="btn btn-confirm">
				<?php
				if ( 'failed' === $api_last_status ) {
					esc_html_e( 'Try again', 'affiliate-wp' );
				} else {
					esc_html_e( 'Verify key', 'affiliate-wp' );
				}
				?>
			</button>
		</form>
		<?php if ( 'failed' === $api_last_status ) : ?>
			<div id="affwp-drm-ajax-messages" data-type="error">
				<?php
				printf(
					wp_kses(
						/* translators: %s - Link to contact support */
						__( 'Your previous attempt to activate your license was unsuccessful. Feel free to give it another try. If you encounter any issues, please don\'t hesitate to <a href="%s">contact our support team</a> for further assistance.', 'affiliate-wp' ),
						array(
							'a' => array(
								'href' => array(),
							),
						)
					),
					esc_url( $this->get_utm_link( 'support' ) )
				);
				?>
			</div>
		<?php endif; ?>

		<?php

		return array_merge_recursive(
			$js_strings,
			array(
				'drm' => array(
					'title'   => sprintf(
						'<span style="color:#d63638">%1$s</span> %2$s',
						__( 'ALERT!', 'affiliate-wp' ),
						__( 'AffiliateWP Backend is Deactivated', 'affiliate-wp' )
					),
					'message' => ob_get_clean(),
					'ajax'    => array(
						'buttonText'         => __( 'Verify key', 'affiliate-wp' ),
						'success'            => __( 'Your license was activated successfully. Your page will be reloaded in 3s.', 'affiliate-wp' ),
						'error'              => __( 'Sorry, we could not activate your license at the moment. Please refresh your page and try again.', 'affiliate-wp' ),
						'invalid'            => __( 'The license key provided is invalid. Please check your license key and try again.', 'affiliate-wp' ),
						'expired'            => sprintf( __( 'The license key provided is expired. <a href="%s" target="_blank">Renew your license</a>.', 'affiliate-wp' ), esc_url( $this->get_utm_link( 'account' ) ) ),
						'licenseHttpFailure' => array(
							'buttonText' => __( 'Try again', 'affiliate-wp' ),
							'message'    => sprintf(
								/* translators: %s - Link to contact support */
								__( 'Your license key could not be verified, please try again in a few minutes. If you need assistance, please <a href="%s" target="_blank">contact our support team</a>.', 'affiliate-wp' ),
								esc_url( $this->get_utm_link( 'support' ) )
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Append a class with the current site DRM level to the body.
	 *
	 * The class is useful for custom styles and other DOM manipulations.
	 *
	 * @since 2.21.1
	 *
	 * @param string $classes The string of wp classes.
	 */
	public function append_body_classes( string $classes ) : string {

		if ( ! affwp_is_admin_page() ) {
			return $classes;
		}

		return "{$classes} affwp-drm-level-{$this->level}";
	}

	/**
	 * Check if Site Health messages should be displayed in admin.
	 *
	 * @since 2.21.1
	 *
	 * @param array $tests Site Health tests array.
	 *
	 * @return array
	 */
	public function add_site_health_test( array $tests ) : array {

		$tests['direct']['affiliatewp_drm'] = array(
			'label' => 'AffiliateWP',
			'test'  => array( $this, 'site_health_test' ),
		);

		return $tests;
	}

	/**
	 * Site Health test.
	 *
	 * @since 2.21.1
	 *
	 * @return array The test result.
	 */
	public function site_health_test() : array {

		if ( ! isset( $this->level ) ) {
			return array();
		}

		$messages = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/site-health.php';

		// Site is fully active.
		if ( 'active' === $this->level && isset( $messages['active'] ) ) {
			return $messages['active'];
		}

		// Site was locked.
		if ( 'locked' === $this->level && isset( $messages['locked'] ) ) {
			return $messages['locked'];
		}

		// Invalid (expired) license message.
		if ( 'invalid' === $this->current_state && isset( $messages['invalid'] ) ) {
			return $messages['invalid'];
		}

		// Unlicensed message.
		if ( 'unlicensed' === $this->current_state && isset( $messages['unlicensed'] ) ) {
			return $messages['unlicensed'];
		}

		// Can not find a Site Health message, nothing will be displayed.
		return array();
	}

	/**
	 * Register all DRM notices.
	 *
	 * @since 2.21.1
	 *
	 * @param Notices_Registry $registry Notices registry API.
	 */
	public function register_notices( Notices_Registry $registry ) {

		$notices = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/notices.php';

		if ( empty( $notices ) ) {
			return; // Can't find any notices.
		}

		foreach ( $notices as $notice_id => $notice ) {

			$registry->add_notice(
				$notice_id,
				array(
					'class'   => array(
						'notice',
						isset( $notice['level'] ) ? "notice-{$notice['level']}" : 'notice-error',
						'notice-drm',
					),
					'message' => function() use ( $notice ) {
						return is_callable( $notice['message'] )
							? call_user_func( $notice['message'] )
							: $notice['message'];
					},
				)
			);
		}
	}

	/**
	 * Calculate and return the number of days since the last time DRM was checked
	 * and returned a no-license or invalid license situation.
	 *
	 * @since 2.21.1
	 *
	 * @return int The number of days.
	 * @throws \Exception Could not generate DateTime results.
	 */
	private function days_elapsed() : int {

		$timestamp = $this->get_last_changed_state_time();

		if ( empty( $timestamp ) ) {
			return 0; // Invalid timestamp.
		}

		$start_date = new \DateTime( current_time( 'Y-m-d' ) );
		$end_date   = new \DateTime( gmdate( 'Y-m-d', $timestamp ) );
		$difference = $end_date->diff( $start_date );

		return absint( $difference->format( '%a' ) );
	}

	/**
	 * Retrieve the current DRM level based on the different states.
	 *
	 * This method utilizes specific constants to compare with the number of days elapsed since the last
	 * state update. The constant values are cumulative, enabling the determination of the current level
	 * of DRM the customer is in.
	 *
	 * The DRM levels are categorized as follows:
	 * - 'active': Indicates that the license is fully active.
	 * - 'initiated': Indicates the grace period started.
	 * - 'low_level': Represents the low phase of an unlicensed site or expired license.
	 * - 'med_level': Represents the medium phase of an unlicensed site or an expired license.
	 * - 'locked': Indicates a locked state due to an extended unlicensed period or an expired license.
	 *
	 * @since 2.21.1
	 *
	 * @return string The DRM level based on the current state.
	 */
	private function get_level() : string {

		// Customer is with a valid and active license.
		if ( 'valid' === $this->current_state ) {
			return 'active';
		}

		try {
			$days_elapsed = $this->days_elapsed();
		} catch ( \Exception $e ) {
			$days_elapsed = 0;
		}

		// Invalid license levels.
		if ( 'invalid' === $this->current_state ) {

			if ( $days_elapsed < self::INVALID_LICENSE_MEDIUM_LEVEL_STARTS_AT ) {
				return 'initiated';
			}

			if ( $days_elapsed < self::INVALID_LICENSE_LOCKED_STARTS_AT ) {
				return 'med_level';
			}

			return 'locked';
		}

		// Unlicensed levels.
		if ( $days_elapsed < self::UNLICENSED_LOW_LEVEL_STARTS_AT ) {
			return 'initiated';
		}

		if ( $days_elapsed < self::UNLICENSED_MEDIUM_LEVEL_STARTS_AT ) {
			return 'low_level';
		}

		if ( $days_elapsed < self::UNLICENSED_LOCKED_STARTS_AT ) {
			return 'med_level';
		}

		return 'locked';
	}

	/**
	 * Remove all DRM related metadata.
	 *
	 * @since 2.21.1
	 */
	public function clean_up_meta() {

		if ( 'valid' === $this->current_state ) {
			return; // The license is valid, nothing should have left at this point.
		}

		delete_option( 'affwp_drm_current_state' );
		delete_option( 'affwp_drm_last_changed_state_time' );
		delete_option( 'affwp_drm_notifications' );
		delete_transient( 'affwp_drm_notice' );
		delete_transient( 'affwp_drm_api_status' );
	}

	/**
	 * Update DRM options and transients accordingly to the new state.
	 *
	 * @since 2.21.1
	 *
	 * @param string $state The new state.
	 */
	private function update_state_metadata( string $state ) {

		// If state is going to change, ensure notifications meta are cleared up to start again.
		if ( get_option( 'affwp_drm_current_state' ) !== $state ) {
			delete_option( 'affwp_drm_notifications' );
		}

		// Update the current state at DB level.
		update_option( 'affwp_drm_current_state', $state, false );

		// Set the time the state has changed.
		update_option( 'affwp_drm_last_changed_state_time', strtotime( current_time( 'mysql' ) ), false );

		// Remove transient notices so we can start over.
		delete_transient( 'affwp_drm_notice' );
	}

	/**
	 * Updates the current DRM state.
	 *
	 * The data will be updated only if the current state has been changed compared to the license status returned,
	 * otherwise we will try to return as soon as we can to not hurt performance.
	 *
	 * @since 2.21.1
	 * @since 2.24.2 Updated to handle known license statuses for DRM.
	 */
	private function update_current_state() {

		// If it is an unknown license status, we ensure that any DRM notice show's up.
		if ( ! in_array(
			$this->license_info['status'] ?? '',
			[
				'valid',
				'invalid',
				'pending',
				'expired',
			],
			true
		) ) {
			$this->current_state = 'valid';
		}

		// License turned to valid, clean up old metadata.
		if (
			'valid' !== $this->current_state &&
			'valid' === $this->license_info['status'] &&
			$this->license_info['is_site_activated']
		) {

			// Clean up all existent meta.
			$this->clean_up_meta();

			// Update the current state.
			$this->current_state = 'valid';

			return;
		}

		// No license was informed yet.
		if (
			'unlicensed' !== $this->current_state &&
			(
				in_array( $this->license_info['status'], array( 'invalid', 'pending' ), true ) ||
				( 'valid' === $this->license_info['status'] && empty( $this->license_info['is_site_activated'] ) )
			)
		) {

			// Update the current state at execution level.
			$this->current_state = 'unlicensed';

			// Update metadata.
			$this->update_state_metadata( $this->current_state );

			return;
		}

		// The license has expired.
		if ( 'invalid' !== $this->current_state && 'expired' === $this->license_info['status'] ) {

			// Update the current state at execution level.
			$this->current_state = 'invalid';

			// Update metadata.
			$this->update_state_metadata( $this->current_state );
		}
	}

	/**
	 * Return the current state for DRM.
	 * Possible values:
	 *  - valid: it means that the license is fully active.
	 *  - invalid: it means an expired license.
	 *  - unlicensed: a license was not informed yet.
	 *
	 * @since 2.21.1
	 *
	 * @return string The current state.
	 */
	private function get_current_state() : string {

		return get_option( 'affwp_drm_current_state', 'valid' );
	}

	/**
	 * Return the last time the DRM state has changed.
	 *
	 * If returns a valid timestamp, is an indication that the site is
	 * under some DRM period: initiated, low, med or locked.
	 *
	 * @since 2.21.1
	 *
	 * @return int Timestamp.
	 */
	private function get_last_changed_state_time() : int {

		return get_option( 'affwp_drm_last_changed_state_time', 0 );
	}

	/**
	 * Retrieve all UTM links.
	 *
	 * @since 2.21.1
	 *
	 * @return array UTM links.
	 */
	private function get_utm_links() : array {

		$links = require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/drm/links.php';

		if ( ! empty( $links ) ) {
			return $links;
		}

		// Fallback links.
		return array();
	}


	/**
	 * Attempt to return an specific UTM link.
	 *
	 * @since 2.21.1
	 *
	 * @param string $page The page of the link, usually: home, account, support or pricing.
	 * @param string $purpose The purpose of the link, usually: general or email.
	 *
	 * @return string The UTM link.
	 */
	public function get_utm_link( string $page, string $purpose = 'general' ) : string {

		if ( ! isset( $this->level ) ) {
			return '';
		}

		// Try to return the requested link.
		if ( isset( $this->links[ $this->level ][ $purpose ][ $page ] ) ) {
			return $this->links[ $this->level ][ $purpose ][ $page ];
		}

		// Return the fallback link if a purpose can not be found.
		if (
			! isset( $this->links[ $this->level ][ $purpose ] ) &&
			isset( $this->links[ $page ] )
		) {
			return $this->links[ $page ];
		}

		// Can't find a link for the required UTM.
		return '';
	}

	/**
	 * Retrieves the URL for the license key field with a hash to redirect directly to the corresponding section.
	 *
	 * @since 2.21.1
	 *
	 * @return string The URL for the license key field.
	 */
	public function get_license_key_field_url() : string {
		return sprintf( '%s#license_key', affwp_admin_url( 'settings' ) );
	}
}
