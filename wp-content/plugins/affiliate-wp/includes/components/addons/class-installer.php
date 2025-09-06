<?php
/**
 * Addons: Installer
 *
 * @package     AffiliateWP
 * @subpackage  Components/Addons
 * @copyright   Copyright (c) 2022, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.6
 */

namespace AffWP\Components\Addons;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Class for implementing the addons installer.
 *
 * @since 2.9.6
 */
class Installer {

	/**
	 * AffiliateWP_Addons_Installer constructor.
	 *
	 * @since 2.9.6
	 */
	public function __construct() {
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/misc.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	/**
	 * Installs an Addon
	 *
	 * @since 2.9.6
	 *
	 * @param int $addon_id Addon ID.
	 * @return bool True if the addon was successfully installed, otherwise false.
	 */
	public function install_addon( $addon_id ) {

		// check if user can install plugins.
		if ( ! $this->can_install_plugins() ) {
			return array(
				'success' => false,
				'error' => __( 'User doesn&#8217;t have permission to install plugins.', 'affiliate-wp' ),
			);
		}

		// Check filesystem credentials.
		if ( ! $this->check_filesystem_credentials() ) {
			return array(
				'success' => false,
				'error' => __( 'User doesn&#8217;t have permission to install plugins.', 'affiliate-wp' ),
			);
		}

		// Request addon data from affiliatewp.com.
		$addon_url = $this->get_addon_url( $addon_id );
		if ( ! $addon_url ) {
			return array(
				'success' => false,
				'error' => __( 'Connection with License Manager has failed.', 'affiliate-wp' ),
			);
		}

		// Install addon.
		$installer = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
		$installer->install( $addon_url );

		// Flush cache.
		wp_cache_flush();

		// Try to activate the plugin.
		if ( $installer->plugin_info() ) {

			if ( ! current_user_can( 'activate_plugins' ) ) {

				return array(
					'success' => false,
					'error' => __( 'User doesn&#8217;t have permission to install plugins.', 'affiliate-wp' ),
				);
			}

			activate_plugin( $installer->plugin_info() );
		}

		return array(
			'success' => true,
			'error' => '',
		);
	}

	/**
	 * Installs a growth tool plugin.
	 *
	 * @since 2.13.0
	 *
	 * @param int $plugin_url Addon URL.
	 * @return bool True if the plugin was successfully installed, otherwise false.
	 */
	public function install_plugin( $plugin_url ) {

		// Check if user can install plugins.
		if ( ! $this->can_install_plugins() ) {

			return array(
				'success' => false,
				'error' => __( 'User doesn&#8217;t have permission to install plugins.', 'affiliate-wp' ),
			);
		}

		// Check filesystem credentials.
		if ( ! $this->check_filesystem_credentials() ) {
			return array(
				'success' => false,
				'error' => __( 'User doesn&#8217;t have permission to install plugins.', 'affiliate-wp' ),
			);
		}

		// Install plugin.
		$installer = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
		$installer->install( $plugin_url );

		// Flush cache.
		wp_cache_flush();

		// Try to activate the plugin.
		if ( $installer->plugin_info() ) {

			if ( ! current_user_can( 'activate_plugins' ) ) {

				return array(
					'success' => false,
					'error' => __( 'User doesn&#8217;t have permission to activate plugins.', 'affiliate-wp' ),
				);
			}

			activate_plugin( $installer->plugin_info() );
		}

		return array(
			'success' => true,
			'error'   => '',
		);
	}

	/**
	 * Determines if current user can install plugins
	 *
	 * @since 2.9.6
	 *
	 * @return bool True if the current user can install plugins, otherwise false.
	 */
	private function can_install_plugins() {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Check if file modifications are allowed.
		if ( ! wp_is_file_mod_allowed( 'affwp_can_install_plugins' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks for file system permissions.
	 *
	 * @since 2.9.6
	 *
	 * @return bool True if credentials accepted, otherwise false.
	 */
	private function check_filesystem_credentials() {
		// Hide the filesystem credentials form.
		ob_start();

		$creds = request_filesystem_credentials( esc_url_raw( admin_url( 'index.php?page=affiliatewp-onboarding' ) ), '', false, false, null );

		ob_end_clean();

		// Check for file system permissions.
		if ( ! $creds || ! WP_Filesystem( $creds ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Fetches the addon url from affiliatewp.com
	 *
	 * @since 2.9.6
	 *
	 * @param int $addon_id Addon ID.
	 * @return String|bool The addon url or false if couldn't get the url.
	 */
	public function get_addon_url( $addon_id ) {
		global $wp_version;

		// get license key.
		$license = affiliate_wp()->settings->get( 'license_key' );

		if ( empty( $license ) ) {
			return false;
		}

		$request = wp_remote_post( 'https://affiliatewp.com', array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      =>  array(
				'affwp_action'  => 'get_addon_download',
				'license'       => $license,
				'id'            => $addon_id,
				'url'           => home_url(),
				'php_version'   => phpversion(),
				'affwp_version' => get_option( 'affwp_version' ),
				'wp_version'    => $wp_version,
		) ) );

		if ( is_wp_error( $request ) ) {
			return false;
		}

		$version_info = json_decode( wp_remote_retrieve_body( $request ) );
		if ( empty( $version_info->download_link ) ) {
			return false;
		}

		return $version_info->download_link;
	}

}
