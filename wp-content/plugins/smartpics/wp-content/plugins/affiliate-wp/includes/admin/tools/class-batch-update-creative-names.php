<?php
/**
 * Tools: Update Creative Names Batch Processor
 *
 * @package     AffiliateWP
 * @subpackage  Tools
 * @copyright   Copyright (c) 2023, Awesome Motive, inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.16.0
 */

namespace AffWP\Utils\Batch_Process;

use AffWP\Utils;
use AffWP\Utils\Batch_Process as Batch;

#[\AllowDynamicProperties]

/**
 * Implements the batch process to make all Creative names publicly visible in version 2.16.0.
 *
 * @see \AffWP\Utils\Batch_Process\Base
 * @see \AffWP\Utils\Batch_Process
 *
 * @since 2.16.0
 */
class Batch_Update_Creative_Names extends Utils\Batch_Process implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @since  2.16.0
	 * @var    string
	 */
	public $batch_id = 'update-creative-names';

	/**
	 * Capability needed to perform the current batch process.
	 *
	 * @since  2.16.0
	 * @var    string
	 */
	public $capability = 'manage_creatives';

	/**
	 * Number of creatives to process per step.
	 *
	 * @since  2.16.0
	 * @var    int
	 */
	public $per_step = 1;

	/**
	 * Additional request data.
	 *
	 * @since  2.16.0
	 * @var    array
	 */
	public array $data = array();

	/**
	 * Initializes the batch process.
	 *
	 * This is the point where any relevant data should be initialized for use by the processor methods.
	 *
	 * @since  2.16.0
	 * @param null|array $data Data to initialize.
	 */
	public function init( $data = null ) {

		$this->data = $data;

		if ( $this->step >= 1 ) {
			return;
		}

		$this->set_current_count( 0 );

	}

	/**
	 * Pre-fetches data to speed up processing.
	 *
	 * @since  2.16.0
	 */
	public function pre_fetch() {

		if ( ! empty( $this->get_total_count() ) ) {
			return;
		}

		$all_creative_ids = affiliate_wp()->creatives->get_creatives(
			array(
				'fields' => 'creative_id',
				'number' => 0,
			)
		);

		if ( count( $all_creative_ids ) > 0 ) {
			affiliate_wp()->utils->data->write( "{$this->batch_id}_all_creative_ids", $all_creative_ids );
		}

		$this->set_total_count( count( $all_creative_ids ) );

	}

	/**
	 * Processes a single step (batch).
	 *
	 * @since  2.16.0
	 */
	public function process_step() {

		$offset                = $this->get_offset();
		$current_count         = $this->get_current_count();
		$creative_name_privacy = in_array( $this->data['creative_name_privacy'], array( 'public', 'private' ), true )
			? sanitize_text_field( $this->data['creative_name_privacy'] )
			: 'private';

		// Save the option just on the first step.
		if ( 1 === (int) $this->step ) {

			update_option( 'affwp_creative_name_privacy', $creative_name_privacy );

		}

		// If public was chosen, we don't need to update any creative and we can stop the process here.
		if ( 'public' === $creative_name_privacy ) {
			return 'done';
		}

		$creative_ids = affiliate_wp()->utils->data->get( "{$this->batch_id}_all_creative_ids", array() );

		// Check if we shall continue our not.
		if ( ! isset( $creative_ids[ $offset ] ) ) {
			return 'done';
		}

		$creative_id = $creative_ids[ $offset ];

		$creative = affwp_get_creative( $creative_id );

		// At this point, we are dealing with private creatives only. Update creative.
		if ( $creative->is_before_migration_time( 'date_updated' ) ) {

			affiliate_wp()->creatives->update(
				$creative_id,
				array(
					'name'  => __( 'Creative', 'affiliate-wp' ),
					'notes' => $creative->name,
				),
				'',
				'creative',
			);

		}

		$this->set_current_count( absint( $current_count ) + 1 );

		$this->step++;

		return $this->step;
	}

	/**
	 * Retrieves a message based on the given message code.
	 *
	 * @since  2.16.0
	 *
	 * @param  string $code Message code.
	 * @return string Message.
	 */
	public function get_message( $code ): string {

		$done = 'done' === $code;

		if ( $done && 0 === $this->get_current_count() ) {
			return __( 'No creatives were found to be updated.', 'affiliate-wp' );
		} elseif ( $done ) {
			return __( 'Creatives have been successfully updated.', 'affiliate-wp' );
		}

		return '';
	}

	/**
	 * Refresh the page if the chosen option was NO, and it is on any Creative admin screen.
	 *
	 * @since 2.16.0
	 * @return string The URL to redirect or an empty string if no redirection needed.
	 */
	public function get_redirect_url() : string {

		// The redirection should only occur on Creative's admin pages.
		$is_creatives_page = false;

		// Verify the referrer URL to determine whether we are on the correct page.
		$parsed_url = isset( $_SERVER['HTTP_REFERER'] )
			? wp_parse_url( $_SERVER['HTTP_REFERER'] )
			: null;

		// The 'page' argument will be used to validate Creative's admin pages.
		if ( isset( $parsed_url['query'] ) ) {

			parse_str( $parsed_url['query'], $query_params );

			$is_creatives_page = isset( $query_params['page'] ) && 'affiliate-wp-creatives' === $query_params['page'];
		}

		// Perform redirection if the Creative's name privacy is set to 'private' and we are on a Creative's admin page.
		if ( 'private' === $this->data['creative_name_privacy'] && $is_creatives_page ) {

			return affwp_admin_url( 'creatives', array( 'affwp_notice' => 'creatives_name_upgraded' ) );
		}

		// No redirection needed.
		return '';
	}

	/**
	 * Defines logic to execute after the batch processing is complete.
	 *
	 * @since  2.16.0
	 *
	 * @param string $batch_id Batch process ID.
	 */
	public function finish( $batch_id ) {

		affwp_set_upgrade_complete( 'upgrade_v2160_update_creative_names' );

		// Invalidate the affiliates cache.
		wp_cache_set( 'last_changed', microtime(), 'creatives' );

		// Clean up.
		parent::finish( $batch_id );
	}

}
