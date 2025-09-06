<?php
namespace Uncanny_Automator_Pro\Integrations\Run_Now;

use Uncanny_Automator\Automator_Status;

/**
 * CLass RECIPE_MANUAL_TRIGGER
 *
 * @package Uncanny_Automator_Pro
 */
class RECIPE_MANUAL_TRIGGER extends \Uncanny_Automator\Recipe\Trigger {

	protected $helpers;

	/**
	 * @return void
	 */
	protected function setup_trigger() {

		$this->helpers = array_shift( $this->dependencies );

		$this->set_integration( 'Run_Now' );
		$this->set_trigger_code( 'RECIPE_MANUAL_TRIGGER' );
		$this->set_trigger_meta( 'RECIPE_MANUAL_TRIGGER_META' );
		$this->set_sentence( sprintf( esc_attr_x( 'Trigger recipe manually', 'Run now', 'uncanny-automator' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Trigger recipe manually', 'Run now', 'uncanny-automator' ) );
		$this->add_action( 'automator_pro_run_now_recipe', 10, 1 );

	}

	/**
	 * @param mixed[] $trigger
	 * @param mixed[] $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		$recipe_id = 0;

		if ( isset( $trigger['post_parent'] ) ) {
			$recipe_id = absint( $trigger['post_parent'] );
		}

		$status = Run_Now_Integration::fetch_recipe_status( $recipe_id );

		return Automator_Status::IN_PROGRESS !== $status;

	}


}
