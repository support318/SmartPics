<?php

namespace Uncanny_Automator_Pro\Integrations\Loop;

use Uncanny_Automator_Pro\Loops_Process_Registry;
use Uncanny_Automator\Recipe\Action;

/**
 * End_Loop
 *
 * Handles the functionality to exit a loop within an Automator recipe.
 * This action can only be used within a loop context and signals the parent
 * loop to terminate its execution.
 *
 * @since 6.3
 *
 * @package Uncanny_Automator_Pro
 */
class End_Loop extends Action {

	/**
	 * The unique identifier for this action.
	 *
	 * @var string
	 */
	protected $action_code = 'END_LOOP';

	/**
	 * The meta identifier for this action.
	 *
	 * @var string
	 */
	protected $action_meta = 'END_LOOP_META';

	/**
	 * The integration identifier.
	 *
	 * @var string
	 */
	protected $integration = 'LOOP';

	/**
	 * Setup the action configuration.
	 *
	 * Initializes the action by setting up basic properties and configuration
	 * including integration details, user requirements, and display sentences.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( $this->integration );
		$this->set_action_code( $this->action_code );
		$this->set_action_meta( $this->action_meta );
		$this->set_requires_user( false );
		$this->set_is_pro( true );
		$this->set_sentence( esc_attr_x( 'End loop', 'Loop', 'uncanny-automator-pro' ) );
		$this->set_readable_sentence( esc_attr_x( 'End loop', 'Loop', 'uncanny-automator-pro' ) );
	}

	/**
	 * Process the action execution
	 *
	 * Handles the logic for exiting a loop. Validates that the action is being used
	 * within a loop context and triggers the appropriate WordPress action to signal
	 * loop termination.
	 *
	 * @param int|null $user_id     The ID of the user, if applicable
	 * @param array    $action_data The action configuration data
	 * @param int      $recipe_id   The ID of the parent recipe
	 * @param array    $args        Additional arguments including loop context
	 * @param array    $parsed      Parsed data
	 *
	 * @throws \Exception If the action is used outside of a loop context.
	 * @return bool Always returns true on successful execution
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		if ( ! isset( $args['loop'] ) ) {
			throw new \Exception( esc_html( esc_attr_x( 'This action can only be used in a loop.', 'Loop', 'uncanny-automator-pro' ) ) );
		}

		$process_id = $args['loop']['loop_item']['filter_id'] ?? '';

		if ( empty( $process_id ) ) {
			throw new \Exception( esc_html( esc_attr_x( 'Loop process not found', 'Loop', 'uncanny-automator-pro' ) ) );
		}

		$loops_registry = Loops_Process_Registry::get_instance();
		$loop_process   = $loops_registry->get_object( $process_id );

		// Stop the loop process.
		$loop_process->terminate();

		return true;
	}
}
