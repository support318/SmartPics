<?php
namespace Uncanny_Automator_Pro;

use Exception;
use LogicException;

/**
 * Class Recipe_Change_Status
 *
 * Handles the actions to change the status of a recipe (activate or deactivate)
 * within the Uncanny Automator Pro plugin.
 *
 * @package Uncanny_Automator_Pro
 */
class Recipe_Change_Status extends \Uncanny_Automator\Recipe\Action {

	/**
	 * Sets up the action properties for recipe status change.
	 *
	 * Initializes integration, action code, meta, and required properties,
	 * and sets the sentence and readable sentence for the action.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'UOA' );
		$this->set_action_code( 'RECIPE_CHANGE_STATUS_CODE' );
		$this->set_action_meta( 'RECIPE_CHANGE_STATUS_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		$this->set_sentence(
			sprintf(
				esc_attr__(
					'{{Deactivate or activate:%1$s}} {{a recipe:%2$s}}',
					'uncanny-automator-pro'
				),
				$this->get_action_meta(),
				'RECIPE_ID:' . $this->get_action_meta()
			)
		);

		$this->set_readable_sentence( esc_attr__( '{{Deactivate or activate}} {{a recipe}}', 'uncanny-automator-pro' ) );

	}

	/**
	 * Renders the available options for the recipe status action.
	 *
	 * Provides selection options for activating or deactivating a recipe, as well as a
	 * dropdown to choose a specific recipe. Supports AJAX for loading recipes dynamically.
	 *
	 * @return array Array of options for the action.
	 */
	public function options() {

		return array(
			array(
				'option_code'            => $this->get_action_meta(),
				'input_type'             => 'select',
				'label'                  => __( 'Status', 'uncanny-automator-pro' ),
				'relevant_tokens'        => array(),
				'required'               => true,
				'show_label_in_sentence' => false,
				'supports_custom_value'  => false,
				'options'                => array(
					array(
						'text'  => __( 'Activate', 'uncanny-automator-pro' ),
						'value' => 'activate',
					),
					array(
						'text'  => __( 'Deactivate', 'uncanny-automator-pro' ),
						'value' => 'deactivate',
					),
				),
			),
			array(
				'option_code'     => 'RECIPE_ID',
				'input_type'      => 'select',
				'label'           => __( 'Recipe', 'uncanny-automator-pro' ),
				'relevant_tokens' => array(),
				'required'        => true,
				'options'         => Uoa_Pro_Helpers::fetch_recipes(),
				'ajax'            => array(
					'endpoint' => 'automator_pro_recipe_change_status_action_recipe_field',
					'event'    => 'on_load',
				),
			),
		);

	}

