<?php
/**
 * AffiliateWP Admin Education Core.
 *
 * Load the resources necessary to handle AffiliateWP Product Education Modals.
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Education
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.18.0
 * @author      Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP\Admin\Education;

/**
 * Education core.
 *
 * @since 2.18.0
 */
class Core {

	/**
	 * Store the JS strings used to display contents dynamically inside modals.
	 *
	 * @since AFFWP
	 *
	 * @var array
	 */
	private array $js_strings = array();

	/**
	 * Indicate if Education core is allowed to load.
	 *
	 * @since 2.18.0
	 *
	 * @return bool
	 */
	protected function allow_load() : bool {
		return affwp_is_admin_page();
	}

	/**
	 * Load all hooks.
	 *
	 * @since 2.18.0
	 */
	public function init() {
		add_action( 'plugins_loaded', array( $this, 'hooks' ) );
	}

	/**
	 * Hooks.
	 *
	 * @since 2.18.0
	 */
	public function hooks() {

		// Only proceed if allowed.
		if ( ! $this->allow_load() ) {
			return;
		}

		$this->js_strings['ok']               = esc_html__( 'Ok', 'affiliate-wp' );
		$this->js_strings['cancel']           = esc_html__( 'Cancel', 'affiliate-wp' );
		$this->js_strings['close']            = esc_html__( 'Close', 'affiliate-wp' );
		$this->js_strings['ajax_url']         = admin_url( 'admin-ajax.php' );
		$this->js_strings['nonce']            = wp_create_nonce( 'affiliatewp-education' );
		$this->js_strings['activate_prompt']  = '<p>' . esc_html(
			sprintf( /* translators: %s - addon name. */
				__( 'The %s addon is installed but not activated. Would you like to activate it?', 'affiliate-wp' ),
				'%name%'
			)
		) . '</p>';
		$this->js_strings['activate_confirm'] = esc_html__( 'Yes, activate', 'affiliate-wp' );
		$this->js_strings['addon_activated']  = esc_html__( 'Addon activated', 'affiliate-wp' );
		$this->js_strings['addon_installed']  = esc_html__( 'Addon installed', 'affiliate-wp' );
		$this->js_strings['plugin_activated'] = esc_html__( 'Plugin activated', 'affiliate-wp' );
		$this->js_strings['activating']       = esc_html__( 'Activating', 'affiliate-wp' );
		$this->js_strings['install_prompt']   = '<p>' . esc_html(
			sprintf( /* translators: %s - addon name. */
				__( 'The %s addon is not installed. Would you like to install and activate it?', 'affiliate-wp' ),
				'%name%'
			)
		) . '</p>';
		$this->js_strings['install_confirm']  = esc_html__( 'Yes, install and activate', 'affiliate-wp' );
		$this->js_strings['installing']       = esc_html__( 'Installing', 'affiliate-wp' );
		$this->js_strings['save_prompt']      = esc_html__( 'Almost done! Would you like to refresh the settings?', 'affiliate-wp' );
		$this->js_strings['save_confirm']     = esc_html__( 'Yes, save and refresh', 'affiliate-wp' );
		$this->js_strings['saving']           = esc_html__( 'Loading ...', 'affiliate-wp' );

		// Check if the user can install addons.
		// Includes license check.
		$can_install_addons = true; // TODO: Do we have a function to check this?

		// Check if the user can install plugins.
		// Only checks if the user has the capability.
		// Needed to display the correct message for non-admin users.
		$can_install_plugins = current_user_can( 'install_plugins' );

		$this->js_strings['can_install_addons'] = $can_install_addons && $can_install_plugins;

		if ( ! $can_install_addons ) {
			$this->js_strings['install_prompt'] = '<p>' . esc_html(
				sprintf( /* translators: %s - addon name. */
					__( 'The %s is not installed. Please install and activate it to use this feature.', 'affiliate-wp' ),
					'%name%'
				)
			) . '</p>';
		}

		if ( ! $can_install_plugins ) {
			/* translators: %s - addon name. */
			$this->js_strings['install_prompt'] = '<p>' . esc_html(
				sprintf( /* translators: %s - addon name. */
					__( 'The %s is not installed. Please contact the site administrator.', 'affiliate-wp' ),
					'%name%'
				)
			) . '</p>';
		}

		// Check if the user can activate plugins.
		$this->js_strings['can_activate_addons'] = current_user_can( 'activate_plugins' );

		if ( ! $this->js_strings['can_activate_addons'] ) {
			/* translators: %s - addon name. */
			$this->js_strings['activate_prompt'] = '<p>' . esc_html( sprintf( __( 'The %s is not activated. Please contact the site administrator.', 'affiliate-wp' ), '%name%' ) ) . '</p>';
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
	}

	/**
	 * Load enqueues.
	 *
	 * @since 2.18.0
	 */
	public function enqueues() {

		// Load for all sites.
		affiliate_wp()->scripts->enqueue(
			'affiliatewp-admin-education-core',
			array(
				'jquery-confirm',
				'affwp_admin_addons',
			),
			sprintf(
				'%1$sadmin-education-core%2$s.js',
				affiliate_wp()->scripts->get_path(),
				affiliate_wp()->scripts->get_suffix(),
			)
		);

		wp_localize_script(
			'affiliatewp-admin-education-core',
			'affiliatewp_education',
			$this->get_js_strings()
		);
	}

	/**
	 * Localize common strings.
	 *
	 * @since 2.18.0
	 *
	 * @return array
	 */
	protected function get_js_strings() : array {
		/**
		 * Allow developers to extend the values to be localized.
		 *
		 * @since 2.18.0
		 *
		 * @param array $js_strings Strings to localize.
		 */
		return (array) apply_filters( 'affiliatewp_admin_education_strings', $this->js_strings );
	}
}

// Initiate.
( new Core() )->init();
