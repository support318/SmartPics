<?php
/**
 * Tracking functions for reporting plugin usage to the AffiliateWP site.
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2023, Awesome Motive, inc
 * @since       2.16.3
 *
 * phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition
 * phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket
 * phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine
 */

use AffWP\Core\License;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Usage tracking
 *
 * @access public
 * @since  2.16.3
 * @return void
 */
class Affiliate_WP_Usage_Tracking {

	/**
	 * Construct
	 *
	 * @since 2.26.0
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'schedule_send' ) );
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( 'affwp_usage_tracking_cron', array( $this, 'send_checkin' ) );

		add_action( 'affwp_set_affiliate_status', array( $this, 'track_first_affiliate' ), 10, 1 );
		add_action( 'affwp_set_referral_status', array( $this, 'track_first_referral' ), 10, 1 );
		add_action( 'affwp_set_referral_status', array( $this, 'track_first_payout' ), 10, 1 );
		add_action( 'affwp_insert_creative', array( $this, 'track_first_creative' ), 10, 2 );
	}

	/**
	 * Track the first affiliate registered, regardless of status.
	 *
	 * @since 2.16.3
	 */
	public function track_first_affiliate(): void {

		// If the date of the first affiliate is already stored, exit early.
		if ( get_option( 'affwp_affiliates_first_created' ) ) {
			return;
		}

		// Fetch the first affiliate.
		$affiliates = affiliate_wp()->affiliates->get_affiliates( array(
			'number' => -1,
			'order'  => 'ASC',
		) );

		if ( ! empty( $affiliates ) ) {
			foreach ( $affiliates as $affiliate ) {
				// Store the registration date if the user isn't an affiliate manager.
				if ( ! user_can( $affiliate->user_id, 'manage_affiliate_options' ) ) {
					add_option( 'affwp_affiliates_first_created', strtotime( $affiliate->date_registered ), '', 'no' );
					break;
				}
			}
		}
	}

	/**
	 * Track the first referral created, regardless of status.
	 *
	 * @since 2.16.3
	 */
	public function track_first_referral(): void {

		// If the date of the first referral is already stored, exit early.
		if ( get_option( 'affwp_referrals_first_created' ) ) {
			return;
		}

		// Fetch the first referral.
		$referrals = affiliate_wp()->referrals->get_referrals( array(
			'number' => 1,
			'order'  => 'ASC',
		) );

		if ( ! empty( $referrals ) ) {
			add_option( 'affwp_referrals_first_created', strtotime( $referrals[0]->date ), '', 'no' );
		}

	}

