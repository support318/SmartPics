<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Advanced_Coupons_Helpers;

/**
 * Class Memberpress_Courses_Pro_Helpers
 *
 * @package Uncanny_Automator
 */
class Advanced_Coupons_Pro_Helpers extends Advanced_Coupons_Helpers {

	/**
	 * Load options variable.
	 *
	 * @var bool
	 */
	public $load_options = true;

	/**
	 * Advanced_Coupons_Pro_Helpers constructor.
	 */
	public function __construct() {
		// Selectively load options
		if ( property_exists( '\Uncanny_Automator\Advanced_Coupons_Helpers', 'load_options' ) ) {
			$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
		}

		// Reister action hooks.
		$this->register_hooks();
	}

	/**
	 * Setpro function is used to set pro variable.
	 *
	 * @param Advanced_Coupons_Pro_Helpers $pro
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	public function setPro( Advanced_Coupons_Pro_Helpers $pro ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		parent::setPro( $pro );
	}

	/**
	 * Register supporting action hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {

		// Add Filter for store credit increase source types
		add_filter(
			'acfw_get_store_credits_increase_source_types',
			array(
				$this,
				'add_new_credit_source_type',
			)
		);

		// Add Filter for store credit decrease action types
		add_filter(
			'acfw_get_store_credit_decrease_action_types',
			array(
				$this,
				'add_decrease_action_type',
			)
		);

		// Add Filter for my account credit labels
		add_filter(
			'acfw_query_store_credit_entries',
			array(
				$this,
				'filter_my_account_credit_labels',
			)
		);
	}

	/**
	 * Adding new credit increase source type.
	 *
	 * @param $registry
	 *
	 * @return mixed
	 */
	public function add_new_credit_source_type( $registry ) {

		$registry['admin_increase_auto'] = array(
			'name'    => _x( 'Uncanny Automator (increase)', 'Advanced Coupons', 'uncanny-automator-pro' ),
			'slug'    => 'admin_increase_auto',
			'related' => array(
				'object_type'         => 'uncanny_link',
				'admin_label'         => _x( 'Uncanny Automator Pro', 'Advanced Coupons', 'uncanny-automator-pro' ),
				'admin_link_callback' => array( 'Uncanny_Automator_Pro\Advanced_Coupons_Pro_Helpers', 'maybe_get_credit_recipe_url' ),
				'label'               => '-',
			),
		);

		return $registry;
	}

	/**
	 * Add credit decrease action type.
	 *
	 * @param array $actions
	 *
	 * @return array
	 */
	public function add_decrease_action_type( $actions ) {

		$actions['admin_decrease_auto'] = array(
			'name'    => _x( 'Uncanny Automator (decrease)', 'Advanced Coupons', 'uncanny-automator-pro' ),
			'slug'    => 'admin_decrease_auto',
			'related' => array(
				'object_type'         => 'uncanny_link',
				'admin_label'         => _x( 'Uncanny Automator Pro', 'Advanced Coupons', 'uncanny-automator-pro' ),
				'label'               => '-',
				'admin_link_callback' => 'get_edit_post_link',
			),
		);

		return $actions;
	}

	/**
	 * Filter Automator action credit labels for My Account.
	 *
	 * @param WP_REST_Response $response
	 *
	 * @return WP_REST_Response
	 */
	public function filter_my_account_credit_labels( $response ) {

		// Bail if the is_admin flag is set.
		if ( automator_filter_has_var( 'is_admin' ) ) {
			return $response;
		}

		// Validate response data.
		if ( empty( $response ) || ! is_a( $response, 'WP_REST_Response' ) || ! is_array( $response->data ) ) {
			return $response;
		}

		$decrease_label = __( 'Uncanny Automator (decrease)', 'uncanny-automator-pro' );
		$increase_label = __( 'Uncanny Automator (increase)', 'uncanny-automator-pro' );

		// Loop through each store credit entry.
		foreach ( $response->data as $k => $credit ) {

			$activity        = isset( $credit['activity'] ) ? $credit['activity'] : '';
			$note            = isset( $credit['note'] ) ? $credit['note'] : '';
			$is_default_note = $note === 'Uncanny Automator';

			if ( $increase_label === $activity ) {
				$response->data[ $k ]['activity'] = $is_default_note ? $increase_label : $note;
			} elseif ( $decrease_label === $activity ) {
				$response->data[ $k ]['activity'] = $is_default_note ? $decrease_label : $note;
			}
		}

		return $response;
	}

	/**
	 * Maybe get the Recipe ID from the credit record object ID.
	 *
	 * @param $object_id
	 *
	 * @return bool|string
	 */
	public static function maybe_get_credit_recipe_url( $object_id ) {
		// Review - the old object ID was for the user, the new object ID is for the recipe.
		$recipe = get_post( $object_id );
		if ( $recipe && 'uo-recipe' === $recipe->post_type ) {
			return get_edit_post_link( $object_id );
		}
		return false;
	}

}
