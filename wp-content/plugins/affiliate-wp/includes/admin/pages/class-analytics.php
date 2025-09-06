<?php
/**
 * Admin: Analytics
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2022, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.5
 */

namespace Affwp\Admin\Pages;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Analytics Sub-page.
 *
 * Add interactive admin subpage that allow installing and activating MonsterInsights plugin.
 *
 * @since 2.9.5
 */
class Analytics {

	/**
	 * Admin menu page slug.
	 *
	 * @since 2.9.5
	 *
	 * @var string
	 */
	const SLUG = 'affiliate-wp-analytics';

	/**
	 * Configuration.
	 *
	 * @since 2.9.5
	 *
	 * @var array
	 */
	private $config = array(
		'lite_plugin'             => 'google-analytics-for-wordpress/googleanalytics.php',
		'lite_wporg_url'          => 'https://wordpress.org/plugins/google-analytics-for-wordpress/',
		'lite_download_url'       => 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip',
		'pro_plugin'              => 'google-analytics-premium/googleanalytics-premium.php',
		'ecommerce_addon'         => 'ga-ecommerce/ga-ecommerce.php',
		'mi_ecommerce_addon_page' => 'https://www.monsterinsights.com/?utm_campaign=affiliateWP&utm_source=affiliateWP&utm_medium=link',
		'mi_onboarding'           => 'admin.php?page=monsterinsights-onboarding',
		'mi_addons'               => 'admin.php?page=monsterinsights_settings#/addons',
		'mi_ecommerce'            => 'admin.php?page=monsterinsights_reports#/ecommerce',
	);

	/**
	 * Runtime data used for generating page HTML.
	 *
	 * @since 2.9.5
	 *
	 * @var array
	 */
	private $output_data = array();

