<?php
/**
 * Admin: Notices API
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2016, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

use AffWP\Core\License;
use Sandhills\Utils\Persistent_Dismissible;

#[\AllowDynamicProperties]

/**
 * AffiliateWP Admin Notices class
 *
 * @since 1.0
 */
class Affiliate_WP_Admin_Notices {

	/**
	 * Current AffiliateWP version.
	 *
	 * @since 2.0
	 * @var string
	 */
	private $version;

	/**
	 * Whether to display notices.
	 *
	 * Used primarily for unit testing expected output.
	 *
	 * @since 2.1
	 * @var bool Default true.
	 */
	private $display_notices = true;

	/**
	 * Notices registry.
	 *
	 * @since 2.4
	 * @var   \AffWP\Admin\Notices_Registry
	 */
	private static $registry;

	/**
	 * Sets up the notices API.
	 *
	 * Core notices are registered against the affwp_notices_registry_init hook, which
	 * grants local access to a canonical instance of the Notices_Registry class.
	 * The init() method of the Notices_Registry class is likewise hooked to admin_init,
	 * thereby effectively registering admin notices on admin_init.
	 *
	 * Since all core notices are registered on the one hook, a similar system can also
	 * be employed by any third-parties (including add-ons) wanting to hook into the core
	 * admin notices API for various purposes.
	 *
	 * Example:
	 *
	 *     add_action( 'affwp_notices_registry_init', function( $registry ) {
	 *         $registry->add_notice( 'example-notice', array(
	 *             'class'   => 'error',
	 *             'message' => 'There was an error with {component}.',
	 *         ) );
	 *     }, 11 );
	 *
	 * @since 1.0
	 */
	public function __construct() {

		$registry = new \AffWP\Admin\Notices_Registry;

		// Set up local access of the single registry instance.
		self::set_registry( $registry );

		add_action( 'affwp_notices_registry_init', array( $this,     'register_notices' ) );
		add_action( 'admin_init',                  array( $registry, 'init'             ) );
		add_action( 'admin_notices',               array( $this,     'show_notices'     ) );
		add_action( 'wp_ajax_affwp_dismiss_promo', array( $this,     'dismiss_promo'    ) );
		add_action( 'affwp_dismiss_notices',       array( $this,     'dismiss_notices'  ) );
		add_action( 'admin_print_scripts',         array( $this,     'hide_non_affiliatewp_notices' ) );
		add_action( 'admin_head',                  array( $this,     'hide_non_affiliatewp_notices' ), PHP_INT_MAX );
		add_action( 'affwp_plugins_loaded',        array( $this,     'fire_affiliatewp_admin_notices_hook'    ) );
	}

	/**
	 * Admin Notices Hook
	 *
	 * Runs on `affwp_plugins_loaded` where you can register more notices using
	 * the \AffiliateWP\Notices\Admin_Notice class/API.
	 *
	 * @since 2.27.4
	 */
	public function fire_affiliatewp_admin_notices_hook() : void {

		if ( ! is_admin() ) {
			return; // The below should only run in the admin.
		}

		/**
		 * Admin Notices (AffiliateWP) Hook
		 *
		 * Use this hook for adding notices outside of this class.
		 *
		 * You can use the \AffiliateWP\Notices\Admin_Notice class for a
		 * more convenient API on this hook:
		 *
		 * add_action( 'affiliatewp_admin_notices', function() {
		 *     new Admin_Notice(
		 *         'my_notice_id',
		 *         __( 'My message', 'text-domain' ),
		 *         'non-dismissible',
		 *         'error',
		 *         'administrator',
		 *         'any'
		 *     );
		 * } );
		 *
		 * @since 2.27.4
		 *
		 * @param \Affiliate_WP_Admin_Notices $this This notices class.
		 */
		do_action( 'affiliatewp_admin_notices', $this );
	}

	/**
	 * Sets the registry for use by the class.
	 *
	 * @since 2.4
	 *
	 * @param \AffWP\Admin\Notices_Registry $registry Registry instance.
	 */
	private static function set_registry( $registry ) {
		self::$registry = $registry;
	}

	/**
	 * Registers the admin notices.
	 *
	 * @since 2.4
	 * @since 2.27.4 Added convenient hook for adding hooks outside of this class.
	 *
	 * @param \AffWP\Admin\Notices_Registry $registry Registry instance.
	 */
	public function register_notices( $registry ) {

		$this->affiliate_notices();
		$this->consumer_notices();
		$this->creative_notices();
		$this->customer_notices();
		$this->payout_notices();
		$this->referral_notices();

		$this->integration_notices();
		$this->settings_notices();
		$this->environment_notices();
		$this->upgrade_notices();
		$this->development_notices();
		$this->addons_notices();

		$this->hrk_notice(); // @TODO Remove after 1/1/2023.
	}

	/**
	 * HRK Notice.
	 *
	 * @since  2.11.0
	 *
	 * @TODO Remove after 1/1/2023.
	 *
	 * @see affwp_maybe_remove_hrk().
	 * @see Affiliate_WP_Admin_Notices::hrk_notice().
	 * @see affwp_hrk_time().
	 */
	public function hrk_notice() {

		if ( time() < affwp_hrk_time() ) {
			return; // Keep HRK until the right time.
		}

		if ( 'hrk' !== strtolower( affwp_get_currency() ) ) {
			return; // The currency isn't HRK, don't do anything.
		}

		if ( ! affwp_is_admin_page() ) {
			return; // Only on our pages.
		}

		// Register a notice.
		$this->add_notice( 'hrk_notice', array(
			'message' => sprintf(
				// Translators: %1$s is a link to settings.
				__( 'You need to %1$s. On January 1, 2023, Croatia joined the Eurozone. This means the Croatian Kuna (HRK) is now deprecated in favor of the euro (EUR).', 'affiliate-wp' ),
				sprintf(
					'<a href="admin.php?page=affiliate-wp-settings#currency_settings">%s</a>',
					_x( 'select a new currency', 'affiliate-wp' )
				)
			),
			'class'   => 'error',
		) );

		self::show_notice( 'hrk_notice', true );
	}

