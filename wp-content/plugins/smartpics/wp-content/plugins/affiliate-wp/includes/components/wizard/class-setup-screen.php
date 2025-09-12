<?php
/**
 * Wizard: Setup Screen
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Wizard
 * @copyright   Copyright (c) 2023, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.13.0
 */

namespace AffWP\Components\Wizard;

use AffWP\Components\Addons\Installer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Class for implementing the post-wizard setup screen.
 *
 * @since 2.13.0
 */
class Setup_Screen {
	/**
	 * Admin menu page slug.
	 *
	 * @since 2.13.0
	 *
	 * @var string
	 */
	const SLUG = 'affiliate-wp-setup-screen';

	/**
	 * Configuration.
	 *
	 * @since 2.13.0
	 *
	 * @var array
	 */
	private $config = array(
		'portal_slug'   => 'affiliatewp-affiliate-portal/affiliatewp-affiliate-portal.php',
		'upgrade_url'   => "https://affiliatewp.com/account/downloads?utm_source=WordPress&amp;utm_campaign=plugin&amp;utm_medium=setup&amp;utm_content=upgrade+Affiliate+Portal",
		'downloads_url' => 'https://affiliatewp.com/account/downloads/?utm_source=WordPress&amp;utm_campaign=plugin&amp;utm_medium=setup&amp;utm_content=download+Affiliate+Portal',
	);

	/**
	 * Constructor.
	 *
	 * @since 2.13.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Get the instance of a class and store it in itself.
	 *
	 * @since 2.13.0
	 */
	public static function get_instance() {

		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Renders the Setup Screen page content.
	 *
	 * @since 2.13.0
	 */
	public static function display() {
		self::get_instance()->output();
	}

	/**
	 * Hooks.
	 *
	 * @since 2.13.0
	 */
	private function hooks() {

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_affwp_customize_registration_step', array( $this, 'ajax_customize_form_intent_complete' ) );
			add_action( 'wp_ajax_affwp_add_yourself_step', array( $this, 'ajax_add_yourself_intent_complete' ) );
		}

		// Check for setup screen page.
		// Check the display option bool.
		// Check if current user is allowed to save settings.
		if ( ! isset( $_GET['page'] ) ||
					self::SLUG !== filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ||
					! get_option( 'affwp_display_setup_screen' ) ||
					! current_user_can( 'manage_affiliate_options' ) ) {
			return;
		}

		// Check if setup screen should be dismissed.
		if( ! empty( $_GET['affwp_dismiss_setup'] ) && $_GET['affwp_dismiss_setup'] ) {
			update_option( 'affwp_display_setup_screen', false );
			wp_safe_redirect( affwp_admin_url( 'affiliate-wp' ) );
			exit;
		}

		// Don't show any admin notices on this page.
		add_action( 'in_admin_header', function () {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}, 1000 );

		add_action( 'admin_enqueue_scripts', array( $this, 'affwp_enqueue_setup_assets' ) );
	}

	/**
	 * Enqueue JS and CSS files.
	 *
	 * @since 2.13.0
	 */
	public function affwp_enqueue_setup_assets() {
		$plugin_url = untrailingslashit( AFFILIATEWP_PLUGIN_URL );
		$min        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Setup screen page style and script.
		wp_enqueue_style(
			'affiliate-wp-setup-screen',
			"{$plugin_url}/assets/css/setup-screen{$min}.css",
			null,
			AFFILIATEWP_VERSION
		);

		wp_enqueue_script(
			'affiliate-wp-setup-screen',
			"{$plugin_url}/assets/js/setup-screen{$min}.js",
			array( 'jquery' ),
			AFFILIATEWP_VERSION,
			true
		);

		wp_localize_script(
			'affiliate-wp-setup-screen',
			'affiliatewpSetupScreen',
			$this->get_js_strings()
		);
	}

