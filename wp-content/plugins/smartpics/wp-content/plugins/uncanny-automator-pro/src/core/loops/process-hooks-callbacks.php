<?php
namespace Uncanny_Automator_Pro\Loops;

use Uncanny_Automator\Automator_Recipe_Process_Complete;
use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Entry_Item_Model;
use Uncanny_Automator_Pro\Loops\Loop\Entity_Factory;
use Uncanny_Automator_Pro\Utilities;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Entry_Model;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Item_Query;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Query;
use Uncanny_Automator_Pro\Loops_Process_Registry;
use WP_Error;

class Process_Hooks_Callback {

	/**
	 * Calls specific class methods when a specific Automator action hook is invoked.
	 *
	 * @return void
	 */
	public function register_hooks() {

		// Adds a single loop entry to the db table.
		add_filter( 'automator_pro_loop_entry_initialized', array( $this, 'add_entry' ), 10, 5 );

		// Completes the entry if there are no batches left.
		// @phpstan-ignore-next-line Action callback returns bool|int|WP_Error but should not return anything.
		add_action( 'automator_pro_loop_batch_completed', array( $this, 'complete_entry' ), 10, 2 );

		// Completes the entry if the loop batch item process terminated.
		add_action( 'automator_pro_loop_batch_item_process_terminated', array( $this, 'complete_entry_on_exit' ), 10, 2 );

		// Adds a single loop entry with error message to the db table.
		add_action( 'automator_pro_loop_entry_error', array( $this, 'flag_entry_with_error' ), 10, 5 );

		// Modifies the recipe object 'has_loop_running' properties.
		add_filter( 'automator_recipe_has_loop_running', array( $this, 'has_loop_running' ), 10, 2 );

		// Replaces the status of loop with cancelled status if its cancelled.
		add_filter( 'automator_loop_logs_resources_status', array( $this, 'replace_status' ), 10, 4 );

		// Hooks to 'automator_pro_async_action_execution_after_invoked' to complete action that is inside the loop.
		add_action( 'automator_pro_async_action_execution_after_invoked', array( $this, 'complete_scheduled_actions' ) );

		// Supports action tokens inside the loop.
		add_filter( 'automator_action_tokens_hydrated_tokens', array( $this, 'hydrate_action_tokens' ), 10, 3 );

		// Parse action tokens from parent.
		add_filter( 'automator_action_tokens_meta_token_value', array( $this, 'parse_action_tokens_meta' ), 10, 3 );

		// Parse action tokens fields from parent.
		add_filter( 'automator_action_tokens_field_token_value', array( $this, 'parse_action_tokens_fields' ), 10, 3 );

		// Short circuits recipe status making sure it reflects the actual recipe status regardless.
		add_filter( 'automator_recipe_process_complete_status', array( $this, 'intercept_recipe_process_complete_status' ), 10, 2 );

		// Change the async actions group to loop process ID so we can cancel them in bulk later on.
		add_filter( 'automator_pro_asyc_actions_group', array( $this, 'use_process_id_as_async_group' ), 10, 2 );
	}

