<?php
/**
 * Database Model
 *
 * @package     AffiliateWP
 * @subpackage  Database
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
 */

// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.MissingReplacements, WordPress.DB.PreparedSQL.NotPrepared -- We do our own escaping sometimes.
// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- This is okay from Oct 12, 2023 on...
// phpcs:disable Squiz.PHP.CommentedOutCode.Found -- Code comments are OK.

#[\AllowDynamicProperties]

/**
 * Affiliate_WP_DB base class.
 *
 * The base class for all core objects.
 *
 * @since 1.9
 */
abstract class Affiliate_WP_DB {

	/**
	 * Database table name.
	 *
	 * @access public
	 * @var    string
	 */
	public $table_name;

	/**
	 * Database version.
	 *
	 * @access public
	 * @var    string
	 */
	public $version;

	/**
	 * Primary key (unique field) for the database table.
	 *
	 * @access public
	 * @var    string
	 */
	public $primary_key;

	/**
	 * Cache group value.
	 *
	 * @access public
	 * @since  2.5
	 * @var    string
	 */
	public $cache_group;

	/**
	 * Database group value.
	 *
	 * @since 2.5
	 * @var string
	 */
	public $db_group = '';

	/**
	 * Object type to query for.
	 *
	 * @access public
	 * @since  1.9
	 * @var    string
	 */
	public $query_object_type = 'stdClass';

	/**
	 * Constructor.
	 *
	 * Sub-classes should define $table_name, $version, and $primary_key here.
	 *
	 * @access public
	 */
	public function __construct() { }

	/**
	 * Retrieves the list of columns for the database table.
	 *
	 * Sub-classes should define an array of columns here.
	 *
	 * @access public
	 * @return array List of columns.
	 */
	public function get_columns() {
		return array();
	}

	/**
	 * Retrieves the list of columns that are generated using sum fields for the database table.
	 *
	 * Sub-classes should define an array of columns here.
	 *
	 * @access public
	 * @since 2.3
	 * @return array List of valid sum columns.
	 */
	public function get_sum_columns() {
		return array();
	}

	/**
	 * Retrieves all of the possible columns, including sum columns.
	 *
	 * @since 2.5
	 *
	 * @return array List of valid columns, including sum columns.
	 */
	public function get_all_columns() {
		$columns = array_merge( $this->get_columns(), $this->get_sum_columns() );

		if ( isset( $columns['date'] ) ) {
			$columns['formatted_date'] = '%s';
		}

		return $columns;
	}

	/**
	 * Retrieves column defaults.
	 *
	 * Sub-classes can define default for any/all of columns defined in the get_columns() method.
	 *
	 * @access public
	 * @return array All defined column defaults.
	 */
	public function get_column_defaults() {
		return array();
	}

