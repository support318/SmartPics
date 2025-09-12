<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model;

use Exception;
use Uncanny_Automator_Pro\Loops\Loop\Entity_Factory;

/**
 * Represents uap_entries table where properties represents columns
 *
 * @since 5.0
 */
class Loop_Entry_Model {

	/**
	 * @var int $id
	 */
	protected $id = null;

	/**
	 * @var int $loop_id
	 */
	protected $loop_id = 0;

	/**
	 * @var string $loop_type
	 */
	protected $loop_type = 'users';

	/**
	 * @var string $process_id
	 */
	protected $process_id = null;

	/**
	 * @var string $status
	 */
	protected $status = null;

	/**
	 * @var string $error_message
	 */
	protected $error_message = '';

	/**
	 * @var string $user_ids
	 */
	protected $user_ids = '';

	/**
	 * @var int $num_users
	 */
	protected $num_users = 0;

	/**
	 * @var string $flow The main recipe flow.
	 */
	protected $flow = null;

	/**
	 * Custom metas.
	 *
	 * @var mixed[]
	 */
	protected $meta = array();

	/**
	 * @var string $process_date_started
	 */
	protected $process_date_started = null;

	/**
	 * @var string $process_date_ended
	 */
	protected $process_date_ended = null;

	/**
	 * @var int $recipe_id
	 */
	protected $recipe_id = 0;

	/**
	 * @var int $recipe_log_id
	 */
	protected $recipe_log_id = 0;

	/**
	 * @var int $run_number
	 */
	protected $run_number = 0;

	/**
	 * Hydrates the object from an associative array. Most likely, it will be from the result
	 * of a query operation using the \wpdb class.
	 *
	 * @param array{
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
	 * } $record
	 *
	 * @return self
	 */
	public function hydate_from_array( $record ) {

		$this->set_id( $record['ID'] );
		$this->set_loop_id( $record['loop_id'] );
		$this->set_loop_type( $record['loop_type'] );
		$this->set_user_ids( $record['entity_ids'], true );

		// No setter. Internal.
		$this->process_id = $record['process_id'];

		$this->set_flow( (array) maybe_unserialize( $record['flow'] ) );
		$this->set_object_meta( (array) $record['meta'] );
		$this->set_status( $record['status'] );
		$this->set_error_message( $record['message'] );
		$this->set_process_date_started( $record['process_date_started'] );
		$this->set_process_date_ended( $record['process_date_ended'] );
		$this->set_recipe_id( $record['recipe_id'] );
		$this->set_recipe_log_id( $record['recipe_log_id'] );
		$this->set_run_number( $record['run_number'] );

		return $this;

	}

	/**
	 * @param int $id
	 *
	 * @return void
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * @param int $loop_id
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function set_loop_id( $loop_id ) {

		if ( empty( absint( $loop_id ) ) ) {
			throw new Exception( 'Loop ID cannot be empty.', 404 );
		}

		$this->loop_id = $loop_id;
	}

	/**
	 * @param string $type Defaults to 'users'.
	 *
	 * @return void
	 */
	public function set_loop_type( $type = 'users' ) {
		$this->loop_type = $type;
	}

	/**
	 * @param string $status
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function set_status( $status ) {

		if ( ! is_string( $status ) || empty( $status ) ) {
			throw new Exception( 'Status should be a string type and cannot be empty.', 404 );
		}

		$this->status = $status;
	}

	/**
	 * @param string $error_message
	 *
	 * @return void
	 */
	public function set_error_message( $error_message ) {

		$this->error_message = $error_message;

	}

	/**
	 * Set the User IDs to iterate from
	 *
	 * @param string $user_ids Comma-separated integer values.
	 * @param bool $allow_empty Whether to allow empty users or not.
	 *
	 * @return void
	 */
	public function set_user_ids( $user_ids, $allow_empty = false ) {

		if ( false === $allow_empty && ( ! is_string( $user_ids ) || empty( $user_ids ) ) ) {
			throw new Exception( 'Invalid user IDs detected - Received: ' . wp_json_encode( $user_ids ), 404 );
		}

		$this->user_ids = $user_ids;

		if ( Entity_Factory::TYPE_TOKEN === $this->get_loop_type() ) {
			$count = count( (array) json_decode( $this->user_ids, true ) );
			$this->set_num_users( $count );
			return;
		}

		// Removes empty entries as well.
		$users = array_filter( (array) explode( ',', $this->user_ids ) );

		$this->set_num_users( count( $users ) );

	}