	/**
	 * Outputs general admin notices.
	 *
	 * @since 1.0
	 * @since 1.8.3 Notices are hidden for users lacking the 'manage_affiliates' capability
	 *
	 * @return string|void Output if `$display_notices` is false, otherwise void.
	 */
	public function show_notices() {
		$affwp_message = $notice_id = $output = '';

		// Handle displaying registered notices triggered via the 'affwp_notice' query arg in the URL.
		if ( ! empty( $_REQUEST['affwp_notice'] ) ) {
			$notice_id = urldecode( sanitize_text_field( $_REQUEST['affwp_notice'] ) );

			$output .= self::show_notice( $notice_id, false );
		}

		$promo_notice_id = isset( $_GET['test-promo'] ) ? 'promo' : 'affwp-license-upgrade';

		$promo_dismissed = Persistent_Dismissible::get( array( 'id' => $promo_notice_id ) );

		if ( ! $promo_dismissed && affwp_is_admin_page() ) {
			$this->show_promo_notices();
		}

		// Handle displaying the settings-updated notice.
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] && isset( $_GET['page'] ) && $_GET['page'] == 'affiliate-wp-settings' ) {
			$output .= self::show_notice( 'settings-updated', false );
		}

		// PHP minimum notice.
		if (
			true === version_compare( phpversion(), '7.4', '<' ) &&
			affwp_is_admin_page() &&
			false === get_transient( 'affwp_requirements_php_74_notice' )
		) {
			$output .= self::show_notice( 'requirements_php_74', false );
		}

		if ( affwp_is_admin_page() && false !== strpos( AFFILIATEWP_VERSION, '-' ) ) {
			$output .= self::show_notice( 'development_version', false );
		}

		if ( affwp_affiliate_email_summaries_enabled_without_wp_mail_smtp() ) {
			$output .= self::show_notice( 'wp_mail_smtp_not_configured', false );
		}

		if ( true === version_compare( AFFILIATEWP_VERSION, '2.0', '<' ) || false === affwp_has_upgrade_completed( 'upgrade_v20_recount_unpaid_earnings' ) ) {
			$output .= self::show_notice( 'upgrade_v20_recount_unpaid_earnings', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v22_create_customer_records' ) ) {
			$output .= self::show_notice( 'upgrade_v22_create_customer_records', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v245_create_customer_affiliate_relationship_records' ) ) {
			$output .= self::show_notice( 'upgrade_v245_create_customer_affiliate_relationship_records', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v26_create_dynamic_coupons' ) ) {
			$output .= self::show_notice( 'upgrade_v26_create_dynamic_coupons', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v261_utf8mb4_compat' ) ) {
			$output .= self::show_notice( 'upgrade_v261_utf8mb4_compat', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v27_calculate_campaigns' ) ) {
			$output .= self::show_notice( 'upgrade_v27_calculate_campaigns', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v274_calculate_campaigns' ) ) {
			$output .= self::show_notice( 'upgrade_v274_calculate_campaigns', false );
		}

		if ( affwp_get_current_migrated_user_meta_fields() !== affwp_get_pending_migrated_user_meta_fields() ) {
			$output .= self::show_notice( 'migrate_affiliate_user_meta', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v281_convert_failed_referrals' ) ) {
			$output .= self::show_notice( 'upgrade_v281_convert_failed_referrals', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v2140_set_creative_type' ) ) {
			$output .= self::show_notice( 'upgrade_v2140_set_creative_type', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v2160_update_creative_names' ) ) {
			$output .= self::show_notice( 'upgrade_v2160_update_creative_names', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v2276_clean_empty_registration_form_meta' ) ) {
			$output .= self::show_notice( 'upgrade_v2276_clean_empty_registration_form_meta', false );
		}

		if ( false === affwp_has_upgrade_completed( 'upgrade_v2250_create_login_registration_pages' ) ) {
			$output .= self::show_notice( 'upgrade_v2250_create_login_registration_pages', false );
		}

		// Payouts Service.
		if ( in_array( affwp_get_current_screen(), array( 'affiliate-wp-referrals', 'affiliate-wp-payouts' ), true ) ) {
			$vendor_id  = affiliate_wp()->settings->get( 'payouts_service_vendor_id', 0 );
			$access_key = affiliate_wp()->settings->get( 'payouts_service_access_key', '' );

			if ( ! ( $vendor_id && $access_key ) && false === get_transient( 'affwp_payouts_service_notice' ) ) {
				$output .= self::show_notice( 'payouts_service', false );
			}
		}

		// Integrations.
		$integrations = affiliate_wp()->integrations->get_enabled_integrations();
		$active_integrations = affiliate_wp()->integrations->query( array( 'fields' => 'ids' ) );

		foreach ( $active_integrations as $id ) {

			$integration = affiliate_wp()->integrations->get( $id );
			$screen      = affwp_get_current_screen();

			if ( 'affiliate-wp-reports' === $screen && ! is_wp_error( $integration ) && true === $integration->needs_synced() ) {
				$output .= self::show_notice( $integration->context . '_needs_synced', false );
			}
		}

		$legacy_plugin_active = class_exists( 'AffiliateWP_Discontinued_Integrations' );

		foreach ( affiliate_wp()->integrations->get_discontinued_integrations() as $integration => $label ) {
			if ( affwp_is_admin_page()
				&& array_key_exists( $integration, $integrations )
				&& false === $legacy_plugin_active
			) {
				$output .= self::show_notice( "{$integration}_discontinued_integration_enabled", false );
			}
		}

		// Don't display other types of notices for users who can't manage affiliates.
		if ( current_user_can( 'manage_affiliates' ) ) {
			// Compat for displaying notices defined via the 'affwp_message' query arg.
			if ( ! empty( $_REQUEST['affwp_message'] ) ) {
				$affwp_message = urldecode( sanitize_text_field( $_REQUEST['affwp_message'] ) );

				if ( ! empty( $_REQUEST['affwp_success'] ) && 'no' === $_REQUEST['affwp_success'] ) {
					$class = 'error';
				} else {
					$class = 'updated';
				}

				$output .= self::prepare_message_for_output( $affwp_message, $class );
			}
		}

		// Show notices registered via the \AffiliateWP\Notices\Admin_Notice class.
		foreach ( self::$registry->get_items() as $id => $item ) {

			if ( 'AffiliateWP\Notices\Admin_Notice' !== ( $item['type'] ?? '' ) ) {
				continue; // Not a class-based admin notice, don't show.
			}

			// Automatically show the notice.
			$output .= self::show_notice( $id, false );
		}

		if ( true === $this->display_notices ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Shows promo notices.
	 *
	 * @since 2.7
	 */
	private function show_promo_notices() {
		$source = affwp_get_current_screen();

		if ( ! $source ) {
			return;
		}

		$license_data = affiliate_wp()->settings->get( 'license_status', array() );
		$license_id   = isset( $license_data->price_id ) ? intval( $license_data->price_id ) : false;

		if ( isset( $_GET['license_id'] ) ) {
			$license_id = absint( $_GET['license_id'] );
		}

		// Bail if no license or if ID is greater than 1 because then they are fully upgraded.
		if ( false === $license_id || $license_id > 1 || empty( $license_data ) ) {
			return;
		}

		if ( 0 === $license_id ) {
			$license_type = 'Personal';
		} elseif ( 1 === $license_id ) {
			$license_type = 'Plus';
		} else {
			$license_type = '';
		}

		if ( empty( $license_type ) ) {
			return;
		}

		$utm_args    = array(
			'utm_source'   => $source,
			'utm_medium'   => sprintf( 'upgrade-from-%s', strtolower( $license_type ) ),
			'utm_campaign' => 'admin',
		);

		$upgrade_url = add_query_arg( $utm_args, 'https://affiliatewp.com/professional' );

		?>
		<div
			id="affwp-notice-license-upgrade"
			class="affwp-admin-notice-top-of-page affwp-promo-notice"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'affwp-dismiss-notice-affwp-license-upgrade' ) ); ?>"
			data-id="affwp-license-upgrade"
			data-lifespan="<?php echo esc_attr( DAY_IN_SECONDS * 90 ); ?>"
		>
			<?php
			/* translators: %1$s License type. %2$s Opening anchor tag, do not translate. %3$s Closing anchor tag, do not translate. */
			$message = __(
				'You are using AffiliateWP with a %1$s license. %2$sUpgrade to Pro now%3$s & unlock the full revenue potential of your affiliate program.',
				'affiliate-wp'
			);

			echo wp_kses(
				sprintf(
					$message,
					$license_type,
					'<a href="' . esc_url( $upgrade_url ) . '" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
				array(
					'a' => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
					),
				)
			);
			?>
			<button class="button-link affwp-notice-dismiss">
				&times;
			</button>
		</div>
		<?php
	}

	/**
	 * Registers affiliate notices.
	 *
	 * @since 2.4
	 */
	private function affiliate_notices() {

		$this->add_notice( 'affiliate_added', array(
			'message' => function() {
				require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-migrate-users.php';

				$total_affiliates = (int) Affiliate_WP_Migrate_Users::get_items_total( 'affwp_migrate_users_current_count' );

				/*
				 * If $total_affiliates is 0 and we know 'affiliate_added' has been fired,
				 * it was a manual addition, and therefore 1 affiliate was added.
				 */
				if ( 0 === $total_affiliates ) {
					$total_affiliates = 1;
				}

				$message = sprintf( _n(
					/* translators: Singular number of affiliates added */
					'%d affiliate was added successfully.',
					/* translators: Plural number of affiliates added */
					'%d affiliates were added successfully',
					$total_affiliates,
					'affiliate-wp'
				), number_format_i18n( $total_affiliates ) );

				Affiliate_WP_Migrate_Users::clear_items_total( 'affwp_migrate_users_current_count' );

				return $message;
			},
		) );

		$this->add_notice( 'affiliate_added_failed', array(
			'class'   => 'error',
			'message' => __( 'Affiliate wasn&#8217;t added, please try again.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'affiliate_updated', array(
			'message' => function() {
				$message =  __( 'Affiliate updated successfully', 'affiliate-wp' );
				/* translators: URL to the affiliates screen */
				$message .= '<p>'. sprintf( __( '<a href="%s">Back to Affiliates</a>.', 'affiliate-wp' ), esc_url( affwp_admin_url( 'affiliates' ) ) ) .'</p>';

				return $message;
			},
		) );

		$this->add_notice( 'affiliate_update_failed', array(
			'class'   => 'error',
			'message' => __( 'Affiliate update failed, please try again', 'affiliate-wp' ),
		) );

		$this->add_notice( 'affiliate_deleted', array(
			'message' => __( 'Affiliate account(s) deleted successfully', 'affiliate-wp' ),
		) );

		$this->add_notice( 'affiliate_delete_failed', array(
			'class'   => 'error',
			'message' => __( 'Affiliate deletion failed, please try again', 'affiliate-wp' ),
		) );

		$this->add_notice( 'affiliate_activated', array(
			'message' => __( 'Affiliate account activated', 'affiliate-wp' ),
		) );

		$this->add_notice( 'affiliate_deactivated', array(
			'message' => __( 'Affiliate account deactivated', 'affiliate-wp' ),
		) );

		$this->add_notice( 'affiliate_accepted', array(
			'message' => __( 'Affiliate request was accepted', 'affiliate-wp' ),
		) );

		$this->add_notice( 'affiliate_rejected', array(
			'message' => __( 'Affiliate request was rejected', 'affiliate-wp' ),
		) );

		$this->add_notice( 'dynamic_coupon_created', array(
			'message' => __( 'Dynamic coupon successfully created.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'dynamic_coupon_create_failed', array(
			'class'   => 'error',
			'message' => __( 'Dynamic coupon creation failed, please try again.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'dynamic_coupon_deleted', array(
			'message' => __( 'Dynamic coupon successfully deleted.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'dynamic_coupon_delete_failed', array(
			'class'   => 'error',
			'message' => __( 'Dynamic coupon deletion failed, please try again.', 'affiliate-wp' ),
		) );
	}

	/**
	 * Registers API consumer admin notices.
	 *
	 * @since 2.4
	 */
	private function consumer_notices() {
		$this->add_notice( 'api_key_generated', array(
			'message' => __( 'The API keys were successfully generated.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'api_key_failed', array(
			'class'   => 'error',
			'message' => __( 'The API keys could not be generated.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'api_key_regenerated', array(
			'message' => __( 'The API keys were successfully regenerated.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'api_key_revoked', array(
			'message' => __( 'The API keys were successfully revoked.', 'affiliate-wp' ),
		) );
	}

	/**
	 * Registers creative admin notices.
	 *
	 * @since 2.4
	 */
	private function creative_notices() {
		$this->add_notice( 'creative_updated', array(
			'message' => function() {
				$message =  __( 'Creative updated successfully', 'affiliate-wp' );
				/* translators: URL to the Creatives screen */
				$message .= '<p>'. sprintf( __( '<a href="%s">Back to Creatives</a>', 'affiliate-wp' ), esc_url( affwp_admin_url( 'creatives' ) ) ) .'</p>';

				return $message;
			},
		) );

		$this->add_notice( 'creative_added', array(
			'message' => __( 'Creative added successfully', 'affiliate-wp' ),
		) );

		$this->add_notice( 'creative_deleted', arraY(
			'message' => __( 'Creative deleted successfully', 'affiliate-wp' ),
		) );

		$this->add_notice( 'creative_activated', array(
			'message' => __( 'Creative activated', 'affiliate-wp' ),
		) );

		$this->add_notice( 'creative_deactivated', array(
			'message' => __( 'Creative deactivated', 'affiliate-wp' ),
		) );

		$this->add_notice( 'creatives_name_upgraded', array(
			'message' => __( 'Creatives have been successfully updated', 'affiliate-wp' ),
		) );
	}

	/**
	 * Registers customer admin notices.
	 *
	 * @since 2.5.7
	 */
	public function customer_notices() {
		$this->add_notice( 'customer_added', array(
			'message' => __( 'Customer added successfully', 'affiliate-wp' ),
		) );

		$this->add_notice( 'customer_added_failed', array(
			'class'   => 'error',
			'message' => __( 'Customer wasn&#8217;t added, please try again.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'customer_updated', array(
			'message' => __( 'Customer updated successfully', 'affiliate-wp' ),
		) );

		$this->add_notice( 'customer_update_failed', array(
			'class'   => 'error',
			'message' => __( 'Customer update failed, please try again', 'affiliate-wp' ),
		) );
	}

	/**
	 * Registers payout admin notices.
	 *
	 * @since 2.4
	 */
	public function payout_notices() {
		$this->add_notice( 'payout_created', array(
			'message' => __( 'A payout has been created.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'payout_deleted', array(
			'message' => __( 'Payout deleted successfully.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'payout_delete_failed', array(
			'message' => __( 'Payout deletion failed, please try again.', 'affiliate-wp' ),
		) );

		// Payouts service notices.
		$this->add_notice( 'payouts_service_site_connected', array(
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			'message' => sprintf( __( 'Website connected to the %s.', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME ),
		) );

		$this->add_notice( 'payouts_service_site_disconnected', array(
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			'message' => sprintf( __( 'Website disconnected from the %s.', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME ),
		) );

		$this->add_notice( 'payouts_service_site_reconnected', array(
			/* translators: Payouts Service name retrieved from the PAYOUTS_SERVICE_NAME constant */
			'message' => sprintf( __( 'Website reconnected to the %s.', 'affiliate-wp' ), PAYOUTS_SERVICE_NAME ),
		) );

		// Payouts Service.
		$message = '<p><strong>' . __( 'Effortlessly pay your affiliates', 'affiliate-wp' ) . '</strong></p>';

		$message .= sprintf(
			__( 'With the Payouts Service provided by AffiliateWP, you can easily pay affiliates in 50 countries using any debit or credit card. Learn more at <a href="%s" target="_blank">payouts.sandhillsplugins.com</a>.', 'affiliate-wp' ),
			PAYOUTS_SERVICE_URL
		);

		$added = $this->add_notice( 'payouts_service', array(
			'class'         => 'updated',
			'message'       => $message,
			'dismissible'   => true,
			'dismiss_label' => _x( 'Maybe later', 'payouts service', 'affiliate-wp' ),
		) );
	}

	/**
	 * Registers referral admin notices.
	 *
	 * @since 2.4
	 */
	private function referral_notices() {
		$this->add_notice( 'referral_added', array(
			'message' => __( 'Referral added successfully.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'referral_add_failed', array(
			'class'   => 'error',
			'message' => __( 'Referral wasn&#8217;t created, please try again.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'referral_add_invalid_affiliate', array(
			'class'   => 'error',
			'message' => __( 'Referral not created because the affiliate is invalid, please try again.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'referral_invalid_amount', array(
            'class'   => 'error',
            'message' => __( 'Referral amount cannot be negative, please try again.', 'affiliate-wp' ),
        ) );

		$this->add_notice( 'referral_updated', array(
			'message' => __( 'Referral updated successfully.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'referral_update_failed', array(
			'message' => __( 'Referral update failed, please try again.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'referral_deleted', array(
			'message' => __( 'Referral deleted successfully.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'referral_delete_failed', array(
			'class'   => 'error',
			'message' => __( 'Referral deletion failed, please try again.', 'affiliate-wp' ),
		) );
	}

	/**
	 * Displays upgrade notices.
	 *
	 * @since 2.0
	 */
	public function upgrade_notices() {

		$this->add_notice( 'upgrade_v20_recount_unpaid_earnings',
			array(
				'class'   => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database needs to be upgraded following the AffiliateWP v2.0 update', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'recount-affiliate-stats-upgrade_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="recount-affiliate-stats-upgrade" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database', 'affiliate-wp' ), 'secondary', 'v20-recount-unpaid-earnings', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			) );

		$this->add_notice( 'upgrade_v22_create_customer_records',
			array(
				'class'   => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database needs to be upgraded following the AffiliateWP v2.2 update. Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'create-customers-upgrade_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="create-customers-upgrade" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database', 'affiliate-wp' ), 'secondary', 'v22-create-customers', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			)
		);

		$this->add_notice( 'upgrade_v245_create_customer_affiliate_relationship_records',
			array(
				'class'   => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database needs to be upgraded following the AffiliateWP v2.4.5 update. Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'create-customer-affiliate-relationship-upgrade_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="create-customer-affiliate-relationship-upgrade" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database', 'affiliate-wp' ), 'secondary', 'v245-create-customer-affiliate-relationship', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			)
		);

		$this->add_notice( 'upgrade_v26_create_dynamic_coupons',
			array(
				'class'   => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database needs to be upgraded following the AffiliateWP v2.6 update. Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'create-dynamic-coupons-upgrade_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="create-dynamic-coupons-upgrade" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database', 'affiliate-wp' ), 'secondary', 'v26-create-dynamic-coupons', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			)
		);

		$this->add_notice( 'upgrade_v261_utf8mb4_compat',
			array(
				'class' => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database tables need to be upgraded following the AffiliateWP v2.6.1 update. Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'upgrade-db-utf8mb4_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="upgrade-db-utf8mb4" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database Tables', 'affiliate-wp' ), 'secondary', 'v261-upgrade-db-utf8mb4', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			)
		);

		$this->add_notice( 'upgrade_v27_calculate_campaigns',
			array(
				'class' => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database tables need to be upgraded following the AffiliateWP v2.7 update. Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'recalculate-campaigns_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="recalculate-campaigns" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database Tables', 'affiliate-wp' ), 'secondary', 'recalculate-campaigns', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			)
		);

		$this->add_notice( 'upgrade_v274_calculate_campaigns',
			array(
				'class' => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database tables need to be upgraded following the AffiliateWP v2.7.4 update. Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'recalculate-campaigns_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="recalculate-campaigns" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database Tables', 'affiliate-wp' ), 'secondary', 'recalculate-campaigns', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			)
		);

		$this->add_notice( 'migrate_affiliate_user_meta',
			array(
				'class' => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database tables need to be upgraded following the AffiliateWP v2.8 update. Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'migrate-affiliate-user-meta_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="migrate-affiliate-user-meta" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database Tables', 'affiliate-wp' ), 'secondary', 'migrate-affiliate-user-meta_step_nonce', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			)
		);

		$this->add_notice( 'upgrade_v281_convert_failed_referrals',
			array(
				'class' => 'notice notice-info is-dismissible',
				'message' => function() {
					$notice = __( 'Your database tables need to be upgraded following the AffiliateWP v2.8.1 update. Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' );
					$nonce  = wp_create_nonce( 'upgrade-convert-failed-referrals_step_nonce' );

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>
					<p><?php echo $notice; ?></p>
					<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="upgrade-convert-failed-referrals" data-nonce="<?php echo esc_attr( $nonce ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database Tables', 'affiliate-wp' ), 'secondary', 'upgrade-convert-failed-referrals', false ); ?>
						</p>
					</form>
					<?php
					return ob_get_clean();
				},
			)
		);

		$this->add_notice(
			'upgrade_v2140_set_creative_type',
			array(
				'class'   => 'notice notice-info is-dismissible',
				'message' => function() {
					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>

					<p><?php esc_html_e( 'Your database needs to be upgraded following the AffiliateWP 2.14.0 update', 'affiliate-wp' ); ?></p>
					<form
						method="post"
						class="affwp-batch-form"
						data-dismiss-when-complete="true"
						data-batch_id="set-creative-type"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'set-creative-type_step_nonce' ) ); ?>">
						<p>
							<?php submit_button( __( 'Upgrade Database', 'affiliate-wp' ), 'secondary', 'v2140-set-creative-type', false ); ?>
						</p>
					</form>

					<?php

					return ob_get_clean();
				},
			)
		);

		// Try to show the creative name upgrade notice.
		$this->creative_name_upgrade_notice();

		// Try to show the notice to clean up legacy post meta.
		$this->clean_empty_registration_form_meta_notice();

		// Try to show the login and registration notice.
		$this->create_login_registration_pages_notice();
	}

	/**
	 * Displays an upgrade notice to remove legacy post meta from the database.
	 *
	 * @since 2.27.6
	 *
	 * @return void
	 */
	public function clean_empty_registration_form_meta_notice() : void {
		if ( ! affwp_is_admin_page() ) {
			return;
		}

		$this->add_notice(
			'upgrade_v2276_clean_empty_registration_form_meta',
			array(
				'class'   => 'notice notice-info is-dismissible',
				'message' => function() {
					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>

					<p><?php esc_html_e( 'Your database needs to be updated following the AffiliateWP 2.27.6 update. This upgrade will improve site performance by removing legacy post meta entries.', 'affiliate-wp' ); ?><br/><?php esc_html_e( 'Depending on the size of your database, this upgrade could take some time.', 'affiliate-wp' ); ?></p>
					<form
						method="post"
						class="affwp-batch-form"
						data-dismiss-when-complete="true"
						data-batch_id="clean-empty-registration-form-meta"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'clean-empty-registration-form-meta_step_nonce' ) ); ?>"
					>
						<p>
							<?php submit_button( __( 'Upgrade Database', 'affiliate-wp' ), 'secondary', 'v2276-clean-empty-registration-form-meta', false ); ?>
						</p>
					</form>

					<?php

					return ob_get_clean();
				},
			)
		);
	}

	/**
	 * Displays an upgrade notice for the Login and Registration pages upgrade.
	 *
	 * This notice will be displayed for users with no Login and Registration pages set yet.
	 * Two option are available, either to create the pages or dismiss and leave as it is.
	 *
	 * @since 2.25.0
	 */
	public function create_login_registration_pages_notice() {

		if ( ! affiliatewp_is_settings_page( 'affiliates' ) ) {
			return; // Shows only in the Affiliates settings tab.
		}

		$notice_id = 'upgrade_v2250_create_login_registration_pages';

		if ( affwp_has_upgrade_completed( $notice_id ) ) {
			return; // Bail if it was already completed.
		}

		$this->add_notice(
			$notice_id,
			[
				'class'      => 'notice notice-info is-dismissible',
				'capability' => 'edit_pages',
				'autop'      => false,
				'message'    => function() {

					ob_start();
					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();
					?>

					<h4><?php esc_html_e( 'New Page Options for Affiliate Login and Registration', 'affiliate-wp' ); ?></h4>
					<p>
						<?php
						echo wp_kses(
							sprintf(
								/* translators: 1: Link to the doc page for the affiliate login page. 2: Link to the doc page for the affiliate registration page. */
								__(
									'We have introduced two new page options to improve the user experience for your affiliates: separate <a href="%1$s" target="_blank" rel="noopener noreferrer">Affiliate Login</a> and <a href="%2$s" target="_blank" rel="noopener noreferrer">Affiliate Registration</a> pages.',
									'affiliate-wp'
								),
								esc_url( 'https://affiliatewp.com/docs/affiliate-login-page' ),
								esc_url( 'https://affiliatewp.com/docs/affiliate-registration-page' )
							),
							affwp_kses()
						);
						?>
					</p>
					<form
						method="post"
						class="affwp-batch-form affwp-batch-form--has-choices"
						data-dismiss-when-complete="true"
						data-batch_id="create-login-registration-pages"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'create-login-registration-pages_step_nonce' ) ); ?>">
						<p>
							<input id="create_login_registration_pages_choice_yes" data-button-label="<?php esc_html_e( 'Create Pages and Update Settings', 'affiliate-wp' ); ?>" type="radio" name="create_login_registration_pages_choice" value="yes">
							<label for="create_login_registration_pages_choice_yes"><?php echo wp_kses( __( 'Create new pages and update settings.', 'affiliate-wp' ), affwp_kses() ); ?></label>
						</p>

						<p>
							<input id="create_login_registration_pages_choice_no" data-button-label="<?php esc_html_e( 'Keep Current Setup and Dismiss', 'affiliate-wp' ); ?>" type="radio" name="create_login_registration_pages_choice" value="no">
							<label for="create_login_registration_pages_choice_no"><?php echo wp_kses( __( 'Keep my current setup.', 'affiliate-wp' ), affwp_kses() ); ?></label>
						</p>
						<p class="affwp-batch-form__submit-container">
							<?php

							submit_button(
								__( 'Create Pages and Update Settings', 'affiliate-wp' ),
								'secondary button-disabled',
								'v2250-create-login-registration-pages',
								false,
								'disabled="disabled"'
							);

							?>
							<noscript>
								<span class="dashicons dashicons-warning" style="vertical-align: middle"></span>
								<span><?php esc_html_e( 'Please enable Javascript to update.', 'affiliate-wp' ); ?></span>
							</noscript>
						</p>
					</form>

					<?php

					return ob_get_clean();
				},
			]
		);
	}

	/**
	 * Displays an upgrade notice for the Creative name improvements.
	 *
	 * This notice presents users with two options: keeping all Creative names public or private on the front-end.
	 * If the public option is chosen, the name will be displayed as it is.
	 * If the private option is chosen, all Creative names are set to `Creative`, and the original names are moved to the Notes field for future renaming.
	 * During the decision-making process, all Creatives are displayed as `Creative`.
	 *
	 * @since 2.16.0
	 *
	 * @return void
	 */
	public function creative_name_upgrade_notice() : void {

		if ( ! affwp_is_admin_page() ) {
			return; // Shows only under AffiliateWP screens.
		}

		$notice_id = 'upgrade_v2160_update_creative_names';

		if ( affwp_has_upgrade_completed( $notice_id ) ) {
			return; // Bail if it was already completed.
		}

		$creatives = affiliate_wp()->creatives->count();

		// If there are no creatives, we don't have reason to display the notice.
		if ( empty( $creatives ) ) {

			// Set as the upgrade was already completed.
			affwp_set_upgrade_complete( $notice_id );

			// Ensure all Creatives will be public.
			update_option( 'affwp_creative_name_privacy', 'public' );

			return;
		}

		$this->add_notice(
			$notice_id,
			array(
				'class'      => 'notice notice-warning',
				'capability' => 'manage_creatives',
				'message'    => function() {

					ob_start();

					// Enqueue admin JS for the batch processor.
					affwp_enqueue_admin_js();

					?>

					<h4><?php esc_html_e( 'Important Update in AffiliateWP 2.16.0: Creative Names Now Visible to Affiliates', 'affiliate-wp' ); ?></h4>
					<p><?php esc_html_e( 'Until now, the names of Creatives were only visible to affiliate managers. With our latest update, the names of all newly added Creatives will be visible to affiliates. This change will assist them in easily identifying and choosing the best Creative for their campaigns.', 'affiliate-wp' ); ?></p>
					<p><?php esc_html_e( 'Please note, until you select an option below, all Creatives will appear to affiliates as “Creative” within the Affiliate Area.', 'affiliate-wp' ); ?></p>
					<p><?php esc_html_e( 'Are you comfortable with allowing affiliates to see the names of your existing Creatives?', 'affiliate-wp' ); ?></p>
					<form
						method="post"
						class="affwp-batch-form"
						data-dismiss-when-complete="true"
						data-batch_id="update-creative-names"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'update-creative-names_step_nonce' ) ); ?>"
					>
						<p>
							<input id="creative_name_privacy_public" type="radio" name="creative_name_privacy" value="public">
							<label for="creative_name_privacy_public"><?php echo wp_kses( __( '<strong>Yes, Proceed:</strong> It’s acceptable for affiliates to see the names of my existing Creatives.', 'affiliate-wp' ), affwp_kses() ); ?></label>
						</p>

						<p>
							<input id="creative_name_privacy_private" type="radio" name="creative_name_privacy" value="private">
							<label for="creative_name_privacy_private"><?php echo wp_kses( __( '<strong>No, Review Needed:</strong> I want to manually review and rename all existing Creatives.', 'affiliate-wp' ), affwp_kses() ); ?></label>
						</p>

						<p><em><?php esc_html_e( '(Important: Selecting this option will rename all Creatives to “Creative”. The original names will be safely stored in a newly created Notes field for your reference and review.)', 'affiliate-wp' ); ?></em></p>

						<p>
							<?php

							submit_button(
								__( 'Make Creative Names Visible', 'affiliate-wp' ),
								'secondary button-disabled',
								'v2160-update-creative-names',
								false,
								'disabled="disabled"'
							);

							?>
							<noscript>
								<span class="dashicons dashicons-warning" style="vertical-align: middle"></span>
								<span><?php esc_html_e( 'Please enable Javascript to update.', 'affiliate-wp' ); ?></span>
							</noscript>
						</p>
					</form>

					<?php

					return ob_get_clean();
				},
			)
		);
	}

	/**
	 * Registers development-related notices.
	 *
	 * @since 2.8
	 */
	public function development_notices() {
		$this->add_notice( 'development_version', array(
			'class' => 'error',
			'message' => function() {
				$message  = sprintf( __( '<strong>Important:</strong> You have installed or updated to AffiliateWP <code>v%s</code>, a pre-release development version.', 'affiliate-wp' ),
					AFFILIATEWP_VERSION
				) . '<br />';
				$message .= sprintf( __( 'Development versions can sometimes be unstable and should <em>never</em> be tested on a live site. If you feel you may have installed this version of AffiliateWP in error, you can get a copy of the latest stable version from your <a href="%s" target="_blank">account page</a>.', 'affiliate-wp' ),
					'https://affiliatewp.com/account/?utm_campaign=admin&utm_source=development_version&utm_medium=error'
				);

				return $message;
			},
		) );
	}

	/**
	 * Display admin notices related to integrations.
	 *
	 * @since 2.1
	 * @since 2.4 Refactored to leverage the notices registry
	 *
	 * @return string|void Output if `$display_notices` is false, otherwise void.
	 */
	public function integration_notices() {
		$integrations = affiliate_wp()->integrations->query( array( 'supports' => 'sales_reporting' ) );

		// Loop through active integrations and post any notices for that integration.
		foreach ( $integrations as $context => $integration ) {
			$this->add_notice( $context . '_needs_synced',
				array(
					'class'   => 'notice notice-warning',
					'message' => function() use ( $context, $integration ) {
						/* translators: Integration name */
						$notice = sprintf( __( "AffiliateWP needs to import %s sales data. This will ensure AffiliateWP sales reports are accurate." ), $integration['name'] );
						$nonce  = wp_create_nonce( 'sync-integration-sales-data_step_nonce' );

						ob_start();
						// Enqueue admin JS for the batch processor.
						affwp_enqueue_admin_js();
						?>
						<p><?php echo $notice; ?></p>
						<form method="post" class="affwp-batch-form" data-dismiss-when-complete="true" data-batch_id="sync-integration-sales-data" data-nonce="<?php echo esc_attr( $nonce ); ?>">
							<input type="hidden" name="context" value="<?php echo esc_attr( $context ); ?>">
							<p>
							<?php submit_button( __( 'Sync', 'affiliate-wp' ), 'secondary', 'sync-integration-sales-data-' . $context, false ); ?>
							</p>
						</form>
						<?php
						return ob_get_clean();
					},
				)
			);
		}

		foreach ( affiliate_wp()->integrations->get_discontinued_integrations() as $slug => $info ) {

			if ( ! isset( $info['label'] ) ) {
				throw new \Exception( 'label must be set.' );
			}

			if ( ! isset( $info['date'] ) ) {
				throw new \Exception( 'date must be set.' );
			}

			$this->add_notice(
				"{$slug}_discontinued_integration_enabled",
				array(
					'class'   => 'notice notice-info',
					'message' => function() use ( $info ) {

						ob_start();

						?>

						<p>
							<?php

							echo wp_kses_post(
								sprintf(
									// Translators: %1$s is the name of the integration, %2$s is the date, and %3$s is the link to the documentation.
									__( 'AffiliateWP is ending official support for the <strong>%1$s</strong> %4$s beginning %2$s. See <a href="%3$s" target="_blank">this guide</a> for more information.', 'affiliate-wp' ),
									esc_html( $info['label'] ),
									esc_html( $info['date'] ),
									esc_url( 'https://affiliatewp.com/docs/discontinued-integrations/' ),
									$info['count'] ?? 0 > 1
										? __( 'integrations', 'affiliate-wp' )
										: __( 'integration', 'affiliate-wp' ),
								)
							);

							?>
						</p>

						<?php

						return ob_get_clean();
					},
				)
			);
		}
	}

	/**
	 * Registers settings admin notices.
	 *
	 * @since 2.4
	 */
	private function settings_notices() {
		$this->add_notice( 'settings-updated', array(
			'message' => __( 'Settings updated.', 'affiliate-wp' ),
		) );

		$this->add_notice( 'affiliates_migrated', array(
			'message' => function() {
				if ( ! class_exists( 'Affiliate_WP_Migrate_WP_Affiliate' ) ) {
					require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-migrate-wp-affiliate.php';
				}

				$total_affiliates = (int) Affiliate_WP_Migrate_WP_Affiliate::get_items_total( 'affwp_migrate_affiliates_total_count' );

				$message = sprintf( _n(
					/* translators: Singular number of affiliates added from WP Affiliate */
					'%d affiliate from WP Affiliate was added successfully.',
					/* translators: Plural number of affiliates added from WP Affiliate */
					'%d affiliates from WP Affiliate were added successfully',
					$total_affiliates,
					'affiliate-wp'
				), number_format_i18n( $total_affiliates ) );

				Affiliate_WP_Migrate_WP_Affiliate::clear_items_total( 'affwp_migrate_affiliates_total_count' );

				return $message;
			},
		) );

		$this->add_notice( 'affiliates_pro_migrated', array(
			'message' => function() {
				if ( ! class_exists( 'Affiliate_WP_Migrate_Affiliates_Pro' ) ) {
					require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-migrate-affiliates-pro.php';
				}

				$total_affiliates = (int) Affiliate_WP_Migrate_Affiliates_Pro::get_items_total( 'affwp_migrate_affiliates_pro_total_count' );

				$message = sprintf( _n(
					/* translators: Singular number of affiliates added from Affiliates Pro */
					'%d affiliate from Affiliates Pro was added successfully.',
					/* translators: Plural number of affiliates added from Affiliates Pro */
					'%d affiliates from Affiliates Pro were added successfully',
					$total_affiliates,
					'affiliate-wp'
				), number_format_i18n( $total_affiliates ) );

				Affiliate_WP_Migrate_Affiliates_Pro::clear_items_total( 'affwp_migrate_affiliates_pro_total_count' );

				return $message;
			},
		) );

		$this->add_notice( 'stats_recounted', array(
			'message' => __( 'Affiliate stats have been recounted!', 'affiliate-wp' ),
		) );

		$this->add_notice( 'settings-imported', array(
			'message' => __( 'Settings successfully imported', 'affiliate-wp' ),
		) );
	}

	/**
	 * Registers environment admin notices.
	 *
	 * @since 2.6
	 * @since 2.7.3 Updated for the PHP 7.0 soft bump
	 */
	public function environment_notices() {

		// Shows on single site when Send your affiliates a monthly email summary is enabled but WP Mail SMTP is not configured.
		if ( affwp_affiliate_email_summaries_enabled_without_wp_mail_smtp() && affwp_is_admin_page() ) {

			$this->add_notice(
				'wp_mail_smtp_not_configured',
				array(
					'message' => function() {
						return sprintf(
							// Translators: %1$s is a link to configure WP Mail SMTP and %2$s is going to be a link to our documentation.
							__( 'WP Mail SMTP is required to send affiliate email summaries. %1$s or learn more %2$s.', 'affiliate-wp' ),
							sprintf(
								'<a href="%1$s">%2$s</a>',
								admin_url( 'admin.php?page=affiliate-wp-smtp' ),
								__( 'Install and setup WP Mail SMTP', 'affiliate-wp' )
							),
							sprintf(
								'<a href="%1$s" rel="noopener noreferrer" target="_blank">%2$s</a>',
								'https://affiliatewp.com/docs/affiliate-email-summaries',
								__( 'in our documentation', 'affiliate-wp' )
							)
						);
					},
					'class'  => 'error',
				)
			);
		}

		// PHP Notice.
		$this->add_notice(
			'requirements_php_74',
			array(
				'dismissible' => true,
				'class'       => 'notice-warning',
				'message'     => function() {
					ob_start();

					?>

					<h3>
						<?php

						echo esc_html(
							sprintf(
								/* translators: Insecure PHP version */
								__( 'AffiliateWP has detected that your site is running on an insecure version of PHP (%s).', 'affiliate-wp' ),
								phpversion()
							)
						);

						?>
					</h3>

					<h4><?php esc_html_e( 'What is PHP, and how does it affect my site?' ); ?></h4>
					<p><?php esc_html_e( 'PHP is the programming language used to build and maintain WordPress and plugins like AffiliateWP. Newer versions of PHP are faster and more secure, so updating will positively affect your site’s performance.', 'affiliate-wp' ); ?></p>

					<h4><?php esc_html_e( 'Why it matters for AffiliateWP', 'affiliate-wp' ); ?></h4>
					<p><?php esc_html_e( 'As we evolve, our ability to tap into more modern PHP features means we can continue delivering a superior product.', 'affiliate-wp' ); ?></p>

					<p>
						<?php

						echo wp_kses(
							sprintf(
								'<strong>%s.</strong>',
								__( 'Starting February 28th, 2023, we require PHP 7.4 or newer to use AffiliateWP', 'affiliate-wp' )
							),
							array(
								'strong' => true,
							)
						);

						?>
					</p>

					<p class="button-container">
						<?php
						printf(
							'<a class="button button-primary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
							esc_url( wp_get_update_php_url() ),
							esc_html__( 'Learn more about updating PHP', 'affiliate-wp' ),
							/* translators: Accessibility text. */
							esc_html__( '(opens in a new tab)', 'affiliate-wp' )
						);
						?>
					</p>
					<?php

					wp_update_php_annotation();
					wp_direct_php_update_button();

					return ob_get_clean();
				},
			)
		);
	}

	/**
	 * Display admin notices related to addons.
	 *
	 * @since 2.9.6
	 */
	public function addons_notices() {
		// Bail if not the Addons page.
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'affiliate-wp-add-ons' ) {
			return;
		}

		// Check license status.
		$license_status = ( new License\License_Data() )->check_status();

		$this->add_notice( 'requires_upgrade_to_download', array(
			'class'   => 'error',
			'message' => function() {
				return sprintf(
					wp_kses( /* translators: %s - AffiliateWP.com account downloads page URL. */
						__( 'In order to get access to Addons, you need to <a href="%s">renew your license</a>.', 'affiliate-wp' ),
						[
							'a' => [
								'href' => [],
							],
						]
					),
					esc_url( 'https://affiliatewp.com/account/downloads/?utm_source=WordPress&utm_medium=addons&utm_campaign=plugin&utm_content=renew%20your%20license' )
				);
			}
		) );

		// Only shows on the Addons page if license needs to be renewed.
		if ( 'expired' === $license_status ) {
			self::show_notice( 'requires_upgrade_to_download' );
		}

		// Bail if the license is set.
		if  ( 'invalid' !== $license_status ) {
			return;
		}

		$this->add_notice( 'no_addon_access', array(
			'class'   => 'error',
			'message' => function() {
				return sprintf(
					wp_kses( /* translators: %s - AffiliateWP plugin settings URL. */
						__( 'To access addons please enter and activate your AffiliateWP license key in the plugin <a href="%s">settings</a>.', 'affiliate-wp' ),
						[
							'a' => [
								'href' => [],
							],
						]
					),
					esc_url_raw( add_query_arg( [ 'page' => 'affiliate-wp-settings' ], admin_url( 'admin.php' ) ) )
				);
			}
		) );

		// Only shows on the Addons page if no license is set.
		self::show_notice( 'no_addon_access' );
	}

	/**
	 * Processes message data for output as admin notices.
	 *
	 * @since 2.1
	 * @since 2.4 Refactored to handle for multiple classes and for message to be a callable
	 * @since 2.25.0 Added autop as a parameter to control if we want to automatically format content or not.
	 *
	 * @param string|callable $message      Notice message.
	 * @param string|array    $class        Notice class or array of classes.
	 * @param string          $extra_output Optional. Extra output to append to the end of the message.
	 *                                      Default empty.
	 * @param bool            $autop        Whether automatically format paragraphs.
	 * @return string Notice markup or empty string if `$message` is empty.
	 */
	public static function prepare_message_for_output( $message, $class, string $extra_output = '', bool $autop = true ) : string {

		if ( empty( $message ) ) {
			return '';
		}

		if ( is_callable( $message ) ) {
			$message = call_user_func( $message );
		}

		if ( ! empty( $extra_output ) ) {
			$message .= $extra_output;
		}

		// Convert string class to array.
		$classes = is_array( $class ) ? $class : array( $class );

		// Add is-dismissible if there's a dismiss button.
		if ( strpos( $extra_output, 'notice-dismiss' ) !== false ) {
			$classes[] = 'is-dismissible';
		}

		if ( $autop ) {
			$message = str_replace( "\n", '', wpautop( $message, false ) ); // wpautop() pads the end.
		}

		return sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( implode( ' ', $classes ) ),
			$message
		);
	}

	/**
	 * Prepares notice dismissal markup.
	 *
	 * @since 2.4
	 *
	 * @param string $notice_id Notice ID.
	 * @param string $label     Label.
	 * @return string HTML dismissal markup.
	 */
	public static function prepare_dismiss_output( $notice_id, $label ) {
		$notice_query_args = array(
			'affwp_action' => 'dismiss_notices',
			'affwp_notice' => $notice_id,
		);

		$url = wp_nonce_url( add_query_arg( $notice_query_args ), 'affwp_dismiss_notice', 'affwp_dismiss_notice_nonce' );

		return sprintf(
			'<button type="button" class="notice-dismiss affwp-notice-dismiss" data-dismiss-url="%1$s"><span class="screen-reader-text">%2$s</span></button>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	/**
	 * Dismisses promos via Ajax.
	 *
	 * @since 2.7
	 */
	public function dismiss_promo() {
		$notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( $_POST['notice_id'] ) : false;
		$nonce     = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : false;

		if ( ! current_user_can( 'manage_options' ) ) {
			return wp_send_json_error();
		}

		if ( ! wp_verify_nonce( $nonce, 'affwp-dismiss-notice-' . $notice_id ) ) {
			return wp_send_json_error();
		}

		$lifespan  = isset( $_POST['lifespan'] ) ? sanitize_text_field( $_POST['lifespan'] ) : '';

		Persistent_Dismissible::set( array(
			'id'   => $notice_id,
			'life' => $lifespan,
		) );

		wp_send_json_success();
	}

	/**
	 * Dismisses admin notices when Dismiss links are clicked.
	 *
	 * @since 1.7.5
	 */
	public function dismiss_notices() {
		if( ! isset( $_GET['affwp_dismiss_notice_nonce'] ) || ! wp_verify_nonce( $_GET['affwp_dismiss_notice_nonce'], 'affwp_dismiss_notice') ) {
			wp_die( __( 'Security check failed', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 403 ) );
		}

		if ( isset( $_GET['affwp_notice'] ) ) {

			$notice = sanitize_key( $_GET['affwp_notice'] );

			switch( $notice ) {
				case 'drm':
					set_transient( 'affwp_drm_notice', true, \AffiliateWP\Admin\DRM\DRM_Controller::NOTICE_DISMISS_TIMEOUT );
					break;
				case 'payouts_service':
					set_transient( 'affwp_payouts_service_notice', true, 2 * WEEK_IN_SECONDS );
					break;
				case 'requirements_php_74':
					set_transient( 'affwp_requirements_php_74_notice', true, 2 * WEEK_IN_SECONDS );
				default:
					/**
					break;
					 * Fires once a notice has been flagged for dismissal.
					 *
					 * @since 1.8 as 'affwp_dismiss_notices'
					 * @since 2.0.4 Renamed to 'affwp_dismiss_notices_default' to avoid a dynamic hook conflict.
					 *
					 * @param string $notice Notice value via $_GET['affwp_notice'].
					 */
					do_action( 'affwp_dismiss_notices_default', $notice );
					break;
			}

			wp_redirect( remove_query_arg( array( 'affwp_action', 'affwp_notice' ) ) );
			exit;
		}
	}

	/**
	 * Helper method to add a notice to the registry.
	 *
	 * @since 2.4
	 *
	 * @param string $notice_id Notice ID.
	 * @param array  $notice_args Notice attributes.
	 * @return true|WP_Error True if successful, otherwise a WP_Error object.
	 */
	public function add_notice( $notice_id, $notice_args ) {
		return self::$registry->add_notice( $notice_id, $notice_args );
	}

	/**
	 * Renders a registered admin notice.
	 *
	 * @since 2.4
	 *
	 * @see Affiliate_WP_Admin_Notices::prepare_message_for_output()
	 *
	 * @param string|callable|array $notice Notice ID, callback to generate output on the fly, or an
	 *                                      array with on-the-fly notice 'message' and 'class' keys.
	 *                                      If passing a callable, the method assumes you've handled
	 *                                      preparation of the message on your own.
	 *                                      See {@see Affiliate_WP_Admin_Notices::prepare_message_for_output()}.
	 * @param bool                  $echo   Optional. Whether to echo the notice. Default true.
	 * @return void|string Void if `$echo` is true, otherwise a string.
	 */
	public static function show_notice( $notice, $echo = true ) {
		$output = '';

		if ( is_callable( $notice ) ) {
			$output = call_user_func( $notice );
		} else {
			// If a notice ID was passed, get its attributes.
			if ( is_string( $notice ) ) {
				$notice_id = $notice;
				$notice    = self::$registry->get( $notice );

				if ( false !== $notice ) {
					$notice['notice_id'] = $notice_id;
				}
			}

			$capability = empty( $notice['capability'] ) ? 'manage_affiliates' : $notice['capability'];

			if ( false !== $notice && current_user_can( $capability )
				&& ! empty( $notice['message'] ) && ! empty( $notice['class'] )
			) {
				if ( isset( $notice['dismissible'] ) && true === $notice['dismissible'] ) {
					$label = empty( $notice['dismiss_label'] ) ? _x( 'Dismiss', 'admin notice', 'affiliate-wp' ) : $notice['dismiss_label'];

					$extra_output = self::prepare_dismiss_output( $notice_id, $label );
				} else {
					$extra_output = '';
				}

				$output .= self::prepare_message_for_output(
					$notice['message'],
					$notice['class'],
					$extra_output,
					$notice['autop']
				);
			}
		}

		if ( true === $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Sets the display_notices property for unit testing purposes.
	 *
	 * If set to false, notice output will be returned rather than echoed.
	 *
	 * @since 2.1
	 *
	 * @param bool $display Whether to display notice output.
	 */
	public function set_display_notices( $display ) {
		$this->display_notices = (bool) $display;
	}

	/**
	 * Helper to retrieve a single notice from the registry.
	 *
	 * @since 2.4
	 *
	 * @param string $notice_id Notice ID.
	 * @return array|false Array of notice attributes if it exists, otherwise false.
	 */
	public function get_notice( $notice_id ) {
		return self::$registry->get( $notice_id );
	}

	/**
	 * Helper to retrieve all notices from the registry.
	 *
	 * @since 2.4
	 *
	 * @param bool $keys_only Optional. Whether to retrieve the notice IDs only. Default false.
	 * @return array Array of notices and their attributes. If `$keys_only` is true, an array of notice IDs.
	 */
	public function get_all_notices( $keys_only = false ) {
		$notices = self::$registry->get_items();

		if ( false !== $notices ) {
			$notices = array_keys( $notices );
		}

		return $notices;
	}

	/**
	 * Retrieves the current instance of the notices registry.
	 *
	 * @return \AffWP\Admin\Notices_Registry
	 */
	public function get_registry() {
		return self::$registry;
	}

	/**
	 * Retrieves the current instance of the notices registry (statically).
	 *
	 * @since 2.27.4
	 *
	 * @return \AffWP\Admin\Notices_Registry
	 */
	public static function get_registry_statically() : \AffWP\Admin\Notices_Registry {
		return self::$registry;
	}

	/**
	 * Removes non-AffiliateWP notices from AffiliateWP admin screens.
	 *
	 * @since 2.25.0
	 *
	 * @return void Early exit if not on an AffiliateWP screen.
	 */
	public function hide_non_affiliatewp_notices() : void {

		// // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Check if we're on an AffiliateWP screen, exit if not.
		if ( empty( $_REQUEST['page'] ) || strpos( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'affiliate-wp' ) === false ) {
			return;
		}

		global $wp_filter;

		// User admin notices.
		if ( ! empty( $wp_filter['user_admin_notices']->callbacks ) && is_array( $wp_filter['user_admin_notices']->callbacks ) ) {
			$this->remove_unwanted_notices( $wp_filter['user_admin_notices'] );
		}

		// Admin notices.
		if ( ! empty( $wp_filter['admin_notices']->callbacks ) && is_array( $wp_filter['admin_notices']->callbacks ) ) {
			$this->remove_unwanted_notices( $wp_filter['admin_notices'] );
		}

		// All admin notices.
		if ( ! empty( $wp_filter['all_admin_notices']->callbacks ) && is_array( $wp_filter['all_admin_notices']->callbacks ) ) {
			$this->remove_unwanted_notices( $wp_filter['all_admin_notices'] );
		}
	}

	/**
	 * Helper function to remove unwanted notices.
	 *
	 * @since 2.25.0
	 *
	 * @param WP_Hook $notices The notices object.
	 * @return void
	 */
	private function remove_unwanted_notices( $notices ) : void {

		foreach ( $notices->callbacks as $priority => $hooks ) {

			foreach ( $hooks as $name => $callback ) {
				// Remove closures, they're not target specific functions or classes.
				if ( is_object( $callback['function'] ) && $callback['function'] instanceof Closure ) {
					unset( $notices->callbacks[ $priority ][ $name ] );
					continue;
				}

				$keep_notice = false;

				// Check if the callback is from an object and the class name starts with "AffiliateWP" or "Affiliate_WP".
				if ( ! empty( $callback['function'][0] ) && is_object( $callback['function'][0] ) ) {
					$class_name = strtolower( get_class( $callback['function'][0] ) );
					if ( strpos( $class_name, 'affiliatewp' ) === 0 || strpos( $class_name, 'affiliate_wp' ) === 0 ) {
						$keep_notice = true;
					}
				}

				// Additionally check if the hook name starts with "affwp".
				if ( ! $keep_notice && ! empty( $name ) && strpos( $name, 'affwp' ) !== false ) {
					$keep_notice = true;
				}

				// If the notice does not match the criteria to be kept, remove it.
				if ( ! $keep_notice ) {
					unset( $notices->callbacks[ $priority ][ $name ] );
				}
			}
		}
	}

}

new Affiliate_WP_Admin_Notices();