	/**
	 * Adds a new loop process entry.
	 *
	 * @param int $loop_id
	 * @param string $loop_type
	 * @param int[] $users
	 * @param string $flow_actions The JSON encoded action flow.
	 * @param mixed[] $args Process args.
	 *
	 * @return string|WP_Error The process ID or WP_Error on empty.
	 */
	public function add_entry( $loop_id, $loop_type, $users, $flow_actions, $args ) {

		$flow_actions_array = (array) json_decode( $flow_actions, true );

		$loopable_expression = get_post_meta( $loop_id, 'iterable_expression', true );

		try {

			$loop_entry = new Loop_Entry_Model();

			// Build the loop entry model.
			$loop_entry->set_loop_id( absint( $loop_id ) );
			$loop_entry->set_loop_type( $loop_type );
			$loop_entry->set_flow( $flow_actions_array );
			$loop_entry->set_meta( 'iterable_expression', $loopable_expression );
			$loop_entry->set_status( Automator_Status::get_class_name( Automator_Status::IN_PROGRESS ) );
			$loop_entry->set_process_date_started( current_time( 'mysql' ) );
			$loop_entry->set_recipe_id( absint( $args['recipe_id'] ) );
			$loop_entry->set_recipe_log_id( absint( $args['recipe_log_id'] ) );
			$loop_entry->set_run_number( absint( $args['run_number'] ) );

			// Set the entities.
			if ( Entity_Factory::TYPE_TOKEN === $loop_type ) {

				$encoded_entities = self::json_encode( $users );
				$loop_entry->set_user_ids( $encoded_entities );

			} else {
				$loop_entry->set_user_ids( implode( ',', array_values( $users ) ) );
			}

			// Save the loop entry model.
			( new Loop_Entry_Query() )->save( $loop_entry );

			return Loops_Process_Registry::generate_process_id( $loop_entry );

		} catch ( \Exception $e ) {

			$force_log = defined( 'AUTOMATOR_DEBUG_MODE' ) ? AUTOMATOR_DEBUG_MODE : true;

			// Otherwise, log the error.
			automator_log(
				array(
					'Error message' => $e->getMessage(),
					'Loop ID'       => $loop_id,
					'Timestamp'     => time(),
				),
				'Loop Exception',
				$force_log,
				'loop-exception-' . $loop_id
			);

			return new WP_Error( '400', $e->getMessage() );

		}
	}

	/**
	 * @param string $err_message
	 * @param mixed[] $args
	 * @param int $loop_id
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	private function update_existing_entry( $err_message, $args, $loop_id ) {

		global $wpdb;
		// Use raw query here so we can prevent another exception.
		$process_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID
				FROM {$wpdb->prefix}uap_loop_entries
				WHERE loop_id = %d
					AND recipe_id = %d
					AND recipe_log_id = %d
					AND run_number = %d",
				$loop_id,
				$args['recipe_id'],
				$args['recipe_log_id'],
				$args['run_number']
			)
		);

		if ( ! empty( $process_id ) ) {
			return $wpdb->update(
				"{$wpdb->prefix}uap_loop_entries",
				array(
					'status'  => 'skipped',
					'message' => $err_message,
				),
				array(
					'ID' => $process_id,
				)
			);
		}

		return false;
	}

	/**
	 * Flags a loop entry with an error message.
	 *
	 * @param int $loop_id
	 * @param string $flow_actions
	 * @param string $err_message
	 * @param int $err_code
	 * @param mixed[] $args
	 *
	 * @return void
	 */
	public function flag_entry_with_error( $loop_id, $flow_actions, $err_message, $err_code, $args ) {

		$flow_actions_array = (array) json_decode( $flow_actions, true );

		$updated = $this->update_existing_entry( $err_message, $args, $loop_id );

		if ( $updated ) {
			return;
		}

		try {

			if ( empty( $err_code ) ) {
				$err_code = Automator_Status::COMPLETED;
			}

			$status = Automator_Status::get_class_name( $err_code );

			$loop_entry = new Loop_Entry_Model();

			// Build the model.
			$loop_entry->set_loop_id( $loop_id );
			$loop_entry->set_user_ids( '', true );
			$loop_entry->set_flow( $flow_actions_array );
			$loop_entry->set_status( $status );
			$loop_entry->set_error_message( $err_message );
			$loop_entry->set_process_date_started( current_time( 'mysql' ) );
			$loop_entry->set_process_date_ended( current_time( 'mysql' ) );
			$loop_entry->set_recipe_id( absint( $args['recipe_id'] ) );
			$loop_entry->set_recipe_log_id( absint( $args['recipe_log_id'] ) );
			$loop_entry->set_run_number( absint( $args['run_number'] ) );

			// Save the model.
			$loop_query = new Loop_Entry_Query();

			$loop_query->save( $loop_entry );

		} catch ( \Exception $e ) {

			$this->update_existing_entry( $err_message, $args, $loop_id );

		}
	}

