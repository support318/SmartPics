<?php
namespace Uncanny_Automator_Pro\Loops;

use Exception;
use Uncanny_Automator\Automator_Recipe_Process_Complete;
use Uncanny_Automator\Automator_Status;

use Uncanny_Automator_Pro\Loops\Loop;
use Uncanny_Automator_Pro\Loops_Process_Registry;
use Uncanny_Automator\Services\Recipe;
use Uncanny_Automator_Pro\Loops\Recipe\Process as Recipe_Process;
use WP_REST_Request;

/**
 * Class Entry_Point.
 *
 * This is a singleton class.
 *
 * This class serves as an entry point for the loops process.
 *
 * @since 5.0
 */
final class Entry_Point {

	/**
	 * The instance of this class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Retrieves the instance.
	 *
	 * @return self
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;

	}

	/**
	 * Registering the required callbacks from various Automator's action hooks.
	 *
	 * @return void
	 */
	protected function __construct() {

		// Hooks to 'automator_trigger_completed' action hook to determine whether to process the recipe as loop or not.
		add_action( 'automator_trigger_completed', array( $this, 'flag_recipe_as_loop' ), 10, 1 ); /** @phpstan-ignore-line Action callback returns bool but should not return anything. */

		// Hooks to 'automator_actions_completed_run_flow' to process the recipe as flow. See automator_trigger_completed@flag_recipe_as_loop.
		add_action( 'automator_actions_completed_run_flow', array( $this, 'process_as_flow' ), 10, 5 );

		// Update the action status to 'completed' from 'processing'.
		add_action( 'automator_action_created', array( $this, 'complete_loop_entry_item_status' ), 10, 1 );

		// Hooks to 'automator_action_created' to complete the status of a single action entry.
		add_filter( 'automator_integration_loop_filters', array( $this, 'integration_insert_loop_filters' ), 10, 1 );

		// Registeres process controller endpoint.
		add_action( 'rest_api_init', array( $this, 'register_process_controller' ), 10, 1 );

		// Handle recipe log deletion.
		add_action( 'automator_recipe_log_deleted', array( $this, 'purge_recipe_run_loops_process' ), 10, 3 );

		// Handle purge requests.
		add_action( 'automator_tables_purged', array( $this, 'purge_all_process' ) );

		// Disable background actions. Everything runs in a background already.
		add_filter( 'automator_action_should_process_in_background', array( $this, 'disable_background_actions' ), 10, 2 );

	}

	/**
	 * Disable background processing for actions that are in loop.
	 *
	 * @param bool $should_be_process_as_bg
	 * @param mixed[] $action
	 *
	 * @return bool
	 */
	public function disable_background_actions( $should_be_process_as_bg, $action ) {

		// If action is iniside the loop, return false immediately.
		if ( is_array( $action['action_data'] ) && isset( $action['action_data']['loop'] ) ) {
			return false;
		}

		return $should_be_process_as_bg;

	}

