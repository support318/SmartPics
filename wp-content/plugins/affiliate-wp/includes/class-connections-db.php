<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- The name of the tile is common among others.
/**
 * Connections Database
 *
 * @package     AffiliateWP
 * @subpackage  Database
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 * @author      Aubrey Portwood <aubrey@awesomeomotive.com>
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket -- Formatting preference.
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Formatting preference.
// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Empty lines okay.
// phpcs:disable Generic.WhiteSpace.ScopeIndent.Incorre_ct — Empty lines okay.
// phpcs:disable Squiz.Commenting.BlockComment.HasEmptyLineBefore -- Spaces before comments okay.
// phpcs:disable PEAR.Functions.FunctionCallSignature.MultipleArguments -- Formatting OK.

namespace AffiliateWP\Connections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\AffiliateWP\Connections\DB' ) ) {
	return;
}

require_once __DIR__ . '/class-connection.php';

affwp_require_util_traits(
	'data',
	'db',
	'sql',
);

#[\AllowDynamicProperties]

/**
 * Connections Database.
 *
 * This class helps store connections in the database.
 *
 * For most of the methods below, the return types follow these rules:
 *
 * 1. If you pass an invalid parameter, you will get a thrown `InvalidArgumentException`.
 * 2. If we find the thing you want, you get the thing (object, integer, values, etc).
 *     2b. If you don't find the thing you want, you get `false` for objects that couldn't be created,
 *         `0` for integers, and empty strings for values that were not found.
 * 3. If we are able to perform the operation, you get back `true`.
 *     3b. If we couldn't do the operation, you always get back a `WP_Error` for error handling.
 *
 * @since 2.12.0
 *
 * @see Affiliate_WP_DB
 */
final class DB extends \Affiliate_WP_DB {

	use \AffiliateWP\Utils\Data;
	use \AffiliateWP\Utils\DB;
	use \AffiliateWP\Utils\DB\SQL;

	/**
	 * Cache group for queries.
	 *
	 * @internal DO NOT change. This is used externally both as a cache group and shortcut
	 *           for accessing db class instances via affiliate_wp()->{$cache_connection}->*.
	 *
	 * @see Affiliate_WP_DB
	 *
	 * @since 2.12.0
	 *
	 * @var   string
	 */
	public $cache_group = 'connections';

	/**
	 * Database group value.
	 *
	 * @see Affiliate_WP_DB
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	public $db_group = 'connections';

	/**
	 * Class for creating individual connection objects.
	 *
	 * @see Affiliate_WP_DB
	 *
	 * @since 2.12.0
	 *
	 * @var   string
	 */
	public $query_object_type = '\AffiliateWP\Connections\Connection';

	/**
	 * Where registered connectables are stored.
	 *
	 * @since 2.12.0
	 *
	 * @var array
	 */
	private $registered_connectables = array();

	/**
	 * REST API.
	 *
	 * When we create the REST API, this is where it will be stored.
	 *
	 * @since 2.12.0
	 *
	 * @var object
	 */
	private $REST; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase -- Name here is conventional.

	/**
	 * The name of the connections DB table.
	 *
	 * Minus the prefix, which is added, conditionally, in the construct.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	public $table_name = 'affiliate_wp_connections';

	/**
	 * Type
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	private $type = 'connection';

	/**
	 * Version
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * DB primary key.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	public $primary_key = 'connection_id';

	/**
	 * Constructor
	 *
	 * @since 2.12.0
	 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
	 *                  loading priority.
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name = defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE
			? $this->table_name // Allows a single connections table for the whole network.
			: "{$wpdb->prefix}{$this->table_name}";

		$this->create_table();
		$this->upgrade_table();
		$this->maybe_init_rest();
	}

	/**
	 * Are the connectable arguments connectable (registerable)?
	 *
	 * @since  2.12.0
	 *
	 * Note that for `name` the column must exist in the connections table or you may get an exception.
	 *
	 * @param array $args {
	 *     Arguments.
	 *     @type string $table Correlates to the table name in our database.
	 *     @type string column Correlates to the column in that table where the ID is stored.
	 *     @type string $name  Correlates to the column in the connections table where a connection to that ID is stored.
	 * }
	 *
	 * @return mixed `true` if they are, `WP_Error` if not.
	 *
	 * @throws \Exception If you register a connectable with a `name` value
	 *                    that does not have a corrilating column in the connections
	 *                    table.
	 *
	 * @throws \InvalidArgumentException If `$args` is not an array.
	 */
	private function args_are_connectable( $args ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array' );
		}

		foreach ( array( 'table', 'column', 'name' ) as $required_key ) {

			if ( ! isset( $args[ $required_key ] ) || ! $this->is_string_and_nonempty( $args[ $required_key ] ) ) {

				return new \WP_Error(
					'bad_arguments',
					'$args[table], $args[column], and $args[name] are all required and must be non-empty strings.',
					$args
				);
			}
		}

		if ( isset( $this->registered_connectables[ $args['name'] ] ) ) {

			return new \WP_Error(
				'already_connected',
				"The connectable {$args['name']} is already registered.",
				array(
					'args'         => $args,
					'connectables' => $this->registered_connectables,
				)
			);
		}

		if ( ! $this->table_exists( $args['table'] ) ) {

			return new \WP_Error(
				'bad_table',
				"The table '{$args['table']}' must exist in the database.",
				$args
			);
		}

		if ( ! $this->column_exists( $args['table'], $args['column'] ) ) {

			return new \WP_Error(
				'bad_column',
				"The column '{$args['column']}' in table '{$args['table']}' must exist in the database.",
				$args
			);
		}

		if ( ! $this->column_exists( $this->table_name, $args['name'] ) ) {
			throw new \Exception( "A matching column in '{$this->table_name}' for '{$args['name']}' was not found, please upgrade the database before registering this connectable. 'name' must corrilate to a column in the connections table." );
		}

