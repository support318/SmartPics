<?php
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration;

use Uncanny_Automator_Pro\Integrations\Plugin_Actions\Utils\Array_Flattener;

/**
 * Migrates user loop filters that reference Woo
 */
class User_Loop_Filter_Migration extends Migration_Abstract {

	/**
	 * There is only one filter that needs migration.
	 */
	const FILTER_CODE = 'WCS_HAS_USER_AN_ACTIVE_SUBSCRIPTION';

	/**
	 * Count user loop filters that need migration
	 *
	 * @return int Number of user loop filters to check
	 */
	public function count_items() {

		// Find all loop tokens with non-empty iterable_expression
		$query = $this->wpdb->prepare(
			"SELECT COUNT(DISTINCT pm1.post_id) as count
			    FROM {$this->wpdb->postmeta} pm1
                JOIN {$this->wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
			    WHERE 
                    pm1.meta_key = 'code'
			        AND pm1.meta_value = %s
                    AND pm2.meta_key = 'integration_code'
                    AND pm2.meta_value = %s
                ",
			self::FILTER_CODE,
			'WC' // <-- Find filters that are associated with the WooCommerce Integration.
		);

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Get eligible loop tokens for migration
	 *
	 * @return array Array of loop tokens to check
	 */
	public function get_items() {

		$query = $this->wpdb->prepare(
			"SELECT 
                pm1.meta_id, 
                pm1.post_id, 
                pm2.meta_value as 'integration_code'
			    FROM {$this->wpdb->postmeta} pm1
                JOIN {$this->wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
			    WHERE 
                    pm1.meta_key = 'code'
			        AND pm1.meta_value = %s
                    AND pm2.meta_key = 'integration_code'
                    AND pm2.meta_value = %s
                ",
			self::FILTER_CODE,
			'WC' // <-- Find filters that are associated with the WooCommerce Integration.
		);

		$items = $this->wpdb->get_results( $query );

		return $items;
	}

	/**
	 * Create backup data for loop tokens
	 *
	 * @param array $user_loop_filters Array of user loop filters
	 * @return array Backup data
	 */
	public function create_backup_data( $user_loop_filters ) {

		$backup_data = array();

		foreach ( $user_loop_filters as $user_loop_filter ) {

			$post_meta = (array) get_post_meta( $user_loop_filter->post_id );

			if ( ! isset(
				$post_meta['code'][0],
				$post_meta['integration'][0],
				$post_meta['integration_name'][0],
				$post_meta['backup'][0]
			) ) {
				// Skip if the required post metas are not set.
				continue;
			}

			$backup_data[] = array(
				'meta_id'          => $user_loop_filter->meta_id,
				'post_id'          => $user_loop_filter->post_id,
				'code'             => $post_meta['code'][0],
				'integration'      => $post_meta['integration'][0],
				'integration_name' => $post_meta['integration_name'][0],
				'backup'           => $post_meta['backup'][0],
			);

		}

		return $backup_data;
	}

	/**
	 * Migrate the loop tokens
	 *
	 * @param array $user_loop_filters Array of tokens
	 * @param array $affected_recipes Reference to affected recipes array
	 * @return array Migration stats
	 */
	public function migrate_items( $user_loop_filters, &$affected_recipes ) {

		$stats = array(
			'found'    => count( $user_loop_filters ),
			'migrated' => 0,
			'errors'   => array(),
		);

		foreach ( $user_loop_filters as $user_loop_filter ) {

			$loop_id            = get_post_parent( $user_loop_filter->post_id );
			$recipe_id          = get_post_parent( $loop_id );
			$affected_recipes[] = $recipe_id;

			$stats['migrated'] = $stats['migrated'] + 1;

			// Update the loop filter to use the new code.
			update_post_meta( $user_loop_filter->post_id, 'integration_name', 'Woo Subscriptions' );

			// Update the loop filter to use the new integration.
			update_post_meta( $user_loop_filter->post_id, 'integration', 'WOOCOMMERCE_SUBSCRIPTION' );
			update_post_meta( $user_loop_filter->post_id, 'integration_code', 'WOOCOMMERCE_SUBSCRIPTION' );

			$backup_sentence = get_post_meta( $user_loop_filter->post_id, 'backup', true );

			if ( is_string( $backup_sentence ) ) {
				$new_backup_sentence = (array) json_decode( $backup_sentence, true );
				if ( isset( $new_backup_sentence['integration_name'] ) ) {
					if ( 'Woo' === $new_backup_sentence['integration_name'] ) {
						$new_backup_sentence['integration_name'] = 'Woo Subscriptions';
						update_post_meta( $user_loop_filter->post_id, 'backup', wp_json_encode( $new_backup_sentence ) );
					}
				}
			} else {
				$stats['errors'][] = $user_loop_filter->post_id;
			}
		}

		return $stats;
	}
}
