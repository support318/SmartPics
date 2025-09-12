<?php
/**
 * Notification Importer
 *
 * @package    AffiliateWP
 * @subpackage Admin\Utils
 * @copyright  Copyright (c) 2022, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.9.5
 */

namespace AffWP\Utils;

use AffWP\Utils;

#[\AllowDynamicProperties]

/**
 * Notification Importer class.
 *
 * Handles importing notifications.
 *
 * @since 2.9.5
 */
class Notification_Importer {

	/**
	 * @var Environment_Checker
	 */
	protected $environment_checker;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->environment_checker = new Utils\Environment_Checker();
	}

	/**
	 * Fetches notifications from the API and imports them locally.
	 *
	 * @since 2.9.5
	 */
	public function run() {
		// Fetch notifications.
		try {
			$notifications = $this->fetch_notifications();
		} catch ( \Exception $e ) {
			// Bail if there's an exception.
			return;
		}

		foreach ( $notifications as $notification ) {
			$notification_id = isset( $notification->id ) ? $notification->id : 'unknown';

			// Process each notification by ID.
			try {
				$this->validate_notification( $notification );

				$existingId = affiliate_wp()->notifications->get_column_by( 'id', 'remote_id', $notification->id );
				if ( $existingId ) {
					// Update existing notification.
					$this->update_existing_notification( $existingId, $notification );
				} else {
					// Insert new notification.
					$this->add_new_notification( $notification );
				}
			} catch ( \Exception $e ) {
				// Processing failed for this notification ID.
			}
		}
	}

	/**
	 * Returns the API endpoint to query.
	 *
	 * @since 2.9.5
	 *
	 * @return string
	 */
	protected function get_api_endpoint() {
		if ( defined( 'AFFILIATEWP_NOTIFICATIONS_API_URL' ) ) {
			return AFFILIATEWP_NOTIFICATIONS_API_URL;
		}

		return 'https://plugin.affiliatewp.com/wp-content/notifications.json';
	}

	/**
	 * Retrieves notifications from the remote API endpoint.
	 *
	 * @since 2.9.5
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function fetch_notifications() {
		$response = wp_remote_get( $this->get_api_endpoint() );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$notifications = wp_remote_retrieve_body( $response );

		return ! empty( $notifications ) ? json_decode( $notifications ) : array();
	}

	/**
	 * Validates the notification from the remote API to make sure we actually
	 * want to save it.
	 *
	 * @since 2.9.5
	 *
	 * @param object $notification
	 *
	 * @throws \Exception
	 */
	public function validate_notification( $notification ) {
		// Make sure we have all the required data.
		$required_properties = array(
			'id',
			'title',
			'content',
		);

		$missing = array_diff( $required_properties, array_keys( get_object_vars( $notification ) ) );

		if ( $missing ) {
			throw new \Exception( sprintf( 'Missing required properties: %s', json_encode( array_values( $missing ) ) ) );
		}

		// Don't save the notification if it has expired.
		if ( ! empty( $notification->end ) && time() > strtotime( $notification->end ) ) {
			throw new \Exception( 'Notification has expired.' );
		}

		// Don't save notification if it started before the plugin installation.
		if (
			! empty( $notification->start ) &&
			null !== get_option( 'affwp_first_installed' ) &&
			get_option( 'affwp_first_installed' ) > strtotime( $notification->start )
		) {
			throw new \Exception( 'Start condition not met.' );
		}

		if (
			! empty( $notification->type ) &&
			is_array( $notification->type ) &&
			! $this->environment_checker->is_valid( $notification->type )
		) {
			throw new \Exception( 'Condition(s) not met.' );
		}
	}

	/**
	 * Retrieves the array of notification data to insert into the database.
	 * Use in both inserts and updates.
	 *
	 * @since 2.9.5
	 *
	 * @param object $notification
	 *
	 * @return array
	 */
	protected function get_notification_data( $notification ) {
		return array(
			'remote_id'  => $notification->id,
			'title'      => $notification->title,
			'content'    => $notification->content,
			'buttons'    => $this->parse_buttons( $notification ),
			'type'       => ! empty( $notification->notification_type ) ? $notification->notification_type : 'success',
			'conditions' => ! empty( $notification->type ) ? $notification->type : null,
			'start'      => ! empty( $notification->start ) ? $notification->start : null,
			'end'        => ! empty( $notification->end ) ? $notification->end : null,
		);
	}

	/**
	 * Parses and formats buttons from the remote notification object.
	 *
	 * @since 2.9.5
	 *
	 * @param object $notification
	 *
	 * @return array|null
	 */
	protected function parse_buttons( $notification ) {
		if ( empty( $notification->btns ) ) {
			return null;
		}

		$buttons = array();

		foreach ( (array) $notification->btns as $buttonType => $buttonInfo ) {
			if ( empty( $buttonInfo->url ) || empty( $buttonInfo->text ) ) {
				continue;
			}

			$buttons[] = array(
				'type' => ( 'main' === $buttonType ) ? 'primary' : 'secondary',
				'url'  => $buttonInfo->url,
				'text' => $buttonInfo->text,
			);
		}

		return ! empty( $buttons ) ? $buttons : '';
	}

	/**
	 * Attempts to add a new notification.
	 *
	 * @since 2.9.5
	 *
	 * @param object $notification
	 * @throws \Exception
	 */
	protected function add_new_notification( $notification ) {
		if ( ! $this->insert_new_notification( $notification ) ) {
			throw new \Exception( 'Failed to insert into database.' );
		}
	}

	/**
	 * Inserts a new notification into the database.
	 *
	 * @since 2.9.5
	 *
	 * @param object $notification
	 */
	protected function insert_new_notification( $notification ) {
		return affiliate_wp()->notifications->add( $this->get_notification_data( $notification ) );
	}


	/**
	 * Updates an existing notification.
	 *
	 * @since 2.9.5
	 *
	 * @param int    $existingId
	 * @param object $notification
	 */
	protected function update_existing_notification( $existingId, $notification ) {
		affiliate_wp()->notifications->update_notification(
			$existingId,
			wp_parse_args(
				$this->get_notification_data( $notification ),
				array(
					'date_updated' => gmdate( 'Y-m-d H:i:s' ),
				)
			)
		);
	}
}
