<?php
/**
 * Main Plugin Bootstrap
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2021, Awesome Motive Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

use AffiliateWP\Admin\DRM\DRM_Controller;
use AffiliateWP\Affiliate_Area;
use AffiliateWP\Affiliate_Area_Creatives;
use AffiliateWP\Scripts;
use AffWP\Components\Notifications;
use AffWP\Components\Wizard;
use AffiliateWP\Affiliate_Links;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Affiliate_WP' ) ) :

	#[AllowDynamicProperties]

	/**
	 * Main Affiliate_WP Class
	 *
	 * @since 1.0
	 */
	final class Affiliate_WP {
		/** Singleton *************************************************************/

		/**
		 * AffiliateWP instance.
		 *
		 * @access private
		 * @since  1.0
		 * @var    Affiliate_WP The one true Affiliate_WP
		 */
		private static $instance;

		/**
		 * The version number of AffiliateWP.
		 *
		 * @access private
		 * @since  1.0
		 * @since  2.15.2 Note, you no longer have to update this manually
		 *                when releasing a version of AffiliateWP. Simply change
		 *                the "Version" in the plugin's `/affiliate-wp.php` plugin header
		 *                and that will auto-populate here.
		 *
		 * @see self::set_plugin_data() where this is set.
		 * @var    string
		 */
		private string $version = '';

		/**
		 * Main plugin file.
		 *
		 * @since 2.7
		 * @var   string
		 */
		private $file = '';

		/**
		 * The affiliates DB instance variable.
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_DB_Affiliates
		 */
		public $affiliates;

		/**
		 * The affiliate meta DB instance variable.
		 *
		 * @access public
		 * @since  1.6
		 * @var    Affiliate_WP_Affiliate_Meta_DB
		 */
		public $affiliate_meta;

		/**
		 * The customers DB instance variable.
		 *
		 * @access public
		 * @since  2.2
		 * @var    Affiliate_WP_Customers_DB
		 */
		public $customers;

		/**
		 * The customer meta DB instance variable.
		 *
		 * @access public
		 * @since  2.2
		 * @var    Affiliate_WP_Customer_Meta_DB
		 */
		public $customer_meta;

		/**
		 * The referrals instance variable.
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Referrals_DB
		 */
		public $referrals;

		/**
		 * References the referral meta DB instance.
		 *
		 * @since 2.4
		 * @var   Affiliate_WP_Referral_Meta_DB
		 */
		public $referral_meta;

		/**
		 * The campaigns instance variable.
		 *
		 * @access public
		 * @since  1.7
		 * @var    Affiliate_WP_Campaigns_DB
		 */
		public $campaigns;

		/**
		 * The visits DB instance variable
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Visits_DB
		 */
		public $visits;

		/**
		 * The settings instance variable
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Settings
		 */
		public $settings;

		/**
		 * The affiliate tracking handler instance variable
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Tracking
		 */
		public $tracking;

		/**
		 * The template loader instance variable
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Templates
		 */
		public $templates;

		/**
		 * The affiliate login handler instance variable
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Login
		 */
		public $login;

		/**
		 * The opt in form handler instance variable
		 *
		 * @access public
		 * @since  2.2
		 * @var    Affiliate_WP_Opt_In
		 */
		public $opt_in;

		/**
		 * The affiliate registration handler instance variable
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Register
		 */
		public $register;

		/**
		 * The integrations handler instance variable
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Integrations
		 */
		public $integrations;

		/**
		 * The email notification handler instance variable
		 *
		 * @access public
		 * @since  1.0
		 * @var    Affiliate_WP_Emails
		 */
		public $emails;

		/**
		 * The creatives instance variable
		 *
		 * @access public
		 * @since  1.2
		 * @var    Affiliate_WP_Creatives_DB
		 */
		public $creatives;

		/**
		 * The creative class instance variable
		 *
		 * @access public
		 * @since  1.3
		 * @var    Affiliate_WP_Creatives
		 */
		public $creative;

		/**
		 * The creative meta DB instance variable.
		 *
		 * @since 2.17.0
		 * @var   AffiliateWP\Creatives\Meta\DB
		 */
		public $creative_meta;

		/**
		 * The creative view class instance variable for use within the Affiliate Area.
		 *
		 * @access public
		 * @since  2.16.0
		 * @var    Affiliate_Area_Creatives
		 */
		public Affiliate_Area_Creatives $creatives_view;

		/**
		 * The rewrite class instance variable
		 *
		 * @access public
		 * @since  1.7.8
		 * @var    Affiliate_WP_Rewrites
		 */
		public $rewrites;

		/**
		 * REST API bootstrap.
		 *
		 * @access public
		 * @since  1.9
		 * @var    Affiliate_WP_REST
		 */
		public $REST;

		/**
		 * The capabilities class instance variable.
		 *
		 * @access public
		 * @since  2.0
		 * @var    Affiliate_WP_Capabilities
		 */
		public $capabilities;

		/**
		 * The utilities class instance variable.
		 *
		 * @access public
		 * @since  2.0
		 * @var    Affiliate_WP_Utilities
		 */
		public $utils;

		/**
		 * The editor class instance variable.
		 *
		 * @since 2.8
		 * @var   Affiliate_WP_Editor
		 */
		public $editor;

		/**
		 * The notifications class instance variable.
		 *
		 * @since 2.9.5
		 * @var   Components\Notifications
		 */
		public $notifications;

		/**
		 * Plugin Data
		 *
		 * @since 2.15.2
		 *
		 * @var array
		 */
		private $plugin_data = [];

		/**
		 * The affiliate links class instance variable.
		 *
		 * @access public
		 * @since  2.25.0
		 * @var    Affiliate_Links
		 */
		public Affiliate_Links $affiliate_links;

		/**
		 * The custom links instance variable.
		 *
		 * @access public
		 * @since  2.14.0
		 * @var    Affiliate_WP_Custom_Links_DB
		 */
		public Affiliate_WP_Custom_Links_DB $custom_links;

		/**
		 * The custom link class instance variable.
		 *
		 * @access public
		 * @since  2.14.0
		 * @var    Affiliate_WP_Custom_Links
		 */
		public Affiliate_WP_Custom_Links $custom_Link;

		/**
		 * The scripts class instance variable.
		 *
		 * @access public
		 * @since  2.15.0
		 * @var    Scripts
		 */
		public Scripts $scripts;

		/**
		 * The DRM class instance variable.
		 *
		 * @access public
		 * @since  2.21.1
		 * @var    DRM_Controller
		 */
		public DRM_Controller $drm;

		/**
		 * Construct
		 *
		 * @since AFFWPN
		 */
		public function __construct() {
			$this->hooks();
		}

		/**
		 * Hooks
		 *
		 * Added to add hooks at plugin runtime.
		 *
		 * @since AFFWPN
		 */
		private function hooks() {
			add_action( 'doing_it_wrong_trigger_error', [ $this, 'fix_load_textdomain_just_in_time_notice' ], 10, 3 );
		}

		/**
		 * Main Affiliate_WP Instance
		 *
		 * Insures that only one instance of Affiliate_WP exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 * @since 2.7 A `$file` parameter was added for requirements check compat.
		 * @static
		 *
		 * @param string $file Main plugin file.
		 * @return \Affiliate_WP The one true plugin instance.
		 */
		public static function instance( $file = null ) {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Affiliate_WP ) ) {

				self::$instance = new Affiliate_WP;

				self::$instance->file = $file;

				self::$instance->set_plugin_data();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->setup_objects();
				self::$instance->load_textdomain();
			}

			return self::$instance;
		}

		/**
		 * Set the plugin data from the plugin header.
		 *
		 * @since 2.15.2
		 * @since 2.16.2 Removed use of `admin_url()` to obtain accurate path to `wp-admin/`.
		 */
		private function set_plugin_data() : void {

			require_once untrailingslashit( ABSPATH ) . '/wp-admin/includes/plugin.php';

			self::$instance->plugin_data = get_plugin_data( self::$instance->file, false, false );
			self::$instance->version     = self::$instance->plugin_data['Version'] ?? '';
		}

		/**
		 * Retrieve the plugin data.
		 *
		 * @since 2.15.2
		 *
		 * @return array Plugin data that correlates with the plugin's header.
		 */
		public function get_plugin_data() : array {
			return self::$instance->plugin_data;
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 1.0
		 * @access protected
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'affiliate-wp' ), '1.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 1.0
		 * @access protected
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'affiliate-wp' ), '1.0' );
		}

		/**
		 * Show a warning to sites running PHP < 5.3
		 *
		 * @since 1.0
		 * @deprecated 2.7
		 * @static
		 *
		 * @return void
		 */
		public static function below_php_version_notice() {
			_deprecated_function( __METHOD__, '2.7' );
		}

		/**
		 * Setup plugin constants
		 *
		 * @access private
		 * @since 1.0
		 * @return void
		 */
		private function setup_constants() {
			// Plugin version
			if ( ! defined( 'AFFILIATEWP_VERSION' ) ) {
				define( 'AFFILIATEWP_VERSION', $this->version );
			}

			// Plugin Folder Path
			if ( ! defined( 'AFFILIATEWP_PLUGIN_DIR' ) ) {
				define( 'AFFILIATEWP_PLUGIN_DIR', plugin_dir_path( $this->file ) );
			}

			// Plugin Folder URL
			if ( ! defined( 'AFFILIATEWP_PLUGIN_URL' ) ) {
				define( 'AFFILIATEWP_PLUGIN_URL', plugin_dir_url( $this->file ) );
			}

			// Plugin directory name only.
			if ( ! defined( 'AFFILIATEWP_PLUGIN_DIR_NAME' ) ) {
				define( 'AFFILIATEWP_PLUGIN_DIR_NAME', basename( dirname( $this->file ) ) );
			}

			// Plugin Root File
			if ( ! defined( 'AFFILIATEWP_PLUGIN_FILE' ) ) {
				define( 'AFFILIATEWP_PLUGIN_FILE', $this->file );
			}

			// Plugin Libraries Path
			if ( ! defined( 'AFFILIATEWP_PLUGIN_LIB_DIR' ) ) {
				define( 'AFFILIATEWP_PLUGIN_LIB_DIR', plugin_dir_path( $this->file ) . 'includes/libraries/' );
			}

			// Make sure CAL_GREGORIAN is defined.
			if ( ! defined( 'CAL_GREGORIAN' ) ) {
				define( 'CAL_GREGORIAN', 1 );
			}

			// Make sure PAYOUTS_SERVICE_NAME is defined.
			if ( ! defined( 'PAYOUTS_SERVICE_NAME' ) ) {
				define( 'PAYOUTS_SERVICE_NAME', 'Payouts Service' );
			}

			// Make sure PAYOUTS_SERVICE_URL is defined.
			if ( ! defined( 'PAYOUTS_SERVICE_URL' ) ) {
				define( 'PAYOUTS_SERVICE_URL', 'https://payouts.sandhillsplugins.com/' );
			}

			// Make sure PAYOUTS_SERVICE_DOCS_URL is defined.
			if ( ! defined( 'PAYOUTS_SERVICE_DOCS_URL' ) ) {
				define( 'PAYOUTS_SERVICE_DOCS_URL', trailingslashit( PAYOUTS_SERVICE_URL ) . 'documentation/' );
			}
		}

		/**
		 * Include required files
		 *
		 * @access private
		 * @since 1.0
		 * @return void
		 */
		private function includes() {

			// Loading files in includes/utils.
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/util-functions.php';

			// Addons functions need to be available sooner.
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/add-ons.php';

			// Libraries.
			require_once AFFILIATEWP_PLUGIN_LIB_DIR . 'affiliatewp-autoload.php';
			require_once AFFILIATEWP_PLUGIN_LIB_DIR . 'sandhills/persistent-dismissible/src/persistent-dismissible.php';
			require_once AFFILIATEWP_PLUGIN_LIB_DIR . 'action-scheduler/action-scheduler.php';

			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/abstracts/class-affwp-object.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-affiliate.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-coupon.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-customer.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-creative.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-payout.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-referral.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-sale.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-visit.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-campaign.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-custom-link.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/filters-referral-rates.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/actions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/abstracts/class-affwp-registry.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/settings/class-settings.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/abstracts/class-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/abstracts/class-meta-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affiliates-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-payouts-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-coupons-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-connections-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-groups-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-sales-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-capabilities.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-utilities.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-editor.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/affiliate-signup-widget/class-affiliate-signup-widget.php';

			if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/functions.php';

				// Bootstrap.
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/AFFWP_Plugin_Updater.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/abstracts/class-affwp-list-table.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-meta-box-base.php';

				// Admin only functions.
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/functions.php';

				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tooltips.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/affiliates/affiliate-groups.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/affiliates/actions.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/affiliates/includes/review-affiliate.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/ajax-actions.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-addon-updater.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-menu.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/affiliates/affiliates.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-notices-registry.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-notices.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-admin-notice.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/dashboard-widgets.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/creatives/creative-categories.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/creatives/creative-privacy.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/creatives/actions.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/creatives/creatives.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-meta-box-base.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/overview/overview.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/customers/actions.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/referrals/actions.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/referrals/referrals.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/payouts/actions.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/payouts/payouts.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/payouts/class-payouts-service.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/reports/reports.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/settings/display-settings.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/visits/visits.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/tools.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/plugins.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-migrate.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/user-profile.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/pages/class-smtp.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/pages/class-analytics.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-about.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/education/class-core.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/education/class-non-pro.php';
			}

			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-shortcodes.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/emails/class-affwp-emails.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/emails/functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/emails/actions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/date-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-graph.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-referrals-graph.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-visits-graph.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/reports/class-payouts-graph.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/reports/class-sales-graph.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/abstracts/class-affwp-opt-in-platform.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-integrations.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-captcha-manager.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-login.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-referrals-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-referral-meta-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-referral-type-registry.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-register.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-templates.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-tracking.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-rewrites.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-visits-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-campaigns-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-customers-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-customer-meta-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-creatives-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-creatives.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-creative-meta-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-custom-links-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-custom-links.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affiliate-meta-db.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/affiliate-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/affiliate-meta-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/customer-meta-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/creative-meta-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/reports/class-registrations-graph.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/misc-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/developer-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/payout-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/referral-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/campaign-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/referral-meta-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/sale-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/visit-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/customer-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/creative-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/custom-link-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/coupon-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/install.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/core-compatibility.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/plugin-compatibility.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/scripts.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-scripts.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-affwp-scheduler.php';

			// REST bootstrap.
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/rest-functions.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/class-rest-consumer.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/class-rest.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/class-rest-authentication.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/class-rest-consumers-db.php';

			// REST endpoints.
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-rest-controller.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-affiliates-endpoints.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-campaigns-endpoints.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-creatives-endpoints.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-customers-endpoints.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-payouts-endpoints.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-referrals-endpoints.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-sales-endpoints.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/REST/v1/class-visits-endpoints.php';
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/components/notifications/REST/v1/class-notifications-endpoints.php';

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-command.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-sub-commands-base.php';

				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/utils/class-affiliate-fetcher.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/utils/class-creative-fetcher.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/utils/class-customer-fetcher.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/utils/class-payout-fetcher.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/utils/class-referral-fetcher.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/utils/class-visit-fetcher.php';

				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-affiliate-sub-commands.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-creative-sub-commands.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-customer-sub-commands.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-payout-sub-commands.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-referral-sub-commands.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-visit-sub-commands.php';

				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-affiliate-meta-sub-commands.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-customer-meta-sub-commands.php';
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/cli/class-referral-meta-sub-commands.php';
			}

			if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-usage.php';
			}
		}

		/**
		 * Setup all objects
		 *
		 * phpcs:disable Generic.WhiteSpace.ScopeIndent.Incorrect
		 * phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning
		 *     Here we don't want to align these, otherwise we constantly have to re-align them
		 *     anytime we add something new, changing un-necessary code.
		 *
		 * phpcs:disable WordPress.Classes.ClassInstantiation.MissingParenthesis
		 *     Not a style we use consistently in this class (though we should).
		 *
		 * @access public
		 *
		 * @since 1.6.2
	     * @return void
		 */
		public function setup_objects() {

			self::$instance->connections = new AffiliateWP\Connections\DB();
			self::$instance->affiliates = new Affiliate_WP_DB_Affiliates;
			self::$instance->affiliate_meta = new Affiliate_WP_Affiliate_Meta_DB;
			self::$instance->referrals = new Affiliate_WP_Referrals_DB;
			self::$instance->referral_meta = new Affiliate_WP_Referral_Meta_DB;
			self::$instance->visits = new Affiliate_WP_Visits_DB;
			self::$instance->customers = new Affiliate_WP_Customers_DB;
			self::$instance->customer_meta = new Affiliate_WP_Customer_Meta_DB;
			self::$instance->campaigns = new Affiliate_WP_Campaigns_DB;
			self::$instance->settings = new Affiliate_WP_Settings;
			self::$instance->REST = new Affiliate_WP_REST;
			self::$instance->tracking = new Affiliate_WP_Tracking;
			self::$instance->templates = new Affiliate_WP_Templates;
			self::$instance->login = new Affiliate_WP_Login;
			self::$instance->register = new Affiliate_WP_Register;
			self::$instance->integrations = new Affiliate_WP_Integrations;
			self::$instance->emails = new Affiliate_WP_Emails;
			self::$instance->creatives = new Affiliate_WP_Creatives_DB;
			self::$instance->creative = new Affiliate_WP_Creatives;
			self::$instance->creative_meta = new AffiliateWP\Creatives\Meta\DB();
			self::$instance->affiliate_links = new Affiliate_Links();
			self::$instance->custom_links = new Affiliate_WP_Custom_Links_DB();
			self::$instance->custom_Link = new Affiliate_WP_Custom_Links();
			self::$instance->rewrites = new Affiliate_WP_Rewrites;
			self::$instance->capabilities = new Affiliate_WP_Capabilities;
			self::$instance->utils = new Affiliate_WP_Utilities;
			self::$instance->editor = new Affiliate_WP_Editor;
			self::$instance->groups = new AffiliateWP\Groups\DB();
			self::$instance->notifications = new Notifications\Notifications_DB();
			self::$instance->scripts = new Scripts();
			self::$instance->drm = new DRM_Controller();

			// Affiliate Area.
			Affiliate_Area::get_instance();
			self::$instance->creatives_view = new Affiliate_Area_Creatives();

			// Onboarding wizard.
			new Wizard\Bootstrap();

			if ( true == get_option( 'affwp_display_setup_screen' ) ) {
				new Wizard\Setup_Screen();
			}

			self::$instance->updater();
		}

		/**
		 * Plugin Updater
		 *
		 * @access private
		 * @since 1.0
		 * @return void
		 */
		private function updater() {

			if( ! is_admin() || ! class_exists( 'AFFWP_Plugin_Updater' ) ) {
				return;
			}

			$license_key = $this->settings->get( 'license_key' );

			// setup the updater
			$affwp_updater = new AFFWP_Plugin_Updater( 'https://affiliatewp.com', $this->file, array(
					'version'   => AFFILIATEWP_VERSION,
					'license'   => $license_key,
					'item_name' => 'AffiliateWP',
					'item_id'   => 17,
					'author'    => 'Pippin Williamson',
					'beta'      => $this->settings->get( 'betas', false )
				)
			);

		}

		/**
		 * Loads the plugin language files
		 *
		 * @access public
		 * @since 1.0
		 * @return void
		 */
		public function load_textdomain() {

			// Set filter for plugin's languages directory
			$lang_dir = AFFILIATEWP_PLUGIN_DIR . '/languages/';

			/**
			 * Filters the languages directory path to use for AffiliateWP.
			 *
			 * @param string $lang_dir The languages directory path.
			 */
			$lang_dir = apply_filters( 'aff_wp_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter

			global $wp_version;

			$get_locale = get_locale();

			if ( $wp_version >= 4.7 ) {
				$get_locale = get_user_locale();
			}

			/**
			 * Defines the plugin language locale used in AffiliateWP.
			 *
			 * @var $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
			 *                  otherwise uses `get_locale()`.
			 */
			$locale = apply_filters( 'plugin_locale', $get_locale, 'affiliate-wp' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'affiliate-wp', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/affiliate-wp/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/affiliate-wp/ folder
				load_textdomain( 'affiliate-wp', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/affiliate-wp/languages/ folder
				load_textdomain( 'affiliate-wp', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'affiliate-wp', false, $lang_dir );
			}
		}

		/**
		 * Suppress `_load_textdomain_just_in_time` notice.
		 *
		 * The reason this is here is because throughout our codebase we
		 * use `__()` before the `init` hook. Fixing all these will require
		 * refactoring our entire plugin to work differently.
		 *
		 * Until then users who have `WP_DEBUG` set to `true` will see a notice
		 * about this issue.
		 *
		 * Until we can fix the overall issue this squelches that notice
		 * in the meantime.
		 *
		 * @since AFFWPN
		 *
		 * @param bool   $suppress The normal value for all notices triggered by `_doing_it_wrong()`.
		 * @param string $func     The function name causing the notice, here we only care about `_load_textdomain_just_in_time`.
		 * @param string $message  The notice message being output.
		 *
		 * @return bool `false` for our plugin, the default value otherwise.
		 */
		public function fix_load_textdomain_just_in_time_notice(
			bool $suppress,
			string $func,
			string $message
		) {

			return (
				'_load_textdomain_just_in_time' === $func &&
				strstr( $message, '<code>affiliate-wp</code>' )
			)
				? false // Suppress any doing it wrong caused by our plugin.
				: $suppress; // Keep the default, not our plugin.
		}
	}
endif; // End if class_exists check

/**
 * The main function responsible for returning the one true Affiliate_WP
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $affiliate_wp = affiliate_wp(); ?>
 *
 * @since 1.0
 * @return Affiliate_WP The one true Affiliate_WP Instance
 */
function affiliate_wp() {
	return Affiliate_WP::instance();
}