	/**
	 * Constructor.
	 *
	 * @since 2.9.5
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Get the instance of a class and store it in itself.
	 *
	 * @since 2.9.5
	 */
	public static function get_instance() {

		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Renders the Analytics page content.
	 *
	 * @since 2.9.5
	 *
	 * @return void
	 */
	public static function display() {
		self::get_instance()->output();
	}

	/**
	 * Hooks.
	 *
	 * @since 2.9.5
	 */
	private function hooks() {

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_affwp_analytics_page_check_plugin_status', array( $this, 'ajax_check_plugin_status' ) );
		}

		// Check what page we are on.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.CSRF.NonceVerification

		// Only load if we are actually on the Analytics page.
		if ( self::SLUG !== $page ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'redirect_to_mi_ecommerce' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue JS and CSS files.
	 *
	 * @since 2.9.5
	 */
	public function enqueue_assets() {

		$plugin_url = untrailingslashit( AFFILIATEWP_PLUGIN_URL );
		$min        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Lightweight, accessible and responsive lightbox.
		wp_enqueue_style(
			'affiliate-wp-lity',
			"{$plugin_url}/assets/css/lity{$min}.css",
			null,
			'2.4.1'
		);

		wp_enqueue_script(
			'affiliate-wp-lity',
			"{$plugin_url}/assets/js/lity{$min}.js",
			array( 'jquery' ),
			'2.4.1',
			true
		);

		// Analytics page style and script.
		wp_enqueue_style(
			'affiliate-wp-analytics',
			"{$plugin_url}/assets/css/mi-analytics{$min}.css",
			null,
			AFFILIATEWP_VERSION
		);

		wp_enqueue_script(
			'affiliate-wp-analytics',
			"{$plugin_url}/assets/js/mi-analytics{$min}.js",
			array( 'jquery' ),
			AFFILIATEWP_VERSION,
			true
		);

		wp_localize_script(
			'affiliate-wp-analytics',
			'affiliate_wp_analytics',
			$this->get_js_strings()
		);
	}

	/**
	 * JS Strings.
	 *
	 * @since 2.9.5
	 *
	 * @return array Array of strings.
	 */
	protected function get_js_strings() {

		$error_could_not_install = sprintf(
			wp_kses( /* translators: %s - Lite plugin download URL. */
				__( 'Could not install the plugin automatically. Please <a href="%s">download</a> it and install it manually.', 'affiliate-wp' ),
				array(
					'a' => array(
						'href' => true,
					),
				)
			),
			esc_url( $this->config['lite_download_url'] )
		);

		$error_could_not_activate = sprintf(
			wp_kses( /* translators: %s - Lite plugin download URL. */
				__( 'Could not activate the plugin. Please activate it on the <a href="%s">Plugins page</a>.', 'affiliate-wp' ),
				array(
					'a' => array(
						'href' => true,
					),
				)
			),
			esc_url( admin_url( 'plugins.php' ) )
		);

		return array(
			'nonce'                    => wp_create_nonce( 'affiliate-wp-admin' ),
			'ajax_url'                 => admin_url( 'admin-ajax.php' ),
			'installing'               => esc_html__( 'Installing...', 'affiliate-wp' ),
			'activating'               => esc_html__( 'Activating...', 'affiliate-wp' ),
			'activated'                => esc_html__( 'MonsterInsights Installed & Activated', 'affiliate-wp' ),
			'install_now'              => esc_html__( 'Install Now', 'affiliate-wp' ),
			'activate_now'             => esc_html__( 'Activate Now', 'affiliate-wp' ),
			'download_now'             => esc_html__( 'Download Now', 'affiliate-wp' ),
			'plugins_page'             => esc_html__( 'Go to Plugins page', 'affiliate-wp' ),
			'error_could_not_install'  => $error_could_not_install,
			'error_could_not_activate' => $error_could_not_activate,
			'mi_manual_install_url'    => $this->config['lite_download_url'],
			'mi_manual_activate_url'   => admin_url( 'plugins.php' ),
		);
	}

	/**
	 * Generate and output page HTML.
	 *
	 * @since 2.9.5
	 */
	public function output() {
		?>
		<div id="affwp-am-plugin-analytics" class="wrap affwp-am-plugin-page">
			<?php
			$this->output_section_heading();
			$this->output_section_screenshot();
			$this->output_section_step_install();
			$this->output_section_step_setup();
			$this->output_section_step_addon();
			?>
		</div>
		<?php
	}

	/**
	 * Generate and output heading section HTML.
	 *
	 * @since 2.9.5
	 */
	private function output_section_heading() {

		// Heading section.
		printf(
			'<section class="top">
				<img class="img-top" src="%1$s" alt="%2$s"/>
				<h1>%3$s</h1>
				<p>%4$s</p>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/analytics/logo-lockup.svg' ),
			esc_attr__( 'AffiliateWP ♥ MonsterInsights', 'affiliate-wp' ),
			esc_html__( 'Get The #1 Best WordPress Analytics Plugin', 'affiliate-wp' ),
			esc_html__( 'Easily track affiliate sales, clicks, and more with MonsterInsights Pro and AffiliateWP. MonsterInsights includes an easy to use WordPress analytics dashboard, and integrates automatically with Google Analytics, no coding needed.', 'affiliate-wp' )
		);
	}

	/**
	 * Generate and output heading section HTML.
	 *
	 * @since 2.9.5
	 */
	protected function output_section_screenshot() {

		// Screenshot section.
		printf(
			'<section class="screenshot">
				<div class="cont">
					<img src="%1$s" alt="%2$s"/>
					<a href="%3$s" class="hover" data-lity></a>
				</div>
				<ul>
					<li>%4$s</li>
					<li>%5$s</li>
					<li>%6$s</li>
					<li>%7$s</li>
				</ul>
			</section>',
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/analytics/screenshot-tnail.png' ),
			esc_attr__( 'MonsterInsights eCommerce report screenshot', 'affiliate-wp' ),
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/analytics/screenshot-full.png' ),
			esc_html__( 'Track each individual affiliate’s traffic and sales directly inside Google Analytics.', 'affiliate-wp' ),
			esc_html__( 'Unlock advanced reporting capabilities and reports.', 'affiliate-wp' ),
			esc_html__( 'One-click enhanced eCommerce tracking for WooCommerce, Easy Digital Downloads, and more.', 'affiliate-wp' ),
			esc_html__( 'Automatic integration with AffiliateWP.', 'affiliate-wp' )
		);
	}

	/**
	 * Generate and output step 'Install' section HTML.
	 *
	 * @since 2.9.5
	 */
	protected function output_section_step_install() {

		$step = $this->get_data_step_install();

		if ( empty( $step ) ) {
			return;
		}

		$button_format       = '<button class="button %3$s" data-plugin="%1$s" data-action="%4$s">%2$s</button>';
		$button_allowed_html = array(
			'button' => array(
				'class'       => true,
				'data-plugin' => true,
				'data-action' => true,
			),
		);

		if (
			! $this->output_data['plugin_installed'] &&
			! $this->output_data['pro_plugin_installed'] &&
			! current_user_can( 'install_plugins' )
		) {
			$button_format       = '<a class="link" href="%1$s" target="_blank" rel="nofollow noopener">%2$s <span aria-hidden="true" class="dashicons dashicons-external"></span></a>';
			$button_allowed_html = array(
				'a'    => array(
					'class'  => true,
					'href'   => true,
					'target' => true,
					'rel'    => true,
				),
				'span' => array(
					'class'       => true,
					'aria-hidden' => true,
				),
			);
		}

		$button = sprintf( $button_format, esc_attr( $step['plugin'] ), esc_html( $step['button_text'] ), esc_attr( $step['button_class'] ), esc_attr( $step['button_action'] ) );

		printf(
			'<section class="step step-install">
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
			esc_attr__( 'Step 1', 'affiliate-wp' ),
			esc_html( $step['heading'] ),
			esc_html( $step['description'] ),
			wp_kses( $button, $button_allowed_html )
		);
	}

	/**
	 * Generate and output step 'Setup' section HTML.
	 *
	 * @since 2.9.5
	 */
	protected function output_section_step_setup() {

		$step = $this->get_data_step_setup();

		if ( empty( $step ) ) {
			return;
		}

		printf(
			'<section class="step step-setup %1$s">
				<aside class="num">
					<img src="%2$s" alt="%3$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%4$s</h2>
					<p>%5$s</p>
					<button class="button %6$s" data-url="%7$s">%8$s</button>
				</div>
			</section>',
			esc_attr( $step['section_class'] ),
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/' . $step['icon'] ),
			esc_attr__( 'Step 2', 'affiliate-wp' ),
			esc_html__( 'Setup MonsterInsights', 'affiliate-wp' ),
			esc_html__( 'Run the MonsterInsights setup wizard to properly configure your website for Google Analytics and AffiliateWP.', 'affiliate-wp' ),
			esc_attr( $step['button_class'] ),
			esc_url( admin_url( $this->config['mi_onboarding'] ) ),
			esc_html( $step['button_text'] )
		);
	}

	/**
	 * Generate and output step 'Addon' section HTML.
	 *
	 * @since 2.9.5
	 */
	protected function output_section_step_addon() {

		$step = $this->get_data_step_addon();

		if ( empty( $step ) ) {
			return;
		}

		printf(
			'<section class="step step-addon %1$s">
				<aside class="num">
					<img src="%2$s" alt="%3$s" />
					<i class="loader hidden"></i>
				</aside>
				<div>
					<h2>%4$s</h2>
					<p>%5$s</p>
					<button class="button %6$s" data-url="%7$s">%8$s</button>
				</div>
			</section>',
			esc_attr( $step['section_class'] ),
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/' . $step['icon'] ),
			esc_attr__( 'Step 3', 'affiliate-wp' ),
			esc_html__( 'Get AffiliateWP Tracking', 'affiliate-wp' ),
			esc_html( $step['description'] ),
			esc_attr( $step['button_class'] ),
			esc_url( $step['button_url'] ),
			esc_html( $step['button_text'] )
		);
	}

	/**
	 * Step 'Install' data.
	 *
	 * @since 2.9.5
	 *
	 * @return array Step data.
	 */
	protected function get_data_step_install() {

		$step                = array();
		$step['heading']     = esc_html__( 'Install & Activate MonsterInsights', 'affiliate-wp' );
		$step['description'] = esc_html__( 'Install MonsterInsights by clicking the button below.', 'affiliate-wp' );

		$this->output_data['all_plugins']          = get_plugins();
		$this->output_data['plugin_installed']     = array_key_exists( $this->config['lite_plugin'], $this->output_data['all_plugins'] );
		$this->output_data['plugin_activated']     = false;
		$this->output_data['pro_plugin_installed'] = array_key_exists( $this->config['pro_plugin'], $this->output_data['all_plugins'] );
		$this->output_data['pro_plugin_activated'] = false;

		if ( ! $this->output_data['plugin_installed'] && ! $this->output_data['pro_plugin_installed'] ) {

			$step['icon']          = 'step-1.svg';
			$step['button_text']   = esc_html__( 'Install MonsterInsights', 'affiliate-wp' );
			$step['button_class']  = 'button-primary';
			$step['button_action'] = 'install';
			$step['plugin']        = $this->config['lite_download_url'];

			if ( ! current_user_can( 'install_plugins' ) ) {

				$step['heading']     = esc_html__( 'MonsterInsights', 'affiliate-wp' );
				$step['description'] = '';
				$step['button_text'] = esc_html__( 'MonsterInsights on WordPress.org', 'affiliate-wp' );
				$step['plugin']      = $this->config['lite_wporg_url'];
			}
		} else {

			$this->output_data['plugin_activated'] = is_plugin_active( $this->config['lite_plugin'] ) || is_plugin_active( $this->config['pro_plugin'] );
			$step['icon']                          = $this->output_data['plugin_activated'] ? 'step-complete.svg' : 'step-1.svg';
			$step['button_text']                   = $this->output_data['plugin_activated'] ? esc_html__( 'MonsterInsights Installed & Activated', 'affiliate-wp' ) : esc_html__( 'Activate MonsterInsights', 'affiliate-wp' );
			$step['button_class']                  = $this->output_data['plugin_activated'] ? 'grey disabled' : 'button-primary';
			$step['button_action']                 = $this->output_data['plugin_activated'] ? '' : 'activate';
			$step['plugin']                        = $this->output_data['pro_plugin_installed'] ? $this->config['pro_plugin'] : $this->config['lite_plugin'];
		}

		return $step;
	}

	/**
	 * Step 'Setup' data.
	 *
	 * @since 2.9.5
	 *
	 * @return array Step data.
	 */
	protected function get_data_step_setup() {

		$step = array();

		$this->output_data['plugin_setup'] = false;

		if ( $this->output_data['plugin_activated'] ) {
			$this->output_data['plugin_setup'] = function_exists( 'monsterinsights_get_ua' ) && '' !== (string) monsterinsights_get_ua();
		}

		$step['icon']          = 'step-2.svg';
		$step['section_class'] = $this->output_data['plugin_activated'] ? '' : 'grey';
		$step['button_text']   = esc_html__( 'Run Setup Wizard', 'affiliate-wp' );
		$step['button_class']  = 'grey disabled';

		if ( $this->output_data['plugin_setup'] ) {
			$step['icon']          = 'step-complete.svg';
			$step['section_class'] = '';
			$step['button_text']   = esc_html__( 'Setup Complete', 'affiliate-wp' );
		} else {
			$step['button_class'] = $this->output_data['plugin_activated'] ? 'button-primary' : 'grey disabled';
		}

		return $step;
	}

	/**
	 * Step 'Addon' data.
	 *
	 * @since 2.9.5
	 *
	 * @return array Step data.
	 */
	protected function get_data_step_addon() {

		$step = array();

		$step['icon']          = 'step-3.svg';
		$step['section_class'] = $this->output_data['plugin_setup'] ? '' : 'grey';
		$step['button_text']   = esc_html__( 'Learn More', 'affiliate-wp' );
		$step['button_class']  = 'grey disabled';
		$step['button_url']    = '';
		$step['description']   = esc_html__( 'Purchase and download MonsterInsights Pro to instantly connect AffiliateWP with MonsterInsights. Get our special offer and save 50% today!', 'affiliate-wp' );

		$plugin_license_level = false;

		if ( $this->output_data['plugin_activated'] ) {

			$mi = \MonsterInsights();

			$plugin_license_level = 'lite';
			if ( is_object( $mi->license ) && method_exists( $mi->license, 'license_can' ) ) {
				$plugin_license_level = $mi->license->license_can( 'plus' ) ? 'lite' : $plugin_license_level;
				$plugin_license_level = $mi->license->license_can( 'pro' ) || $mi->license->license_can( 'agency' ) ? 'pro' : $plugin_license_level;
			}
		}

		switch ( $plugin_license_level ) {
			case 'lite':
				$step['button_url']   = $this->config['mi_ecommerce_addon_page'];
				$step['button_class'] = $this->output_data['plugin_setup'] ? 'button-primary' : 'grey';
				break;

			case 'pro':
				$addon_installed      = array_key_exists( $this->config['ecommerce_addon'], $this->output_data['all_plugins'] );
				$step['button_text']  = $addon_installed ? esc_html__( 'Activate Now', 'affiliate-wp' ) : esc_html__( 'Install Now', 'affiliate-wp' );
				$step['description']  = esc_html__( 'Enable the eCommerce addon to automatically start tracking sales.', 'affiliate-wp' );
				$step['button_url']   = admin_url( $this->config['mi_addons'] );
				$step['button_class'] = $this->output_data['plugin_setup'] ? 'button-primary' : 'grey';
				break;
		}

		return $step;
	}

	/**
	 * Ajax endpoint. Check plugin setup status.
	 * Used to properly init step 2 section after completing step 1.
	 *
	 * @since 2.9.5
	 */
	public function ajax_check_plugin_status() {

		// Security checks.
		if ( ! check_ajax_referer( 'affiliate-wp-admin', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
				)
			);
		}

		$result = array();

		if ( ! function_exists( 'MonsterInsights' ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Plugin unavailable.', 'affiliate-wp' ),
				)
			);
		}

		$result['setup_status'] = (int) ( function_exists( 'monsterinsights_get_ua' ) && '' !== (string) monsterinsights_get_ua() );

		$mi = \MonsterInsights();

		$result['license_level']    = 'lite';
		$result['step3_button_url'] = $this->config['mi_ecommerce_addon_page'];

		if ( is_object( $mi->license ) && method_exists( $mi->license, 'license_can' ) ) {
			$result['license_level']    = $mi->license->license_can( 'pro' ) || $mi->license->license_can( 'agency' ) ? 'pro' : $result['license_level'];
			$result['step3_button_url'] = admin_url( $this->config['mi_addons'] );
		}

		$result['addon_installed'] = (int) array_key_exists( $this->config['ecommerce_addon'], get_plugins() );

		wp_send_json_success( $result );
	}

	/**
	 * Redirect to MI ecommerce reporting page.
	 * We need this function because `is_plugin_active()` available only after `admin_init` action.
	 *
	 * @since 2.9.5
	 */
	public function redirect_to_mi_ecommerce() {

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Redirect to MI eCommerce addon if it is activated.
		if ( is_plugin_active( $this->config['ecommerce_addon'] ) ) {
			wp_safe_redirect( admin_url( $this->config['mi_ecommerce'] ) );
			exit;
		}
	}
}

// Init instance.
Analytics::get_instance();