	/**
	 * Completes the Loop entry.
	 *
	 * @param array{id:int,recipe_id:int,recipe_log_id:int,run_number:int,filter_id:string} $loop
	 * @param string $transient_key
	 *
	 * @return int|WP_Error|boolean Returns Automator_Status code if batch is still in progress, WP_Error if something went wrong, true if success.
	 */
	public function complete_entry( $loop, $transient_key ) {

		$loops_db         = Automator()->loop_db();
		$loop_entry_query = new Loop_Entry_Query();

		$loop_id       = absint( $loop['id'] );
		$recipe_id     = absint( $loop['recipe_id'] );
		$recipe_log_id = absint( $loop['recipe_log_id'] );
		$run_number    = absint( $loop['run_number'] );

		// Bail if either of this things are missing.
		if ( empty( $loop_id ) || empty( $recipe_id ) || empty( $recipe_log_id ) || empty( $run_number ) ) {
			return new WP_Error( 500, 'Missing required parameters' );
		}

		$loop_entry = $loop_entry_query->find_by_process( $loop_id, $recipe_id, $recipe_log_id, $run_number );

		if ( false === $loop_entry ) {
			return new WP_Error( 404, 'Cannot find entry by process' );
		}

		// Get the total number of distinct users added in the items table relative to recipe run.
		$num_processed_items = $loops_db->find_loop_items_completed_count( $loop_id, $loop );

		// If total number of entries in the loop entry table matches the number of distinct users. Then loop is completed.
		$all_users_action_completed = intval( $loop_entry->num_entities() ) === intval( $num_processed_items );

		if ( false === $all_users_action_completed ) {
			return Automator_Status::IN_PROGRESS;
		}

		$status = Automator_Status::get_class_name( Automator_Status::COMPLETED );

		$loop_entry->set_status( $status );
		$loop_entry->set_process_date_ended( current_time( 'mysql' ) );

		$loop_entry_query->update( $loop_entry );

		delete_transient( $transient_key );

		// Mark the recipe as in-progress or completed.
		Recipe\Process::complete_recipe( $recipe_id, $recipe_log_id, $run_number );

		self::process_next( $loop );

		return true;
	}

	/**
	 * Completes the loop entry on exit.
	 *
	 * @param mixed[] $loop_item
	 * @param string $transient_key
	 *
	 * @return void
	 */
	public function complete_entry_on_exit( $loop, $transient_key ) {

		$loop_query = new Loop_Entry_Query();

		$loop_entry = $loop_query->find_by_process(
			$loop['id'] ?? 0,
			$loop['recipe_id'] ?? 0,
			$loop['recipe_log_id'] ?? 0,
			$loop['run_number'] ?? 0
		);

		if ( false === $loop_entry ) {
			return;
		}

		$loop_entry->set_status( 'ended' );
		$loop_entry->set_process_date_ended( current_time( 'mysql' ) );

		$loop_query->update( $loop_entry );

		delete_transient( $transient_key );

		self::process_next( $loop );
	}

	/**
	 * @param int $recipe_log_id
	 *
	 * @return void
	 */
	protected function complete_recipe( $recipe_log_id ) {

		Automator()->db->recipe->mark_complete( $recipe_log_id, Automator_Status::COMPLETED );
	}

	/**
	 * Replaces the loop entry status in loop's log
	 *
	 * @param mixed[] $status
	 * @param mixed[] $params
	 * @param int $loop_id
	 * @param string $flow
	 *
	 * @return mixed[]
	 */
	public function replace_status( $status, $params, $loop_id, $flow ) {

		$registry = Loops_Process_Registry::get_instance();

		$args = wp_parse_args(
			$params,
			array(
				'recipe_id'     => 0,
				'recipe_log_id' => 0,
				'run_number'    => 0,
			)
		);

		$process_id = $registry::generate_process_id_manual( $loop_id, $args['recipe_id'], $args['recipe_log_id'], $args['run_number'] );

		$process = $registry->get_object( $process_id );

		if ( false === $process ) {
			return $status;
		}

		// Background process object deletes the cancelled status so we cnnot use ->is_cancelled() here.
		$loops_query = new Loop_Entry_Query();

		$process = $loops_query->find_entry_by_process_id( $process_id );

		if ( false !== $process ) {
			$status['status_id'] = $process->get_status();
		}

		return $status;
	}

