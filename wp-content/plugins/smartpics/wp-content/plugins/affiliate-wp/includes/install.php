<?php
/**
 * Installation Bootstrap
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

use AffiliateWP\Installation_Tools;
use AffWP\Components\Notifications\Notifications_DB;

/**
 * Installs AffiliateWP.
 *
 * @since 0.1
 * @since 2.14.0 Included support for custom_links features.
 * @since 2.20.1 Updated to properly add rewrite and flush them in order, see https://github.com/awesomemotive/affiliate-wp/issues/5023
 */
function affiliate_wp_install() {

	// Create affiliate caps
	$roles = new Affiliate_WP_Capabilities;
	$roles->add_caps();

	$affiliate_wp_install                 = new stdClass();
	$affiliate_wp_install->affiliates     = new Affiliate_WP_DB_Affiliates;
	$affiliate_wp_install->affiliate_meta = new Affiliate_WP_Affiliate_Meta_DB;
	$affiliate_wp_install->customers      = new Affiliate_WP_Customers_DB;
	$affiliate_wp_install->customer_meta  = new Affiliate_WP_Customer_Meta_DB;
	$affiliate_wp_install->referrals      = new Affiliate_WP_Referrals_DB;
	$affiliate_wp_install->referral_meta  = new Affiliate_WP_Referral_Meta_DB;
	$affiliate_wp_install->visits         = new Affiliate_WP_Visits_DB;
	$affiliate_wp_install->campaigns      = new Affiliate_WP_Campaigns_DB;
	$affiliate_wp_install->creatives      = new Affiliate_WP_Creatives_DB;
	$affiliate_wp_install->creative_meta  = new AffiliateWP\Creatives\Meta\DB();
	$affiliate_wp_install->sales          = new Affiliate_WP_Sales_DB;
	$affiliate_wp_install->settings       = new Affiliate_WP_Settings;
	$affiliate_wp_install->rewrites       = new Affiliate_WP_Rewrites;
	$affiliate_wp_install->REST           = new Affiliate_WP_REST;
	$affiliate_wp_install->notifications  = new Notifications_DB;
	$affiliate_wp_install->custom_links   = new Affiliate_WP_Custom_Links_DB();

	$affiliate_wp_install->affiliates->create_table();
	$affiliate_wp_install->affiliate_meta->create_table();
	$affiliate_wp_install->customers->create_table();
	$affiliate_wp_install->customer_meta->create_table();
	$affiliate_wp_install->referrals->create_table();
	$affiliate_wp_install->referral_meta->create_table();
	$affiliate_wp_install->visits->create_table();
	$affiliate_wp_install->campaigns->create_table();
	$affiliate_wp_install->creatives->create_table();
	$affiliate_wp_install->creative_meta->create_table();
	$affiliate_wp_install->sales->create_table();
	$affiliate_wp_install->affiliates->payouts->create_table();
	$affiliate_wp_install->affiliates->coupons->create_table();
	$affiliate_wp_install->REST->consumers->create_table();
	$affiliate_wp_install->notifications->create_table();
	$affiliate_wp_install->custom_links->create_table();

	if ( ! get_option( 'affwp_is_installed' ) ) {

		// Update settings.
		$affiliate_wp_install->settings->set(
			[
				'affiliates_page'              => Installation_Tools::get_instance()->create_affiliate_area_page(false, false ),
				'affiliates_login_page'        => Installation_Tools::get_instance()->create_login_page( false, false ),
				'affiliates_registration_page' => Installation_Tools::get_instance()->create_registration_page( false, false ),
				'require_approval'             => true,
				'allow_affiliate_registration' => true,
				'revoke_on_refund'             => true,
				'referral_pretty_urls'         => true,
				'enable_payouts_service'       => 1,
				'commission_holding_period'    => 0,
				'required_registration_fields' => array(
					'your_name'   => __( 'Your Name', 'affiliate-wp' ),
					'website_url' => __( 'Website URL', 'affiliate-wp' ),
				),
				'email_notifications'          => $affiliate_wp_install->settings->email_notifications( true ),
			],
			true
		);

		update_option( 'affwp_migrated_meta_fields', affwp_get_pending_migrated_user_meta_fields() );

		// Note, if this value is not found in the database, it means affwp_is_installed was set before this was introduced in version 2.10.0.
		update_option( 'affwp_first_installed', time(), false );
	}

	// 3 equals unchecked
	update_option( 'affwp_js_works', 3 );
	update_option( 'affwp_is_installed', '1' );
	update_option( 'affwp_version', AFFILIATEWP_VERSION );

	// check if needs to trigger wizard.
	if ( ! get_option( 'affwp_has_run_wizard' ) ) {
		update_option( 'affwp_trigger_wizard', true );
		update_option( 'affwp_display_setup_screen', true );
	}

	$affiliate_wp_install->rewrites->rewrites(); // Add rewrite rules.
	$affiliate_wp_install->rewrites->flush_rewrites(); // Flush rewrite cache.

	$completed_upgrades = array(
		'upgrade_v20_recount_unpaid_earnings',
		'upgrade_v22_create_customer_records',
		'upgrade_v245_create_customer_affiliate_relationship_records',
		'upgrade_v26_create_dynamic_coupons',
		'upgrade_v261_utf8mb4_compat',
		'upgrade_v27_calculate_campaigns',
		'upgrade_v274_calculate_campaigns',
		'upgrade_v281_convert_failed_referrals',
		'upgrade_v2140_set_creative_type',
		'upgrade_v2160_update_creative_names',
		'upgrade_v2276_clean_empty_registration_form_meta',
		'upgrade_v2250_create_login_registration_pages',
	);

	// Set past upgrade routines complete for all sites.
	if ( is_multisite() ) {

		if ( true === version_compare( $GLOBALS['wp_version'], '4.6', '<' ) ) {

			$sites = wp_list_pluck( 'blog_id', wp_get_sites() );

		} else {

			$sites = get_sites( array( 'fields' => 'ids' ) );

		}

		foreach ( $sites as $site_id ) {
			switch_to_blog( $site_id );

			update_option( 'affwp_completed_upgrades', $completed_upgrades );

			restore_current_blog();
		}

	} else {

		update_option( 'affwp_completed_upgrades', $completed_upgrades );

	}

	// Bail if activating from network, or bulk
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		return;
	}

	// Add the transient to redirect
	set_transient( '_affwp_activation_redirect', true, MINUTE_IN_SECONDS / 2 );

}
register_activation_hook( AFFILIATEWP_PLUGIN_FILE, 'affiliate_wp_install' );

/**
 * Checks if AffiliateWP is installed, and if not, runs the installer.
 *
 * @since 0.2
 */
function affiliate_wp_check_if_installed() {

	// This is mainly for network-activated installs.
	if ( ! get_option( 'affwp_is_installed' ) ) {
		affiliate_wp_install();
	}
}
add_action( 'admin_init', 'affiliate_wp_check_if_installed' );
