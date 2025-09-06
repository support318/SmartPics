<?php
namespace Uncanny_Automator_Pro\Loops\Filter\Model\Active_Record;

use Uncanny_Automator_Pro\Loops\Loop\Entity_Factory;

class Entity_Filter_Record {

	/**
	 * @var int $recipe_id
	 */
	protected $recipe_id;

	/**
	 * @var int $loop_id
	 */
	protected $loop_id;

	/**
	 * @var string
	 */
	protected $loop_type = '';

	/**
	 * @var mixed[]
	 */
	protected $run_args = array();

	/**
	 * @param int $recipe_id
	 * @param int $loop_id
	 * @param mixed[] $run_args
	 *
	 * @return void
	 */
	public function __construct( $recipe_id, $loop_id, $run_args ) {

		$this->recipe_id = $recipe_id;
		$this->loop_id   = $loop_id;
		$this->run_args  = $run_args;

	}

	/**
	 * Sets the loop id.
	 *
	 * @param int $loop_id
	 *
	 * @return void
	 */
	public function set_loop_id( $loop_id ) {
		$this->loop_id = absint( $loop_id );
	}

	/**
	 * Sets the loop type.
	 *
	 * @param string $type
	 * @return void
	 */
	public function set_loop_type( $type ) {
		$this->loop_type = $type;
	}

	/**
	 * Returns the loop type.
	 *
	 * @return string
	 */
	public function get_loop_type() {
		return $this->loop_type;
	}

	/**
	 * Returns the loop id.
	 *
	 * @return int
	 */
	public function get_loop_id() {
		return $this->loop_id;
	}

	/**
	 * Retrieves all filters inside a specific loop.
	 *
	 * @return mixed[];
	 */
	public function find_all() {

		$filters = Automator()->loop_filters_db()->get_loop_filters( $this->loop_id );

		$loop_filters = array();

		foreach ( $filters as $filter ) {
			$filter_code = get_post_meta( $filter, 'code', true );
			if ( ! empty( $filter_code ) ) {
				$loop_filters[ $filter ] = $filter_code;
			}
		}

		return $loop_filters;

	}

	/**
	 * Retrieve final entities to iterate from.
	 *
	 * @return int[]
	 */
	public function get_entities() {

		$loop_entity_filters = $this->find_all();

		$entity_list = array();

		foreach ( $loop_entity_filters as $filter_id => $entity_filter ) {

			$default_class_mapping = 'Uncanny_Automator_Pro\\Loop_Filters\\' . $entity_filter;

			$filter_class = apply_filters(
				'uncanny_automator_pro_loop_filter_class',
				$default_class_mapping,
				array(
					'filter'         => $entity_filter,
					'filter_id'      => $filter_id,
					'entity_filters' => $loop_entity_filters,
				)
			);

			$base_class = 'Uncanny_Automator_Pro\\Loops\\Filter\\Base\\Loop_Filter';

			if ( is_subclass_of( $filter_class, $base_class ) ) {

				$loop_id = $this->get_loop_id();

				$filter = new $filter_class( $filter_id, $this->run_args, $loop_id );

				$filter->set_run_args( $this->run_args );
				$filter->set_loop_id( $loop_id );

				$entities_from_filter = $filter->get_entities();

				if ( ! is_wp_error( $entities_from_filter ) ) {
					$entity_list[] = $entities_from_filter;
				}
			}
		}

		if ( empty( $entity_list ) ) {
			return array();
		}

		// Get the intersection of the array to find the entities that are common in two or more filter conditions.
		if ( count( $entity_list ) >= 2 ) {
			if ( Entity_Factory::TYPE_TOKEN === $this->get_loop_type() ) {
				// Return the associative intersection.
				return $this->get_loopable_token_intersection( $entity_list );
			}
			// Return the numeric intersection.
			return $this->get_intersection( $entity_list );
		}

		// Just shift the array if there is only 1 filter condition.
		$entity_list = array_shift( $entity_list );

		return $entity_list;

	}

	/**
	 * Calculates the intersection of entities. Returns a subset of data that is common to all lists.
	 *
	 * @param mixed[] $entities_list
	 *
	 * @return int[]
	 */
	private function get_intersection( $entities_list ) {

		$intersection = (array) call_user_func_array( 'array_intersect', $entities_list );

		return array_map( 'absint', $intersection );

	}

	/**
	 * Calculates the intersection of associative intities.
	 *
	 * @param mixed[] $entities_list
	 *
	 * @return mixed[]
	 */
	private function get_loopable_token_intersection( $entities_list ) {

		$intersection_map = $this->convert_to_hash_map( $entities_list[0] );

		// Iterate through each subsequent array and find intersection.
		for ( $i = 1; $i < count( $entities_list ); $i++ ) {
			$current_map      = $this->convert_to_hash_map( $entities_list[ $i ] );
			$intersection_map = array_intersect_key( $intersection_map, $current_map );
		}

		// Convert hash map back to array.
		$intersection = array_values( $intersection_map );

		return $intersection;
	}

	/**
	 * Converts array to hashmaps for efficiency.
	 *
	 * @param mixed $array
	 * @return array
	 */
	private function convert_to_hash_map( $array ) {
		$map = array();
		foreach ( $array as $item ) {
			$key         = json_encode( $item );
			$map[ $key ] = $item;
		}
		return $map;
	}

}
