<?php
namespace Uncanny_Automator_Pro\Integration\Plugin_Actions\Actions\Listeners;

/**
 *
 */
class Queries {

	/**
	 * @var wpdb
	 */
	private $db;

	/**
	 * @var string
	 */
	protected $trigger_meta = 'ADD_ACTION_TRIGGER_META';
	/**
	 * @var string
	 */
	protected $action_priority = 'ADD_ACTION_HOOK_PRIORITY';
	/**
	 * @var string
	 */
	protected $action_args_count = 'ADD_ACTION_ARGS_COUNT';

	/**
	 *
	 */
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	/**
	 * @return int[]
	 */
	public function find_triggers() {

		$results = $this->db->get_col(
			$this->db->prepare(
				"SELECT post_meta.post_id
					FROM {$this->db->postmeta} AS post_meta
					INNER JOIN {$this->db->posts} AS trigger_details ON trigger_details.ID = post_meta.post_id
						AND trigger_details.post_status = %s
						AND trigger_details.post_type = 'uo-trigger'
					INNER JOIN {$this->db->posts} AS recipe_details ON recipe_details.ID = trigger_details.post_parent
						AND recipe_details.post_status = %s
						AND recipe_details.post_type = 'uo-recipe'
					WHERE post_meta.meta_key = %s",
				'publish',
				'publish',
				$this->trigger_meta
			)
		);

		if ( empty( $results ) ) {
			return array();
		}

		return $results;

	}

	/**
	 * Retrieve all triggers that are listening.
	 *
	 * @return int[]
	 */
	public function find_triggers_listening() {

		$listener_meta_key = 'ADD_ACTION_WP_HOOK_LISTENING';

		$triggers = (array) $this->db->get_col(
			$this->db->prepare(
				"SELECT meta.post_id
					FROM {$this->db->postmeta} AS meta
					INNER JOIN {$this->db->posts} AS post ON post.ID = meta.post_id
						AND post.post_type = 'uo-trigger'
					WHERE meta.meta_key = %s
						AND meta.meta_value = %s",
				$listener_meta_key,
				'listening'
			)
		);

		return $triggers;

	}

}
