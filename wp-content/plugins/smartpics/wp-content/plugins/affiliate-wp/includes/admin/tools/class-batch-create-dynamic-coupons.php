<?php
/**
 * Tools: Dynamic Coupon Creation Batch Processor
 *
 * @package     AffiliateWP
 * @subpackage  Tools
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.6
 */

namespace AffWP\Utils\Batch_Process;

use AffWP\Utils;
use AffWP\Utils\Batch_Process as Batch;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Implements a batch processor for generating coupons for affiliates.
 *
 * @since 2.6
 *
 * @see \AffWP\Utils\Batch_Process
 * @see \AffWP\Utils\Batch_Process\With_PreFetch
 */
class Create_Dynamic_Coupons extends Utils\Batch_Process implements Batch\With_PreFetch {

	/**
	 * Batch process ID.
	 *
	 * @access public
	 * @since  2.6
	 * @var    string
	 */
	public $batch_id = 'create-dynamic-coupons';

	/**
	 * Capability needed to perform the current batch process.
	 *
	 * @access public
	 * @since  2.6
	 * @var    string
	 */
	public $capability = 'manage_affiliates';

	/**
	 * Number of affiliate coupons to generate per step.
	 *
	 * @access public
	 * @since  2.6
	 * @var    int
	 */
	public $per_step = 40;

	/**
	 * Override existing coupon for the affiliate.
	 *
	 * @access public
	 * @since  2.6
	 * @var    int
	 */
	public $override_coupon = 0;

	/**
	 * Initializes values needed following instantiation.
	 *
	 * @access public
	 * @since  2.6
	 *
	 * @param null|array $data Optional. Form data. Default null.
	 */
	public function init( $data = null ) {

		if ( null !== $data && ! empty( $data['override_coupon'] ) ) {
			$this->override_coupon = $data['override_coupon'];
		}
	}

	/**
	 * Handles pre-fetching user IDs for accounts in migration.
	 *
	 * @access public
	 * @since  2.6
	 */
	public function pre_fetch() {

		$total_to_export = $this->get_total_count();

		if ( false === $total_to_export ) {
			$total_to_process = affiliate_wp()->affiliates->count( array(
				'status' => 'active',
				'number' => -1,
			) );

			$this->set_total_count( absint( $total_to_process ) );
		}
	}

	/**
	 * Executes a single step in the batch process.
	 *
	 * @access public
	 * @since  2.6
	 *
	 * @return int|string|\WP_Error Next step number, 'done', or a WP_Error object.
	 */
	public function process_step() {

		$current_count = $this->get_current_count();

		$args = array(
			'number'  => $this->per_step,
			'offset'  => $this->get_offset(),
			'orderby' => 'affiliate_id',
			'status'  => 'active',
			'order'   => 'ASC',
			'fields'  => array( 'affiliate_id' ),
		);

		$affiliates = affiliate_wp()->affiliates->get_affiliates( $args );

		if ( empty( $affiliates ) ) {
			return 'done';
		}

		$inserted = 0;

		foreach ( $affiliates as $affiliate_id ) {

			// Get all dynamic type coupons for the given affiliate.
			$coupons = affwp_get_dynamic_affiliate_coupons( $affiliate_id, false, false );

			// Handle if the affiliate has coupons.
			if ( $coupons ) {

				// If override setting is false, don't update. Continue to next affiliate.
				if ( ! $this->override_coupon ) {
					continue;
				}

				// Check each coupon.
				foreach( $coupons as $coupon ) {

					$updated = false;

					// If coupon is locked, don't update. Continue to next coupon.
					if ( true === $coupon->locked ) {
						continue;
					}

					// Update the dynamic coupon.
					$updated = $this->update_coupon( $affiliate_id, $coupon );

					// If successfully updated, increment the inserted count by 1.
					if ( false !== $updated ) {
						$inserted++;
					}

				}

			} else {

				// If the affiliate does not have coupons, add a dynamic coupon.
				$added = affiliate_wp()->affiliates->coupons->add( array(
					'affiliate_id' => $affiliate_id,
				) );

				// If successfully added, increment the inserted count by 1.
				if ( false !== $added ) {
					$inserted++;
				}

			}

		}

		$this->set_current_count( absint( $current_count ) + $inserted );

		return ++$this->step;
	}

	/**
	 * Updates coupon.
	 *
	 * @access public
	 * @since  2.9
	 *
	 * @param string $affiliate_id Affiliate ID.
	 * @param object $coupon       Coupon.
	 * @return bool True if the coupon was successfully updated, otherwise false.
	 */
	public function update_coupon( $affiliate_id, $coupon ) {

		// Bail if empty.
		if ( empty( $affiliate_id ) || empty( $coupon ) ) {
			return false;
		}

		// Bail if coupon isn't an object.
		if ( ! is_object( $coupon ) ) {
			return false;
		}

		// Set up coupon arguments.
		$coupon_args = array();

		$coupon_args['affiliate_id'] = $affiliate_id;
		$coupon_args['coupon_code']  = affiliate_wp()->affiliates->coupons->generate_code( $coupon_args );

		// Get the coupon format setting.
		$coupon_format = affiliate_wp()->settings->get( 'coupon_format' );

		// Use coupon format to set the coupon code.
		if ( false !== $coupon_format && ! empty( $coupon_format ) ) {
			// TO DO: Update this to get appropriate integration when we have multiple dynamic coupons.
			// Make Affiliate ID, coupon code, and integration available for coupon parsing functions.
			$coupon_args['integration'] = 'coupon_template_woocommerce';

			affiliate_wp()->affiliates->coupons->coupon = $coupon_args;

			$coupon_code = affiliate_wp()->affiliates->coupons->parse_tags( $coupon_format );

			$coupon_code = affwp_sanitize_coupon_code( $coupon_code );

			// If coupon code is unchanged, no need to validate.
			if ( $coupon->coupon_code === $coupon_code ){
				$coupon_args['coupon_code'] = $coupon_code;
			} else {
				// Otherwise, only update if valid. If not, it defaults to the generated code.
				if ( true === affwp_validate_coupon_code( $coupon_code ) ) {
					$coupon_args['coupon_code'] = $coupon_code;
				}
			}
		}

		return affiliate_wp()->affiliates->coupons->update_coupon( $coupon->ID, $coupon_args );
	}

	/**
	 * Retrieves a message for the given code.
	 *
	 * @access public
	 * @since  2.6
	 *
	 * @param string $code Message code.
	 * @return string Message.
	 */
	public function get_message( $code ) {

		switch ( $code ) {

			case 'done':

				$final_count = $this->get_current_count();

				if ( empty( $final_count ) ) {

					$message = __( 'All affiliates have a coupon already assigned.', 'affiliate-wp' );

				} else {

					$message = sprintf(
						_n(
							/* translators: Singular coupon for singular affiliate */
							'Coupon successfully generated for %s affiliate.',
							/* translators: Plural coupons for plural affiliates */
							'Coupons successfully generated for %s affiliates.',
							$final_count,
							'affiliate-wp'
						), number_format_i18n( $final_count )
					);

				}

				break;

			default:
				$message = '';
				break;
		}

		return $message;
	}

}
