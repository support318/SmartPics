<?php
/**
 * Admin: Tools Bootstrap
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Tools
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/migration.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-recount.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/class-rest-consumers-table.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/system-info.php';

require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-base-exporter.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-csv-exporter.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-base-importer.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/interfaces/interface-csv-importer.php';

require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/import/import.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/export.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/import/class-import.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-export.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-export-affiliates.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-export-referrals.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-export-referrals-payout.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/export/class-export-settings.php';
require_once AFFILIATEWP_PLUGIN_DIR . 'includes/admin/tools/import/class-import-settings.php';

/**
 * Options Page
 *
 * Renders the options page contents.
 *
 * @since 1.0
 * @return void
 */
function affwp_tools_admin() {

	$active_tab = affwp_get_current_tools_tab();

	ob_start();
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php
			affwp_navigation_tabs( affwp_get_tools_tabs(), $active_tab, array(
				'settings-updated' => false,
				'affwp_notice'     => false
			) );
			?>
		</h2>
		<div id="tab_container">
			<?php
			/**
			 * Fires in the Tools screen tab.
			 *
			 * The dynamic portion of the hook name, `$active_tab`, refers to the slug of
			 * the currently active tools tab.
			 *
			 * @since 1.0
			 */
			do_action( 'affwp_tools_tab_' . $active_tab );
			?>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}


/**
 * Retrieve tools tabs
 *
 * @since 1.0
 * @return array $tabs
 */
function affwp_get_tools_tabs() {

	$tabs                  = array();
	$tabs['export_import'] = __( 'Export / Import', 'affiliate-wp' );

	if ( current_user_can( 'manage_consumers' ) ) {
		$tabs['api_keys'] = __( 'API Keys', 'affiliate-wp' );
	}

	$tabs['recount']       = __( 'Recount Stats', 'affiliate-wp' );
	$tabs['migration']     = __( 'Migration Assistant', 'affiliate-wp' );

	if ( current_user_can( 'manage_affiliate_options' ) ) {
		$tabs['system_info'] = __( 'System Info', 'affiliate-wp' );
	}

	if( affiliate_wp()->settings->get( 'debug_mode', false ) ) {
		$tabs['debug'] = __( 'Debug Assistant', 'affiliate-wp' );
	}

	if ( affwp_get_dynamic_coupons_integrations() ) {
		$tabs['coupons'] = __( 'Coupons', 'affiliate-wp' );
	}

	if ( current_user_can( 'manage_affiliate_options' ) ) {
		$tabs['terms_of_use_generator'] = __( 'Terms of Use Generator', 'affiliate-wp' );
	}

	/**
	 * Filters AffiliateWP tools tabs.
	 *
	 * @since 1.0
	 *
	 * @param array $tabs Array of tools tabs.
	 */
	return apply_filters( 'affwp_tools_tabs', $tabs );
}

/**
 * Retrieves the current Tools tab.
 *
 * @since 1.8
 *
 * @return string Current Tools tab if present in the URL, 'export_import' otherwise.
 */
function affwp_get_current_tools_tab() {
	if ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], affwp_get_tools_tabs() ) ) {
		$active_tab = sanitize_text_field( $_GET['tab'] );
	} else {
		$active_tab = 'export_import';
	}

	/**
	 * Filter the current Tools tab.
	 *
	 * @since 1.8
	 *
	 * @param string $active_tab Current Tools tab ID.
	 */
	return apply_filters( 'affwp_current_tools_tab', $active_tab );
}

/**
 * Recount Tab
 *
 * @since       1.0
 * @return      void
 */
