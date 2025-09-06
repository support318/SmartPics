<?php
/**
 * Tools: Set Creative Type Batch Processor
 *
 * @package     AffiliateWP
 * @subpackage  Tools
 * @copyright   Copyright (c) 2023, Awesome Motive, inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.14.0
 */

namespace AffWP\Utils\Batch_Process;

use AffWP\Utils;
use AffWP\Utils\Batch_Process as Batch;

#[\AllowDynamicProperties]

/**
 * Implements a batch process to set missing creative types.
 *
 * @see \AffWP\Utils\Batch_Process\Base
 * @see \AffWP\Utils\Batch_Process
 */
class Batch_Set_Creative_Type extends Utils\Batch_Process implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @access public
	 * @since  2.14.0
	 * @var    string
	 */
	public $batch_id = 'set-creative-type';

	/**
	 * Capability needed to perform the current batch process.
	 *
	 * @access public
	 * @since  2.14.0
	 * @var    string
	 */
	public $capability = 'manage_creatives';

	/**
	 * Number of creatives to process per step.
	 *
	 * @access public
	 * @since  2.14.0
	 * @var    int
	 */
	public $per_step = 1;

	/**
	 * Initializes the batch process.
	 *
	 * This is the point where any relevant data should be initialized for use by the processor methods.
	 *
	 * @access public
	 * @since  2.14.0
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
	 * @access public
	 * @since  2.14.0
	 */
	public function pre_fetch() {

		if ( ! empty( $this->get_total_count() ) ) {
			return;
		}

		$all_creative_ids = affiliate_wp()->creatives->get_creatives(
			array(
				'fields' => 'creative_id',
				'type'   => '',
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
	 * @access public
	 * @since  2.14.0
	 */
	public function process_step() {
		$offset        = $this->get_offset();
		$current_count = $this->get_current_count();

		$creative_ids = affiliate_wp()->utils->data->get( "{$this->batch_id}_all_creative_ids", array() );

		if ( ! isset( $creative_ids[ $offset ] ) ) {
			return 'done';
		}

		$creative_id = $creative_ids[ $offset ];

		$creative = affwp_get_creative( $creative_id );

		affiliate_wp()->creatives->update(
			$creative_id,
			array(
				'type' => $creative->get_type(),
				'',
				'creative',
			)
		);

		$this->set_current_count( absint( $current_count ) + 1 );

		return ++$this->step;
	}

	/**
	 * Retrieves a message based on the given message code.
	 *
	 * @access public
	 * @since  2.14.0
	 *
	 * @param  string $code Message code.
	 * @return string Message.
	 */
	public function get_message( $code ): string {

		if ( 'done' === $code ) {
			if ( 0 === $this->get_current_count() ) {
				return __( 'No creatives were found to be updated.', 'affiliate-wp' );
			}

			return __( 'Creatives have been successfully updated.', 'affiliate-wp' );

		}

		return '';
	}

	/**
	 * Defines logic to execute after the batch processing is complete.
	 *
	 * @access public
	 * @since  2.14.0
	 *
	 * @param string $batch_id Batch process ID.
	 */
	public function finish( $batch_id ) {
		// Set upgrade complete.
		affwp_set_upgrade_complete( 'upgrade_v2140_set_creative_type' );

		// Invalidate the affiliates cache.
		wp_cache_set( 'last_changed', microtime(), 'creatives' );

		// Clean up.
		parent::finish( $batch_id );

	}

}
