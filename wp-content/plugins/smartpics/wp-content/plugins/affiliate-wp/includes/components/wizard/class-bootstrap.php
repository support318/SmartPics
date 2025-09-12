<?php
/**
 * Wizard: Bootstrap
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Wizard
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

namespace AffWP\Components\Wizard;

use AffWP\Core\License;
use AffWP\Components\Addons\Installer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Class for implementing the setup wizard.
 *
 * @since 2.9
 */
class Bootstrap {

	/**
	 * AffiliateWP_Onboarding_Wizard constructor.
	 *
	 * @since 2.9
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_load_onboarding_wizard' ) );
		add_action( 'admin_menu', array( $this, 'add_dashboard_page' ) );

		// Redirect to onboarding wizard.
		add_action( 'admin_init', array( $this, 'redirect_to_wizard' ) );

		// Add wizard button to General Settings.
		add_filter( 'affwp_settings_general', array( $this, 'add_wizard_button_to_settings' ) );

		// AJAX Actions.
		add_action( 'wp_ajax_affiliatewp_vue_get_settings', array( $this, 'get_settings' ) );
		add_action( 'wp_ajax_affiliatewp_verify_license', array( $this, 'verify_license' ) );
		add_action( 'wp_ajax_affiliatewp_vue_get_license', array( $this, 'get_license' ) );
		add_action( 'wp_ajax_affiliatewp_vue_get_integrations', array( $this, 'get_integrations' ) );
		add_action( 'wp_ajax_affiliatewp_vue_get_addon_recommendations', array( $this, 'get_addon_recommendations' ) );
		add_action( 'wp_ajax_affiliatewp_vue_save_integrations', array( $this, 'save_integrations' ) );
		add_action( 'wp_ajax_affiliatewp_vue_update_settings', array( $this, 'update_setting' ) );
		add_action( 'wp_ajax_affiliatewp_vue_allset', array( $this, 'finish_wizard' ) );
		add_action( 'wp_ajax_affiliatewp_vue_install_addons', array( $this, 'install_addons' ) );
		add_action( 'wp_ajax_affiliatewp_vue_install_plugins', array( $this, 'install_growth_tools' ) );
		add_action( 'wp_ajax_affiliatewp_vue_skip_upgrade', array( $this, 'skip_upgrade' ) );


	}

	/**
	 * Checks if the Wizard should be loaded in current context.
	 *
	 * @since 2.9
	 */
	public function maybe_load_onboarding_wizard() {
		// Check for wizard-specific parameter and if current user is allowed to save settings.
		if ( ! isset( $_GET['page'] ) ||
					'affiliatewp-onboarding' !== $_GET['page'] || // WPCS: CSRF ok, input var ok.
					! current_user_can( 'manage_affiliate_options' ) ) {
			return;
		}

		// Don't load the interface if doing an ajax call.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		set_current_screen();

		// Remove an action in the Gutenberg plugin ( not core Gutenberg ) which throws an error.
		remove_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );

