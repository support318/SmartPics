<?php
namespace Uncanny_Automator_Pro\Loops;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Query;
use Uncanny_Automator_Pro\Loops_Process_Registry;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;

/**
 * Class Process_Controller
 *
 * @since 5.0
 */
class Process_Controller {

	/**
	 * Handles various commands
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Rest_Response|WP_Error|mixed[]
	 */
	public function handle( $request ) {

		// The action to execute.
		$action = $request->get_param( 'action' );

		// The process ID.
		$process_id = strval( $request->get_param( 'process_id' ) ); // @phpstan-ignore-line

		$transports = Loops_Process_Registry::get_instance();

		// Retrieve the loop process.
		$loop_process = $transports->get_object( $process_id );

		if ( empty( $loop_process ) ) {
			return new \WP_Error(
				'loop_process_not_found',
				'Cannot find loop process with process ID of: ' . $process_id,
				array(
					'received' => time(),
				)
			);
		}

		$command = '';

		switch ( $action ) {
			case 'pause':
				$command = 'PROCESS::PAUSE';
				$loop_process->pause();
				break;
			case 'resume':
				$command = 'PROCESS::RESUME';
				$loop_process->resume();
				break;
			case 'cancel':
				$command = 'PROCESS::CANCEL';

				$loop_process->cancel();

				// Make sure its cancelled.
				if ( $loop_process->is_cancelled() ) {

					$loop_entry_query = new Loop_Entry_Query();

					$loop_entry = $loop_entry_query->find_entry_by_process_id( $process_id );

					if ( false !== $loop_entry ) {
						// Mark the current process as cancelled.
						$loop_entry->set_status( Automator_Status::get_class_name( Automator_Status::CANCELLED ) );
						$loop_entry->set_process_date_ended( current_time( 'mysql' ) );
						$loop_entry_query->update( $loop_entry );
						// Update the recipe status.
						Automator()->db->recipe->mark_complete(
							$loop_entry->get_recipe_log_id(),
							Automator_Status::COMPLETED
						);
					}
				}

				break;
			case 'cancel_scheduled_actions':
				// Cancel all scheduled actions regardless.
				if ( class_exists( '\ActionScheduler' ) ) {
					\ActionScheduler::store()->cancel_actions_by_group( $process_id );
				}
				break;
			default:
				$command = 'PROCESS::LISTEN';
				break;
		}

		return array(
			'success'    => true,
			'state'      => array(
				'is_processing' => $loop_process->is_processing(),
				'is_active'     => $loop_process->is_active(),
				'is_paused'     => $loop_process->is_paused(),
				'is_cancelled'  => $loop_process->is_cancelled(),
				'command'       => 'Issued ' . $command . ' at ' . time(),
			),
			'properties' => array(
				'process_id' => $process_id,
			),
		);

	}

}
