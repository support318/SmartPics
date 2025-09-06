<?php
namespace Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities;

/**
 * RSS_Items_Table_Handler
 *
 * @package Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities
 */
class RSS_Items_Table_Handler {

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
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . 'uap_rss_items';
	}

	/**
	 * Get the valid schema for the RSS items table.
	 *
	 * @return string The SQL schema definition for the table.
	 */
	public function get_table_schema() {
		return "
            CREATE TABLE {$this->table_name} (
                id INT NOT NULL AUTO_INCREMENT,
                action_id INT NOT NULL,
                guid VARCHAR(255) NOT NULL,
                title VARCHAR(255) NOT NULL,
                link TEXT NOT NULL,
                processed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY action_id (action_id)
            ) ENGINE=InnoDB {$this->wpdb->get_charset_collate()};
        ";
	}

	/**
	 * Add a new RSS item to the table.
	 *
	 * @param int    $action_id The ID of the action.
	 * @param string $guid      The unique GUID of the RSS item.
	 * @param string $title     The title of the RSS item.
	 * @param string $link      The link to the RSS item.
	 * @return bool True on success, false on failure.
	 */
	public function add_item( $action_id, $guid, $title, $link ) {
		$data = array(
			'action_id' => $action_id,
			'guid'      => $guid,
			'title'     => $title,
			'link'      => $link,
		);

		$format = array( '%d', '%s', '%s', '%s' );

		return (bool) $this->wpdb->insert( $this->table_name, $data, $format );
	}

	/**
	 * Edit an existing RSS item.
	 *
	 * @param int    $id    The ID of the RSS item to update.
	 * @param string $title The updated title of the RSS item.
	 * @param string $link  The updated link to the RSS item.
	 * @return bool True on success, false on failure.
	 */
	public function edit_item( $id, $title, $link ) {
		$data = array(
			'title' => $title,
			'link'  => $link,
		);

		$where        = array( 'id' => $id );
		$format       = array( '%s', '%s' );
		$where_format = array( '%d' );

		return (bool) $this->wpdb->update( $this->table_name, $data, $where, $format, $where_format );
	}

	/**
	 * Delete an RSS item by ID.
	 *
	 * @param int $id The ID of the RSS item to delete.
	 * @return bool True on success, false on failure.
	 */
	public function delete_item( $id ) {
		$where        = array( 'id' => $id );
		$where_format = array( '%d' );

		return (bool) $this->wpdb->delete( $this->table_name, $where, $where_format );
	}

	/**
	 * Fetch RSS items by action ID.
	 *
	 * @param int $action_id The action ID to filter RSS items.
	 * @return array|null An array of RSS items, or null if none found.
	 */
	public function fetch_by_action_id( $action_id ) {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE action_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$action_id
		);

		return $this->wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Delete all RSS items by action ID.
	 *
	 * @param int $action_id The action ID to delete RSS items.
	 * @return int|false The number of rows deleted, or false on failure.
	 */
	public function delete_by_action_id( $action_id ) {
		return $this->wpdb->delete(
			$this->table_name,
			array( 'action_id' => $action_id ),
			array( '%d' )
		);
	}
}
