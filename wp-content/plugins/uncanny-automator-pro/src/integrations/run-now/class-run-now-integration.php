<?php
namespace Uncanny_Automator_Pro\Integrations\Run_Now;

use Uncanny_Automator\Automator_Status;

/**
 * Class Run_Now_Integration
 *
 * @package Uncanny_Automator_Pro
 *
 * @since 5.1
 */
class Run_Now_Integration extends \Uncanny_Automator\Integration {

	/**
	 * Setup Automator integration.
	 *
	 * @return void
	 */
	protected function setup() {

		$this->helpers = new Run_Now_Helpers();

		$this->set_integration( 'Run_Now' );

		$this->set_name( 'Run now' );

		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/run-now-icon.svg' );

	}

	/**
	 * Load Integration Classes.
	 *
	 * @return void
	 */
	public function load() {

		$this->load_action_hooks();

		// Loads the everyone type counter part.
		new RECIPE_MANUAL_TRIGGER_ANON( $this->helpers );

	}

	/**
	 * Loads action hooks.
	 *
	 * @return void
	 */
	protected function load_action_hooks() {

		add_filter( 'automator_recipe_main_object\structure\miscellaneous', array( $this, 'misc_add_properties' ), 10, 2 );

		add_action( 'automator_recipe_trigger_created', array( $this, 'publish_recipe' ), 10, 3 );

	}

	/**
	 * Publishes the recipe when the trigger "" is added.
	 *
	 * @param int $trigger_id
	 * @param string $item_code
	 * @param \WP_REST_Request $request
	 * @return void
	 */
	public function publish_recipe( $trigger_id, $item_code, $request ) {

		if ( 'RECIPE_MANUAL_TRIGGER_ANON' !== $item_code ) {
			return;
		}

		$recipe_id = 0;

		if ( ! empty( $request->get_param( 'parent_id' ) ) ) {

			$recipe_id = $request->get_param( 'parent_id' );

			// Update the recipe.
			wp_update_post(
				array(
					'ID'          => absint( $recipe_id ),
					'post_status' => 'publish',
				),
				true
			);

			// Update the trigger.
			wp_update_post(
				array(
					'ID'          => absint( $trigger_id ),
					'post_status' => 'publish',
				),
				true
			);
		}

	}

	/**
	 * Sets several new properties.
	 *
	 * The property "has_run_now" determines whether it has a run_now recipe or not.
	 *
	 * @param \Uncanny_Automator\Services\Recipe\Structure\Miscellaneous $miscellaneous
	 * @param \Uncanny_Automator\Services\Recipe\Structure $recipe_object
	 *
	 * @return mixed
	 */
	public function misc_add_properties( $miscellaneous, $recipe_object ) {

		$recipe_id = $recipe_object->get_recipe_id();

		$status = self::fetch_recipe_status( $recipe_id );

		if ( method_exists( '\Uncanny_Automator\Services\Recipe\Structure\Miscellaneous', 'set' ) ) {

			$has_run_now = $this->recipe_has_run_now_trigger( $recipe_id );

			if ( false === $has_run_now ) {
				return $miscellaneous;
			}

			$miscellaneous->set( 'has_run_now', $has_run_now );
			$miscellaneous->set( 'recipe_is_running', Automator_Status::IN_PROGRESS === $status );

			$can_edit = true;

			// Make sure to only apply if has run now trigger.
			if ( true === $has_run_now && true === $miscellaneous->get( 'recipe_is_running' ) ) {
				$can_edit = false;
			}

			$miscellaneous->set( 'can_edit', $can_edit );
			$miscellaneous->set( 'latest_recipe_log_status', Automator_Status::get_class_name( $status ) );
		}

		return $miscellaneous;

	}

	/**
	 * Determines whether the recipe has run now trigger.
	 *
	 * @param int $recipe_id
	 *
	 * @return bool True if the recipe has run now Trigger. Returns false, otherwise.
	 */
	protected function recipe_has_run_now_trigger( $recipe_id ) {

		return ! empty( $this->fetch_recipe_run_now_trigger( $recipe_id ) );

	}

	/**
	 * Fetches all Triggers having "RECIPE_MANUAL_TRIGGER_ANON" as trigger code in a specific recipe.
	 *
	 * @param int $recipe_id
	 *
	 * @return string[]
	 */
	protected function fetch_recipe_run_now_trigger( $recipe_id ) {

		global $wpdb;

		$results = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} AS post 
					INNER JOIN {$wpdb->postmeta} AS meta 
						ON meta.post_id = post.ID
					WHERE meta.meta_key = %s AND meta.meta_value = %s
						AND post.post_parent = %d",
				'code',
				'RECIPE_MANUAL_TRIGGER_ANON',
				$recipe_id
			),
			ARRAY_A
		);

		return $results;

	}

	/**
	 * Fetches the recipe status from the database using the recipe ID.
	 *
	 * The status will be based on the most recent log.
	 *
	 * @param int $recipe_id
	 *
	 * @return int The recipe status.
	 */
	public static function fetch_recipe_status( $recipe_id ) {

		global $wpdb;

		$results = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, completed from {$wpdb->prefix}uap_recipe_log WHERE automator_recipe_id = %d ORDER by ID DESC",
				$recipe_id
			),
			ARRAY_A
		);

		// If there are no results, maybe recipe is still running?
		// Return not completed.
		if ( empty( $results ) || ! isset( $results[0]['completed'] ) ) {
			return Automator_Status::NOT_COMPLETED;
		}

		return absint( $results[0]['completed'] );

	}

	/**
	 * Run_Now integration has no external dependencies.
	 *
	 * @return true
	 */
	public function plugin_active() {

		return true;

	}

}
