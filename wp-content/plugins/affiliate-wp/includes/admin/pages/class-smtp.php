<?php
/**
 * Admin: SMTP
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
 * SMTP Sub-page.
 *
 * Add interactive admin subpage that allow installing and activating WP Mail SMTP plugin.
 *
 * @since 2.9.5
 */
class SMTP {

	/**
	 * Admin menu page slug.
	 *
	 * @since 2.9.5
	 *
	 * @var string
	 */
	const SLUG = 'affiliate-wp-smtp';

	/**
	 * Configuration.
	 *
	 * @since 2.9.5
	 *
	 * @var array
	 */
	private $config = array(
		'lite_plugin'       => 'wp-mail-smtp/wp_mail_smtp.php',
		'lite_wporg_url'    => 'https://wordpress.org/plugins/wp-mail-smtp/',
		'lite_download_url' => 'https://downloads.wordpress.org/plugin/wp-mail-smtp.zip',
		'pro_plugin'        => 'wp-mail-smtp-pro/wp_mail_smtp.php',
		'smtp_settings_url' => 'admin.php?page=wp-mail-smtp',
		'smtp_wizard_url'   => 'admin.php?page=wp-mail-smtp-setup-wizard',
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
	 * Renders the SMTP page content.
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
			add_action( 'wp_ajax_affwp_am_smtp_page_check_plugin_status', array( $this, 'ajax_check_plugin_status' ) );
		}

		// Check what page we are on.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.CSRF.NonceVerification

