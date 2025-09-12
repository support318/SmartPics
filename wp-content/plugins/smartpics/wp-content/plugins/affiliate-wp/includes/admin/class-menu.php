<?php
/**
 * Admin: Menu
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

#[\AllowDynamicProperties]

/**
 * Sets up the Affiliates menu and core submenu pages in the WordPress admin.
 *
 * @since 1.0.0
 */
class Affiliate_WP_Admin_Menu {

	/**
	 * Registers any needed hook callbacks for the component.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ), 10 );
		add_action( 'admin_menu', array( $this, 'change_affiliates_admin_menu_title_to_affiliatewp' ), 11 );
	}

	/**
	 * Change the Affiliates Menu Item to AffiliateWP.
	 *
	 * Changing the menu title to AffiliateWP using add_menu_page() also changes the
	 * value of WP_Screen->id which Addons and various places in Core rely on.
	 *
	 * This ensures that we change the menu title but keep WP_Screen->id the same
	 * as it always has until we can afford an effort to change all references to
	 * WP_Screen->id to a new one and change the menu title using the
	 * natural add_menu_page() function.
	 *
	 * @TODO Set this using `add_menu_page()` in `$this->register_menus()` and switch all
	 *       Addon and core references to old `WP_Screen->id` since it will change.
	 *
	 * @since 2.9.5.1
	 */
	public function change_affiliates_admin_menu_title_to_affiliatewp() {

		$old_menu_title = 'Affiliates';

		global $menu;

		foreach ( $menu as $menu_position => $menu_item ) {

			if ( $old_menu_title !== $menu_item[0] ) {
				continue; // Not our menu item.
			}

			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Change Affiliates to AffiliateWP using global vs add_menu_page(), see docblock.
			$menu[ $menu_position ][0] = __( 'AffiliateWP', 'affiliate-wp' );
		}
	}

