<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Background_Process;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Query;
use Uncanny_Automator_Pro\Loops\Loop\Background_Process\Entity_Actions;
use Uncanny_Automator_Pro\Loops\Loop_MQ;
use Uncanny_Automator_Pro\Loops_Process_Registry;

/**
 * Schedule a recurring event that checks the health of our loops.
 *
 * Checks if the current item in the queue is active or not.
 *
 * Removes the current queue item if its not active and dispatch the next item.
 */
class Queue_Health_Check {

	/**
	 * @var string $hook_identifier
	 */
	protected $hook_identifier = 'automator_pro_loops_run_queue';

	/**
	 * @var string $plugin_file
	 */
	private $plugin_file = '';

	/**
	 * @return void
	 */
	public function __construct() {

		$this->plugin_file = UAPro_ABSPATH . 'uncanny-automator-pro.php';

	}

	/**
	 * Initializes the hooks.
	 *
	 * @return void
	 */
	public function init_hook() {

		add_filter( 'cron_schedules', array( $this, 'schedule_interval' ) ); //phpcs:ignore WordPress.WP.CronInterval.CronSchedulesInterval

		/**
		 * This is our cron hook. The action hook tag must match with this class hook_identifier.
		 *
		 * @see $this->hook_identifier.
		 */
		add_action( 'automator_pro_loops_run_queue', array( $this, 'queue_runner' ) );

		register_deactivation_hook( $this->plugin_file, array( $this, 'deactivate' ) );

		if ( ! wp_next_scheduled( $this->hook_identifier ) ) {

			wp_schedule_event( time(), $this->get_schedule(), $this->hook_identifier );

		}

	}

	/**
	 * Callback function to "cron_schedules" filter.
	 *
	 * @param mixed[] $schedules
	 *
	 * @return mixed[]
	 */
	public function schedule_interval( $schedules ) {

		$schedules[ $this->get_schedule() ] = array(
			'interval' => 120,
			'display'  => esc_html__( 'Every 2 minutes' ),
		);

		return $schedules;

	}

	/**
	 * Retrieves the schedule.
	 *
	 * @return string
	 */
	private function get_schedule() {

		return $this->hook_identifier . '_schedule';

	}

	/**
	 * Deactivation hook
	 *
	 * @return void
	 */
	public function deactivate() {

		wp_clear_scheduled_hook( $this->hook_identifier );

	}


	/**
	 * Callback function to our hook identifier.
	 *
	 * This method runs every 5 minutes making sure the queue is running.
	 *
	 * @return void
	 */
	public function queue_runner() {

		global $wpdb;

		$registry      = Loops_Process_Registry::get_instance();
		$queue_handler = new Loop_MQ();

		$result = (array) $wpdb->get_results( "SELECT process_id FROM {$wpdb->prefix}uap_queue ORDER BY ID ASC", ARRAY_A );

		if ( empty( $result ) ) {
			return;
		}

		$queue = array_column( $result, 'process_id' );

		$current = $queue[0];

		$process = $registry->get_object( $current );

		// Fire the next process if the current process is not active.
		// It means that the next item in queue was not dispatched for unknown reason.
		$process_is_valid = $process instanceof Entity_Actions;

		$batch_is_idle = $process_is_valid && $this->is_idle( $process );

		// Handle queues that are stucked in progress, and queues that were cancelled.
		if ( false === $process || $batch_is_idle ) {
			$this->run_next_queue( $queue_handler, $registry, $current );
			return;
		}

		// Attempt recovery for a valid process if its valid and is active but is not processing.
		if ( $process_is_valid && $process->is_active() && ! $process->is_processing() ) {
			// Only attempt recovery for process that has no schedules.
			$has_schedule = wp_next_scheduled( $process->get_cron_hook_identifier() );
			if ( ! $has_schedule ) {
				// Manually dispatch.
				$this->run_next_queue( $queue_handler, $registry, $current );
			}
		}
	}

	/**
	 * @param Loop_MQ $queue_handler
	 * @param Loops_Process_Registry $registry
	 * @param string $current_process_id The current process_id.
	 *
	 * @return void
	 */
	public function run_next_queue( $queue_handler, $registry, $current_process_id ) {

		$loop_entry_query = new Loop_Entry_Query();

		// Remove the current process queue.
		$queue_handler->remove( $current_process_id );

		// The cancelled state becomes identical to completed after some time.
		if ( false === $this->is_process_marked_completed( $current_process_id ) ) {
			// Make sure we're only marking the cancelled for non completed process.
			$loop_entry_query->mark_process_as(
				Automator_Status::get_class_name( Automator_Status::CANCELLED ),
				$current_process_id
			);
		}

		// Retrieve the next loop process.
		$next_loop = $queue_handler->get_next_item();

		// Bail if nothing left to process.
		if ( false === $next_loop ) {
			return;
		}

		$next_process = $registry->get_object( $next_loop['process_id'] );

		if ( false !== $next_process ) {
			// Dispatch the next process.
			$next_process->dispatch();
			// Mark the next process as 'in-progress'.
			$loop_entry_query->mark_process_as(
				Automator_Status::get_class_name( Automator_Status::IN_PROGRESS ),
				$next_loop['process_id']
			);
		}

	}

	/**
	 * @param Entity_Actions $process
	 *
	 * @return bool True if the process is neither process or is active. Otherwise, false.
	 */
	protected function is_idle( Entity_Actions $process ) {

		return ! $process->is_active() && ! $process->is_processing();

	}

	/**
	 * Determines whether the process was marked as completed or not.
	 *
	 * @param string $process_id
	 *
	 * @return boolean
	 */
	protected function is_process_marked_completed( $process_id ) {

		$process = ( new Loop_Entry_Query() )->find_entry_by_process_id( $process_id );

		if ( false === $process ) {
			return false;
		}

		return Automator_Status::get_class_name( Automator_Status::COMPLETED ) === $process->get_status();

	}

}
