<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- The name of the tile is common among others.
/**
 * Grouping Database
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
// phpcs:disable PEAR.Functions.FunctionCallSignature.MultipleArguments -- Formatting OK.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- Formatting OK.

namespace AffiliateWP\Groups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( '\AffiliateWP\Groups\DB' ) ) {
	return;
}

require_once __DIR__ . '/class-group.php';

affwp_require_util_traits(
	'sql',
	'db',
	'data',
);

#[\AllowDynamicProperties]

/**
 * Grouping Database
 *
 * @see \AffiliateWP\Connections\DB This handles return types in the same way
 *                                  that's descriped in the docblock for that class.
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
	 *           for accessing db class instances via affiliate_wp()->{$cache_group}->*.
	 *
	 * @since 2.12.0
	 * @see Affiliate_WP_DB
	 *
	 * @var   string
	 */
	public $cache_group = 'groups';

	/**
	 * Database group value.
	 *
	 * @since 2.12.0
	 * @see Affiliate_WP_DB
	 *
	 * @var string
	 */
	public $db_group = 'groups';

	/**
	 * The group type sanitizer for setting the value in the database.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	private $group_type_sanitizer = 'sanitize_key';

	/**
	 * Class for creating individual group objects.
	 *
	 * @since 2.12.0
	 * @see Affiliate_WP_DB
	 *
	 * @var   string
	 */
	public $query_object_type = '\AffiliateWP\Groups\Group';

	/**
	 * Group types.
	 *
	 * When registering group types in the API here, this is
	 * where they are stored.
	 *
	 * @since 2.12.0
	 *
	 * @var array
	 */
	private $registered_group_types = array();

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
	 * The name of the group DB table.
	 *
	 * Minus the prefix, which is added, conditionally, in the construct.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	public $table_name = 'affiliate_wp_groups';

	/**
	 * Type.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	private $type = 'group';

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
	public $primary_key = 'group_id';

	/**
	 * Constructor
	 *
	 * @since 2.12.0
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name = defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE
			? $this->table_name // Allows a single grouping table for the whole network.
			: "{$wpdb->prefix}{$this->table_name}";

		$this->create_table();
		$this->maybe_convert_meta_to_longtext();

		$this->maybe_init_rest();
	}

	/**
	 * Add a group.
	 *
	 * @param array  $args {
	 *     Arguments (type and title must be a unique combination and sanitized with sanitize_key()).
	 *     @type string $type  (Required) The group type (must be sanitized with sanitize_key() prior).
	 *                                    You cannot use an unregistered group type here.
	 *     @type string $title (Required) The title of the group (can be changed later with `self::update`, unlike `type`).
	 *     @type array  $meta  (Must be an array) Additional information about the Group.
	 * }
	 * @param string $return What to return, `id` (default) for the group's `group_id` value in the database,
	 *                       or `object` for a `\AffiliateWP\Groups\Group`.
	 *
	 * @return mixed `WP_Error` if we were unable to add the group to the database.
	 *               `WP_Error` if you try to add a group to an unregistered group type.
	 *               `WP_Error` if you include `group_id` in your arguments.
	 *               If `$return` is set to `id` (default), and we were able to add the group, the `group_id` of the group from the database.
	 *               If `$return` is set to `object`, a `\AffiliateWP\Groups\Group` instance for the added group in the database.
	 *
	 * @throws \InvalidArgumentException If `$args` is not an `array`.
	 *                                   If `$return` is not a `string` and is not set to either `id` or `object`.
	 */
	public function add_group( $args, $return = 'id' ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		if ( ! is_string( $return ) || ( 'id' !== $return && 'object' !== $return ) ) {
			throw new \InvalidArgumentException( "\$return must be a string set to 'id' or 'object' but is set to '{$return}'." );
		}

		$args = wp_parse_args(
			$args,
			array(
				'type'  => '', // Note, must be sanitized with sanitize_key().
				'title' => '',
				'meta'  => null,
			)
		);

		// group_id must not be set.
		if ( isset( $args['group_id'] ) ) {
			return new \WP_Error( 'bad_arguments', "You want to set 'group_id' on insert, and you cannot.", $args );
		}

		// type/title must not be empty.
		if ( ! $this->is_string_and_nonempty( $args['type'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[type] must be a non-empty string.', $args );
		}

		// type must be registered for inserts.
		if ( ! is_array( $this->get_registered_group_type( $args['type'] ) ) ) {
			return new \WP_Error( 'unregistered_group_type', "Cannot add group of type '{$args['type']}' as it is not a registered group type.", $args['type'] );
		}

		// type/title must be set and sanitized for inserts.
		$sanitized = $this->args_are_sanitzed( $args );

		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		$esc_title = (string) esc_html( $args['title'] );

		// See if a group with type/title already exists.
		$existing_group_id = $this->get_group_id(
			array(
				'type'  => $args['type'],
				'title' => $esc_title,
			)
		);

		// A group with this type/title already exists.
		if ( $this->is_numeric_and_gt_zero( $existing_group_id ) ) {

			return new \WP_Error(
				'group_exists',
				"Cannot add group with type '{$args['type']}' and title '{$args['title']}' because they already exist in the database. These must be unique in the database.",
				array(
					'existing_group_id' => $existing_group_id,
					'args'              => $args,
				)
			);
		}

		// Meta must be an array for initial insert, if null use empty array.
		if ( is_null( $args['meta'] ) ) {
			$args['meta'] = array();
		}

		// Title is required for insert.
		if ( ! $this->is_string_and_nonempty( $args['title'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[title] must be a non-empty string, you cannot have an empty title.', $args );
		}

		global $wpdb;

		// We won't be placing these values.
		$column_sanitize['group_id'] = null;

		// Insert the data.
		$insert = $wpdb->insert(
			$this->table_name,
			array(
				'group_id' => null,
				'type'     => (string) $args['type'],
				'title'    => $esc_title,
				'meta'     => $this->json_encode( $args['meta'], null ),
			),
			array_values( $this->get_columns() )
		);

		// Error.
		if ( false === $insert ) {

			// Error inserting these arguments into the database.
			return new \WP_Error(
				'db_error',
				'Database insert failed.',
				array(
					'result'        => $insert,
					'column_values' => array_values( $this->get_columns() ),
				)
			);
		}

		// Should add just one row.
		if ( 1 !== $insert ) {

			// We should have only inserted one row.
			return new \WP_Error(
				'db_unexpected_results',
				'$wpdb::insert failed.',
				array(
					'args'      => $args,
					'db_insert' => array_values( $this->get_columns() ),
					'result'    => $insert,
				)
			);
		}

		// Verify: Check if it was inserted properly, we should be able to get it's ID.
		$group_id = $this->get_group_id(
			array(
				'type'  => $args['type'],
				'title' => $esc_title,
			)
		);

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {

			// If we inserted the row, we should be able to get an ID by title and type.
			return new \WP_Error(
				'db_unexpected_results',
				"\$wpdb->insert succeeded, but couldn't find group (insert failed, database may be damaged).",
				$group_id
			);
		}

		if ( 'object' === $return ) {

			// They want the group object after insertion (might come back false if we couldn't form an object).
			return $this->get_group( $group_id );
		}

		// Theyt want an ID back.
		return intval( $group_id );
	}

	/**
	 * Check if the values for title and type in arguments are properly sanitized.
	 *
	 * The values for columns `type` are expected to be properly sanitized using
	 * `sanitize_key()` before storing them in `$args`.
	 *
	 * @param  array $args The arguments (requires keys `title` and `type`).
	 *
	 * @return mixed `true` if they are properly formatted,
	 *               `false` if not.
	 *               `WP_Error` if your arguments are incomplete or `type` or `title` are not sanitized with `sanitize_key()`.
	 *
	 * @throws \InvalidArgumentException If `$args` is not an array.
	 */
	private function args_are_sanitzed( $args ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		// Is the key set...
		if ( ! isset( $args['type'] ) ) {

			// You should have known this check (type/title) was at least required.
			return new \WP_Error( 'argument_type_not_set', '$args[type] is not set.', $args );
		}

		// Is the value a string...
		if ( ! is_string( $args['type'] ) ) {
			return new \WP_Error( 'argument_type_not_string', '$args[type] not a string.', $args );
		}

		// Is the value sanitized...
		if ( $this->sanitize_group_type( $args['type'] ) !== $args['type'] ) {
			return new \WP_Error( 'argument_type_not_sanitized', "\$args[type] not a sanitized with '{$this->group_type_sanitizer}()'.", $args );
		}

		return true;
	}

	/**
	 * Get the number of groups in the database.
	 *
	 * @param  array $args Arguments for `self::get_groups()`.
	 *
	 * @return int The number of groups in the database.
	 *
	 * @throws \InvalidArgumentException If `$args` are not an array.
	 */
	public function count( $args = array() ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		return $this->get_groups( $args, true );
	}

	/**
	 * Delete a group from the database.
	 *
	 * @since 2.12.0
	 *
	 * @param  int $group_id The `group_id` of the group in the database.
	 *
	 * @return bool True if it was deleted, otherwise false.
	 *
	 * @throws \InvalidArgumentException If you do not supply a positive numeric value for `$group_id`.
	 */
	public function delete_group( $group_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			throw new \InvalidArgumentException( '$group id must be a positive numeric value.' );
		}

		/**
		 * When we delete a group from the database.
		 *
		 * @since 2.12.0
		 *
		 * @param bool $deleted  `true` if we deleted it, `false` otherwise.
		 * @param int  $group_id The ID of the group.
		 */
		do_action( 'affwp_delete_group', $group_id );

		return parent::delete( $group_id );
	}

	/**
	 * Get default column values.
	 *
	 * @access public
	 * @see Affiliate_WP_DB
	 *
	 * @since 2.12.0
	 *
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'group_id' => 0,
			'type'     => '',
			'title'    => '',
			'meta'     => '',
		);
	}

	/**
	 * Defines the database columns and their default formats.
	 *
	 * @since 2.12.0
	 *
	 * @see Affiliate_WP_DB
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'group_id' => '%d',
			'type'     => '%s',
			'title'    => '%s',
			'meta'     => '%s',
		);
	}

	/**
	 * Get a group object.
	 *
	 * @param  int $group_id The value for `group_id` in the database.
	 *
	 * @return mixed Group object, or `WP_Error`.
	 *
	 * @throws \InvalidArgumentException If you do not supply a valid Connection ID.
	 */
	public function get_group( $group_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			throw new \InvalidArgumentException( '$group_id must be numeric.' );
		}

		if ( ! $this->group_exists( $group_id ) ) {

			return new \WP_Error(
				'not_exists',
				"Group with group_id {$group_id} does not exist.",
				$group_id
			);
		}

		// Try and form a new Group object, will throw Exception if it's not in the DB.
		$object = $this->get_core_object( $group_id, $this->query_object_type );

		if ( ! is_a( $object, '\AffiliateWP\Groups\Group' ) ) {

			return new \WP_Error(
				'bad_object',
				"Unable to convert {$group_id} to a group object.",
				$object
			);
		}

		return $object;
	}

	/**
	 * Get a group's id (group_id) from the database.
	 *
	 * You can use a `type` and `title` (columns in the database) to retrive an value for `group_id`
	 * from the database.
	 *
	 * @param array $args {
	 *     Arguments for attempting to get `group_id` by unique `title` and `type` commbination.
	 *
	 *     @type string $type  (Requred) The value for `type` column in the database.
	 *     @type string $title (Requred) The value for `title` column in the database.
	 * }
	 *
	 * @return mixed The `group_id` (int) value of the group in the database if it is found by `title` and `type`.
	 *               `false` if a group was not found by that `title` and `type`.
	 *               `WP_Error` if your arguments are not properly configured (`type` and `title` must be set and be non-empty strings).
	 *               `WP_Error` if there was an issue finding the `group_id` in the database (other than not being found).
	 *
	 * @throws \InvalidArgumentException If `$args` is not an array.
	 */
	public function get_group_id( $args = array() ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		if (
			! isset( $args['type'] ) ||
			! $this->is_string_and_nonempty( $args['type'] )
		) {

			// These are required and must be string, let them know one of them is not.
			return new \WP_Error(
				'invalid_arguments',
				'$args[type] must be set and must be a non-empty string.',
				$args
			);
		}

		if ( ! isset( $args['title'] ) || ! $this->is_string_and_nonempty( $args['title'] ) ) {

			// These are required and must be string, let them know one of them is not.
			return new \WP_Error(
				'invalid_arguments',
				'$args[title] must be set and must be a non-empty string.',
				$args
			);
		}

		$sanitized = $this->args_are_sanitzed( $args );

		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		global $wpdb;

		// See if a group_id exists with that title and type...
		$group_id = $wpdb->get_var(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See self::inject_table_name() for justification.
			$this->inject_table_name(
				$wpdb->prepare(
					'
					SELECT
						group_id FROM {table_name}
						WHERE
							type = %s
							AND
							BINARY title = %s -- %s
					',
					$args['type'],
					$args['title'],
					wp_generate_uuid4() // Helps reduce duplicate queries being reported to Query Monitor.
				)
			)
		);

		if ( is_null( $group_id ) ) {
			return false; // No group by that type and title, just simply not found.
		}

		if ( $this->is_numeric_and_gt_zero( $group_id ) ) {
			return intval( $group_id ); // We got a group_id.
		}

		// It was something else.
		return new \WP_Error(
			'group_id_not_numeric',
			"The group_id with type '{$args['type']}' and title '{$args['title']}' was not numeric, the database could be damaged.",
			array(
				'type'   => $args['type'],
				'title'  => $args['title'],
				'result' => $group_id,
			)
		);
	}

	/**
	 * Get group meta from the database.
	 *
	 * This always returns the full set of data,
	 * use `\AffiliateWP\Groups\Group::get_meta( $key )` to get
	 * subsets of the data.
	 *
	 * @since 2.12.0
	 * @since 2.13.0 Added $key parameter so you can get specific value.
	 *
	 * @param  int    $group_id The `group_id` of the group in the database.
	 * @param  string $key      The meta key for specific value.
	 *
	 * @return mixed An `array` of the data, if it exists.
	 *               Any value if `$key` is set, null if the meta value is not set.
	 *               `WP_Error` if the group does not exist.
	 *               `WP_Error` if the data in the database is not decodable.
	 *
	 * @throws \InvalidArgumentException If you pass a non-numeric non-positive value for `$group_id`.
	 */
	public function get_group_meta( int $group_id, string $key = '' ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			throw new \InvalidArgumentException( '$group_id must be a positive numeric value.' );
		}

		if ( ! $this->group_exists( $group_id ) ) {

			return new \WP_Error(
				'does_not_exists',
				"A group with the group_id '{$group_id}' does not exist.",
				$group_id
			);
		}

		global $wpdb;

		$meta = $wpdb->get_var(
			$wpdb->prepare(

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name injection OK.
				$this->inject_table_name( 'SELECT meta FROM {table_name} WHERE group_id = %d -- %s' ),
				$group_id,
				wp_generate_uuid4() // Helps reduce duplicate queries reported to Query Monitor.
			)
		);

		if ( ! is_string( $meta ) ) {

			// We store a json_encoded string in the database.
			return new \WP_Error(
				'db_unexpected_results',
				"Meta for group with group_id of '{$group_id}' was not a string we could decode.",
				$meta
			);
		}

		try {

			// Decode it (might fail).
			$meta_decoded = json_decode(
				$meta,
				true,
				JSON_NUMERIC_CHECK | JSON_OBJECT_AS_ARRAY | JSON_ERROR_UNSUPPORTED_TYPE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);

		} catch ( \ValueError $error ) {

			// Decoding failed.
			return new \WP_Error(
				'json_error',
				'We were not able to convert meta into an array due to a decoding issue.',
				$error
			);
		}

		if ( ! is_array( $meta_decoded ) ) {

			// Something else went wrong.
			return new \WP_Error(
				'unexpected_results',
				'We were unable to convert encoded JSON data to an array.',
				array(
					'raw_meta' => $meta,
					'decoded'  => $meta_decoded,
				)
			);
		}

		if ( $this->is_string_and_nonempty( $key ) ) {

			return isset( $meta_decoded[ $key ] )
				? $meta_decoded[ $key ]
				: null; // Null means it's not set.
		}

		return $meta_decoded;
	}

	/**
	 * Get the group title from the database.
	 *
	 * @since 2.12.0
	 *
	 * @param int $group_id The value for `group_id` for the group in the database.
	 *
	 * @return mixed The title of the group or a `WP_Error` if the group doesn't exist.
	 *               Also a `WP_Error` if the title in the database ends up not being a string.
	 *
	 * @throws \InvalidArgumentException If you do not supply a positive numeric value for `$group_id`.
	 */
	public function get_group_title( $group_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			throw new \InvalidArgumentException( '$group_id must be a positive numeric value.' );
		}

		if ( ! $this->group_exists( $group_id ) ) {

			return new \WP_Error(
				'does_not_exists',
				"A group with the group_id '{$group_id}' does not exist.",
				$group_id
			);
		}

		global $wpdb;

		$title = $this->get_column( 'title', $group_id );

		if ( ! is_string( $title ) ) {

			// We store a json_encoded string in the database.
			return new \WP_Error(
				'db_unexpected_results',
				"The value for the column 'title' in the database does not appear to be a string, there may be database damage.",
				$title
			);
		}

		return $title;
	}

	/**
	 * Get the group type for the group from the database.
	 *
	 * @since 2.12.0
	 *
	 * @param int $group_id The `group_id` for the group in the database.
	 *
	 * @return mixed The value for `type` in the database for the group.
	 *               `WP_Error` if the group doesn't exist.
	 *               `WP_Error` if the value is not a string, as we expect.
	 *
	 * @throws \InvalidArgumentException If you supply a non-positive value for `$group_id`.
	 */
	public function get_group_type( $group_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			throw new \InvalidArgumentException( '$group_id must be a positive numeric value.' );
		}

		if ( ! $this->group_exists( $group_id ) ) {

			return new \WP_Error(
				'does_not_exists',
				"A group with the group_id '{$group_id}' does not exist.",
				$group_id
			);
		}

		global $wpdb;

		$type = $this->get_column( 'type', $group_id );

		if ( ! is_string( $type ) ) {

			// We store a json_encoded string in the database.
			return new \WP_Error(
				'db_unexpected_results',
				"The value for the column 'type' in the database does not appear to be a string, there may be database damage.",
				$type
			);
		}

		return $type;
	}

	/**
	 * Get the registered Group Types.
	 *
	 * @since 2.12.0
	 *
	 * @return array Contents of self::$registered_group_types.
	 */
	public function get_group_types() {
		return array_keys( $this->registered_group_types );
	}

	/**
	 * Get groups from the database.
	 *
	 * @param  array $args {
	 *     Arguments for getting a list of groups (ids or objects).
	 *
	 *     @type string $fields  Set to `ids` to get back an array of group ids (group_id column) from the database (default).
	 *                           Set to `objects` to get a list of `\AffiliateWP\Groups\Group` objects.
	 *     @type int $group_id   When set to a positive numeric value, we will only give you a single group when this is set.
	 *                           Setting `fields` to `ids` here would be silly if you already have the id.
	 *     @type int $number     Used to set `LIMIT` and limit the number of groups handed back.
	 *     @type int $offset     Used to set `OFFSET` and used to offset query.
	 *     @type string $orderby Used to set `ORDER BY` and should be a valid column in the groups database table.
	 *                           If you supply a column that doesn't exist in our database, you will get back empty results.
	 *                           If you do not supply anything, the default is `title`.
	 *     @type string $order   Used to set `ORDER` and accepts `ASC` or `DESC`.
	 *                           If you do not supply these, we will always default to `ASC`.
	 *     @type string $type    Used to set `WHERE type = %s` and will help you return groups grouped by a specific group type.
	 *                           Note, does not restrict you to registered group types or enforce sanitization beforehand.
	 *     @type string $search  Search string (searches title).
	 * }
	 * @param  bool  $count      Set to `true` to just get the number of result.
	 *
	 * @since 2.13.0
	 * @since 2.15.0 `orderby` default was changed from `group_id` to `title`.
	 *               `search` argument added that will return results where `title` is like the search term.
	 *
	 * @return array|WP_Error An `array` of `\AffiliateWP\Groups\Group` objects (might contain a `WP_Error`)
	 *                        if `$args[fields]` is not set to `ids`.
	 *                        An `array` of group ids (`group_id` in the database).
	 *                        A `WP_Error` if there is a problem with your arguments.
	 *                        A `WP_Error` if there are unexpected DB results.
	 *                        The number of groups in the database if `$count` is set to `true`.
	 *
	 * @throws \InvalidArgumentException If `$args` is not an array.
	 *                                   If `$count` is not `true` or `false`.
	 */
	public function get_groups( $args = array(), $count = false ) {

		if ( ! is_array( $args ) ) {
			throw new \InvalidArgumentException( '$args must be an array.' );
		}

		if ( ! is_bool( $count ) ) {
			throw new \InvalidArgumentException( '$count must be true or false.' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'fields'   => 'ids',              // Can also be 'objects'.
				'group_id' => 0,                  // WHERE group_id = %d.
				'number'   => -1,                 // LIMIT %d.
				'offset'   => 0,                  // OFFSET %d.
				'orderby'  => 'title',            // ORDER BY %s (Default: title).
				'order'    => 'ASC',              // ORDER ASC|DESC.
				'type'     => '',                 // Where type = %s.
				'search'   => null,               // Search.
			)
		);

		if ( ! $this->is_numeric_and_at_least_zero( $args['group_id'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[group_id] can only be set to a positive numeric value.', $args );
		}

		if ( $this->is_numeric_and_gt_zero( $args['group_id'] ) ) {

			return array(
				$this->get_group( $args['group_id'] ),
			);
		}

		if ( ! $this->is_string_and_nonempty( $args['fields'] ) || ! $this->string_is_one_of( $args['fields'], array( 'ids', 'objects' ) ) ) {
			return new \WP_Error( 'bad_arguments', "\$args[fields] must be a non-empty string and can only be set to 'ids' or 'objects'.", $args );
		}

		if ( ! is_numeric( $args['number'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[number] can only be set to a positive numeric value (zero is not allowed because it is silly).', $args );
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

		if ( null !== $args['search'] && ! $this->is_string_and_nonempty( $args['search'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[search] must null or a non-empty string.', $args );
		}

		global $wpdb;

		$results = $wpdb->get_results(
			$this->inject_table_name( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- See self::inject_table_name() for justification.
				sprintf(
					'SELECT group_id FROM {table_name} %s %s %s %s %s %s %s -- %s',

					// WHERE, etc.
					$this->where_sql(),
					$this->group_id_sql( $args['group_id'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.
					$this->type_sql( $args['type'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.
					$this->search_sql( is_null( $args['search'] ) ? '' : $args['search'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.

					// ORDER BY, ORDER, LIMIT, OFFSET (should always be last).
					$this->orderby_sql( $args['orderby'], $args['order'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->_real_escape in method.
					$this->limit_sql( $args['number'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.
					$this->offset_sql( $args['offset'] ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Values are properly $wpdb->prepare in method.
					wp_generate_uuid4() // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Used to reduce duplicate queries reported to Query Monitor.
				)
			)
		);

		if ( ! is_array( $results ) ) {
			return new \WP_Error( 'db_unexpected_results', "\$wpdb->get_results() did not return an 'array', as expected. There may be database damage.", $results );
		}

		// We need group ids for both ids and objects...
		$group_ids = $this->pluck_property_from_objects( $results, 'group_id' );

		if ( ! is_array( $group_ids ) ) {
			return $group_ids; // Probably a WP_Error because something in the DB didn't have group_id.
		}

		if ( true === $count ) {
			return count( $group_ids );
		}

		// You want id's...
		if ( 'ids' === $args['fields'] ) {

			// Might be a WP_Error because we can't reliably give you a list of ID's from the database (probaby damage).
			return array_map( 'intval', $group_ids );
		}

		// You want objects...
		$objects = array_map(
			function( $group_id ) {

				if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {

					return new \WP_Error(
						'bad_group_id',
						'group_id is not a positive numeric value.',
						$group_id
					);
				}

				if ( ! $this->group_exists( $group_id ) ) {

					return new \WP_Error(
						'group_not_exists',
						"Group with group_id '{$group_id}' does not exist in the database.",
						$group_id
					);
				}

				if ( $this->is_numeric_and_gt_zero( $group_id ) ) {
					return $this->get_group( $group_id ); // Group object (what we want).
				}

				return new \WP_Error(
					'bad_group',
					'Error getting group with group_id from the database.',
					$group_id
				);
			},
			$group_ids
		);

		// Might contain WP_Error's.
		return $objects;
	}

	/**
	 * Get a group's type from the database.
	 *
	 * @since 2.12.0
	 *
	 * @param string $type The type (cannot be empty).
	 * @param string $data If you want data about the group type, e.g. `title`
	 *                     specify that here and the value will be returned.
	 *
	 * @return mixed `false` if the group type you are asking for is not registered.
	 *               `array` of information about the a registered group type if it is registered.
	 *               `WP_Error` if `$args[type]` is not a string, or not sanitized with `sanitize_key()`, or is empty.
	 *               `null` if you ask for data from the registered groupt type and it's not there.
	 *
	 * @throws \InvalidArgumentException If `$type` is empty or not a string.
	 * @throws \InvalidArgumentException If `$data` is not a string.
	 */
	public function get_registered_group_type( $type, $data = '' ) {

		if ( ! $this->is_string_and_nonempty( $type ) ) {
			throw new \InvalidArgumentException( '$type must be a non-empty string.' );
		}

		if ( ! is_string( $data ) ) {
			throw new \InvalidArgumentException( '$data must be a string.' );
		}

		if ( $this->sanitize_group_type( $type ) !== $type ) {
			return new \WP_Error( 'type_param_not_sanitized', "\$type must be sanitized with {$this->group_type_sanitizer}().", $type );
		}

		if ( ! empty( $data ) ) {

			return isset( $this->registered_group_types[ $type ][ $data ] )

				// What they asked for.
				? $this->registered_group_types[ $type ][ $data ]

				// They asked for a registered group type that isn't registered, let's tell them why.
				: null;
		}

		return isset( $this->registered_group_types[ $type ] )

			// What they asked for.
			? $this->registered_group_types[ $type ]

			// They asked for a registered group type that isn't registered, let's tell them why.
			: false;
	}

	/**
	 * Retrieves results for a variety of query types.
	 *
	 * We built out `self::get_groups()` instead, but this is here
	 * for backwards compatibility with the `Affiliate_DB` class.
	 *
	 * @since 2.12.0
	 *
	 * @param array    $clauses  See parent::get_results().
	 * @param array    $args     See parent::get_results().
	 * @param callable $callback See parent::get_results().
	 * @param bool     $use      Set to `true` to use `parent::get_results()`.
	 *
	 * @return mixed See parent::get_results().
	 *
	 * @throws \Exception If you do not set `$use` to `true`.
	 */
	public function get_results( $clauses, $args, $callback = '', $use = false ) {

		if ( false === $use ) {
			throw new \Exception( "Use \AffiliateWP\Groups\DB::get_groups() instead, or set '\$use' to 'true' to use parent::get_results()." );
		}

		return parent::get_results( $clauses, $args, $callback = '' );
	}

	/**
	 * Does a group exist in the database?
	 *
	 * @since 2.12.0
	 *
	 * @param int $group_id The `group_id` value in the database.
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you supply a non-numeric id.
	 */
	public function group_exists( $group_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			throw new \InvalidArgumentException( '$group_id must be numeric.' );
		}

		global $wpdb;

		$db_id = $wpdb->get_var(

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We properly prepare table name in inject_table_name().
			$this->inject_table_name(
				$wpdb->prepare(
					'SELECT group_id from {table_name} WHERE group_id = %d -- %s',
					$group_id,
					wp_generate_uuid4() // Helps reduce duplicate queries reported to Query Monitor.
				)
			)
		);

		if ( ! is_numeric( $db_id ) ) {
			return false; // We're expecting a number.
		}

		if ( absint( $db_id ) === absint( $group_id ) ) {
			return true; // Verify: The group you want to see exist is in the database.
		}

		return false; // We're not sure, but probably not.
	}

	/**
	 * WHERE group_id SQL.
	 *
	 * @since 2.12.0
	 *
	 * @param mixed $group_id A numeric value for `group_id` in the database.
	 *
	 * @return string Prepared SQL.
	 */
	private function group_id_sql( $group_id ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			return ''; // This can only be numeric and positive.
		}

		global $wpdb;
		return $wpdb->prepare( 'AND group_id = %d', $group_id );
	}

	/**
	 * Insert group directly into the database.
	 *
	 * Using this method is dangerous and may cause database corruption, consider
	 * using `self::add()` instead.
	 *
	 * @since 2.12.0
	 *
	 * @param array  $data      See `Affiliate_WP_DB::insert()`.
	 * @param string $type      See `Affiliate_WP_DB:insert()`.
	 * @param int    $insert_id See `Affiliate_WP_DB:insert()`.
	 * @param bool   $raw       You must set to true to use.
	 *
	 * @return mixed See `Affiliate_WP_DB:insert()`.
	 *
	 * @throws \Exception If you try and use w/out setting `$raw` to `true` when calling.
	 */
	public function insert( $data, $type = '', $insert_id = null, $raw = false ) {

		if ( true !== $raw ) {
			throw new \Exception( "This method inserts groups directly into the database, consider using self::add() instead. If you still want to do it this way, set \$raw to 'true'." );
		}

		return parent::insert( $data, $type, $insert_id );
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
			! class_exists( '\AffWP\Affiliate\Groups\REST\v1\Endpoints' )
		) {
			return;
		}

		$this->REST = new \AffiliateWP\Groups\REST\v1\Endpoints(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Name here is conventional.
	}

	/**
	 * Register a group type.
	 *
	 * Group types are used for setting the `type` column in the database.
	 * This method also stores global information about the type.
	 *
	 * If you try to add a new group with a type that isn't registered, you can't.
	 *
	 * @param string $type The group type (must be sanitized with `santize_title_with_dashes()` prior).
	 * @param array  $args {
	 *     Arguments for registering group types.
	 *     @type string $title The title of the group (not required, not yet used).
	 * }
	 *
	 * @return mixed `true` if we registered it successfully.
	 *               `false` if it's already registered.
	 *               `WP_Error` if there is an issue with your arguments.
	 *
	 * @throws \InvalidArgumentException  If `$args` is not an `array`.
	 *                                    If `$type` is not a `string` or is empty.
	 */
	public function register_group_type( $type, $args = array() ) {

		if ( ! is_string( $type ) ) {
			throw new \InvalidArgumentException( '$type must be a string.' );
		}

		if ( ! is_array( $args ) ) {
			return new \InvalidArgumentException( '$args must be an array.' );
		}

		if ( $this->sanitize_group_type( $type ) !== $type ) {
			return new \WP_Error( 'type_param_not_sanitized', "\$type must be sanitized with {$this->group_type_sanitizer}() first.", $type );
		}

		if ( isset( $this->registered_group_types[ $type ] ) ) {
			return false; // Already registered.
		}

		$this->registered_group_types[ $type ] = array(
			'type'  => $type,
			'title' => isset( $args['title'] ) && is_string( $args['title'] )
				? $args['title']
				: '',
		);

		return true;
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

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No need for prepare here.
			$this->inject_table_name(
				'
					CREATE TABLE IF NOT EXISTS `{table_name}` (

						`group_id` bigint(20)   NOT NULL AUTO_INCREMENT,

						`type`     varchar(191) NOT NULL,
						`title`    varchar(191) NOT NULL,
						`meta`     varchar(191) NOT NULL,

						PRIMARY KEY (`group_id`)

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
	 * Update a group directly in the database.
	 *
	 * @throws \Exception If you try and use w/out setting `$raw` to `true` when calling.
	 *
	 * Using this method is dangerous and may cause database corruption, consider
	 * using `self::update_group` instead.
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
	 *
	 * @throws \Exception     If you do not set `$raw` to true.
	 */
	public function update( $row_id, $data = array(), $where = '', $type = '', $raw = false ) {

		if ( true !== $raw ) {
			throw new \Exception( "This method updates groups directly in the database, consider using self::update_group() instead. If you still want to do it this way, set \$raw to 'true'." );
		}

		return parent::update( $row_id, $data, $where, $type );
	}

	/**
	 * Update a group in the database.
	 *
	 * @since 2.12.0
	 *
	 * @param int   $group_id The value for `group_id` in the database.
	 * @param array $args {
	 *     Arguments for updating a group. Set either to null to
	 *     skip updating (or do not set).
	 *
	 *     @type string $title A value to update the title to.
	 *     @type array  $meta  A value to update the meta to.
	 * }
	 *
	 * @return mixed `true` if data was actually changed, `false` if nothing was changed (was same data).
	 *               `WP_Error` if there was an issue with your arguments.
	 *               `WP_Error` if the group doesn't exist.
	 *
	 * @throws \InvalidArgumentException If you pass invalid parameters.
	 */
	public function update_group( $group_id, $args ) {

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			throw new \InvalidArgumentException( '$group_id must be numeric and greather than zero.' );
		}

		$args = wp_parse_args(
			$args,
			array(

				// null means no update to the value.
				'title' => null,
				'meta'  => null,
				'type'  => null,
			)
		);

		// If it's not set to null, it has to be an array.
		if ( ! is_null( $args['meta'] ) && ! is_array( $args['meta'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[meta] must be an array or null.', $args );
		}

		// Title must be a string.
		if ( ! is_null( $args['title'] ) && ! $this->is_string_and_nonempty( $args['title'] ) ) {
			return new \WP_Error( 'bad_arguments', '$args[title] must be a non-empty string or null for no update.', $args );
		}

		// A group should exist to update it.
		if ( ! $this->group_exists( $group_id ) ) {
			return new \WP_Error( 'not_found', "A group with the group_id {$group_id} does not exist in the database.", $group_id );
		}

		if ( is_null( $args['type'] ) ) {
			return new \WP_Error( 'no_type', 'You must supply $args[type].', $group_id );
		}

		if ( ! in_array( $args['type'], array_keys( $this->registered_group_types ), true ) ) {
			return new \WP_Error( 'bad_type', 'Not a valid registered group type.', $group_id );
		}

		$exists_id = $this->get_group_id(
			array(
				'title' => $args['title'],
				'type'  => $args['type'],
			)
		);

		if ( $this->is_numeric_and_gt_zero( $exists_id ) ) {

			return new \WP_Error(
				'group_exists',
				'A group with the same title and type already exists, cannot update.',
				array(
					'group_id' => $group_id,
					'args'     => $args,
				)
			);
		}

		if ( ! is_null( $args['meta'] ) ) {

			$args['meta'] = array_filter(

				// Combine old meta with new meta.
				array_merge(
					$this->get_group_meta( $group_id ),
					$args['meta'] // Will override current.
				),

				// Omit meta values that are set to null.
				function( $meta_value ) {
					return is_null( $meta_value )
						? false // Omit this value, they want it turned off (null).
						: true; // Keep the value, it's not null.
				}
			);
		}

		return in_array(
			true, // If something is true in here, something was updated/changed.
			array(

				// Title.
				is_null( $args['title'] )
					? false // No changes when null.

					// Try and update it...
					: $this->update(
						$group_id,
						array(
							'title' => esc_html( $args['title'] ),
						),
						'',
						$this->type,
						true
					),

				// Meta.
				is_null( $args['meta'] )
					? false // No changes when null.

					// Try and update it...
					: $this->update(
						$group_id,
						array(
							'meta' => $this->json_encode( $args['meta'], null ),
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
	 * Santize a value for the type column in the database.
	 *
	 * @param string $value The value.
	 *
	 * @return mixed The value sanitized for the database.
	 *
	 * @throws \InvalidArgumentException If `$value` is not a string or empty.
	 */
	private function sanitize_group_type( $value ) {

		if ( ! is_string( $value ) || empty( $value ) ) {
			throw new \InvalidArgumentException( '$value must be a string and not empty.' );
		}

		$sanitizer = $this->group_type_sanitizer;

		return $sanitizer( $value );
	}

	/**
	 * WHERE type sql.
	 *
	 * @since 2.12.0
	 *
	 * @param string $type The type int he database type column.
	 *
	 * @return string Prepared sql for type.
	 */
	private function type_sql( $type ) {

		if ( ! is_string( $type ) ) {
			return ''; // You're asking for something improperly.
		}

		if ( empty( trim( $type ) ) ) {
			return ''; // You want an invisible type.
		}

		global $wpdb;
		return $wpdb->prepare( 'AND type = %s', trim( $type ) );
	}

	/**
	 * Get the group ID by title in the database.
	 *
	 * @since 2.13.0
	 *
	 * @param string $group_title The title of the group in the database.
	 * @param string $group_type  The group type in the database.
	 *
	 * @return mixed `false` if it's not in the database, ID if we found it.
	 *
	 * @throws \InvalidArgumentException If you do not supply a group type.
	 */
	public function get_group_id_by_title( string $group_title, string $group_type ) {

		if ( ! $this->is_string_and_nonempty( $group_type ) ) {
			throw new \InvalidArgumentException( '$group_type must be a non-empty string.' );
		}

		if ( ! $this->is_string_and_nonempty( $group_title ) ) {
			return false;
		}

		global $wpdb;

		$group_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We need to avoid tick marks around the table name.
				str_replace(
					'{table_name}',

					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We need to avoid tick marks around the table name.
					$wpdb->_real_escape( affiliate_wp()->groups->table_name ),

					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We need to avoid tick marks around the table name.
					'SELECT group_id FROM {table_name} WHERE `title` = %s AND `type` = %s'
				),
				$group_title,
				$group_type
			)
		);

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			return false;
		}

		return intval( $group_id );
	}

	/**
	 * Filter a group of group ids by group type.
	 *
	 * @since 2.13.0
	 *
	 * @param array  $group_ids The group ids.
	 * @param string $group_type The group type (does not have to be registered).
	 *
	 * @return array Group ids of groups that have matching group type.
	 */
	public function filter_groups_by_type( array $group_ids, string $group_type ) : array {

		return array_filter(
			$group_ids,
			function( $group_id ) use ( $group_type ) {

				if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
					throw new \InvalidArgumentException( '$group_id must be an array of group ids.' );
				}

				return $this->group_exists( $group_id ) &&
					trim( $group_type ) === $this->get_group_type( $group_id );
			}
		);
	}

	/**
	 * Convert the meta column to longtext.
	 *
	 * @since 2.13.0 Prior to this version it was varchar(191) which didn't allow for long meta strings.
	 *
	 * @return void
	 *
	 * @throws \Exception If we cannot upgrade the column.
	 */
	private function maybe_convert_meta_to_longtext() : void {

		if ( ! $this->table_exists( $this->table_name ) ) {
			return; // The table hasn't even been created, try again later.
		}

		if ( 'longtext' === $this->get_column_type( $this->table_name, 'meta' ) ) {
			return; // It's been converted.
		}

		global $wpdb;

		$wpdb->query(
			sprintf(
				'ALTER TABLE `%s`
					CHANGE `meta` `meta` longtext
					CHARACTER SET utf8mb4
					COLLATE utf8mb4_unicode_ci
					NOT NULL',

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We do not want to escape this to have '' around the table name.
				$this->table_name
			)
		);

		$column_type = $this->get_column_type( $this->table_name, 'meta' );

		if ( 'longtext' === $column_type ) {
			return;
		}

		throw new \Exception( "Unable to update table '{$this->table_name}` column 'meta' to data_type 'longtext', still '{$column_type}', please update manually and refresh this page." );
	}
}