	/**
	 * @param array{filter_id:string} $loop
	 *
	 * @return void;
	 */
	public static function process_next( $loop ) {

		$mq = new Loop_MQ();

		// Delete the current item in queue.
		$mq->remove( $loop['filter_id'] );

		$next_loop = $mq->get_next_item();

		if ( false !== $next_loop ) {

			$registry = Loops_Process_Registry::get_instance();

			$registry->spawn_process( $next_loop['process_id'] );

			( new Loop_Entry_Query() )->mark_process_as( 'in-progress', $next_loop['process_id'] );

			$loop_process = $registry->get_object( $next_loop['process_id'] );

			if ( false === $loop_process ) {
				return;
			}

			$loop_process->dispatch();

		}
	}

	/**
	 * Completes a loop action on behalf of the given user.
	 *
	 * @param int $user_id The user ID.
	 * @param int $entity_id The entity ID.
	 * @param int $action_id The action ID.
	 * @param mixed[] $args The process args.
	 * @param mixed[] $loop_item The loop item to process.
	 *
	 * @return void
	 */
	public function complete_loop_action( $user_id, $entity_id, $action_id, $args, $loop_item ) {

		$automator_action_processor = Automator_Recipe_Process_Complete::get_instance();

		$action_data = array(
			'ID'          => $action_id,
			'post_status' => 'publish',
			'meta'        => Utilities::flatten_post_meta( (array) get_post_meta( $action_id ) ),
		);

		$loop_data = array(
			'user_id'   => $user_id,
			'entity_id' => $entity_id,
			'action_id' => $action_id,
			'loop_item' => $loop_item,
		);

		$action_data['loop'] = $loop_data;

		/**
		 * Set 'loop' attributes to main process args. Action tokens needs this information.
		 *
		 * Legacy actions adds the loop data in 'action_data'. We need to make sure to 'set' loop
		 * in the process args so that legacy actions can understand it.
		 *
		 * @since 5.0.2
		 */
		$args['loop'] = $loop_data;

		/**
		 * @since 6.0 - Register the parser per action run as action can be delay in the future.
		 */
		if ( isset( $loop_item['type'] ) && 'token' === $loop_item['type'] ) {

			$action_id = $this->get_action_id_from_iterable_expression( $loop_item );

			// Only register the action hooks if its action token and there is action id.
			if ( ! empty( $action_id ) ) {

				$action_meta = Utilities::flatten_post_meta( get_post_meta( $action_id ) );
				$action      = Automator()->get_action( $action_meta['code'] );

				if ( ! empty( $action['loopable_tokens'] ) && is_array( $action['loopable_tokens'] ) ) {
					foreach ( (array) $action['loopable_tokens'] as $id => $class ) {
						$token_class = new $class( $action_id );
						$token_class->register_child_parser_hooks();
					}
				}
			}
		}

		try {
			$args['process_recipe_completion'] = false;
			$automator_action_processor->complete_action( $action_data, $args['recipe_id'], $user_id, $args['recipe_log_id'], $args );
		} catch ( \Error $e ) {
			$this->complete_with_error( $action_data, $args, $loop_item, 'Error: ' . $e->getMessage() );
		} catch ( \Exception $e ) {
			$this->complete_with_error( $action_data, $args, $loop_item, 'Exception: ' . $e->getMessage() );
		}
	}

	/**
	 * @param array{id:int} $loop_item
	 *
	 * @return void
	 */
	protected function get_action_id_from_iterable_expression( $loop_item ) {

		$iterable_expression = get_post_meta( absint( $loop_item['id'] ), 'iterable_expression', true );
		$fields              = $iterable_expression['fields'] ?? '';
		$fields_decoded      = (array) json_decode( $fields, true );

		$value = $fields_decoded['TOKEN']['value'] ?? '';

		$action_id = $this->extract_id_from_text( $value );

		return absint( $action_id );
	}

