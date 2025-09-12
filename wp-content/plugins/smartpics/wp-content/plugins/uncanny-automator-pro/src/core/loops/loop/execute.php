<?php
namespace Uncanny_Automator_Pro\Loops\Loop;

use Exception;
use Uncanny_Automator\Automator_Recipe_Process_Complete;
use Uncanny_Automator\Automator_Status;
use Uncanny_Automator\Logger\Recipe_Objects_Logger;
use Uncanny_Automator\Services\Recipe\Structure;
use Uncanny_Automator_Pro\Loops\Loop;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Query;
use Uncanny_Automator_Pro\Loops\Loop\Background_Process\Entity_Actions;
use Uncanny_Automator_Pro\Loops\Loop_MQ;
use Uncanny_Automator_Pro\Loops\Recipe\Trait_Process;
use Uncanny_Automator_Pro\Loops\Filter\Model\Active_Record\Entity_Filter_Record;
use Uncanny_Automator_Pro\Loops_Process_Registry;
use WP_Error;

/**
 * Execute
 *
 * @package Uncanny_Automator_Pro\Loops\Loop
 */
class Execute {

	use Trait_Process;

	/**
	 * @var Structure
	 */
	protected $recipe_structure = null; //@phpstan-ignore-line

	/**
	 * @var string $loop_type The type of loop. Can be enum. Currently we only have users. Defaults to "users".
	 */
	protected $loop_type = 'users';

	/**
	 * @var Loops_Process_Registry
	 */
	protected $process_registry = null;

	/**
	 * @var int $loop_id The loop ID.
	 */
	protected $loop_id = null;

	/**
	 * The user ID here refers to a top level user, not iterated user.
	 *
	 * @var int $user_id
	 */
	protected $user_id = null;

	/**
	 * Sets the recipe structure property
	 *
	 * @param Structure $recipe_structure The main recipe object.
	 *
	 * @return void
	 */
	// @phpstan-ignore argument.type
	public function set_recipe_structure( Structure $recipe_structure ) {

		$this->recipe_structure = $recipe_structure;
		$this->process_registry = Loops_Process_Registry::get_instance();

	}

	/**
	 * Sets the loop ID.
	 *
	 * @param int $loop_id
	 *
	 * @return void
	 */
	public function set_loop_id( $loop_id ) {
		$this->loop_id = absint( $loop_id );
	}

	/**
	 * Sets the user ID. The user ID here refers to the top level user, and not to the iterated user.
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = absint( $user_id );
	}

	/**
	 * Retrives the loop id.
	 *
	 * @return int
	 */
	public function get_loop_id() {
		return $this->loop_id;
	}

	/**
	 * @param string $type
	 *
	 * @return void
	 */
	public function set_loop_type( $type = 'users' ) {
		$this->loop_type = $type;
	}

	/**
	 * @return string
	 */
	public function get_loop_type() {
		return $this->loop_type;
	}

	/**
	 * Determines if doing rest.
	 *
	 * @return bool
	 */
	private static function is_doing_rest() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Creates a loop entity object depending on the context.
	 *
	 * @param string $type The loop type. Defaults to 'users'.
	 * @param int[] $entities The entities found.
	 *
	 * @since 5.3
	 *
	 * @return Entity_Loopable
	 */
	protected function create_entity( $type = 'users', $entities = array() ) {

		return ( new Entity_Factory() )->make( $type, $entities );

	}

	/**
	 * Retrieve all loopable items.
	 *
	 * @return int[]|array{array{mixed}}
	 */
	public function get_loopable_items() {

		$loop_id = $this->get_loop_id();

		$filter_records = new Entity_Filter_Record(
			$this->get_recipe_id(),
			$loop_id,
			$this->get_process_args()
		);

		$type = $this->get_loop_type();

		$filter_records->set_loop_id( $loop_id );
		$filter_records->set_loop_type( $type );

		$entities = $filter_records->get_entities();

		// Create a new instance Entity_Loopable from loop type and entities record.
		$entity = $this->create_entity( $type, $entities );
		return $entity->get();

	}