	/**
	 * JS Strings.
	 *
	 * @since 2.13.0
	 *
	 * @return array Array of strings.
	 */
	private function get_js_strings() {

		return array(
			'nonce'                       => wp_create_nonce( 'affiliate-wp-admin' ),
			'ajax_url'                    => admin_url( 'admin-ajax.php' ),
			'accessing'                   => esc_html__( 'Accessing...', 'affiliate-wp' ),
			'adding'                      => esc_html__( 'Adding...', 'affiliate-wp' ),
			'setup_screen_error'          => esc_html__( 'Something went wrong. Please try again.', 'affiliate-wp' ),
			'step_complete'               => esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/step-complete.svg' ),
			'registration_step_complete'  => __( 'Affiliate Registration Form Created', 'affiliate-wp'),
			'add_affiliate_step_complete' => __( 'Affiliate Added', 'affiliate-wp'),
			'portal_step_complete'        => __( 'Affiliate Portal Activated', 'affiliate-wp'),
			'manual_install_url'          => esc_url( $this->config['downloads_url'] ),
			'manual_activate_url'         => admin_url( 'plugins.php' ),
			'download_now'                => esc_html__( 'Download Now', 'affiliate-wp' ),
			'plugins_page'                => esc_html__( 'Go to Plugins page', 'affiliate-wp' ),
			'error_could_not_install'     => sprintf(
				wp_kses( /* translators: %s - AffiliateWP.com downloads page. */
					__( 'Could not install the plugin automatically. Please <a href="%s">download</a> it and install it manually.', 'affiliate-wp' ),
					array(
						'a' => array(
							'href' => true,
						),
					)
				),
				esc_url( $this->config['downloads_url'] )
			),
			'error_could_not_activate' => sprintf(
				wp_kses( /* translators: %s - Admin plugins page URL. */
					__( 'Could not activate the plugin. Please activate it on the <a href="%s">Plugins page</a>.', 'affiliate-wp' ),
					array(
						'a' => array(
							'href' => true,
						),
					)
				),
				esc_url( admin_url( 'plugins.php' ) )
			),
		);
	}

	/**
	 * Generate and output page HTML.
	 *
	 * @since 2.13.0
	 */
	public function output() {
		?>
		<div id="affwp-setup-screen-page" class="wrap">
			<?php

			// First, three steps that everyone gets by default.
			$this->output_section_step_registration_form();
			$this->output_section_step_add_yourself();
			$this->output_section_step_portal_addon();

			// If Payouts Service is enabled, show payout step.
			if ( affiliate_wp()->settings->get( 'enable_payouts_service' ) ) {
				$this->output_section_step_payouts();
			}

			$this->output_section_dismiss();

			?>
		</div>
		<?php
	}

