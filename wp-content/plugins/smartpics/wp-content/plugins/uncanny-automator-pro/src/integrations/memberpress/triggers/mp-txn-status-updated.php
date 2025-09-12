<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class MP_TXN_STATUS_UPDATED
 *
 * @package Uncanny_Automator_Pro
 */
class MP_TXN_STATUS_UPDATED {
	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {

		$this->setup_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'MP' );
		$this->set_trigger_code( 'TXN_STATUS_CODE' );
		$this->set_trigger_meta( 'TXN_STATUS_META' );
		$this->set_is_login_required( false );
		$this->set_is_pro( true );
		/* Translators: Trigger sentence */
		$this->set_sentence( sprintf( esc_html__( "A user's transaction for {{a membership:%1\$s}} is set to {{a status:%2\$s}}", 'uncanny-automator-pro' ), 'MPPRODUCT', $this->get_trigger_meta() ) );
		/* Translators: Trigger sentence */
		$this->set_readable_sentence( esc_html__( "A user's transaction for {{a membership}} is set to {{a status}}", 'uncanny-automator-pro' ) ); // Non-active state sentence to show
		$this->add_action( 'mepr-txn-transition-status' );
		$this->set_action_args_count( 3 );
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
					Automator()->helpers->recipe->memberpress->options->pro->all_memberpress_products(
						__( 'Membership', 'uncanny-automator-pro' ),
						'MPPRODUCT',
						array(
							'uo_include_any' => true,
							'uo_any_label'   => esc_attr__( 'Any membership', 'uncanny-automator' ),
						)
					),
					Automator()->helpers->recipe->memberpress->options->pro->get_all_txn_statuses( null, $this->get_trigger_meta(), array( 'uo_include_any' => true ) ),
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
		$is_valid = false;

		list( $old_status, $new_status, $txn ) = array_shift( $args );

		if ( isset( $old_status ) && isset( $new_status ) && is_object( $txn ) && $old_status !== $new_status ) {
			$is_valid = true;
		}

		$this->set_user_id( $txn->user_id );
		$this->set_is_signed_in( true );

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
	 * @param ...$args
	 *
	 * @return array
	 */
	public function validate_conditions( ...$args ) {
		list( $old_status, $new_status, $txn ) = $args[0];
		$membership                            = $txn->product_id;
		$this->actual_where_values             = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta(), 'MPPRODUCT' ) )
					->match( array( $new_status, $membership ) )
					->format( array( 'trim', 'intval' ) )
					->get();
	}
}
