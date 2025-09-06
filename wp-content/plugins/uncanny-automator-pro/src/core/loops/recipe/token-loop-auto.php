<?php
namespace Uncanny_Automator_Pro\Loops\Recipe;

use Exception;
use InvalidArgumentException;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Backup;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Fields;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Iterable_Expression;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Backup as Loop_FilterBackup;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Fields as Loop_FilterFields;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter_Query;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Query;
use Uncanny_Automator_Pro\Utilities;

final class Token_Loop_Auto {

	/**
	 * Persists an empty token loop.
	 *
	 * @param mixed $item
	 * @param int $recipe_id
	 * @param string $meta_key
	 *
	 * @return void
	 */
	public static function persist( $item, $recipe_id, $config ) {

		$entity_id = $item->ID ?? '';

		$post_meta = Utilities::flatten_post_meta( get_post_meta( $entity_id ) );

		// Create the loop here automatically.
		$parent_id = $recipe_id;
		$ts        = time();

		$field_value = "{{TOKEN_EXTENDED:DATA_TOKEN_{$config['loopable_id']}:{$entity_id}:{$config['entity_code']}:{$config['loopable_id']}}}";

		if ( 'ACTION_TOKEN' === $config['type'] ) {
			$field_value = "{{TOKEN_EXTENDED:DATA_TOKEN_{$config['loopable_id']}:{$config['type']}:{$entity_id}:{$config['entity_code']}:{$config['loopable_id']}}}";
		}

		try {

			$fields = new Fields();
			$fields->set_id( 'TOKEN' );
			$fields->set_value( $field_value );
			$fields->set_backup(
				array(
					'show_label_in_sentence' => false,
					'label'                  => $post_meta[ $config['meta'] ] ?? '(Empty describe data)',
				)
			);

			$backup = new Backup();

			$iterable_expression = new Iterable_Expression( $fields, $backup );
			$iterable_expression->set_type( 'token' );

			$loop = new Loop( $iterable_expression );
			$loop->set_parent( $parent_id );
			$loop->set_title( "loop-recipe-{$ts}-{$parent_id}" );
			$loop->set_code( 'LOOP_TOKEN' );
			$loop->set_status( 'publish' );

			$query   = new Loop_Query();
			$loop_id = $query->add( $loop );

			// Add the filter.
			self::add_loop_filter( $loop_id );

			update_post_meta( $item->ID, 'LOOP_ADDED', 'yes' );

		} catch ( Exception $e ) {

			automator_log( 'Creating loop failed with error: ' . $e->getMessage(), 'loop error', true, 'debug' );

		}

	}

	/**
	 * @param int $loop_id
	 * @return int|WP_Error
	 *
	 * @throws InvalidArgumentException
	 */
	public static function add_loop_filter( $loop_id ) {

		$recently_added_loop_id = $loop_id;
		$filter_code            = 'ITEM_NOT_EMPTY';

		$backup = new Loop_FilterBackup();
		$fields = new Loop_FilterFields();

		$backup->set_integration_name( 'GEN' );
		$backup->set_sentence( 'The item is not empty' );
		$backup->set_sentence_html( '<span class="sentence sentence--standard"><span class="sentence-plain">The item is not empty</span></span>' );

		$filter = new Loop_Filter( $fields, $backup );
		$filter->set_title( "loop_filter_{$filter_code}_{$recently_added_loop_id}" );
		$filter->set_code( $filter_code );
		$filter->set_status( 'publish' );
		$filter->set_parent( $recently_added_loop_id );
		$filter->set_fields( $fields );
		$filter->set_integration( 'GEN' );
		$filter->set_integration_name( 'General' );

		$filter_query = new Loop_Filter_Query();

		return $filter_query->add( $filter );

	}
}