	/**
	 * Generate and output step 'Registration Form' section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_step_registration_form() {
		$step = $this->get_data_step_registration_form();

		if ( empty( $step ) ) {
			return;
		}

		$button = sprintf(
			'<button class="button %2$s">%1$s</button>',
			esc_html( $step['button_text'] ),
			esc_attr( $step['button_class'] ),
		);

		$link = sprintf(
			'<a href="%1$s" class="%3$s" target="_blank">%2$s</a>',
			esc_attr( $step['button_url'] ),
			esc_html( $step['link_text'] ),
			esc_attr( $step['link_class'] ),
		);

		printf(
			'<section class="step step-registration-form">
				<aside class="num">
					<img src="%1$s" alt="%2$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%3$s</h2>
					<p>%4$s</p>
					<span>
						%5$s
						%6$s
					</span>
				</div>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/' . $step['icon'] ),
			esc_attr__( 'Step 1', 'affiliate-wp' ),
			esc_html( $step['heading'] ),
			esc_html( $step['description'] ),
			wp_kses(
				$button,
				array(
					'button' => array(
						'class'    => true,
						'data-url' => true,
					),
			) ),
			wp_kses(
				$link,
				array(
					'a' => array(
						'href'   => true,
						'class'  => true,
						'target' => true,
					),
			) ),
		);
	}

	/**
	 * Generate and output step 'Add Yourself' as an affiliate section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_step_add_yourself() {
		$step = $this->get_data_step_add_yourself();

		if ( empty( $step ) ) {
			return;
		}

		$button_format       = '<button class="button %2$s">%1$s</button>';
		$button_allowed_html = array(
			'button' => array(
				'class' => true,
			),
		);

		$button = sprintf(
			$button_format,
			esc_html( $step['button_text'] ),
			esc_attr( $step['button_class'] ),
		);

		// Only show link, if there is an affiliate edit URL.
		$link_allowed_html = array();

		if ( ! empty( $step['edit_link'] ) ) {
			$link_allowed_html = array(
				'a' => array(
					'href'   => true,
					'class'  => true,
					'target' => true,
				),
			);
		}

		printf(
			'<section class="step step-add-yourself">
				<aside class="num">
					<img src="%1$s" alt="%2$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%3$s</h2>
					<p>%4$s</p>
					<span>
						%5$s
						%6$s
					</span>
				</div>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/' . $step['icon'] ),
			esc_attr__( 'Step 2', 'affiliate-wp' ),
			esc_html( $step['heading'] ),
			esc_html( $step['description'] ),
			wp_kses( $button, $button_allowed_html ),
			wp_kses(
				$step['edit_link'],
				$link_allowed_html
			)
		);
	}

	/**
	 * Generate and output step 'Portal Addon' section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_step_portal_addon() {
		$step = $this->get_data_step_portal_addon();

		if ( empty( $step ) ) {
			return;
		}
		$desc_allowed_html  = array( 'p' => true );

		$button_format       = '<button class="button %2$s" data-action="%4$s" data-plugin="%3$s">%1$s</button>';
		$button_allowed_html = array(
			'button' => array(
				'class'       => true,
				'data-action' => true,
				'data-plugin' => true,
			),
		);

		// If upgrade is needed, link to Affiliate Account
		if ( 'upgrade' === $step['button_action'] ) {

			$desc_allowed_html  = array(
				'p'    => array(
					'class' => true,
				),
				'span' => true,
			);

			$button_format       = '<a href="%3$s" class="button %2$s" target="_blank" rel="noopener noreferrer">%1$s</a>';
			$button_allowed_html = array(
				'a' => array(
					'href'   => true,
					'class'  => true,
					'target' => true,
					'rel'    => true,
				),
			);
		}

		$button = sprintf(
			$button_format,
			esc_html( $step['button_text'] ),
			esc_attr( $step['button_class'] ),
			esc_attr( $step['button_plugin'] ),
			esc_attr( $step['button_action'] ),
		);

		printf(
			'<section class="step step-portal-addon">
				<aside class="num">
					<img src="%1$s" alt="%2$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%3$s<span class="affwp-addon-label-pro">pro</span></h2>
					%4$s
					%5$s
				</div>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/' . $step['icon'] ),
			esc_attr__( 'Step 3', 'affiliate-wp' ),
			wp_kses(
				$step['heading'],
				array( 'span' => true )
			),
			wp_kses( $step['description'], $desc_allowed_html ),
			wp_kses( $button, $button_allowed_html ),
		);
	}

	/**
	 * Generate and output step 'Payouts' section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_step_payouts() {
		$step = $this->get_data_step_payouts();

		if ( empty( $step ) ) {
			return;
		}

		$button_format       = '<a href="%3$s" class="button %2$s" target="_blank">%1$s</a>';
		$button_allowed_html = array(
			'a' => array(
				'href'   => true,
				'class'  => true,
				'target' => true,
			),
		);

		$button = sprintf(
			$button_format,
			esc_html( $step['button_text'] ),
			esc_attr( $step['button_class'] ),
			esc_attr( $step['button_url'] ),
		);

		printf(
			'<section class="step step-payouts">
				<aside class="num">
					<img src="%1$s" alt="%2$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%3$s</h2>
					<p>%4$s</p>
					%5$s
				</div>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/' . $step['icon'] ),
			esc_attr__( 'Step 4', 'affiliate-wp' ),
			esc_html( $step['heading'] ),
			esc_html( $step['description'] ),
			wp_kses( $button, $button_allowed_html )
		);
	}

	/**
	 * Generate and output dismiss section HTML.
	 *
	 * @since 2.13.0
	 */
	private function output_section_dismiss() {
		echo sprintf(
			'<p class="affwp-dismiss-setup">%s</p>',
			affwp_admin_link(
				'setup-screen',
				__( 'Dismiss Setup Screen', 'affiliate-wp' ),
				array( 'affwp_dismiss_setup' => '1' ),
			)
		);
	}