	/**
	 * Extracts the action id from the token. Go absint to convert into int.
	 *
	 * @param mixed $text
	 *
	 * @return string|null
	 */
	private function extract_id_from_text( $text ) {

		// Define a regular expression to match the ID (digits after ACTION_TOKEN:)
		$pattern = '/ACTION_TOKEN:(\d+)/';

		// Apply the regex to find the ID
		if ( preg_match( $pattern, $text, $matches ) ) {
			// Return the first captured group, which is the ID
			return $matches[1];
		}

		// Return null if no ID is found
		return null;
	}


	/**
	 * @param mixed[] $action_data
	 * @param mixed[] $args
	 * @param mixed[] $loop_item
	 * @param string $err_message
	 *
	 * @return void
	 */
	protected function complete_with_error( $action_data, $args, $loop_item, $err_message ) {

		$automator_action_processor = Automator_Recipe_Process_Complete::get_instance();

		$action_data['complete_with_errors'] = true;

		$args['action_data']['loop']['loop_item'] = $loop_item; // @phpstan-ignore-line
		$args['process_recipe_completion']        = false; // @phpstan-ignore-line

		$automator_action_processor->action( $args['user_id'], $action_data, $loop_item['recipe_id'], $err_message, $loop_item['recipe_log_id'], $args );
	}

	/**
	 * Determines whether a specific recipe loop is running.
	 *
	 * @param bool $has_loop_running
	 * @param int $recipe_id
	 *
	 * @return bool
	 */
	public function has_loop_running( $has_loop_running, $recipe_id ) {

		$loops = Automator()->loop_db()->find_recipe_loops( $recipe_id );

		$loop_is_proccessing = array();

		$background_process = \Uncanny_Automator_Pro\Loops_Process_Registry::get_instance();

		foreach ( $loops as $loop ) {

			if ( ! isset( $loop['process_id'] ) ) {
				continue;
			}

			$background_process_object = $background_process->get_object( $loop['process_id'] );

			if ( ! empty( $background_process_object ) && is_object( $background_process ) && method_exists( $background_process_object, 'is_processing' ) ) {
				$loop_is_proccessing[] = $background_process_object->is_active();
			}
		}

		// Collects all loops that are are processing.
		$a_loop_is_processing = array_filter(
			$loop_is_proccessing,
			function ( $is_processing ) {
				return true === $is_processing;
			}
		);

		// If there is a loop that is in process. It will return true.
		return ! empty( $a_loop_is_processing );
	}

	/**
	 * @param array{ID:int,error_message:string,completed:int,loop:null|array{user_id:int,action_id:int,loop_item:array{id:int,recipe_id:int,recipe_log_id:int,run_number:int}}} $action_data
	 *
	 * @return void
	 */
	public function complete_scheduled_actions( $action_data ) {

		// Bail if not loop.
		if ( ! isset( $action_data['loop'] ) ) {
			return;
		}

		$status_code = $action_data['completed'];

		$loop_item = $action_data['loop']['loop_item'];

		$user_id           = absint( $action_data['loop']['user_id'] );
		$loop_id           = absint( $loop_item['id'] );
		$recipe_id         = absint( $loop_item['recipe_id'] );
		$action_id         = absint( $action_data['ID'] );
		$recipe_log_id     = absint( $loop_item['recipe_log_id'] );
		$recipe_run_number = absint( $loop_item['run_number'] );

		$query = new Loop_Entry_Item_Query();

		$entry_item = $query->find_by_action_process(
			$user_id,
			$action_id,
			$loop_id,
			$recipe_id,
			$recipe_log_id,
			$recipe_run_number
		);

		if ( false === $entry_item ) {
			return;
		}

		$entry_item->set_status( Automator_Status::get_class_name( $status_code ) );
		$entry_item->set_error_message( $action_data['error_message'] );

		// Update the item.
		$query->update( $entry_item );

		// Marks the loop entry as completed.
		$this->loop_entry_mark_completed( (array) $loop_item );
	}