		$this->load_onboarding_wizard();
	}

	/**
	 * Register page through WordPress's hooks.
	 *
	 * @since 2.9
	 */
	public function add_dashboard_page() {
		add_submenu_page( '', '', '', 'manage_affiliate_options', 'affiliatewp-onboarding' );
	}

	/**
	 * Load the Onboarding Wizard template.
	 *
	 * @since 2.9
	 */
	private function load_onboarding_wizard() {
		$this->enqueue_scripts();

		$this->onboarding_wizard_header();
		$this->onboarding_wizard_content();
		$this->onboarding_wizard_footer();

		exit;
	}

	/**
	 * Redirects users to wizard if first time using wizard.
	 *
	 * @since 2.9
	 * @since 2.13.1 Redirect on activation.
	 */
	public function redirect_to_wizard() {
		// Check if we should consider redirection.
		if ( ! get_transient( '_affwp_activation_redirect' ) ) {
			return;
		}

		// If we are redirecting, clear the transient so it only happens once.
		delete_transient( '_affwp_activation_redirect' );

		if (
				/**
				 * Filters enabling the onboarding wizard on activation.
				 *
				 * @since 2.13.1
				 *
				 * @param bool $enable_onboarding_wizard Default true. False allows plugins to disable this redirect.
				 */
				apply_filters( 'affiliatewp_enable_onboarding_wizard', true ) === false ||
				! current_user_can( 'manage_affiliate_options' )
		) {
			return;
		}

		// Only do this for single site installs.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate-multi'] ) || is_network_admin() ) {
			return;
		}

		// Check if it still should be triggered.
		if (
				! get_option( 'affwp_trigger_wizard' ) ||
				false === get_option( 'affwp_trigger_wizard' )
		) {
			return;
		}

		wp_safe_redirect( menu_page_url( 'affiliatewp-onboarding', false ) );
		exit;
	}

	/**
	 * Adds wizard button to general settings.
	 *
	 * @since 2.9
	 *
	 * @param array $settings General settings.
	 * @return array General settings.
	 */
	public function add_wizard_button_to_settings( $settings = array() ) {
		$new_settings = array(
			'wizard_button' => array(
				'name'     => __( 'Setup Wizard', 'affiliate-wp' ),
				'desc'     => '',
				'type'     => 'text',
				'callback' => array( $this, 'render_wizard_button' ),
			),
		);

		return $settings + $new_settings;
	}

	/**
	 * Renders wizard button for settings.
	 *
	 * @since 2.9
	 */
	public function render_wizard_button() {
		$wizard_url = menu_page_url( 'affiliatewp-onboarding', false );
		?>
		<a href="<?php echo esc_url( $wizard_url ); ?>" class="button"><?php esc_html_e( 'Launch Setup Wizard', 'affiliate-wp' ); ?></a><br>
		<p class="description"><?php esc_html_e( 'Use our configuration wizard to properly set up AffiliateWP (with just a few clicks).', 'affiliate-wp' ); ?></p>
		<?php
	}


	/**
	 * AJAX callback to get current settings.
	 *
	 * @since 2.9
	 */
	public function get_settings() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		$setup_intent = get_option( 'affwp_setup_intent' );
		$settings     = get_option( 'affwp_settings' );

		// Paypal is recommended so intent should default to true.
		if ( ! isset( $setup_intent['intent_setup_paypal'] ) ) {
			update_option(
				'affwp_setup_intent',
				array_merge(
					is_array( $setup_intent )
						? $setup_intent
						: array(),
					array( 'intent_setup_paypal' => 1 ),
				)
			);
		}

		// Sync with settings, in case the user has changed it.
		if ( isset( $settings['paypal_payouts'] ) ) {
			$setup_intent['intent_setup_paypal'] = $settings['paypal_payouts'];
		}

		if ( isset( $settings['manual_payouts'] ) ) {
			$setup_intent['intent_manual_payouts'] = $settings['manual_payouts'];
		}

		// check lifetime commissions status.
		$lc_enabled = false;

		if ( function_exists( 'affiliate_wp_lifetime_commissions' ) ) {
			$affiliate_id         = affwp_get_affiliate_id( get_current_user_id() );
			$global_lc_enabled    = affiliate_wp()->settings->get( 'lifetime_commissions' );
			$affiliate_lc_enabled = affwp_get_affiliate_meta( $affiliate_id, 'affwp_lc_enabled', true );
			$lc_enabled           = ( $global_lc_enabled || ( ! $global_lc_enabled && $affiliate_lc_enabled ) );
		}

		wp_send_json( array(
			'enable_payouts_service' => affiliate_wp()->settings->get( 'enable_payouts_service' ),
			'intent_setup_paypal'    => isset( $setup_intent['intent_setup_paypal'] ) ? $setup_intent['intent_setup_paypal'] : 1,
			'intent_manual_payouts'  => isset( $setup_intent['intent_manual_payouts'] ) ? $setup_intent['intent_manual_payouts'] : 0,
			'currencies'             => affwp_get_currencies(),
			'currency'               => affiliate_wp()->settings->get( 'currency', 'USD' ),
			'referral_rate'          => affiliate_wp()->settings->get( 'referral_rate', 20 ),
			'referral_rate_type'     => affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' ),
			'flat_rate_basis'        => affiliate_wp()->settings->get( 'flat_rate_basis', 'per_product' ),
			'lifetime_commissions'   => $lc_enabled ? 1 : 0,
		) );
	}

	/**
	 * AJAX callback to verify if license is valid.
	 *
	 * @since 2.9
	 * @since 2.16.0 Moved check for empty license to allow clearing of license key.
	 */
	public function verify_license() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		$license_key = ! empty( $_POST['license'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['license'] ) ) ) : false;

		// Check if user has permissions.
		if ( ! current_user_can( 'manage_affiliate_options' ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'You are not allowed to verify a license key.', 'affiliate-wp' ),
				)
			);
		}

		// Activate license.
		$license = ( new License\License_Data() );
		$license_activation = $license->activation_status( $license_key );

		// Check if activation request has failed.
		if ( is_null( $license_activation ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'There was an error connecting to the remote key API. Please try again later.', 'affiliate-wp' ),
				)
			);
		}

		if ( false === $license_activation['license_status'] ) {
			wp_send_json_error(
				array(
					'error' => $license_activation['affwp_message'],
				)
			);
		}

		$license_data = $license_activation['license_data'];

		if (
			(
				! isset( $license_data->license ) ||
				'valid' !== $license_data->license
			) ||
			empty( $license_data->success )
		) {
			// Check if user entered a license key.
			if ( ! $license_key ) {
				wp_send_json_error(
					array(
						'error' => esc_html__( 'Please enter a license key.', 'affiliate-wp' ),
					)
				);
			}

			// If license is expired, show error with a link to downloads page to renew.
			if ( 'expired' === $license_data->license ) {
				wp_send_json_error(
					array(
						'error' => sprintf(
							wp_kses( /* translators: 1: License has expired error, 2: Renew your license to continue. 3: AffiliateWP.com downloads page URL */
								__( '%1$s <a href="%3$s">%2$s</a>', 'affiliate-wp' ),
								array(
									'a' => array(
										'href' => true,
									),
								)
							),
							__( 'This license key has expired.', 'affiliate-wp' ),
							__( 'Renew your license to continue.', 'affiliate-wp' ),
							esc_url( 'https://affiliatewp.com/account/downloads/?utm_source=WordPress&utm_medium=onboardingwizard&utm_campaign=plugin&utm_content=renew%20your%20license' )
						),
					)
				);
			}
			// Otherwise, show generic error message.
			wp_send_json_error(
				array(
					'error' => __( 'This license key doesn&#8217;t appear to be valid. Try again?', 'affiliate-wp' ),
				)
			);
		}

		if ( is_object( $license_data ) && isset( $license_data->expires ) ) {
			$expires_on  = $license_data->expires === 'lifetime' ?
				'lifetime' :
				date('m/d/Y', $license_data->expires );
		}

		$price_id =  isset( $license_data->price_id ) ? intval( $license_data->price_id ) : false;

		wp_send_json_success(
			array(
				'message'      => __( 'Congratulations! This site is now receiving automatic updates.', 'affiliate-wp' ),
				'license_type' => $price_id === false ? '' : $license->get_license_type( $price_id ),
				'site'    => array(
					'key'        => $license_key,
					'status'     => $license_data->license,
					'is_invalid' => 'valid' !== $license_data->license,
					'type'       => $license->get_license_type( $price_id ),
					'price_id'   => $price_id,
					'expires_on' => isset( $expires_on ) ? $expires_on : false,
				),
				'network' => array(),
				)
		);
	}

	/**
	 * AJAX callback to get license details.
	 *
	 * @since 2.9
	 * @since 2.16.0 Improved UX by automatically activating a valid license key from the settings.
	 */
	public function get_license() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		$license = new License\License_Data();

		// Attempt to get data from the settings.
		$license_data = affiliate_wp()->settings->get( 'license_status', '' );

		$license_key = $license->get_license_key();

		$status = ( is_object( $license_data ) && isset( $license_data->license ) )
			? $license_data->license
			: $license->check_status();

		// If no license data but a valid key, activate this previously deactivated license for better UX.
		if ( empty( $license_data ) && ! empty( $license_key ) && 'valid' === $status ) {
			$license_data = $license->activation_status( $license_key )['license_data'];
		}

		if ( is_object( $license_data ) && isset( $license_data->expires ) ) {
			$expires_on  = $license_data->expires === 'lifetime' ?
				'lifetime' :
				date('m/d/Y', $license_data->expires );
		}

		$price_id = isset( $license_data->price_id ) ? intval( $license_data->price_id ) : false;

		wp_send_json( array(
			'site'    => array(
				'key'        => $license_key,
				'status'     => $status,
				'is_invalid' => 'valid' !== $status,
				'type'       => $license->get_license_type( $price_id ),
				'price_id'   => $price_id,
				'expires_on' => isset( $expires_on ) ? $expires_on : false,
			),
			'network' => array(),
		) );
	}

	/**
	 * Check for the conditions to display the Multi-Currency recommendations.
	 *
	 * First checks against some compatible multi-currency plugins, if they are active,
	 * this can indicate intention of using multi-currency solutions on this site.
	 * Then, if any of the supported integrations are active, check if they are using a
	 * different currency than AffiliateWP, which is also one of the use-cases for the Multi-Currency addon.
	 *
	 * @since 2.26.1
	 * @return bool Whether the Wizard should recommend or not.
	 */
	private function should_recommend_multi_currency_addon() : bool {
		// Check if FOX - Currency Switcher Professional for WooCommerce plugin is active.
		if ( class_exists( 'WOOCS_STARTER' ) ) {
			return true;
		}

		// Check if CURCY - Multi Currency for WooCommerce plugin is active.
		if ( class_exists( 'WOOMULTI_CURRENCY_F' ) ) {
			return true;
		}

		// Check if WooCommerce and AffiliateWP uses different currencies.
		if ( function_exists( 'get_woocommerce_currency' ) && get_woocommerce_currency() !== affwp_get_currency() ) {
			return true;
		}

		// Check if EDD and AffiliateWP uses different currencies.
		if ( function_exists( 'edd_get_currency' ) && edd_get_currency() !== affwp_get_currency() ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the site fulfills conditions to display addons recommendations.
	 *
	 * To add a new condition, add a new key representing the addon name and a method to
	 * return a boolean indicating the recommendation.
	 *
	 * @since 2.26.1
	 * @return void
	 */
	public function get_addon_recommendations() : void {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		wp_send_json(
			[
				'multi_currency' => $this->should_recommend_multi_currency_addon(),
			],
		);
		wp_die();
	}

	/**
	 * Get current list of AffiliateWP integrations.
	 *
	 * @since 2.9
	 */
	public function get_integrations() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		// Integrations by category.
		$integrations_categories = array(
			'eCommerce'  => array(
				'woocommerce',
				'edd',
				'stripe',
				'paypal',
				'wpeasycart',
			),
			'Membership' => array(
				'membermouse',
				'memberpress',
				'pmp',
				'pms',
				'rcp',
			),
			'Form'       => array(
				'contactform7',
				'formidablepro',
				'gravityforms',
				'ninja-forms',
				'wpforms',
			),
			'Invoice'    => array(
				'sproutinvoices',
			),
			'Course'     => array(
				'learndash',
				'lifterlms',
			),
			'Donation'   => array(
				'give',
			),
		);

		// Get enabled integrations.
		$all_integrations     = affiliate_wp()->integrations->get_integrations();
		$enabled_integrations = affiliate_wp()->integrations->get_enabled_integrations();
		$enabled_keys         = array_keys( $enabled_integrations );

		// Show recommendations if there are any active integration-related plugins detected.
		$active_recommendations = false;

		// Build integrations array with all details.
		$integrations = array();

		foreach ( $all_integrations as $integration => $title ) {

			// Skip PayPal since it doesn't require a plugin.
			if ( 'paypal' === $integration ) {
				continue;
			}

			// Recommend integrations with active plugins.
			$recommended = ! empty( $integration ) ? affiliate_wp()->integrations->get( $integration )->plugin_is_active() : false;

			// Set category.
			foreach( $integrations_categories as $category => $list ) {
				$set_category = '';
				if ( in_array( $integration, $list, true ) ) {
					$set_category = $category;
					break;
				}
			}

			$integrations[ $integration ] = array(
				'feature'     => $integration,
				'title'       => isset( $title ) ? $title : '',
				'checked'     => in_array( $integration, $enabled_keys, true),
				'description' => '',
				'faux'        => ! $recommended ? true : false,
				'recommend'   => $recommended,
				'category'    => $set_category,
				// Add tooltip to any integrations that are not recommended or the default recomendations.
				'tooltip'     => ! $recommended
					? sprintf( esc_html( 'The %s plugin was not detected. Once installed and activated, %s can be integrated with AffiliateWP.', 'affiliate-wp' ),
						$title,
						$title
					)
					: '',
			);

			// No need to update if already true or the this integration isn't recommended.
			if ( true === $active_recommendations || false === $recommended ) {
				continue;
			}
			$active_recommendations = true;
		}

		// Determines integration step view.
		$integrations['show_recommended'] = $active_recommendations;

		wp_send_json( $integrations );
	}

	/**
	 * AJAX callback to save selected integrations.
	 *
	 * @since 2.9
	 */
	public function save_integrations() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		// List of integrations that don't require a plugin.
		$no_plugin_required = array( 'paypal' );

		// Build array of enabled integrations.
		$integrations     = affiliate_wp()->integrations->get_integrations();
		$current_enabled  = affiliate_wp()->integrations->get_enabled_integrations();
		$wizard_enabled   = isset( $_POST['integrations'] ) ? explode( ',', $_POST['integrations'] ) : array();
		$new_integrations = array_filter(
			$integrations,
			function( $integration ) use ( $no_plugin_required, $current_enabled, $wizard_enabled ) {
				// If it doesn't require an active plugin, don't override the current settings.
				if ( in_array( $integration, $no_plugin_required, true ) ){
					return in_array( $integration, array_keys( $current_enabled ), true );
				}
				// Otherwise, we update from the Wizard settings.
				return in_array( $integration, $wizard_enabled, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		// Add selected integrations into settings.
		$settings = affiliate_wp()->settings->get_all();
		if ( is_array( $settings ) && isset( $new_integrations ) ) {
			$settings['integrations'] = $new_integrations;

			// Update settings.
			update_option( 'affwp_settings', $settings );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX callback to update selected setting or intent.
	 *
	 * @since 2.9
	 */
	public function update_setting() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		// Get POST vars.
		$setting = isset( $_POST['setting'] ) ? sanitize_text_field( $_POST['setting'] ) : '';

		$settings = affiliate_wp()->settings->get_all();

		// TO DO: Maybe separate this to separate function which would also require a new Vue checkbox template. Customers don't need to know difference between setting or intention but might be simpler code to have it separate. The question is: will there be any settings that we save the intention as well?
		$setup_intent = get_option( 'affwp_setup_intent' );

		// Use to note updated intentions.
		$updated_intent = array();

		// Payouts Step
		if ( 'enable_payouts_service' === $setting ) {
			$settings['enable_payouts_service'] = isset( $_POST['value'] ) && '1' === sanitize_text_field( $_POST['value'] ) ? 1 : 0;
		}

		// Update the intention to setup PayPal Payouts for later use on the setup screen.
		if ( 'intent_setup_paypal' === $setting ) {
			$updated_intent['intent_setup_paypal'] = isset( $_POST['value'] ) && '1' === sanitize_text_field( $_POST['value'] ) ? 1 : 0;
			$settings['paypal_payouts'] = $updated_intent['intent_setup_paypal'];
		}

		// Update the intention to pay manually for later use on the setup screen.
		if ( 'intent_manual_payouts' === $setting ) {
			$updated_intent['intent_manual_payouts'] = isset( $_POST['value'] ) && '1' === sanitize_text_field( $_POST['value'] ) ? 1 : 0;
			$settings['manual_payouts'] = $updated_intent['intent_manual_payouts'];
		}

		// Commissions and Growth Tools Step
		if ( 'currency' === $setting ) {
			$settings['currency'] = sanitize_text_field( $_POST['value'] );
		}
		if ( 'referral_rate' === $setting ) {
			$settings['referral_rate'] = floatval( $_POST['value'] );
		}
		if ( 'referral_rate_type' === $setting ) {
			$settings['referral_rate_type'] = sanitize_text_field( $_POST['value'] );
		}
		if ( 'flat_rate_basis' === $setting ) {
			$settings['flat_rate_basis'] = sanitize_text_field( $_POST['value'] );
		}

		// Update settings.
		update_option( 'affwp_settings', $settings );

		// Update setup intentions.
		update_option(
			'affwp_setup_intent',
			array_merge(
				is_array( $setup_intent )
					? $setup_intent
					: array(),
				$updated_intent,
			)
		);

		wp_send_json_success();
	}

	/**
	 * Ajax endpoint for the final step of the wizard.
	 *
	 * @since 2.9
	 */
	public function finish_wizard() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		// Mark the wizard completed.
		update_option( 'affwp_has_run_wizard', 1 );
		update_option( 'affwp_trigger_wizard', false );

		// Send JSON response.
		wp_send_json_success();
	}

	/**
	 * Ajax endpoint for installing addons.
	 *
	 * @since 2.13.0
	 */
	public function install_addons() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		$response = array(
			'success' => false,
			'error'   => '',
		);

		$enabled_addons  = isset( $_POST['addons'] ) ? explode( ',', $_POST['addons'] ) : array();

		if ( empty( $enabled_addons ) ) {
			// Nothing to install.
			wp_send_json_success();
		}

		// Refresh license.
		( new License\License_Data() )->check_status( true );

		// Check license.
		$license_data    = affiliate_wp()->settings->get( 'license_status', '' );
		$license_status  = is_object( $license_data ) ? $license_data->license : $license_data;
		$price_id        = isset( $license_data->price_id ) ? intval( $license_data->price_id ) : false;
		$minimum_license = count( $enabled_addons ) === 1 && in_array( 'multi_currency', $enabled_addons, true )
			? 1 // If only Multi-Currency was selected, a Plus license is required.
			: 2; // For any other cases, Pro license is required.

		if ( 'valid' !== $license_status || $price_id < $minimum_license ) {
			$response['success']         = false;
			$response['minimum_license'] = $minimum_license === 1 ? 'plus' : 'pro';
			$response['error']           = sprintf(
				/* translators: The license level. */
				__( 'You need a %s license to install Advanced features.', 'affiliate-wp' ),
				$minimum_license === 1 ? 'Plus' : 'Professional'
			);
			wp_send_json( $response );
		}

		if ( in_array( 'multi_tier_commissions', $enabled_addons, true ) ) {

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = activate_plugin( affiliate_wp()->settings->addons['multi_tier_commissions']['path'] );

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_addon( affiliate_wp()->settings->addons['multi_tier_commissions']['id'] );

				if ( ! $status['success'] ) {
					$response['success'] = false;
					$response['error']   = $status['error'];
					wp_send_json( $response );
				}
			}
		}

		if ( in_array( 'multi_currency', $enabled_addons, true ) ) {

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = activate_plugin( affiliate_wp()->settings->addons['multi_currency']['path'] );

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_addon( affiliate_wp()->settings->addons['multi_currency']['id'] );

				if ( ! $status['success'] ) {
					$response['success'] = false;
					$response['error']   = $status['error'];
					wp_send_json( $response );
				}
			}
		}

		if ( in_array( 'lifetime_commissions', $enabled_addons, true ) ) {

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = activate_plugin( 'affiliate-wp-lifetime-commissions/affiliate-wp-lifetime-commissions.php' );

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_addon( 6956 );

				if ( ! $status['success'] ) {
					$response['success'] = false;
					$response['error']   = $status['error'];
					wp_send_json( $response );
				}
			}

			// Enable LC for all affiliates.
			if ( function_exists( 'affiliate_wp_lifetime_commissions' ) ) {
				$affiliate_id         = affwp_get_affiliate_id( get_current_user_id() );
				$global_lc_enabled    = affiliate_wp()->settings->get( 'lifetime_commissions' );
				$affiliate_lc_enabled = affwp_get_affiliate_meta( $affiliate_id, 'affwp_lc_enabled', true );
				$lc_enabled           = ( $global_lc_enabled || ( ! $global_lc_enabled && $affiliate_lc_enabled ) );

				if ( ! $lc_enabled ) {
					$settings                         = affiliate_wp()->settings->get_all();
					$settings['lifetime_commissions'] = 1;
					update_option( 'affwp_settings', $settings );
				}
			}
		}

		if ( in_array( 'recurring_referrals', $enabled_addons, true ) ) {

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = activate_plugin( 'affiliate-wp-recurring-referrals/affiliate-wp-recurring-referrals.php' );

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_addon( 1670 );

				if ( ! $status['success'] ) {
					$response['success'] = false;
					$response['error']   = $status['error'];
					wp_send_json( $response );
				}
			}
		}

		// Send JSON response.
		wp_send_json_success();
	}

	/**
	 * Ajax endpoint for installing free versions of recommended plugins.
	 *
	 * @since 2.13.0
	 * @param array $install_list List of plugins to install.
	 */
	public function install_growth_tools() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		$install_list  = isset( $_POST['plugins'] ) ? explode( ',', $_POST['plugins'] ) : array();

		if ( empty( $install_list ) || ! is_array( $install_list ) ) {
			// Nothing to install or missing install growth tools checkbox permission.
			wp_send_json_success();
		}

		// Use to note any failed growth tool installations.
		$failed_install = array();

		// Maybe install & activate MonsterInsights lite version.
		if ( in_array( 'monster_insights', $install_list, true ) ) {

			// Check if installed and active (lite or pro version).
			$is_active = function_exists( 'MonsterInsights' ) && ( is_plugin_active( 'google-analytics-for-wordpress/googleanalytics.php' ) || is_plugin_active( 'google-analytics-premium/googleanalytics-premium.php' ) );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = false === $is_active ? activate_plugin( 'google-analytics-for-wordpress/googleanalytics.php' ) : $is_active;

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_plugin( 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.8.13.0.zip' );

				if ( ! $status['success'] ) {

					// Intent failed.
					$failed_install['affwp_growth_tool_analytics_failed'] = 1;
				}
			}

			// Prevent welcome/onboarding redirect after activation.
			delete_transient( '_monsterinsights_activation_redirect' );
		}

		// Maybe install & activate Trust Pulse plugin.
		if ( in_array( 'trust_pulse', $install_list ) ) {

			// Check if installed and active.
			$is_active = class_exists( 'TPAPI' ) && is_plugin_active( 'trustpulse-api/trustpulse.php' );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = false === $is_active ? activate_plugin( 'trustpulse-api/trustpulse.php' ) : $is_active;

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_plugin( 'https://downloads.wordpress.org/plugin/trustpulse-api.1.0.7.zip' );

				if ( ! $status['success'] ) {

					// Intent failed.
					$failed_install['affwp_growth_tool_social_proof_failed'] = 1;
				}
			}

			// Prevent welcome/onboarding redirect after activation.
			delete_option( 'trustpulse_api_plugin_do_activation_redirect' );
		}

		// Maybe install & activate AIOSEO lite version.
		if ( in_array( 'aioseo', $install_list ) ) {

			// Check if installed and active (lite or pro version).
			$is_active = function_exists( 'aioseo' ) && ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) || is_plugin_active( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' ) );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = false === $is_active ? activate_plugin( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) : $is_active;

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_plugin( 'https://downloads.wordpress.org/plugin/all-in-one-seo-pack.4.3.3.zip' );

				if ( ! $status['success'] ) {

					// Intent failed.
					$failed_install['affwp_growth_tool_seo_failed'] = 1;
				}
			}

			// Prevent welcome/onboarding redirect after activation.
			update_option( 'aioseo_activation_redirect', true );
		}

		// Maybe install & activate SeedProd lite version.
		if ( in_array( 'seedprod', $install_list ) ) {

			// Check if installed and active (lite or pro version).
			$is_active = ( function_exists( 'seedprod_lite_activation' ) || function_exists( 'seedprod_pro_activation' ) ) && ( is_plugin_active( 'coming-soon/coming-soon.php' ) || is_plugin_active( 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php' ) );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = false === $is_active ? activate_plugin( 'coming-soon/coming-soon.php' ) : $is_active;

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_plugin( 'https://downloads.wordpress.org/plugin/coming-soon.6.15.6.zip' );

				if ( ! $status['success'] ) {
					// Intent failed.
					$failed_install['affwp_growth_tool_landing_pages_failed'] = 1;
				}
			}
		}

		// Maybe install & activate WP Mail SMTP lite version.
		if ( in_array( 'wp_mail_smtp', $install_list ) ) {

			// Check if installed and active (lite or pro version).
			$is_active = function_exists( 'wp_mail_smtp' ) && ( is_plugin_active( 'wp-mail-smtp/wp_mail_smtp.php' ) || is_plugin_active( 'wp-mail-smtp-pro/wp_mail_smtp.php' ) );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = false === $is_active ? activate_plugin( 'wp-mail-smtp/wp_mail_smtp.php' ) : $is_active;

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_plugin( 'https://downloads.wordpress.org/plugin/wp-mail-smtp.3.7.0.zip' );

				if ( ! $status['success'] ) {
					// Intent failed.
					$failed_install['affwp_growth_tool_mail_smtp_failed'] = 1;
				}
			}
		}

		// Maybe install & activate Uncanny Automator lite version.
		if ( in_array( 'uncanny_automator', $install_list ) ) {

			// Check if installed and active (lite or pro version).
			$is_active = ( function_exists( 'Automator' ) || function_exists( 'Automator_Pro' ) ) && ( is_plugin_active( 'uncanny-automator/uncanny-automator.php' ) || is_plugin_active( 'uncanny-automator-pro/uncanny-automator-pro.php' ) );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = false === $is_active ? activate_plugin( 'uncanny-automator/uncanny-automator.php' ) : $is_active;

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_plugin( 'https://downloads.wordpress.org/plugin/uncanny-automator.4.12.0.1.zip' );

				if ( ! $status['success'] ) {
					// Intent failed.
					$failed_install['affwp_growth_tool_automator_failed'] = 1;
				}
			}
		}

		// Maybe install & activate OptinMonster plugin.
		if ( in_array( 'optin_monster', $install_list ) ) {

			// Check if installed and active.
			$is_active = function_exists( 'optin_monster' ) && is_plugin_active( 'optinmonster/optin-monster-wp-api.php' );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				exit;
			}

			// Try first to activate.
			$status = false === $is_active ? activate_plugin( 'optinmonster/optin-monster-wp-api.php' ) : $is_active;

			if ( is_wp_error( $status ) && current_user_can( 'install_plugins' ) ) {

				// Install plugin.
				$installer = new Installer();
				$status    = $installer->install_plugin( 'https://downloads.wordpress.org/plugin/optinmonster.2.13.0.zip' );

				if ( ! $status['success'] ) {
					// Intent failed.
					$failed_install['affwp_growth_tool_optin_failed'] = 1;
				}
			}

			// Prevent welcome/onboarding redirect after activation.
			delete_transient( 'optin_monster_api_activation_redirect' );
		}

		// Check if anything failed.
		if ( ! empty( $failed_install ) ) {

			// Get setup intent option.
			$setup_intent = get_option( 'affwp_setup_intent' );

			// Update so we know what failed to install.
			update_option(
				'affwp_setup_intent',
				array_merge(
					is_array( $setup_intent )
						? $setup_intent
						: array(),
					$failed_install,
				)
			);
		}

		// Send JSON response.
		wp_send_json_success();
	}

	/**
	 * Ajax endpoint for skipping upgrade step.
	 *
	 * @since 2.13.0
	 */
	public function skip_upgrade() {
		check_ajax_referer( 'affwpwizard-admin-nonce', 'nonce' );

		// Get setup intent option.
		$setup_intent = get_option( 'affwp_setup_intent' );

		// Update that option with intention to skip.
		update_option(
			'affwp_setup_intent',
			array_merge(
				is_array( $setup_intent )
					? $setup_intent
					: array(),
				array( 'affwp_user_skipped_upgrade' => 1 ),
			)
		);

		// Send JSON response.
		wp_send_json_success();
	}

	/**
	 * Load the scripts needed for the Onboarding Wizard.
	 *
	 * @since 2.9
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'affwpwizard-vue-style', plugins_url( '/assets/vue/wizard/dist/css/wizard.css', AFFILIATEWP_PLUGIN_FILE ), array(), AFFILIATEWP_VERSION );
		wp_register_script( 'affwpwizard-vue-script', plugins_url( '/assets/vue/wizard/dist/js/wizard.js', AFFILIATEWP_PLUGIN_FILE ), array(), AFFILIATEWP_VERSION, true );
		wp_enqueue_script( 'affwpwizard-vue-script' );

		wp_localize_script(
			'affwpwizard-vue-script',
			'affwpwizard',
			array(
				'ajax'                => add_query_arg( 'page', 'affiliatewp-onboarding', admin_url( 'admin-ajax.php' ) ),
				'nonce'               => wp_create_nonce( 'affwpwizard-admin-nonce' ),
				'affwpAdminUrl'       => affwp_admin_url(),
				'network'             => is_network_admin(),
				'translations'        => $this->get_jed_locale_data( 'mi-vue-app' ),
				'assets'              => plugins_url( '/assets/vue/wizard', AFFILIATEWP_PLUGIN_FILE ),
				'wizard_url'          => is_network_admin() ? network_admin_url( 'index.php?page=affiliatewp-onboarding' ) : admin_url( 'index.php?page=affiliatewp-onboarding' ),
				'exit_url'            => affwp_admin_url(),
				'setup_url'           => get_option( 'affwp_display_setup_screen' ) ? affwp_admin_url( 'setup-screen' ) : affwp_admin_url(),
				'plugin_version'      => AFFILIATEWP_VERSION,
				'site_url'            => get_site_url(),
				'logo'                => AFFILIATEWP_PLUGIN_URL . 'assets/images/affiliatewp-1.svg',
				'compatWarningTitle'  => __( 'No Compatible Plugins Detected', 'affiliate-wp'),
				'compatWarningDesc'   => __( 'We recommend integrating AffiliateWP with one of the plugins below.', 'affiliate-wp' ),
				'compatTitle'         => __( 'Compatible Plugins', 'affiliate-wp' ),
				'errorLicenseExpired' => sprintf(
					wp_kses( /* translators: 1: License has expired error, 2: Renew your license to continue. 3: AffiliateWP.com downloads page URL */
						__( '%1$s <a href="%3$s">%2$s</a>', 'affiliate-wp' ),
						array(
							'a' => array(
								'href' => true,
							),
						)
					),
					__( 'This license key has expired.', 'affiliate-wp' ),
					__( 'Renew your license to continue.', 'affiliate-wp' ),
					esc_url( 'https://affiliatewp.com/account/downloads/?utm_source=WordPress&utm_medium=onboardingwizard&utm_campaign=plugin&utm_content=renew%20your%20license' )
				),
			)
		);

	}

	/**
	 * Outputs the simplified header used for the Onboarding Wizard.
	 *
	 * @since 2.9
	 */
	public function onboarding_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php esc_html_e( 'AffiliateWP &rsaquo; Onboarding Wizard', 'affiliate-wp' ); ?></title>
			<?php @do_action( 'admin_print_styles' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Suppress notices caused by these. ?>
			<?php @do_action( 'admin_print_scripts' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Suppress notices caused by these. ?>
			<?php @do_action( 'admin_head' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Suppress notices caused by these. ?>
		</head>
		<body class="affwpwizard-onboarding">
		<?php
	}

	/**
	 * Outputs the content of the current step.
	 *
	 * @since 2.9
	 */
	public function onboarding_wizard_content() {
		$admin_url = is_network_admin() ? network_admin_url() : admin_url();

		$this->error_page( 'affwpwizard-vue-onboarding-wizard', '<a href="' . $admin_url . '">' . esc_html__( 'Return to Dashboard', 'affiliate-wp' ) . '</a>' );
		$this->inline_js();
	}

	/**
	 * Outputs the simplified footer used for the Onboarding Wizard.
	 *
	 * @since 2.9
	 */
	public function onboarding_wizard_footer() {
		?>
		<?php wp_print_scripts( 'affwpwizard-vue-script' ); ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Error page HTML.
	 *
	 * @since 2.9
	 *
	 * @param string $id Page div ID.
	 * @param string $footer Additional HTML code for footer.
	 * @param string $margin Margin for error div.
	 **/
	public function error_page( $id = 'affwpwizard-vue-onboarding-wizard', $footer = '', $margin = '82px 0' ) {
		$logo_image = AFFILIATEWP_PLUGIN_URL . 'assets/images/affwp-onboarding-logo.png';
	?>
	<style type="text/css">
			#affwpwizard-settings-area {
				visibility: hidden;
				animation: loadAffiliateWPSettingsNoJSView 0s 2s forwards;
			}

			@keyframes loadAffiliateWPSettingsNoJSView{
				to   { visibility: visible; }
			}
	</style>
	<!--[if IE]>
			<style>
					#affwpwizard-settings-area{
							visibility: visible !important;
					}
			</style>
	<![endif]-->
	<div id="<?php echo $id; ?>">
			<div id="affwpwizard-settings-area" class="affwpwizard-settings-area mi-container" style="font-family:'Helvetica Neue', 'HelveticaNeue-Light', 'Helvetica Neue Light', Helvetica, Arial, 'Lucida Grande', sans-serif;margin: auto;width: 750px;max-width: 100%;">
					<div id="affwpwizard-settings-error-loading-area">
							<div class="" style="text-align: center; background-color: #fff;border: 1px solid #D6E2EC; padding: 15px 50px 30px; color: #777777; margin: <?php echo esc_attr( $margin ); ?>">
									<div class="" style="border-bottom: 0;padding: 5px 20px 0;">
											<img class="" src="<?php echo esc_attr( $logo_image ); ?>" alt="" style="max-width: 100%;width: 240px;padding: 30px 0 15px;">
									</div>
									<div id="affwpwizard-error-js">
											<h3 class="" style="font-size: 20px;color: #434343;font-weight: 500;line-height:1.4;"><?php esc_html_e( 'Ooops! It Appears JavaScript Didn’t Load', 'affiliate-wp' ); ?></h3>
											<p class="info" style="line-height: 1.5;margin: 1em 0;font-size: 16px;color: #434343;padding: 5px 20px 20px;"><?php esc_html_e( 'There seems to be an issue running JavaScript on your website, which AffiliateWP is crafted in to give you the best experience possible.', 'affiliate-wp' ); ?></p>
					<p class="info"style="line-height: 1.5;margin: 1em 0;font-size: 16px;color: #434343;padding: 5px 20px 20px;">
						<?php
						// Translators: Placeholders make the text bold.
						printf( esc_html__( 'If you are using an %1$sad blocker%2$s, please disable or whitelist the current page to load AffiliateWP correctly.', 'affiliate-wp' ), '<strong>', '</strong>' );
						?>
					</p>
											<div style="display: none" id="affwpwizard-nojs-error-message">
													<div class="" style="  border: 1px solid #E75066;
																															border-left: 3px solid #E75066;
																															background-color: #FEF8F9;
																															color: #E75066;
																															font-size: 14px;
																															padding: 18px 18px 18px 21px;
																															font-weight: 300;
																															text-align: left;">
															<strong style="font-weight: 500;" id="affwpwizard-alert-message"></strong>
													</div>
													<p class="" style="font-size: 14px;color: #777777;padding-bottom: 15px;"><?php esc_html_e( 'Copy the error message above and paste it in a message to the AffiliateWP support team.', 'affiliate-wp' ); ?></p>
											</div>
									</div>
									<div id="affwpwizard-error-browser" style="display: none">
											<h3 class="" style="font-size: 20px;color: #434343;font-weight: 500;"><?php esc_html_e( 'Your browser version is not supported', 'affiliate-wp' ); ?></h3>
											<p class="info" style="line-height: 1.5;margin: 1em 0;font-size: 16px;color: #434343;padding: 5px 20px 20px;"><?php esc_html_e( 'You are using a browser which is no longer supported by AffiliateWP. Please update or use another browser in order to access the plugin settings.', 'affiliate-wp' ); ?></p>
											<a href="https://www.monsterinsights.com/docs/browser-support-policy/" target="_blank" style="margin-left: auto;background-color: #54A0E0;border-color: #3380BC;border-bottom-width: 2px;color: #fff;border-radius: 3px;font-weight: 500;transition: all 0.1s ease-in-out;transition-duration: 0.2s;padding: 14px 35px;font-size: 16px;margin-top: 10px;margin-bottom: 20px; text-decoration: none; display: inline-block;">
													<?php esc_html_e( 'View supported browsers', 'affiliate-wp' ); ?>
											</a>
									</div>
							</div>
					</div>
		<div style="text-align: center;">
			<?php echo wp_kses_post( $footer ); ?>
		</div>
			</div>
	</div>
		<?php
	}

	/**
	 * Attempt to catch the js error preventing the Vue app from loading and displaying that message for better support.
	 *
	 * @since 2.9
	 */
	public function inline_js() {
		?>
		<script type="text/javascript">
			var ua = window.navigator.userAgent;
			var msie = ua.indexOf( 'MSIE ' );
			if ( msie > 0 ) {
				var browser_error = document.getElementById( 'affwpwizard-error-browser' );
				var js_error = document.getElementById( 'affwpwizard-error-js' );
				js_error.style.display = 'none';
				browser_error.style.display = 'block';
			} else {
				window.onerror = function myErrorHandler( errorMsg, url, lineNumber ) {
									/* Don't try to put error in container that no longer exists post-vue loading */
					var message_container = document.getElementById( 'affwpwizard-nojs-error-message' );
									if ( ! message_container ) {
											return false;
									}
					var message = document.getElementById( 'affwpwizard-alert-message' );
					message.innerHTML = errorMsg;
					message_container.style.display = 'block';
					return false;
				}
			}
		</script>
		<?php
	}

	/**
	 * Returns Jed-formatted localization data. Added for backwards-compatibility.
	 *
	 * @since 2.9
	 *
	 * @param  string $domain Translation domain.
	 * @return array Array of Jed-formatted localization data.
	 */
	public function get_jed_locale_data( $domain ) {
		$translations = get_translations_for_domain( $domain );

		$locale = array(
			'' => array(
				'domain' => $domain,
				'lang'   => is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
			),
		);

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			$locale[ $msgid ] = $entry->translations;
		}

		return $locale;
	}

}
