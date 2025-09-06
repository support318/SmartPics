<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class WCM_USER_HAS_ACTIVE_PRODUCT_MEMBERSHIP
 *
 * @package Uncanny_Automator_Pro
 * Loop filter The user has an active membership to {{a product}}
 *
 * @since   5.0
 */
class WCM_USER_HAS_ACTIVE_PRODUCT_MEMBERSHIP extends Loop_Filter {

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function setup() {
		$this->set_integration( 'WCMEMBERSHIPS' );
		$this->set_meta( 'WCM_USER_HAS_ACTIVE_PRODUCT_MEMBERSHIP' );
		$this->set_sentence( esc_html_x( 'The user has an active membership of {{a product}}', 'Filter sentence', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: Filter sentence */
				esc_html_x( 'The user has an active membership of {{a product:%1$s}}', 'Filter sentence', 'uncanny-automator-pro' ),
				$this->get_meta()
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_users_with_active_membership' ) );
	}

	/**
	 * @return array
	 */
	public function load_options() {

		return array(
			$this->get_meta() => array(
				array(
					'option_code'     => $this->get_meta(),
					'type'            => 'select',
					'label'           => esc_html_x( 'Membership product', 'WooCommerce filter options', 'uncanny-automator-pro' ),
					'required'        => true,
					'options'         => $this->membership_product_options(),
					'options_show_id' => false,
				),
			),
		);

	}

	/**
	 * Get membership products.
	 *
	 * @return array
	 */
	public function membership_product_options() {
		$options  = array();
		$products = Automator()->helpers->recipe->wc_memberships->options->wcm_get_all_membership_plans( null, '', array( 'is_any' => true ) );

		foreach ( $products['options'] as $product_id => $product_name ) {
			$options[] = array(
				'value' => $product_id,
				'text'  => $product_name,
			);
		}

		return $options;
	}

	/**
	 * Get user IDs with active membership.
	 *
	 * @param array{WCM_USER_HAS_ACTIVE_PRODUCT_MEMBERSHIP:int} $fields
	 *
	 * @return array
	 */
	public function retrieve_users_with_active_membership( $fields ) {
		// Bail if value is falsy.
		if ( empty( $fields['WCM_USER_HAS_ACTIVE_PRODUCT_MEMBERSHIP'] ) ) {
			return array();
		}

		// Get memberships users by product ID.
		$product_id = $fields['WCM_USER_HAS_ACTIVE_PRODUCT_MEMBERSHIP'];

		return $this->get_active_members_by_product_id( $product_id );
	}

	/**
	 * Get active member's user IDs by product ID.
	 *
	 * @param int $product_id
	 *
	 * @return array
	 */
	private function get_active_members_by_product_id( $product_id ) {
		global $wpdb;

		$user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_type = %s AND post_status = %s", 'wc_user_membership', 'wcm-active' ) );
		if ( intval( '-1' ) !== intval( $product_id ) ) {
			$user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_type = 'wc_user_membership' AND post_status = 'wcm-active' AND post_parent = %d", absint( $product_id ) ) );
		}

		return is_null( $user_ids ) ? array() : $user_ids;
	}
}
