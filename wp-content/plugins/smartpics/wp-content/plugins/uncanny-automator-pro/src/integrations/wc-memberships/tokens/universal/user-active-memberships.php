<?php

namespace Uncanny_Automator_Pro\Integrations\Woocommerce\Tokens\Loopable\Universal;

use Uncanny_Automator\Services\Loopable\Loopable_Token_Collection;
use Uncanny_Automator\Services\Loopable\Universal_Loopable_Token;

/**
 * User_Active_Memberships
 *
 * @package Uncanny_Automator\Integrations\Woocommerce\Tokens\Loopable
 */
class User_Active_Memberships extends Universal_Loopable_Token {

	/**
	 * Register loopable token.
	 *
	 * @return void
	 */
	public function register_loopable_token() {

		$child_tokens = array(
			'MEMBERSHIP_ID'          => array(
				'name'       => _x( 'Membership ID', 'Woo Membership', 'uncanny-automator' ),
				'token_type' => 'integer',
			),
			'MEMBERSHIP_PLAN_ID'     => array(
				'name'       => _x( 'Membership plan ID', 'Woo Membership', 'uncanny-automator' ),
				'token_type' => 'integer',
			),
			'MEMBERSHIP_PRODUCT_IDS' => array(
				'name'       => _x( 'Membership product IDs', 'Woo Membership', 'uncanny-automator' ),
				'token_type' => 'text',
			),
			'MEMBERSHIP_NAME'        => array(
				'name' => _x( 'Membership name', 'Woo Membership', 'uncanny-automator' ),
			),
			'MEMBERSHIP_START_DATE'  => array(
				'name'       => _x( 'Membership start date', 'Woo Membership', 'uncanny-automator' ),
				'token_type' => 'text',
			),
			'MEMBERSHIP_END_DATE'    => array(
				'name'       => _x( 'Membership end date', 'Woo Membership', 'uncanny-automator' ),
				'token_type' => 'text',
			),
		);

		$this->set_id( 'ACTIVE_MEMBERSHIPS' );
		$this->set_name( _x( "User's active memberships", 'Woo Membership', 'uncanny-automator' ) );
		$this->set_log_identifier( '#{{MEMBERSHIP_ID}} {{MEMBERSHIP_NAME}}' );
		$this->set_child_tokens( $child_tokens );

	}

	/**
	 * Hydrate the tokens.
	 *
	 * @param mixed $args
	 *
	 * @return Loopable_Token_Collection
	 */
	public function hydrate_token_loopable( $args ) {
		$loopable    = new Loopable_Token_Collection();
		$memberships = $this->get_user_memberships_details( absint( $args['user_id'] ?? 0 ) );

		// Bail if empty.
		if ( false === $memberships ) {
			return $loopable;
		}

		foreach ( $memberships as $membership ) {
			$loopable->create_item(
				array(
					'MEMBERSHIP_ID'          => $membership['membership_id'],
					'MEMBERSHIP_PLAN_ID'     => $membership['plan_id'],
					'MEMBERSHIP_PRODUCT_IDS' => join( ', ', $membership['product_ids'] ),
					'MEMBERSHIP_NAME'        => $membership['name'],
					'MEMBERSHIP_START_DATE'  => $membership['start_date'],
					'MEMBERSHIP_END_DATE'    => $membership['end_date'],
				)
			);
		}

		return $loopable;

	}

	/**
	 * Retrieves specific membership details for a user in WooCommerce.
	 *
	 * @param int $user_id The ID of the user whose memberships are being retrieved.
	 *
	 * @return array|false An array of membership details or false on failure.
	 */
	public function get_user_memberships_details( $user_id ) {
		// Validate the user ID.
		if ( ! is_numeric( $user_id ) || $user_id <= 0 ) {
			return false; // Invalid user ID provided.
		}

		// Retrieve all memberships for the specified user ID.
		$memberships = wc_memberships_get_user_active_memberships( $user_id );

		// Check if any memberships were found.
		if ( empty( $memberships ) ) {
			return false; // No memberships found for this user.
		}

		$membership_data = array();

		// Loop through each membership and gather relevant details.
		foreach ( $memberships as $membership ) {

			// Ensure $membership is a valid WC_Memberships_User_Membership object.
			if ( false === ( $membership instanceof \WC_Memberships_User_Membership ) ) {
				continue; // Skip if the object is not a valid membership.
			}

			// Get the membership ID.
			$membership_id = $membership->get_id();
			$plan          = $membership->get_plan();
			// Initialize variables for membership name, price, and description.
			$membership_name = $plan->name;

			$membership_data[] = array(
				'membership_id' => $membership_id,
				'name'          => $plan->get_name(),
				'plan_id'       => $plan->get_id(),
				'product_ids'   => $plan->get_product_ids(),
				'start_date'    => $plan->get_access_start_date( 'Y-m-d H:i:s' ),
				'end_date'      => $plan->get_access_end_date( 'Y-m-d H:i:s' ),
			);
		}

		// Return the membership data or false if no valid memberships were processed.
		return ! empty( $membership_data ) ? $membership_data : false;
	}


}
