<?php
namespace Uncanny_Automator_Pro\Loops\Db;

class Db {

	/**
	 * @var \wpdb $db
	 */
	protected $db;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	/**
	 * Given a recipe ID, find all loops that are under it.
	 *
	 * @param int $recipe_id
	 *
	 * @return mixed[]
	 */
	public function find_recipe_loops( $recipe_id ) {

		$loops = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->db->posts} WHERE post_parent = %d AND post_type = %s",
				absint( $recipe_id ),
				'uo-loop'
			),
			ARRAY_A
		);

		return (array) $loops;
	}

	/**
	 * Given a loop ID, find all filters that are under it.
	 *
	 * @param int $loop_id
	 *
	 * @return mixed[] array
	 */
	public function find_loop_filters( $loop_id ) {

		$filters = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->db->posts} WHERE post_parent = %d AND post_type = %s ORDER BY menu_order ASC",
				absint( $loop_id ),
				'uo-loop-filter'
			),
			ARRAY_A
		);

		return (array) $filters;

	}

	/**
	 * Given a loop ID, find all actions that are under it.
	 *
	 * @param int $loop_id
	 *
	 * @return mixed[] array
	 */
	public function find_loop_actions( $loop_id ) {

		$actions = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM {$this->db->posts} WHERE post_parent = %d AND post_type = %s ORDER BY menu_order ASC",
				absint( $loop_id ),
				'uo-action'
			),
			ARRAY_A
		);

		return (array) $actions;

	}
}