	/**
	 * Marks the loop entry as completed
	 *
	 * @param mixed[] $loop_item
	 *
	 * @return bool True if completed. Otherwise, false.
	 */
	protected function loop_entry_mark_completed( $loop_item ) {

		$loop_db = Automator()->loop_db();

		$loop_item = wp_parse_args(
			array(
				'id'            => $loop_item['id'],
				'recipe_id'     => $loop_item['recipe_id'],
				'recipe_log_id' => $loop_item['recipe_log_id'],
				'run_number'    => $loop_item['run_number'],
			)
		);

		$loop_has_item_in_progress = $loop_db->loop_has_in_progress_item(
			$loop_item['id'],
			$loop_item['recipe_id'],
			$loop_item['recipe_log_id'],
			$loop_item['run_number']
		);

		// Bail if item is still in progress.
		if ( $loop_has_item_in_progress ) {
			return false;
		}

		return true;
	}

	/**
	 * This method only updates the entry_item in the DB to copy the hydrated tokens if there is.
	 *
	 * @param mixed[] $action_tokens
	 * @param array{action_data:array{loop: null|array{user_id:int,action_id:int,loop_item:array{id:int,recipe_id:int,recipe_log_id:int,run_number:int}}}} $args
	 * @param Store|object $store Refers to the instance of action token store or the legacy support for action instance.
	 *
	 * @return mixed[] The action tokens.
	 */
	public function hydrate_action_tokens( $action_tokens, $args, $store ) {

		// Supports loop.
		if ( isset( $args['action_data']['loop'] ) ) {

			$action_tokens['should_skip_add_meta'] = true;

			$loop_args = $args['action_data']['loop'];

			$loop_item_query = new Loop_Entry_Item_Query();

			$loop_entry = $loop_item_query->find_by_action_process(
				$loop_args['user_id'],
				$loop_args['action_id'],
				$loop_args['loop_item']['id'],
				$loop_args['loop_item']['recipe_id'],
				$loop_args['loop_item']['recipe_log_id'],
				$loop_args['loop_item']['run_number']
			);

			if ( empty( $loop_entry ) ) {
				return $action_tokens;
			}

			$hydrated_values = $this->hydrate_loop_action_token( $store, $loop_entry );

			// Grab the hydrated tokens replace pairs and persist it.
			$loop_entry->set_action_tokens( $hydrated_values );

			// Just update the entry item query.
			$loop_item_query->update( $loop_entry );

		}

		return $action_tokens;
	}

	/**
	 * Hydrate loop action token.
	 *
	 * @param object $action Refers to the current action that is running.
	 * @param Loop_Entry_Item_Model $loop_entry
	 *
	 * @return string
	 */
	private function hydrate_loop_action_token( $store, Loop_Entry_Item_Model $loop_entry ) {

		$is_store = false !== strpos( get_class( $store ), 'Action\\Token\\Store' );

		// If the class is coming from new Store class, support default action tokens for Loops.
		if ( $is_store ) {

			// Retrieve the action run args.
			$action_args = maybe_unserialize( $loop_entry->get_action_data() );

			// The default action tokens.
			$default_values = array(
				'ACTION_RUN_STATUS' => Automator_Status::name( $action_args['action_data']['completed'] ),
			);

			// Hydrate the token using the new method.
			$action_token_value = (array) json_decode( $store->get_key_value_pairs(), true );

			return wp_json_encode( array_merge( $default_values, $action_token_value ) );

		}

		// Otherwise, it means the user updated pro and left free behind. So use the legacy method.
		$action_token_value = (array) json_decode( $store->get_hydrated_tokens_replace_pairs(), true );

		// If free is out of date. It means, it does not support the default values so just send the action token result.
		return wp_json_encode( $action_token_value );
	}

