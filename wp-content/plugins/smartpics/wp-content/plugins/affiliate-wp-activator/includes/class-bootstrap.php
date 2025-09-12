<?php

namespace Tomi\AffiliateWpActivator;

defined( 'ABSPATH' ) or exit;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

class Bootstrap {

	private static $instance;
	private $mu_loader;

	public function __construct() {

		$this->mu_loader = new Must_Use_Loader( PN_B40560_PLUGIN, PN_B40560_PREFIX );
	}

	public static function on_activation() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "activate-plugin_$plugin" );

		// Access the $mu_loader object via the singleton instance
		$instance = self::get_instance();
		$instance->mu_loader->mu_set();
	}

	public static function get_instance(): Bootstrap {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function on_deactivation() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "deactivate-plugin_$plugin" );

		$instance = self::get_instance();
		$instance->mu_loader->mu_remove();
	}

	public static function on_uninstall() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		check_admin_referer( 'bulk-plugins' );

		$instance = self::get_instance();
		$instance->mu_loader->mu_remove();

		if ( __FILE__ != WP_UNINSTALL_PLUGIN ) {
			/** @noinspection PhpUnnecessaryStopStatementInspection */
			return;
		}
	}

	public static function init() {

		// Ensure the plugin is running within WordPress
		if ( ! defined( 'ABSPATH' ) ) {
			exit;
		}

		// Get the Update URI from the plugin header
		$update_uri = self::get_update_uri();

		if ( empty( $update_uri ) ) {
			return; // Exit if no update URI is provided
		}

		if ( ! class_exists( 'Puc_v5_Factory' ) ) {
			require PN_B40560_DIR . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
		}

		// Initialize the update checker
		PucFactory::buildUpdateChecker( $update_uri, PN_B40560_DIR . '/affiliate-wp-activator.php', 'affiliate-wp-activator' );
	}

	private static function get_update_uri(): string {

		$plugin_file = wp_normalize_path( PN_B40560_DIR . '/affiliate-wp-activator.php' );
		$plugin_data = get_file_data( $plugin_file, [ 'UpdateURI' => 'Update URI' ] );

		return ! empty( $plugin_data['UpdateURI'] ) ? esc_url( $plugin_data['UpdateURI'] ) : '';

	}
}