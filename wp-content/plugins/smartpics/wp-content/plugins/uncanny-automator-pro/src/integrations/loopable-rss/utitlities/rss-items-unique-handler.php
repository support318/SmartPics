<?php
namespace Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities;

use InvalidArgumentException;

/**
 * RSS_Items_Unique_Handler
 *
 * @package Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities
 */
class RSS_Items_Unique_Handler {

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * The table name for RSS items.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor to initialize the database object and table name.
	 *
	 * @param wpdb $wpdb The WordPress database object.
	 */
	public function __construct( $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'uap_rss_items';
	}

	/**
	 * Checks if an RSS item is unique by a specific column.
	 *
	 * @param string $value The value to check for uniqueness.
	 * @param int    $action_id The value to check for uniqueness.
	 * @param string $type  The type of uniqueness to check ('guid' or 'title').
	 * @return bool True if the RSS item is unique, false otherwise.
	 * @throws InvalidArgumentException If an unsupported type is provided.
	 */
	public function is_unique( $value, $action_id, $type = 'guid' ) {

		// Validate the type of uniqueness.
		if ( ! in_array( $type, array( 'guid', 'title' ), true ) ) {
			throw new InvalidArgumentException( "Unsupported uniqueness type: $type" );
		}

		// Prepare the query.
		$query = $this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->table_name} WHERE {$type} = %s AND action_id = %d", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$value,
			$action_id
		);

		// Execute the query and get the count.
		$count = (int) $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Return true if no matching record exists.
		return $count === 0; // phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
	}
}