	/**
	 * Registers the Affiliates admin menu and submenu pages.
	 *
	 * @since 1.0.0
	 * @since 2.9.4  Added our Affiliate Reports link to main Dashboard menu.
	 * @since 2.12.0 Added Setup screen for onboarding new installs.
	 *
	 * @TODO See todo in $this->change_affiliates_admin_menu_title_to_affiliatewp docblock.
	 */
	public function register_menus() {
		global $submenu;

		add_menu_page(
			'AffiliateWP',
			'Affiliates', // Note, the reason we still have "Affiliates" here is because of backwards compatibility with get_current_screen()->id in which changing this would change the WP_Screen->id. See $this->change_affiliates_admin_menu_title_to_affiliatewp() docblock.
			'view_affiliate_reports',
			'affiliate-wp',
			'affwp_affiliates_dashboard',
			'',
			'25.9' . crc32( 'affiliate-wp' )
		);

		$overview   = add_submenu_page( 'affiliate-wp', __( 'Overview', 'affiliate-wp' ),    __( 'Overview', 'affiliate-wp' ),              'view_affiliate_reports',   'affiliate-wp',            'affwp_affiliates_dashboard' );
		$affiliates = add_submenu_page( 'affiliate-wp', __( 'Affiliates', 'affiliate-wp' ),  __( 'Affiliates', 'affiliate-wp' ),            'manage_affiliates',        'affiliate-wp-affiliates', 'affwp_affiliates_admin'     );
		$referrals  = add_submenu_page( 'affiliate-wp', __( 'Referrals', 'affiliate-wp' ),   __( 'Referrals', 'affiliate-wp' ),             'manage_referrals',         'affiliate-wp-referrals',  'affwp_referrals_admin'      );
		$payouts    = add_submenu_page( 'affiliate-wp', __( 'Payouts', 'affiliate-wp' ),     __( 'Payouts', 'affiliate-wp' ),               'manage_payouts',           'affiliate-wp-payouts',    'affwp_payouts_admin'        );
		$visits     = add_submenu_page( 'affiliate-wp', __( 'Visits', 'affiliate-wp' ),      __( 'Visits', 'affiliate-wp' ),                'manage_visits',            'affiliate-wp-visits',     'affwp_visits_admin'         );
		$creatives  = add_submenu_page( 'affiliate-wp', __( 'Creatives', 'affiliate-wp' ),   __( 'Creatives', 'affiliate-wp' ),             'manage_creatives',         'affiliate-wp-creatives',  'affwp_creatives_admin'      );
		$reports    = add_submenu_page( 'affiliate-wp', __( 'Reports', 'affiliate-wp' ),     __( 'Reports', 'affiliate-wp' ),               'view_affiliate_reports',   'affiliate-wp-reports',    'affwp_reports_admin'        );
		$tools      = add_submenu_page( 'affiliate-wp', __( 'Tools', 'affiliate-wp' ),       __( 'Tools', 'affiliate-wp' ),                 'manage_affiliate_options', 'affiliate-wp-tools',      'affwp_tools_admin'          );
		$settings   = add_submenu_page( 'affiliate-wp', __( 'Settings', 'affiliate-wp' ),    __( 'Settings', 'affiliate-wp' ),              'manage_affiliate_options', 'affiliate-wp-settings',   'affwp_settings_admin'       );
		$migration  = add_submenu_page( '', __( 'AffiliateWP Migration', 'affiliate-wp' ), __( 'AffiliateWP Migration', 'affiliate-wp' ), 'manage_affiliate_options', 'affiliate-wp-migrate',    'affwp_migrate_admin'        );
		$add_ons    = add_submenu_page(
			'affiliate-wp',
			__( 'Add-ons', 'affiliate-wp' ),
			sprintf(
				'<span style="color:#E34F43">%s</span>',
				__( 'Addons', 'affiliate-wp' )
			),
			'manage_affiliate_options',
			'affiliate-wp-add-ons',
			'affwp_add_ons_admin'
		);
		$analytics  = add_submenu_page( 'affiliate-wp', __( 'Analytics', 'affiliate-wp' ),   __( 'Analytics', 'affiliate-wp' ),             'view_affiliate_reports',   'affiliate-wp-analytics',  [ 'Affwp\Admin\Pages\Analytics', 'display' ] );
		$smtp       = add_submenu_page( 'affiliate-wp', __( 'SMTP', 'affiliate-wp' ),        __( 'SMTP', 'affiliate-wp' ),                  'view_affiliate_reports',   'affiliate-wp-smtp',       [ 'Affwp\Admin\Pages\SMTP', 'display' ] );
		$about      = add_submenu_page( 'affiliate-wp', __( 'About Us', 'affiliate-wp' ),    __( 'About Us', 'affiliate-wp' ),              'view_affiliate_reports',   'affiliate-wp-about',      [ 'Affwp\Admin\About', 'display' ] );

		// Add our reports link in the main Dashboard menu.
		$submenu['index.php'][] = array(
			__( 'Affiliate Reports', 'affiliate-wp' ),
			'view_affiliate_reports',
			'admin.php?page=affiliate-wp-reports',
		);

		// Only new installs see the setup screen (until it's dismissed.)
		if ( get_option( 'affwp_display_setup_screen' ) ) {
			add_submenu_page( 'affiliate-wp', __( 'Setup', 'affiliate-wp' ), __( 'Setup', 'affiliate-wp' ), 'manage_affiliate_options', 'affiliate-wp-setup-screen', [ 'AffWP\Components\Wizard\Setup_Screen', 'display' ], 0 );
		}

		add_action( 'load-' . $affiliates, 'affwp_affiliates_screen_options' );
		add_action( 'load-' . $referrals, 'affwp_referrals_screen_options' );
		add_action( 'load-' . $payouts, 'affwp_payouts_screen_options' );
		add_action( 'load-' . $visits, 'affwp_visits_screen_options' );
		add_action( 'load-' . $creatives, 'affwp_creatives_screen_options' );
	}

}

$affiliatewp_menu = new Affiliate_WP_Admin_Menu;
