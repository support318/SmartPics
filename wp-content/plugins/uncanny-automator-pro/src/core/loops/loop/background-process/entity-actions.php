<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Background_Process;

use Uncanny_Automator_Pro\Loops\Filter\Registry;
use Uncanny_Automator_Pro\Loops\Loop\Background_Process\Lib\Auth;
use Uncanny_Automator_Pro\Loops\Loop\Background_Process\Lib\WP_Background_Processing\WP_Background_Process;
use Uncanny_Automator_Pro\Loops\Loop\Entity_Factory;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Item_Query;
use Uncanny_Automator_Pro\Loops\Process_Hooks_Callback;
use Uncanny_Automator_Pro\Loops\Recipe\Process;
use Uncanny_Automator_Pro\Loops\Token;
use Uncanny_Automator_Pro\Loops_Process_Registry;

/**
 * Class Entity_Actions
 *
 * Entity_Actions class stands for {User}_{Actions} or {Posts}_{Actions}
 *
 * As each item in the task represents the entity and the actions belongs to it.
 *
 * @since 5.0
 */
final class Entity_Actions extends WP_Background_Process {

	/**
	 * @var mixed[] $items
	 */
	protected $items = array();

	/**
	 * @var int $cron_interval
	 */
	protected $cron_interval = 5; // 5 minute.

	/**
	 * The unique identifier prefix.
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * The unique identifier action.
	 *
	 * @var string
	 */
	protected $action = '';

	/**
	 * @var string
	 */
	const AUTH_ACTION = 'loop_start_process';

	/**
	 * Status for terminated process.
	 *
	 * The 'End Loop' action depends on this status to terminate the process. The
	 * STATUS_TERMINATED is used instead of STATUS_ENDED because "ENDED" is ubiquitous
	 * depending on the context and we don't want to confuse it with the loop termination.
	 *
	 * @var int
	 */
	const STATUS_TERMINATED = 10001; // WP Background Process starts at 1. Lets start at 10001 to avoid conflicts.

	/**
	 * Current batch index
	 *
	 * @var int
	 */
	protected $current_batch_index = 0;

	/**
	 * @param string $process_id
	 *
	 * @return void
	 */
	public function __construct( $process_id = '' ) {

		$this->prefix = 'uap_loops';
		$this->action = $process_id;

		parent::__construct();
	}

	/**
	 * @return void
	 */
	public function clear_process_schedule() {
		$this->clear_scheduled_event();
	}

	/**
	 * @return string
	 */
	public function get_cron_hook_identifier() {
		return $this->cron_hook_identifier;
	}

	/**
	 * This method is copied from \Uncanny_Automator\Recipe\Trigger_Tokens Trait.
	 *
	 * @param mixed $value
	 * @param mixed $pieces
	 * @param mixed $recipe_id
	 * @param mixed[] $trigger_data
	 * @param mixed $user_id
	 * @param mixed $replace_arg
	 * @return mixed
	 */
	public function fetch_token_data( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_arg ) {

		if ( empty( $trigger_data ) || ! isset( $trigger_data[0] ) ) {
			return $value;
		}

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		list( $recipe_id, $token_identifier, $token_id ) = $pieces;

		$data = Automator()->db->token->get( $token_identifier, $replace_arg );
		$data = is_array( $data ) ? $data : json_decode( $data, true );

		if ( is_array( $data ) && isset( $data[ $token_id ] ) ) {
			return $data[ $token_id ];
		}

		return $value;
	}

	/**
	 * Get the current batch index
	 *
	 * @return int
	 */
	public function get_current_batch_index() {
		return $this->current_batch_index;
	}

