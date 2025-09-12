<?php
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration;

/**
 * Handles action-specific migration logic
 */
class Actions_Migration extends Migration_Abstract {

	/**
	 * List of action codes to migrate
	 *
	 * @var array
	 */
	private $action_codes = array(
		'WCVARIATIONSUBCANCELLED',
		'WCCREATEORDERFORSUBSCRIPTION',
		'WCCREATEORDERFORSUBSCRIPTIONWITHPG',
		'WC_EXTENDUSERSUBSCRIPTION',
		'WCS_NEXT_DATE_EXTENDED',
		'WCS_NEXT_DATE_EXTENDED_SV',
		'WC_SHORTEN_SUBSCRIPTION',
		'WC_EXTENDUSERVARIATIONSUBSCRIPTION',
		'WCS_REMOVE_PRODUCT',
		'WCS_REMOVE_VARIATION',
		'WCVARIATIONSUBSCRIPION',
		'WCVARIATIONSUBSCRIPIONS',
	);

	/**
	 * Count actions that need migration
	 *
	 * @return int Number of actions to migrate
	 */
	public function count_items() {

		// Create placeholders for the IN clause
		$placeholders = implode( ', ', array_fill( 0, count( $this->action_codes ), '%s' ) );

		// IMPORTANT: First parameter must be the post_type
		$query_params = array( 'uo-action' );

		// Then add all code parameters for the IN clause
		$query_params = array_merge( $query_params, $this->action_codes );

		// Finally add the integration parameter
		$query_params[] = 'WC';

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
	 * Get eligible actions for migration
	 *
	 * @return array Array of actions to migrate
	 */
	public function get_items() {

		// Create placeholders for the IN clause
		$placeholders = implode( ', ', array_fill( 0, count( $this->action_codes ), '%s' ) );

		// IMPORTANT: First parameter must be the post_type
		$query_params = array( 'uo-action' );

		// Then add all code parameters for the IN clause
		$query_params = array_merge( $query_params, $this->action_codes );

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
	 * @param array $actions Array of actions
	 * @return array Backup data
	 */
	public function create_backup_data( $actions ) {

		$backup_data = array();

		foreach ( $actions as $action ) {
			// Get the action code for backup
			$action_code = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT meta_value FROM {$this->wpdb->postmeta} WHERE meta_id = %d",
					$action->code_meta_id
				)
			);

			// Get action title for backup
			$action_title = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT post_title FROM {$this->wpdb->posts} WHERE ID = %d",
					$action->post_id
				)
			);

			$backup_data[] = array(
				'meta_id'    => $action->integration_meta_id,
				'post_id'    => $action->post_id,
				'meta_key'   => 'integration',
				'meta_value' => 'WC',
				'code'       => $action_code,
				'title'      => $action_title,
				'type'       => 'action',
			);
		}

		return $backup_data;
	}

	/**
	 * Migrate the actions
	 *
	 * @param array $actions Array of actions
	 * @param array $affected_recipes Reference to affected recipes array
	 * @return array Migration stats
	 */
	public function migrate_items( $actions, &$affected_recipes ) {

		$stats = array(
			'found'    => count( $actions ),
			'migrated' => 0,
			'errors'   => array(),
		);

		foreach ( $actions as $action ) {
			$affected_recipes[ $action->post_id ] = true;

			// Verify current value before updating to prevent issues
			$current_value = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT meta_value FROM {$this->wpdb->postmeta} WHERE meta_id = %d",
					$action->integration_meta_id
				)
			);

			if ( 'WC' !== $current_value ) {
				$stats['errors'][] = array(
					'message' => "Integration value for meta_id {$action->integration_meta_id} is not 'WC', it's '{$current_value}'. Skipping.",
				);
				continue;
			}

			// Update the integration value
			$updated = $this->wpdb->update(
				$this->wpdb->postmeta,
				array( 'meta_value' => 'WOOCOMMERCE_SUBSCRIPTION' ),
				array( 'meta_id' => $action->integration_meta_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				$stats['migrated']++; // phpcs:ignore Universal.Operators.DisallowStandalonePostIncrementDecrement.PostIncrementFound

				// Get the action code for logging
				$action_code = $this->wpdb->get_var(
					$this->wpdb->prepare(
						"SELECT meta_value FROM {$this->wpdb->postmeta} WHERE meta_id = %d",
						$action->code_meta_id
					)
				);

				automator_log(
					'Migrated action',
					array(
						'recipe_id' => $action->post_id,
						'code'      => $action_code,
					),
					true,
					'wcs-migration'
				);
			} else {
				$stats['errors'][] = array(
					'message' => "Failed to update meta_id: {$action->integration_meta_id}",
					'error'   => $this->wpdb->last_error,
				);
			}
		}

		return $stats;
	}
}