		return true;
	}

	/**
	 * Connect two registered connectables in the database.
	 *
	 * A connectable is a table and a column where an ID resides, see `self::register_connectable()`.
	 * Once registered, you can link those ID's together using this API and method.
	 *
	 * @since 2.12.0
	 *
	 * @param array  $args   Arguments for connecting two registered connectables (see above notes).
	 *                       Accepts an `array` or an array of arrays in the same format.
	 *
	 *                       You can ONLY connect two connectables by using, e.g.:
	 *
	 *                           array(
	 *                               group    => 1,
	 *                               creative => 2,
	 *                           )
	 *
	 *                       ... which would connect group with ID of `1` to creative with ID of `2.
	 *
	 *                       `group` and `creative` above represent registered connectables.
	 * @param string $return Set to `id` (default) to get the value for `connection_id` once it's inserted,
	 *                       or set to `object` to get an `\AffiliateWP\Connections\Connection` object representing it.
	 *
	 * @return mixed If `$return` is set to `id` you get an integer.
	 *               If `$object` is set to `object` you get back an object.
	 *               If there are issues, a `WP_Error`.
	 *
	 * @throws \InvalidArgumentException If you do not supply an `array` for `$args`.
	 */
	public function connect( $args, $return = 'id' ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		if ( ! $this->is_string_and_nonempty( $return ) || ! $this->string_is_one_of( $return, array( 'id', 'object' ) ) ) {
			throw new \InvalidArgumentException( "\$return must be set to 'id' or 'object." );
		}

		$valid_args = $this->connection_args_valid( $args );

		if ( is_wp_error( $valid_args ) ) {
			return $valid_args;
		}

		$items = $this->transpose_connection( $args ); // Easier to work with.

		if ( ! is_array( $items ) ) {
			return $items; // May be a WP_Error.
		}

		// What if it's already connected?
		$connected_id = $this->items_connected(
			$items[0]['connectable'],
			$items[0]['id'],
			$items[1]['connectable'],
			$items[1]['id']
		);

		if ( $this->is_numeric_and_gt_zero( $connected_id ) ) {
			return intval( $connected_id ); // We already found this connected the way you want.
		}

		global $wpdb;

		$insert = $wpdb->insert(
			$this->table_name,
			array(
				'connection_id'          => null,
				'date'                   => gmdate( $this->date_format, time() ),
				$items[0]['connectable'] => $items[0]['id'],
				$items[1]['connectable'] => $items[1]['id'],
			),
			array(
				'connection_id'          => '%d',
				'date'                   => '%s',
				$items[0]['connectable'] => '%d',
				$items[1]['connectable'] => '%d',
			)
		);

		if ( false === $insert || ! is_numeric( $insert ) ) {

			return new \WP_Error(
				'unable_to_insert',
				'Unable to insert a connection into the database',
				array(
					'args'   => $args,
					'items'  => $items,
					'result' => $insert,
				)
			);
		}

		if ( 1 !== intval( $insert ) ) {

			return new \WP_Error(
				'too_many_inserts',
				'We should have inserted just one row, but that was not the case. There may be database damage.',
				array(
					'args'   => $args,
					'items'  => $items,
					'result' => $insert,
				)
			);
		}

		if ( ! $this->is_numeric_and_gt_zero( $wpdb->insert_id ) ) {

			return new \WP_Error(
				'unexpected_insert_id',
				'Insert appears to have succeeded but could not get row id.',
				array(
					'connection'       => $args,
					'items'            => $items,
					'result'           => $insert,
					'$wpdb->insert_id' => $wpdb->insert_id,
				)
			);
		}

		// Verify: We should now find this as connected.
		if ( false === $this->items_connected(
			$items[0]['connectable'],
			$items[0]['id'],
			$items[1]['connectable'],
			$items[1]['id']
		) ) {

			return new \WP_Error(
				'connection_failed',
				'We seemed to be able to insert the record into the database, but were unable to verify it.',
				array(
					'args'   => $args,
					'items'  => $items,
					'result' => $insert,
				)
			);
		}

		if ( 'object' === $return ) {
			return $this->get_core_object( $wpdb->insert_id, $this->query_object_type );
		}

		// We formed the connection.
		return intval( $wpdb->insert_id );
	}

	/**
	 * Is a connection argument valid?
	 *
	 * E.g.
	 *
	 * array(
	 *     connectable_one => id,
	 *     connectable_two => id,
	 * )
	 *
	 * @since  2.12.0
	 *
	 * @param array $args A single argument for a connection.
	 *
	 * @return mixed `true` if it is, `WP_Error` if not.
	 *
	 * @throws \InvalidArgumentException If you do not pass an array.
	 */
	private function connection_arg_valid( $args ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		$item1 = current( $args );
		$item2 = end( $args );

		// Make sure we have two items to connect.
		if ( ! is_numeric( $item1 ) || ! is_numeric( $item2 ) ) {

			return new \WP_Error(
				'bad_arguments',
				"To connect two items you must specify each's registered connectable => id. See AffiliateWP\Connections\DB::connect().",
				array(
					'args'   => $args,
					'single' => $single,
				)
			);
		}

		// Test for registered connectables.
		foreach ( array_keys( $args ) as $index => $connectable ) {

			if ( isset( $this->registered_connectables[ $connectable ] ) ) {
				continue;
			}

			return new \WP_Error(
				'connectable_not_registered',
				"The connectable '{$connectable}' isn't a registered connectable."
			);
		}

		// Test the ids (all must be numeric positives).
		foreach ( array_values( $args ) as $index => $id ) {

			if ( $this->is_numeric_and_gt_zero( $id ) ) {
				continue;
			}

			return new \WP_Error(
				'connectable_item_not_id',
				'The connection item (right side-assignment) must be a positive numeric value) and represent a unique numeric id in the database.',
				$id
			);
		}

		// The connection argument is formatted correctly.
		return true;
	}

	/**
	 * Are the connection args for self::connect() valid?
	 *
	 * @since  2.12.0
	 *
	 * @param array $args Arguments.
	 *
	 * @return mixed `true` if they are, `WP_Error` otherwise.
	 *
	 * @throws \InvalidArgumentException If you pass bad parameters.
	 */
	private function connection_args_valid( $args ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		return $this->connection_arg_valid( $args );
	}

	/**
	 * Does a connection exist (by ID)?
	 *
	 * @since  2.12.0
	 *
	 * @param int $connection_id The connection ID.
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you do not supply a postitive numeric value for connection id.
	 */
	public function connection_exists( $connection_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			throw new \InvalidArgumentException( '$connection_id must be a positive numeric value.' );
		}

		global $wpdb;

		$result = $wpdb->get_var(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See parent::inject_table_name() for justification.
			$this->inject_table_name(
				$wpdb->prepare(
					'SELECT connection_id FROM {table_name} WHERE connection_id = %d',
					$connection_id
				)
			)
		);

		if ( ! is_numeric( $result ) ) {
			return false;
		}

		return intval( $connection_id ) === intval( $result );
	}

	/**
	 * WHERE connection_id SQL.
	 *
	 * @since 2.12.0
	 *
	 * @param mixed $connection_id A numeric value for `connection_id` in the database.
	 *
	 * @return string Prepared SQL.
	 */
	private function connection_id_sql( $connection_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			return ''; // This can only be numeric and positive.
		}

		global $wpdb;

		return $wpdb->prepare( 'WHERE connection_id = %d', $wpdb->_real_escape( $connection_id ) );
	}

	/**
	 * Is a connection in the database live?
	 *
	 * @since  2.12.0
	 *
	 * @param int $connection_id The value for `connection_id` in the database.
	 *
	 * @return mixed `true` if it is live, `false` or `WP_Error` otherwise.
	 *
	 * @throws \InvalidArgumentException If you do not supply a positive numeric value for `$connection_id`.
	 */
	public function connection_is_live( $connection_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			throw new \InvalidArgumentException( '$connection_id must be a positive numeric value.' );
		}

		if ( ! $this->connection_exists( $connection_id ) ) {
			return false; // Does not exist, so it can't be connected to anything.
		}

		global $wpdb;

		$results = $wpdb->get_results(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See parent::inject_table_name() for justification.
			$this->inject_table_name(
				$wpdb->prepare(

					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We don't want this to get escaped w/ tick marks by $wpdb->prepare.
					str_replace(
						array( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Same as above.
							'{connectables}',
						),
						array( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Same as above.
							$wpdb->_real_escape(
								implode(
									',',
									array_map( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Same as above.
										function( $column ) use ( $wpdb ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Same as above.
											return "`{$column}`"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- Same as above.
										}, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Same as above.
										// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Same as above.
										array_keys( $this->registered_connectables )
									)
								)
							),
						),
						'
						SELECT
							{connectables}
						FROM
							{table_name}
						WHERE
							connection_id = %d
						'
					),
					$connection_id
				)
			)
		);

		if ( is_array( $results ) && empty( $results ) ) {
			return false; // Just simply not there, no results for those things being connected in the database at all.
		}

		if ( is_null( $results ) || ! is_array( $results ) ) {
			return false;
		}

		if ( count( $results ) !== 1 ) {

			return new \WP_Error(
				'unexpected_db_results',
				'Expected one row, but got something else. There may be database damage.',
				$results
			);
		}

		$connection_arg = array();

		foreach ( current( $results ) as $connectable => $id ) {
			$connection_arg[ $connectable ] = intval( $id );
		}

		if ( count( $connection_arg ) !== 2 ) {

			return new \WP_Error(
				'unexpected_db_results',
				"Expected 2 connected items for connection_id '{$connection_id}', but got something else. There may be database damage.",
				$results
			);
		}

		$valid_connection_arg = $this->connection_arg_valid( $connection_arg );

		if ( true !== $valid_connection_arg ) {
			return $valid_connection_arg; // Could not form a valid connection arg, might be WP_Error.
		}

		$connected_id = $this->is_connected( $connection_arg );

		// Did we get back a valid connection_id?
		return $this->is_numeric_and_gt_zero( $connected_id )

			// And does it match the connection_id you asked for?
			&& intval( $connection_id ) === $connected_id;
	}

	/**
	 * Get the number of connections in the database.
	 *
	 * @param  array $args Arguments for `self::get_connections()`.
	 *
	 * @return int The number of connections in the database.
	 *
	 * @throws \InvalidArgumentException If `$args` are not an array.
	 */
	public function count( $args = array() ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		return $this->get_connections( $args, true );
	}

	/**
	 * Disconnect a connection in the database.
	 *
	 * @since  2.12.0
	 *
	 * @param array $args Accepts a `connection_id`, or an `array` like `self::connect()`.
	 *
	 * @return mixed `WP_Error` if we had issues disconnecting, otherwise `true`.
	 *               `false` if it's not in the database or it wasn't disconnected.
	 *
	 * @throws \InvalidArgumentException If you do not supply valid parameters.
	 */
	public function disconnect( $args ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		$valid_args = $this->connection_args_valid( $args );

		if ( is_wp_error( $valid_args ) ) {
			return $valid_args; // May be a WP_Error.
		}

		$items = $this->transpose_connection( $args ); // Easier to work with.

		if ( ! is_array( $items ) ) {
			return $items; // Might be a WP_Error.
		}

		global $wpdb;

		// Get the connection id (if it exists) to disconnect it.
		$connected_id = $wpdb->get_var(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We want to avoid tick marks on some of these replacements.
			$this->inject_table_name(
				$wpdb->prepare(

					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					str_replace(
						array( '{column1}', '{column2}' ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						array( $items[0]['connectable'], $items[1]['connectable'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						'SELECT connection_id FROM {table_name} WHERE `{column1}` = %d AND `{column2}` = %d'
					),
					$wpdb->_real_escape( $items[0]['id'] ),
					$wpdb->_real_escape( $items[1]['id'] )
				)
			)
		);

		if ( ! $this->is_numeric_and_gt_zero( $connected_id ) ) {
			return false; // Not even in the database, not disconnected.
		}

		// Delete the row from the database (does not return a WP_Error).
		return $this->delete( $connected_id ) &&

			// And verify it's not there anymore.
			false === $this->connection_exists( $connected_id );
	}

	/**
	 * Delete a connection from the database.
	 *
	 * @since 2.12.0
	 *
	 * @param  int $connection_id The `connection_id` of the connection in the database.
	 *
	 * @return bool True if it was deleted, otherwise false.
	 *
	 * @throws \InvalidArgumentException If you do not supply a positive numeric value for `$connection_id`.
	 */
	public function delete_connection( $connection_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			throw new \InvalidArgumentException( '$connection id must be a positive numeric value.' );
		}

		return parent::delete( $connection_id );
	}

	/**
	 * Get default column values.
	 *
	 * @access public
	 *
	 * @since 2.12.0
	 *
	 * @return array
	 *
	 * @see Affiliate_WP_DB
	 */
	public function get_column_defaults() {

		return array(
			'connection_id' => 0,
			'date'          => gmdate( $this->date_format ),
			'group'         => null,
			'creative'      => null,
		);
	}

	/**
	 * Defines the database columns and their default formats.
	 *
	 * @since 2.12.0
	 *
	 * @return array
	 *
	 * @see Affiliate_WP_DB
	 */
	public function get_columns() {

		return array(
			'connection_id' => '%d',
			'date'          => '%s',
			'group'         => '%d',
			'creative'      => '%d',
		);
	}

	/**
	 * Get connected ids from the database.
	 *
	 * To get, e.g., what creatives are connected to a group, you would:
	 *
	 *     get_connected(
	 *         'creative',
	 *         'group',
	 *         int
	 *     )
	 *
	 * ...in this example, we want all the ids in the `creatives` column that have a `group` column
	 * that contains the id of `int`.
	 *
	 * @since 2.12.0
	 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
	 *                  loading priority.
	 * @since 2.17.2 Updated to allow asking for the count instead of the entire list.
	 *
	 * @param string $get_connectable   The registered connectable ids to get from the database.
	 * @param string $where_connectable The registered connectable that they must be connected to.
	 * @param int    $where_id          The id of the connectable that they have to be connected to.
	 * @param string $return            Set to `count` to get the count or `list` for a list of connectables.
	 *
	 * @return mixed An `array` of ids connected items to the connectable, or an `int` if you set `$return` to `count`.
	 *
	 * @throws \InvalidArgumentException If you do not supply valid parameters.
	 */
	public function get_connected(
		string $get_connectable = '',
		string $where_connectable = '',
		$where_id = false,
		$return = 'list'
	) {

		affwp_register_connectables();

		if ( ! $this->is_numeric_and_gt_zero( $where_id ) ) {
			throw new \InvalidArgumentException( '$where_id must be a positive numeric value.' );
		}

		if ( ! $this->is_string_and_nonempty( $get_connectable ) || ! $this->string_is_one_of( $get_connectable, array_keys( $this->registered_connectables ) ) ) {
			throw new \InvalidArgumentException( "\$get_connectable must be set to a string set to a registered connectable and {$get_connectable} is not." );
		}

		if ( ! $this->is_string_and_nonempty( $where_connectable ) || ! $this->string_is_one_of( $where_connectable, array_keys( $this->registered_connectables ) ) ) {
			throw new \InvalidArgumentException( "\$where_connectable must be set to a string set to a registered connectablea and {$where_connectable} is not." );
		}

		if ( ! $this->string_is_one_of( $return, array( 'count', 'list' ) ) ) {
			throw new \InvalidArgumentException( '$return can only be count or list (defaults to list).' );
		}

		if (
			false === $this->id_in_database(
				$this->get_registered_connectable( $where_connectable, 'table' ),
				$this->get_registered_connectable( $where_connectable, 'column' ),
				$where_id
			)
		) {

			// The thing you are asking for is no longer in the database, so nothing can be connected to it.
			return ( 'count' === $return ) ? 0 : array();
		}

		global $wpdb;

		$wpdb_query = ( 'count' === $return )
			? 'get_var' // Get the count.
			: 'get_results'; // Get an array (list).

		$results = $wpdb->$wpdb_query(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See parent::inject_table_name() for justification.
			$this->inject_table_name(
				$wpdb->prepare(

					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $wpdb will inject tick marks, we don't want that.
					str_replace(

						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See notes above.
						array(
							'{count_or_list}',
							'{get_connectable_name}',
							'{where_connectable_name}',
							'{uuid4}',
						),

						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See notes above.
						array(

							// Get a list of connectables or the count.
							'list' === $return // phpcs:ignore -- Not sure what WP is throwing here.

								// Get the connectables.
								? '`{get_connectable_name}`' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See notes above.

								// Get the count of the connectables.
								: 'COUNT(`{get_connectable_name}`)', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See notes above.

							// The connectables.
							$wpdb->_real_escape( $this->get_registered_connectable( $get_connectable, 'name' ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See notes above.
							$wpdb->_real_escape( $this->get_registered_connectable( $where_connectable, 'name' ) ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See notes above.
							wp_generate_uuid4(),
						),
						'
							SELECT
								{count_or_list}
							FROM
								{table_name}
							WHERE
								`{where_connectable_name}` = %d
							AND
								`{get_connectable_name}` IS NOT NULL -- {uuid4}
						'
					),
					$where_id
				)
			)
		);

		// Might be a count.
		if ( 'count' === $return && is_numeric( $results ) ) {
			return intval( $results ); // You asked for the count, we'll hand it over.
		}

		// List should be an array...
		if ( ! is_array( $results ) ) {

			// But it's not...
			return new \WP_Error(
				'results_not_array',
				'Expected an array (or an integer), but got something else.',
				$results
			);
		}

		// Pluck just the ID's from the connectables (objects).
		$ids = $this->pluck_property_from_objects(
			$results,
			$this->get_registered_connectable( $get_connectable, 'name' )
		);

		if ( ! is_array( $ids ) ) {
			return $ids; // Probably a WP_Error, something went wrong.
		}

		// We want only live connections, this will only return ids that are still in the database where they belong.
		return array_map(
			'intval', // Sure, clean them up (strings) while where there.
			array_filter(
				$ids,
				function( $get_connectable_id ) use ( $get_connectable, $where_connectable, $where_id ) {

					// Are the two items connected (are the ids where they belong).
					return $this->items_connected(
						$where_connectable,
						$where_id,
						$get_connectable,
						$get_connectable_id
					);
				}
			)
		);
	}

	/**
	 * Get the connectables (and their id's) in a connection.
	 *
	 * @since  2.12.0
	 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
	 *                  loading priority.
	 *
	 * @param int $connection_id The id for the connection in the database.
	 *
	 * @return mixed `array` of values for individual connectables.
	 *               `WP_Error` if there are issues with the connection data in the database.
	 *
	 * @throws \InvalidArgumentException If you do not supply a valid connection id.
	 */
	public function get_connected_ids( $connection_id ) {

		affwp_register_connectables();

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			throw new \InvalidArgumentException( '$connection_id must be a positive numeric value.' );
		}

		if ( ! $this->connection_exists( $connection_id ) ) {

			return new \WP_Error(
				'not_exists',
				"A connection with 'connection_id' '{$connection_id}' does not appear to exist in the database.",
				$connection_id
			);
		}

		$connected = array_filter(
			// First filter out all the connectables.
			array_filter(
				(array) affiliate_wp()->connections->get_by(
					'connection_id',
					$connection_id
				),
				function( $value, $column ) {

					// Only pass back the registered connectables from the database (ignoring others).
					return in_array(
						$column,
						array_keys( affiliate_wp()->connections->get_registered_connectables() ),
						true
					);
				},
				ARRAY_FILTER_USE_BOTH
			),

			// Next, keep all the connectables that have values that are integers.
			function( $value ) {
				return $this->is_numeric_and_gt_zero( $value );
			}
		);

		// There should always be only two things connected once we get the connected connectables.
		if ( count( $connected ) !== 2 ) {

			return new \WP_Error(
				'can_only_connection_two_things',
				'Only two things should be connected, but more or less seems to be the case. There may be database damage.',
				$connected
			);
		}

		// Make sure the string numeric (id) is converted to intval.
		return array_map( 'intval', $connected );
	}

	/**
	 * Get a connection object.
	 *
	 * @param int $connection_id The value for `connection_id` in the database.
	 *
	 * @return mixed Group object, or `WP_Error`.
	 *
	 * @throws \InvalidArgumentException If you do not supply a valid Connection ID.
	 */
	public function get_connection( $connection_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			throw new \InvalidArgumentException( '$connection_id must be numeric.' );
		}

		if ( ! $this->connection_exists( $connection_id ) ) {

			return new \WP_Error(
				'not_exists',
				"Group with connection_id {$connection_id} does not exist.",
				$connection_id
			);
		}

		// Try and form a new Group object, will throw Exception if it's not in the DB.
		$object = $this->get_core_object( $connection_id, $this->query_object_type );

		if ( ! is_a( $object, '\AffiliateWP\Connections\Connection' ) ) {

			return new \WP_Error(
				'bad_object',
				"Unable to convert {$connection_id} to a connection object.",
				$object
			);
		}

		return $object;
	}

	/**
	 * Get a connection's date from the database.
	 *
	 * @since  2.12.0
	 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
	 *                  loading priority.
	 *
	 * @param int $connection_id The `connection_id` in the database.
	 *
	 * @return mixed The date of the connection, `WP_Error` if there are database issues.
	 *
	 * @throws \InvalidArgumentException If you do not supply a positive numeric value for the connection id.
	 */
	public function get_connection_date( $connection_id ) {

		affwp_register_connectables();

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			throw new \InvalidArgumentException( '$connection_id must be a positive numeric value.' );
		}

		if ( ! $this->connection_exists( $connection_id ) ) {

			return new \WP_Error(
				'does_not_exists',
				"A connection with the connection_id '{$connection_id}' does not exist.",
				$connection_id
			);
		}

		global $wpdb;

		$date = $this->get_column( 'date', $connection_id );

		if ( ! $this->is_string_and_nonempty( $date ) || ! strtotime( $date ) ) {

			return new \WP_Error(
				'bad_db_date',
				"The date for connection with connection_id '{$connection_id}' did not come back as a valid string or date from the database. There may be database damage or this connection doesn't exist.",
				array(
					'connection_id' => $connection_id,
					'date'          => $date,
					'strtotime'     => strtotime( $date ),
				)
			);
		}

		return $date;
	}

	/**
	 * Get connections from the database.
	 *
	 * @param  array $args {
	 *     Arguments for getting a list of connections (ids or objects).
	 *
	 *     @type string $fields  Set to `ids` to get back an array of connection ids (connection_id column) from the database (default).
	 *                           Set to `objects` to get a list of `\AffiliateWP\Connections\Connection` objects.
	 *     @type int $connection_id   When set to a positive numeric value, we will only give you a single connection when this is set.
	 *                           Setting `fields` to `ids` here would be silly if you already have the id.
	 *     @type int $number     Used to set `LIMIT` and limit the number of connections handed back.
	 *     @type int $offset     Used to set `OFFSET` and used to offset query.
	 *     @type string $orderby Used to set `ORDER BY` and should be a valid column in the connections database table.
	 *                           If you supply a column that doesn't exist in our database, you will get back empty results.
	 *                           If you do not supply anything, the default is `connection_id`.
	 *     @type string $order   Used to set `ORDER` and accepts `ASC` or `DESC`.
	 *                           If you do not supply these, we will always default to `ASC`.
	 * }
	 * @param  bool  $count      Set to `true` to just get the number of result.
	 *
	 * @return array|WP_Error An `array` of `\AffiliateWP\Connections\Connection` objects (might contain a `WP_Error`)
	 *                        if `$args[fields]` is not set to `ids`.
	 *                        An `array` of connection ids (`connection_id` in the database).
	 *                        A `WP_Error` if there is a problem with your arguments.
	 *                        A `WP_Error` if there are unexpected DB results.
	 *                        The number of connections in the database if `$count` is set to `true`.
	 *
	 * @throws \InvalidArgumentException If `$args` is not an array.
	 *                                   If `$count` is not `true` or `false`.
	 */
	public function get_connections( $args = array(), $count = false ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		if ( ! is_bool( $count ) ) {
			throw new \InvalidArgumentException( '$count must be true or false.' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'fields'        => 'ids',              // Can also be 'objects'.
				'connection_id' => 0,                  // WHERE connection_id                   = %d.
				'number'        => apply_filters( 'affwp_unlimited', -1, 'connections_db_get_connections_number' ),                 // LIMIT %d.
				'offset'        => 0,                  // OFFSET %d.
				'orderby'       => $this->primary_key, // ORDER BY %s (Default: connection_id).
				'order'         => 'ASC',              // ORDER ASC|DESC.
				'date'          => array(),            // DATE or Date range.
			)
		);

		if ( ! $this->is_numeric_and_at_least_zero( $args['connection_id'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[connection_id] can only be set to a positive numeric value.', $args );
		}

		if ( $this->is_numeric_and_gt_zero( $args['connection_id'] ) ) {

			return array(
				$this->get_connection( $args['connection_id'] ),
			);
		}

		if ( ! $this->is_string_and_nonempty( $args['fields'] ) || ! $this->string_is_one_of( $args['fields'], array( 'ids', 'objects' ) ) ) {
			return new \WP_Error( 'bad_arguments', "\$args[fields] must be a non-empty string and can only be set to 'ids' or 'objects'.", $args );
		}

		if ( ! is_numeric( $args['number'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[number] can only be set to a positive numeric value or -1 for unlimited.', $args );
		}

		if ( ! $this->is_numeric_and_at_least_zero( $args['offset'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[offset] can only be set to a numeric value or zero.', $args );
		}

		if ( ! $this->is_string_and_nonempty( $args['orderby'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[orderby] must be a non-empty string.', $args );
		}

		if ( ! $this->is_string_and_nonempty( $args['order'] ) || ! $this->string_is_one_of( strtolower( $args['order'] ), array( 'asc', 'desc' ) ) ) {
			return new \WP_Error( 'bad_arguments', "\$args[order] must be a non-empty string and can only be set to 'ASC' or 'DESC'.", $args );
		}

		$date_arg_valid = $this->is_valid_date_arg( $args['date'] );

		if ( true !== $date_arg_valid ) {
			return $date_arg_valid; // Must be a WP_Error.
		}

		global $wpdb;

		$results = $wpdb->get_results(
			$this->inject_table_name( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See self::inject_table_name() for justification.
				sprintf(
					'SELECT connection_id FROM {table_name} %s %s %s %s %s',

					// WHERE, etc.
					$this->connection_id_sql( $args['connection_id'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.
					$this->date_sql( $args['date'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.

					// ORDER BY, ORDER, LIMIT, OFFSET (should always be last).
					$this->orderby_sql( $args['orderby'], $args['order'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->_real_escape in method.
					$this->limit_sql( $args['number'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.
					$this->offset_sql( $args['offset'] ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.
				)
			)
		);

		if ( ! is_array( $results ) ) {
			return new \WP_Error( 'db_results_not_array', "\$wpdb->get_results() did not return an 'array', as expected. There may be database damage.", $results );
		}

		if ( empty( $results ) ) {
			return $count ? 0 : array();
		}

		// We need connection ids for both ids and objects...
		$connection_ids = $this->pluck_property_from_objects( $results, 'connection_id' );

		if ( ! is_array( $connection_ids ) ) {
			return $connection_ids; // Probably a WP_Error because something in the DB didn't have connection_id.
		}

		if ( true === $count ) {
			return count( $connection_ids );
		}

		// You want id's...
		if ( 'ids' === $args['fields'] ) {

			// Might be a WP_Error because we can't reliably give you a list of ID's from the database (probaby damage).
			return array_map( 'intval', $connection_ids );
		}

		// You want objects...
		$objects = array_map(
			function( $connection_id ) {

				if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {

					return new \WP_Error(
						'bad_connection_id',
						'connection_id is not a positive numeric value.',
						$connection_id
					);
				}

				if ( ! $this->connection_exists( $connection_id ) ) {

					return new \WP_Error(
						'connection_not_exists',
						"Connection with connection_id '{$connection_id}' does not exist in the database.",
						$connection_id
					);
				}

				if ( $this->is_numeric_and_gt_zero( $connection_id ) ) {
					return $this->get_connection( $connection_id ); // Connection object (what we want).
				}

				return new \WP_Error(
					'bad_connection',
					'Error getting connection with connection_id from the database.',
					$connection_id
				);
			},
			$connection_ids
		);

		// Might contain WP_Error's.
		return $objects;
	}

	/**
	 * Get a registered connectable information.
	 *
	 * @since  2.12.0
	 * @since 2.15.0 Connectables are registered in the case they are not due to plugin
	 *                  loading priority.
	 *
	 * @param string $connectable The registered connectable's name.
	 * @param string $item        The data you want from the connectable, e.g. `table`, `column`, or `name`.
	 *                            Set to `none` (default) for the entire array.
	 *
	 * @return string|array If asking for a sub-set of information, `string`. Otherwise the entire `array` for the
	 *                      registered connectable.
	 *
	 * @throws \InvalidArgumentException If you do not supply non-empty string for both arguments.
	 * @throws \Exception                If you ask for a connectable that isn't registered.
	 */
	public function get_registered_connectable( $connectable, $item = 'none' ) {

		affwp_register_connectables();

		if ( ! $this->is_string_and_nonempty( $connectable ) ) {
			throw new \InvalidArgumentException( '$connectable must be a non-empty string.' );
		}

		if ( ! $this->is_string_and_nonempty( $item ) || ! $this->string_is_one_of( $item, array( 'none', 'table', 'column', 'name' ) ) ) {
			throw new \InvalidArgumentException( '$item must be either table, column, name, or non for the entire array of data.' );
		}

		if ( ! isset( $this->registered_connectables[ $connectable ] ) ) {
			throw new \Exception( "You cannot ask for a connectable that isn't registered." );
		}

		if ( 'none' === $item ) {

			// You want the whole thing.
			return $this->registered_connectables[ $connectable ];
		}

		// You want a piece of the data.
		return $this->registered_connectables[ $connectable ][ $item ];
	}

	/**
	 * Get all the registered connectables.
	 *
	 * @since  2.12.0
	 *
	 * @return array
	 */
	public function get_registered_connectables() {
		return $this->registered_connectables;
	}

	/**
	 * Retrieves results for a variety of query types.
	 *
	 * We built out `self::get_connections()` instead, but this is here
	 * for backwards compatibility with the `Affiliate_DB` class.
	 *
	 * @since 2.12.0
	 *
	 * @param array    $clauses  See `parent::get_results()`.
	 * @param array    $args     See `parent::get_results()`.
	 * @param callable $callback See `parent::get_results()`.
	 * @param bool     $use      Set to `true` to use `parent::get_results()`.
	 *
	 * @return mixed See parent::get_results().
	 *
	 * @throws \Exception If you do not set `$use` to `true`.
	 */
	public function get_results( $clauses, $args, $callback = '', $use = false ) {

		if ( false === $use ) {
			throw new \Exception( "Use \AffiliateWP\Connections\DB::get_connections() instead, or set '\$use' to 'true' to use parent::get_results()." );
		}

		return parent::get_results( $clauses, $args, $callback = '' );
	}

	/**
	 * Is an ID present in the database?
	 *
	 * @since  2.12.0
	 *
	 * @param string $table  The table where it would be.
	 * @param string $column The column where it would be.
	 * @param int    $id     Numeric ID in the database.
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you supply invalid parameters.
	 */
	private function id_in_database( $table, $column, $id ) {

		if ( ! $this->is_string_and_nonempty( $table ) ) {
			throw new \InvalidArgumentException( '$table needs to be a non-empty string.' );
		}

		if ( ! $this->is_string_and_nonempty( $column ) ) {
			throw new \InvalidArgumentException( '$column needs to be a non-empty string.' );
		}

		if ( ! $this->is_numeric_and_gt_zero( $id ) ) {
			throw new \InvalidArgumentException( '$id needs to be a positive numeric value.' );
		}

		global $wpdb;

		$result = $wpdb->get_var(

			// phpcs:ignore  -- $wpdb->prepare() will add tick marks, and we don't want that here (same for ignores in sprintf below).
			sprintf(
				'SELECT `%s` from `%s` WHERE `%s` = %d -- %s',
				(string) $column, // phpcs:ignore.
				(string) $table, // phpcs:ignore.
				(string) $column, // phpcs:ignore.
				intval( $id ), // phpcs:ignore.
				wp_generate_uuid4() // Helps reduce duplicate queries reported to Query Monitor.
			)
		);

		if ( ! is_numeric( $result ) ) {
			return false; // We didn't find the ID in the database, otherwise it would have been numeric.
		}

		// Verify that ID is the same that we found.
		return intval( $result ) === intval( $id );
	}

	/**
	 * Insert connection directly into the database.
	 *
	 * Using this method is dangerous and may cause database corruption, consider
	 * using `self::add()` instead.
	 *
	 * @since 2.12.0
	 *
	 * @param array  $data      See `Affiliate_WP_DB::insert()`.
	 * @param string $type      See `Affiliate_WP_DB:insert()`.
	 * @param int    $insert_id See `Affiliate_WP_DB:insert()`.
	 * @param bool   $raw       You must set to 'true' to use.
	 *
	 * @return mixed See `Affiliate_WP_DB:insert()`.
	 *
	 * @throws \Exception If you try and use w/out setting `$raw` to `true` when calling.
	 */
	public function insert( $data, $type = '', $insert_id = null, $raw = false ) {

		if ( true !== $raw ) {
			throw new \Exception( "This method inserts connections directly into the database, consider using self::add() instead. If you still want to do it this way, set \$raw to 'true'." );
		}

		return parent::insert( $data, $type, $insert_id );
	}

	/**
	 * Are two things connected?
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Connectables are registered in the case they are not due to plugin
	 *                   loading priority.
	 *
	 * @param array $args Accepts a connection argument much like `self::connect()`.
	 *
	 * @return mixed `true` if we find it connected, `WP_Error` otherwise.
	 *
	 * @throws \InvalidArgumentException If you don't supply an array for `$args`.
	 */
	public function is_connected( $args ) {

		affwp_register_connectables();

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		$valid_args = $this->connection_args_valid( $args );

		if ( is_wp_error( $valid_args ) ) {
			return $valid_args; // Might be a WP_Error.
		}

		$items = $this->transpose_connection( $args );

		if ( ! is_array( $items ) ) {
			return $items; // Might be a WP_Error.
		}

		// Is this single connection connected in the database (might be an true/false/int)?
		return $this->items_connected(
			$items[0]['connectable'],
			$items[0]['id'],
			$items[1]['connectable'],
			$items[1]['id']
		);
	}

	/**
	 * Is a date argument valid?
	 *
	 * @since  2.12.0
	 *
	 * @param  array|string $date_arg Date argument.
	 *
	 * @return bool `true` if it's valid, `false` if not.
	 */
	private function is_valid_date_arg( $date_arg ) {

		if ( is_array( $date_arg ) && empty( $date_arg ) ) {

			// An empty array means no date filtering, valid.
			return true;
		}

		// Date value is a string, e.g. '2023-8-1'.
		if ( is_string( $date_arg ) ) {

			// It must pass strtotime().
			return false === strtotime( $date_arg )

				// It didn't pass strtotime().
				? new \WP_Error(
					'bad_arguments',
					'When date argument is a string, it must be a valid date string.',
					$date_arg
				)
				: true; // It passed strtotime().
		}

		if ( ! is_array( $date_arg ) ) {

			// It must be a string or an array.
			return new \WP_Error(
				'bad_arguments',
				"Date argument must be a valid date string or an array with 'start' and 'end' dates set.",
				$date_arg
			);
		}

		if (
			! isset( $date_arg['start'] ) &&
			! isset( $date_arg['end'] )
		) {

			// Values for start or end must be set if it's a non-empty array.
			return new \WP_Error(
				'bad_arguments',
				"Date argument must have 'start' and/or 'end' values set to valid date string.",
				$date_arg
			);
		}

		if ( isset( $date_args['start'] ) && ! strtotime( $date_args['start'] ) ) {

			// Value for `start` did not pass strtotime().
			return new \WP_Error(
				'bad_arguments',
				"'start' value must be a valid date string.",
				$date_arg
			);
		}

		if ( isset( $date_args['end'] ) && ! strtotime( $date_args['end'] ) ) {

			// Value for `end` did not pass strtotime().
			return new \WP_Error(
				'bad_arguments',
				"'end' value must be a valid date string.",
				$date_arg
			);
		}

		return true; // Valid array with valid start and/or end date strings.
	}

	/**
	 * Are two ID's connected in our connections table?
	 *
	 * @since  2.12.0
	 *
	 * @param string $connectable1 A registered connectable.
	 * @param int    $id1          ID in that registered connectable's table/column.
	 *
	 * @param string $connectable2 A registered connectable.
	 * @param int    $id2          ID in that registered connectable's table/column.
	 *
	 * @return false|int The `connection_id` (int) value if it is connected in the database, `false` otherwise.
	 *
	 * @throws \InvalidArgumentException If you supply improper parameters.
	 *                                   Connectables must be a string, and ID's must be positive and numeric values.
	 */
	private function items_connected( $connectable1, $id1, $connectable2, $id2 ) {

		if ( ! $this->is_string_and_nonempty( $connectable1 ) ) {
			throw new \InvalidArgumentException( '$connectable1 must be a non-empty string.' );
		}

		if ( ! $this->is_string_and_nonempty( $connectable2 ) ) {
			throw new \InvalidArgumentException( '$connectable2 must be a non-empty string.' );
		}

		if ( ! $this->is_numeric_and_gt_zero( $id1 ) ) {
			throw new \InvalidArgumentException( '$id1 must be a positive numeric value.' );
		}

		if ( ! $this->is_numeric_and_gt_zero( $id2 ) ) {
			throw new \InvalidArgumentException( '$id2 must be a positive numeric value.' );
		}

		if ( ! $this->registered_connectable_has( $connectable1, 'name' ) ) {
			return false; // We can't check for non-registered connectable, so not connected.
		}

		if ( ! $this->registered_connectable_has( $connectable2, 'name' ) ) {
			return false; // We can't check for non-registered connectable, so not connected.
		}

		global $wpdb;

		// Is this already connected (not necessarily live)?
		$connected_id = $wpdb->get_var(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See parent::inject_table_name() for justification.
			$this->inject_table_name(
				sprintf(
					'
					SELECT
						connection_id
					FROM {table_name}
					WHERE
						`%s` = %d
					AND
						`%s` = %d
					',
					(string) $this->get_registered_connectable( $connectable1, 'name' ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Because $wpdb->prepare() adds tick marks we had to use sprintf().
					(int) $id1, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Because $wpdb->prepare() adds tick marks we had to use sprintf().
					(string) $this->get_registered_connectable( $connectable2, 'name' ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Because $wpdb->prepare() adds tick marks we had to use sprintf().
					(int) $id2 // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Because $wpdb->prepare() adds tick marks we had to use sprintf().
				)
			)
		);

		if ( ! $this->is_numeric_and_gt_zero( $connected_id ) ) {
			return false; // We didn't find the two id's connected in the database at all.
		}

		// You care about it being live, but we have NO WAY of detecting if the id's are live anymore.
		if (
			! $this->registered_connectable_has_table_and_column( $connectable1 ) ||
			! $this->registered_connectable_has_table_and_column( $connectable2 )
		) {
			return false; // Assume the ids are not in their respective databases.
		}

		// You CARE about it being live, but the id's for this connection are not there anymore, so this isn't a live connection afterall.
		if (

			// First id.
			false === $this->id_in_database(
				$this->get_registered_connectable( $connectable1, 'table' ),
				$this->get_registered_connectable( $connectable1, 'column' ),
				$id1
			)

			|| // Either of them are not there.

			// Second id.
			false === $this->id_in_database(
				$this->get_registered_connectable( $connectable2, 'table' ),
				$this->get_registered_connectable( $connectable2, 'column' ),
				$id2
			)
		) {

			// Remove the stale connection from the database.
			$this->delete_connection( $connected_id );

			return false; // This item is not connected.
		}

		// Since it's connected, hand back the connection ID (the ids are also there) which is more useful than true.
		return intval( $connected_id );
	}

	/**
	 * Initialize REST API.
	 *
	 * !! INCOMPLETE !!!
	 *
	 * @TODO Create Endpoints class.
	 *
	 * @since 2.12.0
	 */
	private function maybe_init_rest() {

		global $wp_version;

		if (
			! version_compare( $wp_version, '6.1.1', '>=' ) ||
			! class_exists( '\AffWP\Affiliate\Connections\REST\v1\Endpoints' )
		) {
			return;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Name here is conventional.
		$this->REST = new \AffiliateWP\Connections\REST\v1\Endpoints();
	}

	/**
	 * Register a connectable.
	 *
	 * @since  2.12.0
	 *
	 * @param array $args {
	 *     Arguments for the connectable.
	 *
	 *     A connectable is just a location where a unique id exists for something,
	 *     e.g. for creatives thats the table `wp_affiliate_wp_creatives` and the column
	 *     is `connection_id`. So e.g.
	 *
	 *         array(
	 *             'name'  => 'creative',
	 *             'table' => 'wp_affiliate_wp_creatives',
	 *             'column'=> 'connection_id',
	 *         )
	 *
	 *     The name must be a unique name for the connectable, it will be referred to
	 *     throughout this API.
	 *
	 *     @type string $name   The name of the connectable
	 *                          (which corrilates with a same-named column in the connections table).
	 *     @type string $table  The table where the column exists.
	 *     @type string $column The column in the table where the id exists.
	 * }
	 *
	 * @return mixed `true` if it was successfuly registered, `WP_Error` if not.
	 *
	 * @throws \InvalidArgumentException If you do not supply an array for `$args`.
	 *
	 * @see self::args_are_connectable() for exceptions that could be thrown there too.
	 */
	public function register_connectable( $args ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		$args_connectable = $this->args_are_connectable( $args );

		if ( is_wp_error( $args_connectable ) && 'already_connected' === $args_connectable->get_error_code() ) {
			return true; // Already registered.
		}

		if ( is_wp_error( $args_connectable ) ) {
			return $args_connectable; // WP_Error.
		}

		$this->registered_connectables[ $args['name'] ] = $args;

		return true;
	}

	/**
	 * Is a connectable registered?
	 *
	 * @since  2.12.0
	 *
	 * @param string $connectable Connectable that might be registered.
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you do not supply a non-empty string.
	 */
	public function is_registered_connectable( $connectable ) {

		if ( ! $this->is_string_and_nonempty( $connectable ) ) {
			throw new \InvalidArgumentException( '$connectable must be a non-empty string.' );
		}

		return in_array(
			trim( $connectable ),
			array_keys( $this->registered_connectables ),
			true
		);
	}

	/**
	 * Does a registered connectable have certain data.
	 *
	 * @since  2.12.0
	 *
	 * @param string $connectable The registered connectable name.
	 * @param string $item        The data, e.g. table, column, name.
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you do not supply non-empty string values for all parameters.
	 */
	private function registered_connectable_has( $connectable, $item ) {

		if ( ! $this->is_string_and_nonempty( $connectable ) ) {
			throw new \InvalidArgumentException( '$connectable must be a non-empty string.' );
		}

		if ( ! $this->is_string_and_nonempty( $item ) || ! $this->string_is_one_of( $item, array( 'table', 'column', 'name' ) ) ) {
			throw new \InvalidArgumentException( '$item must be either table, column, or name.' );
		}

		return isset( $this->registered_connectables[ $connectable ][ $item ] ) &&
			$this->is_string_and_nonempty( $this->registered_connectables[ $connectable ][ $item ] );
	}

	/**
	 * Does a potential connectable have a table and column?
	 *
	 * @since  2.12.0
	 *
	 * @param string $connectable The registered connectable.
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If `$connectable` is not a non-empty string.
	 */
	private function registered_connectable_has_table_and_column( $connectable ) {

		if ( ! $this->is_string_and_nonempty( $connectable ) ) {
			throw new \InvalidArgumentException( '$connectable needs to be a non-empty string.' );
		}

		return $this->registered_connectable_has( $connectable, 'table' ) &&
			$this->registered_connectable_has( $connectable, 'column' );
	}

	/**
	 * Create the DB table.
	 *
	 * @since 2.12.0
	 *
	 * @throws \Exception If we can't create the table.
	 */
	public function create_table() {

		if ( $this->table_exists( $this->table_name ) ) {
			return;
		}

		global $wpdb;

		$result = $wpdb->query(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No prepare needed here.
			$this->inject_table_name(
				'
					CREATE TABLE IF NOT EXISTS `{table_name}` (

						`connection_id` bigint(20) NOT NULL AUTO_INCREMENT,

						`date`          varchar(191) NOT NULL,
						`group`         bigint(20),
						`creative`      bigint(20),
						`affiliate`     bigint(20),

						PRIMARY KEY (`connection_id`)

					) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
				'
			)
		);

		// If query failed (returned false), throw exception
		if ( false === $result ) {
			throw new \Exception( "Could not create table {$this->table_name}: " . $wpdb->last_error );
		}

		// Clear any cached existence check for this table
		if ( isset( $this->existence_cache['table_exists'][$this->table_name] ) ) {
			unset( $this->existence_cache['table_exists'][$this->table_name] );
		}
	}

	/**
	 * Convert a connection argument to an easier to grok format.
	 *
	 * The format that we accept in `self::connect()` is easy to understand and write,
	 * but is hard to code against. To make things simpler we use this to format it.
	 *
	 * @since  2.12.0
	 *
	 * @param array $connection The connection arg, see `self::connect()`.
	 *
	 * @return array
	 *
	 * @throws \InvalidArgumentException If you pass an invalid connection argument.
	 */
	private function transpose_connection( $connection ) {

		if ( ! is_array( $connection ) ) {
			throw new \InvalidArgumentException( '$connection must be an array.' );
		}

		if ( is_array( current( $connection ) ) ) {
			throw new \InvalidArgumentException( '$connection cannot be an array of arrays.' );
		}

		$items = array();

		foreach ( $connection as $connectable => $id ) {

			$items[] = array(
				'connectable' => $connectable,
				'id'          => $id,
			);
		}

		// You aren't connecting 2 items.
		if ( 2 !== count( $items ) ) {

			return new \WP_Error(
				'can_only_connection_two_things',
				'You cannot connect more than two things.',
				$connection
			);
		}

		return $items;
	}

	/**
	 * Update a connection directly in the database.
	 *
	 * @throws \Exception If you try and use w/out setting `$raw` to `true` when calling.
	 *
	 * Using this method is dangerous and may cause database corruption, consider
	 * using `self::update_connection` instead.
	 *
	 * @since 2.12.0
	 *
	 * @param int    $row_id See `Affiliate_WP_DB:update()`.
	 * @param array  $data   See `Affiliate_WP_DB:update()`.
	 * @param string $where  See `Affiliate_WP_DB:update()`.
	 * @param string $type   See `Affiliate_WP_DB:update()`.
	 * @param bool   $raw    You must set this to true to use.
	 *
	 * @return mixed See `Affiliate_WP_DB:update()`.
	 */
	public function update( $row_id, $data = array(), $where = '', $type = '', $raw = false ) {

		if ( true !== $raw ) {
			throw new \Exception( "This method updates connections directly in the database, consider using self::update_connection() instead. If you still want to do it this way, set \$raw to 'true'." );
		}

		return parent::update( $row_id, $data, $where, $type );
	}

	/**
	 * Update a connection in the database.
	 *
	 * @since 2.12.0
	 *
	 * @param int   $connection_id The value for `connection_id` in the database.
	 * @param array $args {
	 *     Arguments for updating a connection.
	 *     Set either to null to skip updating (or do not set).
	 *
	 *     @type array $date A value (valid date) to update the date to.
	 * }
	 *
	 * @return mixed `true` if data was actually changed, `false` if nothing was changed (was same data).
	 *               `WP_Error` if there was an issue with your arguments.
	 *               `WP_Error` if the connection doesn't exist.
	 *
	 * @throws \InvalidArgumentException If you pass invalid parameters.
	 */
	public function update_connection( $connection_id, $args ) {

		if ( ! $this->is_numeric_and_gt_zero( $connection_id ) ) {
			throw new \InvalidArgumentException( '$connection_id must be a positive numeric value.' );
		}

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		if ( ! $this->connection_exists( $connection_id ) ) {
			return new \WP_Error( 'not_found', "A connection with the connection_id {$connection_id} does not exist in the database.", $connection_id );
		}

		$args = wp_parse_args(
			$args,
			array(
				'date' => null,
			)
		);

		if ( in_array(
			array_keys( $this->registered_connectables ),
			$args,
			true
		) ) {

			return new \WP_Error(
				'bad_args',
				'You cannot update connectables via this method, use ::update() instead. You can only update the date.',
				$args
			);
		}

		if (
			! is_null( $args['date'] ) &&
			! strtotime( $args['date'] )
		) {

			return new \WP_Error(
				'bad_args',
				'$args[date] must be null or a valid date.',
				$args
			);
		}

		// Something was updated.
		return in_array(
			true, // If anything ends up being true, we return true.
			array(

				// Date.
				is_null( $args['date'] )
					? false // Unchanged purposefully.

					// Might be false, true, or WP_Error (we validate date prior).
					: $this->update(
						$connection_id,
						array(
							'date' => $args['date'],
						),
						'',
						$this->type,
						true
					),
			),
			true
		);
	}

	/**
	 * Add missing item columns.
	 *
	 * @since 2.13.0
	 *
	 * @return void
	 *
	 * @throws \Exception If we cannot create any of the columns.
	 */
	private function upgrade_table() {

		if ( ! $this->table_exists( $this->table_name ) ) {
			return; // Table wasn't created, can't alter.
		}

		global $wpdb;

		// Add new columns (for connectables) since 2.12.0.
		foreach ( array( 'affiliate' ) as $column ) {

			if ( $this->column_exists( $this->table_name, $column ) ) {
				continue; // See if the column already exists.
			}

			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- $column is not a dynamic value.
			$wpdb->query( $this->inject_table_name( "ALTER TABLE {table_name} ADD `{$column}` bigint(20) AFTER `creative`" ) );

			if ( $this->column_exists( $this->table_name, $column ) ) {
				continue; // Make sure the column exists.
			}

			// Let someone know we couldn't make this column.
			throw new \Exception( "Unable to add '{$column}' column to the '{$this->table_name}' table." );
		}
	}

	/**
	 * Get a connectable's API from `affiliate_wp()`.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return mixed The API attached to `affiliate_wp()->$api`.
	 *
	 * @throws \InvalidArgumentException If you pass an empty connectable (would never work).
	 * @throws \Exception                If there is no API for the connectable (the whole things falls apart).
	 */
	public function get_connectable_api( string $connectable ) {

		if ( empty( $connectable ) ) {
			throw new \InvalidArgumentException( '$connectable must be a non-empty string.' );
		}

		$api = '';

		if ( 'affiliate' === $connectable ) {
			$api = 'affiliates';
		}

		if ( 'creative' === $connectable ) {
			$api = 'creatives';
		}

		if ( 'group' === $connectable ) {
			$api = 'groups';
		}

		if ( empty( $api ) ) {
			throw new \Exception( "Cannot determine affiliate_wp() API for '{$connectable}'." );
		}

		return affiliate_wp()->$api;
	}
}
