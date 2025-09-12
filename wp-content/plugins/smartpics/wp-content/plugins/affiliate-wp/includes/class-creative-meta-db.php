<?php
/**
 * Creative Meta Database Abstraction Layer
 *
 * @package     AffiliateWP
 * @subpackage  Database
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.17.0
 */

namespace AffiliateWP\Creatives\Meta;

#[\AllowDynamicProperties]

/**
 * Core class used to implement creative meta.
 *
 * @since 2.17.0
 *
 * @see Affiliate_WP_Meta_DB
 */
final class DB extends \Affiliate_WP_Meta_DB {

	/**
	 * Represents the meta table database version.
	 *
	 * @since 2.17.0
	 * @var   string
	 */
	public $version = '1.0.0';

	/**
	 * Database group value.
	 *
	 * @since 2.17.0
	 * @var string
	 */
	public $db_group = 'creative_meta';

	/**
	 * Retrieves the table columns and data types.
	 *
	 * @since  2.17.0
	 *
	 * @return array List of creative meta table columns and their respective types.
	 */
	public function get_columns() : array {

		return array(
			'meta_id'     => '%d',
			'creative_id' => '%d',
			'meta_key'    => '%s',
			'meta_value'  => '%s',
		);
	}

	/**
	 * Retrieves the meta type.
	 *
	 * @since 2.17.0
	 *
	 * @return string Meta type.
	 */
	public function get_meta_type() : string {
		return 'creative';
	}

	/**
	 * Creates the table.
	 *
	 * @since 2.17.0
	 *
	 * @see dbDelta()
	 */
	public function create_table() {

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$this->table_name} (
			meta_id bigint(20) NOT NULL AUTO_INCREMENT,
			creative_id bigint(20) NOT NULL DEFAULT '0',
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY creative_id (creative_id),
			KEY meta_key (meta_key(191))
			) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}
}
