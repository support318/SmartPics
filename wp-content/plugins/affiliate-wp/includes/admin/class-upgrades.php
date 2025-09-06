<?php
/**
 * Upgrades API
 *
 * @package     AffiliateWP
 * @subpackage  Utilities
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 *
 * @see includes/utils/traits/trait-db.php `upgrade_table()` for alternative usage for
 *      upgrading database tables vs. using legacy methods for doing so here.
 */

affwp_require_util_traits( 'db', 'data' );

#[\AllowDynamicProperties]

/**
 * Core class for handling upgrade operations.
 *
 * @since 1.0.0
 */
class Affiliate_WP_Upgrades {

	use \AffiliateWP\Utils\DB;
	use \AffiliateWP\Utils\Data;

	/**
	 * Whether debug mode is enabled.
	 *
	 * @since 1.8.6
	 * @access private
	 * @var bool
	 */
	private $debug;

	/**
	 * Affiliate_WP_Logging instance.
	 *
	 * @since 1.8.6
	 * @access private
	 * @var Affiliate_WP_Logging
	 */
	private $logs;

	/**
	 * Signals whether the upgrade was successful.
	 *
	 * @access public
	 * @var    bool
	 */
	private $upgraded = false;

	/**
	 * AffiliateWP version.
	 *
	 * @access private
	 * @since  2.0
	 * @var    string
	 */
	private $version;

	/**
	 * Utilities class instance.
	 *
	 * @access private
	 * @since  2.0
	 * @var    \Affiliate_WP_Utilities
	 */
	private $utils;

	/**
	 * Upgrade routine registry.
	 *
	 * @access private
	 * @since  2.0.5
	 * @var    \AffWP\Utils\Upgrades\Registry
	 */
	private $registry;

	/**
	 * Sets up the Upgrades class instance.
	 *
	 * @access public
	 *
	 * @param \Affiliate_WP_Utilities $utils Utilities class instance.
	 */
	public function __construct( $utils ) {

		$this->utils    = $utils;
		$this->version  = get_option( 'affwp_version' );
		$this->registry = new \AffWP\Utils\Upgrades\Registry();

		add_action( 'init', [ $this, 'init' ], -9999 );

		add_action( 'init', [ $this, 'schedule_v2270_upgrade' ], 20 );
		add_action( 'affwp_schedule_v2270_upgrade_batches', [ $this, 'schedule_v2270_upgrade_batches' ] );
		add_action( 'affwp_v2270_process_affiliate_batch', [ $this, 'process_v2270_affiliate_batch' ], 10, 3 );

		$settings    = new Affiliate_WP_Settings();
		$this->debug = (bool) $settings->get( 'debug_mode', false );

		$this->register_core_upgrades();
	}

	/**
	 * Initializes upgrade routines for the current version of AffiliateWP.
	 *
	 * @access public
	 */
	public function init() {

		if ( empty( $this->version ) ) {
			$this->version = '1.0.6'; // last version that didn't have the version option set
		}

		if ( version_compare( $this->version, '1.1', '<' ) ) {
			$this->v11_upgrades();
		}

		if ( version_compare( $this->version, '1.2.1', '<' ) ) {
			$this->v121_upgrades();
		}

		if ( version_compare( $this->version, '1.3', '<' ) ) {
			$this->v13_upgrades();
		}

		if ( version_compare( $this->version, '1.6', '<' ) ) {
			$this->v16_upgrades();
		}

		if ( version_compare( $this->version, '1.7', '<' ) ) {
			$this->v17_upgrades();
		}

		if ( version_compare( $this->version, '1.7.3', '<' ) ) {
			$this->v173_upgrades();
		}

		if ( version_compare( $this->version, '1.7.11', '<' ) ) {
			$this->v1711_upgrades();
		}

		if ( version_compare( $this->version, '1.7.14', '<' ) ) {
			$this->v1714_upgrades();
		}

		if ( version_compare( $this->version, '1.9', '<' ) ) {
			$this->v19_upgrade();
		}

		if ( version_compare( $this->version, '1.9.5', '<' ) ) {
			$this->v195_upgrade();
		}

		if ( true === version_compare( AFFILIATEWP_VERSION, '2.0', '<' ) ) {
			$this->v20_upgrade();
		}

		if ( version_compare( $this->version, '2.0.2', '<' ) ) {
			$this->v202_upgrade();
		}

		if ( version_compare( $this->version, '2.0.10', '<' ) ) {
			$this->v210_upgrade();
		}

		if ( version_compare( $this->version, '2.1', '<' ) ) {
			$this->v21_upgrade();
		}

		if ( version_compare( $this->version, '2.1.3.1', '<' ) ) {
			$this->v2131_upgrade();
		}

		if ( version_compare( $this->version, '2.2', '<' ) ) {
			$this->v22_upgrade();
		}

		if ( version_compare( $this->version, '2.2.2', '<' ) ) {
			$this->v222_upgrade();
		}

		if ( version_compare( $this->version, '2.2.8', '<' ) ) {
			$this->v228_upgrade();
		}

		if ( version_compare( $this->version, '2.2.9', '<' ) ) {
			$this->v229_upgrade();
		}

		if ( version_compare( $this->version, '2.3', '<' ) ) {
			$this->v23_upgrade();
		}

		if ( version_compare( $this->version, '2.4', '<' ) ) {
			$this->v24_upgrade();
		}

		if ( version_compare( $this->version, '2.4.2', '<' ) ) {
			$this->v242_upgrade();
		}

		if ( version_compare( $this->version, '2.5', '<' ) ) {
			$this->v25_upgrade();
		}

		if ( version_compare( $this->version, '2.6', '<' ) ) {
			$this->v26_upgrade();
		}

		if ( version_compare( $this->version, '2.7', '<' ) ) {
			$this->v27_upgrade();
		}

		if ( version_compare( $this->version, '2.7.4', '<' ) ) {
			$this->v274_upgrade();
		}

		if ( version_compare( $this->version, '2.8', '<' ) ) {
			$this->v28_upgrade();
		}

		if ( version_compare( $this->version, '2.9', '<' ) ) {
			$this->v29_upgrade();
		}

		if ( version_compare( $this->version, '2.9.5', '<' ) ) {
			$this->v295_upgrade();
		}

		if ( version_compare( $this->version, '2.9.6', '<' ) ) {
			$this->v296_upgrade();
		}

		if ( version_compare( $this->version, '2.9.6.1', '<' ) ) {
			$this->v2961_upgrade();
		}

		if ( version_compare( $this->version, '2.11.0', '<' ) ) {
			$this->v2110_upgrade();
		}

		if ( version_compare( $this->version, '2.14.0', '<' ) ) {
			$this->v2140_upgrade();
		}

		if ( version_compare( $this->version, '2.15.0', '<' ) ) {
			$this->v2150_upgrade();
		}

		if ( version_compare( $this->version, '2.16.0', '<' ) ) {
			$this->v2160_upgrade();
		}

		if ( version_compare( $this->version, '2.16.3', '<' ) ) {
			$this->v2163_upgrade();
		}

		if ( version_compare( $this->version, '2.17.0', '<' ) ) {
			$this->v2170_upgrade();
		}

		if ( version_compare( $this->version, '2.18.0', '<' ) ) {
			$this->v2180_upgrade();
		}

		if ( version_compare( $this->version, '2.27.0', '<' ) ) {
			$this->v2270_upgrade();
		}

		if ( version_compare( $this->version, '2.28.0', '<' ) ) {
			$this->upgrade_v2280_migrate_captcha_settings();
		}

		// Inconsistency between current and saved version.
		if ( version_compare( $this->version, AFFILIATEWP_VERSION, '<>' ) ) {
			$this->upgraded = true;
		}

		// If upgrades have occurred.
		if ( $this->upgraded ) {

			update_option( 'affwp_version_upgraded_from', $this->version );
			update_option( 'affwp_version', AFFILIATEWP_VERSION );
		}
	}

