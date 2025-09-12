<?php
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration;

/**
 * Handles trigger-specific migration logic
 */
class Triggers_Migration extends Migration_Abstract {

	/**
	 * List of trigger codes to migrate
	 *
	 * @var array
	 */
	private $trigger_codes = array(
		'WCSUBSCRIPTIONCANCELLED',
		'WCSUBSCRIPTIONVARIATION',
		'WCSUBSCRIPTIONRENEWED',
		'WC_SUBSCRIPTION_RENEWAL_COUNT',
		'WCVARIATIONSUBSCRIPTIONRENEWED',
		'WCSUBSCRIPTIONEXPIRED',
		'WCS_PAYMENT_FAILS',
		'WCSUBSCRIPTIONSSWITCHED',
		'WCSUBSCRIPTIONSTATUSCHANGED',
		'WCVARIATIONSUBSCRIPTIONEXPIRED',
		'WCVARIATIONSUBSCRIPTIONSTATUSCHANGED',
		'WCVARIATIONSUBSCRIPTIONTRIALEXPIRES',
		'WCSUBSCRIPTIONTRIALEXPIRES',
		'WCSUBSCRIPTIONSUBSCRIBE',
		'WCSPECIFICSUBVARIATION',
	);

	/**
	 * Count triggers that need migration
	 *
	 * @return int Number of triggers to migrate
	 */
	public function count_items() {

		// Create placeholders for the IN clause
		$placeholders = implode( ', ', array_fill( 0, count( $this->trigger_codes ), '%s' ) );

		// IMPORTANT: First parameter must be the post_type
		$query_params = array( 'uo-trigger' );

		// Then add all code parameters for the IN clause
		$query_params = array_merge( $query_params, $this->trigger_codes );

		// Finally add the integration parameter
		$query_params[] = 'WC';

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $this->wpdb->prepare(
			"SELECT 
            COUNT(DISTINCT post_meta_1.post_id) as count
            FROM {$this->wpdb->postmeta} post_meta_1
            INNER JOIN {$this->wpdb->postmeta} post_meta_2 ON post_meta_1.post_id = post_meta_2.post_id
            INNER JOIN {$this->wpdb->posts} posts ON post_meta_1.post_id = posts.ID
            WHERE posts.post_type = %s
            AND posts.post_status IN ('publish', 'trash', 'draft')
            AND post_meta_1.meta_key = 'code' 
            AND post_meta_1.meta_value IN ($placeholders)
            AND post_meta_2.meta_key = 'integration'
            AND post_meta_2.meta_value = %s",
			...$query_params
		);

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Get eligible triggers for migration
	 *
	 * @return array Array of triggers to migrate
	 */
	public function get_items() {

		// Create placeholders for the IN clause
		$placeholders = implode( ', ', array_fill( 0, count( $this->trigger_codes ), '%s' ) );

		// IMPORTANT: First parameter must be the post_type
		$query_params = array( 'uo-trigger' );

		// Then add all code parameters for the IN clause
		$query_params = array_merge( $query_params, $this->trigger_codes );

		// Finally add the integration parameter
		$query_params[] = 'WC';

		$query = $this->wpdb->prepare(
			"SELECT DISTINCT post_meta_1.post_id, post_meta_1.meta_id as code_meta_id, post_meta_2.meta_id as integration_meta_id 
            FROM {$this->wpdb->postmeta} post_meta_1
            JOIN {$this->wpdb->postmeta} post_meta_2 ON post_meta_1.post_id = post_meta_2.post_id
            JOIN {$this->wpdb->posts} posts ON post_meta_1.post_id = posts.ID
            WHERE posts.post_type = %s
            AND posts.post_status IN ('publish', 'trash', 'draft')
            AND post_meta_1.meta_key = 'code' 
            AND post_meta_1.meta_value IN ($placeholders)
            AND post_meta_2.meta_key = 'integration'
            AND post_meta_2.meta_value = %s",
			...$query_params
		);

		return (array) $this->wpdb->get_results( $query );
	}

	/**
	 * Create backup data for rollback utility
	 *
	 * @param array $triggers Array of triggers
	 * @return array Backup data
	 */
	public function create_backup_data( $triggers ) {
		$backup_data = array();

		foreach ( $triggers as $trigger ) {
			// Get the trigger code for backup
			$trigger_code = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT meta_value FROM {$this->wpdb->postmeta} WHERE meta_id = %d",
					$trigger->code_meta_id
				)
			);

			// Get trigger title for backup
			$trigger_title = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT post_title FROM {$this->wpdb->posts} WHERE ID = %d",
					$trigger->post_id
				)
			);

			$backup_data[] = array(
				'meta_id'    => $trigger->integration_meta_id,
				'post_id'    => $trigger->post_id,
				'meta_key'   => 'integration',
				'meta_value' => 'WC',
				'code'       => $trigger_code,
				'title'      => $trigger_title,
				'type'       => 'trigger',
			);
		}

		return $backup_data;
	}

	/**
	 * Migrate the triggers
	 *
	 * @param array $triggers Array of triggers
	 * @param array $affected_recipes Reference to affected recipes array
	 * @return array Migration stats
	 */
	public function migrate_items( $triggers, &$affected_recipes ) {
		$stats = array(
			'found'    => count( $triggers ),
			'migrated' => 0,
			'errors'   => array(),
		);

		foreach ( $triggers as $trigger ) {
			$affected_recipes[ $trigger->post_id ] = true;

			// Verify current value before updating to prevent issues
			$current_value = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT meta_value FROM {$this->wpdb->postmeta} WHERE meta_id = %d",
					$trigger->integration_meta_id
				)
			);

			if ( 'WC' !== $current_value ) {
				$stats['errors'][] = array(
					'message' => "Integration value for meta_id {$trigger->integration_meta_id} is not 'WC', it's '{$current_value}'. Skipping.",
				);
				continue;
			}

			// Update the integration value
			$updated = $this->wpdb->update(
				$this->wpdb->postmeta,
				array( 'meta_value' => 'WOOCOMMERCE_SUBSCRIPTION' ),
				array( 'meta_id' => $trigger->integration_meta_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				$stats['migrated']++; // phpcs:ignore Universal.Operators.DisallowStandalonePostIncrementDecrement.PostIncrementFound

				// Get the trigger code for logging
				$trigger_code = $this->wpdb->get_var(
					$this->wpdb->prepare(
						"SELECT meta_value FROM {$this->wpdb->postmeta} WHERE meta_id = %d",
						$trigger->code_meta_id
					)
				);

				automator_log(
					'Migrated trigger',
					array(
						'recipe_id' => $trigger->post_id,
						'code'      => $trigger_code,
					),
					true,
					'wcs-migration'
				);
			} else {
				$stats['errors'][] = array(
					'message' => "Failed to update meta_id: {$trigger->integration_meta_id}",
					'error'   => $this->wpdb->last_error,
				);
			}
		}

		return $stats;
	}
}
