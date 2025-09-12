<?php

namespace Uncanny_Automator_Pro\Loops\Loop\Model\Query;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Entry_Item_Model;
use Uncanny_Automator_Pro\Loops_Process_Registry;

class Loop_Entry_Item_Query {

	/**
	 * @var \wpdb $db
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
		'%d', // user_id
		'%d', // entity_id
		'%d', // action_id
		'%s', // filter_id
		'%s', // status
		'%s', // error_message
		'%d', // recipe_id
		'%d', // recipe_log_id
		'%d', // recipe_run_number
		'%s', // action_data
		'%s', // action_tokens
		'%s', // date_added
		'%s', // date_updated
	);

	/**
	 * - Sets the prop $db to global $wpdb.
	 * - Sets the prop $table
	 */
	public function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = $this->db->prefix . 'uap_loop_entries_items';
	}

	/**
	 * Find a specific process by id.
	 *
	 * @param int $id
	 *
	 * @return Loop_Entry_Item_Model|false Returns false if no record is found. Otherwise, returns the Loop Model.
	 */
	public function find_by_id( $id = 0 ) {

		$record = $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->db->prefix}uap_loop_entries_items WHERE ID = %d",
				$id
			),
			ARRAY_A
		);

		if ( empty( $record ) ) {
			return false;
		}

		return ( new Loop_Entry_Item_Model() )->hydrate_from_array( (array) $record );

	}

	/**
	 * Determines whether the specific process has item that is marked as in-progress.
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $recipe_run_number
	 *
	 * @return bool
	 */
	public function has_in_progress( $recipe_id, $recipe_log_id, $recipe_run_number ) {

		$items = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->db->prefix}uap_loop_entries_items
					WHERE recipe_id = %d
					AND recipe_log_id = %d
					AND recipe_run_number = %d
					AND status = %s",
				$recipe_id,
				$recipe_log_id,
				$recipe_run_number,
				Automator_Status::get_class_name( Automator_Status::IN_PROGRESS )
			),
			ARRAY_A
		);

		return ! empty( $items );

	}

	/**
	 * Find record by action process.
	 *
	 * @param int $user_id
	 * @param int $action_id
	 * @param int $loop_id
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $recipe_run_number
	 *
	 * @return Loop_Entry_Item_Model|false Returns false if no record is found. Otherwise, returns the Loop Model.
	 */
	public function find_by_action_process( $user_id, $action_id, $loop_id, $recipe_id, $recipe_log_id, $recipe_run_number ) {

		$process_id = Loops_Process_Registry::generate_process_id_manual( $loop_id, $recipe_id, $recipe_log_id, $recipe_run_number );

		$record = $this->db->get_row(
			$this->db->prepare(
				"SELECT * FROM {$this->db->prefix}uap_loop_entries_items
					WHERE user_id = %d
						AND action_id = %d
						AND filter_id = %s
						AND recipe_id = %d
						AND recipe_log_id = %d
						AND recipe_run_number = %d
				",
				$user_id,
				$action_id,
				$process_id,
				$recipe_id,
				$recipe_log_id,
				$recipe_run_number
			),
			ARRAY_A
		);

		if ( empty( $record ) ) {
			return false;
		}

		return ( new Loop_Entry_Item_Model() )->hydrate_from_array( (array) $record );

	}

	/**
	 * Updates an entry item
	 *
	 * @param Loop_Entry_Item_Model $loop
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function update( Loop_Entry_Item_Model $loop ) {

		$updated = $this->db->update(
			$this->table,
			array(
				'user_id'           => $loop->get_user_id(),
				'entity_id'         => $loop->get_entity_id(),
				'action_id'         => $loop->get_action_id(),
				'filter_id'         => $loop->get_filter_id(),
				'status'            => $loop->get_status(),
				'error_message'     => $loop->get_error_message(),
				'recipe_id'         => $loop->get_recipe_id(),
				'recipe_log_id'     => $loop->get_recipe_log_id(),
				'recipe_run_number' => $loop->get_recipe_run_number(),
				'action_data'       => $loop->get_action_data(),
				'action_tokens'     => $loop->get_action_tokens(),
				'date_added'        => current_time( 'mysql' ),
				'date_updated'      => current_time( 'mysql' ),
			),
			array(
				'ID' => $loop->get_id(),
			),
			$this->table_structure_format,
			array(
				'%d', // Where format; The ID should be int.
			)
		);

		return $updated;
	}

	/**
	 * Saves the loop entry
	 *
	 * @param Loop_Entry_Item_Model $loop
	 *
	 * @return int The last ID inserted
	 * @throws \Exception
	 *
	 */
	public function save( Loop_Entry_Item_Model $loop ) {

		$params = array(
			'user_id'           => $loop->get_user_id(),
			'entity_id'         => $loop->get_entity_id(),
			'action_id'         => $loop->get_action_id(),
			'filter_id'         => $loop->get_filter_id(),
			'status'            => $loop->get_status(),
			'error_message'     => $loop->get_error_message(),
			'recipe_id'         => $loop->get_recipe_id(),
			'recipe_log_id'     => $loop->get_recipe_log_id(),
			'recipe_run_number' => $loop->get_recipe_run_number(),
			'action_data'       => $loop->get_action_data(),
			'action_tokens'     => $loop->get_action_tokens(),
			'date_added'        => current_time( 'mysql' ),
			'date_updated'      => current_time( 'mysql' ),
		);

		$inserted = $this->db->insert( $this->table, $params );

		if ( false === $inserted ) {
			throw new Loops_Exception( 'An error has occured while saving the record.' . $this->db->last_error );
		}

		return $this->db->insert_id;

	}

	/**
	 * @param string $process_id
	 *
	 * @return int|false The number of raws deleted. False on failure.
	 */
	public function delete_by_process_id( $process_id ) {

		return $this->db->delete(
			$this->table,
			array(
				'filter_id' => $process_id,
			),
			array(
				'%s',
			)
		);

	}

	/**
	 * @param string $process_id
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function cancel_by_group( $process_id ) {

		return $this->db->update(
			$this->table,
			array(
				'status' => 'cancelled',
			),
			array(
				'filter_id' => $process_id,
				'status'    => Automator_Status::get_class_name( Automator_Status::IN_PROGRESS ),
			),
			array(
				'%s',
			),
			array(
				'%s',
			)
		);

	}

}