	/**
	 * Runs a loop.
	 *
	 * @since 6.1 - Throws an exception if registry query is disabled.
	 *
	 * @param array{items:mixed[],loopable_expression:array{type:string}} $action_item An action item taken from the main recipe object.
	 * @param array{\Uncanny_Automator\Services\Recipe\Structure\Triggers\Trigger\Trigger} $triggers
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function run_loop( $action_item, $triggers ) {

		try {

			if ( Loops_Process_Registry::is_loop_registry_query_disabled() ) {
				throw new Exception(
					'The automator_pro_loop_registry_query_disabled filter is set to true, disabling the registry query. As a result, the Loop process will not be initiated.',
					Automator_Status::COMPLETED_WITH_ERRORS
				);
			}

			// Handles unexpected errors on initialization. Loop initialization is not yet run in the background and can fail due to memory issues.
			$this->handle_unexpected_bad_errors();

			// @since 5.10
			do_action(
				'automator_pro_before_loop_is_queued',
				array(
					'user_id'             => $this->get_user_id(),
					'triggers'            => $triggers,
					'recipe_id'           => $this->get_recipe_id(),
					'process_args'        => $this->get_process_args(),
					'recipe_log_id'       => $this->get_recipe_log_id(),
					'iterable_expression' => $action_item['iterable_expression'],
				)
			);

			$this->set_loop_type( $action_item['iterable_expression']['type'] );

			// We get the loopable items. Loopable items refer to entities, or array values that can be iterated. We can have Users or Posts or RSS feed.
			$loopable_items = $this->get_loopable_items();

			// Creates a process ID. 3rd-party plugin fills this information.
			$process_id = $this->generate_process_id( $loopable_items );

			if ( is_wp_error( $process_id ) ) {
				throw new Exception( 'Failed to spawn a new process with error message: ' . $process_id->get_error_message(), Automator_Status::COMPLETED_WITH_NOTICE );
			}

			// Spawn a new process since engine is not real-time.
			$process = $this->process_registry->spawn_process( $process_id );

			// Chunk size controls the numbers of entries that are saved in the batch, preventing mysql packet size exhaustion.
			// Use this filter to fix mysql packet size issue. The less the better.
			$chunk_size = apply_filters( 'automator_pro_loop_queue_chunk_size', 1024, $this->get_process_args() );

			// Skip the process if there are no actions to iterate.
			if ( empty( $action_item['items'] ) ) {
				throw new Exception( 'Process was skipped because there were no actions to execute.', Automator_Status::COMPLETED );
			}

			// This method allows as to queue the loopable items in an effecient way without sending the properties into the queue.
			$this->generate_loop_process_transient( $process_id, $action_item, $triggers );

			$counter = 0;

			// Send each loopable action to background processor.
			foreach ( $loopable_items as $key => $item ) {

				$entity_id = $item;

				// Use the array index or key if the loop type is 'token'.
				if ( Entity_Factory::TYPE_TOKEN === $this->get_loop_type() ) {
					$entity_id = $key;
				}

				// Construct the item to be added in the queue.
				$item = array(
					'entity_id'         => $entity_id,
					'process_transient' => self::generate_key( $process_id ),
				);

				$process->push_to_queue( $item );

				// Dispatch every nth item to avoid very large queue. Large queues can cause mysql packets to overload.
				if ( 0 === $counter % $chunk_size ) {
					$process->save();
				}

				$counter++;
			}

			// Saves the item on queue in case it was not saved.
			$process->save();

			// Mark the recipe as in-progress.
			Automator()->db->recipe->mark_complete(
				$this->get_recipe_log_id(),
				Automator_Status::IN_PROGRESS
			);

			// Add the process to queue. Dispatches or queues the item.
			self::add_to_queue( $process, $process_id );

		} catch ( \Error $error ) {

			$this->catch_errors( $error );

		} catch ( \Exception $exception ) {

			$this->catch_errors( $exception );

		}
	}

	/**
	 * Exceptions cannot catch some errors like exhausted memories exhaustion.
	 *
	 * Handle bad errors from this function. Prevents in-progress status that can't be resume without deleting the logs.
	 *
	 * @return void
	 */
	private function handle_unexpected_bad_errors() {

		$recipe_process = Automator_Recipe_Process_Complete::get_instance();
		$loop_id        = $this->get_loop_id();
		$recipe_flow    = $this->recipe_structure->retrieve();
		$recipe_id      = $this->get_recipe_id();
		$user_id        = $this->get_user_id();
		$recipe_log_id  = $this->get_recipe_log_id();
		$args           = $this->get_process_args();

		register_shutdown_function(
			function() use ( $recipe_process, $loop_id, $recipe_flow, $recipe_id, $user_id, $recipe_log_id, $args ) {
				$error = error_get_last();
				if ( null !== $error && ( E_USER_ERROR === $error['type'] || E_ERROR === $error['type'] ) ) { // Fatal error has occured.
					do_action( 'automator_pro_loop_entry_error', $loop_id, wp_json_encode( $recipe_flow->get( 'actions' ) ), $error['message'], Automator_Status::COMPLETED_WITH_ERRORS, $args );
					$recipe_process->recipe( $recipe_id, $user_id, $recipe_log_id, $args );
					// Ask queue to resume next process?
				}
			}
		);
	}