	/**
	 * @param array{entity_id:int,process_transient:string} $item
	 */
	protected function task( $item ) {

		// Start of with null user id.
		$user_id = null;

		// The idea here is to let CPU do other tasks while we sleep the process.
		$throttle_seconds = apply_filters( 'automator_pro_throttle_background_task_execution', 0, $item );

		if ( $throttle_seconds >= 1 ) {
			sleep( $throttle_seconds );
		}

		$transient = Registry::get_process_transient( $item['process_transient'] );

		$entity_id  = $item['entity_id'];
		$flow_items = $transient['items'];
		$args       = $transient['args'];
		$loop_item  = $transient['loop_item'];
		$triggers   = $transient['triggers'];

		foreach ( $triggers as $trigger ) {
			$this->attach_trigger_token_hooks( $trigger );
		}

		$loop_type = $transient['loop_item']['type'];

		// Register the common parser.
		$common_parser = new Token\Common\Parser();
		$common_parser->register_parser();
		$common_parser->set_loop_item( $item );

		if ( Entity_Factory::TYPE_USERS === $loop_type ) {
			// Kickstart the user parser.
			$user_parser = new Token\Users\Parser();
			$user_parser->set_loop_item( $item );
			$user_parser->register_parser();
			// User's loop entity ID and user ID is the same.
			$user_id = $entity_id;
		}

		if ( Entity_Factory::TYPE_POSTS === $loop_type ) {
			// Kickstart the parser.
			$post_parser = new Token\Posts\Parser();
			$post_parser->set_loop_item( $item );
			$post_parser->register_parser();
			// Process args trigger user ID. Automatically compatible with user-selector.
			$user_id = $transient['args']['user_id'];
		}

		if ( Entity_Factory::TYPE_TOKEN === $loop_type ) {
			// Kickstart the parser.
			Token\Loopable\Parser_Support::register_parser( $loop_item );
			// Process args trigger user ID. Automatically compatible with user-selector.
			$user_id = $transient['args']['user_id'];
		}

		foreach ( (array) $flow_items as $flow_item ) {

			// Action conditions.
			if ( 'filter' === $flow_item['type'] ) {

				$actions = $flow_item['items'];

				foreach ( $actions as $action ) {
					$action_condition_process = new Process_Hooks_Callback();
					$action_condition_process->complete_loop_action( $user_id, $entity_id, $action['id'], $args, $loop_item );
				}
			}

			// Normal actions.
			if ( 'action' === $flow_item['type'] ) {

				$action_process = new Process_Hooks_Callback();
				$action_process->complete_loop_action( $user_id, $entity_id, $flow_item['id'], $args, $loop_item );

			}
		}

		return false;
	}

	/**
	 * Attaches the missing trigger tokens.
	 *
	 * @param  \Uncanny_Automator\Services\Recipe\Structure\Triggers\Trigger\Trigger $trigger
	 * @return void
	 */
	public function attach_trigger_token_hooks( $trigger ) {

		$trigger_props = json_decode( Process_Hooks_Callback::json_encode( $trigger ), true );

		if ( false === $trigger ) {
			return;
		}

		if ( ! is_array( $trigger_props ) || ! isset( $trigger_props['integration_code'] ) || ! isset( $trigger_props['code'] ) ) {
			return;
		}

		$filter = strtr(
			'automator_parse_token_for_trigger_{{integration}}_{{trigger_code}}',
			array(
				'{{integration}}'  => strtolower( $trigger_props['integration_code'] ),
				'{{trigger_code}}' => strtolower( $trigger_props['code'] ),
			)
		);

		// Get the token value when `automator_parse_token_for_trigger_{{integration}}_{{trigger_code}}`.
		add_filter( $filter, array( $this, 'fetch_token_data' ), 20, 6 );

		// Loopable tokens.
		$this->register_loopable_trigger_tokens_hooks( $trigger_props['code'], $trigger_props['id'] );
	}

	/**
	 * @param mixed $args
	 * @return void
	 */
	private function register_loopable_trigger_tokens_hooks( $trigger_code, $trigger_id ) {

		$trigger_code    = $trigger_code ?? null;
		$trigger         = Automator()->get_trigger( $trigger_code );
		$loopable_tokens = $trigger['loopable_tokens'] ?? array();

		foreach ( (array) $loopable_tokens as $token_class ) {
			if ( is_string( $token_class ) && class_exists( $token_class ) ) {
				$token_class = new $token_class( $trigger_id );
				$token_class->register_hooks( $trigger );
				$token_class->set_trigger( $trigger );
			}
		}
	}


