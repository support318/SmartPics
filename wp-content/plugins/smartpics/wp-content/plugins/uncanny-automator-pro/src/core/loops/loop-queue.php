<?php
namespace Uncanny_Automator_Pro\Loops;

/**
 * A simple database driven message queue for
 * proccesing the loop execution in-order on FIFO bases.
 *
 * @class Automator_Loop_MQ
 */
class Loop_MQ {

	/**
	 * @var \wpdb
	 */
	protected $db;

	/**
	 * @var string $table
	 */
	protected $table = '';

	public function __construct() {

		global $wpdb;

		$this->db    = $wpdb;
		$this->table = $wpdb->prefix . 'uap_queue';

	}

	/**
	 * @param string $process_id The process ID.
	 * @param string $state
	 *
	 * @return false|int|-1
	 */
	public function add( $process_id = null, $state = '' ) {

		return $this->db->insert(
			$this->table,
			array(
				'process_id' => $process_id,
				'state'      => $state,
			)
		);

	}

	/**
	 * @param string $process_id
	 *
	 * @return false|array{ID: int, process_id: string, state: string} The process. False if empty result.
	 */
	public function get( $process_id ) {

		$result = (array) $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->table} WHERE process_id = %s",
				$process_id
			),
			ARRAY_A
		);

		if ( empty( $result ) ) {
			return false;
		}

		return $result; // @phpstan-ignore-line (core-wp built-in)

	}

	/**
	 * Remove a process from queue.
	 *
	 * @param string $process_id
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function remove( $process_id ) {

		// Throttle the execution of batch processing, we dont want to bombard the server.
		// The process ID was just inserted in the DB, allow 1 second for MySQL to process it.
		sleep( apply_filters( 'automator_queue_next_delay', 1, $process_id ) );

		$queue_item = $this->get( $process_id );

		if ( false === $queue_item ) {
			return false;
		}

		$deleted = $this->db->delete(
			$this->table,
			array(
				'ID' => $queue_item['ID'],
			),
			array(
				'%d',
			)
		);

		return $deleted;

	}

	/**
	 * Determines if the process exists from the queue.
	 *
	 * @param string $process_id
	 *
	 * @return bool
	 */
	public function exists( $process_id ) {

		$process = $this->db->get_var(
			$this->db->prepare(
				"SELECT process_id FROM {$this->table} WHERE process_id = %s",
				$process_id
			)
		);

		return ! empty( $process );

	}

	/**
	 * Determines if there are any active queues.
	 *
	 * @return bool
	 */
	public function has_active() {

		$processes = $this->db->get_results( "SELECT *, NOW() FROM {$this->table}" ); // Prevents cache which is compatible with MySQL 5.6 too.

		return ! empty( $processes );

	}

	/**
	 * Get the next item to process.
	 *
	 * This queue is based on FIFO, next process means the first item in the table.
	 *
	 * @return false|array{'process_id': string} Returns false if process id cannot be found. Otherwise an array.
	 */
	public function get_next_item() {

		$processes = (array) $this->db->get_results( "SELECT process_id FROM {$this->table} ORDER BY ID ASC", ARRAY_A );

		if ( empty( $processes ) ) {
			return false;
		}

		return $processes[0]; // @phpstan-ignore-line (core-wp built-in)

	}

}
