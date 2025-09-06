<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Notifications Database
 *
 * @package    AffiliateWP
 * @subpackage Components/Notifications
 * @copyright  Copyright (c) 2022, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.9.5
 */

namespace AffWP\Components\Notifications;

use AffWP\Utils;

#[AllowDynamicProperties]

/**
 * Notifications database class.
 *
 * @since 2.9.5
 *
 * @see Affiliate_WP_DB
 */
class Notifications_DB extends \Affiliate_WP_DB {

	/**
	 * REST API.
	 *
	 * @since Unknown
	 *
	 * @var null
	 */
	public $REST = null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Cache group for queries.
	 *
	 * @internal DO NOT change. This is used externally both as a cache group and shortcut
	 *           for accessing db class instances via affiliate_wp()->{$cache_group}->*.
	 *
	 * @access public
	 * @since  2.9.5
	 * @var    string
	 */
	public $cache_group = 'affwp_notifications';

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb, $wp_version;

		$this->table_name  = "{$wpdb->prefix}affiliate_wp_notifications";
		$this->primary_key = 'id';
		$this->version     = '1.0';

		// REST endpoints.
		if ( version_compare( $wp_version, '4.4', '>=' ) ) {
			$this->REST = new \AffWP\Components\Notifications\REST\v1\Notifications_Endpoints;
		}

		add_action( 'affwp_daily_scheduled_events', array( $this, 'schedule_daily_notification_checks' ) );
	}

	/**
	 * Add a cron event to check for new notifications.
	 *
	 * @since 2.9.5
	 */
	public static function schedule_daily_notification_checks() {
		affiliate_wp()->utils->notification_importer->run();
	}

	/**
	 * Columns and their formats.
	 *
	 * @since 2.9.5
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return array(
			'id'           => '%d',
			'remote_id'    => '%d',
			'title'        => '%s',
			'content'      => '%s',
			'buttons'      => '%s',
			'type'         => '%s',
			'conditions'   => '%s',
			'start'        => '%s',
			'end'          => '%s',
			'dismissed'    => '%d',
			'date_created' => '%s',
			'date_updated' => '%s',
		);
	}

	/**
	 * Let MySQL handle most of the defaults.
	 * We just set the dates here to ensure they get saved in UTC.
	 *
	 * @since 2.9.5
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'date_created' => gmdate( 'Y-m-d H:i:s' ),
			'date_updated' => gmdate( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * JSON-encodes any relevant columns.
	 *
	 * @since 2.9.5
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function maybe_json_encode( $data ) {
		foreach ( array( 'buttons', 'conditions' ) as $column ) {
			if ( empty( $data[ $column ] ) || ! is_array( $data[ $column ] ) ) {
				continue;
			}

			$data[ $column ] = json_encode( $data[ $column ] );
		}

		return $data;
	}

	/**
	 * Adds a new notification.
	 *
	 * @since 2.9.5
	 *
	 * @param array  $data
	 *
	 * @return int
	 */
	public function add( $data = array() ) {
		wp_cache_delete( 'affwp_active_notification_count', $this->cache_group );

		return parent::insert( $this->maybe_json_encode( $data ), 'notification' );
	}

	/**
	 * Updates an existing notification.
	 *
	 * @since 2.9.5
	 *
	 * @param int    $row_id
	 * @param array  $data
	 * @param string $where
	 *
	 * @return bool
	 */
	public function update_notification( $row_id, $data = array(), $where = '' ) {
		return parent::update( $row_id, $this->maybe_json_encode( $data ), $where, 'notification' );
	}

	/**
	 * Returns all notifications that have not been dismissed and should be
	 * displayed on this site.
	 *
	 * @since 2.9.5
	 *
	 * @param bool $conditions_only If set to true, then only the `conditions` column is retrieved
	 *                             for each notification.
	 *
	 * @return Notification[]
	 */
	public function get_active_notifications( $conditions_only = false ) {
		global $wpdb;

		$notifications = $wpdb->get_results( $this->get_active_query( $conditions_only ) );

		if ( ! is_array( $notifications ) ) {
			unset( $notifications );
			return [];
		}

		$environment_checker = new Utils\Environment_Checker();
		$models             = array();

		foreach ( $notifications as $notification ) {
			$model = new Notification( (array) $notification );

			// Only add to the array if all conditions are met or if the notification has no conditions.
			try {
				if (
					! $model->conditions ||
					(
						is_array( $model->conditions ) &&
						$environment_checker->is_valid( $model->conditions )
					)
				) {
					$models[] = $model;
				}
			} catch ( \Exception $e ) {}
		}

		unset( $notifications );
		return $models;
	}

	/**
	 * Builds the query for selecting or counting active notifications.
	 *
	 * @since 2.9.5
	 *
	 * @param bool $conditions_only
	 *
	 * @return string
	 */
	private function get_active_query( $conditions_only = false ) {
		global $wpdb;

		$select = $conditions_only ? 'conditions' : '*';

		return $wpdb->prepare(
			"SELECT {$select} FROM {$this->table_name}
			WHERE dismissed = 0
			AND (start <= %s OR start IS NULL)
			AND (end >= %s OR end IS NULL)
			ORDER BY start DESC, remote_id DESC",
			gmdate( 'Y-m-d H:i:s' ),
			gmdate( 'Y-m-d H:i:s' )
		);
	}

	/**
	 * Counts the number of active notifications.
	 * Note: We can't actually do a real `COUNT(*)` on the database, because we want
	 * to double-check the conditions are met before displaying. That's why we use
	 * `get_active_notifications()` which runs the conditions through the EnvironmentChecker.
	 *
	 * @since 2.9.5
	 *
	 * @return int
	 */
	public function count_active_notifications() {
		$numberActive = wp_cache_get( 'affwp_active_notification_count', $this->cache_group );

		if ( false === $numberActive ) {
			$numberActive = count( $this->get_active_notifications( true ) );

			wp_cache_set( 'affwp_active_notification_count', $numberActive, $this->cache_group );
		}

		return $numberActive;
	}

	/**
	 * Creates the table.
	 *
	 * @since 2.9.5
	 */
	public function create_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		dbDelta(
			"CREATE TABLE {$this->table_name} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				remote_id bigint(20) UNSIGNED DEFAULT NULL,
				title text NOT NULL,
				content longtext NOT NULL,
				buttons longtext DEFAULT NULL,
				type varchar(64) NOT NULL,
				conditions longtext DEFAULT NULL,
				start datetime DEFAULT NULL,
				end datetime DEFAULT NULL,
				dismissed tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
				date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
				date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP(),
				PRIMARY KEY (id),
				KEY dismissed_start_end (dismissed, start, end)
			) DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate};"
		);

		update_option( "{$this->table_name}_db_version", $this->version );
	}

}
