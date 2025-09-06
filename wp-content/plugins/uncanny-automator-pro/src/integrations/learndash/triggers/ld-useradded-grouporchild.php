<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class LD_USERADDED_GROUPORCHILD
 *
 * @package Uncanny_Automator_Pro
 */
class LD_USERADDED_GROUPORCHILD {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! Learndash_Pro_Helpers::is_group_hierarchy_enabled() ) {
			return;
		}

		//$this->setup_trigger();
		$this->set_helper( new Learndash_Pro_Helpers() );
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'LD' );
		$this->set_trigger_code( 'LD_GROUP_OR_ITS_CHILD' );
		$this->set_trigger_meta( 'LDGROUP' );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_trigger_code(), 'integration/learndash/' ) );
		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf( esc_html__( 'A user is added to {{a group:%1$s}} or its children', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'A user is added to {{a group}} or its children', 'uncanny-automator-pro' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( 'ld_added_group_access' );
		$this->set_action_args_count( 2 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();

	}

	/**
	 * callback for loading options
	 *
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					$this->get_helper()->all_ld_groups_with_hierarchy(
						null,
						$this->get_trigger_meta(),
						false,
						false,
						true,
						array(
							$this->get_trigger_meta() . '_LEADERS' => __( 'Group', 'uncanny-automator-pro' ),
						)
					),
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
		list( $user_id, $group_id ) = array_shift( $args );
		$has_child_groups = get_children( array( 'post_parent' => $group_id ) );
		$is_child_group   = get_post_parent( $group_id );

		if ( empty( $has_child_groups ) && null === $is_child_group ) {
			return false;
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
	 * Check contact status against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $user_id, $group_id ) = $args[0];

		return $this->find_all( $this->trigger_recipes() )
		            ->where( array( $this->get_trigger_meta() ) )
		            ->match( array( absint( $group_id ) ) )
		            ->format( array( 'intval' ) )
		            ->get();
	}

}
