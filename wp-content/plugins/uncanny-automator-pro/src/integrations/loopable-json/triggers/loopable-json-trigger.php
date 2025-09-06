<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

/**
 * @package Uncanny_Automator\Integrations\Loopable_Json\Triggers
 *
 * @since 6.0
 */

namespace Uncanny_Automator\Integrations\Loopable_Json\Triggers;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator\Integrations\Loopable_Json\Helpers\Loopable_Json_Helpers;
use Uncanny_Automator_Pro\Integrations\Loopable_Json\Tokens\Loopable\Trigger\Json_Items as Trigger_Json_Items;
use Uncanny_Automator_Pro\Integrations\Run_Now\Run_Now_Integration;
use Uncanny_Automator_Pro\Loops\Recipe\Token_Loop_Auto;
use Uncanny_Automator_Pro\Utilities;

/**
 *
 * Loopable_Json_Trigger
 *
 * @package Uncanny_Automator\Integrations\Loopable_Json\Triggers
 *
 */
class Loopable_Json_Trigger extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * Setups the Trigger properties.
	 *
	 * @link https://developer.automatorplugin.com/adding-a-custom-trigger-to-uncanny-automator/
	 * @return void
	 */
	protected function setup_trigger() {

		$this->set_integration( 'LOOPABLE_JSON' );
		$this->set_trigger_code( 'TRIGGER_LOOPABLE_JSON_CODE' );
		$this->set_trigger_meta( 'TRIGGER_LOOPABLE_JSON_META' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );

		$this->set_readable_sentence(
			/* translators: Trigger sentence */
			esc_attr_x( 'Import {{a JSON file}}', 'JSON', 'uncanny-automator-pro' )
		);

		$this->set_sentence(
			sprintf(
				/* translators: Trigger sentence */
				esc_attr_x( 'Import {{a JSON file:%1$s}}', 'JSON', 'uncanny-automator-pro' ),
				$this->get_trigger_meta()
			)
		);

		$this->set_loopable_tokens(
			array(
				'TRIGGER_LOOPABLE_JSON_ITEMS' => Trigger_Json_Items::class,
			)
		);

		// Run now action hook.
		$this->add_action( 'automator_pro_run_now_recipe', 10, 1 );

		$this->register_hooks();

	}

	/**
	 * Registers necessary hooks.
	 *
	 * Automatically creates a token loop when the action is added.
	 *
	 * @return void
	 */
	public function register_hooks() {

		// Create a new loop for this entity.
		$closure = function( $item, $recipe_id ) {

			$post_meta       = Utilities::flatten_post_meta( get_post_meta( $item->ID ?? null ) );
			$code            = $post_meta['code'] ?? '';
			$requesting_meta = automator_filter_input( 'optionCode', INPUT_POST );

			if ( 'TRIGGER_LOOPABLE_JSON_META' !== $requesting_meta ) {
				return;
			}

			$loop_been_added = isset( $post_meta['LOOP_ADDED'] ) && 'yes' === $post_meta['LOOP_ADDED'];

			if ( $loop_been_added ) {
				return;
			}

			$config = array(
				'loopable_id' => 'TRIGGER_LOOPABLE_JSON_ITEMS',
				'type'        => 'TRIGGER_TOKEN',
				'entity_id'   => $item->ID ?? null,
				'entity_code' => $code ?? null,
				'meta'        => $this->get_trigger_meta(),
			);

			Token_Loop_Auto::persist( $item, $recipe_id, $config );

		};

		add_action( 'automator_recipe_option_updated_before_cache_is_cleared', $closure, 10, 2 );

	}

	/**
	 * @return mixed[]
	 */
	public function options() {
		return Loopable_Json_Helpers::make_fields( $this->get_trigger_meta() );
	}

	/**
	 * Validates the Trigger. This method would allow you to narrow down the execution of the Trigger.\
	 *
	 * For example, we only want to fire the Trigger if the redirect type is 302.
	 *
	 * @param array{'ID': int, 'post_status': string, 'meta': mixed[]} $trigger The arguments supplied by the Trigger itself.
	 * @param mixed[]                                                  $hook_args The action hook arguments passed into this method.
	 *
	 * @return bool True, always. We want to fire the trigger regardless of the $trigger and $hook_args.
	 */
	public function validate( $trigger, $hook_args ) {

		$recipe_id           = $trigger['post_parent'] ?? null;
		$recipe_id_from_hook = $hook_args[0] ?? null;

		if ( empty( $recipe_id ) || empty( $recipe_id_from_hook ) ) {
			return false;
		}

		if ( absint( $recipe_id ) !== absint( $recipe_id_from_hook ) ) {
			return false;
		}

		$status = Run_Now_Integration::fetch_recipe_status( $recipe_id );

		return Automator_Status::IN_PROGRESS !== $status;

	}

}
