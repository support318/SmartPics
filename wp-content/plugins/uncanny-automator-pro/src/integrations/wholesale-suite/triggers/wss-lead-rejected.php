<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class WSS_LEAD_APPROVED
 *
 * @package Uncanny_Automator_Pro
 */
class WSS_LEAD_REJECTED {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! function_exists( 'wwlc_check_plugin_dependencies' ) ) {
			return;
		}
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'WHOLESALESUITE' );
		$this->set_trigger_code( 'WSS_LEAD_REJECTED' );
		$this->set_trigger_meta( 'WSS_LEAD' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/wholesale-suite/' ) );
		$this->set_sentence(
		/* Translators: Trigger sentence */
			esc_html__( 'A wholesale lead is rejected', 'uncanny-automator-pro' )
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'A wholesale lead is rejected', 'uncanny-automator-pro' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( 'wwlc_action_after_reject_user' );
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
		list( $user ) = array_shift( $args );

		if ( ! is_object( $user ) ) {
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

}