function affwp_recount_tab() {
?>
	<div id="affwp-dashboard-widgets-wrap">
		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php esc_html_e( 'Recount Affiliate Stats', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Use this tool to recount statistics for one or all affiliates.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="affwp-batch-form" data-batch_id="recount-affiliate-stats" data-nonce="<?php echo esc_attr( wp_create_nonce( 'recount-affiliate-stats_step_nonce' ) ); ?>">
						<p>
							<span class="affwp-ajax-search-wrap">
								<input type="text" name="user_name" id="user_name" class="affwp-user-search" data-affwp-status="any" autocomplete="off" placeholder="<?php esc_html_e( 'Affiliate name', 'affiliate-wp' ); ?>"/>
							</span>
							<select name="recount_type">
								<option value="earnings"><?php esc_html_e( 'Paid Earnings', 'affiliate-wp' ); ?></option>
								<option value="unpaid-earnings"><?php esc_html_e( 'Unpaid Earnings', 'affiliate-wp' ); ?></option>
								<option value="referrals"><?php esc_html_e( 'Referrals', 'affiliate-wp' ); ?></option>
								<option value="visits"><?php esc_html_e( 'Visits', 'affiliate-wp' ); ?></option>
							</select>
							<div class="description"><?php esc_html_e( 'Enter the name of the affiliate or begin typing to perform a search based on the affiliate&#8217;s name.', 'affiliate-wp' ); ?></div>
						</p>
						<p>
							<input type="hidden" name="affwp_action" value="recount_stats"/>
							<?php submit_button( __( 'Recount', 'affiliate-wp' ), 'secondary', 'recount-stats-submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
		</div><!-- .metabox-holder -->

		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php esc_html_e( 'Recount Campaigns', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Use this tool to recount campaigns for one or all affiliates.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="affwp-batch-form" data-batch_id="recalculate-campaigns" data-nonce="<?php echo esc_attr( wp_create_nonce( 'recalculate-campaigns_step_nonce' ) ); ?>">
						<p>
							<span class="affwp-ajax-search-wrap">
								<input type="text" name="user_name" id="user_name" class="affwp-user-search" data-affwp-status="any" autocomplete="off" placeholder="<?php esc_html_e( 'Affiliate name', 'affiliate-wp' ); ?>"/>
							</span>
							<div class="description"><?php esc_html_e( 'Enter the name of the affiliate or begin typing to perform a search based on the affiliate&#8217;s name.', 'affiliate-wp' ); ?></div>
						</p>
						<p>
							<input type="hidden" name="affwp_action" value="recalculate_campaigns"/>
							<?php submit_button( __( 'Recount', 'affiliate-wp' ), 'secondary', 'recalculate-campaigns-submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
		</div><!-- .metabox-holder -->

	</div><!-- #affwp-dashboard-widgets-wrap -->
<?php
}
add_action( 'affwp_tools_tab_recount', 'affwp_recount_tab' );

/**
 * Migration assistant tab
 *
 * @since 1.0
 *
 * @global string $wp_version WordPress version
 *
 * @return void
 */
function affwp_migration_tab() {
	global $wp_version;
	$tool_is_compatible = version_compare( $wp_version, '4.4', '>=' );

	$affiliate_user_ids = affiliate_wp()->affiliates->get_affiliates( array(
		'number' => -1,
		'fields' => 'user_id',
	) );

	$_roles = new WP_Roles();
	$roles  = array();

	foreach ( $_roles->get_names() as $role => $label ) {

		$roles[ $role ]['label'] = translate_user_role( $label );

		$user_query = new WP_User_Query( array(
			'role'    => $role,
			'fields'  => 'ID',
			'exclude' => $affiliate_user_ids,
		) );

		$roles[ $role ]['count'] = (int) $user_query->get_total();

	}
?>
	<div id="affwp-dashboard-widgets-wrap">
		<div class="metabox-holder">
			<div class="postbox">
				<div class="inside">
					<p><?php esc_html_e( 'These tools assist in migrating affiliate and referral data from existing platforms.', 'affiliate-wp' ); ?></p>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php esc_html_e( 'User Accounts', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<?php if ( $tool_is_compatible ) : ?>
						<p><?php esc_html_e( 'Use this tool to create affiliate accounts for each of your existing WordPress user accounts that belong to the selected roles below.', 'affiliate-wp' ); ?></p>
						<strong><?php esc_html_e( 'NOTE: Users that already have affiliate accounts will be skipped. Duplicate accounts will not be created.', 'affiliate-wp' ); ?></strong>
						<form method="post" id="affiliate-wp-migrate-user-accounts" class="affwp-batch-form" data-batch_id="migrate-users" data-nonce="<?php echo esc_attr( wp_create_nonce( 'migrate-users_step_nonce' ) ); ?>">
							<h4><span><?php esc_html_e( 'Select User Roles', 'affiliate-wp' ); ?></span></h4>
							<?php foreach ( $roles as $role => $data ) : ?>
								<?php $has_users = ! empty( $data['count'] ); ?>
								<label>
									<input type="checkbox" name="roles[]" value="<?php echo esc_attr( $role ); ?>" <?php checked( $has_users ); disabled( ! $has_users ) ?>>
									<span class="<?php echo ( ! $has_users ) ? 'muted' : ''; ?>"><?php echo esc_html( $data['label'] ); ?> (<?php echo absint( $data['count'] ); ?>)</span>
								</label>
								<br>
							<?php endforeach; ?>
							<p>
								<input type="submit" value="<?php esc_html_e( 'Create Affiliate Accounts for Users', 'affiliate-wp' ); ?>" class="button" />
							</p>
						</form>
					<?php else : ?>
						<?php if ( current_user_can( 'update_core' ) ) : ?>
							<p><?php printf( __( '<strong>NOTE:</strong> WordPress 4.5 or newer is required to use the User Accounts migration tool. <a href="%s" aria-label="Update WordPress now">Update WordPress now</a>.', 'affiliate-wp' ), network_admin_url( 'update-core' ) ); ?></p>
						<?php else : ?>
							<p><?php _e( '<strong>NOTE:</strong> WordPress 4.5 or newer is required to use the User Accounts migration tool.', 'affiliate-wp' ); ?></p>
						<?php endif; // 'update_core' ?>
					<?php endif; // $tool_is_compatible ?>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span>Affiliates Pro</span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Use this tool to migrate existing affiliate / referral data from Affiliates Pro to AffiliateWP.', 'affiliate-wp' ); ?></p>
					<strong><?php esc_html_e( 'NOTE: This tool should only ever be used on a fresh install. If you have already collected affiliate or referral data, do not use this tool.', 'affiliate-wp' ); ?></strong>
					<form method="get">
						<input type="hidden" name="type" value="affiliates-pro"/>
						<input type="hidden" name="part" value="affiliates"/>
						<input type="hidden" name="page" value="affiliate-wp-migrate"/>
						<p>
							<input type="submit" value="<?php esc_html_e( 'Migrate Data from Affiliates Pro', 'affiliate-wp' ); ?>" class="button"/>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span>WP Affiliate</span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Use this tool to migrate existing affiliate accounts from WP Affiliate to AffiliateWP.', 'affiliate-wp' ); ?></p>
					<form method="get" class="affwp-batch-form" data-batch_id="migrate-wp-affiliate" data-nonce="<?php echo esc_attr( wp_create_nonce( 'migrate-wp-affiliate_step_nonce' ) ); ?>">
						<p>
							<input type="submit" value="<?php esc_html_e( 'Migrate Data from WP Affiliate', 'affiliate-wp' ); ?>" class="button"/>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

		</div><!-- .metabox-holder -->
	</div><!-- #affwp-dashboard-widgets-wrap -->
<?php
}
add_action( 'affwp_tools_tab_migration', 'affwp_migration_tab' );

/**
 * Export / Import tab
 *
 * @since       1.0
 * @return      void
 */
function affwp_export_import_tab() {
?>
	<div id="affwp-dashboard-widgets-wrap">
		<div class="metabox-holder">

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Export Affiliates', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Export affiliates to a CSV file.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="affwp-batch-form" data-batch_id="export-affiliates" data-nonce="<?php echo esc_attr( wp_create_nonce( 'export-affiliates_step_nonce' ) ); ?>">
						<p>
							<select name="status" id="status">
								<?php $statuses = affwp_get_affiliate_statuses(); ?>
								<option value="0"><?php esc_html_e( 'All Statuses', 'affiliate-wp' ); ?></option>

								<?php foreach ( $statuses as $status => $label ) : ?>
									<option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<?php submit_button( __( 'Export', 'affiliate-wp' ), 'secondary', 'export-affiliates-submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Export Referrals', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Export referrals to a CSV file.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="affwp-batch-form" data-batch_id="export-referrals" data-nonce="<?php echo esc_attr( wp_create_nonce( 'export-referrals_step_nonce' ) ); ?>">
						<p>
							<span class="affwp-ajax-search-wrap">
								<input type="text" name="user_name" id="user_name" class="affwp-user-search" data-affwp-status="any" autocomplete="off" placeholder="<?php esc_html_e( 'Affiliate name', 'affiliate-wp' ); ?>" />
							</span>
							<input type="text" class="affwp-datepicker" autocomplete="off" name="start_date" placeholder="<?php esc_html_e( 'From - mm/dd/yyyy', 'affiliate-wp' ); ?>"/>
							<input type="text" class="affwp-datepicker" autocomplete="off" name="end_date" placeholder="<?php esc_html_e( 'To - mm/dd/yyyy', 'affiliate-wp' ); ?>"/>
							<select name="status" id="status">
								<?php $statuses = affwp_get_referral_statuses(); ?>
								<option value="0"><?php esc_html_e( 'All Statuses', 'affiliate-wp' ); ?></option>
								<?php foreach ( $statuses as $status => $label ) : ?>
									<option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<div class="description"><?php esc_html_e( 'To search for an affiliate, enter the affiliate&#8217;s login name, first name, or last name. Leave blank to export referrals for all affiliates.', 'affiliate-wp' ); ?></div>
						</p>
						<p>
							<?php submit_button( __( 'Export', 'affiliate-wp' ), 'secondary', 'export-referrals-submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Export Payouts', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Export payouts to a CSV file.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="affwp-batch-form" data-batch_id="export-payouts" data-nonce="<?php echo esc_attr( wp_create_nonce( 'export-payouts_step_nonce' ) ); ?>">
						<p>
							<span class="affwp-ajax-search-wrap">
								<input type="text" name="user_name" id="user_name" class="affwp-user-search" data-affwp-status="any" autocomplete="off" placeholder="<?php esc_html_e( 'Affiliate name', 'affiliate-wp' ); ?>" />
							</span>
							<input type="text" class="affwp-datepicker" autocomplete="off" name="start_date" placeholder="<?php esc_html_e( 'From - mm/dd/yyyy', 'affiliate-wp' ); ?>"/>
							<input type="text" class="affwp-datepicker" autocomplete="off" name="end_date" placeholder="<?php esc_html_e( 'To - mm/dd/yyyy', 'affiliate-wp' ); ?>"/>
							<select name="status" id="status">
								<option value="processing"><?php esc_html_e( 'Processing', 'affiliate-wp' ); ?></option>
								<option value="paid"><?php esc_html_e( 'Paid', 'affiliate-wp' ); ?></option>
								<option value="unpaid"><?php esc_html_e( 'Failed', 'affiliate-wp' ); ?></option>
							</select>
							<div class="description"><?php esc_html_e( 'To search for an affiliate, enter the affiliate&#8217;s login name, first name, or last name. Leave blank to export payouts for all affiliates.', 'affiliate-wp' ); ?></div>
						</p>
						<p>
							<?php submit_button( __( 'Export', 'affiliate-wp' ), 'secondary', 'export-payouts-submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Export Visits', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Export visits to a CSV file.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="affwp-batch-form" data-batch_id="export-visits" data-nonce="<?php echo esc_attr( wp_create_nonce( 'export-visits_step_nonce' ) ); ?>">
						<p>
							<span class="affwp-ajax-search-wrap">
								<input type="text" name="user_name" id="user_name" class="affwp-user-search" data-affwp-status="any" autocomplete="off" placeholder="<?php esc_html_e( 'Affiliate name', 'affiliate-wp' ); ?>" />
							</span>
							<input type="text" class="affwp-datepicker" autocomplete="off" name="start_date" placeholder="<?php esc_html_e( 'From - mm/dd/yyyy', 'affiliate-wp' ); ?>"/>
							<input type="text" class="affwp-datepicker" autocomplete="off" name="end_date" placeholder="<?php esc_html_e( 'To - mm/dd/yyyy', 'affiliate-wp' ); ?>"/>
							<select name="referral_status" id="referral_status">
								<option value=""><?php esc_html_e( 'All', 'affiliate-wp' ); ?></option>
								<option value="converted"><?php esc_html_e( 'Converted', 'affiliate-wp' ); ?></option>
								<option value="unconverted"><?php esc_html_e( 'Unconverted', 'affiliate-wp' ); ?></option>
							</select>
							<div class="description"><?php esc_html_e( 'To search for an affiliate, enter the affiliate&#8217;s login name, first name, or last name. Leave blank to export visits for all affiliates.', 'affiliate-wp' ); ?></div>
						</p>
						<p>
							<?php submit_button( __( 'Export', 'affiliate-wp' ), 'secondary', 'export-visits-submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Export Settings', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Export the AffiliateWP settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'affiliate-wp' ); ?></p>
					<form method="post" action="<?php echo esc_url( affwp_admin_url( 'tools', array( 'tab' => 'export_import' ) ) ); ?>">
						<p><input type="hidden" name="affwp_action" value="export_settings" /></p>
						<p>
							<?php wp_nonce_field( 'affwp_export_nonce', 'affwp_export_nonce' ); ?>
							<?php submit_button( __( 'Export', 'affiliate-wp' ), 'secondary', 'export-settings-submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Import Settings', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Import the AffiliateWP settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( affwp_admin_url( 'tools', array( 'tab' => 'export_import' ) ) ); ?>">
						<p>
							<input type="file" name="import_file"/>
						</p>
						<p>
							<input type="hidden" name="affwp_action" value="import_settings" />
							<?php wp_nonce_field( 'affwp_import_nonce', 'affwp_import_nonce' ); ?>
							<?php submit_button( __( 'Import', 'affiliate-wp' ), 'secondary', 'import-settings-submit', false ); ?>
						</p>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Import Affiliates', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Import a CSV of affiliate records.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="affwp-batch-import-form" data-batch_id="import-affiliates" data-required="email" data-nonce=""<?php echo esc_attr( wp_create_nonce( 'import-affiliates_step_nonce' ) ); ?>">
						<div class="affwp-import-file-wrap">
							<p>
								<input name="affwp-import-file" id="affwp-import-affiliates-file" type="file" />
							</p>
							<p>
								<?php wp_nonce_field( 'affwp_import_nonce', 'affwp_import_nonce' ); ?>
								<?php submit_button( __( 'Import CSV', 'affiliate-wp' ), 'secondary', 'import-affiliates-submit', false ); ?>
							</p>
						</div>

						<div class="affwp-import-options" id="affwp-import-affiliates-options" style="display:none;">

							<p>
								<?php
								printf(
									/* translators: Documentation URL */
									__( 'Each column loaded from the CSV may be mapped to an affiliate field. Select the column that should be mapped to each field below. Any columns not needed can be ignored. See <a href="%s" target="_blank">this guide</a> for assistance with importing affiliate records.', 'affiliate-wp' ),
									esc_url( 'https://affiliatewp.com/docs/importing-affiliates-from-csv/' )
								);
								?>
							</p>

							<table class="widefat affwp_repeatable_table striped" width="100%" cellpadding="0" cellspacing="0">
								<thead>
								<tr>
									<th><strong><?php esc_html_e( 'Affiliate Field', 'affiliate-wp' ); ?></strong></th>
									<th><strong><?php esc_html_e( 'CSV Column', 'affiliate-wp' ); ?></strong></th>
									<th><strong><?php esc_html_e( 'Data Preview', 'affiliate-wp' ); ?></strong></th>
								</tr>
								</thead>
								<tbody>
									<?php affwp_do_import_fields( 'affiliates' ); ?>
								</tbody>
							</table>
							<p class="submit">
								<button class="affwp-import-proceed button-primary"><?php esc_html_e( 'Process Import', 'affiliate-wp' ); ?></button>
							</p>
						</div>

					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Import Referrals', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Import a CSV of referral records.', 'affiliate-wp' ); ?></p>
					<form method="post" enctype="multipart/form-data" class="affwp-batch-import-form" data-batch_id="import-referrals" data-required="affiliate,amount" data-nonce=""<?php echo esc_attr( wp_create_nonce( 'import-referrals_step_nonce' ) ); ?>">
						<div class="affwp-import-file-wrap">
							<p>
								<input name="affwp-import-file" id="affwp-import-referrals-file" type="file" />
							</p>
							<p>
								<?php wp_nonce_field( 'affwp_import_nonce', 'affwp_import_nonce' ); ?>
								<?php submit_button( __( 'Import CSV', 'affiliate-wp' ), 'secondary', 'import-referrals-submit', false ); ?>
							</p>
						</div>

						<div class="affwp-import-options" id="affwp-import-referrals-options" style="display:none;">

							<p>
								<?php
								printf(
									/* translators: Documentation URL */
									__( 'Each column loaded from the CSV may be mapped to a referral field. Select the column that should be mapped to each field below. Any columns not needed can be ignored. Any affiliates that don&#8217;t exist will be created. See <a href="%s" target="_blank">this guide</a> for assistance with importing referral records.', 'affiliate-wp' ),
									esc_url( 'https://affiliatewp.com/docs/importing-referrals-from-csv/' )
								);
								?>
							</p>

							<table class="widefat affwp_repeatable_table striped" width="100%" cellpadding="0" cellspacing="0">
								<thead>
								<tr>
									<th><strong><?php esc_html_e( 'Referral Field', 'affiliate-wp' ); ?></strong></th>
									<th><strong><?php esc_html_e( 'CSV Column', 'affiliate-wp' ); ?></strong></th>
									<th><strong><?php esc_html_e( 'Data Preview', 'affiliate-wp' ); ?></strong></th>
								</tr>
								</thead>
								<tbody>
									<?php affwp_do_import_fields( 'referrals' ); ?>
								</tbody>
							</table>
							<p class="submit">
								<button class="affwp-import-proceed button-primary"><?php esc_html_e( 'Process Import', 'affiliate-wp' ); ?></button>
							</p>
						</div>

					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
		</div><!-- .metabox-holder -->
	</div><!-- #affwp-dashboard-widgets-wrap -->
<?php
}
add_action( 'affwp_tools_tab_export_import', 'affwp_export_import_tab' );

/**
 * System Info tab.
 *
 * @since 1.8.7
 */
function affwp_system_info_tab() {
	if ( ! current_user_can( 'manage_affiliate_options' ) ) {
		return;
	}

	$action_url = affwp_admin_url( 'tools', array( 'tab' => 'system_info' ) );
	?>
	<form action="<?php echo esc_url( $action_url ); ?>" method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="affwp-system-info-textarea" name="affwp-sysinfo" title="<?php esc_attr_e( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'affiliate-wp' ); ?>">
			<?php echo affwp_tools_system_info_report(); ?>
		</textarea>
		<p class="submit">
			<input type="hidden" name="affwp_action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'affwp-download-sysinfo', false ); ?>
		</p>
	</form>
	<?php
}
add_action( 'affwp_tools_tab_system_info', 'affwp_system_info_tab' );

/**
 * Allows the x-text attribute while sanitizing using wp_kses_post().
 * Only allowed within the Terms of Use Generator tab.
 *
 * @since 2.9.6
 *
 * @param array $html Allowed HTML tags.
 * @param string $context Context name.
 *
 * @return array $html Allowed HTML tags.
 */
function affwp_tools_wp_kses_allowed_html( $html, $context ) {

	if ( 'terms_of_use_generator' !== affwp_get_current_tools_tab() || 'post' !== $context ) {
		return $html;
	}

	return array_merge(
		$html,
		array(
			'span' => array(
				'x-text' => 1,
			),
		)
	);
}
add_filter( 'wp_kses_allowed_html', 'affwp_tools_wp_kses_allowed_html', 10, 2 );

/**
 * Terms of Use Generator tab.
 *
 * @since 2.9.6
 * @return void
 */
function affwp_terms_of_use_generator_tab() {
	if ( ! current_user_can( 'manage_affiliate_options' ) ) {
		return;
	}

	$site_name      = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$placeholder    = affwp_get_terms_of_use_content( array( 'company' => $site_name ) );
	$placeholder    = str_replace( $site_name, '<span x-text="company">' . $site_name . '</span>', $placeholder );
	$terms_page     = affwp_get_affiliate_terms_of_use_page_id();
	$button_text    = __( 'Create Terms of Use Page', 'affiliate-wp' );
	$term_page_link = get_permalink( $terms_page );
	?>
	<div id="affwp-dashboard-widgets-wrap">
		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php esc_html_e( 'Terms of Use Generator', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<form class="wp-create-terms-of-use-page" method="post" action="" x-data="{ company: '<?php echo esc_attr( $site_name ); ?>' }">
						<p><?php esc_html_e( 'Use this tool to create a Terms of Use page for your affiliate program. You will be able to make changes before publishing.', 'affiliate-wp' ); ?></p>
						<p><?php esc_html_e( 'Our Terms of Use template is a generic starting point. It is your responsibility to modify it as needed and to provide any additional information. For legal opinion and advice, refer to a professional attorney.', 'affiliate-wp' ); ?></p>

						<p>
							<label for="company"><?php esc_html_e( 'Company Name', 'affiliate-wp' ); ?></label>
							<span class="description"><?php esc_html_e( 'Your company name is shown throughout the Terms of Use.', 'affiliate-wp' ); ?></span>
							<input id="company" type="text" x-model="company" name="company" class="regular-text">
						</p>

						<p><strong><?php esc_html_e( 'Terms of Use Preview', 'affiliate-wp' ); ?></strong> </p>

						<div id="terms-of-use-preview"><h1><?php esc_html_e( 'Affiliate Terms of Use', 'affiliate-wp' ); ?></h1><?php echo wp_kses_post( $placeholder ); ?></div>

						<?php if ( $terms_page && 'publish' === get_post_status( $terms_page ) ) : ?>
							<?php $button_text = __( 'Create a New Terms of Use Page', 'affiliate-wp' ); ?>
							<?php // Translators: %s below is a link to the TOS page. ?>
							<p><?php echo wp_kses_post( sprintf( __( 'You already have a %s page. Are you sure you want to create a new one?', 'affiliate-wp' ), sprintf( "<a href='{$term_page_link}' target='_blank'>%s</a>", __( 'Terms of Use', 'affiliate-wp' ) ) ) ); ?></p>
						<?php endif; ?>

						<?php submit_button( $button_text, 'primary', 'affwp-create-terms-of-use-page', false ); ?>
						<?php wp_nonce_field( 'affwp-create-terms-of-use-page' ); ?>

						<input type="hidden" name="affwp_action" value="create_terms_of_use_page" />
					</form>
				</div>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'affwp_tools_tab_terms_of_use_generator', 'affwp_terms_of_use_generator_tab' );

/**
 * Retrieve Terms of Use page content.
 *
 * @since 2.9.6
 *
 * @param array $data Data sent from generator.
 * @return string $terms_of_use_content Terms of Use content.
 */
function affwp_get_terms_of_use_content( $data = array() ) {
	$company = isset( $data['company'] ) ? $data['company'] : '';
	ob_start();
	?>

	<!-- wp:paragraph -->
	<?php // Translators: %1$s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( 'As an authorized affiliate (Affiliate) of %1$s, you agree to abide by the terms and conditions contained in this Agreement (Agreement). Please read the entire Agreement carefully before registering and promoting %1$s as an Affiliate.', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<?php // Translators: %s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( 'Your participation in the Program is solely to legally advertise our website to receive a commission on memberships and products purchased by individuals referred to %s by your own website or personal referrals.', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<?php // Translators: %s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( 'By signing up for the %s Affiliate Program (Program), you indicate your acceptance of this Agreement and its terms and conditions.', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="approval-or-rejection"><?php esc_html_e( 'Approval or Rejection of the Application', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'We reserve the right to approve or reject ANY Affiliate Program Application at our sole and absolute discretion. You will have no legal recourse against us for the rejection of your Affiliate Program Application.', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="commissions"><?php esc_html_e( 'Commissions', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Commissions will be paid once a month. For an Affiliate to receive a commission, the referred account must remain active for a minimum of 31 days.', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'You cannot refer yourself, and you will not receive a commission on your own accounts.', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Payments will only be sent for transactions that have been successfully completed. Transactions that result in chargebacks or refunds will not be paid out.', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="termination"><?php esc_html_e( 'Termination', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Your affiliate application and status in the Program may be suspended or terminated for any of the following reasons:', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:list -->
	<ul>
		<li><?php esc_html_e( 'Inappropriate advertisements (false claims, misleading hyperlinks, etc.).', 'affiliate-wp' ); ?></li>
		<li><?php esc_html_e( 'Spamming (mass email, mass newsgroup posting, etc.).', 'affiliate-wp' ); ?></li>
		<li><?php esc_html_e( 'Advertising on sites containing or promoting illegal activities.', 'affiliate-wp' ); ?></li>
		<li><?php esc_html_e( 'Failure to disclose the affiliate relationship for any promotion that qualifies as an endorsement under existing Federal Trade Commission guidelines and regulations, or any applicable state laws.', 'affiliate-wp' ); ?></li>
		<?php // Translators: %1$s below is the Company Name. ?>
		<li><?php echo esc_html( sprintf( __( 'Violation of intellectual property rights. %1$s reserves the right to require license agreements from those who employ trademarks of %1$s in order to protect our intellectual property rights.', 'affiliate-wp' ), $company ) ); ?></li>
		<?php // Translators: %s below is the Company Name. ?>
		<li><?php echo esc_html( sprintf( __( 'Offering rebates, coupons, or other form of promised kick-backs from your affiliate commission as an incentive. Adding bonuses or bundling other products with %s, however, is acceptable.', 'affiliate-wp' ), $company ) ); ?></li>
		<li><?php esc_html_e( 'Self referrals, fraudulent transactions, suspected Affiliate fraud.', 'affiliate-wp' ); ?></li>
	</ul>
	<!-- /wp:list -->

	<!-- wp:paragraph -->
	<?php // Translators: %s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( 'In addition to the foregoing, %s reserves the right to terminate any Affiliate account at any time, for any violations of this Agreement or no reason.', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="affiliate-links"><?php esc_html_e( 'Affiliate Links', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<?php // Translators: %s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( 'You may use graphic and text links both on your website and within in your email messages. You may also advertise the %s site in online and offline classified ads, magazines, and newspapers.', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'You may use the graphics and text provided by us, or you may create your own as long as they are deemed appropriate according to the conditions and not in violation as outlined in the Termination section.', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="coupon-and-deal-sites"><?php esc_html_e( 'Coupon and Deal Sites', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<?php // Translators: %s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( '%s occasionally offers coupon to select affiliates and to our newsletter subscribers. If you’re not pre-approved / assigned a branded coupon, then you’re not allowed to promote the coupon. Below are the terms that apply for any affiliate who is considering the promotion of our products in relation to a deal or coupon:', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:list -->
	<ul>
		<li><?php esc_html_e( 'Affiliates may not use misleading text on affiliate links, buttons or images to imply that anything besides currently authorized deals to the specific affiliate.', 'affiliate-wp' ); ?></li>
		<?php // Translators: %1$s below is the Company Name. ?>
		<li><?php echo esc_html( sprintf( __( 'Affiliates may not bid on %1$s Coupons, %1$s Discounts or other phrases implying coupons are available.', 'affiliate-wp' ), $company ) ); ?></li>
		<li><?php esc_html_e( 'Affiliates may not generate pop-ups, pop-unders, iframes, frames, or any other seen or unseen actions that set affiliate cookies unless the user has expressed a clear and explicit interest in activating a specific savings by clicking on a clearly marked link, button or image for that particular coupon or deal. Your link must send the visitor to the merchant site.', 'affiliate-wp' ); ?></li>
		<li><?php esc_html_e( 'User must be able to see coupon/deal/savings information and details before an affiliate cookie is set (i.e. “click here to see coupons and open a window to merchant site” is NOT allowed).', 'affiliate-wp' ); ?></li>
		<li><?php esc_html_e( 'Affiliate sites may not have “Click for (or to see) Deal/Coupon” or any variation, when there are no coupons or deals available, and the click opens the merchant site or sets a cookie. Affiliates with such text on the merchant landing page will be removed from the program immediately.', 'affiliate-wp' ); ?></li>
	</ul>
	<!-- /wp:list -->

	<!-- wp:heading {"level":2} -->
	<h2 id="ppc-policy"><?php esc_html_e( 'Pay Per Click (PPC) Policy', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'PPC bidding is NOT allowed without prior written permission.', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="liability"><?php esc_html_e( 'Liability', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<?php // Translators: %s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( '%s will not be liable for indirect or accidental damages (loss of revenue, commissions) due to affiliate tracking failures, loss of database files, or any results of intents of harm to the Program and/or to our website(s).', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<?php // Translators: %s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( 'We do not make any expressed or implied warranties with respect to the Program and/or the memberships or products sold by %s. We make no claim that the operation of the Program and/or our website(s) will be error-free and we will not be liable for any interruptions or errors.', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="term-of-agreement"><?php esc_html_e( 'Term of the Agreement', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'The term of this Agreement begins upon your acceptance in the Program and will end when your Affiliate account is terminated.', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'The terms and conditions of this agreement may be modified by us at any time. If any modification to the terms and conditions of this Agreement are unacceptable to you, your only choice is to terminate your Affiliate account. Your continuing participation in the Program will constitute your acceptance of any change.', 'affiliate-wp' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="indemnification"><?php esc_html_e( 'Indemnification', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<?php // Translators: %1$s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( 'Affiliate shall indemnify and hold harmless %1$s and its affiliate and subsidiary companies, officers, directors, employees, licensees, successors and assigns, including those licensed or authorized by %1$s to transmit and distribute materials, from any and all liabilities, damages, fines, judgments, claims, costs, losses, and expenses (including reasonable legal fees and costs) arising out of or related to any and all claims sustained in connection with this Agreement due to the negligence, misrepresentation, failure to disclose, or intentional misconduct of Affiliate.', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2} -->
	<h2 id="electronic-signatures-effective"><?php esc_html_e( 'Electronic Signatures Effective', 'affiliate-wp' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<?php // Translators: %1$s below is the Company Name. ?>
	<p><?php echo esc_html( sprintf( __( 'The Agreement is an electronic contract that sets out the legally binding terms of your participation in the %1$s affiliate program. You indicate your acceptance of this Agreement and all of the terms and conditions contained or referenced in this Agreement by completing the %1$s application process. This action creates an electronic signature that has the same legal force and effect as a handwritten signature.', 'affiliate-wp' ), $company ) ); ?></p>
	<!-- /wp:paragraph -->

	<?php
	return ob_get_clean();
}

/**
 * Handles submit actions for the terms of use page.
 *
 * @since 2.9.6
 */
function affwp_create_terms_of_use_page() {

	if ( ! current_user_can( 'manage_affiliate_options' ) ) {
		return;
	}

	check_admin_referer( 'affwp-create-terms-of-use-page' );

	if ( isset( $_REQUEST['affwp-create-terms-of-use-page'] ) ) {

		$company = isset( $_POST['company'] ) ? $_POST['company'] : '';

		$terms_of_use_page_id = wp_insert_post(
			array(
				'post_title'     => __( 'Affiliate Terms of Use', 'affiliate-wp' ),
				'post_content'   => affwp_get_terms_of_use_content( array( 'company' => $company ) ),
				'post_status'    => 'draft',
				'post_author'    => get_current_user_id(),
				'post_type'      => 'page',
				'comment_status' => 'closed',
			)
		);

		if ( is_wp_error( $terms_of_use_page_id ) ) {
			add_settings_error(
				'page_for_affiliate_terms',
				'page_for_affiliate_terms',
				__( 'Unable to create an Affiliate Terms of Use page.', 'affiliate-wp' ),
				'error'
			);
		} else {
			// Set the Terms of use page.
			affiliate_wp()->settings->set(
				array(
					'terms_of_use' => $terms_of_use_page_id,
				),
				$save = true
			);

			wp_safe_redirect( admin_url( 'post.php?post=' . $terms_of_use_page_id . '&action=edit' ) );
			exit;
		}
	}
}
add_action( 'affwp_create_terms_of_use_page', 'affwp_create_terms_of_use_page' );

/**
 * Listens for system info download requests and delivers the file.
 *
 * @since 1.8.7
 */
function affwp_tools_sysinfo_download() {

	if ( wp_doing_ajax() ) {
		return;
	}

	if ( ! current_user_can( 'manage_affiliate_options' ) ) {
		return;
	}

	if ( ! isset( $_POST['affwp-download-sysinfo'] ) ) {
		return;
	}

	nocache_headers();

	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="affwp-system-info.txt"' );

	echo wp_strip_all_tags( $_POST['affwp-sysinfo'] );
	exit;
}
add_action( 'admin_init', 'affwp_tools_sysinfo_download' );

/**
 * Debug Tab
 *
 * @since       1.7.15
 * @return      void
 */
function affwp_debug_tab() {
	?>
	<div id="affwp-dashboard-widgets-wrap">
		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php esc_html_e( 'Debug Log', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<form id="affwp-debug-log" method="post">
						<p><?php esc_html_e( 'Use this tool to help debug referral tracking.', 'affiliate-wp' ); ?></p>
						<textarea readonly="readonly" onclick="this.focus(); this.select()" class="large-text" rows="15" name="affwp-debug-log-contents"><?php echo esc_textarea( affiliate_wp()->utils->logs->get_log() ); ?></textarea>
						<p class="submit">
							<input type="hidden" name="affwp_action" value="submit_debug_log" />
							<?php
							submit_button( __( 'Download Debug Log File', 'affiliate-wp' ), 'primary', 'affwp-download-debug-log', false );
							submit_button( __( 'Clear Log', 'affiliate-wp' ), 'secondary affwp-inline-button', 'affwp-clear-debug-log', false  );
							?>
						</p>
						<?php wp_nonce_field( 'affwp-debug-log-action' ); ?>
					</form>
				</div><!-- .inside -->
			</div><!-- .postbox -->
			<?php if ( isset( $_REQUEST['advanced'] ) ) : ?>
				<div class="postbox">
					<h3><span><?php esc_html_e( 'Options', 'affiliate-wp' ); ?></span></h3>
					<div class="inside">
						<table class="widefat affwp_repeatable_table striped" width="100%" cellpadding="0" cellspacing="0">
							<thead>
							<tr>
								<th width="50%"><strong><?php _ex( 'Key', 'Option key', 'affiliate-wp' ); ?></strong></th>
								<th><strong><?php _ex( 'Value', 'Option value', 'affiliate-wp' ); ?></strong></th>
							</tr>
							</thead>
							<tbody>
								<?php foreach ( affwp_debug_get_option_keys() as $key ) :
									$value = get_option( $key, '' );

									if ( 'affwp_usage_tracking_last_checkin' === $key ) {
										$value = affwp_date_i18n( $value, 'datetime' );
									}
									?>
									<tr>
										<td><code><?php echo esc_html( $key ); ?></code></td>
										<td><code><?php echo esc_html( $value ); ?></code></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div><!-- .inside -->
				</div><!-- .postbox -->

				<div class="postbox">
					<h3><span><?php esc_html_e( 'Constants', 'affiliate-wp' ); ?></span></h3>
					<div class="inside">
						<table class="widefat affwp_repeatable_table striped" width="100%" cellpadding="0" cellspacing="0">
							<thead>
							<tr>
								<th width="50%"><strong><?php _ex( 'Name', 'Constant name', 'affiliate-wp' ); ?></strong></th>
								<th><strong><?php _ex( 'Value', 'Constant value', 'affiliate-wp' ); ?></strong></th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( affwp_debug_get_constants() as $name ) :
								$value = defined( $name ) ? constant( $name ) : '(undefined)';
								?>
								<tr>
									<td><code><?php echo esc_html( $name ); ?></code></td>
									<td><code><?php echo esc_html( $value ); ?></code></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div><!-- .inside -->
				</div><!-- .postbox -->
			<?php endif; ?>
		</div><!-- .metabox-holder -->
	</div><!-- #affwp-dashboard-widgets-wrap -->
<?php
}
add_action( 'affwp_tools_tab_debug', 'affwp_debug_tab' );

/**
 * Retrieves options keys to display in the advanced section of the debug assistant.
 *
 * @since 2.8
 *
 * @return string[] List of options keys.
 */
function affwp_debug_get_option_keys() {

	$keys = array(
		'affwp_version',
		'affwp_version_upgraded_from',
		'affwp_usage_tracking_last_checkin',
		'affwp_js_works',
		'affwp_is_installed',
		'affwp_alltime_earnings',
	);

	$tables = array(
		affiliate_wp()->affiliates->table_name,
		affiliate_wp()->affiliate_meta->table_name,
		affiliate_wp()->campaigns->table_name,
		affiliate_wp()->affiliates->coupons->table_name,
		affiliate_wp()->creatives->table_name,
		affiliate_wp()->customers->table_name,
		affiliate_wp()->customer_meta->table_name,
		affiliate_wp()->affiliates->payouts->table_name,
		affiliate_wp()->referrals->table_name,
		affiliate_wp()->referrals->sales->table_name,
		affiliate_wp()->REST->consumers->table_name,
		affiliate_wp()->visits->table_name,
	);

	foreach ( $tables as $table ) {
		$keys[] = $table . '_db_version';
	}

	return $keys;
}

/**
 * Retrieves a list of core AffiliateWP constants.
 *
 * @since 2.8
 *
 * @return string[] List of constants to display the values for.
 */
function affwp_debug_get_constants() {
	return array(
		'AFFILIATEWP_VERSION',
		'AFFILIATE_WP_NETWORK_WIDE',
		'AFFILIATE_WP_DEBUG',
		'AFFILIATEWP_LICENSE_KEY',
		'AFFILIATEWP_PLUGIN_DIR',
		'AFFILIATEWP_PLUGIN_URL',
		'AFFILIATEWP_PLUGIN_DIR_NAME',
		'AFFILIATEWP_PLUGIN_FILE',
		'AFFILIATEWP_PLUGIN_LIB_DIR',
		'AFFILIATE_WP_EXPORT_CHARSET',
		'AFFILIATEWP_PAYPAL_IPN',
		'PAYOUTS_SERVICE_NAME',
		'PAYOUTS_SERVICE_URL',
		'PAYOUTS_SERVICE_DOCS_URL',
	);
}

/**
 * Generate Coupons Tab
 *
 * @since  2.6
 * @return void
 */
function affwp_coupons_tab() {
	$dynamic_coupon_template = affiliate_wp()->settings->get( 'coupon_template_woocommerce' )
	?>
	<div id="affwp-dashboard-widgets-wrap">
		<div class="metabox-holder">

			<div class="postbox">
				<h3><span><?php esc_html_e( 'Dynamic Coupons', 'affiliate-wp' ); ?></span></h3>
				<div class="inside">
					<p><?php esc_html_e( 'Generate dynamic coupons for all affiliates.', 'affiliate-wp' ); ?></p>
					<?php if ( $dynamic_coupon_template ): ?>
						<form method="post" enctype="multipart/form-data" class="affwp-batch-form" data-batch_id="create-dynamic-coupons" data-nonce="<?php echo esc_attr( wp_create_nonce( 'create-dynamic-coupons_step_nonce' ) ); ?>">
							<p>
								<label>
									<input type="checkbox" name="override_coupon" value="1">
									<span class="">Override existing coupon for all affiliates</span>
								</label>
							</p>
							<p>
								<?php submit_button( __( 'Generate Coupons', 'affiliate-wp' ), 'secondary', 'create-dynamic-coupons-submit', false ); ?>
							</p>
						</form>
					<?php else: ?>
						<p class="description">
							<?php
							/* translators: Coupons settings screen URL */
							printf( __( 'Generating coupons requires a <a href="%s" target="_blank">Coupon Template</a> to be selected.', 'affiliate-wp' ), esc_url( affwp_admin_url( 'settings', array( 'tab' => 'coupons' ) ) ) );
							?>
						</p>
					<?php endif; ?>
				</div><!-- .inside -->
			</div><!-- .postbox -->

		</div><!-- .metabox-holder -->
	</div><!-- #affwp-dashboard-widgets-wrap -->
	<?php
}
add_action( 'affwp_tools_tab_coupons', 'affwp_coupons_tab' );

/**
 * Handles submit actions for the debug log.
 *
 * @since 2.1
 */
function affwp_submit_debug_log() {
	if ( ! current_user_can( 'manage_affiliate_options' ) ) {
		return;
	}

	check_admin_referer( 'affwp-debug-log-action' );

	if ( isset( $_REQUEST['affwp-download-debug-log'] ) ) {
		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="affwp-debug-log.txt"' );

		echo wp_strip_all_tags( $_REQUEST['affwp-debug-log-contents'] );
		exit;

	} elseif ( isset( $_REQUEST['affwp-clear-debug-log'] ) ) {

		// Clear the debug log.
		affiliate_wp()->utils->logs->clear_log();

		wp_safe_redirect( affwp_admin_url( 'tools', array( 'tab' => 'debug' ) ) );
		exit;

	}
}
add_action( 'affwp_submit_debug_log', 'affwp_submit_debug_log' );

/**
 * Clear the debug log
 *
 * @since       1.7.15
 * @deprecated  2.1 See affwp_submit_debug_log
 * @see         affwp_submit_debug_log()
 */
function affwp_clear_debug_log() {
	_deprecated_function( __FUNCTION__, '2.1', 'affwp_submit_debug_log' );

	affwp_submit_debug_log();
}

/**
 * Renders the API Keys tools tab.
 *
 * @since 1.9
 */
function affwp_rest_api_keys_tab() {
	if ( ! current_user_can( 'manage_consumers' ) ) {
		return;
	}

	$keys_table = new \AffWP\REST\Admin\Consumers_Table;
	$keys_table->prepare_items();

	$keys_table->views();
	$keys_table->display();
}
add_action( 'affwp_tools_tab_api_keys', 'affwp_rest_api_keys_tab' );

/**
 * Processes a batch export download request.
 *
 * @since 2.0
 */
function affwp_process_batch_export_download() {
	if( ! wp_verify_nonce( $_REQUEST['nonce'], 'affwp-batch-export' ) ) {
		wp_die(
			__( 'Nonce verification failed', 'affiliate-wp' ),
			__( 'Error', 'affiliate-wp' ),
			array( 'response' => 403 )
		);
	}

	if ( empty( $_REQUEST['batch_id'] ) || false === $batch = affiliate_wp()->utils->batch->get( $_REQUEST['batch_id'] ) ) {
		wp_die(
			__( 'Invalid batch ID.', 'affiliate-wp' ),
			__( 'Error', 'affiliate-wp' ),
			array( 'response' => 403 )
		);
	}

	require_once $batch['file'];

	if ( empty( $batch['class'] ) || ( ! empty( $batch['class'] ) && ! class_exists( $batch['class'] ) ) ) {
		wp_die(
			__( 'Invalid batch export class.', 'affiliate-wp' ),
			__( 'Error', 'affiliate-wp' ),
			array( 'response' => 403 )
		);
	}

	$export = new $batch['class']( $step = 0 );
	$export->export();

}
add_action( 'affwp_download_batch_export', 'affwp_process_batch_export_download' );
