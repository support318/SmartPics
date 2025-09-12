<?php
/**
 * REST: Notifications Endpoints
 *
 * @package     AffiliateWP
 * @subpackage  REST
 * @copyright   Copyright (c) 2022, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.5
 */

namespace AffWP\Components\Notifications\REST\v1;

use \AffWP\REST\v1\Controller;
use AffWP\Components\Notifications\Notification;

#[\AllowDynamicProperties]

/**
 * Notification Endpoints class.
 *
 * Handles viewing and dismissing in-plugin notifications.
 *
 * @since 2.9.5
 */
class Notifications_Endpoints extends Controller {

	/**
	 * Object type.
	 *
	 * @since 2.9.5
	 * @access public
	 * @var string
	 */
	public $object_type = 'affwp_notifications';

	/**
	 * Route base for affiliates.
	 *
	 * @since 2.9.5
	 * @access public
	 * @var string
	 */
	public $rest_base = 'notifications';

	/**
	 * AffWP REST namespace.
	 *
	 * @since 2.9.5
	 * @access public
	 * @var string
	 */
	public $namespace = 'affwp/v1';

	/**
	 * Registers the endpoints.
	 *
	 * @since 2.9.5
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_notifications' ),
					'permission_callback' => array( $this, 'can_view_notification' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			"{$this->rest_base}/(?P<id>\d+)",
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'dismiss_notification' ),
					'permission_callback' => array( $this, 'can_view_notification' ),
					'args'                => array(
						'id' => array(
							'description'       => __( 'ID of the notification.', 'affiliate-wp' ),
							'type'              => 'integer',
							'required'          => true,
							'validate_callback' => function ( $param, $request, $key ) {
								$notification = affiliate_wp()->notifications->get( intval( $param ) );

								return ! empty( $notification );
							},
							'sanitize_callback' => function ( $param, $request, $key ) {
								return intval( $param );
							},
						),
					),
				),
			)
		);
	}

	/**
	 * Whether the current user can view (and dismiss) notifications.
	 *
	 * @since 2.9.5
	 *
	 * @return bool
	 */
	public function can_view_notification() {
		return current_user_can( 'manage_affiliate_options' );
	}

	/**
	 * Returns a list of notifications.
	 *
	 * @since 2.9.5
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 *
	 * @TODO At a later date we may want to receive dismissed notifications too.
	 */
	public function list_notifications( \WP_REST_Request $request ) {
		$active = array_map( function ( Notification $notification ) {
			return $notification->to_array();
		}, affiliate_wp()->notifications->get_active_notifications() );

		return new \WP_REST_Response( array(
			'active' => $active,
		) );
	}

	/**
	 * Dismisses a single notification.
	 *
	 * @since 2.9.5
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function dismiss_notification( \WP_REST_Request $request ) {
		$notification_removed = affiliate_wp()->notifications->update(
			$request->get_param( 'id' ),
			array( 'dismissed' => 1 )
		);

		if ( ! $notification_removed ) {
			return new \WP_REST_Response( array(
				'error' => __( 'Failed to dismiss notification.', 'affiliate-wp' ),
			), 500 );
		}

		wp_cache_delete( 'affwp_active_notification_count', 'affwp_notifications' );

		return new \WP_REST_Response( null, 204 );
	}
}
