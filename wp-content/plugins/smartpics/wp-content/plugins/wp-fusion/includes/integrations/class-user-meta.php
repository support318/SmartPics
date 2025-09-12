<?php
/**
 * WP Fusion - User Meta Integration.
 *
 * @package WP Fusion
 * @copyright Copyright (c) 2024, Very Good Plugins, https://verygoodplugins.com
 * @license   GPL-3.0+
 * @since     3.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * User Meta integration class.
 *
 * @since 3.0.7
 */
class WPF_User_Meta extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */
	public $slug = 'user-meta';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'User meta';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/user-meta/';

	/**
	 * Gets things started.
	 *
	 * @since 3.0.7
	 */
	public function init() {

		add_filter( 'wpf_pulled_user_meta', array( $this, 'pulled_user_meta' ), 10, 2 );

		// Settings.
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		// Defer until activation.
		add_filter( 'user_meta_pre_user_register', array( $this, 'before_user_registration' ) );
		add_action( 'user_meta_user_activate', array( $this, 'after_user_activation' ) );
		add_action( 'user_meta_email_verified', array( $this, 'after_user_activation' ) );
		add_action( 'user_meta_user_approved', array( $this, 'after_user_activation' ) );

		// User Meta hooks.
		add_action( 'user_meta_after_user_update', array( $this, 'user_update' ), 10, 2 );
		add_action( 'user_meta_after_user_register', array( $this, 'user_update' ), 10 );
	}

	/**
	 * Adds User Meta field group to meta fields list.
	 *
	 * @since 3.0.7
	 *
	 * @param  array $field_groups The field groups.
	 * @return array Field groups.
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['usermeta'] = array(
			'title' => __( 'User Meta', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/membership/user-meta/',
		);

		return $field_groups;
	}

	/**
	 * Adds User Meta meta fields to WPF contact fields list.
	 *
	 * @since 3.0.7
	 *
	 * @param  array $meta_fields The meta fields.
	 * @return array Meta Fields.
	 */
	public function add_meta_fields( $meta_fields ) {

		// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
		global $userMeta;

		// Get shared fields.
		// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$fields = $userMeta->getData( 'fields' );

		if ( ! empty( $fields ) ) {

			foreach ( (array) $fields as $field ) {

				if ( ! isset( $field['meta_key'] ) ) {
					continue;
				}

				if ( 'datetime' === $field['field_type'] ) {
					$field['field_type'] = 'date';
				}

				$meta_fields[ $field['meta_key'] ] = array(
					'label' => $field['field_title'],
					'type'  => $field['field_type'],
					'group' => 'usermeta',
				);

			}
		}

		// Get form specific fields.
		// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$forms = $userMeta->getData( 'forms' );

		if ( ! empty( $forms ) ) {

			foreach ( $forms as $form ) {

				foreach ( $form['fields'] as $field ) {

					if ( ! isset( $field['meta_key'] ) ) {
						continue;
					}

					if ( 'datetime' === $field['field_type'] ) {
						$field['field_type'] = 'date';
					}

					$meta_fields[ $field['meta_key'] ] = array(
						'label' => $field['field_title'],
						'type'  => $field['field_type'],
						'group' => 'usermeta',
					);

				}
			}
		}

		return $meta_fields;
	}

	/**
	 * Add fields to settings page.
	 *
	 * @since 3.0.7
	 *
	 * @param  array $settings The settings.
	 * @param  array $options  The options.
	 * @return array Settings.
	 */
	public function register_settings( $settings, $options ) {

		$settings['ump_header'] = array(
			'title'   => __( 'User Meta Pro Integration', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'integrations',
		);

		// translators: %s is the CRM name.
		$description = sprintf( __( 'Don\'t send any data to %s until the account has been activated, either by an administrator or via email activation.', 'wp-fusion' ), wp_fusion()->crm->name );

		$settings['ump_defer'] = array(
			'title'   => __( 'Defer Until Activation', 'wp-fusion' ),
			'desc'    => $description,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		return $settings;
	}

	/**
	 * Triggered before registration, allows removing WPF create_user hook.
	 *
	 * @since 3.0.7
	 *
	 * @param  array $registration_data The registration data.
	 * @return array The registration data.
	 */
	public function before_user_registration( $registration_data ) {

		if ( true === wpf_get_option( 'ump_defer' ) ) {
			remove_action( 'user_register', array( wp_fusion()->user, 'user_register' ), 20 );
		}

		return $registration_data;
	}

	/**
	 * Triggered after activation, syncs the new user to the CRM.
	 *
	 * @since 3.0.7
	 *
	 * @param  int $user_id The user ID.
	 */
	public function after_user_activation( $user_id ) {

		if ( true === wpf_get_option( 'ump_defer' ) ) {
			wp_fusion()->user->user_register( $user_id );
		}
	}

	/**
	 * Format date fields when data is loaded.
	 *
	 * @since 3.0.7
	 *
	 * @param  array $user_meta The user meta.
	 * @param  int   $user_id   The user ID.
	 * @return array Meta data.
	 */
	public function pulled_user_meta( $user_meta, $user_id ) {

		// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
		global $userMeta;

		// Get shared fields.
		// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$fields = $userMeta->getData( 'fields' );

		if ( ! empty( $fields ) ) {

			foreach ( (array) $fields as $field ) {

				if ( ! isset( $field['meta_key'] ) ) {
					continue;
				}

				if ( ! empty( $user_meta[ $field['meta_key'] ] ) && 'datetime' === $field['field_type'] ) {

					if ( ! isset( $field['date_format'] ) ) {
						$format = 'Y-m-d';
					} else {
						$format = $field['date_format'];
					}

					$user_meta[ $field['meta_key'] ] = gmdate( $format, strtotime( $user_meta[ $field['meta_key'] ] ) );

				}
			}
		}

		// Get form specific fields.
		// @phpcs:ignore WordPress.NamingConventions.ValidVariableName
		$forms = $userMeta->getData( 'forms' );

		if ( ! empty( $forms ) ) {

			foreach ( $forms as $form ) {

				foreach ( $form['fields'] as $field ) {

					if ( ! isset( $field['meta_key'] ) ) {
						continue;
					}

					if ( ! empty( $user_meta[ $field['meta_key'] ] ) && 'datetime' === $field['field_type'] ) {

						if ( ! isset( $field['date_format'] ) ) {
							$format = 'Y-m-d';
						} else {
							$format = $field['date_format'];
						}

						$user_meta[ $field['meta_key'] ] = gmdate( $format, strtotime( $user_meta[ $field['meta_key'] ] ) );

					}
				}
			}
		}

		return $user_meta;
	}

	/**
	 * Push changes to user meta on profile update and registration.
	 *
	 * @since 3.0.7
	 *
	 * @param  array $response  The response data.
	 * @param  bool  $formname  The form name.
	 */
	public function user_update( $response, $formname = false ) {

		$user_meta = array();

		foreach ( $response as $key => $value ) {
			$user_meta[ $key ] = $value;
		}

		wp_fusion()->user->push_user_meta( $user_meta['ID'], $user_meta );
	}
}

new WPF_User_Meta();
