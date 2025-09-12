<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Creatives Database Abstraction Layer
 *
 * @package     AffiliateWP
 * @subpackage  Database
 * @copyright   Copyright (c) 2017, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
 */

#[AllowDynamicProperties]

/**
 * Class Affiliate_WP_Creatives_DB
 *
 * @see Affiliate_WP_DB
 *
 * @property-read \AffWP\Creative\REST\v1\Endpoints $REST Creatives REST endpoints.
 */
class Affiliate_WP_Creatives_DB extends Affiliate_WP_DB {

	/**
	 * Cache group for queries.
	 *
	 * @internal DO NOT change. This is used externally both as a cache group and shortcut
	 *           for accessing db class instances via affiliate_wp()->{$cache_group}->*.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $cache_group = 'creatives';

	/**
	 * Database group value.
	 *
	 * @since 2.5
	 * @var string
	 */
	public $db_group = 'creatives';

	/**
	 * Object type to query for.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $query_object_type = 'AffWP\Creative';

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.2
	*/
	public function __construct() {
		global $wpdb, $wp_version;

		if ( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
			// Allows a single creatives table for the whole network
			$this->table_name  = 'affiliate_wp_creatives';
		} else {
			$this->table_name  = $wpdb->prefix . 'affiliate_wp_creatives';
		}
		$this->primary_key = 'creative_id';
		$this->version     = '1.4.0';

		// REST endpoints.
		if ( version_compare( $wp_version, '4.4', '>=' ) ) {
			$this->REST = new \AffWP\Creative\REST\v1\Endpoints;
		}

		add_action( 'affwp_scheduled_creative_status_check', array( $this, 'scheduled_creatives_daily_check' ) );
	}

	/**
	 * Retrieves a creative object.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @see Affiliate_WP_DB::get_core_object()
	 *
	 * @param int|AffWP\Creative $creative Creative ID or object.
	 * @return AffWP\Creative|false Creative object, otherwise false.
	 */
	public function get_object( $creative ) {
		return $this->get_core_object( $creative, $this->query_object_type );
	}

	/**
	 * Database columns
	 *
	 * @access  public
	 * @since   1.2
	 * @since   2.15.0 Added start_date and end_date columns.
	*/
	public function get_columns() {
		return array(
			'creative_id'   => '%d',
			'name'          => '%s',
			'type'          => '%s',
			'description'   => '%s',
			'url'           => '%s',
			'text'          => '%s',
			'image'         => '%s',
			'attachment_id' => '%d',
			'status'        => '%s',
			'date'          => '%s',
			'date_updated'  => '%s',
			'start_date'    => '%s',
			'end_date'      => '%s',
			'notes'         => '%s',
		);
	}

