<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WPF_WP_Remote_Users_Sync extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.10
	 * @var string $slug
	 */

	public $slug = 'wp-remote-users-sync';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.10
	 * @var string $name
	 */
	public $name = 'WP Remote Users Sync';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.10
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/other/wp-remote-users-sync/';

	/**
	 * The WPRUS API.
	 *
	 * @since 3.38.12
	 * @var array $api
	 */
	public $api;

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.35.9
	 * @return  void
	 */
	public function init() {

		add_action( 'wprus_ready', array( $this, 'ready' ), 10, 4 );
		add_filter( 'wprus_action_data', array( $this, 'merge_contact_data' ), 10, 3 );

		add_action( 'wpf_tags_applied', array( $this, 'tags_modified' ) );
		add_action( 'wpf_tags_removed', array( $this, 'tags_modified' ) );

		// Catch incoming tag changes from remote sites.
		add_action( 'wprus_before_handle_action_notification', array( $this, 'before_handle_action_notification' ), 10, 2 );
		add_action( 'wprus_after_handle_action_notification', array( $this, 'handle_action_notification' ), 10, 3 );
	}

	/**
	 * Runs when WRPUS is ready and makes the API available to WP Fusion.
	 *
	 * @since 3.38.12
	 *
	 * @param Wprus          $wprus        The main WPRUS plugin.
	 * @param array          $api          The WPRUS API interfaces.
	 * @param Wprus_Settings $settings     The WPRUS settings.
	 * @param Wprus_Logger   $wprus_logger The logger.
	 */
	public function ready( $wprus, $api, $settings, $wprus_logger ) {

		$this->api = $api;
	}


	/**
	 * Merge the CID and tags into the create and update requests
	 *
	 * @since 3.35.9
	 *
	 * @param array  $data     The data.
	 * @param string $endpoint The endpoint.
	 * @param string $url      The URL.
	 * @return array Data
	 */
	public function merge_contact_data( $data, $endpoint, $url ) {

		if ( 'create' === $endpoint || 'update' === $endpoint ) {

			$user = get_user_by( 'login', $data['username'] );

			if ( $user ) {

				$contact_id = wp_fusion()->user->get_contact_id( $user->ID );

				if ( ! empty( $contact_id ) ) {

					$data[ WPF_CONTACT_ID_META_KEY ] = $contact_id;
					$data[ WPF_TAGS_META_KEY ]       = wp_fusion()->user->get_tags( $user->ID );

					wpf_log( 'info', $user->ID, ucwords( $endpoint ) . ' action triggered. Synced tags to remote site ' . $url . ':', array( 'tag_array' => $data[ WPF_TAGS_META_KEY ] ) );

				}
			}
		}

		return $data;
	}


	/**
	 * When the tags are modified, notify the remote site.
	 *
	 * @since 3.38.12
	 *
	 * @param int $user_id The user ID.
	 */
	public function tags_modified( $user_id ) {

		// Tells WPRUS the tags have been modified.
		$this->api['update']->track_updates( $user_id );
	}

	/**
	 * Prevent the normal user register actions from running when a user is synced
	 * from the remote site.
	 *
	 * @since 3.45.5
	 *
	 * @param string $endpoint The endpoint.
	 * @param array  $data     The data.
	 */
	public function before_handle_action_notification( $endpoint, $data ) {

		foreach ( $data as $key => $value ) {

			if ( false !== strpos( $key, '_contact_id' ) ) {

				if ( 'create' === $endpoint ) {
					remove_action( 'user_register', array( wp_fusion()->user, 'user_register' ), 20 );
				} elseif ( 'update' === $endpoint ) {
					remove_action( 'profile_update', array( wp_fusion()->user, 'profile_update' ), 10 );
				}

				return;
			}
		}
	}

	/**
	 * Trigger appropriate actions when tags are modified via incoming request.
	 *
	 * @since 3.35.9
	 *
	 * @param string $endpoint The endpoint.
	 * @param array  $data     The data.
	 * @param bool   $result   The result.
	 */
	public function handle_action_notification( $endpoint, $data, $result ) {

		if ( $result && ( 'update' === $endpoint || 'create' === $endpoint ) ) {

			if ( empty( $data[ WPF_CONTACT_ID_META_KEY ] ) ) {

				// See if we need to handle data coming in from a site connected to a different CRM.

				foreach ( $data as $key => $value ) {

					if ( false !== strpos( $key, '_contact_id' ) ) {
						$data[ WPF_CONTACT_ID_META_KEY ] = $value;
						unset( $data[ $key ] );
					} elseif ( false !== strpos( $key, '_tags' ) ) {
						$data[ WPF_TAGS_META_KEY ] = $value;
						unset( $data[ $key ] );
					}
				}
			}

			if ( ! empty( $data[ WPF_CONTACT_ID_META_KEY ] ) ) {

				$user = get_user_by( 'login', $data['username'] );

				if ( $user ) {
					$user_id = $user->ID;
				} else {
					$user_id = wpf_get_user_id( $data[ WPF_CONTACT_ID_META_KEY ] ); // try to get it from an existing contact ID.
				}

				if ( $user_id ) {

					update_user_meta( $user_id, WPF_CONTACT_ID_META_KEY, $data[ WPF_CONTACT_ID_META_KEY ] );

					if ( isset( $data[ WPF_TAGS_META_KEY ] ) ) {

						wp_fusion()->logger->add_source( $data['base_url'] );

						wp_fusion()->user->set_tags( $data[ WPF_TAGS_META_KEY ], $user_id );

					}
				}
			}
		}
	}
}

new WPF_WP_Remote_Users_Sync();