	/**
	 * Registers core upgrade routines.
	 *
	 * @access private
	 * @since  2.0.5
	 *
	 * @see \Affiliate_WP_Upgrades::add_routine()
	 */
	private function register_core_upgrades() {
		$this->add_routine(
			'upgrade_v20_recount_unpaid_earnings',
			[
				'version'       => '2.0',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'recount-affiliate-stats-upgrade',
					'class' => 'AffWP\Utils\Batch_Process\Upgrade_Recount_Stats',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/upgrades/class-batch-upgrade-recount-affiliate-stats.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v22_create_customer_records',
			[
				'version'       => '2.2',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'create-customers-upgrade',
					'class' => 'AffWP\Utils\Batch_Process\Upgrade_Create_Customers',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/upgrades/class-batch-upgrade-create-customers.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v245_create_customer_affiliate_relationship_records',
			[
				'version'       => '2.4.5',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'create-customer-affiliate-relationship-upgrade',
					'class' => 'AffWP\Utils\Batch_Process\Upgrade_Create_Customer_Affiliate_Relationship',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/upgrades/class-batch-upgrade-create-customer-affiliate-relationship.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v26_create_dynamic_coupons',
			[
				'version'       => '2.6',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'create-dynamic-coupons-upgrade',
					'class' => 'AffWP\Utils\Batch_Process\Upgrade_Create_Dynamic_Coupons',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/upgrades/class-batch-upgrade-create-dynamic-coupons.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v261_utf8mb4_compat',
			[
				'version'       => '2.6.1',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'upgrade-db-utf8mb4',
					'class' => 'AffWP\Utils\Batch_Process\Upgrade_Database_ut8mb4_Compat',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/upgrades/class-batch-upgrade-db-utf8mb4.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v27_calculate_campaigns',
			[
				'version' => '2.7',
				'compare' => '<',
			]
		);

		$this->add_routine(
			'upgrade_v274_calculate_campaigns',
			[
				'version' => '2.7.4',
				'compare' => '<',
			]
		);

		$this->add_routine(
			'migrate_affiliate_user_meta',
			[
				'version'       => '2.8',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'migrate-affiliate-user-meta',
					'class' => 'AffWP\Utils\Batch_Process\Batch_Migrate_Affiliate_User_Meta',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-batch-migrate-affwp-user-meta.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v281_convert_failed_referrals',
			[
				'version'       => '2.8.1',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'upgrade-convert-failed-referrals',
					'class' => 'AffWP\Utils\Batch_Process\Batch_Upgrade_Convert_Failed_Referrals',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/upgrades/class-batch-upgrade-convert-failed-referrals.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v2140_set_creative_type',
			[
				'version'       => '2.14.0',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'set-creative-type',
					'class' => 'AffWP\Utils\Batch_Process\Batch_Set_Creative_Type',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-batch-set-creative-type.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v2160_update_creative_names',
			[
				'version'       => '2.16.0',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'update-creative-names',
					'class' => 'AffWP\Utils\Batch_Process\Batch_Update_Creative_Names',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-batch-update-creative-names.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v2276_clean_empty_registration_form_meta',
			[
				'version'       => '2.27.6',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'clean-empty-registration-form-meta',
					'class' => 'AffWP\Utils\Batch_Process\Batch_Clean_Empty_Registration_Form_Meta',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-batch-clean-empty-registration-form-meta.php',
				],
			]
		);

		$this->add_routine(
			'upgrade_v2250_create_login_registration_pages',
			[
				'version'       => '2.25.0',
				'compare'       => '<',
				'batch_process' => [
					'id'    => 'create-login-registration-pages',
					'class' => 'AffWP\Utils\Batch_Process\Batch_Create_Login_Registration_Pages',
					'file'  => AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-batch-create-login-registration-pages.php',
				],
			]
		);
	}

	/**
	 * Registers a new upgrade routine.
	 *
	 * @access public
	 * @since  2.0.5
	 *
	 * @param string $upgrade_id Upgrade ID.
	 * @param array  $args {
	 *     Arguments for registering a new upgrade routine.
	 *
	 *     @type string $version       Version the upgrade routine should be run against.
	 *     @type string $compare       Comparison operator to use when determining if the routine
	 *                                 should be executed.
	 *     @type array  $batch_process {
	 *         Optional. Arguments for registering a batch process.
	 *
	 *         @type string $id    Batch process ID.
	 *         @type string $class Batch processor class to use.
	 *         @type string $file  File containing the batch processor class.
	 *     }
	 * }
	 * @return bool True if the upgrade routine was added, otherwise false.
	 */
	public function add_routine( $upgrade_id, $args ) {
		// Register the batch process if one has been defined.
		if ( ! empty( $args['batch_process'] ) ) {

			$utils = $this->utils;
			$batch = $args['batch_process'];

			// Log an error if it's too late to register the batch process.
			if ( did_action( 'affwp_batch_process_init' ) ) {

				$utils->log(
					sprintf(
						'The %s batch process was registered too late. Registrations must occur while/before <code>affwp_batch_process_init</code> fires.',
						esc_html( $args['batch_process']['id'] )
					)
				);

				return false;

			} else {

				add_action(
					'affwp_batch_process_init',
					function () use ( $utils, $batch ) {
						$utils->batch->register_process(
							$batch['id'],
							[
								'class' => $batch['class'],
								'file'  => $batch['file'],
							]
						);
					}
				);

			}

			unset( $args['batch_process'] );
		}

		// Add the routine to the registry.
		return $this->registry->add_upgrade( $upgrade_id, $args );
	}

	/**
	 * Retrieves an upgrade routine from the registry.
	 *
	 * @access public
	 * @since  2.0.5
	 *
	 * @param string $upgrade_id Upgrade ID.
	 * @return array|false Upgrade entry from the registry, otherwise false.
	 */
	public function get_routine( $upgrade_id ) {
		return $this->registry->get( $upgrade_id );
	}

	/**
	 * Writes a log message.
	 *
	 * @access private
	 * @since 1.8.6
	 *
	 * @param string $message Optional. Message to log.
	 */
	private function log( $message = '' ) {
		$this->utils->log( $message );
	}

	/**
	 * Perform database upgrades for version 1.1
	 *
	 * @access  private
	 * @since   1.1
	 */
	private function v11_upgrades() {

		@affiliate_wp()->affiliates->create_table();

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 1.2.1
	 *
	 * @access  private
	 * @since   1.2.1
	 */
	private function v121_upgrades() {

		@affiliate_wp()->creatives->create_table();

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 1.3
	 *
	 * @access  private
	 * @since   1.3
	 */
	private function v13_upgrades() {

		@affiliate_wp()->creatives->create_table();

		// Clear rewrite rules
		flush_rewrite_rules();

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 1.6
	 *
	 * @access  private
	 * @since   1.6
	 */
	private function v16_upgrades() {

		@affiliate_wp()->affiliate_meta->create_table();
		@affiliate_wp()->referrals->create_table();

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 1.7
	 *
	 * @access  private
	 * @since   1.7
	 */
	private function v17_upgrades() {

		@affiliate_wp()->referrals->create_table();
		@affiliate_wp()->visits->create_table();
		@affiliate_wp()->campaigns->create_view();

		$this->v17_upgrade_referral_rates();

		$this->v17_upgrade_gforms();

		$this->v17_upgrade_nforms();

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 1.7.3
	 *
	 * @access  private
	 * @since   1.7.3
	 */
	private function v173_upgrades() {

		$this->v17_upgrade_referral_rates();

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for referral rates in version 1.7
	 *
	 * @access  private
	 * @since   1.7
	 */
	private function v17_upgrade_referral_rates() {

		global $wpdb;

		$prefix  = ( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) ? null : $wpdb->prefix;
		$results = $wpdb->get_results( "SELECT affiliate_id, rate FROM {$prefix}affiliate_wp_affiliates WHERE rate_type = 'percentage' AND rate > 0 AND rate <= 1;" );

		if ( $results ) {
			foreach ( $results as $result ) {
				$wpdb->update(
					"{$prefix}affiliate_wp_affiliates",
					[ 'rate' => floatval( $result->rate ) * 100 ],
					[ 'affiliate_id' => $result->affiliate_id ],
					[ '%d' ],
					[ '%d' ]
				);
			}
		}

		$settings  = get_option( 'affwp_settings' );
		$rate_type = ! empty( $settings['referral_rate_type'] ) ? $settings['referral_rate_type'] : null;
		$rate      = isset( $settings['referral_rate'] ) ? $settings['referral_rate'] : 20;

		if ( 'percentage' !== $rate_type ) {
			return;
		}

		if ( $rate > 0 && $rate <= 1 ) {
			$settings['referral_rate'] = floatval( $rate ) * 100;
		} elseif ( '' === $rate || '0' === $rate || '0.00' === $rate ) {
			$settings['referral_rate'] = 0;
		} else {
			$settings['referral_rate'] = floatval( $rate );
		}

		// Update settings.
		affiliate_wp()->settings->set( $settings, $save = true );
	}

	/**
	 * Perform database upgrades for Gravity Forms in version 1.7
	 *
	 * @access  private
	 * @since   1.7
	 */
	private function v17_upgrade_gforms() {

		$settings = get_option( 'affwp_settings' );

		if ( empty( $settings['integrations'] ) || ! array_key_exists( 'gravityforms', $settings['integrations'] ) ) {
			return;
		}

		global $wpdb;

		$tables = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}rg_form%';" );

		if ( ! $tables ) {
			return;
		}

		$forms = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}rg_form;" );

		if ( ! $forms ) {
			return;
		}

		foreach ( $forms as $form ) {

			$meta = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT display_meta FROM {$wpdb->prefix}rg_form_meta WHERE form_id = %d;",
					$form->id
				)
			);

			$meta = json_decode( $meta );

			if ( isset( $meta->gform_allow_referrals ) ) {
				continue;
			}

			$meta->gform_allow_referrals = 1;

			$meta = json_encode( $meta );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}rg_form_meta SET display_meta = %s WHERE form_id = %d;",
					$meta,
					$form->id
				)
			);

		}
	}

	/**
	 * Perform database upgrades for Ninja Forms in version 1.7
	 *
	 * @access  private
	 * @since   1.7
	 */
	private function v17_upgrade_nforms() {

		$settings = get_option( 'affwp_settings' );

		if ( empty( $settings['integrations'] ) || ! array_key_exists( 'ninja-forms', $settings['integrations'] ) ) {
			return;
		}

		global $wpdb;

		$tables = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}nf_object%';" );

		if ( ! $tables ) {
			return;
		}

		$forms = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}nf_objects WHERE type = 'form';" );

		if ( ! $forms ) {
			return;
		}

		// There could be forms that already have this meta saved in the DB, we will ignore those
		$_forms = $wpdb->get_results( "SELECT object_id FROM {$wpdb->prefix}nf_objectmeta WHERE meta_key = 'affwp_allow_referrals';" );

		$forms  = wp_list_pluck( $forms, 'id' );
		$_forms = wp_list_pluck( $_forms, 'object_id' );
		$forms  = array_diff( $forms, $_forms );

		if ( ! $forms ) {
			return;
		}

		foreach ( $forms as $form_id ) {

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}nf_objectmeta (object_id,meta_key,meta_value) VALUES (%d,'affwp_allow_referrals','1');",
					$form_id
				)
			);

		}
	}

	/**
	 * Perform database upgrades for version 1.7.11
	 *
	 * @access  private
	 * @since   1.7.11
	 */
	private function v1711_upgrades() {

		$settings = affiliate_wp()->settings->get_all();

		// Ensures settings are not lost if the duplicate email/subject fields were used before they were removed
		if ( ! empty( $settings['rejected_email'] ) && empty( $settings['rejection_email'] ) ) {
			$settings['rejection_email'] = $settings['rejected_email'];
			unset( $settings['rejected_email'] );
		}

		if ( ! empty( $settings['rejected_subject'] ) && empty( $settings['rejection_subject'] ) ) {
			$settings['rejection_subject'] = $settings['rejected_subject'];
			unset( $settings['rejected_subject'] );
		}

		// Update settings.
		affiliate_wp()->settings->set( $settings, $save = true );

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 1.7.14
	 *
	 * @access  private
	 * @since   1.7.14
	 */
	private function v1714_upgrades() {

		@affiliate_wp()->visits->create_table();

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 1.9.
	 *
	 * @since 1.9
	 * @access private
	 */
	private function v19_upgrade() {
		@affiliate_wp()->referrals->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The Referrals table upgrade for 1.9 has completed.' );

		@affiliate_wp()->affiliates->payouts->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The Payouts table creation process for 1.9 has completed.' );

		@affiliate_wp()->REST->consumers->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The API consumers table creation process for 1.9 has completed' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 1.9.5.
	 *
	 * @since 1.9.5
	 * @access private
	 */
	private function v195_upgrade() {
		@affiliate_wp()->affiliates->payouts->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The Payouts table upgrade for 1.9.5 has completed.' );

		wp_cache_set( 'last_changed', microtime(), 'payouts' );
		@affiliate_wp()->utils->log( 'Upgrade: The Payouts cache has been invalidated following the 1.9.5 upgrade routine.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.0.
	 *
	 * @since 2.0
	 * @access private
	 */
	private function v20_upgrade() {
		// New primitive and meta capabilities.
		@affiliate_wp()->capabilities->add_caps();
		@affiliate_wp()->utils->log( 'Upgrade: Core capabilities have been upgraded.' );

		// Update settings
		@affiliate_wp()->settings->set(
			[
				'required_registration_fields' => [
					'your_name'   => __( 'Your Name', 'affiliate-wp' ),
					'website_url' => __( 'Website URL', 'affiliate-wp' ),
				],
			],
			$save = true
		);
		@affiliate_wp()->utils->log( 'Upgrade: The default required registration field settings have been configured.' );

		// Affiliate schema update.
		@affiliate_wp()->affiliates->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The unpaid_earnings column has been added to the affiliates table.' );

		wp_cache_set( 'last_changed', microtime(), 'affiliates' );
		@affiliate_wp()->utils->log( 'Upgrade: The Affiliates cache has been invalidated following the 2.0 upgrade.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.0.2.
	 *
	 * @since 2.0.2
	 * @access private
	 */
	private function v202_upgrade() {
		// New 'context' column for visits.
		@affiliate_wp()->visits->create_table();
		$this->log( 'Upgrade: The context column has been added to the Visits table.' );

		wp_cache_set( 'last_changed', microtime(), 'visits' );
		$this->log( 'Upgrade: The Visits cache has been invalidated following the 2.0.2 upgrade.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.0.10.
	 *
	 * @since 2.0.10
	 * @access private
	 */
	private function v210_upgrade() {
		update_option( 'affwp_flush_rewrites', '1' );
		@affiliate_wp()->utils->log( 'Upgrade: AffiliateWP rewrite rules have been flushed following the 2.0.10 upgrade.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.1.
	 *
	 * @access private
	 * @since  2.1
	 */
	private function v21_upgrade() {
		// Schedule a rewrites flush.
		flush_rewrite_rules();
		$this->log( 'Upgrade: Rewrite rules flushed following the 2.1 upgrade.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.1.3.1.
	 *
	 * @access private
	 * @since  2.1.3.1
	 */
	private function v2131_upgrade() {
		// Refresh capabilities missed in 2.1 update (export_visit_data).
		@affiliate_wp()->capabilities->add_caps();
		@affiliate_wp()->utils->log( 'Upgrade: Core capabilities have been upgraded for 2.1.3.1.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.2.
	 *
	 * @access private
	 * @since  2.2
	 */
	private function v22_upgrade() {

		global $wpdb;

		// Add type column to referrals database.
		@affiliate_wp()->referrals->create_table();
		$table = affiliate_wp()->referrals->table_name;
		$wpdb->query( "UPDATE $table SET type = 'sale' where type IS NULL;" );
		@affiliate_wp()->utils->log( 'Upgrade: Referrals table has been upgraded for 2.2.' );

		// New 'customer_id' column for referrals.
		@affiliate_wp()->referrals->create_table();
		@affiliate_wp()->capabilities->add_caps();
		@affiliate_wp()->customers->create_table();
		affiliate_wp()->utils->log( 'Upgrade: The customers table has been created.' );
		@affiliate_wp()->customer_meta->create_table();
		affiliate_wp()->utils->log( 'Upgrade: The customer meta table has been created.' );

		// Update email settings
		$registration_notifications   = 'registration_notifications';
		$admin_referral_notifications = 'admin_referral_notifications';
		$disable_all_emails           = 'disable_all_emails';

		/**
		 * Enable all email notifications by default.
		 * Fresh installations of AffiliateWP and upgrades should enable all notifications.
		 */
		$email_notifications = affiliate_wp()->settings->email_notifications( true );

		/**
		 * If "Disable All Emails" checkbox option was previously enabled,
		 * clear out the email notification array, essentially disabling all notifications.
		 */
		if ( affiliate_wp()->settings->get( $disable_all_emails ) ) {
			$email_notifications = [];
		}

		// Enable the new admin affiliate registration email if it was previously enabled.
		if ( affiliate_wp()->settings->get( $registration_notifications ) ) {
			$email_notifications['admin_affiliate_registration_email'] = __( 'Notify site admin when a new affiliate has registered', 'affiliate-wp' );
		} else {
			// Uncheck the new admin affiliate registration email if it was previously unchecked.
			unset( $email_notifications['admin_affiliate_registration_email'] );
		}

		// Enable the new admin referral notification email if it was previously enabled.
		if ( affiliate_wp()->settings->get( $admin_referral_notifications ) ) {
			$email_notifications['admin_new_referral_email'] = __( 'Notify site admin when new referrals are earned', 'affiliate-wp' );
		} else {
			// Uncheck the new admin referral notification email if it was previously unchecked.
			unset( $email_notifications['admin_new_referral_email'] );
		}

		// Make the required changes to the Email Notifications.
		@affiliate_wp()->settings->set(
			[
				'email_notifications' => $email_notifications,
			],
			$save = true
		);

		// Get all settings.
		$settings = affiliate_wp()->settings->get_all();

		// Remove old "Disable All Emails" setting.
		if ( isset( $settings[ $disable_all_emails ] ) ) {
			unset( $settings[ $disable_all_emails ] );
		}

		// Remove old "Notify Admin" setting.
		if ( isset( $settings[ $registration_notifications ] ) ) {
			unset( $settings[ $registration_notifications ] );
		}

		// Remove old "Notify Admin of Referrals" setting.
		if ( isset( $settings[ $admin_referral_notifications ] ) ) {
			unset( $settings[ $admin_referral_notifications ] );
		}

		// Update affwp_settings option.
		update_option( 'affwp_settings', $settings );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.2.2.
	 *
	 * @since 2.2.2
	 */
	private function v222_upgrade() {
		foreach ( $this->get_sites_for_upgrade() as $site_id ) {

			if ( is_multisite() ) {
				switch_to_blog( $site_id );
			}

			affiliate_wp()->affiliates->create_table();
			@affiliate_wp()->utils->log( sprintf( 'Upgrade: The rest_id column has been added to the Affiliates table for site #%1$s.', $site_id ) );

			affiliate_wp()->referrals->create_table();
			@affiliate_wp()->utils->log( sprintf( 'Upgrade: The rest_id column has been added to the Referrals table for site #%1$s.', $site_id ) );

			affiliate_wp()->REST->consumers->create_table();
			@affiliate_wp()->utils->log( sprintf( 'Upgrade: The status and date columns have been added to the REST Consumers table for site #%1$s.', $site_id ) );

			affiliate_wp()->visits->create_table();
			@affiliate_wp()->utils->log( sprintf( 'Upgrade: The rest_id column has been added to the Visits table for site #%1$s.', $site_id ) );

			// Populate the date and status columns for existing consumers.
			$consumers = affiliate_wp()->REST->consumers->get_consumers(
				[
					'number' => -1,
				]
			);

			if ( ! empty( $consumers ) ) {
				$date = get_post_field( 'post_date', affwp_get_affiliate_area_page_id() );

				if ( empty( $date ) ) {
					$date = gmdate( 'Y-m-d H:i:s' );
				} else {
					$date = gmdate( 'Y-m-d H:i:s', strtotime( $date ) );
				}

				foreach ( $consumers as $consumer ) {

					affiliate_wp()->REST->consumers->update(
						$consumer->ID,
						[
							'date'   => $date,
							'status' => 'active',
						]
					);
				}
			}

			if ( is_multisite() ) {
				restore_current_blog();
			}
		}

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.2.8.
	 *
	 * @since 2.2.8
	 */
	private function v228_upgrade() {
		affiliate_wp()->referrals->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The length of the campaign column in the Referrals table has been changed to 50 characters.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.2.9.
	 *
	 * @since 2.2.9
	 */
	private function v229_upgrade() {
		affiliate_wp()->referrals->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The parent_id column has been added to the Referrals table.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.3.
	 *
	 * @since 2.3
	 */
	private function v23_upgrade() {
		// Adds the flat rate basis column.
		affiliate_wp()->affiliates->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: the flat_rate_basis column has been added to the Affiliates table.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.4.
	 *
	 * @since 2.4
	 */
	private function v24_upgrade() {
		// New 'service_account, service_id, service_invoice_link and description' columns for payouts.
		affiliate_wp()->affiliates->payouts->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The service_account, service_id, service_invoice_link and description columns have been added to the Payouts table.' );

		wp_cache_set( 'last_changed', microtime(), 'payouts' );
		@affiliate_wp()->utils->log( 'Upgrade: The Payouts cache has been invalidated following the 2.4 upgrade.' );

		// Adds the referral meta table.
		affiliate_wp()->referral_meta->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The referral meta table has been created.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.4.2.
	 *
	 * @since 2.4.2
	 */
	private function v242_upgrade() {
		// Flush rewrites for the benefit of the EDD integration.
		flush_rewrite_rules();
		@affiliate_wp()->utils->log( 'Upgrade: Rewrite rules flushed.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.5.
	 *
	 * @since 2.5
	 */
	private function v25_upgrade() {
		affiliate_wp()->referrals->sales->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The sales table has been created.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for version 2.6.
	 *
	 * @since 2.6
	 */
	private function v26_upgrade() {
		affiliate_wp()->affiliates->coupons->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The coupons table has been created.' );

		// Enable the affiliate coupons setting (will not cause unexpected behavior).
		@affiliate_wp()->settings->set(
			[
				'affiliate_coupons' => true,
			],
			$save = true
		);

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for 2.7
	 *
	 * @since 2.7
	 */
	private function v27_upgrade() {
		global $wpdb;

		$dropped = $wpdb->query( "DROP VIEW IF EXISTS {$wpdb->prefix}affiliate_wp_campaigns" );

		if ( true === $dropped ) {
			@affiliate_wp()->utils->log( 'Upgrade: The campaigns view has been dropped.' );
		} else {
			@affiliate_wp()->utils->log( 'Upgrade: The campaigns view was not dropped.', $wpdb->last_error );
		}

		@affiliate_wp()->campaigns->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The campaigns table has been created.' );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for 2.7.4
	 *
	 * @since 2.7.4
	 */
	private function v274_upgrade() {
		$upload_dir = wp_upload_dir( null, false );
		$base_dir   = isset( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : ABSPATH;

		$old_file = trailingslashit( $base_dir ) . 'affwp-debug.log';

		if ( file_exists( $old_file ) && is_writeable( $old_file ) && is_writeable( $base_dir ) ) {
			$hash     = affwp_get_hash( $upload_dir, affiliatewp_get_salt() );
			$new_file = trailingslashit( $base_dir ) . sprintf( 'affwp-debug-log__%s.log', $hash );
			@rename( $old_file, $new_file );
		}

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for 2.8.
	 *
	 * @since 2.8
	 */
	private function v28_upgrade() {
		global $wpdb;

		$table_name = affiliate_wp()->affiliates->coupons->table_name;

		// Update the length of the coupon_code column to 191 characters.
		affiliate_wp()->affiliates->coupons->create_table();

		affiliate_wp()->utils->log( 'Upgrade: The coupons table has been updated to support lengthier coupon codes and types.' );

		// Set default coupon format and hyphen delimeter.
		$coupons_settings = [
			'coupon_format'           => '{coupon_code}',
			'coupon_hyphen_delimiter' => 1,
		];

		affiliate_wp()->settings->set( $coupons_settings, $save = true );

		$this->upgraded = true;
	}

	/**
	 * Performs database upgrades for 2.9.
	 *
	 * @since 2.9
	 */
	private function v29_upgrade() {
		global $wpdb;

		$table_name = affiliate_wp()->affiliates->coupons->table_name;

		// Add the 'locked' column.
		affiliate_wp()->affiliates->coupons->create_table();

		affiliate_wp()->utils->log( 'Upgrade: The locked column has been added to the coupons table.' );

		// Update type field of existing coupons.
		$old_type = '';
		$new_type = 'dynamic';

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_name SET type = %s where type = %s;",
				$new_type,
				$old_type
			)
		);

		affiliate_wp()->utils->log( 'Upgrade: All dynamic coupons now have a "dynamic" type in the coupons table.' );

		wp_cache_set( 'last_changed', microtime(), 'coupons' );

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.9.5
	 *
	 * @access  private
	 * @since   2.9.5
	 */
	private function v295_upgrade() {
		affiliate_wp()->notifications->create_table();
		affiliate_wp()->utils->log( 'Upgrade: The in-plugin notifications table has been created.' );
		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.9.6
	 *
	 * @access  private
	 * @since   2.9.6
	 */
	private function v296_upgrade() {

		affiliate_wp()->referrals->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The flag column has been added to the referrals table.' );

		affiliate_wp()->visits->create_table();
		@affiliate_wp()->utils->log( 'Upgrade: The flag column has been added to the visits table.' );

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.9.6.1
	 *
	 * @access  private
	 * @since   2.9.6.1
	 */
	private function v2961_upgrade() {

		$this->fix_296_action_scheduler_issue();

		$this->upgraded = true;
	}

	/**
	 * Fix scheduler issues in 2.9.6
	 *
	 * Ensure that for the Action Scheduler actions
	 * affwp_daily_scheduled_events, and affwp_monthly_email_summaries
	 * that we make sure there are only one of each of these.
	 *
	 * In 2.9.6 we had an issue where many of these were created, when we only need one
	 * pending action for each of these.
	 *
	 * @since  2.9.6.1
	 * @access private
	 *
	 * @return void Early bail if there's just one scheduled (no duplicates).
	 */
	private function fix_296_action_scheduler_issue() {

		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return; // We can't fix it.
		}

		foreach ( [
			'affwp_monthly_email_summaries',
			'affwp_daily_scheduled_events',
		] as $action ) {

			if ( count(
				// Get all schedule actions (there may be many) for $action.
				as_get_scheduled_actions(
					[
						'hook'     => $action,
						'group'    => 'affiliatewp',
						'status'   => ActionScheduler_Store::STATUS_PENDING,
						'per_page' => -1,
					]
				)
			) <= 1 ) {

				// We only have one scheduled hook for $action, that's correct.
				continue;
			}

			// Remove them all, there should only be one.
			as_unschedule_all_actions( $action, [], 'affiliatewp' );

			// Tell the scheduler not to schedule an email summary for now.
			if ( 'affwp_monthly_email_summaries' === $action ) {
				update_option( 'affwp_email_summary_now', 'no' );
			}
		}
	}

	/**
	 * Perform database upgrades for version 2.11.0
	 *
	 * @access  private
	 * @since   2.11.0
	 */
	private function v2110_upgrade() {

		affiliate_wp()->creatives->create_table();

		@affiliate_wp()->utils->log( 'Upgrade: The attachment_id column has been added to the creatives table.' );

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.14.0.
	 *
	 * @access  private
	 * @since   2.14.0
	 */
	private function v2140_upgrade() {

		affiliate_wp()->creatives->create_table();

		@affiliate_wp()->utils->log( 'Upgrade: The type and date_updated columns has been added to the creatives table.' );

		affiliate_wp()->custom_links->create_table();

		@affiliate_wp()->utils->log( 'Upgrade: The custom_links table was created.' );

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.15.0.
	 *
	 * @access  private
	 * @since   2.15.0
	 */
	private function v2150_upgrade() {

		affiliate_wp()->creatives->create_table();

		@affiliate_wp()->utils->log( 'Upgrade: The start_date and end_date columns have been added to the creatives table.' );

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.16.0.
	 *
	 * @access  private
	 * @since   2.16.0
	 */
	private function v2160_upgrade() {

		affiliate_wp()->creatives->create_table();

		@affiliate_wp()->utils->log( 'Upgrade: The notes column has been added to the creatives table.' );

		// Ensure this will never be overridden.
		if ( ! in_array( get_option( 'affwp_creative_name_privacy', '' ), [ 'pending', 'private', 'public' ], true ) ) {

			update_option( 'affwp_creative_name_upgrade_date', gmdate( 'Y-m-d H:i:s' ) );

			$creatives = affiliate_wp()->creatives->count();

			update_option(
				'affwp_creative_name_privacy',
				empty( $creatives )
					? 'public'
					: 'pending'
			);

		}

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.16.3.
	 *
	 * @access  private
	 * @since   2.16.3
	 */
	private function v2163_upgrade() {
		require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/class-usage.php';

		$usage_tracking = new Affiliate_WP_Usage_Tracking();

		// Track first registered affiliate.
		$usage_tracking->track_first_affiliate();

		// Track first referral.
		$usage_tracking->track_first_referral();

		// Track first payout.
		$usage_tracking->track_first_payout();

		// Track first creative.
		$usage_tracking->track_first_creative( 0, [] );

		/**
		 * Installs before v2.10.0 won't have the affwp_first_installed option row.
		 * If it doesn't exist, create it based on the post date of the current Affiliate Area page.
		 */
		if ( ! get_option( 'affwp_first_installed' ) ) {
			add_option( 'affwp_first_installed', strtotime( get_post_field( 'post_date', affwp_get_affiliate_area_page_id() ) ), '', 'no' );
		}

		// Remove older affwp_last_checkin option row.
		if ( get_option( 'affwp_last_checkin' ) ) {
			delete_option( 'affwp_last_checkin' );
		}

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.17.0.
	 *
	 * @since 2.17.0
	 */
	private function v2170_upgrade() {

		affiliate_wp()->creative_meta->create_table();

		@affiliate_wp()->utils->log( 'Upgrade: The creativemeta table was created.' );

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.18.0.
	 *
	 * @since 2.18.0
	 */
	private function v2180_upgrade() {
		// Get all settings.
		$settings = affiliate_wp()->settings->get_all();

		// User has Auto Register New Users option enabled.
		if ( ! empty( $settings['auto_register'] ) ) {

			// Enable our new setting under "Additional Registration Modes".
			$settings['additional_registration_modes'] = 'auto_register_new_users';

			// Unset the old setting.
			unset( $settings['auto_register'] );

			// Update the affwp_settings option.
			update_option( 'affwp_settings', $settings );
		}

		$this->upgraded = true;
	}

	/*
	Perform database upgrades for version 2.27.0.
	*
	* @since 2.27.0
	*/
	private function v2270_upgrade() {
		// Set the Commission Delay Period to 0 to for existing users.
		affiliate_wp()->settings->set( [ 'commission_holding_period' => 0 ], $save = true );

		$this->upgraded = true;
	}

	/**
	 * Perform database upgrades for version 2.28.0
	 *
	 * Migrate old reCAPTCHA settings to new CAPTCHA provider structure.
	 *
	 * Scenarios handled:
	 * - recaptcha_type = 'v2' → captcha_type = 'recaptcha' (keep recaptcha_type = 'v2')
	 * - recaptcha_type = 'v3' → captcha_type = 'recaptcha' (keep recaptcha_type = 'v3')
	 * - recaptcha_type missing/other → captcha_type = 'none'
	 * - captcha_type = 'none' + recaptcha_type = 'v2'/'v3' → captcha_type = 'recaptcha' (fixes inconsistent state)
	 *
	 * @access private
	 * @since  2.28.0
	 */
	private function upgrade_v2280_migrate_captcha_settings() {
		affiliate_wp()->utils->log( 'Upgrade v2.28.0: Starting CAPTCHA settings migration.' );

		// Get all settings.
		$settings = affiliate_wp()->settings->get_all();

		// Debug: Log what we're seeing.
		$captcha_type_status   = isset( $settings['captcha_type'] ) ? "'{$settings['captcha_type']}'" : 'NOT SET';
		$recaptcha_type_status = isset( $settings['recaptcha_type'] ) ? "'{$settings['recaptcha_type']}'" : 'NOT SET';
		affiliate_wp()->utils->log( "Upgrade v2.28.0: Current settings - captcha_type: $captcha_type_status, recaptcha_type: $recaptcha_type_status" );

		// Migrate if captcha_type doesn't exist, or if it's 'none' but recaptcha_type indicates reCAPTCHA is configured
		$should_migrate = ! isset( $settings['captcha_type'] )
			|| ( 'none' === $settings['captcha_type'] && isset( $settings['recaptcha_type'] ) && in_array( $settings['recaptcha_type'], [ 'v2', 'v3' ] ) );

		if ( $should_migrate ) {
			affiliate_wp()->utils->log( 'Upgrade v2.28.0: Performing CAPTCHA settings migration.' );

			// Check if recaptcha_type is set to v2 or v3 values
			if ( isset( $settings['recaptcha_type'] ) && in_array( $settings['recaptcha_type'], [ 'v2', 'v3' ] ) ) {
				// Set captcha_type to 'recaptcha' when reCAPTCHA is being used
				$settings['captcha_type'] = 'recaptcha';
				affiliate_wp()->utils->log( "Upgrade: Set captcha_type='recaptcha' for existing recaptcha_type='{$settings['recaptcha_type']}'." );
			} else {
				// No reCAPTCHA configuration found - set to none.
				$settings['captcha_type'] = 'none';
				$recaptcha_debug          = isset( $settings['recaptcha_type'] ) ? $settings['recaptcha_type'] : 'NOT SET';
				affiliate_wp()->utils->log( "Upgrade: Set captcha_type='none' - recaptcha_type was '$recaptcha_debug'." );
			}

			// Update the affwp_settings option.
			update_option( 'affwp_settings', $settings );
			affiliate_wp()->utils->log( 'Upgrade v2.28.0: Settings updated successfully.' );
		} else {
			affiliate_wp()->utils->log( "Upgrade v2.28.0: captcha_type setting already exists ('{$settings['captcha_type']}') and is consistent, skipping migration." );
		}

		affiliate_wp()->utils->log( 'Upgrade v2.28.0: CAPTCHA settings migration completed.' );
		$this->upgraded = true;
	}


	/**
	 * Retrieves the site IDs array.
	 *
	 * Most commonly used for db schema changes in networks (but also works for single site).
	 *
	 * @return array Site IDs in the network (single or multisite).
	 */
	private function get_sites_for_upgrade() {
		if ( is_multisite() ) {

			if ( true === version_compare( $GLOBALS['wp_version'], '4.6', '<' ) ) {

				$sites = wp_list_pluck( 'blog_id', wp_get_sites() );

			} else {

				$sites = get_sites( [ 'fields' => 'ids' ] );

			}
		} else {

			$sites = [ get_current_blog_id() ];

		}

		$plugin = AFFILIATEWP_PLUGIN_DIR_NAME . '/affiliate-wp.php';

		// Only return sites AffWP is active on.
		foreach ( $sites as $index => $site_id ) {

			if ( is_multisite() ) {

				switch_to_blog( $site_id );

			}

			if ( ! in_array( $plugin, get_option( 'active_plugins', [] ) ) ) {
				unset( $sites[ $index ] );
			}

			if ( is_multisite() ) {

				restore_current_blog();

			}
		}
		return $sites;
	}

	/**
	 * Schedules the upgrade process.
	 *
	 * This method checks if the current version is less than 2.27.0 and
	 * schedules an asynchronous action to initiate the upgrade process.
	 *
	 * @since 2.27.1
	 * @access public
	 */
	public function schedule_v2270_upgrade() {
		if ( version_compare( $this->version, '2.27.0', '<' ) ) {
			as_enqueue_async_action( 'affwp_schedule_v2270_upgrade_batches', [], 'affiliatewp' );
		}
	}

	/**
	 * Schedules upgrade batches for version 2.27.0.
	 *
	 * This method schedules batches for processing affiliate data upgrades
	 * required for version 2.27.0. It retrieves affiliate IDs that need updating,
	 * calculates the number of batches needed, and schedules individual batch
	 * processing actions.
	 *
	 * @since 2.27.1
	 * @access public
	 */
	public function schedule_v2270_upgrade_batches() {
		global $wpdb;

		$batch_size = 10; // Process 10 affiliates at a time.
		$table_name = affiliate_wp()->affiliate_meta->table_name;

		// Get all affiliate IDs that need to be processed.
		$affiliate_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT a1.affiliate_id
				FROM {$table_name} a1
				JOIN {$table_name} a2 ON a1.affiliate_id = a2.affiliate_id
				WHERE a1.meta_key = %s
				AND a2.meta_key = %s
				AND a2.meta_value = %s",
				'wpforms_form_id',
				'registration_method',
				'affiliate_registration_form'
			)
		);

		$total_affiliates = count( $affiliate_ids );
		$batches          = ceil( $total_affiliates / $batch_size );

		affiliate_wp()->utils->log( "v2270_upgrade: Total affiliates to process: {$total_affiliates}." );

		// Store the total number of affiliates to process.
		update_option( 'affwp_v2270_total_affiliates', $total_affiliates );

		// Schedule an action for each batch.
		for ( $i = 1; $i <= $batches; $i++ ) {
			$batch_affiliate_ids = array_slice( $affiliate_ids, ( $i - 1 ) * $batch_size, $batch_size );
			$batch_count         = count( $batch_affiliate_ids );

			as_schedule_single_action(
				time() + ( ( $i - 1 ) * 30 ), // Add a 30-second delay between batches.
				'affwp_v2270_process_affiliate_batch',
				[
					'batch'            => $i,
					'affiliate_ids'    => $batch_affiliate_ids,
					'total_affiliates' => $total_affiliates,
				],
				'affiliatewp'
			);

			affiliate_wp()->utils->log( "v2270_upgrade: Scheduled batch {$i} with {$batch_count} affiliates." );
		}

		affiliate_wp()->utils->log( "v2270_upgrade: Scheduled {$batches} batches for WPForms registration method update. Total affiliates: {$total_affiliates}." );
	}

	/**
	 * Processes a batch of affiliates for the v2.2.70 upgrade.
	 *
	 * This method updates the registration method for affiliates who used the WPForms
	 * registration form, changing it from 'affiliate_registration_form' to
	 * 'affiliate_registration_form_wpforms'.
	 *
	 * @since 2.27.1
	 *
	 * @param int   $batch             The batch number being processed.
	 * @param array $affiliate_ids     The affiliate IDs to process in this batch.
	 * @param int   $total_affiliates  The total number of affiliates to process across all batches.
	 */
	public function process_v2270_affiliate_batch( $batch, $affiliate_ids, $total_affiliates ) {
		global $wpdb;

		$affiliate_meta_table = affiliate_wp()->affiliate_meta->table_name;
		$updated_count        = 0;
		$total_in_batch       = count( $affiliate_ids );

		affiliate_wp()->utils->log( "v2270_upgrade: Processing batch {$batch} with {$total_in_batch} affiliates" );

		if ( ! empty( $affiliate_ids ) ) {
			foreach ( $affiliate_ids as $affiliate_id ) {
				$result = $wpdb->update(
					$affiliate_meta_table,
					[ 'meta_value' => 'affiliate_registration_form_wpforms' ],
					[
						'affiliate_id' => $affiliate_id,
						'meta_key'     => 'registration_method',
						'meta_value'   => 'affiliate_registration_form',
					],
					[ '%s' ],
					[ '%d', '%s', '%s' ]
				);
				if ( false !== $result ) {
					++$updated_count;
				}
			}

			affiliate_wp()->utils->log( "v2270_upgrade: Updated registration_method for {$updated_count} out of {$total_in_batch} affiliates using WPForms (Batch {$batch})." );

			// Update the count of processed affiliates.
			$processed_affiliates = get_option( 'affwp_v2270_processed_affiliates', 0 ) + $updated_count;
			update_option( 'affwp_v2270_processed_affiliates', $processed_affiliates );

			// Check if all affiliates have been processed.
			if ( $processed_affiliates >= $total_affiliates ) {
				$this->upgraded = true;
				affiliate_wp()->utils->log( 'v2270_upgrade: All batches processed. Upgrade completed.' );

				// Clean up options.
				delete_option( 'affwp_v2270_total_affiliates' );
				delete_option( 'affwp_v2270_processed_affiliates' );
			}
		} else {
			affiliate_wp()->utils->log( "v2270_upgrade: No affiliates to process for WPForms registration method update (Batch {$batch})." );
		}
	}
}
