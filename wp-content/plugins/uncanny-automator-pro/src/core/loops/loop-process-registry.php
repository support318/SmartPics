<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator_Pro\Loops\Loop\Background_Process\Entity_Actions;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Entry_Model;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Item_Query;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Query;
use Uncanny_Automator_Pro\Loops\Loop_MQ;

/**
 * Singleton instance of Loop Process Registry
 *
 * @since 5.0
 */
final class Loops_Process_Registry {

	/**
	 * @var self
	 */
	private static $instance = null;

	/**
	 * @var Entity_Actions[]
	 */
	private static $transport_objects = array();

	/**
	 * Fills the object with process on initilize
	 *
	 * @since 6.1 - Added ability for users to disable the registry query.
	 *
	 * @return void
	 */
	protected function __construct() {

		if ( self::is_loop_registry_query_disabled() ) {
			return false;
		}

		$processes = ( new Loop_Entry_Query() )->find_all_in_progress_process();

		if ( false === $processes ) {
			return;
		}

		foreach ( (array) $processes as $process_id ) {
			if ( is_string( $process_id ) && ! $this->has_object( $process_id ) ) {
				// Registers the callback for loops as defined by WP Background Processing.
				self::$transport_objects[ $process_id ] = new Entity_Actions( $process_id );
			}
		}

	}

	/**
	 * Determines whether the registry query for loops is enabled or disabled.
	 *
	 * @return bool Default to false.
	 *
	 */
	public static function is_loop_registry_query_disabled() {
		return (bool) apply_filters( 'automator_pro_loop_registry_query_disabled', false );
	}

	/**
	 * Inserts new process into the transport objects.
	 *
	 * @param string $process_id
	 *
	 * @return Entity_Actions
	 */
	public function spawn_process( $process_id = '' ) {
		self::$transport_objects[ $process_id ] = new Entity_Actions( $process_id );
		return self::$transport_objects[ $process_id ];
	}

	/**
	 * Retrieves current running processes in-progress or queued.
	 *
	 * @return Entity_Actions[]
	 */
	public function get_processes() {
		return self::$transport_objects;
	}

	/**
	 * @param string $id
	 *
	 * @return bool True if object is found. Returns false, otherwise.
	 */
	public function has_object( $id ) {
		return isset( self::$transport_objects[ $id ] );
	}

	/**
	 * Get the process object.
	 *
	 * @param string $id
	 *
	 * @return false|Entity_Actions
	 */
	public function get_object( $id ) {
		if ( ! $this->has_object( $id ) ) {
			return false;
		}
		return self::$transport_objects[ $id ];
	}

	/**
	 * @param string $process_id The process ID.
	 *
	 * @return array{loop_id:string,recipe_id:string,recipe_log_id:string,run_number:string}|false
	 */
	public static function extract_process_id( $process_id ) {

		$extracted = explode( '_', $process_id );

		if ( is_array( $extracted ) && 6 !== count( $extracted ) ) {
			return false;
		}

		return array(
			'loop_id'       => $extracted[2],
			'recipe_id'     => $extracted[3],
			'recipe_log_id' => $extracted[4],
			'run_number'    => $extracted[5],
		);

	}

	/**
	 * Generates a process ID string from entry model.
	 *
	 * @return string The process ID token we can use.
	 */
	public static function generate_process_id( Loop_Entry_Model $loop_entry ) {

		return 'loop_process' .
		'_' . $loop_entry->get_loop_id() .
		'_' . $loop_entry->get_recipe_id() .
		'_' . $loop_entry->get_recipe_log_id() .
		'_' . $loop_entry->get_run_number();

	}

	/**
	 * Generates a process ID string from given args.
	 *
	 * @param int $loop_id
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $run_number
	 *
	 * @return string The process ID token we can use.
	 */
	public static function generate_process_id_manual( $loop_id, $recipe_id, $recipe_log_id, $run_number ) {

		return 'loop_process' .
		'_' . $loop_id .
		'_' . $recipe_id .
		'_' . $recipe_log_id .
		'_' . $run_number;

	}

	/**
	 * Deletes specific process from recipe run.
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $run_number
	 *
	 * @return void
	 */
	public function delete_process( $recipe_id, $recipe_log_id, $run_number ) {

		// Retrieve all loops from specific process.
		$loop_entry_query = new Loop_Entry_Query();

		$loop_entries = $loop_entry_query->find_by_recipe_process( $recipe_id, $recipe_log_id, $run_number );

		foreach ( $loop_entries as $loop_entry ) {

			// Delete from entry.
			$loop_entry_query->delete( $loop_entry );

			$proc_id = $loop_entry->get_process_id();

			// Delete the object from the registry.
			$process = $this->get_object( $proc_id );

			if ( false !== $process ) {
				$process->delete_all();
			}
			// Delete orphan process here?

			// Delete the record from the queue.
			$queue = new Loop_MQ();
			$queue->remove( $proc_id );

			// Delete the transients.
			delete_transient( $proc_id . '_transaction_transient' );

			// Delete the items.
			$loop_entries_items_query = new Loop_Entry_Item_Query();
			$loop_entries_items_query->delete_by_process_id( $proc_id );

		}

	}

	/**
	 * Manually deletes all orphaned processes.
	 *
	 * @param string $process_id
	 *
	 * @return void
	 */
	public function delete_orphaned_process( $process_id ) {
		// @todo Delete orpaned processes? Not sure how the process can have orphaned records, but possible.
	}

	/**
	 * Determine if there are any active process.
	 *
	 * @return bool When an active process is found. Otherwise, false.
	 */
	public function has_active_process() {

		foreach ( $this->get_processes() as $id => $process ) {

			$proc_obj = $this->get_object( $id );

			if ( false !== $proc_obj && $proc_obj->is_active() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Determines if there are any running processes.
	 *
	 * @return bool True if there are any running process. Otherwise, false.
	 */
	public function has_running_process() {

		foreach ( $this->get_processes() as $id => $process ) {

			$proc_obj = $this->get_object( $id );

			if ( false !== $proc_obj && $proc_obj->is_processing() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Prevent cloning of object.
	 */
	protected function __clone() { }

	/**
	 * Prevent serialization of the object.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}

	/**
	 * Retrieve the instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

}