		// Only load if we are actually on the SMTP page.
		if ( self::SLUG !== $page ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'redirect_to_smtp_settings' ) );
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

		// SMTP page style and script.
		wp_enqueue_style(
			'affiliate-wp-smtp',
			"{$plugin_url}/assets/css/smtp{$min}.css",
			null,
			AFFILIATEWP_VERSION
		);

		wp_enqueue_script(
			'affiliate-wp-smtp',
			"{$plugin_url}/assets/js/smtp{$min}.js",
			array( 'jquery' ),
			AFFILIATEWP_VERSION,
			true
		);

		wp_localize_script(
			'affiliate-wp-smtp',
			'affiliate_wp_smtp',
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
	private function get_js_strings() {

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
			'activated'                => esc_html__( 'WP Mail SMTP Installed & Activated', 'affiliate-wp' ),
			'install_now'              => esc_html__( 'Install Now', 'affiliate-wp' ),
			'activate_now'             => esc_html__( 'Activate Now', 'affiliate-wp' ),
			'download_now'             => esc_html__( 'Download Now', 'affiliate-wp' ),
			'plugins_page'             => esc_html__( 'Go to Plugins page', 'affiliate-wp' ),
			'error_could_not_install'  => $error_could_not_install,
			'error_could_not_activate' => $error_could_not_activate,
			'manual_install_url'       => $this->config['lite_download_url'],
			'manual_activate_url'      => admin_url( 'plugins.php' ),
			'smtp_settings'            => esc_html__( 'Go to SMTP settings', 'affiliate-wp' ),
			'smtp_wizard'              => esc_html__( 'Open Setup Wizard', 'affiliate-wp' ),
			'smtp_settings_url'        => esc_url( $this->config['smtp_settings_url'] ),
			'smtp_wizard_url'          => esc_url( $this->config['smtp_wizard_url'] ),
		);
	}

	/**
	 * Generate and output page HTML.
	 *
	 * @since 2.9.5
	 */
	public function output() {
		?>
		<div id="affwp-am-plugin-smtp" class="wrap affwp-am-plugin-page">
			<?php
			$this->output_section_heading();
			$this->output_section_screenshot();
			$this->output_section_step_install();
			$this->output_section_step_setup();
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
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/smtp/logo-lockup.svg' ),
			esc_attr__( 'AffiliateWP ♥ WP Mail SMTP', 'affiliate-wp' ),
			esc_html__( 'Making Email Deliverability Easy for WordPress', 'affiliate-wp' ),
			esc_html__( 'WP Mail SMTP fixes deliverability problems with your WordPress emails and form notifications. It\'s built by the same folks behind WPForms.', 'affiliate-wp' )
		);
	}

	/**
	 * Generate and output screenshot section HTML.
	 *
	 * @since 2.9.5
	 */
	private function output_section_screenshot() {

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
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/smtp/screenshot-tnail.png' ),
			esc_attr__( 'WP Mail SMTP screenshot', 'affiliate-wp' ),
			esc_url( AFFILIATEWP_PLUGIN_URL . 'assets/images/smtp/screenshot-full.png' ),
			esc_html__( 'Improves email deliverability in WordPress.', 'affiliate-wp' ),
			esc_html__( 'Used by 2+ million websites.', 'affiliate-wp' ),
			esc_html__( 'Free mailers: SendLayer, SMTP.com, Sendinblue, Google Workspace / Gmail, Mailgun, Postmark, SendGrid.', 'affiliate-wp' ),
			esc_html__( 'Pro mailers: Amazon SES, Microsoft 365/ Outlook.com, Zoho Mail.', 'affiliate-wp' )
		);
	}

	/**
	 * Generate and output step 'Install' section HTML.
	 *
	 * @since 2.9.5
	 */
	private function output_section_step_install() {

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
	private function output_section_step_setup() {

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
			esc_html__( 'Set Up WP Mail SMTP', 'affiliate-wp' ),
			esc_html__( 'Select and configure your mailer.', 'affiliate-wp' ),
			esc_attr( $step['button_class'] ),
			esc_url( admin_url( $this->config['smtp_wizard_url'] ) ),
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
	private function get_data_step_install() {

		$step = array();

		$step['heading']     = esc_html__( 'Install and Activate WP Mail SMTP', 'affiliate-wp' );
		$step['description'] = esc_html__( 'Install WP Mail SMTP from the WordPress.org plugin repository.', 'affiliate-wp' );

		$this->output_data['all_plugins']          = get_plugins();
		$this->output_data['plugin_installed']     = array_key_exists( $this->config['lite_plugin'], $this->output_data['all_plugins'] );
		$this->output_data['pro_plugin_installed'] = array_key_exists( $this->config['pro_plugin'], $this->output_data['all_plugins'] );
		$this->output_data['plugin_activated']     = false;
		$this->output_data['plugin_setup']         = false;

		if ( ! $this->output_data['plugin_installed'] && ! $this->output_data['pro_plugin_installed'] ) {
			$step['icon']          = 'step-1.svg';
			$step['button_text']   = esc_html__( 'Install WP Mail SMTP', 'affiliate-wp' );
			$step['button_class']  = 'button-primary';
			$step['button_action'] = 'install';
			$step['plugin']        = $this->config['lite_download_url'];

			if ( ! current_user_can( 'install_plugins' ) ) {
				$step['heading']     = esc_html__( 'WP Mail SMTP', 'affiliate-wp' );
				$step['description'] = '';
				$step['button_text'] = esc_html__( 'WP Mail SMTP on WordPress.org', 'affiliate-wp' );
				$step['plugin']      = $this->config['lite_wporg_url'];
			}
		} else {
			$this->output_data['plugin_activated'] = $this->is_smtp_activated();
			$this->output_data['plugin_setup']     = $this->is_smtp_configured();
			$step['icon']                          = $this->output_data['plugin_activated'] ? 'step-complete.svg' : 'step-1.svg';
			$step['button_text']                   = $this->output_data['plugin_activated'] ? esc_html__( 'WP Mail SMTP Installed & Activated', 'affiliate-wp' ) : esc_html__( 'Activate WP Mail SMTP', 'affiliate-wp' );
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
	private function get_data_step_setup() {

		$step = array(
			'icon' => 'step-2.svg',
		);

		if ( $this->output_data['plugin_activated'] ) {
			$step['section_class'] = '';
			$step['button_class']  = 'button-primary';
			$step['button_text']   = esc_html__( 'Open Setup Wizard', 'affiliate-wp' );
		} else {
			$step['section_class'] = 'grey';
			$step['button_class']  = 'grey disabled';
			$step['button_text']   = esc_html__( 'Start Setup', 'affiliate-wp' );
		}

		if ( $this->output_data['plugin_setup'] ) {
			$step['icon']        = 'step-complete.svg';
			$step['button_text'] = esc_html__( 'Go to SMTP settings', 'affiliate-wp' );
		}

		return $step;
	}

	/**
	 * Ajax endpoint. Check plugin setup status.
	 * Used to properly init step 'Setup' section after completing step 'Install'.
	 *
	 * @since 2.9.5
	 */
	public function ajax_check_plugin_status() {

		// Security check.
		if ( ! check_ajax_referer( 'affiliate-wp-admin', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
				)
			);
		}

		if ( ! $this->is_smtp_activated() ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Plugin unavailable.', 'affiliate-wp' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'setup_status'  => (int) $this->is_smtp_configured(),
				'license_level' => wp_mail_smtp()->get_license_type(),
			)
		);
	}

	/**
	 * Get $phpmailer instance.
	 *
	 * @since 2.9.5
	 *
	 * @return \PHPMailer|\PHPMailer\PHPMailer\PHPMailer Instance of PHPMailer.
	 */
	private function get_phpmailer() {

		if ( version_compare( get_bloginfo( 'version' ), '5.5-alpha', '<' ) ) {
			return $this->get_phpmailer_v5();
		}

		return $this->get_phpmailer_v6();
	}

	/**
	 * Get $phpmailer v5 instance.
	 *
	 * @since 2.9.5
	 *
	 * @return \PHPMailer Instance of PHPMailer.
	 */
	private function get_phpmailer_v5() {

		global $phpmailer;

		if ( ! ( $phpmailer instanceof \PHPMailer ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			require_once ABSPATH . WPINC . '/class-smtp.php';
			$phpmailer = new \PHPMailer( true ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $phpmailer;
	}

	/**
	 * Get $phpmailer v6 instance.
	 *
	 * @since 2.9.5
	 *
	 * @return \PHPMailer\PHPMailer\PHPMailer Instance of PHPMailer.
	 */
	private function get_phpmailer_v6() {

		global $phpmailer;

		if ( ! ( $phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			$phpmailer = new \PHPMailer\PHPMailer\PHPMailer( true ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $phpmailer;
	}

	/**
	 * Whether WP Mail SMTP plugin configured or not.
	 *
	 * @since 2.9.5
	 *
	 * @return bool True if some mailer is selected and configured properly.
	 */
	private function is_smtp_configured() {

		if ( ! $this->is_smtp_activated() ) {
			return false;
		}

		$phpmailer = $this->get_phpmailer();
		$mailer    = \WPMailSMTP\Options::init()->get( 'mail', 'mailer' );

		return ! empty( $mailer ) && 'mail' !== $mailer && wp_mail_smtp()
				->get_providers()
				->get_mailer( $mailer, $phpmailer )
				->is_mailer_complete();
	}

	/**
	 * Whether WP Mail SMTP plugin active or not.
	 *
	 * @since 2.9.5
	 *
	 * @return bool True if SMTP plugin is active.
	 */
	private function is_smtp_activated() {

		return function_exists( 'wp_mail_smtp' ) && ( is_plugin_active( $this->config['lite_plugin'] ) || is_plugin_active( $this->config['pro_plugin'] ) );
	}

	/**
	 * Redirect to SMTP settings page.
	 *
	 * @since 2.9.5
	 */
	public function redirect_to_smtp_settings() {

		// Redirect to SMTP plugin if it is activated.
		if ( $this->is_smtp_configured() ) {
			wp_safe_redirect( admin_url( $this->config['smtp_settings_url'] ) );
			exit;
		}
	}
}

// Init instance.
SMTP::get_instance();
