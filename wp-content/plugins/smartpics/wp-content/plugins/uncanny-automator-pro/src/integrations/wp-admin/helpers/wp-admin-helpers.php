<?php

namespace Uncanny_Automator_Pro\Integrations\Wp_Admin;

/**
 * Class Wp_Admin_Helpers
 * @package Uncanny_Automator_Pro
 */
class Wp_Admin_Helpers {

	/**
	 * @return array
	 */
	public function get_all_installed_plugins() {
		$plugins   = get_plugins();
		$options   = array();
		$options[] = array(
			'value' => '-1',
			'text'  => __( 'Any plugin', 'uncanny-automator-pro' ),
		);
		foreach ( $plugins as $path => $plugin ) {
			$options[] = array(
				'value' => $plugin['Name'],
				'text'  => $plugin['Name'],
			);
		}

		return $options;
	}

	/**
	 * @return array[]
	 */
	public function plugin_theme_statuses() {

		return array(
			array(
				'value' => 'activated',
				'text'  => __( 'Activated', 'uncanny-automator-pro' ),
			),
			array(
				'value' => 'deactivated',
				'text'  => __( 'Deactivated', 'uncanny-automator-pro' ),
			),
		);

	}

	/**
	 * @return array
	 */
	public function get_all_installed_themes() {
		$themes    = wp_get_themes();
		$options   = array();
		$options[] = array(
			'value' => '-1',
			'text'  => __( 'Any theme', 'uncanny-automator-pro' ),
		);
		foreach ( $themes as $theme_slug => $theme ) {
			$options[] = array(
				'value' => $theme->get( 'Name' ),
				'text'  => $theme->get( 'Name' ),
			);
		}

		return $options;
	}

	/**
	 * @return array[]
	 */
	public function wp_admin_get_common_tokens_for_core_update() {
		return array(
			array(
				'tokenId'   => 'WP_UPGRADED_VERSION',
				'tokenName' => __( 'Version', 'uncanny-automator-pro' ),
				'tokenType' => 'float',
			),
			array(
				'tokenId'   => 'WP_UPGRADE_STATUS',
				'tokenName' => __( 'Status', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'WP_ERROR_MESSAGE',
				'tokenName' => __( 'Error message', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);
	}

	/**
	 * @return array[]
	 */
	public function wp_admin_get_common_tokens() {
		$core_tokens   = $this->wp_admin_get_common_tokens_for_core_update();
		$common_tokens = array(
			array(
				'tokenId'   => 'WP_NAME',
				'tokenName' => __( 'Name', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'WP_URI',
				'tokenName' => __( 'URI', 'uncanny-automator-pro' ),
				'tokenType' => 'url',
			),
			array(
				'tokenId'   => 'WP_DESTINATION_FOLDER',
				'tokenName' => __( 'Destination folder', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'WP_DESTINATION_PATH',
				'tokenName' => __( 'Destination path', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			),
		);

		return array_merge( $core_tokens, $common_tokens );
	}

	/**
	 * @param $object
	 *
	 * @return array
	 */
	public function wp_admin_parse_common_tokens( $hook_args, $type = 'core' ) {
		list( $object, $options ) = $hook_args;
		// Generate array of empty default values.
		$defaults       = wp_list_pluck( $this->wp_admin_get_common_tokens(), 'tokenId' );
		$trigger_tokens = array_fill_keys( $defaults, '' );

		$trigger_tokens['WP_UPGRADE_STATUS'] = 'Successfully updated';

		if ( ( empty( $object->result ) || is_wp_error( $object->result ) ) && isset( $object->skin ) ) {
			// Check if the skin itself is an error
			if ( is_wp_error( $object->skin ) ) {
				$errors = $object->skin;
			} elseif ( method_exists( $object->skin, 'get_errors' ) && is_wp_error( $object->skin->get_errors() ) ) {
				// In case there is a custom method get_errors
				$errors = $object->skin->get_errors();
			}

			if ( isset( $errors ) && is_wp_error( $errors ) ) {
				$trigger_tokens['WP_ERROR_MESSAGE']  = $errors->get_error_message();
				$trigger_tokens['WP_UPGRADE_STATUS'] = 'Failed to update';
			}
		}

		if ( 'core' !== $type ) {
			$trigger_tokens['WP_DESTINATION_PATH']   = ! empty( $object->result ) ? $object->result['destination'] : '';
			$trigger_tokens['WP_DESTINATION_FOLDER'] = ! empty( $object->result ) ? $object->result['destination_name'] : '';
			$names                                   = array();
			$versions                                = array();
			$URIs                                    = array();

			if ( 'plugins' === $type ) {
				$updated_plugins_themes = $options['plugins'];
				foreach ( $updated_plugins_themes as $k => $plugin ) {
					$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
					$names[ $k ]    = $plugin_data['Name'];
					$versions[ $k ] = $plugin_data['Version'];
					$URIs[ $k ]     = $plugin_data['PluginURI'];
				}
			}

			if ( 'themes' === $type ) {
				$updated_plugins_themes = $options['themes'];
				foreach ( $updated_plugins_themes as $k => $theme ) {
					$theme_data     = wp_get_theme( $theme );
					$names[ $k ]    = $theme_data->get( 'Name' );
					$versions[ $k ] = $theme_data->get( 'Version' );
					$URIs[ $k ]     = $theme_data->get( 'ThemeURI' );
				}
			}

			$trigger_tokens['WP_NAME']             = join( ', ', $names );
			$trigger_tokens['WP_UPGRADED_VERSION'] = join( ', ', $versions );
			$trigger_tokens['WP_URI']              = join( ', ', $URIs );
		}

		if ( 'core' === $type ) {
			$trigger_tokens['WP_UPGRADED_VERSION'] = ! empty( $object->result ) ? $object->result['destination_name'] : get_bloginfo( 'version' );
		}

		return $trigger_tokens;
	}
}
