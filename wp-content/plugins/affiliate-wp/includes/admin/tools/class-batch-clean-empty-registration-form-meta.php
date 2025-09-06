<?php
/**
 * Tools: Clean Empty Registration Form Meta Batch Processor
 *
 * @package     AffiliateWP
 * @subpackage  Tools
 * @copyright   Copyright (c) 2023, Awesome Motive, inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.27.6
 */

namespace AffWP\Utils\Batch_Process;

use AffWP\Utils;
use AffWP\Utils\Batch_Process as Batch;

/**
 * Implements a batch process to set missing creative types.
 *
 * @see \AffWP\Utils\Batch_Process\Base
 * @see \AffWP\Utils\Batch_Process
 */
class Batch_Clean_Empty_Registration_Form_Meta extends Utils\Batch_Process implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @since  2.27.6
	 * @var    string
	 */
	public $batch_id = 'clean-empty-registration-form-meta';

	/**
	 * Number of items to process per step.
	 *
	 * @since  2.27.6
	 * @var    int
	 */
	public $per_step = 100;

	/**
	 * Initializes the batch process.
	 *
	 * This is the point where any relevant data should be initialized for use by the processor methods.
	 *
	 * @since  2.27.6
	 * @param null|array $data Data to initialize.
	 */
	public function init( $data = null ) {

		if ( $this->step >= 1 ) {
			return;
		}

		$this->set_current_count( 0 );
	}

	/**
	 * Pre-fetches data to speed up processing.
	 *
	 * @since  2.27.6
	 */
	public function pre_fetch() {

		if ( ! empty( $this->get_total_count() ) ) {
			return;
		}

		global $wpdb;

		$table = "{$wpdb->prefix}postmeta";

		$this->set_total_count(
			$wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- We are trusting $table.
					"SELECT COUNT(*) FROM $table WHERE meta_key = %s AND meta_value = %s",
					'affwp_submission_forms_hashes',
					'a:0:{}'
				)
			) ?? 0
		);
	}

	/**
	 * Processes a single step (batch).
	 *
	 * @since  2.27.6
	 */
	public function process_step() {

		$current_count = $this->get_current_count();

		global $wpdb;

		$table = "{$wpdb->prefix}postmeta";

		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- We are trusting $table.
				"SELECT meta_id FROM $table WHERE meta_key = %s AND meta_value = %s LIMIT %d, %d",
				'affwp_submission_forms_hashes',
				'a:0:{}',
				0,
				$this->per_step
			)
		);

		if ( empty( $results ) ) {
			return 'done';
		}

		$wpdb->query(
			sprintf(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- We are trusting $table.
				"DELETE FROM $table WHERE meta_id IN (%s)",
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepare statement causes delays when deleting rows.
				implode( ',', wp_list_pluck( $results, 'meta_id' ) )
			)
		);

		$this->set_current_count( absint( $current_count ) + $this->per_step );

		return ++$this->step;
	}

	/**
	 * Retrieves a message based on the given message code.
	 *
	 * @since  2.27.6
	 *
	 * @param  string $code Message code.
	 * @return string Message.
	 */
	public function get_message( $code ): string {

		if ( 'done' === $code ) {
			if ( 0 === $this->get_current_count() ) {
				return esc_html__( 'No legacy post meta entries found.', 'affiliate-wp' );
			}

			return esc_html__( 'Legacy post meta entries removed.', 'affiliate-wp' );
		}

		return '';
	}

	/**
	 * Defines logic to execute after the batch processing is complete.
	 *
	 * @since  2.27.6
	 *
	 * @param string $batch_id Batch process ID.
	 */
	public function finish( $batch_id ) {
		// Set upgrade complete.
		affwp_set_upgrade_complete( 'upgrade_v2276_clean_empty_registration_form_meta' );

		// Clean up.
		parent::finish( $batch_id );

	}
}