	/**
	 * Track the first payout created.
	 *
	 * @since 2.16.3
	 */
	public function track_first_payout(): void {

		// If the date of the first payout is already stored, exit early.
		if ( get_option( 'affwp_payouts_first_created' ) ) {
			return;
		}

		// Fetch the first payout.
		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'number' => 1,
			'order'  => 'ASC',
		) );

		if ( ! empty( $payouts ) ) {
			add_option( 'affwp_payouts_first_created', strtotime( $payouts[0]->date ), '', 'no' );
		}
	}

	/**
	 * Track the first creative added, regardless of status.
	 *
	 * @since 2.16.3
	 * @since 2.18.0 Updated to fire when a creative is successfully added.
	 *
	 * @param int   $creative_id The Creative being updated ID.
	 * @param array $args        Arguments used when adding creative.
	 */
	public function track_first_creative( int $creative_id, array $args ): void {

		if ( get_option( 'affwp_creatives_first_created' ) ) {
			return; // If the date of the first creative is already stored, exit early.
		}

		// Get the oldest creative's date (could even be this creative).
		$creatives = affiliate_wp()->creatives->get_creatives(
			array(
				'number'  => 1,
				'order'   => 'ASC',
				'orderby' => 'date',
			)
		);

		if ( ! empty( $creatives ) ) {
			add_option( 'affwp_creatives_first_created', strtotime(	$creatives[0]->date ), '', false );
		}

	}

	/**
	 * Get the options.
	 *
	 * @since 2.16.3
	 *
	 * @return array
	 */
	private function get_options() {
		$settings = array();
		$settings = get_option( 'affwp_settings' );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$settings = array();
		}

		return $settings;
	}

	/**
	 * Get the plugin data.
	 *
	 * @since 2.16.3
	 *
	 * @param string $type Type.
	 *
	 * @return array
	 */
	private function get_plugin_data( $type = '' ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		$plugins_data = array( 'active' => array(), 'inactive' => array() );

		foreach ( $plugins as $key => $plugin ) {
			$status = in_array( $key, $active_plugins ) ? 'active' : 'inactive';
			$plugins_data[ $status ][] = array(
				'name'    => $key,
				'version' => $plugin['Version'],
			);
		}

		return $type ? $plugins_data[ $type ] : $plugins_data;
	}

	/**
	 * Gets the date ranges for each query.
	 *
	 * @since 2.16.3
	 * @return array
	 */
	public function get_date_ranges() {
		return array(
			'7days'  => '-7 days',
			'30days' => '-30 days',
		);
	}

	/**
	 * Get the net affiliate revenue.
	 *
	 * @since 2.16.3
	 *
	 * @param string $date Date range string or 'alltime'.
	 * @return int|float Returns the net affiliate revenue.
	 */
	private function get_net_affiliate_revenue( string $date = 'alltime' ) {

		$date_params = null;

		if ( isset( $this->get_date_ranges()[ $date ] ) ) {
			$date_params = array(
				'start' => gmdate( 'Y-m-d', strtotime( $this->get_date_ranges()[ $date ] ) ),
				'end'   => gmdate( 'Y-m-d' ),
			);
		}

		return affiliate_wp()->referrals->sales->get_profits_by_referral_status( array( 'paid', 'unpaid' ), 0, $date_params );
	}

	/**
	 * Get the affiliate revenue growth.
	 *
	 * @since 2.16.3
	 *
	 * @param string $date Date range string or 'alltime'.
	 * @return int|float|null Returns revenue growth or null on error.
	 */
	private function get_affiliate_revenue_growth( string $date = 'alltime' ) {

		$revenue_growth = null;
		$date_params = null;

		if ( 'alltime' !== $date && isset( $this->get_date_ranges()[ $date ] ) ) {
			$date_params = array(
				'start' => gmdate( 'Y-m-d', strtotime( $this->get_date_ranges()[ $date ] ) ),
				'end'   => gmdate( 'Y-m-d' ),
			);
		}

		$revenue_growth = affiliate_wp()->integrations->get_affiliate_generated_sale_percentage( $date_params );

		return ! is_wp_error( $revenue_growth ) ? $revenue_growth : null;
	}

	/**
	 * Get AI usage data.
	 *
	 * @since 2.26.0
	 *
	 * @return array AI usage data.
	 */
	private function get_ai_usage_data(): array {

		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/affiliates/includes/review-affiliate.php';

		$usage_data = array(
			'ai_request'               => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_first( 'ai_request' ),
			'application_review'       => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_first( 'application_review' ),
			'requests'                 => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_usage( 'requests' ),
			'credits_used'             => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_usage( 'credits_used' ),
			'tokens_used'              => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_usage( 'tokens_used' ),
			'words'                    => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_usage( 'words' ),
			'characters'               => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_usage( 'characters' ),
			'available_credits'        => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_amount( 'available_credits' ),
			'available_applications'   => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_amount( 'available_applications' ),
			'agreements_disagreements' => \AffiliateWP\Admin\Affiliates\Review_Affiliate\get_ai_agreements_disagreements(),
		);

		return $usage_data;
	}

	/**
	 * Get the data to send.
	 *
	 * @since 2.16.3
	 *
	 * @return array
	 */
	private function get_data() {
		global $wpdb;
		$data = array();

		// Retrieve current theme info.
		$theme_data = wp_get_theme();

		// Get options.
		$options = $this->get_options();

		// Get license key.
		$license     = ( new License\License_Data() );
		$license_key = $license->get_license_key();

		$count_b = 1;
		if ( is_multisite() ) {
			if ( function_exists( 'get_blog_count' ) ) {
				$count_b = get_blog_count();
			} else {
				$count_b = 'Not Set';
			}
		}

		// Get creative type totals.
		$creatives              = affiliate_wp()->creatives->get_creatives( array( 'number' => -1 ) );
		$creatives_types_totals = array_count_values( array_filter( wp_list_pluck( $creatives, 'type' ) ) );

		// Get affiliate status totals.
		$affiliates               = affiliate_wp()->affiliates->get_affiliates( array( 'number' => -1 ) );
		$affiliates_status_totals = array_count_values( wp_list_pluck( $affiliates, 'status' ) );

		// Get payout status totals.
		$payouts               = affiliate_wp()->affiliates->payouts->get_payouts( array( 'number' => -1, 'status' => array( 'processing', 'paid', 'failed' ) ) );
		$payouts_status_totals = array_count_values( wp_list_pluck( $payouts, 'status' ) );

		$data = [

			// Generic data (environment).
			'url'                                  => home_url(),
			'php_version'                          => phpversion(),
			'wp_version'                           => get_bloginfo( 'version' ),
			'mysql_version'                        => $wpdb->db_version(),
			'server_version'                       => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
			'is_ssl'                               => is_ssl(),
			'is_multisite'                         => is_multisite(),
			'sites_count'                          => $count_b,
			'user_count'                           => function_exists( 'get_user_count' ) ? get_user_count() : 'Not Set',
			'active_plugins'                       => $this->get_plugin_data( 'active' ),
			'inactive_plugins'                     => $this->get_plugin_data( 'inactive' ),
			'theme_name'                           => $theme_data->name,
			'theme_version'                        => $theme_data->version,
			'email'                                => get_bloginfo( 'admin_email' ),
			'locale'                               => get_locale(),
			'timezone_offset'                      => $this->get_timezone_offset(),

			// AffiliateWP-specific data.
			'affwp_version'                        => AFFILIATEWP_VERSION,
			'affwp_license_key'                    => $license_key,
			'affwp_license_type'                   => $this->get_license_type(),
			'affwp_license_status'                 => $this->get_license_status(),
			'affwp_settings'                       => $options,
			'affwp_usage_tracking'                 => get_option( 'affwp_usage_tracking_config', false ),
			'affwp_affiliates_total'               => affiliate_wp()->affiliates->count( array( 'number' => -1 ) ),
			'affwp_referrals_total'                => affiliate_wp()->referrals->count( array( 'number' => -1 ) ),
			'affwp_visits_total'                   => affiliate_wp()->visits->count( array( 'number' => -1 ) ),
			'affwp_creatives_total'                => affiliate_wp()->creatives->count( array( 'number' => -1 ) ),
			'affwp_payouts_total'                  => affiliate_wp()->affiliates->payouts->count( array( 'number' => -1 ) ),
			'affwp_customers_total'                => affiliate_wp()->customers->count( array( 'number' => -1 ) ),
			'affwp_consumers_total'                => affiliate_wp()->REST->consumers->count( array( 'number' => -1 ) ),
			'affwp_affiliates_status_totals'       => $affiliates_status_totals,
			'affwp_creatives_types_totals'         => $creatives_types_totals,
			'affwp_payouts_status_totals'          => $payouts_status_totals,
			'affwp_active_integrations'            => affiliate_wp()->integrations->get_enabled_integrations(),
			'affwp_installed_date'                 => $this->installed_date(),
			'affwp_affiliates_first_created'       => get_option( 'affwp_affiliates_first_created', false ),
			'affwp_referrals_first_created'        => get_option( 'affwp_referrals_first_created', false ),
			'affwp_payouts_first_created'          => get_option( 'affwp_payouts_first_created', false ),
			'affwp_creatives_first_created'        => get_option( 'affwp_creatives_first_created', false ),
			'affwp_registration_method_totals'     => affiliatewp_get_registration_method_totals(),

			// Core metrics.
			'affwp_7day_net_affiliate_revenue'     => $this->get_net_affiliate_revenue( '7days' ),
			'affwp_30day_net_affiliate_revenue'    => $this->get_net_affiliate_revenue( '30days' ),
			'affwp_total_net_affiliate_revenue'    => $this->get_net_affiliate_revenue( 'alltime' ),
			'affwp_7day_affiliate_revenue_growth'  => $this->get_affiliate_revenue_growth( '7days' ),
			'affwp_30day_affiliate_revenue_growth' => $this->get_affiliate_revenue_growth( '30days' ),
			'affwp_total_affiliate_revenue_growth' => $this->get_affiliate_revenue_growth( 'alltime' ),
			'affwp_ai_usage'                       => $this->get_ai_usage_data(),
		];

		return $data;
	}

	/**
	 * Get installed date.
	 *
	 * @since 2.16.3
	 *
	 * @return string
	 */
	private function installed_date() {
		$first_installed = get_option( 'affwp_first_installed', false );

		if ( ! empty( $first_installed ) ) {
			return $first_installed;
		}

		return get_post_field( 'post_date', affwp_get_affiliate_area_page_id() );
	}

	/**
	 * Get timezone offset.
	 * We use `wp_timezone_string()` when it's available (WP 5.3+),
	 * otherwise fallback to the same code, copy-pasted.
	 *
	 * @see wp_timezone_string()
	 *
	 * @since 2.16.3
	 *
	 * @return string
	 */
	private function get_timezone_offset() {

		// It was added in WordPress 5.3.
		if ( function_exists( 'wp_timezone_string' ) ) {
			return wp_timezone_string();
		}

		/*
		 * The code below is basically a copy-paste from that function.
		 */
		$timezone_string = get_option( 'timezone_string' );

		if ( $timezone_string ) {
			return $timezone_string;
		}

		$offset  = (float) get_option( 'gmt_offset' );
		$hours   = (int) $offset;
		$minutes = ( $offset - $hours );

		$sign      = ( $offset < 0 ) ? '-' : '+';
		$abs_hour  = abs( $hours );
		$abs_mins  = abs( $minutes * 60 );
		$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

		return $tz_offset;
	}

	/**
	 * Get the license type.
	 *
	 * @since 2.16.3
	 *
	 * @return string
	 */
	private function get_license_type() {
		$license_data = new \AffWP\Core\License\License_Data();
		$license_id   = $license_data->get_license_id();
		$license_type = $license_data->get_license_type( $license_id );

		if ( ! is_string( $license_type ) || empty( $license_type ) ) {
			return '';
		}

		return strtolower( $license_type );
	}

	/**
	 * Get the license status.
	 *
	 * @since 2.16.3
	 *
	 * @return string
	 */
	private function get_license_status() {
		$status = affiliate_wp()->settings->get( 'license_status', '' );

		$status = ( is_object( $status ) && isset( $status->license ) )
		? $status->license
		: $status;

		return $status;
	}

	/**
	 * Send the checkin.
	 *
	 * @since 2.16.3
	 *
	 * @param bool $override           Override.
	 * @param bool $ignore_last_checkin Ignore.
	 *
	 * @return bool
	 */
	public function send_checkin( $override = false, $ignore_last_checkin = false ) {

		$home_url = trailingslashit( home_url() );
		if ( strpos( $home_url, 'affiliatewp.com' ) !== false ) {
			return false;
		}

		if ( ! $this->tracking_allowed() && ! $override ) {
			return false;
		}

		// Send a maximum of once per week.
		$last_send = get_option( 'affwp_usage_tracking_last_checkin' );
		if ( is_numeric( $last_send ) && $last_send > strtotime( '-1 week' ) && ! $ignore_last_checkin ) {
			return false;
		}

		$request = wp_remote_post( 'https://usg.affiliatewp.com/v1/checkin/', array(
			'method'      => 'POST',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.1',
			'body'        => $this->get_data(),
			'user-agent'  => 'AFFWP/' . AFFILIATEWP_VERSION . '; ' . get_bloginfo( 'url' )
		) );

		// If we have completed successfully, recheck in 1 week.
		update_option( 'affwp_usage_tracking_last_checkin', time() );

		return true;
	}

	/**
	 * Check if tracking is allowed.
	 *
	 * @since 2.16.3
	 *
	 * @return bool
	 */
	private function tracking_allowed() {
		// Tracking always allowed.
		return true;
	}

	/**
	 * Schedule the send.
	 *
	 * @since 2.16.3
	 *
	 * @return void
	 */
	public function schedule_send() {
		if ( ! wp_next_scheduled( 'affwp_usage_tracking_cron' ) ) {
			$tracking             = array();
			$tracking['day']      = rand( 0, 6 );
			$tracking['hour']     = rand( 0, 23 );
			$tracking['minute']   = rand( 0, 59 );
			$tracking['second']   = rand( 0, 59 );
			$tracking['offset']   = ( $tracking['day'] * DAY_IN_SECONDS ) +
				( $tracking['hour'] * HOUR_IN_SECONDS ) +
				( $tracking['minute'] * MINUTE_IN_SECONDS ) +
					$tracking['second'];

			$tracking['initsend'] = strtotime( "next sunday" ) + $tracking['offset'];

			wp_schedule_event( $tracking['initsend'], 'weekly', 'affwp_usage_tracking_cron' );
			update_option( 'affwp_usage_tracking_config', $tracking );
		}
	}

	/**
	 * Add weekly schedule.
	 *
	 * @since 2.16.3
	 *
	 * @param array $schedules Schedules.
	 *
	 * @return array
	 */
	public function add_schedules( $schedules = array() ) {
		// Adds once weekly to the existing schedules.
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'affiliate-wp' ),
		);
		return $schedules;
	}
}
new Affiliate_WP_Usage_Tracking();