	/**
	 * Default column values
	 *
	 * @access  public
	 * @since   1.2
	*/
	public function get_column_defaults() {
		return array(
			'date'         => gmdate( 'Y-m-d H:i:s' ),
			'date_updated' => gmdate( 'Y-m-d H:i:s' ),
			'start_date'   => gmdate( 'Y-m-d H:i:s' ),
			'end_date'     => gmdate( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Retrieves creatives from the database.
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @param array $args {
	 *     Optional. Arguments for querying creatives. Default empty array.
	 *
	 *     @type int          $number       Number of creatives to query for. Default 20.
	 *     @type int          $offset       Number of creatives to offset the query for. Default 0.
	 *     @type int|array    $creative_id  Creative ID or array of creative IDs to explicitly retrieve. Default 0.
	 *     @type string       $status       Creative status. Default empty (all).
	 *     @type string       $type         Creative type. Default `any`.
	 *     @type string       $type_compare Creative type compare. Default =. Also accepts !=, <>
	 *     @type bool         $hide_empty   If true, will do additional checks to hide empty results.
	 *     @type string       $start_date   Get creatives scheduled to start on or before this date.
	 *                                      Use '0000-00-00 00:00:00' or 'NULL' for no start date. Use '*' to get all with dates.
	 *     @type string       $end_date     Get creatives scheduled to end on or before this date.
	 *                                      Use '0000-00-00 00:00:00' or 'NULL' for no end date. Use '*' to get all with dates.
	 *     @type bool         $scheduled    Whether to retrieve only scheduled creatives. Any status, but must have a start or end date.
	 *     @type string       $order        How to order returned creative results. Accepts 'ASC' or 'DESC'.
	 *                                      Default 'DESC'.
	 *     @type string       $orderby      Creatives table column to order results by. Accepts any AffWP\Creative
	 *                                      field. Default 'creative_id'.
	 *     @type string|array $fields       Specific fields to retrieve. Accepts 'ids', a single creative field, or an
	 *                                      array of fields. Default '*' (all).
	 *     @type string       $date_format  Specific format for date. Adds a formatted_date to response. Uses MYSQL date_format syntax.
	 * }
	 * @param bool $count Whether to retrieve only the total number of results found. Default false.
	 * @return array|int Array of creative objects or field(s) (if found), int if `$count` is true.
	 */
	public function get_creatives( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'       => 20,
			'offset'       => 0,
			'creative_id'  => 0,
			'status'       => '',
			'type'         => 'any',
			'type_compare' => '',
			'hide_empty'   => false,
			'start_date'   => '',
			'end_date'     => '',
			'orderby'      => $this->primary_key,
			'order'        => 'ASC',
			'fields'       => '',
			'date_format'  => '',
			'include'      => array(),
			'exclude'      => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$args['type_compare'] = in_array( $args['type_compare'], array( '=', '!=', '<>' ), true )
			? esc_sql( $args['type_compare'] )
			: '=';

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = $join = '';

		$where = $this->add_include_exclude_clauses( $where, $args );

		// Specific creative ID or IDs.
		if ( ! empty( $args['creative_id'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			if( is_array( $args['creative_id'] ) ) {
				$creatives = implode( ',', array_map( 'intval', $args['creative_id'] ) );
			} else {
				$creatives = intval( $args['creative_id'] );
			}

			$where .= "`creative_id` IN( {$creatives} ) ";
		}

		// Status.
		if ( ! empty( $args['status'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			$status = esc_sql( $args['status'] );

			if ( ! empty( $where ) ) {
				$where .= "`status` = '" . $status . "' ";
			} else {
				$where .= "`status` = '" . $status . "' ";
			}
		}

		$where .= $this->add_connected_to_clauses(
			$where,
			$args['connected_to']['get_connectable'] ?? '',
			$args['connected_to']['where_connectable'] ?? '', // If this is group you should have a where_group_type.
			$args['connected_to']['where_id'] ?? 0,
			$args['connected_to']['where_group_type'] ?? '',
		);

		// Type.
		if ( is_string( $args['type'] ) && 'any' !== $args['type'] ) {
			$where .= empty( $where ) ? 'WHERE ' : 'AND ';
			$where .= "`type` {$args['type_compare']} '" . esc_sql( $args['type'] ) . "' ";
		}

		// Support selecting specific types per query.
		if ( is_array( $args['type'] ) ) {

			$type_values = array_map(
				function( $value ) {

					global $wpdb;

					return $wpdb->prepare( "'%s'", esc_sql( $value ) );
				},
				$args['type']
			);

			$where .= empty( $where ) ? 'WHERE ' : 'AND ';
			$where .= '`type` IN (' . implode( ',', $type_values ) . ') ';
		}

		// Hide empty.
		if ( $args['hide_empty'] ) {
			$where .= empty( $where ) ? 'WHERE ' : 'AND ';
			$where .= "( (`type` = 'text_link' AND `text` != '') OR (`type` = 'image' AND image != '') OR (`type` = 'qr_code' AND url != '') ) ";
		}

		// Creatives for a date or date range.
		if ( ! empty( $args['date'] ) ) {
			$where = $this->prepare_date_query( $where, $args['date'] );
		}

		// Start date.
		if ( ! empty( $args['start_date'] ) ) {
			if ( '*' === $args['start_date'] ) {
				// Get all creatives with start dates.
				$where .= empty( $where ) ? 'WHERE ' : 'AND ';
				$where .= "`start_date` != '0000-00-00 00:00:00'";
			} elseif ( '0000-00-00 00:00:00' !== $args['start_date'] && 'NULL' !== $args['start_date']) {
				//  Get all start dates before or equal to the given date. Used to determine status.
				$where .= empty( $where ) ? 'WHERE ' : 'AND ';
				$where .= "`start_date` != '0000-00-00 00:00:00' ";
				$where .= "AND `start_date` <= '" . esc_sql( $args['start_date'] ) . "' ";
			} elseif ( '0000-00-00 00:00:00' === $args['start_date'] || 'NULL' === $args['start_date'] ) {
				// Checking for this indicates that the start date is not set.
				$where .= empty( $where ) ? 'WHERE ' : 'AND ';
				$where .= "`start_date` = '0000-00-00 00:00:00' ";
			}
		}

		// End date.
		if ( ! empty( $args['end_date'] ) ) {
			if ( '*' === $args['end_date'] ) {
				// Get all creatives with end dates.
				$where .= empty( $where ) ? 'WHERE ' : 'AND ';
				$where .= "`end_date` != '0000-00-00 00:00:00' ";
			} elseif ( '0000-00-00 00:00:00' !== $args['end_date'] && 'NULL' !== $args['end_date'] ) {
				// Get all end dates before or equal to the given date. Used to determine status.
				$where .= empty( $where ) ? 'WHERE ' : 'AND ';
				$where .= "`end_date` != '0000-00-00 00:00:00' AND `end_date` <= '" . esc_sql( $args['end_date'] ) . "' ";
			} elseif ( '0000-00-00 00:00:00' === $args['start_date'] || 'NULL' === $args['end_date'] ) {
				// Checking for this indicates that the end date is not set.
				$where .= empty( $where ) ? 'WHERE ' : 'AND ';
				$where .= "`end_date` = '0000-00-00 00:00:00' ";
			}
		}

		// Only get scheduled. May be any status, but has a start and end date.
		if ( ! empty( $args['scheduled'] ) && true == $args['scheduled'] ) {
			$where .= empty( $where ) ? 'WHERE ' : 'AND ';
			$where .= "`start_date` != '0000-00-00 00:00:00' OR `end_date` !='0000-00-00 00:00:00' ";
		}

		// Select valid creatives only
		$where .= empty( $where ) ? "WHERE " : "AND ";
		$where .= "`$this->primary_key` > 0";

		// There can be only two orders.
		if ( 'ASC' === strtoupper( $args['order'] ) ) {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? $args['orderby'] : $this->primary_key;

		// Overload args values for the benefit of the cache.
		$args['orderby'] = $orderby;
		$args['order']   = $order;

		// Fields.
		$callback = '';

		if ( 'ids' === $args['fields'] ) {
			$fields   = "$this->primary_key";
			$callback = 'intval';
		} else {
			$fields = $this->parse_fields( $args['fields'], $args['date_format'] );

			if ( '*' === $fields ) {
				$callback = 'affwp_get_creative';
			}
		}

		$key = ( true === $count ) ? md5( 'affwp_creatives_count' . serialize( $args ) ) : md5( 'affwp_creatives_' . serialize( $args ) );

		$last_changed = wp_cache_get( 'last_changed', $this->cache_group );
		if ( ! $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, $this->cache_group );
		}

		$cache_key = "{$key}:{$last_changed}";

		$results = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $results ) {

			$clauses = compact( 'fields', 'join', 'where', 'orderby', 'order', 'count' );

			$results = $this->get_results( $clauses, $args, $callback );
		}

		wp_cache_add( $cache_key, $results, $this->cache_group, HOUR_IN_SECONDS );

		return $results;

	}

	/**
	 * Return the number of results found for a given query
	 *
	 * @param  array  $args
	 * @return int
	 */
	public function count( $args = array() ) {
		return $this->get_creatives( $args, true );
	}

	/**
	 * Add a new creative
	 *
	 * @access  public
	 * @since   1.2
	*/
	public function add( $data = array() ) {

		$args = wp_parse_args( $data, array(
			'status'        => 'active',
			'url'           => '',
			'image'         => '',
			'attachment_id' => 0,
		) );

		if ( empty( $args['date'] ) ) {
			unset( $args['date'] );
		} else {
			$time = strtotime( $args['date'] );

			$args['date'] = gmdate( 'Y-m-d H:i:s', $time - affiliate_wp()->utils->wp_offset );
		}

		$args['start_date'] = ! empty( $args['start_date'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $args['start_date'] ) ) : '';

		$args['end_date'] = ! empty( $args['end_date'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( $args['end_date'] ) ) : '';

		$args['attachment_id'] = ( ! empty( $args['image'] ) && 0 === $args['attachment_id'] )
			? attachment_url_to_postid( $args['image'] )
			: 0;

		$add = $this->insert( $args, 'creative' );

		if ( $add ) {
			/**
			 * Fires immediately after a creative has been added to the database.
			 *
			 * @since 1.2
			 *
			 * @param array $add The creative data being added.
			 */
			do_action( 'affwp_insert_creative', $add, $args );
			return $add;
		}

		return false;

	}

	/**
	 * Scheduled creatives daily check.
	 * Update the status of creatives which are due to start or end today.
	 *
	 * @since 2.15.0
	 * @return void
	 */
	public function scheduled_creatives_daily_check() {

		// Creatives which are due to start.
		$creative_ids = $this->get_creatives_to_start_today();

		if ( ! empty( $creative_ids ) && is_array( $creative_ids ) ) {
			foreach ( $creative_ids as $creative_id ) {
				$this->update( $creative_id, array( 'status' => 'active' ) );
			}
		}

		// Creatives which are due to end.
		$creative_ids = $this->get_creatives_to_end_today();

		if ( ! empty( $creative_ids ) && is_array( $creative_ids ) ) {
			foreach ( $creative_ids as $creative_id ) {
				$this->update( $creative_id, array( 'status' => 'inactive' ) );
			}
		}
	}

	/**
	 * Get scheduled creatives that start today.
	 *
	 * @since 2.15.0
	 * @return array Array of creative IDs.
	 */
	public function get_creatives_to_start_today() {

		return $this->get_creatives( array(
			'status'     => 'scheduled',
			'start_date' => gmdate( 'Y-m-d H:i:s', strtotime( 'now' ) + affiliate_wp()->utils->wp_offset ),
			'fields'     => 'ids',
			'number'     => -1,
		) );
	}

	/**
	 * Get active creatives that end today.
	 *
	 * @since 2.15.0
	 * @return array Array of creative IDs.
	 */
	public function get_creatives_to_end_today() {

		return $this->get_creatives( array(
			'status'   => 'active',
			'end_date' => gmdate( 'Y-m-d H:i:s', strtotime( 'now' ) + affiliate_wp()->utils->wp_offset ),
			'fields'   => 'ids',
			'number'   => -1,
		) );
	}

	public function create_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
			creative_id   bigint(20)   NOT NULL AUTO_INCREMENT,
			name          tinytext     NOT NULL,
			type          varchar(255) NOT NULL,
			description   longtext     NOT NULL,
			url           varchar(255) NOT NULL,
			text          tinytext     NOT NULL,
			image         varchar(255) NOT NULL,
			attachment_id bigint(20)   NOT NULL,
			status        tinytext     NOT NULL,
			date          datetime     NOT NULL,
			date_updated  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP(),
			start_date    datetime     NOT NULL,
			end_date      datetime     NOT NULL,
			notes         longtext     NOT NULL,
			PRIMARY KEY  (creative_id),
			KEY creative_id (creative_id)
			) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