	/**
	 * Generates a process ID.
	 *
	 * @param mixed[] $loopable_items
	 *
	 * @return string|WP_Error The process ID.
	 */
	public function generate_process_id( $loopable_items ) {

		return apply_filters(
			'automator_pro_loop_entry_initialized',
			$this->get_loop_id(),
			$this->get_loop_type(),
			$loopable_items,
			wp_json_encode( $this->recipe_structure->retrieve()->get( 'actions' ) ),
			$this->get_process_args()
		);

	}

	/**
	 * Generates a loop process transient.
	 *
	 * @param string $process_id
	 * @param mixed[] $action_item
	 * @param array{\Uncanny_Automator\Services\Recipe\Structure\Triggers\Trigger\Trigger} $triggers
	 *
	 * @return void
	 */
	public function generate_loop_process_transient( $process_id, $action_item, $triggers ) {

		set_transient(
			self::generate_key( $process_id ),
			array(
				'triggers'  => $triggers,
				'items'     => $action_item['items'],
				'args'      => $this->get_process_args(),
				'loop_item' => array(
					'id'            => $this->get_loop_id(),
					'type'          => $this->get_loop_type(),
					'filter_id'     => $process_id, // The background process filter ID.
					'recipe_id'     => $this->get_recipe_id(),
					'recipe_log_id' => $this->get_recipe_log_id(),
					'run_number'    => $this->get_process_args()['run_number'],
				),
			)
		);

	}

	/**
	 * Adds a process and process id to the queue.
	 *
	 * @param Entity_Actions $process
	 * @param string $process_id
	 *
	 * @return bool Returns true if the process was dispatched and added to queue. Otherwise, returns false.
	 */
	public static function add_to_queue( Entity_Actions $process, $process_id ) {

		$mq = new Loop_MQ();

		// Dispatch the item immediately if there are no items in queue.
		if ( ! $mq->has_active() ) {

			// Use $process->scheduled_dispatch() as an alternative.
			$process->dispatch();

			// Add them to queue.
			$mq->add( $process_id, 'processing' );

			return true;

		}

		// Otherwise, queue the process as 'queued'.
		( new Loop_Entry_Query() )->mark_process_as( 'queued', $process_id );

		// Add them to queue.
		$mq->add( $process_id, 'queued' );

		return false;
	}

	/**
	 * Generates a transient key from process_id
	 *
	 * @param string $process_id
	 *
	 * @return string
	 */
	public static function generate_key( $process_id ) {
		return $process_id . '_transaction_transient';
	}

	/**
	 * Catch errors and perform a do_action when an Exception occurs.
	 *
	 * @param \Exception|\Error $exception
	 *
	 * @return void
	 */
	private function catch_errors( $exception ) {

		do_action(
			'automator_pro_loop_entry_error',
			$this->get_loop_id(),
			wp_json_encode( $this->recipe_structure->retrieve()->get( 'actions' ) ),
			$exception->getMessage(),
			$exception->getCode(),
			$this->get_process_args()
		);

		$code = Automator_Status::COMPLETED_WITH_NOTICE;

		if ( empty( $code ) ) {
			$code = Automator_Status::COMPLETED_WITH_ERRORS;
		}

		Automator()->db->recipe->mark_complete( $this->get_recipe_log_id(), $code );

	}

}
