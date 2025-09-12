<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WPAI_IMPORT_FAIL
 *
 * @package Uncanny_Automator_Pro
 */
class WPAI_IMPORT_FAIL {

	use Recipe\Triggers;

	protected $helpers;

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
		$this->set_integration( 'WPAI' );
		$this->set_trigger_code( 'WPAI_IMPORT_FAIL' );
		$this->set_trigger_meta( 'WPAI_IMPORT_XML' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_login_required( false );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/wp-all-import/' ) );
		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf( esc_html__( 'An import fails', 'uncanny-automator-pro' ), $this->get_trigger_meta() )
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'An import fails', 'uncanny-automator-pro' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( 'pmxi_import_failed' );
		$this->register_trigger();

	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		list( $import_id ) = array_shift( $args );

		if ( empty( $import_id ) ) {
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
		$this->set_conditional_trigger( false );
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
