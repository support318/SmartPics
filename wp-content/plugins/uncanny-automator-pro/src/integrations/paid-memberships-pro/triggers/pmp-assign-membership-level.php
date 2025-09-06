<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class PMP_ASSIGN_MEMBERSHIP_LEVEL
 *
 * @package Uncanny_Automator_Pro
 */
class PMP_ASSIGN_MEMBERSHIP_LEVEL {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = '';

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = '';

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

		$this->set_integration( 'PMP' );
		$this->set_trigger_code( 'ASSIGN_LEVEL_CODE' );
		$this->set_trigger_meta( 'ASSIGN_LEVEL_META' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_trigger_code(), 'integration/paid-memberships-pro/' ) );
		$this->set_sentence(
			sprintf(
			/* Translators: Trigger sentence - Paid Memberships Pro */
				esc_attr__( 'An admin assigns {{a membership level:%1$s}} to a user', 'uncanny-automator-pro' ),
				$this->get_trigger_meta()
			)
		);

		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'An admin assigns {{a membership level}} to a user', 'uncanny-automator-pro' ) );

		// Which do_action() fires this trigger.
		$this->add_action( 'pmpro_after_change_membership_level' );
		$this->set_action_args_count( 3 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();

	}


	/**
	 * @return array[]
	 */
	public function load_options() {

		$options            = Automator()->helpers->recipe->paid_memberships_pro->options->all_memberships( __( 'Membership', 'uncanny-automator' ), $this->get_trigger_meta() );
		$options['options'] = array( '-1' => __( 'Any membership', 'uncanny-automator' ) ) + $options['options'];

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$options,
				),
			)
		);
	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		if ( ! is_admin() && ! current_user_can( 'manage_plugins' ) ) {
			return false;
		}

		$args = array_shift( $args );

		if ( ! empty( $args[2] ) ) {
			return false;
		}

		// Set member user ID as current recipe user.
		$member_user_id = $args[1];
		// Add filter to override setting member as user
		if ( apply_filters( 'automator_pro_pmp_admin_assigns_level_set_member_as_user', true, $member_user_id ) ) {
			$this->set_user_id( $member_user_id );
		}

		return true;
	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}


	/**
	 * Validate if trigger matches the condition.
	 *
	 * @param $args
	 *
	 * @return array
	 */
	public function validate_conditions( ...$args ) {
		$args                      = array_shift( $args );
		$this->actual_where_values = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $args[0] ) )
					->format( array( 'intval' ) )
					->get();
	}

}
