<?php
namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration;

use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration\Triggers_Migration;
use Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration\Actions_Migration;
use Uncanny_Automator\Migrations\Migration;

/**
 * Migrate WooCommerce Subscription triggers and actions from WC to WOOCOMMERCE_SUBSCRIPTION integration.
 */
class Migrate_WCS_Integration extends Migration {

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $integration_name = 'WooCommerce Subscription';

	/**
	 * Option name for storing backup data.
	 *
	 * @var string
	 */
	private $backup_option_name = 'automator_wcs_migration_backup';

	/**
	 * Helper for trigger migrations
	 *
	 * @var Triggers_Migration
	 */
	private $trigger_migration;

	/**
	 * Helper for action migrations
	 *
	 * @var Actions_Migration
	 */
	private $action_migration;

	/**
	 * Helper for loop token migrations
	 *
	 * @var Loop_Tokens_Migration
	 */
	private $loop_token_migration;

	/**
	 * Helper for user loop filter migrations
	 *
	 * @var User_Loop_Filter_Migration
	 */
	private $user_loop_filter_migration;

	/**
	 * Stats tracking.
	 *
	 * @var array
	 */
	private $stats = array(
		'triggers_found'    => 0,
		'triggers_migrated' => 0,
		'actions_found'     => 0,
		'actions_migrated'  => 0,
		'recipes_affected'  => 0,
		'errors'            => array(),
	);

	/**
	 * Constructor
	 */
	public function __construct( $migration_name ) {

		parent::__construct( $migration_name );

		$this->trigger_migration          = new Triggers_Migration( $this );
		$this->action_migration           = new Actions_Migration( $this );
		$this->loop_token_migration       = new Loop_Tokens_Migration( $this );
		$this->user_loop_filter_migration = new User_Loop_Filter_Migration( $this );

		// Force migration if the HTTP query is set. In case of a bug.
		if ( $this->http_query_force_migrate() ) {
			$this->migrate();
		}
	}

	/**
	 * Run only if necessary.
	 *
	 * @return bool True if migration should run.
	 */
	public function conditions_met() {

		// Check for triggers and actions that need migration
		$trigger_count = $this->trigger_migration->count_items();
		$action_count  = $this->action_migration->count_items();

		return ( $trigger_count > 0 || $action_count > 0 );
	}

	/**
	 * Perform the migration.
	 *
	 * @return void
	 */
	public function migrate() {

		automator_log( 'Starting WooCommerce Subscription migration', $this->integration_name, true, 'wcs-migration' );

		try {
			// Get affected recipes
			$affected_recipes = array();

			// Step 1: Find all eligible items to migrate
			$loop_tokens       = $this->loop_token_migration->get_items();
			$triggers          = $this->trigger_migration->get_items();
			$actions           = $this->action_migration->get_items();
			$user_loop_filters = $this->user_loop_filter_migration->get_items();

			// If no items found, complete the migration
			if ( empty( $triggers ) && empty( $actions ) && empty( $loop_tokens ) && empty( $user_loop_filters ) ) {
				automator_log( 'No items found to migrate', $this->integration_name, true, 'wcs-migration' );
				$this->complete();
				return;
			}

			// Step 2: Create backup data for rollback utility
			$backup_data = array_merge(
				$this->loop_token_migration->create_backup_data( $loop_tokens ),
				$this->trigger_migration->create_backup_data( $triggers ),
				$this->action_migration->create_backup_data( $actions ),
				$this->user_loop_filter_migration->create_backup_data( $user_loop_filters )
			);

			update_option( $this->backup_option_name, $backup_data, false );

			automator_log(
				'Created backup of ' . count( $backup_data ) . ' items',
				$this->integration_name,
				true,
				'wcs-migration'
			);

			// Step 3: Migrate the items
			$trigger_results          = $this->trigger_migration->migrate_items( $triggers, $affected_recipes );
			$action_results           = $this->action_migration->migrate_items( $actions, $affected_recipes );
			$loop_token_results       = $this->loop_token_migration->migrate_items( $loop_tokens, $affected_recipes );
			$user_loop_filter_results = $this->user_loop_filter_migration->migrate_items( $user_loop_filters, $affected_recipes );

			// Update stats
			$this->stats['triggers_found']    = $trigger_results['found'];
			$this->stats['triggers_migrated'] = $trigger_results['migrated'];

			// Actions.
			$this->stats['actions_found']    = $action_results['found'];
			$this->stats['actions_migrated'] = $action_results['migrated'];

			// Loops tokens.
			$this->stats['loop_tokens_found']    = $loop_token_results['found'];
			$this->stats['loop_tokens_migrated'] = $loop_token_results['migrated'];

			// User loop filters.
			$this->stats['user_loop_filters_found']    = $user_loop_filter_results['found'];
			$this->stats['user_loop_filters_migrated'] = $user_loop_filter_results['migrated'];

			// Recipes affected.
			$this->stats['recipes_affected'] = count( $affected_recipes );
			$this->stats['errors']           = array_merge(
				$trigger_results['errors'],
				$action_results['errors'],
				$loop_token_results['errors'],
				$user_loop_filter_results['errors']
			);

			// Step 4: Log results and clear cache
			$this->log_migration_results();
			$this->clear_cache();
			$this->complete();

		} catch ( \Exception $e ) {

			$this->stats['errors'][] = array(
				'message' => $e->getMessage(),
				'trace'   => $e->getTraceAsString(),
			);

			automator_log( 'WCS migration failed', $this->stats, true, 'wcs-migration' );
		}
	}

	/**
	 * Log migration results.
	 *
	 * @return void
	 */
	private function log_migration_results() {

		// Check if any items were actually migrated.
		$total_found    = $this->stats['triggers_found'] + $this->stats['actions_found'];
		$total_migrated = $this->stats['triggers_migrated'] + $this->stats['actions_migrated'];

		if ( $total_found > 0 && 0 === $total_migrated ) {
			automator_log( 'Warning: No items were migrated despite finding ' . $total_found . ' eligible items.', $this->integration_name, true, 'wcs-migration' );
		}

		if ( ! empty( $this->stats['errors'] ) ) {
			automator_log( 'Migration completed with some errors', $this->stats['errors'], true, 'wcs-migration' );
		}

		// Log completion stats.
		automator_log( 'WCS migration completed', $this->stats, true, 'wcs-migration' );
	}

	/**
	 * Clear all caches.
	 *
	 * @return bool True if cache was cleared, false otherwise.
	 */
	private function clear_cache() {

		// Clear all caches using Automator's cache utility if available.
		if ( method_exists( 'Automator', 'cache' ) && method_exists( Automator()->cache, 'remove_all' ) ) {
			Automator()->cache->remove_all();
			return true;
		}

		return false;
	}

	/**
	 * Check if the migration should be forced.
	 *
	 * @return bool True if migration should be forced.
	 */
	private function http_query_force_migrate() {

		return isset( $_GET['force_migrate_woocommerce_subscription'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& 'yes' === $_GET['force_migrate_woocommerce_subscription'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& is_admin() // Must be in wp-admin.
			&& current_user_can( 'manage_options' ); // Must be able to manage options.
	}
}
