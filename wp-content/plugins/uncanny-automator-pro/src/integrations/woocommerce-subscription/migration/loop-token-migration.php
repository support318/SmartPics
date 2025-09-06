<?php
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace Uncanny_Automator_Pro\Integrations\Woocommerce_Subscription\Migration;

/**
 * Migrates loop tokens that reference WooCommerce Subscription
 */
class Loop_Tokens_Migration extends Migration_Abstract {

	/**
	 * Count loop tokens that need migration
	 *
	 * @return int Number of tokens to check
	 */
	public function count_items() {
		// Find all loop tokens with non-empty iterable_expression
		$query = $this->wpdb->prepare(
			"SELECT COUNT(DISTINCT pm1.post_id) as count
			FROM {$this->wpdb->postmeta} pm1
			JOIN {$this->wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
			WHERE pm1.meta_key = 'code'
			AND pm1.meta_value = %s
			AND pm2.meta_key = 'iterable_expression'
			AND pm2.meta_value IS NOT NULL
			AND pm2.meta_value != ''",
			'LOOP_TOKEN'
		);

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Get eligible loop tokens for migration
	 *
	 * @return array Array of loop tokens to check
	 */
	public function get_items() {
		// Find all loop tokens with non-empty iterable_expression
		$query = $this->wpdb->prepare(
			"SELECT pm1.post_id, pm1.meta_id AS code_meta_id, 
			pm2.meta_id AS iterable_meta_id, pm2.meta_value AS iterable_expression
			FROM {$this->wpdb->postmeta} pm1
			JOIN {$this->wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
			WHERE pm1.meta_key = 'code'
			AND pm1.meta_value = %s
			AND pm2.meta_key = 'iterable_expression'
			AND pm2.meta_value IS NOT NULL
			AND pm2.meta_value != ''",
			'LOOP_TOKEN'
		);

		return (array) $this->wpdb->get_results( $query );
	}

	/**
	 * Create backup data for loop tokens
	 *
	 * @param array $tokens Array of tokens
	 * @return array Backup data
	 */
	public function create_backup_data( $tokens ) {
		$backup_data = array();

		foreach ( $tokens as $token ) {
			// We need to analyze the token to see if it actually needs migration
			$needs_migration = $this->token_needs_migration( $token );

			if ( $needs_migration ) {
				$backup_data[] = array(
					'meta_id'    => $token->iterable_meta_id,
					'post_id'    => $token->post_id,
					'meta_key'   => 'iterable_expression',
					'meta_value' => $token->iterable_expression,
					'type'       => 'loop_token',
				);
			}
		}

		return $backup_data;
	}

	/**
	 * Migrate the loop tokens
	 *
	 * @param array $tokens Array of tokens
	 * @param array $affected_recipes Reference to affected recipes array
	 * @return array Migration stats
	 */
	public function migrate_items( $tokens, &$affected_recipes ) {

		$stats = array(
			'found'    => count( $tokens ),
			'migrated' => 0,
			'errors'   => array(),
		);

		foreach ( $tokens as $token ) {
			try {
				// Check if token needs migration
				if ( ! $this->token_needs_migration( $token ) ) {
					continue;
				}

				$affected_recipes[ $token->post_id ] = true;

				// Unserialize the iterable_expression
				$iterable_data = maybe_unserialize( $token->iterable_expression );

				// Decode the JSON string in fields
				$fields = json_decode( $iterable_data['fields'], true );

				if ( ! is_array( $fields ) || json_last_error() !== JSON_ERROR_NONE ) {
					$stats['errors'][] = array(
						'message' => "Failed to decode JSON for token in post_id: {$token->post_id}",
						'error'   => json_last_error_msg(),
					);
					continue;
				}

				// Check token value and replace :WC: with :WOOCOMMERCE_SUBSCRIPTION:
				if ( isset( $fields['TOKEN'] ) && isset( $fields['TOKEN']['value'] ) ) {
					$token_value     = $fields['TOKEN']['value'];
					$new_token_value = str_replace( ':WC:', ':WOOCOMMERCE_SUBSCRIPTION:', $token_value );

					// Only update if there was a change
					if ( $token_value !== $new_token_value ) {
						$fields['TOKEN']['value'] = $new_token_value;

						// Re-encode the fields
						$iterable_data['fields'] = wp_json_encode( $fields );

						// Update the database
						$updated = $this->wpdb->update(
							$this->wpdb->postmeta,
							array( 'meta_value' => maybe_serialize( $iterable_data ) ),
							array( 'meta_id' => $token->iterable_meta_id ),
							array( '%s' ),
							array( '%d' )
						);

						if ( $updated ) {
							++$stats['migrated'];
							automator_log(
								'Migrated loop token',
								array(
									'recipe_id' => $token->post_id,
									'from'      => $token_value,
									'to'        => $new_token_value,
								),
								true,
								'wcs-migration'
							);
						} else {
							$stats['errors'][] = array(
								'message' => "Failed to update meta_id: {$token->iterable_meta_id}",
								'error'   => $this->wpdb->last_error,
							);
						}
					}
				}
			} catch ( \Exception $e ) {
				$stats['errors'][] = array(
					'message' => "Error processing token for post_id: {$token->post_id}",
					'error'   => $e->getMessage(),
				);
			}
		}

		return $stats;
	}

	/**
	 * Determine if a token needs migration
	 *
	 * @param object $token Token data
	 * @return bool Whether the token needs migration
	 */
	private function token_needs_migration( $token ) {
		try {
			// Unserialize the iterable_expression
			$iterable_data = maybe_unserialize( $token->iterable_expression );

			if ( ! is_array( $iterable_data ) || ! isset( $iterable_data['fields'] ) || empty( $iterable_data['fields'] ) ) {
				return false;
			}

			// Decode the JSON string in fields
			$fields = json_decode( $iterable_data['fields'], true );

			if ( ! is_array( $fields ) || json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}

			// Check if there's a TOKEN field with a value containing :WC:
			if ( isset( $fields['TOKEN'] ) && isset( $fields['TOKEN']['value'] ) ) {
				$token_value = $fields['TOKEN']['value'];
				return strpos( $token_value, ':WC:' ) !== false;
			}

			return false;
		} catch ( \Exception $e ) {
			return false;
		}
	}
}
