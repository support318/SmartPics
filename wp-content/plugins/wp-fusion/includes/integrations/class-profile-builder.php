<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Profile_Builder extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'profile-builder';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'Profile builder';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/profile-builder-pro/';

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */
	public function init() {

		// User registrations.

		// This cleans the user cache when activating an account via activation link. Fixes
		// an issue with W3 Total Cache where custom profile fields aren't synced.

		add_action( 'wpf_user_register_start', 'clean_user_cache' );

		// Sync custom profile fields after they've been copied over to the usermeta table
		// after activation via an email link.

		if ( isset( $_GET['activation_key'] ) ) {

			remove_action( 'user_register', array( wp_fusion()->user, 'user_register' ), 20 );
			add_action( 'wppb_activate_user', array( wp_fusion()->user, 'user_register' ) );

		}

		// Profile updates
		add_filter( 'wpf_user_update', array( $this, 'profile_update' ), 10, 2 );

		// WPF stuff
		add_filter( 'wpf_meta_box_post_types', array( $this, 'unset_wpf_meta_boxes' ) );
	}

	/**
	 * Profile Update
	 * Format profile update post data.
	 *
	 * @since unknown
	 * @since 3.42.13 Changed media ID to media URL for sync.
	 *
	 * @param  array $user_meta User Meta.
	 * @param  int   $user_id   User ID.
	 *
	 * @return  array User Meta
	 */
	public function profile_update( $user_meta, $user_id ) {

		// Convert media ID to URL.
		foreach ( $user_meta as $field => $data ) {
			if ( is_numeric( $data ) ) {
				if ( ! empty( wp_get_attachment_url( $data ) ) ) {
					$user_meta[ $field ] = wp_get_attachment_url( $data );
				}
			}
		}

		$field_map = array(
			'email'   => 'user_email',
			'passw1'  => 'user_pass',
			'website' => 'user_url',
		);

		$user_meta = $this->map_meta_fields( $user_meta, $field_map );

		return $user_meta;
	}


	/**
	 * Removes standard WPF meta boxes from Profile Builder admin pages
	 *
	 * @access  public
	 * @return  array Post Types
	 */
	public function unset_wpf_meta_boxes( $post_types ) {

		unset( $post_types['wppb-roles-editor'] );

		return $post_types;
	}


	/**
	 * Adds Profile Builder field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['profile_builder'] = array(
			'title' => __( 'Profile Builder', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/membership/profile-builder-pro/',
		);

		return $field_groups;
	}

	/**
	 * Adds User Meta meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */
	public function add_meta_fields( $meta_fields ) {

		foreach ( get_option( 'wppb_manage_fields', array() ) as $field ) {

			if ( empty( $field['meta-name'] ) ) {
				continue;
			}

			if ( $field['field'] == 'Checkbox' ) {
				$field['field'] = 'checkboxes';
			}

			$meta_fields[ $field['meta-name'] ] = array(
				'label' => $field['field-title'],
				'type'  => strtolower( $field['field'] ),
				'group' => 'profile_builder',
			);

		}

		return $meta_fields;
	}
}

new WPF_Profile_Builder();
