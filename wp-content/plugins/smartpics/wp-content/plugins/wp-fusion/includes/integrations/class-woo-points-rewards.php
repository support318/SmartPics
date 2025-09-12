<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Woo_Points_Rewards extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'woo-points-rewards';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'WooCommerce Points & Rewards';

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */
	public function init() {

		add_filter( 'wpf_meta_fields', array( $this, 'set_contact_field_names' ), 30 );
		add_action( 'wc_points_rewards_after_increase_points', array( $this, 'after_set_points_balance' ) );
		add_action( 'wc_points_rewards_after_set_points_balance', array( $this, 'after_set_points_balance' ) );
		add_filter( 'wpf_get_user_meta', array( $this, 'get_user_meta' ), 10, 2 );
	}

	/**
	 * Syncs points when they're awarded.
	 *
	 * @param int $user_id The user ID who earned the points.
	 */
	public function after_set_points_balance( $user_id ) {

		$points_balance = WC_Points_Rewards_Manager::get_users_points( $user_id );

		update_user_meta( $user_id, 'wc_points_balance', $points_balance ); // keep it in usermeta too.

		wp_fusion()->user->push_user_meta( $user_id, array( 'wc_points_balance' => $points_balance ) );
	}

	/**
	 * Gets the points balance when exporting user meta.
	 *
	 * @since 3.43.11
	 *
	 * @param array $user_meta The user meta.
	 * @param int   $user_id   The user ID.
	 *
	 * @return array $user_meta The user meta.
	 */
	public function get_user_meta( $user_meta, $user_id ) {

		$user_meta['wc_points_balance'] = WC_Points_Rewards_Manager::get_users_points( $user_id );

		update_user_meta( $user_id, 'wc_points_balance', $user_meta['wc_points_balance'] ); // keep it in usermeta too.

		return $user_meta;
	}

	/**
	 * Adds points field to contact fields list
	 *
	 * @access public
	 * @return array Settings
	 */
	public function set_contact_field_names( $meta_fields ) {

		$meta_fields['wc_points_balance'] = array(
			'label'  => 'Points Balance',
			'type'   => 'int',
			'group'  => 'woocommerce',
			'pseudo' => true,
		);

		return $meta_fields;
	}
}

new WPF_Woo_Points_Rewards();
