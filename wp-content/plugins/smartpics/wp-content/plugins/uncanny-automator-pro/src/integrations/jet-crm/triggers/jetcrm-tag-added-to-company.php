<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Jet_Crm_Helpers;
use Uncanny_Automator\Recipe;

/**
 * Class JETCRM_TAG_ADDED_TO_COMPANY
 *
 * @package Uncanny_Automator_Pro
 */
class JETCRM_TAG_ADDED_TO_COMPANY {

	use Recipe\Triggers;

	protected $helpers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'Uncanny_Automator\Jet_Crm_Helpers' ) ) {
			return;
		}
		$this->helpers = new Jet_Crm_Helpers();
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'JETCRM' );
		$this->set_trigger_code( 'JETCRM_TAG_TO_COMPANY' );
		$this->set_trigger_meta( 'JETCRM_TAGS' );
		$this->set_is_login_required( false );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/jestpack-crm/' ) );
		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf( esc_attr__( '{{A tag:%1$s}} is added to a company', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( '{{A tag}} is added to a company', 'uncanny-automator-pro' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( 'zbs_tag_added_to_objid' );
		$this->set_action_args_count( 3 );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();

	}


	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_trigger_meta() => array(
						$this->helpers->get_all_jetpack_tags(
							$this->get_trigger_meta(),
							true,
							array( $this->get_trigger_meta() => __( 'Tag name', 'uncanny-automator-pro' ) ),
							ZBS_TYPE_COMPANY,
							false
						),
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
		list( $tag_id, $obj_type, $obj_id ) = array_shift( $args );

		if ( ! isset( $tag_id ) && ZBS_TYPE_COMPANY !== $obj_type ) {
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
	 * Check email subject against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $tag_id, $obj_type, $obj_id ) = $args[0];
		$this->actual_where_values          = array(); // Fix for when not using the latest Trigger_Recipe_Filters version. Newer integration can omit this line.

		// Find tag ID
		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta() ) )
					->match( array( $tag_id ) )
					->format( array( 'intval' ) )
					->get();
	}

	/**
	 * @param ...$args
	 *
	 * @return bool
	 */
	public function do_continue_anon_trigger( ...$args ) {
		return true;
	}

}