	/**
	 * Purges all processes.
	 *
	 * @return void
	 */
	public function purge_all_process() {

		$registry = Loops_Process_Registry::get_instance();

		$processes = $registry->get_processes();

		if ( ! empty( $processes ) ) {
			foreach ( $processes as $proc_id => $process ) {
				// Gracefully delete all processes.
				$process->delete_all();
				// Dlete th transient as well.
				delete_transient( $proc_id . '_transaction_transient' );
			}
		}

		// Truncate individual tables.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}uap_loop_entries" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}uap_loop_entries_items" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}uap_queue" );

		// Delete all wp_options process. This would safely delete stale processes as well.
		$loops_process = '%' . $wpdb->esc_like( 'uap_loops_loop_process_' ) . '%';

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
				$loops_process
			)
		);

	}

	/**
	 * Purges recipe run loops process
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $recipe_run_number
	 *
	 * @return void
	 */
	public function purge_recipe_run_loops_process( $recipe_id, $recipe_log_id, $recipe_run_number ) {

		$registry = Loops_Process_Registry::get_instance();

		$registry->delete_process( $recipe_id, $recipe_log_id, $recipe_run_number );

	}

	/**
	 * Injects the registered loop filters into the integration object.
	 *
	 * @param mixed[] $filters
	 *
	 * @return mixed[]
	 */
	public function integration_insert_loop_filters( $filters ) {

		return array_merge( $filters, automator_pro_loop_filters()->get_filters() );

	}

	/**
	 * Flags the recipe process as loop sending 'loop' when the action hook 'automator_trigger_completed' is invoked..
	 *
	 * @uses Automator filter 'automator_triggers_completed_run_flow_type' to set the flow type to 'loop'.
	 *
	 * @param mixed[] $process_further
	 *
	 * @return bool True if the recipe process was flagged as loop, otherwise false.
	 */
	public function flag_recipe_as_loop( $process_further ) {

		if ( ! isset( $process_further['recipe_id'] ) ) {
			return false;
		}

		// @improvement: get_post is slow, use simple query.
		$loops = get_posts(
			array(
				'post_parent' => absint( $process_further['recipe_id'] ),
				'post_type'   => 'uo-loop',
			)
		);

		if ( empty( $loops ) ) { // It means its a simple action.
			return false;
		}

		// Otherwise, tell Uncanny Automator to process the 'loop' flow type.
		add_filter(
			'automator_triggers_completed_run_flow_type',
			function() {
				return 'loop';
			}
		);

		return true;

	}

	/**
	 * Recipes without loops processes the actions in a linear way.
	 *
	 * For recipes that contains loops, we follow the recipe main object.
	 *
	 * @param string $flow_type
	 * @param int $recipe_id
	 * @param int $user_id
	 * @param int $recipe_log_id
	 * @param mixed[] $args
	 *
	 * @return void
	 */
	public function process_as_flow( $flow_type, $recipe_id, $user_id, $recipe_log_id, $args ) {

		if ( 'loop' !== $flow_type ) {
			return;
		}

		// Creates a new recipe process.
		$recipe_process = $this->create_recipe_process( $user_id, $recipe_id, $recipe_log_id, $args );

		// Retrieve the flow actions items from main recipe object.
		$recipe_structure = $this->create_recipe_structure( $recipe_id );

		// Retrieve the actions items object from main recipe object.
		$recipe_flow_actions_items = $recipe_process->retrieve_flow_actions_items( $recipe_structure );

		// Make sure action items is loopable.
		if ( ! is_iterable( $recipe_flow_actions_items ) ) {
			return;
		}

		// Recipe flow actions items are 'action', 'filter (action conditions)', 'loop'.
		foreach ( $recipe_flow_actions_items as $action_item ) {

			// Normal actions.
			if ( 'action' === $action_item['type'] ) {
				$recipe_process->complete_action( $action_item );
			}

			// Actions with action conditions.
			if ( 'filter' === $action_item['type'] ) {
				$recipe_process->complete_actions_conditions( $action_item );
			}

			if ( 'loop' === $action_item['type'] ) {
				$recipe_process->run_loops( $recipe_structure, $action_item, $user_id );
			}
		}

		// Invoke the closure action hook.
		do_action( 'automator_recipe_process_complete_complete_actions_before_closures', $recipe_id, $user_id, $recipe_log_id, $args );

		// Invoke the closure.
		Automator_Recipe_Process_Complete::get_instance()->closures( $recipe_id, $user_id, $recipe_log_id, $args );

	}

	/**
	 * Creates a new instance of Recipe\Structure from given Recipe ID.
	 *
	 * @param int $recipe_id
	 *
	 * @return Recipe\Structure
	 */
	protected function create_recipe_structure( $recipe_id ) {

		// With original field resolver structure set to true.
		$config = array(
			'fields'       => array(
				'show_original_field_resolver_structure' => true,
			),
			'publish_only' => true,
		);

		return new Recipe\Structure( $recipe_id, $config );

	}

	/**
	 * Creates a new instance of recipe process class.
	 *
	 * @param int $user_id
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param mixed[] $args The process args.
	 *
	 * @return Recipe_Process
	 */
	protected function create_recipe_process( $user_id, $recipe_id, $recipe_log_id, $args ) {

		// Create a new recipe process.
		$recipe_process = new Recipe_Process();
		// Hydrate the process object.
		$recipe_process->set_user_id( $user_id );
		// Set the recipe ID.
		$recipe_process->set_recipe_id( $recipe_id );
		// Set the recipe log ID.
		$recipe_process->set_recipe_log_id( $recipe_log_id );
		// Set the process args.
		$recipe_process->set_process_args( $args );

		return $recipe_process;

	}

	/**
	 * When the loop starts running the actions that are under it, initially, the action status is set to in-progress.
	 *
	 * This method is a callback to action hook 'automator_action_created' to complete the action of the loop.
	 *
	 * @param array{error_message:null|string,action_data:array{error_message:string,completed:int,loop:null|array{user_id:int,entity_id:int,action_id:int,loop_item:array{id:int,recipe_id:int,filter_id:string,recipe_log_id:int,run_number:int}}}} $args
	 *
	 * @return void
	 */
	public function complete_loop_entry_item_status( $args ) {

		$action_data = $args['action_data'];

		// Bail if from normal actions.
		if ( ! isset( $action_data['loop'] ) ) {
			return;
		}

		$entry_item = new Loop\Model\Loop_Entry_Item_Model();
		$record     = new Loop\Model\Query\Loop_Entry_Item_Query();

		$loop_item = $action_data['loop']['loop_item'];

		$serialized_args = maybe_serialize( $args );

		// Store only serialized arrays.
		if ( ! is_string( $serialized_args ) || is_numeric( $serialized_args ) ) {
			$serialized_args = '';
		}

		// Set status to the one that sent by Automator process args' action_data.
		$entry_item->set_status( Automator_Status::get_class_name( $action_data['completed'] ) );
		$entry_item->set_user( absint( $action_data['loop']['user_id'] ) );
		$entry_item->set_entity_id( absint( $action_data['loop']['entity_id'] ) );
		$entry_item->set_filter_id( $loop_item['filter_id'] );
		$entry_item->set_recipe_id( $loop_item['recipe_id'] );
		$entry_item->set_recipe_log_id( $loop_item['recipe_log_id'] );
		$entry_item->set_recipe_run_number( $loop_item['run_number'] );
		$entry_item->set_action_data( $serialized_args );
		$entry_item->set_action_id( absint( $action_data['loop']['action_id'] ) );

		$error_message = isset( $args['error_message'] ) ? $args['error_message'] : '';
		$entry_item->set_error_message( $error_message );

		// Save the record.
		try {

			$record->save( $entry_item );

		} catch ( \Exception $e ) {

			$force_log = defined( 'AUTOMATOR_DEBUG_MODE' ) ? AUTOMATOR_DEBUG_MODE : true;

			// @phpstan-ignore-next-line
			automator_log(
				array(
					'message'         => $e->getMessage(),
					'possible_reason' => 'Cannot save the entry in the database. Check if there are any missing tables. Run repair.',
					'args'            => $args,
				),
				'Loop iteration error',
				$force_log,
				'loop-iteration-error'
			);

		}

	}

	/**
	 * Registers various process controllers.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public function register_process_controller( $request ) {

		$process_controller = new Process_Controller();

		register_rest_route(
			'uap/v2',
			'/loop/process/(?P<action>\w+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $process_controller, 'handle' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'process_id' => array(
						'required' => true,
					),
				),
			)
		);

	}

	/**
	 * Prevents cloning of object.
	 *
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Prevents serialization of the object.
	 *
	 * @throws \Exception Cannot unserialize a singleton.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a singleton.' );
	}


}