	/**
	 * Retrieves a row from the database based on a given row ID.
	 *
	 * Corresponds to the value of $primary_key.
	 *
	 * @param  int                    $row_id Row ID.
	 * @return array|null|object|void
	 */
	public function get( $row_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) );
	}

	/**
	 * Retrieves a row based on column and value.
	 *
	 * @since 1.0
	 * @since 2.6.1 Renamed the `$row_id` parameter to `$value`.
	 *
	 * @param string $column Column name. See get_columns().
	 * @param mixed  $value  Column value.
	 * @return object|false|null Database query result object, null if nothing was found, or false on failure.
	 */
	public function get_by( $column, $value ) {
		global $wpdb;

		if ( ! array_key_exists( $column, $this->get_columns() ) || empty( $value ) ) {
			return false;
		}

		if( empty( $column ) || empty( $value ) ) {
			return false;
		}

		$query = $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = '%s' LIMIT 1;", $value );

		return $wpdb->get_row( $query );
	}

	/**
	 * Retrieves a value based on column name and row ID.
	 *
	 * @access public
	 *
	 * @param  string      $column Column name. See get_columns().
	 * @param  int|string  $row_id Row ID.
	 * @return string|null         Database query result (as string), or null on failure
	 */
	public function get_column( $column, $row_id ) {
		global $wpdb;

		if ( ! array_key_exists( $column, $this->get_columns() ) || empty( $row_id ) ) {
			return false;
		}

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT $column FROM $this->table_name WHERE $this->primary_key = '%s' LIMIT 1; -- %s",
				$row_id,
				wp_generate_uuid4() // Helps reduce duplicate queries reported to Query Monitor.
			)
		);
	}

	/**
	 * Retrieves one column value based on another given column and matching value.
	 *
	 * @access public
	 *
	 * @param  string $column       Column name. See get_columns().
	 * @param  string $column_where Column to match against in the WHERE clause.
	 * @param  $column_value        mixed Value to match to the column in the WHERE clause.
	 * @return string|null          Database query result (as string), or null on failure
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;

		if ( empty( $column ) || empty( $column_where ) || empty( $column_value )
			|| ! array_key_exists( $column, $this->get_columns() )
		) {
			return false;
		}

		return $wpdb->get_var(
			$wpdb->prepare(
				str_replace(
					array(
						'{column}',
						'{this->table_name}',
						'{column_where}',
					),
					array(
						$column,
						$this->table_name,
						$column_where,
					),
					'SELECT `{column}` FROM `{this->table_name}` WHERE `{column_where}` = %s LIMIT 1; -- %s'
				),
				$column_value,
				wp_generate_uuid4() // Used to eliminate duplicate queries.
			)
		);
	}

	/**
	 * Retrieves results for a variety of query types.
	 *
	 * @access public
	 *
	 * @since 1.9.0
	 *
	 * @since 2.24.4 Updated array_map() checks to only operate on arrays.
	 * @see          https://github.com/awesomemotive/affiliate-wp/issues/5149 Error in AffiliateWP Affiliates Search Functionality.
	 *
	 * @param array    $clauses  Compacted array of query clauses.
	 * @param array    $args     Query arguments.
	 * @param callable $callback Optional. Callback to run against results in the generic results case.
	 *                           Default empty.
	 *
	 * @return array|int|null|object Query results.
	 */
	public function get_results( $clauses, $args, $callback = '' ) {
		global $wpdb;

		if ( true === $clauses['count'] ) {

			$key = $this->table_name . '.' . $this->primary_key;

			$results = $wpdb->get_var(
				str_replace(
					'{uuid4}',
					wp_generate_uuid4(), // Helps reduce duplicate queries reported to Query Monitor.
					"SELECT COUNT({$key}) FROM {$this->table_name} {$clauses['join']} {$clauses['where']}; -- {uuid4}"
				)
			);

			$results = absint( $results );

		} else {

			$fields   = $clauses['fields'];
			$group_by = isset( $clauses['groupby'] ) ? $clauses['groupby'] : '';

			// Run the query.

			$query = $wpdb->prepare(
				"SELECT {$fields} FROM {$this->table_name} {$clauses['join']} {$clauses['where']} {$group_by} ORDER BY {$clauses['orderby']} {$clauses['order']} LIMIT %d, %d;",
				absint( $args['offset'] ),
				absint( $args['number'] )
			);

			$results = $wpdb->get_results( $query );

			/*
			 * If the query is for a single field, pluck the field into an array.
			 *
			 * Note that if only the single field was selected in the query, get_results()
			 * will return an array of objects regardless, thus the pluck.
			 */
			if ( '*' !== $fields && false === strpos( $fields, ',' ) && 0 !== strpos( $fields, 'SUM' ) ) {
				if ( false !== strpos( $fields, '.' ) ) {
					$fields = explode( '.', $fields );
					$fields = array_pop( $fields );
				}
				$results = wp_list_pluck( $results, $fields );
			}

			// Run the results through the fields-dictated callback.
			if ( ! empty( $callback ) && is_callable( $callback ) && is_array( $results ) ) {
				$results = array_map( $callback, $results );
			}

		}

		return $results;
	}

	/**
	 * Inserts a new record into the database.
	 *
	 * Please note: inserting a record flushes the cache.
	 *
	 * @since 1.9
	 * @since 2.5 Added an optional `$insert_id` parameter for use with tables lacking auto-incremented IDs.
	 *
	 * @param array    $data      Column data. See get_column_defaults().
	 * @param string   $type      Optional. Data type context, e.g. 'affiliate', 'creative', etc. Default empty.
	 * @param int|null $insert_id Optional. Object ID to use in lieu of an auto-incremented ID handled by WordPress.
	 *                            Used for scenarios such as with sales records, which don't use an auto-incremented ID.
	 *                            Default null (the value of `$wpdb->insert_id`.
	 * @return int ID for the newly inserted record.
	 */
	public function insert( $data, $type = '', $insert_id = null ) {
		global $wpdb;

		$errors = new \WP_Error();

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		/**
		 * Filters the data array to be used for inserting a new object of a given type.
		 *
		 * The dynamic portion of the hook, `$type`, refers to the data type, such as
		 * 'affiliate', 'creative', 'payout', etc.
		 *
		 * Passing a falsey value back via a filter callback will effectively allow
		 * insertion of the new object to be short-circuited. Example:
		 *
		 *     add_filter( 'affwp_pre_insert_payout_data', '__return_empty_array' );
		 *
		 * @since 2.1.9
		 *
		 * @param array $data Data to be inserted for the new object.
		 */
		$data = apply_filters( "affwp_pre_insert_{$type}_data", $data );

		if ( empty( $data ) ) {

			$errors->add( "missing_{$type}_data_to_insert", 'Insertion failed because no data was provided.' );

		} else {

			/**
			 * Fires immediately before an item has been created in the database.
			 *
			 * The dynamic portion of the hook name, `$type`, refers to the object type.
			 *
			 * @since 1.0
			 *
			 * @param array $data Array of object data.
			 */
			do_action( 'affwp_pre_insert_' . $type, $data );

			// Initialise column format array
			$column_formats = $this->get_columns();

			// Force fields to lower case
			$data = array_change_key_case( $data );

			// White list columns
			$data = array_intersect_key( $data, $column_formats );

			// Unslash data.
			$data = wp_unslash( $data );

			// Reorder $column_formats to match the order of columns given in $data
			$data_keys = array_keys( $data );
			$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

			$inserted = $wpdb->insert( $this->table_name, $data, $column_formats );

			if ( ! $inserted ) {

				$errors->add( 'wp_failed_to_insert', sprintf( 'WordPress failed to insert the %s record.', $type ), $data );

			} else {

				if ( null === $insert_id ) {
					$insert_id = $wpdb->insert_id;
				}

				$object = $this->get_core_object( $insert_id, $this->query_object_type );

				// Prime the item cache, and invalidate related query caches.
				affwp_clean_item_cache( $object );

				/**
				 * Fires immediately after an item has been created in the database.
				 *
				 * @since 1.0
				 *
				 * @param int   $object_id Object ID.
				 * @param array $data      Array of object data.
				 */
				do_action( 'affwp_post_insert_' . $type, $object->{$this->primary_key}, $data );

			}
		}

		$has_errors = method_exists( $errors, 'has_errors' ) ? $errors->has_errors() : ! empty( $errors->errors );

		if ( true === $has_errors ) {
			affiliate_wp()->utils->log( sprintf( 'There was a problem inserting the %s record into the database.', $type ), $errors );

			return false;
		}

		return $object->{$this->primary_key};
	}

	/**
	 * Updates an existing record in the database.
	 *
	 * @access public
	 *
	 * @param  int    $row_id Row ID for the record being updated.
	 * @param  array  $data   Optional. Array of columns and associated data to update. Default empty array.
	 * @param  string $where  Optional. Column to match against in the WHERE clause. If empty, $primary_key
	 *                        will be used. Default empty.
	 * @param  string $type   Optional. Data type context, e.g. 'affiliate', 'creative', etc. Default empty.
	 * @return bool           False if the record could not be updated, true otherwise.
	 */
	public function update( $row_id, $data = array(), $where = '', $type = '' ) {
		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		$object = $this->get_core_object( $row_id, $this->query_object_type );

		if ( ! $object || empty( $data ) ) {
			return false;
		}

		if( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case ( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Unslash data.
		$data = wp_unslash( $data );

		// Ensure primary key is not included in the $data array
		if( isset( $data[ $this->primary_key ] ) ) {
			unset( $data[ $this->primary_key ] );
		}

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( empty( $data ) ) {
			return false;
		}

		if ( false === $wpdb->update( $this->table_name, $data, array( $where => $object->{$this->primary_key} ), $column_formats ) ) {
			return false;
		}

		// Invalidate and prime the item cache, and invalidate related query caches.
		affwp_clean_item_cache( $object );

		/**
		 * Fires immediately after an item has been successfully updated.
		 *
		 * @since 1.0
		 *
		 * @param array $data   Array of item data.
		 * @param int   $row_id Current item ID.
		 */
		do_action( 'affwp_post_update_' . $type, $data, $row_id );

		return true;
	}

	/**
	 * Deletes a record from the database.
	 *
	 * Please note: successfully deleting a record flushes the cache.
	 *
	 * @access public
	 *
	 * @param  int|string $row_id Row ID.
	 * @return bool               False if the record could not be deleted, true otherwise.
	 */
	public function delete( $row_id = 0, $type = '' ) {
		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );
		$object = $this->get_core_object( $row_id, $this->query_object_type );

		if ( ! $object ) {
			return false;
		}

		/**
		 * Fires immediately before an item deletion has been attempted.
		 *
		 * @since 1.0
		 *
		 * @param string     $object Core object type.
		 * @param int|string $row_id Row ID.
		 */
		do_action( 'affwp_pre_delete_' . $type, $row_id );

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $object->{$this->primary_key} ) ) ) {
			return false;
		}

		/**
		 * Fires immediately after an item has been successfully deleted.
		 *
		 * In the case of deletion, this must fire prior
		 * to the cache being invalidated below.
		 *
		 * @since 1.0
		 *
		 * @param string     $object Core object type.
		 * @param int|string $row_id Row ID.
		 */
		do_action( 'affwp_post_delete_' . $type, $row_id );

		// Invalidate the item cache along with related query caches.
		affwp_clean_item_cache( $object );

		return true;
	}

	/**
	 * Retrieves a core object instance based on the given type.
	 *
	 * @since  1.9
	 * @access protected
	 *
	 * @param  object|int   $instance Instance or object ID.
	 * @param  string       $class    Object class name.
	 * @return object|false           Object instance, otherwise false.
	 */
	protected function get_core_object( $instance, $object_class ) {
		// Back-compat for non-core objects.
		if ( 'stdClass' === $object_class ) {
			return $this->get( $instance );
		}

		if ( ! class_exists( $object_class ) ) {
			return false;
		}

		if ( $instance instanceof $object_class ) {
			$_object = $instance;
		} elseif ( is_object( $instance ) ) {
			if ( isset( $instance->{$this->primary_key} ) ) {
				$_object = new $object_class( $instance );
			} else {
				$_object = $object_class::get_instance( $instance );
			}
		} else {
			$_object = $object_class::get_instance( $instance );
		}

		if ( ! $_object ) {
			return false;
		}

		return $_object;
	}

	/**
	 * Parses a string of one or more valid object fields into a SQL-friendly format.
	 *
	 * @access public
	 * @since  2.1
	 * @since  2.6.2 Added a `$date_format` parameter.
	 *
	 * @param string|array $fields      Object fields.
	 * @param false|string $date_format Optional. Specifies a date format to provide to the date column. Default false.
	 * @return string SQL-ready fields list. If empty, default is '*'.
	 */
	public function parse_fields( $fields, $date_format = false ) {

		if ( ! is_array( $fields ) ) {
			$fields = array( $fields );
		}

		$whitelist = array_keys( $this->get_columns() );

		foreach ( $fields as $index => $field ) {
			if ( ! in_array( $field, $whitelist, true ) ) {
				unset( $fields[ $index ] );
			}
		}

		if ( in_array( 'date', $whitelist ) && ! empty( $date_format ) && is_string( $date_format ) ) {
			$format = esc_sql( $date_format );

			if ( empty( $fields ) ) {
				$fields[] = '*';
			}

			$fields[] = "date_format( date, '{$format}' ) AS formatted_date";
		}

		$fields_sql = implode( ', ', $fields );

		if ( empty ( $fields_sql ) ) {
			$fields_sql = '*';
		}

		return $fields_sql;
	}

	/**
	 * Filters invalid sum columns from the provided array.
	 *
	 * @access public
	 * @since  2.3
	 *
	 * @param array $sum_columns The sum columns provided in the query to filter out.
	 * @return array List of valid filtered sum columns valid for the query.
	 */
	public function filter_sum_columns( $sum_columns ) {
		$filtered_sum_columns = array();
		$possible_sum_columns = $this->get_sum_columns();

		if ( ! is_array( $sum_columns ) ) {
			$sum_columns = array( $sum_columns );
		}

		foreach ( $sum_columns as $sum_column ) {
			$sum_field_column_name = "{$sum_column}_sum";
			if ( array_key_exists( $sum_field_column_name, $possible_sum_columns ) ) {
				$filtered_sum_columns[ $sum_field_column_name ] = $possible_sum_columns[ $sum_field_column_name ];
			}
		}

		return $filtered_sum_columns;
	}

	/**
	 * Prepares the group by clause based on the specified column to group by.
	 *
	 * @since 2.3
	 *
	 * @param string $group_by The column to create the group by clause.
	 * @return string A sanitized group by clause. Returns an empty string if the specified group by column is invalid.
	 */
	public function prepare_group_by( $group_by ) {
		$result = '';
		if ( is_string( $group_by ) && array_key_exists( $group_by, $this->get_all_columns() ) ) {
			$result = "GROUP BY {$group_by}";
		}

		return $result;
	}

	/**
	 * Prepares the date query section of the WHERE clause if set.
	 *
	 * @since 2.1.9
	 *
	 * @param string       $where WHERE clause for the query up to this point.
	 * @param string|array $date {
	 *     Date string or array of start and end dates to query by.
	 *
	 *     @type string $start Starting date string.
	 *     @type string $end   Ending date string.
	 * }
	 * @param string       $field Optional. Field to query by (this will be 'date' for all
	 *                            except affiliate queries). Default 'date'.
	 * @return string WHERE clause string for date conditions.
	 */
	public function prepare_date_query( $where, $date, $field = 'date' ) {

		if ( empty( $field ) ) {
			$field = 'date';
		} else {
			sanitize_key( $field );
		}

		$gmt_offset = affiliate_wp()->utils->wp_offset;

		if ( is_array( $date ) ) {

			if( ! empty( $date['start'] ) ) {

				$where .= empty( $where ) ? "WHERE " : "AND ";

				if ( false === strpos( $date['start'], ':' ) ) {
					$date['start'] .= ' 00:00:00';
				}

				$start = esc_sql( gmdate( 'Y-m-d H:i:s', strtotime( $date['start'] ) - $gmt_offset ) );

				$where .= "{$field} >= '{$start}' ";
			}

			if ( ! empty( $date['end'] ) ) {

				$where .= empty( $where ) ? "WHERE " : "AND ";

				if ( false === strpos( $date['end'], ':' ) ) {
					$date['end'] .= ' 23:59:59';
				}

				$end = esc_sql( gmdate( 'Y-m-d H:i:s', strtotime( $date['end'] ) - $gmt_offset ) );

				$where .= "{$field} <= '{$end}' ";
			}

		} else {

			$year  = gmdate( 'Y', strtotime( $date ) - $gmt_offset );
			$month = gmdate( 'm', strtotime( $date ) - $gmt_offset );
			$day   = gmdate( 'd', strtotime( $date ) - $gmt_offset );

			$where .= empty( $where ) ? "WHERE " : "AND ";
			$where .= "$year = YEAR ( {$field} ) AND $month = MONTH ( {$field} ) AND $day = DAY ( {$field} ) ";
		}

		return $where;
	}

	/**
	 * Prepares the SUM functions inside of the fields section of our query.
	 *
	 * @since 2.3
	 *
	 * @param string       $fields     Fields value for the query up to this point.
	 * @param array|string $sum_fields A single database column, or an array of database columns to sum up in this query.
	 * @return string fields string with specified sum columns.
	 */
	public function prepare_sum_fields( $fields, $sum_fields ) {
		if ( ! is_array( $sum_fields ) ) {
			$sum_fields = array( $sum_fields );
		}
		if( !is_string( $fields ) ){
			$fields = '';
		}

		foreach ( $sum_fields as $sum_field ) {
			$sum_field_column_name = "{$sum_field}_sum";
			if ( array_key_exists( $sum_field_column_name, $this->get_sum_columns() ) ) {
				$sum_values[] = "SUM({$sum_field}) as {$sum_field_column_name}";
			}
		}

		if ( ! empty( $sum_values ) ) {
			$fields .= empty( $fields ) ? implode( ', ', $sum_values ) : ', ' . implode( ', ', $sum_values );
		}

		return $fields;
	}

	/**
	 * Handles (maybe) converting the current table to utf8mb4 compatibility.
	 *
	 * @since 2.6.1
	 *
	 * @see maybe_convert_table_to_utf8mb4()
	 *
	 * @return bool True if the table was converted, otherwise false.
	 */
	public function maybe_convert_table_to_utf8mb4() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$db_version = get_option( $this->table_name . '_db_version', false );

		$result = false;

		if ( version_compare( $this->version, $db_version, '>' ) && 'utf8mb4' === $wpdb->charset ) {
			$result = maybe_convert_table_to_utf8mb4( $this->table_name );
		}

		return $result;
	}

	/**
	 * Method for adding usual include/exclude WHERE SQL clauses.
	 *
	 * @since 2.13.0
	 *
	 * @param string $where Current WHERE clause.
	 * @param array  $args  Arguments passed to get items method.
	 *
	 * @return string
	 */
	protected function add_include_exclude_clauses( string $where = '', array $args = array() ) : string {

		if ( ! empty( $args['include'] ) ) {
			$where .= empty( $where ) ? 'WHERE ' : 'AND ';

			if ( is_array( $args['include'] ) ) {
				$include = implode( ',', array_map( 'intval', $args['include'] ) );
			} else {
				$include = intval( $args['include'] );
			}

			$where .= "`{$this->primary_key}` IN( {$include} )";
		}

		if ( ! empty( $args['exclude'] ) ) {
			$where .= empty( $where ) ? 'WHERE ' : 'AND ';

			if ( is_array( $args['exclude'] ) ) {
				$exclude = implode( ',', array_map( 'intval', $args['exclude'] ) );
			} else {
				$exclude = intval( $args['exclude'] );
			}

			$where .= "`{$this->primary_key}` NOT IN( {$exclude} )";
		}

		return $where;
	}

	/**
	 * Add clauses for items connected to.
	 *
	 * This interacts with the connections DB table to get items (like affiliates for instance)
	 * that are connected to a specific item in the connections table (e.g. a group).
	 *
	 * To understand the parameters, see \AffiliateWP\Connections\DB::get_connected(),
	 * as this uses similar arguments.
	 *
	 * @since 2.17.2
	 *
	 * @param string $where              The current where string (we need this to determine WHERE or AND).
	 * @param string $get_connectable    The connectable of the item you are requesting (e.g. for `get_affiliates`, would be `affiliate`).
	 * @param string $where_connectable  The connectable you want connected to the item (e.g. for `affiliate`, e.g. `group`).
	 * @param string $where_id           The ID of the connectable from above that the `$get_connectable` would be connected to.
	 * @param string $where_group_type   Any group type associated when `$where_connectable` is a `group`.
	 *
	 * @return string Where clauses.
	 */
	protected function add_connected_to_clauses(
		string $where,
		string $get_connectable = '',
		string $where_connectable = '',
		string $where_id = '',
		string $where_group_type = '' // Only used for groups.
	) : string {

		if (
			empty( $get_connectable ) ||
			empty( $where_connectable ) ||
			! is_numeric( $where_id )
		) {
			return '';
		}

		if (
			false === affiliate_wp()->connections->is_registered_connectable( $get_connectable ) ||
			false === affiliate_wp()->connections->is_registered_connectable( $where_connectable )
		) {
			return ''; // You can only query registered connectables (helps against SQL injection).
		}

		if ( 'group' === $where_connectable && empty( $where_group_type ) ) {
			return ''; // If you specify a `group` as the `$where_connectable` you must supply a group type.
		}

		$connected_clause = stristr( strtolower( $where ), 'where' )
			? 'AND' // WHERE is already there, use AND.
			: 'WHERE'; // Always start off the SQL with WHERE.

		global $wpdb;

		// Not connected to anything from the $where_connectable...
		if ( 0 === intval( $where_id ) ) {

			// Groups are special, they have a group types...
			if ( 'group' === $where_connectable ) {

				return $wpdb->prepare(
					str_replace(
						array(
							'{connected_clause}',
							'{get_connectable_primary_key}',
							'{get_connectable}',
							'{where_connectable}',
							'{connections_table}',
							'{where_connectable_primary_key}',
							'{where_connectable_table}',
						),
						array(
							/* {connected_clause} */ $wpdb->_real_escape( $connected_clause ),
							/* {get_connctable_primary_key} */ $wpdb->_real_escape( affiliate_wp()->connections->get_connectable_api( $get_connectable )->primary_key ),
							/* {get_connectable} */ $wpdb->_real_escape( $get_connectable ),
							/* {where_connectable} */ $where_connectable,
							/* {connections_table} */ $wpdb->_real_escape( affiliate_wp()->connections->table_name ),
							/* {where_connectable_primary_key} */ $wpdb->_real_escape( affiliate_wp()->connections->get_connectable_api( $where_connectable )->primary_key ),
							/* {where_connectable_table} */ $wpdb->_real_escape( affiliate_wp()->connections->get_connectable_api( $where_connectable )->table_name ),
						),

						/*
						 * E.g.:
						 *
						 *    ; The affiliate should not be in a list of...
						 *    WHERE `affiliate_id` NOT IN (
						 *
						 *      ; ...affiliates that are connected to...
						 *      SELECT `affiliate` FROM wp_affiliate_wp_connections WHERE `group` IN (
						 *
						 *        ; ..any affiliate group.
						 *        SELECT `group_id` FROM wp_affiliate_wp_groups WHERE `type` = 'affiliate-group'
						 *      )
						 *    )
						 *
						 * Here we don't know what group type you mean, so you have to send it to us (%s).
						 */
						'
							{connected_clause} `{get_connectable_primary_key}` NOT IN (
								SELECT `{get_connectable}` FROM {connections_table} WHERE `{where_connectable}` IN (
									SELECT `{where_connectable_primary_key}` FROM {where_connectable_table} WHERE `type` = %s
								)
							)
						'
					),
					$where_group_type
				);
			}
		}

		/*
		 * Create sub-query for the connections DB table to get items connected to another item.
		 *
		 * E.g. For Affiliates connected to an Affiliate Group:
		 *
		 *     WHERE `affiliate_id` IN (
		 *         SELECT
		 *             `affiliate`
		 *         FROM
		 *             affiliate_wp_connections
		 *         WHERE
		 *             `group` = 1
		 *      )
		 *
		 * Note, we trust the `group` ID to be the right group type before you send it.
		 */
		return $wpdb->prepare(
			str_replace(
				array(
					'{connected_clause}',
					'{get_connectable_primary_key}',
					'{get_connectable}',
					'{connections_table}',
					'{where_connectable}',
				),
				array(
					/* {connected_clause} */ $wpdb->_real_escape( $connected_clause ),
					/* {get_connctable_primary_key} */ $wpdb->_real_escape( affiliate_wp()->connections->get_connectable_api( $get_connectable )->primary_key ),
					/* {For get_connectable} */ $wpdb->_real_escape( $get_connectable ),
					/* {connections_table} */ $wpdb->_real_escape( affiliate_wp()->connections->table_name ),
					/* {where_connectable} */ $wpdb->_real_escape( $where_connectable ),
				),
				'
					{connected_clause} `{get_connectable_primary_key}` IN (
						SELECT
							`{get_connectable}`
						FROM
							{connections_table}
						WHERE
							`{where_connectable}` = %d
					)
				'
			),
			$where_id
		);
	}
}
