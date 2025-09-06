<?php
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration;

/**
 * Base class for migration helpers
 */
abstract class Migration_Abstract {

	/**
	 * The migration coordinator
	 *
	 * @var Migrate_WCS_Integration
	 */
	protected $migration;

	/**
	 * WordPress database
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Constructor
	 *
	 * @param Migrate_WCS_Integration $migration The parent migration
	 */
	public function __construct( $migration ) {
		global $wpdb;
		$this->wpdb      = $wpdb;
		$this->migration = $migration;
	}

	/**
	 * Check if items need migration
	 *
	 * @return int Count of items needing migration
	 */
	abstract public function count_items();

	/**
	 * Get items to migrate
	 *
	 * @return array Items to migrate
	 */
	abstract public function get_items();

	/**
	 * Create backup data for items
	 *
	 * @param array $items Items to backup
	 * @return array Backup data
	 */
	abstract public function create_backup_data( $items );

	/**
	 * Migrate items
	 *
	 * @param array $items Items to migrate
	 * @param array $affected_recipes Reference to affected recipes
	 * @return array Migration stats
	 */
	abstract public function migrate_items( $items, &$affected_recipes );
}
