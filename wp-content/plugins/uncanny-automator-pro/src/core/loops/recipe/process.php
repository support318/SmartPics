<?php
namespace Uncanny_Automator_Pro\Loops\Recipe;

use Uncanny_Automator\Automator_Recipe_Process_Complete;
use Uncanny_Automator\Automator_Status;
use Uncanny_Automator\Services\Recipe\Structure;
use Uncanny_Automator_Pro\Automator_Pro_Recipe_Process_Complete;
use Uncanny_Automator_Pro\Loops\Loop;
use Uncanny_Automator_Pro\Utilities;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Entry_Model;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Query;
use Uncanny_Automator_Pro\Loops\Loop\Model\Query\Loop_Entry_Item_Query;

class Process {

	/**
	 * Trait_Process contains the properties, setter, and getter for this Process object.
	 */
	use Trait_Process;

	/**
	 * @var Automator_Recipe_Process_Complete
	 */
	protected $recipe_process_complete_class;

	public function __construct() {
		$this->recipe_process_complete_class = Automator_Recipe_Process_Complete::get_instance();
	}

	/**
	 * Completes a specific action from the flow action item.
	 *
	 * @param mixed[] $action_item
	 *
	 * @return false|void
	 */
	public function complete_action( $action_item ) {

		return $this->recipe_process_complete_class->complete_action(
			self::generate_action_data( $action_item ),
			$this->get_recipe_id(),
			$this->get_user_id(),
			$this->get_recipe_log_id(),
			$this->get_process_args()
		);

	}

	/**
	 * Complete all actions with conditions.
	 *
	 * @param mixed[] $action_item
	 *
	 * @return void
	 */
	public function complete_actions_conditions( $action_item ) {

		$condition_actions = (array) $action_item['items'];
		// We have to iterate the conditions recipe flow and process each action
		// one by one. In the future, we can evaluate the conditions even before
		// iterating the actions that belongs to it.
		foreach ( $condition_actions as $condition_action_item ) {
			// Process individual action condition.
			$this->recipe_process_complete_class->complete_action(
				self::generate_action_data( (array) $condition_action_item ),
				$this->get_recipe_id(),
				$this->get_user_id(),
				$this->get_recipe_log_id(),
				$this->get_process_args()
			);
		}

	}

	/**
	 * Completes all of the loops inside a recipe.
	 *
	 * @param Structure $recipe_structure
	 * @param array{items:mixed[],id:int,loopable_expression:array{type:string}} $action_item
	 * @param int $user_id The recipe USER ID.
	 *
	 * @return void
	 */
	public function run_loops( Structure $recipe_structure, $action_item, $user_id ) {

		$exe = new Loop\Execute();

		$exe->set_recipe_structure( $recipe_structure );
		$exe->set_recipe_id( $this->get_recipe_id() );
		$exe->set_recipe_log_id( $this->get_recipe_log_id() );
		$exe->set_process_args( $this->get_process_args() );
		$exe->set_loop_id( absint( $action_item['id'] ) );
		$exe->set_user_id( $user_id );

		// Get a copy of triggers.
		$triggers = $recipe_structure->get( 'triggers' )->get( 'items' );

		$exe->run_loop( $action_item, $triggers );

	}

	/**
	 * Generates action data compatible with automator process args.
	 *
	 * @param mixed[] $action
	 *
	 * @return mixed[]
	 */
	public static function generate_action_data( $action ) {

		$action_id = $action['id'];

		return array(
			'ID'          => $action_id,
			'post_status' => 'publish',
			'meta'        => Utilities::flatten_post_meta( (array) get_post_meta( absint( $action_id ) ) ),
		);

	}

	/**
	 * @param Structure $recipe_structure
	 *
	 * @return object The 'items' object from Recipe Structure.
	 */
	public function retrieve_flow_actions_items( Structure $recipe_structure ) {

		$recipe_flow_actions = $recipe_structure->retrieve()->get( 'actions' );

		$items = $recipe_flow_actions->get( 'items' );

		return $items;
	}

	/**
	 * Portable function complete_recipe
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $run_number
	 *
	 * @return void
	 */
	public static function complete_recipe( $recipe_id, $recipe_log_id, $run_number ) {

		$status = self::resolve_recipe_status( $recipe_id, $recipe_log_id, $run_number );

		Automator()->db->recipe->mark_complete( $recipe_log_id, $status );

	}

	/**
	 * Resolves the recipe status.
	 *
	 * The status will be marked as in-progress when any of the following is true:
	 *
	 * - Multiple loop entries in a single recipe and the next recipe is still in progress.
	 * - If the current loop has still items in progress (e.g. delayed).
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $run_number
	 *
	 * @return int See Automator_Status.
	 */
	public static function resolve_recipe_status( $recipe_id, $recipe_log_id, $run_number ) {

		$entry_query = new Loop_Entry_Query();

		$results = $entry_query->find_by_recipe_process( $recipe_id, $recipe_log_id, $run_number );

		$status = Automator_Status::COMPLETED;

		// If there are multiple loops in a single recipe.
		if ( self::loop_entries_has_inprogress_state( $results ) ) {
			$status = Automator_Status::IN_PROGRESS;
		}

		// If the current loop process has items in-progress remaining (scheduled/delayed ).
		if ( self::loop_entries_items_has_inprogress_state( $recipe_id, $recipe_log_id, $run_number ) ) {
			$status = Automator_Status::IN_PROGRESS;
		}

		// If the recipe's root actions has in-progress status.
		if ( self::recipe_has_in_progress_actions( $recipe_id, $recipe_log_id ) ) {
			$status = Automator_Status::IN_PROGRESS;
		}

		return $status;

	}

	/**
	 * Determines whether the recipe has still items that are in progress.
	 *
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 *
	 * @return bool True if the specific recipe run has in-progress action. Otherwise, returns false.
	 */
	public static function recipe_has_in_progress_actions( $recipe_id, $recipe_log_id ) {

		$results = Automator_Pro_Recipe_Process_Complete::fetch_specific_recipe_log_by_status(
			$recipe_id,
			$recipe_log_id,
			Automator_Status::IN_PROGRESS
		);

		return ! empty( $results );
	}

	/**
	 * @param int $recipe_id
	 * @param int $recipe_log_id
	 * @param int $run_number
	 *
	 * @return bool True if there are items that are marked in-progress in the entries items table. Returns false, otherwise.
	 */
	public static function loop_entries_items_has_inprogress_state( $recipe_id, $recipe_log_id, $run_number ) {

		$loop_entries_items_query = new Loop_Entry_Item_Query();

		return $loop_entries_items_query->has_in_progress( $recipe_id, $recipe_log_id, $run_number );

	}

	/**
	 * Portable function loop_entries_has_inprogress_state
	 *
	 * Determines whether the loop entries has 'in_progress' state.
	 *
	 * @param Loop_Entry_Model[] $loop_entries
	 *
	 * @return bool True or false.
	 *
	 * @phpstan-ignore-next-line
	 */
	public static function loop_entries_has_inprogress_state( $loop_entries ) {

		$processing_state = array(
			Automator_Status::get_class_name( Automator_Status::IN_PROGRESS ),
			Automator_Status::get_class_name( Automator_Status::QUEUED ),
		);

		foreach ( (array) $loop_entries as $loop_entry ) {
			if ( in_array( $loop_entry->get_status(), $processing_state, true ) ) {
				return true;
			}
		}

		return false;

	}

}
