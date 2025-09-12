<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model\Query;

use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Entry_Model;
use Uncanny_Automator_Pro\Loops_Process_Registry;

/**
 * Loop_Entry_Query
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model\Query
 */
class Loop_Entry_Query {

	/**
	 * @var \wpdb
	 */
	private $db = null;

	/**
	 * @var string $table
	 */
	private $table = '';

	/**
	 * @var string[] $table_structure_format
	 */
	private $table_structure_format = array(
		'%d', // loop_id
		'%s', // loop_type
		'%s', // process_id
		'%s', // status
		'%s', // message
		'%s', // user_ids
		'%d', // num_users
		'%s', // flow(serialized)
		'%s', // meta(serialized)
		'%s', // process date started
		'%s', // process date ended
		'%d', // recipe id
		'%d', // recipe id
		'%d', // recipe run number
		'%s', // date started
	);

	/**
	 * @return void
	 */
	public function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = $this->db->prefix . 'uap_loop_entries';
	}

	/**
	 * Quick way to mark a process status
	 *
	 * @param string $status
	 * @param string $process_id
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function mark_process_as( $status, $process_id ) {

		return $this->db->update(
			$this->db->prefix . 'uap_loop_entries',
			array(
				'status' => $status,
			),
			array(
				'process_id' => $process_id,
			)
		);

	}

	/**
	 * Deletes an entry
	 *
	 * @param Loop_Entry_Model $entry
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( Loop_Entry_Model $entry ) {
		return $this->db->delete(
			$this->table,
			array(
				'ID' => $entry->get_id(),
			)
		);
	}

	/**
	 * Fetches all processes that are in progress and have ended 15 minutes ago to gracefully wait for the processes that havent fully shutdown yet.
	 *
	 * @since 5.2 Fetch all processes that are in progress (process_date_ended IS NULL) and
	 *            process that has ended (cron interval) minutes ago (process_date_ended > NOW() - INTERVAL %d MINUTE)
	 *
	 * @since 5.0 Fetches all process unconditionally - The safest but not the most effecient solution.
	 *
	 * @see Notes about "Dispatching process" <https://github.com/deliciousbrains/wp-background-processing>
	 *
	 * @return mixed[]|false The list of process IDs (string). Otherwise, returns false if no record is found.
	 */
	public function find_all_in_progress_process() {

		// Add 10 minutes allowance since CRON is not realtime and can be delayed.
		$interval_in_minutes = 15;

		$record = (array) $this->db->get_results(
			$this->db->prepare(
				"SELECT DISTINCT ID, process_id, process_date_ended 
					FROM {$this->db->prefix}uap_loop_entries
					WHERE
						process_date_ended IS NULL 
						OR 
						process_date_ended > NOW() - INTERVAL %d MINUTE 
					ORDER BY ID DESC",
				$interval_in_minutes
			),
			ARRAY_A
		);

		if ( empty( $record ) ) {
			return false;
		}

		return array_column( $record, 'process_id' );

	}

	/**
	 * Retrieve a single loop process by process id.
	 *
	 * @param string $process_id
	 *
	 * @return Loop_Entry_Model|false Returns false if no record is found. Otherwise, returns the Loop Model.
	 */
	public function find_entry_by_process_id( $process_id ) {

		$record = $this->sql_find_entry_by_process_id( $process_id );

		if ( empty( $record ) ) {
			return false;
		}

		$loop_entry = new Loop_Entry_Model();

		return $loop_entry->hydate_from_array( $record );

	}

	/**
	 *
	 * @param string $process_id
	 *
	 * @return null|array{
	 *  ID:int,
	 *  loop_id:int,
	 *  loop_type: string,
	 *  entity_ids:string,
	 *  process_id:string,
	 *  flow:string,
	 *  status:string,
	 *  message:string,
	 *  process_date_started:string,
	 *  process_date_ended:string,
	 *  recipe_id:int,
	 *  recipe_log_id:int,
	 *  run_number:int,
	 *  meta: mixed[]
	 * }
	 */
	protected function sql_find_entry_by_process_id( $process_id ) {

		$entry = $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->db->prefix}uap_loop_entries WHERE process_id = %s",
				$process_id
			),
			ARRAY_A
		);

		// Return null if falsy.
		if ( empty( $entry ) ) {
			return null;
		}

		return $entry; // @phpstan-ignore-line (No control over core WP, statement understood it will return array containing the key values).

	}

	/**
	 * @param int $loop_id
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $run_number
	 *
	 * @return Loop_Entry_Model|false Returns false if no record is found. Otherwise, returns the Loop Model.
	 */
	public function find_by_process( $loop_id, $recipe_id, $recipe_log_id, $run_number ) {

		$record = (array) $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->db->prefix}uap_loop_entries
                    WHERE loop_id = %d
					AND recipe_id = %d
					AND recipe_log_id = %d
					AND run_number = %d
					",
				$loop_id,
				$recipe_id,
				$recipe_log_id,
				$run_number
			),
			ARRAY_A
		);

		if ( empty( $record ) ) {
			return false;
		}

		$loop_entry = new Loop_Entry_Model();

		return $loop_entry->hydate_from_array( $record ); // @phpstan-ignore-line (value from core-wp function).

	}

	/**
	 * Updates the loop entry based on the received model.
	 *
	 * @param Loop_Entry_Model $loop_entry
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function update( Loop_Entry_Model $loop_entry ) {

		$params = array(
			'loop_id'              => $loop_entry->get_loop_id(),
			'loop_type'            => $loop_entry->get_loop_type(),
			'process_id'           => $loop_entry->get_process_id(),
			'status'               => $loop_entry->get_status(),
			'message'              => $loop_entry->get_error_message(),
			'entity_ids'           => $loop_entry->get_user_ids(),
			'num_entities'         => $loop_entry->num_entities(),
			'flow'                 => $loop_entry->get_flow(),
			'meta'                 => maybe_serialize( $loop_entry->get_meta() ),
			'process_date_started' => $loop_entry->get_process_date_started(),
			'process_date_ended'   => $loop_entry->get_process_date_ended(),
			'recipe_id'            => $loop_entry->get_recipe_id(),
			'recipe_log_id'        => $loop_entry->get_recipe_log_id(),
			'run_number'           => $loop_entry->get_run_number(),
			'date_updated'         => current_time( 'mysql' ),
		);

		return $this->db->update(
			$this->table,
			$params,
			array(
				'ID' => $loop_entry->get_id(),
			),
			$this->table_structure_format,
			array(
				'%d',
			)
		);

	}

	/**
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $recipe_run_number
	 *
	 * @return Loop_Entry_Model[]
	 */
	public function find_by_recipe_process( $recipe_id, $recipe_log_id, $recipe_run_number ) {

		$results = (array) $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->db->prefix}uap_loop_entries
                    WHERE recipe_id = %d
					AND recipe_log_id = %d
					AND run_number = %d
					",
				$recipe_id,
				$recipe_log_id,
				$recipe_run_number
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return array();
		}

		$entries = array();

		foreach ( $results as $result ) {
			$loop_entry = new Loop_Entry_Model();
			$entries[]  = $loop_entry->hydate_from_array( $result ); // @phpstan-ignore-line (value from core-wp function).
		}

		return $entries;

	}

	/**
	 * @param Loop_Entry_Model $loop_entry
	 *
	 * @throws Loops_Exception
	 *
	 * @return int The last inserted ID.
	 */
	public function save( Loop_Entry_Model $loop_entry ) {

		$process_id = Loops_Process_Registry::generate_process_id( $loop_entry );

		$params = array(
			'loop_id'              => $loop_entry->get_loop_id(),
			'loop_type'            => $loop_entry->get_loop_type(),
			'process_id'           => $process_id,
			'status'               => $loop_entry->get_status(),
			'message'              => $loop_entry->get_error_message(),
			'entity_ids'           => $loop_entry->get_user_ids(),
			'num_entities'         => $loop_entry->num_entities(),
			'flow'                 => $loop_entry->get_flow(),
			'meta'                 => maybe_serialize( $loop_entry->get_meta() ),
			'process_date_started' => $loop_entry->get_process_date_started(),
			'process_date_ended'   => $loop_entry->get_process_date_ended(),
			'recipe_id'            => $loop_entry->get_recipe_id(),
			'recipe_log_id'        => $loop_entry->get_recipe_log_id(),
			'run_number'           => $loop_entry->get_run_number(),
			'date_added'           => current_time( 'mysql' ),
		);

		$inserted = $this->db->insert(
			$this->table,
			$params,
			$this->table_structure_format
		);

		if ( false === $inserted ) {
			throw new Loops_Exception( 'An error has occured while saving the record. ' . $this->db->last_error );
		}

		return $this->db->insert_id;

	}

}
