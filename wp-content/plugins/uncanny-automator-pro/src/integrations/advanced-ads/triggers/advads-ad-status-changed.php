<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Advanced_Ads_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class ADVADS_AD_STATUS_CHANGED
 *
 * @package Uncanny_Automator_Pro
 */
class ADVADS_AD_STATUS_CHANGED {

	use Recipe\Triggers;

	public $helpers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'Uncanny_Automator\Advanced_Ads_Helpers' ) ) {
			return;
		}
		$this->helpers = new Advanced_Ads_Helpers();
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'ADVADS' );
		$this->set_trigger_code( 'AD_STATUS_CHANGED_CODE' );
		$this->set_trigger_meta( 'ALL_ADS_META' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_action_args_count( 3 );

		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( "{{An ad's:%1\$s}} status changes from {{a specific status:%3\$s}} to {{a specific status:%2\$s}}", 'uncanny-automator' ), $this->get_trigger_meta(), 'AD_STATUS', 'AD_OLD_STATUS' ) );

		/* Translators: Trigger sentence */
		$this->set_readable_sentence( esc_html__( "{{An ad's}} status changes from {{a specific status}} to {{a specific status}}", 'uncanny-automator' ) ); // Non-active state sentence to show

		$this->set_action_hook( 'transition_post_status' );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();
	}

	/**
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->helpers->get_all_ads( $this->get_trigger_meta(), true ),
					$this->helpers->ad_statuses( 'AD_STATUS', false, esc_attr__( 'New status', 'uncanny-automator' ) ),
					$this->helpers->ad_statuses(
						'AD_OLD_STATUS',
						false,
						esc_attr__( 'Current status', 'uncanny-automator' ),
						array( 'AD_OLD_STATUS' => __( 'Ad old status', 'uncanny-automator-pro' ) )
					),
				),
			)
		);

	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function validate_trigger( ...$args ) {
		$is_valid                                   = false;
		list( $ad_new_status, $ad_old_status, $ad ) = $args[0];

		if ( isset( $ad_new_status ) && isset( $ad_old_status ) && isset( $ad ) ) {
			$is_valid = true;
		}

		return $is_valid;

	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Check email subject against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $ad_new_status, $ad_old_status, $ad ) = $args[0];
		$this->actual_where_values                  = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		// Find ad ID and status
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta(), 'AD_STATUS', 'AD_OLD_STATUS' ) )
					->match( array( $ad->ID, $ad_new_status, $ad_old_status ) )
					->format( array( 'intval', 'trim' ) )
					->get();
	}

}