	/**
	 * @param mixed[] $flow
	 *
	 * @return void
	 */
	public function set_flow( $flow ) {

		if ( empty( $flow ) ) {
			throw new Exception( 'Flow cannot be empty.', 404 );
		}

		$serialized_flow = maybe_serialize( $flow );

		if ( ! empty( $serialized_flow ) && is_string( $serialized_flow ) ) {
			$this->flow = $serialized_flow;
			return;
		}

		$this->flow = '';

	}

	/**
	 * Set meta.
	 *
	 * @param string $key
	 * @param mixed $meta
	 *
	 * @return void
	 */
	public function set_meta( $key, $meta ) {
		$this->meta[ $key ] = $meta;
	}

	/**
	 * Set the object meta.
	 *
	 * @param mixed[] $meta
	 *
	 * @return void
	 */
	public function set_object_meta( $meta ) {
		$this->meta = $meta;
	}

	/**
	 * Set the date when the process started.
	 *
	 * @param string $date_started The date when the process started.
	 *
	 * @return void
	 */
	public function set_process_date_started( $date_started ) {

		$this->process_date_started = $date_started;

	}

	/**
	 * Set the date when the process ended.
	 *
	 * @param string $date_ended The date when the process ended.
	 *
	 * @return void
	 */
	public function set_process_date_ended( $date_ended ) {

		$this->process_date_ended = $date_ended;

	}

	/**
	 * Set the ID of the recipe.
	 *
	 * @param int $recipe_id The ID of the recipe.
	 *
	 * @return void
	 */
	public function set_recipe_id( $recipe_id ) {

		$this->recipe_id = $recipe_id;

	}

	/**
	 * Set the ID of the recipe log.
	 *
	 * @param int $recipe_log_id The ID of the recipe log.
	 *
	 * @return void
	 */
	public function set_recipe_log_id( $recipe_log_id ) {

		$this->recipe_log_id = $recipe_log_id;

	}

	/**
	 * Set the run number.
	 *
	 * @param int $run_number The run number.
	 *
	 * @return void
	 */
	public function set_run_number( $run_number ) {

		$this->run_number = $run_number;

	}

	/**
	 * Set the number of users.
	 *
	 * @param mixed $integer
	 *
	 * @return void
	 */
	public function set_num_users( $integer ) {

		$this->num_users = absint( $integer );

	}

	/**
	 * Get the ID.
	 *
	 * @return int The ID.
	 */
	public function get_id() {

		return $this->id;

	}

	/**
	 * Get the loop ID.
	 *
	 * @return int The loop ID.
	 */
	public function get_loop_id() {

		return $this->loop_id;

	}

	/**
	 * @return string
	 */
	public function get_loop_type() {

		return $this->loop_type;

	}

	/**
	 * Get the process ID.
	 *
	 * @return string The process ID.
	 */
	public function get_process_id() {

		return $this->process_id;

	}

	/**
	 * Get the status.
	 *
	 * @return string The status.
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get the error message.
	 *
	 * @return string The error message.
	 */
	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * Get the user IDs.
	 *
	 * @return string The user IDs.
	 */
	public function get_user_ids() {
		return $this->user_ids;
	}

	/**
	 * Get the flow.
	 *
	 * @return string The serialized flow.
	 */
	public function get_flow() {

		return $this->flow;

	}

	/**
	 * Retrieve the meta.
	 *
	 * @return mixed[]
	 */
	public function get_meta() {
		return $this->meta;
	}

	/**
	 * Get the number of entities.
	 *
	 * @return int The number of entities.
	 */
	public function num_entities() {
		return $this->num_users;
	}

	/**
	 * Get the date when the process started.
	 *
	 * @return string The date when the process started.
	 */
	public function get_process_date_started() {
		return $this->process_date_started;
	}

	/**
	 * Get the date when the process ended.
	 *
	 * @return string The date when the process ended.
	 */
	public function get_process_date_ended() {
		return $this->process_date_ended;
	}

	/**
	 * Get the ID of the recipe.
	 *
	 * @return int The ID of the recipe.
	 */
	public function get_recipe_id() {
		return $this->recipe_id;
	}

	/**
	 * Get the ID of the recipe log.
	 *
	 * @return int The ID of the recipe log.
	 */
	public function get_recipe_log_id() {
		return $this->recipe_log_id;
	}

	/**
	 * Get the run number.
	 *
	 * @return int The run number.
	 */
	public function get_run_number() {
		return $this->run_number;
	}

}
