<?php
/**
 * Custom Links Database Abstraction Layer
 *
 * @package     AffiliateWP
 * @subpackage  Database
 * @copyright   Copyright (c) 2023, Awesome Motive, inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.14.0
 */

#[\AllowDynamicProperties]

/**
 * Class Affiliate_WP_CustomLinks_DB
 *
 * @see Affiliate_WP_DB
 */
class Affiliate_WP_Custom_Links_DB extends Affiliate_WP_DB {

	/**
	 * Cache group for queries.
	 *
	 * @internal DO NOT change. This is used externally both as a cache group and shortcut
	 *           for accessing db class instances via affiliate_wp()->{$cache_group}->*.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var string
	 */
	public $cache_group = 'custom_links';

	/**
	 * Database group value.
	 *
	 * @since 2.14.0
	 * @var string
	 */
	public $db_group = 'custom_links';

	/**
	 * Object type to query for.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var string
	 */
	public $query_object_type = 'AffWP\CustomLink';

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.14.0
	 */
	public function __construct() {
		if ( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {

			// Allows a single custom_links table for the whole network.
			$this->table_name = 'affiliate_wp_custom_links';
		} else {

			global $wpdb;

			$this->table_name = $wpdb->prefix . 'affiliate_wp_custom_links';
		}

		$this->primary_key = 'custom_link_id';
		$this->version     = '1.0.0';
	}

	/**
	 * Retrieves a custom_link object.
	 *
	 * @since 2.14.0
	 * @access public
	 *
	 * @see Affiliate_WP_DB::get_core_object()
	 *
	 * @param int|AffWP\CustomLink $custom_link CustomLink ID or object.
	 * @return AffWP\CustomLink|false CustomLink object, otherwise false.
	 */
	public function get_object( $custom_link ) {
		return $this->get_core_object( $custom_link, $this->query_object_type );
	}

	/**
	 * Database columns
	 *
	 * @access  public
	 * @since   2.14.0
	 */
	public function get_columns() {
		return array(
			'custom_link_id' => '%d',
			'affiliate_id'   => '%d',
			'date_created'   => '%s',
			'link'           => '%s',
			'campaign'       => '%s',
		);
	}

	/**
	 * Default column values
	 *
	 * @access  public
	 * @since   2.14.0
	 */
	public function get_column_defaults() {
		return array(
			'date_created' => gmdate( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Retrieves custom_links from the database.
	 *
	 * @access public
	 * @since  2.14.0
	 *
	 * @param array $args {
	 *     Optional. Arguments for querying custom_links. Default empty array.
	 *
	 *     @type int          $number       Number of custom_links to query for. Default 20.
	 *     @type int          $offset       Number of custom_links to offset the query for. Default 0.
	 *     @type int          $affiliate_id Affiliate ID to explicitly retrieve. Default 0.
	 *     @type string       $order        How to order returned custom_links results. Accepts 'ASC' or 'DESC'.
	 *                                      Default 'DESC'.
	 *     @type string       $orderby      Custom Links table column to order results by. Accepts any AffWP\CustomLink
	 *                                      field. Default 'custom_link_id'.
	 *     @type string|array $fields       Specific fields to retrieve. Accepts 'ids', a single creative field, or an
	 *                                      array of fields. Default '*' (all).
	 *     @type string       $date_format  Specific format for date. Adds a formatted_date to response. Uses MYSQL date_format syntax.
	 * }
	 * @param bool  $count Whether to retrieve only the total number of results found. Default false.
	 *
	 * @return array|int Array of custom_link objects or field(s) (if found), int if `$count` is true.
	 */
	public function get_custom_links( array $args = array(), bool $count = false ) {

		$args = wp_parse_args(
			$args,
			array(
				'number'       => 20,
				'offset'       => 0,
				'affiliate_id' => 0,
				'order'        => 'ASC',
				'orderby'      => $this->primary_key,
				'fields'       => '',
				'date_format'  => '',
			)
		);

		if ( $args['number'] < 0 ) {
			$args['number'] = 999999999999;
		}

		$where   = $this->add_include_exclude_clauses( '', $args );
		$join    = '';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? $args['orderby'] : $this->primary_key;

		// Overload args values for the benefit of the cache.
		$args['orderby'] = $orderby;
		$args['order']   = $order;

		// Specific affiliate(s).
		if ( ! empty( $args['affiliate_id'] ) ) {
			$where.= sprintf(
				'%1$s `affiliate_id` = %2$d ',
				empty( $where ) ? 'WHERE' : 'AND',
				intval( $args['affiliate_id'] )
			);
		}

		// Fields.
		$callback = '';

		if ( 'ids' === $args['fields'] ) {
			$fields   = "{$this->primary_key}";
			$callback = 'intval';
		} else {
			$fields = $this->parse_fields( $args['fields'], $args['date_format'] );
		}

		$callback = ( '*' === $fields ) ? 'affwp_get_custom_link' : $callback;

		$key = ( true === $count )
			? md5( 'affwp_custom_links_count' . maybe_serialize( $args ) )
			: md5( 'affwp_custom_links_' . maybe_serialize( $args ) );

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
	 * Return the number of results found for a given query.
	 *
	 * @since  2.14.0
	 *
	 * @param  array $args Custom Link query args.
	 * @return int
	 */
	public function count( array $args = array() ) {
		return $this->get_custom_links( $args, true );
	}

	/**
	 * Create table.
	 *
	 * @access  public
	 * @since   2.14.0
	 */
	public function create_table() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$this->table_name} (
			custom_link_id bigint(20)   NOT NULL AUTO_INCREMENT,
			affiliate_id   bigint(20)   NOT NULL,
			link           mediumtext   NOT NULL,
			campaign       tinytext     NOT NULL,
			date_created   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP(),
			PRIMARY KEY  (custom_link_id),
			KEY custom_link_id (custom_link_id)
			) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