	/**
	 * @param string $token_value
	 * @param int $action_id
	 * @param array{loop: null|array{user_id:int,loop_item:array{id:int,recipe_id:int,recipe_log_id:int,run_number:int}}} $process_args
	 *
	 * @return string
	 */
	public function parse_action_tokens_meta( $token_value, $action_id, $process_args ) {

		if ( ! isset( $process_args['loop'] ) ) {
			return $token_value;
		}

		$loop_item_query = new Loop_Entry_Item_Query();

		$loop = $process_args['loop'];

		$loop_entry_item = $loop_item_query->find_by_action_process(
			$loop['user_id'],
			$action_id,
			$loop['loop_item']['id'],
			$loop['loop_item']['recipe_id'],
			$loop['loop_item']['recipe_log_id'],
			$loop['loop_item']['run_number']
		);

		if ( false === $loop_entry_item ) {
			return $token_value;
		}

		return $loop_entry_item->get_action_tokens();
	}

	/**
	 * Parses action tokens fields that are inside a loop.
	 *
	 * @param mixed[] $unserialized_data
	 * @param int $action_id
	 * @param array{loop: null|array{user_id:int,loop_item:array{id:int,recipe_id:int,recipe_log_id:int,run_number:int}}} $process_args
	 *
	 * @return mixed[]
	 */
	public function parse_action_tokens_fields( $unserialized_data, $action_id, $process_args ) {

		if ( ! isset( $process_args['loop'] ) ) {
			return $unserialized_data;
		}

		$loop_item_query = new Loop_Entry_Item_Query();

		$loop = $process_args['loop'];

		$loop_entry_item = $loop_item_query->find_by_action_process(
			$loop['user_id'],
			$action_id,
			$loop['loop_item']['id'],
			$loop['loop_item']['recipe_id'],
			$loop['loop_item']['recipe_log_id'],
			$loop['loop_item']['run_number']
		);

		if ( false === $loop_entry_item ) {
			return $unserialized_data;
		}

		$metas = (array) maybe_unserialize( $loop_entry_item->get_action_data() );

		$action_data_meta = is_array( $metas['action_data'] ) && isset( $metas['action_data']['meta'] )
			? (array) $metas['action_data']['meta'] : array();

		$meta_items = array();

		foreach ( $action_data_meta as $key => $value ) {

			// This is how Automator saves the fields. In a serialize array of objects.
			$action_meta = new \stdClass();

			$action_meta->meta_key   = $key;
			$action_meta->meta_value = $value;

			$meta_items[] = $action_meta;

		}

		return $meta_items;
	}

	/**
	 * @param int $status See Automator_Status.
	 * @param mixed[] $args The process args.
	 *
	 * @return int The status ID.
	 */
	public function intercept_recipe_process_complete_status( $status, $args ) {

		// Early bail with original status if any of these returns empty or falsy.
		if ( empty( $args['recipe_id'] ) || empty( $args['recipe_log_id'] ) || empty( $args['run_number'] ) ) {
			return $status;
		}

		// Do not intercept recipes without loops.
		$recipe_loops = Automator()->loop_db()->find_recipe_loops( $args['recipe_id'] );
		// Bail if there are no loops.
		if ( empty( $recipe_loops ) ) {
			return $status;
		}

		$recipe_id     = absint( $args['recipe_id'] );
		$recipe_log_id = absint( $args['recipe_log_id'] );
		$run_number    = absint( $args['run_number'] );

		return Recipe\Process::resolve_recipe_status( $recipe_id, $recipe_log_id, $run_number );
	}

	/**
	 * Use the process ID as group.
	 *
	 * @param string $original_group
	 * @param mixed[] $args
	 *
	 * @return string
	 */
	public function use_process_id_as_async_group( $original_group, $args ) {

		if ( empty( $args['action_data'] ) ) {
			return $original_group;
		}

		$action_data = (array) $args['action_data'];

		if ( ! isset( $action_data['loop'] ) ) {
			return $original_group;
		}

		$loop = (array) $action_data['loop'];

		if ( is_array( $loop['loop_item'] ) && isset( $loop['loop_item']['filter_id'] ) ) {
			return $loop['loop_item']['filter_id'];
		}

		return $original_group;
	}

	/**
	 * Utility function to encode input into associative array.
	 *
	 * @param mixed $input The input.
	 *
	 * @return string
	 */
	public static function json_encode( $input ) {

		$data = wp_json_encode( $input );

		if ( false === $data ) {
			return '';
		}

		return $data;
	}
}