	/**
	 * Step 'Registration Form' data.
	 *
	 * @since 2.13.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_registration_form() {

		$step = array();

		// Get Affiliate Area page ID.
		$page_id = affiliate_wp()->settings->get( 'affiliates_page' );

		// Should be disabled by default because it's complete on plugin install.
		$step['heading']      = esc_html__( 'Create Your First Affiliate Registration Form', 'affiliate-wp' );
		$step['description']  = esc_html__( 'Every successful affiliate program begins with a registration form.', 'affiliate-wp' );
		$step['icon']         = 'step-complete.svg';
		$step['button_text']  = esc_html__( 'Affiliate Registration Form Created', 'affiliate-wp' );
		$step['button_class'] = esc_attr__( 'grey disabled' , 'affiliate-wp' );
		$step['button_url']   = admin_url( sprintf( 'post.php?post=%1$s&action=edit', $page_id ) );
		$step['link_text']    = esc_html__( 'Edit Form', 'affiliate-wp' );
		$step['link_class'] = 'affwp-setup-edit-link';

		// If no affiliate area page is set, disable button.
		if ( empty( $page_id ) ) {
			$step['link_text']    = esc_html__( 'Select Affiliate Area Page', 'affiliate-wp' );
			$step['button_url']   = affwp_admin_url( 'settings' );
		}

		return $step;
	}

	/**
	 * Step 'Add Yourself' as an affiliate data.
	 *
	 * @since 2.13.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_add_yourself() {

		$step = array();

		$step['heading']      = esc_html__( 'Add Your First Affiliate', 'affiliate-wp' );
		$step['description']  = esc_html__( 'Add yourself as your very first affiliate account so you can test AffiliateWP.', 'affiliate-wp' );
		$step['icon']         = 'step-2.svg';
		$step['button_text']  = esc_html__( 'Add Affiliate', 'affiliate-wp' );
		$step['button_class'] = 'button-primary';
		$step['edit_link']    = '';

		// Disable if completed or intent was completed by an admin.
		$setup_intent = get_option( 'affwp_setup_intent' );

		// Check if current user is an affiliate.
		$user_id = get_current_user_id();

		if ( affwp_is_affiliate( $user_id ) ) {
				$step['icon']          = 'step-complete.svg';
				$step['button_class']  = 'grey disabled';
				$step['button_action'] = '';
				$step['button_text']  = esc_html__( 'Affiliate Created', 'affiliate-wp' );
				$step['edit_link']     = affwp_admin_link(
					'affiliates',
					__( 'Edit Affiliate', 'affiliate-wp' ),
					array(
						'affwp_notice' => false,
						'action'       => 'edit_affiliate',
						'affiliate_id' => affwp_get_affiliate_id( $user_id )
					),
					array(
						'class'  => 'affwp-setup-edit-link',
						'target' => '_blank'
					)
				);
		}

		return $step;
	}

	/**
	 * Step 'Portal Addon' data.
	 *
	 * @since 2.13.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_portal_addon() {

		$step = array();

		$step['heading']      = sprintf(
			'%1$s<span>%2$s</span>',
			esc_html__( 'Level Up Your Affiliate Area:', 'affiliate-wp' ),
			esc_html__( 'Affiliate Portal Addon', 'affiliate-wp' )
		);
		$step['description']  = sprintf(
				'<p>%1$s</p>',
				esc_html__( 'Using the Affiliate Portal addon, you can give your affiliates a premium experience, ensuring they have everything they need to perform.', 'affiliate-wp' ),
			);
		$step['button_text']  = esc_html__( 'Install Now', 'affiliate-wp' );
		$step['icon']         = 'step-3.svg';
		$step['button_class'] = 'button-primary';

		$all_plugins      = get_plugins();
		$portal_installed = array_key_exists( $this->config['portal_slug'], $all_plugins );

		// Step is complete if active and installed.
		if ( $portal_installed && is_plugin_active( $this->config['portal_slug']) ) {
				$step['icon']          = 'step-complete.svg';
				$step['button_class']  = 'grey disabled';
				$step['button_action'] = '';
				$step['button_plugin'] = '';
				$step['button_text']  = esc_html__( 'Affiliate Portal Activated', 'affiliate-wp' );

				return $step;
		}

		// If Portal is installed but not active, activate it.
		if ( $portal_installed ) {
			$step['button_action'] = 'activate';
			$step['button_plugin'] = $this->config['portal_slug'];
			$step['button_text']   = esc_html__( 'Activate Now', 'affiliate-wp' );

			return $step;
		}

		// If not installed, check license.
		$license_data   = affiliate_wp()->settings->get( 'license_status', '' );
		$license_status = is_object( $license_data ) ? $license_data->license : $license_data;
		$price_id       = isset( $license_data->price_id ) ? intval( $license_data->price_id ) : false;

		// If license is not valid or Professional, link to their AffiliateWP.com account page to upgrade.
		if ( 'valid' !== $license_status || $price_id < 2 ) {
			$step['description']  = sprintf(
				'<p>%1$s</p><p class="affwp-desc-offer"><span>%2$s</span> %3$s</p>',
				esc_html__( 'Using the Affiliate Portal addon, you can give your affiliates a premium experience, ensuring they have everything they need to perform.', 'affiliate-wp' ),
				esc_html__( 'Special Upgrade Offer:', 'affiliate-wp' ),
				esc_html__( 'Get 60% off the regular price, automatically applied at checkout.', 'affiliate-wp' )
			);
			$step['button_text']   = esc_html__( 'Upgrade to Pro and Save 60%', 'affiliate-wp' );
			$step['button_action'] = 'upgrade';
			$step['button_plugin'] = esc_url( $this->config['downloads_url'] );

			return $step;
		}

		// Otherwise, install and activate the Affiliate Portal addon.
		$step['button_action'] = 'install';
		$step['button_plugin'] = ( new Installer() )->get_addon_url( 570647 );

		return $step;
	}

	/**
	 * Step 'Payouts' data.
	 *
	 * @since 2.13.0
	 *
	 * @return array Step data.
	 */
	private function get_data_step_payouts() {

		$step = array();

		$step['heading']      = esc_html__( 'Set Up the Payouts Service', 'affiliate-wp' );
		$step['description']  = esc_html__( 'Connect your site to the Payouts Service to pay your affiliates directly from a credit or debit card.', 'affiliate-wp' );
		$step['icon']         = 'step-4.svg';
		$step['button_text']  = esc_html__( 'Connect Your Site', 'affiliate-wp' );
		$step['button_class'] = 'button-primary';
		$step['button_url']   = affwp_admin_url( 'settings', array( 'tab' => 'payouts_service' ) );

		$connection_status = affiliate_wp()->settings->get( 'payouts_service_connection_status', '' );

		// Complete if Payouts connection is active.
		if ( 'active' === $connection_status ) {
			$step['icon']          = 'step-complete.svg';
			$step['button_class']  = 'grey disabled';
			$step['button_action'] = '';
			$step['button_text']  = esc_html__( 'Payouts Service Connected', 'affiliate-wp' );
		}

		return $step;
	}

