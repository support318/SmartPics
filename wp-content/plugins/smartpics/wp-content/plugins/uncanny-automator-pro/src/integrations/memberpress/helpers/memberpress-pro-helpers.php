<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Memberpress_Helpers;

/**
 * Class Memberpress_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Memberpress_Pro_Helpers extends Memberpress_Helpers {

	/**
	 * Memberpress_Pro_Helpers constructor.
	 */
	public function __construct() {
		// Selectively load options

		//		if ( property_exists( '\Uncanny_Automator\Memberpress_Helpers', 'load_options' ) ) {
		//
		//			$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
		//		}

	}

	/**
	 * @param Memberpress_Pro_Helpers $pro
	 */
	public function setPro( Memberpress_Pro_Helpers $pro ) {
		parent::setPro( $pro );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function all_memberpress_products( $label = null, $option_code = 'MPPRODUCT', $args = array() ) {
		if ( ! $this->load_options ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Product', 'uncanny-automator-pro' );
		}

		$defaults = array(
			'uo_include_any' => false,
			'uo_include_all' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'      => 'memberpressproduct',
			'posts_per_page' => 999,
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_mepr_product_period_type',
					'value'   => 'lifetime',
					'compare' => '!=',
				),
				array(
					'key'     => '_mepr_product_period_type',
					'value'   => 'lifetime',
					'compare' => '=',
				),
			),
		);

		$options = Automator()->helpers->recipe->options->wp_query( $query_args );
		if ( true === $args['uo_include_any'] ) {
			$options = array( '-1' => esc_attr__( 'Any product', 'uncanny-automator-pro' ) ) + $options;
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code                => esc_attr__( 'Product title', 'uncanny-automator-pro' ),
				$option_code . '_ID'        => esc_attr__( 'Product ID', 'uncanny-automator-pro' ),
				$option_code . '_URL'       => esc_attr__( 'Product URL', 'uncanny-automator-pro' ),
				$option_code . '_THUMB_ID'  => esc_attr__( 'Product featured image ID', 'uncanny-automator-pro' ),
				$option_code . '_THUMB_URL' => esc_attr__( 'Product featured image URL', 'uncanny-automator-pro' ),
			),
		);

		return apply_filters( 'uap_option_all_memberpress_products', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function all_memberpress_products_recurring( $label = null, $option_code = 'MPPRODUCT', $args = array() ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Product', 'uncanny-automator' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any recurring subscription product', 'uncanny-automator' ),
			)
		);

		$options = array();

		if ( $args['uo_include_any'] ) {
			$options[- 1] = $args['uo_any_label'];
		}

		$query_args = array(
			'post_type'      => 'memberpressproduct',
			'posts_per_page' => 999,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_mepr_product_period_type',
					'value'   => 'lifetime',
					'compare' => '!=',
				),
			),
		);
		$qry        = Automator()->helpers->recipe->wp_query( $query_args );
		$options    = $options + $qry;

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code                => esc_attr__( 'Product title', 'uncanny-automator' ),
				$option_code . '_ID'        => esc_attr__( 'Product ID', 'uncanny-automator' ),
				$option_code . '_URL'       => esc_attr__( 'Product URL', 'uncanny-automator' ),
				$option_code . '_THUMB_ID'  => esc_attr__( 'Product featured image ID', 'uncanny-automator' ),
				$option_code . '_THUMB_URL' => esc_attr__( 'Product featured image URL', 'uncanny-automator' ),
			),
		);

		if ( isset( $args['relevant_tokens'] ) && is_array( $args['relevant_tokens'] ) ) {
			$option['relevant_tokens'] = $args['relevant_tokens'];
		}

		return apply_filters( 'uap_option_all_memberpress_products_recurring', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function all_mpca_corporate_accounts( $label = null, $option_code = 'MPCAACCOUNTS', $args = array() ) {
		if ( ! $this->load_options ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Parent account', 'uncanny-automator-pro' );
		}

		$args    = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any parent account', 'uncanny-automator-pro' ),
			)
		);
		$options = array();

		if ( $args['uo_include_any'] ) {
			$options[- 1] = $args['uo_any_label'];
		}

		$parent_accounts = \MPCA_Corporate_Account::get_all();

		foreach ( $parent_accounts as $parent_account ) {
			$user                                = get_userdata( $parent_account->user_id );
			$options[ $parent_account->user_id ] = $user->user_email;
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code                  => esc_attr__( 'Parent account', 'uncanny-automator-pro' ),
				$option_code . '_SUBACCUNAME' => esc_attr__( 'Username', 'uncanny-automator-pro' ),
				$option_code . '_SUBACCFNAME' => esc_attr__( 'First name', 'uncanny-automator-pro' ),
				$option_code . '_SUBACCLNAME' => esc_attr__( 'Last name', 'uncanny-automator-pro' ),
				$option_code . '_SUBACCEMAIL' => esc_attr__( 'Email', 'uncanny-automator-pro' ),
			),
		);

		return apply_filters( 'uap_option_all_mpca_corporate_accounts', $option );
	}

	/**
	 * @param $label
	 * @param $option_code
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_txn_statuses( $label = null, $option_code = 'TXN_STATUS', $args = array() ) {
		if ( ! $this->load_options ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Status', 'uncanny-automator-pro' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any status', 'uncanny-automator-pro' ),
			)
		);

		$options = array(
			'complete' => __( 'Complete', 'uncanny-automator-pro' ),
			'failed'   => __( 'Failed', 'uncanny-automator-pro' ),
			'pending'  => __( 'Pending', 'uncanny-automator-pro' ),
			'refunded' => __( 'Refunded', 'uncanny-automator-pro' ),
		);

		if ( $args['uo_include_any'] ) {
			$options = array( '-1' => $args['uo_any_label'] ) + $options;
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code => esc_attr__( 'Status', 'uncanny-automator-pro' ),
			),
		);

		return apply_filters( 'uap_option_get_all_txn_statuses', $option );
	}

	/**
	 * get_all_thrive_quizzes
	 *
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_mp_coupons( $args = array() ) {
		$defaults = array(
			'option_code'           => 'MP_COUPONS',
			'label'                 => esc_attr__( 'Coupon', 'uncanny-automator-pro' ),
			'is_any'                => false,
			'is_all'                => false,
			'supports_custom_value' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			//phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'posts_per_page' => apply_filters( 'automator_select_all_posts_limit', 999, 'memberpresscoupon' ),
			'orderby'        => 'title',
			'order'          => 'DESC',
			'post_type'      => 'memberpresscoupon',
			'post_status'    => 'publish',
		);

		$mp_coupons = Automator()->helpers->recipe->options->wp_query( $query_args );

		if ( true === $args['is_any'] ) {
			$mp_coupons = array( '-1' => __( 'Any coupon', 'uncanny-automator-pro' ) ) + $mp_coupons;
		}

		if ( true === $args['is_all'] ) {
			$mp_coupons = array( '-1' => __( 'All coupons', 'uncanny-automator-pro' ) ) + $mp_coupons;
		}

		$relevant_tokens = array(
			'MPPRODUCT'           => esc_attr__( 'Product title', 'uncanny-automator-pro' ),
			'MPPRODUCT_ID'        => esc_attr__( 'Product ID', 'uncanny-automator-pro' ),
			'MPPRODUCT_URL'       => esc_attr__( 'Product URL', 'uncanny-automator-pro' ),
			'MPPRODUCT_THUMB_ID'  => esc_attr__( 'Product featured image ID', 'uncanny-automator-pro' ),
			'MPPRODUCT_THUMB_URL' => esc_attr__( 'Product featured image URL', 'uncanny-automator-pro' ),
			'MP_COUPON'           => esc_attr__( 'Coupon code', 'uncanny-automator-pro' ),
			'MP_COUPON_TYPE'      => esc_attr__( 'Coupon type', 'uncanny-automator-pro' ),
			'MP_COUPON_AMOUNT'    => esc_attr__( 'Coupon amount', 'uncanny-automator-pro' ),
		);

		$option = array(
			'option_code'           => $args['option_code'],
			'label'                 => $args['label'],
			'input_type'            => 'select',
			'required'              => true,
			'options_show_id'       => false,
			'relevant_tokens'       => $relevant_tokens,
			'options'               => $mp_coupons,
			'supports_custom_value' => $args['supports_custom_value'],
		);

		return apply_filters( 'uap_option_get_all_thrive_quizzes', $option );
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
			'label'                 => esc_html__( 'Membership', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_membership_conditions_options(),
			'supports_custom_value' => true,
		);
	}

	/**
	 * Get Membership Select Condition options.
	 *
	 * @return array
	 */
	public function get_membership_conditions_options() {
		if ( ! class_exists( '\MeprCptModel' ) ) {
			return array();
		}
		// Get the cached options.
		static $condition_options = null;
		if ( ! is_null( $condition_options ) ) {
			return $condition_options;
		}

		// Build Options.
		$condition_options = array();
		$memberships       = \MeprCptModel::all( 'MeprProduct' );

		if ( ! empty( $memberships ) ) {
			// Add any membership option.
			$condition_options[] = array(
				'value' => - 1,
				'text'  => esc_html__( 'Any membership', 'uncanny-automator-pro' ),
			);
			// Sort memberships by title.
			usort(
				$memberships,
				function ( $a, $b ) {
					return strcmp( $a->post_title, $b->post_title );
				}
			);
			// Add memberships to options.
			foreach ( $memberships as $membership ) {
				$condition_options[] = array(
					'value' => $membership->ID,
					'text'  => $membership->post_title,
				);
			}
		}

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

		$mepr_user            = new \MeprUser( $user_id );
		$ignore_cache         = true;
		$exclude_expired      = true;
		$active_subscriptions = $mepr_user->active_product_subscriptions( 'ids', $ignore_cache, $exclude_expired );

		// No Active Subscriptions.
		if ( empty( $active_subscriptions ) ) {
			return false;
		}

		// Any Membership.
		if ( $membership_id < 0 ) {
			return true;
		}

		// Specific Membership.
		return in_array( $membership_id, $active_subscriptions, true );
	}

}