	/**
	 * Defines tokens for the recipe status action.
	 *
	 * Provides a token for use in action sentences and processing.
	 *
	 * @return array Array of defined tokens.
	 */
	public function define_tokens() {
		return array(
			'RECIPE'           => array(
				'name' => _x( 'Recipe', 'Uncanny Automator', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
			'RECIPE_ID'        => array(
				'name' => _x( 'Recipe ID', 'Uncanny Automator', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			'RECIPE_TITLE'     => array(
				'name' => _x( 'Recipe title', 'Uncanny Automator', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			'RECIPE_EDIT_LINK' => array(
				'name' => _x( 'Recipe edit link', 'Uncanny Automator', 'uncanny-automator-pro' ),
				'type' => 'url',
			),
		);
	}

	/**
	 * Processes the recipe status change action.
	 *
	 * Executes the action of changing a recipe's status, either activating or deactivating it.
	 *
	 * @param mixed $user_id The user ID associated with the action.
	 * @param mixed $action_data Data relevant to the action being processed.
	 * @param mixed $recipe_id The ID of the recipe to process.
	 * @param mixed $args Additional arguments for processing.
	 * @param mixed $parsed Parsed arguments for the action.
	 *
	 * @throws Exception if there is an error during processing.
	 *
	 * @return bool Always returns true if action processing completes successfully.
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$status    = $parsed[ $this->get_action_meta() ] ?? '';
		$recipe_id = absint( $parsed['RECIPE_ID'] ?? 0 );

		if ( '' === $status ) {
			throw new LogicException(
				'10001: ' . __( 'Status must not be empty.', 'uncanny-automator-pro' ),
				1001
			);
		}

		$this->hydrate_tokens(
			array(
				'RECIPE'           => get_edit_post_link( $recipe_id ),
				'RECIPE_ID'        => $recipe_id,
				'RECIPE_TITLE'     => get_the_title( $recipe_id ),
				'RECIPE_EDIT_LINK' => get_edit_post_link( $recipe_id ),
			)
		);

		// Throw exception if the recipe is using instant trigger.
		if ( $this->is_recipe_using_instant_trigger( $recipe_id ) ) {
			throw new LogicException(
				'10002: ' . __( "The recipe status cannot be changed for recipes with an instant trigger or a 'Run now' button", 'uncanny-automator-pro' ),
				1002
			);
		}

		if ( 'activate' === $status ) {
			self::activate_recipe( $recipe_id );
			return true;
		}

		self::deactivate_recipe( $recipe_id );
		return true;
	}

	/**
	 * Determines whether the recipe has a run now button.
	 *
	 * @param string|int $recipe_id Required. The recipe ID.
	 *
	 * @return bool
	 */
	public function is_recipe_using_instant_trigger( $recipe_id ) {

		$recipe_id     = absint( $recipe_id );
		$recipe_object = Automator()->get_recipe_object( $recipe_id, ARRAY_A );
		$has_run_now   = $recipe_object['miscellaneous']['has_run_now'] ?? false;

		return $has_run_now;
	}

	/**
	 * Activates a recipe by setting its status to 'publish'.
	 *
	 * @param int $recipe_id The ID of the recipe to activate.
	 * @return bool True if the recipe was successfully activated.
	 * @throws Exception If the post update fails.
	 */
	public static function activate_recipe( $recipe_id ) {

		if ( self::can_recipe_be_activated( $recipe_id ) ) {
			return self::update_post( $recipe_id, 'publish' );
		}

		throw new Exception( 'Recipe activation failed: at least one trigger and one action must be active.', 1003 );
	}

	/**
	 * Deactivates a recipe by setting its status to 'draft'.
	 *
	 * @param int $recipe_id The ID of the recipe to deactivate.
	 * @return bool True if the recipe was successfully deactivated.
	 * @throws Exception If the post update fails.
	 */
	public static function deactivate_recipe( $recipe_id ) {
		return self::update_post( $recipe_id, 'draft' );
	}

	/**
	 * Updates the post status for a given recipe.
	 *
	 * @param int $recipe_id The ID of the recipe post to update.
	 * @param string $status The new post status (e.g., 'publish' or 'draft').
	 * @return bool True if the post status was successfully updated.
	 * @throws Exception If the post update fails.
	 */
	public static function update_post( $recipe_id, $status ) {

		// Prepare post update with the specified status.
		$updated_post = array(
			'ID'          => $recipe_id,
			'post_status' => $status,
		);

		// Update the post status using wp_update_post().
		$result = wp_update_post( $updated_post );

		// Check for errors and throw an exception if the update fails.
		if ( is_wp_error( $result ) ) {
			throw new Exception( 'Failed to update post status: ' . $result->get_error_message(), 1002 );
		}

		return true;
	}

	/**
	 * Checks if a recipe can be activated by ensuring it has at least one active trigger and one active action.
	 *
	 * @param int $recipe_id The ID of the recipe to check.
	 * @return bool True if the recipe can be activated; otherwise, false.
	 */
	public static function can_recipe_be_activated( $recipe_id ) {

		// Retrieve recipe data as an associative array.
		$recipe_object = Automator()->get_recipe_object( $recipe_id, 'ARRAY_A' );

		// Check for at least one active trigger and action.
		$has_active_trigger = self::has_active_item( $recipe_object['triggers']['items'] );
		$has_active_action  = self::has_active_item( $recipe_object['actions']['items'] );

		return $has_active_trigger && $has_active_action;
	}

	/**
	 * Checks if there is at least one active item in the provided list.
	 *
	 * @param array $items List of items to check.
	 * @return bool True if there is at least one active item; otherwise, false.
	 */
	private static function has_active_item( $items ) {

		foreach ( $items as $item ) {
			if ( true === $item['is_item_on'] ) {
				return true;
			}
		}

		return false;
	}


}
