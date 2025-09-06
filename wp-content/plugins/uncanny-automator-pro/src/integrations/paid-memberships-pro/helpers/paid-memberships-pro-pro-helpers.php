<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Paid_Memberships_Pro_Helpers;

/**
 * Class Paid_Memberships_Pro_Pro_Helpers
 * @package Uncanny_Automator_Pro
 */
class Paid_Memberships_Pro_Pro_Helpers extends Paid_Memberships_Pro_Helpers {

	/**
	 * Paid_Memberships_Pro_Pro_Helpers constructor.
	 */
	public function __construct() {
	}

	/**
	 * Get Membership Select Condition field args.
	 *
	 * @param string $option_code - The option code identifier.
	 *
	 * @return array
	 */
	public function get_membership_condition_field_args( $option_code ) {
		return array(
			'option_code'           => $option_code,
			'label'                 => esc_html__( 'Level', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_membership_conditions_options(),
			'supports_custom_value' => true,
		);
	}

	/**
	 * Get the membership condition options
	 *
	 * @return array
	 */
	public function get_membership_conditions_options() {
		if ( ! function_exists( 'pmpro_getAllLevels' ) ) {
			return array();
		}
		static $condition_options = null;

		if ( ! is_null( $condition_options ) ) {
			return $condition_options;
		}

		$include_hidden    = true;
		$use_cache         = true;
		$memberships       = pmpro_getAllLevels( $include_hidden, $use_cache );
		$condition_options = array();

		if ( empty( $memberships ) ) {
			return $condition_options;
		}

		$condition_options[] = array(
			'value' => - 1,
			'text'  => __( 'Any level', 'uncanny-automator-pro' ),
		);

		foreach ( $memberships as $membership ) {
			$condition_options[] = array(
				'value' => $membership->id,
				'text'  => $membership->name,
			);
		}

		//usort( $condition_options, array( $this, 'sort_by_text_key' ) );

		return $condition_options;
	}

	/**
	 * Evaluate the condition
	 *
	 * @param $membership_id - WP_Post ID of the membership plan
	 * @param $user_id - WP_User ID
	 *
	 * @return bool
	 */
	public function evaluate_condition_check( $membership_id, $user_id ) {

		// Check for Any Active memberships.
		if ( $membership_id < 0 ) {
			$include_inactive   = false;
			$active_memberships = pmpro_getMembershipLevelsForUser( $user_id, $include_inactive );

			return ! empty( $active_memberships );
		}

		// Check for specific membership.
		$is_member = pmpro_hasMembershipLevel( $membership_id, $user_id );

		return ! empty( $is_member );
	}

}