	/**
	 * Ajax endpoint. Customize registration form setup intent complete.
	 *
	 * @since 2.13.0
	 */
	public function ajax_customize_form_intent_complete() {

		// Security check.
		if ( ! check_ajax_referer( 'affiliate-wp-admin', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
				)
			);
		}

		$setup_intent = get_option( 'affwp_setup_intent' );

		// Update setup intent so we know this step is complete.
		update_option(
			'affwp_setup_intent',
			array_merge(
				is_array( $setup_intent )
					? $setup_intent
					: array(),
				array( 'affwp_customize_registration_complete' => 1 ),
			)
		);

		wp_send_json_success();
	}

	/**
	 * Ajax endpoint. Add yourself as an affiliate intent complete.
	 *
	 * @since 2.13.0
	 */
	public function ajax_add_yourself_intent_complete() {

		// Security check.
		if ( ! check_ajax_referer( 'affiliate-wp-admin', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
				)
			);
		}

		// Add yourself as an affiliate.
		$user_id = get_current_user_id();

		$params = array(
			'user_id'             => $user_id,
			'status'              => 'active',
			'registration_method' => 'setup_screen',
		);
		if ( false === affwp_add_affiliate( $params ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Something went wrong adding you as an affiliate. Please try again.', 'affiliate-wp' ),
				)
			);
		}

		wp_send_json_success();
	}
}