	/**
	 * Handle a dispatched request.
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 *
	 * @return void|mixed
	 */
	protected function handle() {
		$this->lock_process();

		/**
		 * Number of seconds to sleep between batches. Defaults to 0 seconds, minimum 0.
		 *
		 * @param int $seconds
		 */
		$throttle_seconds = max(
			0,
			apply_filters(
				/**
				 * @see WP_Async_Request
				 */
				$this->identifier . '_seconds_between_batches',
				apply_filters(
					/**
					 * @see WP_Async_Request
					 */
					$this->prefix . '_seconds_between_batches',
					0
				)
			)
		);

		do {
			$batch = $this->get_batch();

			foreach ( $batch->data as $key => $value ) {
				// Add index to the item
				$value['batch_index'] = $this->current_batch_index++;

				$task = $this->task( $value );

				if ( $this->is_terminated() ) {
					$this->loop_terminated( $value );
					$this->delete_all();
					break;
				}

				if ( false !== $task ) {
					$batch->data[ $key ] = $task;
				} else {
					unset( $batch->data[ $key ] );
				}

				// Keep the batch up to date while processing it.
				if ( ! empty( $batch->data ) ) {
					$this->update( $batch->key, $batch->data );
				}

				// Let the server breathe a little.
				sleep( $throttle_seconds );

				if ( $this->time_exceeded() || $this->memory_exceeded() || $this->is_paused() || $this->is_cancelled() ) {
					// Check the last iteration value.
					$this->loop_batch_completed( $value );
					break;
				}
			}

			// Delete current batch if fully processed.
			if ( empty( $batch->data ) ) {
				$this->loop_batch_completed( $value );
				$this->delete( $batch->key );
			}
		} while ( ! $this->time_exceeded()
			&& ! $this->memory_exceeded()
			&& ! $this->is_queue_empty()
			&& ! $this->is_paused()
			&& ! $this->is_cancelled()
			&& ! $this->is_terminated()
		);

		$this->unlock_process();

		// Start next batch or complete process.
		if ( ! $this->is_queue_empty() ) {
			$this->dispatch();
		} else {
			$this->complete();
		}

		return $this->maybe_wp_die();
	}

	/**
	 * Loop batch completed.
	 *
	 * @param mixed[] $value
	 *
	 * @return void
	 */
	public function loop_batch_completed( array $value ): void {

		if ( ! isset( $value ) || ! isset( $value['process_transient'] ) ) {
			return;
		}

		$process_transient = Registry::get_process_transient( $value['process_transient'] );

		do_action(
			'automator_pro_loop_batch_completed',
			$process_transient['loop_item'],
			$value['process_transient']
		);
	}

	/**
	 * Loop batch item process stopped.
	 *
	 * @param mixed[] $value
	 *
	 * @return void
	 */
	public function loop_terminated( array $value ): void {

		if ( ! isset( $value ) || ! isset( $value['process_transient'] ) ) {
			return;
		}

		$process_transient = Registry::get_process_transient( $value['process_transient'] );

		do_action(
			'automator_pro_loop_batch_item_process_terminated',
			$process_transient['loop_item'],
			$value['process_transient']
		);
	}
	/**
	 * @return void
	 */
	public function cancelled() {

		// Update the status.
		$item_query = new Loop_Entry_Item_Query();
		$item_query->cancel_by_group( $this->action );

		$process_args = Loops_Process_Registry::extract_process_id( $this->action );

		if ( is_array( $process_args ) ) {

			$loop_id       = $process_args['loop_id'];
			$recipe_id     = $process_args['recipe_id'];
			$recipe_log_id = $process_args['recipe_log_id'];
			$run_number    = $process_args['run_number'];

			$status = Process::resolve_recipe_status( absint( $recipe_id ), absint( $recipe_log_id ), absint( $run_number ) );

			Automator()->db->recipe->mark_complete( $recipe_log_id, $status );

			// Start the next one.
			$loop = array(
				'filter_id' => $this->action,
			);

			Process_Hooks_Callback::process_next( $loop );

		}

		parent::cancelled();
	}

	/**
	 * Adds a query parameter to post request.
	 *
	 * @return mixed[]
	 */
	protected function get_query_args() {

		$query_args = parent::get_query_args();

		$token = automator_pro_loop_auth_token()->generate_token( self::AUTH_ACTION );

		$query_args['token'] = $token;

		return $query_args;
	}

	/**
	 * Exits the process with different status.
	 *
	 * @return void
	 */
	public function terminate() {
		update_site_option( $this->get_status_key(), self::STATUS_TERMINATED );
		// In case the process was paused, dispatch it again.
		$this->terminated();
	}

	/**
	 * Triggered when the process is terminated.
	 *
	 * @return void
	 */
	protected function terminated() {
		do_action( $this->identifier . '_terminated' );
	}

	/**
	 * Has the process been terminated?
	 *
	 * @return bool
	 */
	public function is_terminated() {
		$status = get_site_option( $this->get_status_key(), 0 );

		if ( absint( $status ) === self::STATUS_TERMINATED ) {
			return true;
		}

		return false;
	}
	/**
	 * Schedule a dispatch.
	 *
	 * @return void
	 */
	public function scheduled_dispatch() {
		$this->schedule_event();
	}
}
