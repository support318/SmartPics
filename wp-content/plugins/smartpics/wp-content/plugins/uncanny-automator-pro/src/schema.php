<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator_Pro\Integration\Loopable_Rss\Utilities\RSS_Items_Table_Handler;
use wpdb;
/**
 * Class Schema
 *
 * @package Uncanny_Automator_Pro
 */
class Schema {

	/**
	 * @var string
	 */
	const VERSION = '6.2.0';

	/**
	 * @var string
	 */
	const VERSION_OPTION_KEY = 'uap_pro_db_version';

	/**
	 * @var wpdb
	 */
	protected $db = null;

	/**
	 * @var string[] $tables
	 */
	protected $tables = array();

	/**
	 * @var string $table_queries
	 */
	private $table_queries = '';

	/**
	 * Initiates the db property.
	 *
	 * @return void
	 */
	public function __construct() {

		global $wpdb;
		$this->db = $wpdb;

	}

	/**
	 * Registers a filter that adds ability for pro tables to be repair from Status > Tools > Database.
	 *
	 * @return void
	 */
	public function tools_attach_tables() {

		// List Pro tables.
		add_filter( 'automator_database_tables', array( $this, 'list_tables' ) );
		// Add the missing Pro tables to the missing tables notification.
		add_filter( 'automator_db_missing_tables', array( $this, 'add_missing_tables' ) );
		// Handle the repair.
		add_action( 'automator_repair_tables_after', array( $this, 'repair_tables' ) );

	}

	/**
	 * Callback method to the filter 'automator_database_tables'.
	 *
	 * Includes Pro tables in the table list inside Status > Tools > Database.
	 *
	 * @param object $core_tables
	 *
	 * @return string[]
	 */
	public function list_tables( $core_tables ) {

		$tables = $this->define_tables();

		$core_tables = array_merge( (array) $core_tables, array_keys( $tables ) );

		return $core_tables;
	}

	/**
	 * Callback method to the 'automator_db_missing_tables'.
	 *
	 * Includes Pro tables as missing tables if it is missing.
	 *
	 * @param string[] $tables
	 *
	 * @return string[]
	 */
	public function add_missing_tables( $tables ) {

		$this->prepare_tables();

		$wp_delta = dbDelta( $this->table_queries, false );

		foreach ( (array) $wp_delta as $table_name => $query ) {

			if ( "Created table $table_name" === $query ) {
				$tables[] = $table_name;
			}
		}

		return $tables;

	}

	/**
	 * Repairs the Pro table.
	 *
	 * @return void
	 */
	public function repair_tables() {
		delete_option( self::VERSION_OPTION_KEY );
	}

	/**
	 * @return string[]
	 */
	protected function define_tables() {

		$charset = $this->db->get_charset_collate();

		$loop_entries       = $this->db->prefix . 'uap_loop_entries';
		$loop_entries_items = $this->db->prefix . 'uap_loop_entries_items';
		$loop_queue         = $this->db->prefix . 'uap_queue';
		$scheduled_actions  = $this->db->prefix . 'uap_scheduled_actions';

		// Table "uap_loop_entries".
		$this->tables['uap_loop_entries'] = "CREATE TABLE {$loop_entries} (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `loop_id` bigint(20) NOT NULL,
            `loop_type` varchar(20) NOT NULL,
            `process_id` varchar(80) NOT NULL,
            `recipe_id` bigint(20) NOT NULL,
            `recipe_log_id` bigint(20) NOT NULL,
            `run_number` bigint(20) NOT NULL,
            `status` varchar(40) NOT NULL,
            `message` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
            `entity_ids` longtext NOT NULL,
            `num_entities` bigint(20) NOT NULL,
            `flow` longtext NOT NULL,
            `meta` longtext DEFAULT NULL,
            `process_date_started` datetime NOT NULL,
            `process_date_ended` datetime DEFAULT NULL,
            `date_added` datetime NOT NULL,
            `date_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`ID`),
            KEY loop_id (`loop_id`),
            KEY loop_type (`loop_type`),
            KEY process_id (`process_id`),
            KEY recipe_id (`recipe_id`),
            KEY recipe_log_id (`recipe_log_id`),
            KEY status (`status`)
            ) ENGINE=InnoDB {$charset};";

		// Table "uap_loop_entries_items".
		$this->tables['uap_loop_entries_items'] = "CREATE TABLE {$loop_entries_items} (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) NOT NULL,
            `entity_id` bigint(20) NOT NULL,
            `action_id` bigint(20) NOT NULL,
            `filter_id` text NOT NULL,
            `status` varchar(40) NOT NULL,
            `error_message` text NOT NULL,
            `recipe_id` bigint(20) NOT NULL,
            `recipe_log_id` bigint(20) NOT NULL,
            `recipe_run_number` bigint(20) NOT NULL,
            `action_data` longtext NOT NULL,
            `action_tokens` longtext NOT NULL,
            `date_added` datetime NOT NULL,
            `date_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
            KEY user_id (`user_id`),
            KEY entity_id (`entity_id`),
            KEY action_id (`action_id`),
            KEY status (`status`),
            KEY recipe_id (`recipe_id`),
            KEY recipe_log_id (`recipe_log_id`)
            ) ENGINE=InnoDB {$charset};";

		// Table "uap_queue"
		$this->tables['uap_queue'] = "CREATE TABLE {$loop_queue} (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `process_id` varchar(80) NOT NULL,
            `state` varchar(20) NOT NULL,
            PRIMARY KEY (`ID`),
			UNIQUE KEY `process_id` (`process_id`)
            ) ENGINE=InnoDB {$charset};";

		// Table "uap_scheduled_actions"
		$this->tables['uap_scheduled_actions'] = "CREATE TABLE {$scheduled_actions} (
            `ID` bigint(20) NOT NULL AUTO_INCREMENT,
            `hash` varchar(100) NOT NULL,
            `value` longtext NOT NULL,
			`date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ID`),
			UNIQUE KEY `hash` (`hash`)
            ) ENGINE=InnoDB {$charset};";

		// Table "uap_rss_items"
		$rss_table_handler             = new RSS_Items_Table_Handler( $this->db );
		$this->tables['uap_rss_items'] = $rss_table_handler->get_table_schema();

		return $this->tables;

	}

	/**
	 * @return void
	 */
	protected function prepare_tables() {

		foreach ( (array) $this->tables as $table ) {
			$this->table_queries .= $table;
		}

	}

	/**
	 * Creates the tables.
	 *
	 * @return void
	 */
	public function create_tables() {

		$current_version = get_option( self::VERSION_OPTION_KEY, '' );

		if ( self::VERSION === $current_version ) {
			return;
		}

		$this->define_tables();
		$this->prepare_tables();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( $this->table_queries );

		update_option( self::VERSION_OPTION_KEY, self::VERSION );

	}

}
