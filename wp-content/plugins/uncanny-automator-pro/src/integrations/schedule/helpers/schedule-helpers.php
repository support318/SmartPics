<?php

namespace Uncanny_Automator_Pro\Integrations\Schedule\Helpers;

/**
 * Class Schedule_Helpers
 *
 * @package Uncanny_Automator_Pro\Integrations\Schedule\Helpers
 */
class Schedule_Helpers {

	/**
	 * @var string[]
	 */
	public static $valid_hooks = array(
		'automator_pro_trigger_on_specific_date',
		'automator_pro_recurring_weekday_trigger',
		'automator_pro_recurring_trigger',
	);

	/**
	 * @param $post_id
	 * @param $recipe_id
	 * @param $post_status
	 * @param $return
	 *
	 * @return void
	 */
	public function schedule_recipe( $post_id, $recipe_id, $post_status, $return ) {
		if ( ! isset( $return['recipes_object'] ) ) {
			return;
		}

		if ( empty( $return['recipes_object'] ) || ! isset( $return['recipes_object'][ $recipe_id ] ) ) {
			return;
		}

		$recipe_object = $return['recipes_object'][ $recipe_id ];

		if ( false === $this->has_schedule_trigger( $recipe_object ) ) {
			return;
		}

		$trigger = $this->get_schedule_trigger( $recipe_object );

		do_action( 'automator_pro_schedule_trigger', $trigger, $recipe_id );
	}

	/**
	 * @param $item
	 * @param $meta_key
	 * @param $meta_value
	 * @param $before_update_value
	 * @param $recipe_id
	 * @param $return
	 *
	 * @return void
	 */
	public function trigger_recipe_option_updated( $item, $meta_key, $meta_value, $before_update_value, $recipe_id, $return ) {
		if ( ! isset( $return['recipes_object'] ) ) {
			return;
		}

		if ( empty( $return['recipes_object'] ) || ! isset( $return['recipes_object'][ $recipe_id ] ) ) {
			return;
		}

		// If the item is not a trigger, do nothing
		if ( 'uo-trigger' !== $item->post_type ) {
			return;
		}

		// If the trigger is set to draft, do nothing
		if ( 'draft' === $item->post_status ) {
			return;
		}

		// If the recipe is set to draft, do nothing
		if ( 'draft' === get_post_status( $recipe_id ) ) {
			return;
		}

		$recipe_object = $return['recipes_object'][ $recipe_id ];

		if ( false === $this->has_schedule_trigger( $recipe_object ) ) {
			return;
		}

		$trigger = $this->get_schedule_trigger( $recipe_object );

		do_action( 'automator_pro_schedule_trigger_option_updated', $trigger, $recipe_id, $meta_key, $before_update_value );
	}

	/**
	 * Recipe has schedule trigger
	 *
	 * @param $recipe_object
	 *
	 * @return bool
	 */
	public function has_schedule_trigger( $recipe_object ) {
		if ( empty( $recipe_object ) ) {
			return false;
		}

		$triggers = $recipe_object['triggers'];

		if ( empty( $triggers ) ) {
			return false;
		}

		foreach ( $triggers as $trigger ) {
			if ( 'SCHEDULE' === $trigger['meta']['integration'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the hook for the trigger
	 *
	 * @param $trigger
	 *
	 * @return mixed
	 */
	public function get_trigger_hook( $trigger ) {
		return $trigger['meta']['add_action'];
	}

	/**
	 * Get the trigger id
	 *
	 * @param $trigger
	 *
	 * @return int
	 */
	public function get_trigger_id( $trigger ) {
		return absint( $trigger['ID'] );
	}

	/**
	 * Get the trigger from the recipe object
	 *
	 * @param $recipe_object
	 *
	 * @return false|mixed
	 */
	public function get_schedule_trigger( $recipe_object ) {

		$triggers = $recipe_object['triggers'];

		foreach ( $triggers as $trigger ) {
			if ( 'SCHEDULE' === $trigger['meta']['integration'] ) {
				return $trigger;
			}
		}

		return false;
	}

	/**
	 * Get trigger code
	 *
	 * @param $trigger
	 *
	 * @return mixed
	 */
	public function get_trigger_code( $trigger ) {
		return $trigger['meta']['code'];
	}

	/**
	 * Unschedule the trigger if the trigger is scheduled when recipe is drafted
	 *
	 * @param $trigger
	 *
	 * @return void
	 */
	public function unschedule_trigger( $trigger ) {

		$trigger_id = $this->get_trigger_id( $trigger );

		$scheduled_ids = get_post_meta( $trigger_id, 'as_scheduled_id' );

		if ( empty( $scheduled_ids ) ) {
			return;
		}

		foreach ( $scheduled_ids as $scheduled_id ) {
			\ActionScheduler::store()->cancel_action( $scheduled_id );
		}
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate_trigger( $trigger, $hook_args ) {
		$trigger_id = absint( $trigger['ID'] );
		$recipe_id  = absint( $trigger['post_parent'] );

		list( $triggered_id, $triggered_recipe_id ) = $hook_args;

		// If the trigger ID and the triggered ID are not the same, return false
		if ( absint( $trigger_id ) !== absint( $triggered_id ) && absint( $recipe_id ) !== absint( $triggered_recipe_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Store the schedule ID in the post meta
	 *
	 * @param $action_id
	 *
	 * @return void
	 */
	public function store_schedule_id( $action_id ) {
		$action = \ActionScheduler::store()->fetch_action( $action_id );

		if ( ! $action ) {
			return;
		}

		// Get the action hook name
		$hook_name   = $action->get_hook();
		$action_args = $action->get_args();

		// Check if the action hook is valid
		if ( ! in_array( $hook_name, self::$valid_hooks, true ) ) {
			return;
		}

		// Get the action arguments
		$args = $action->get_args();

		$trigger_id = $args[0];

		add_post_meta( $trigger_id, 'as_scheduled_id', $action_id );
	}

	/**
	 * @param $action_id
	 *
	 * @return void
	 */
	public function delete_schedule_id( $action_id ) {
		$action = \ActionScheduler::store()->fetch_action( $action_id );

		if ( ! $action ) {
			return;
		}

		// Get the action hook name
		$hook_name = $action->get_hook();

		// Check if the action hook is valid
		if ( ! in_array( $hook_name, self::$valid_hooks, true ) ) {
			return;
		}

		// Get the action arguments
		$args = $action->get_args();

		$trigger_id = $args[0];

		delete_post_meta( $trigger_id, 'as_scheduled_id', $action_id );
	}

	/**
	 * @param $post_id
	 * @param $post
	 *
	 * @return void
	 */
	public function unschedule_on_bulk_edit( $post_id, $post ) {
		if ( ! isset( $post->post_type ) || 'uo-recipe' !== $post->post_type ) {
			return;
		}

		// Check if the post is a draft
		if ( 'draft' !== $post->post_status ) {
			return;
		}

		$recipe_id      = $post->ID;
		$recipes_object = Automator()->get_recipes_data( false, $recipe_id );

		if ( empty( $recipes_object ) ) {
			return;
		}

		$recipe_object = $recipes_object[ $recipe_id ];

		if ( false === $this->has_schedule_trigger( $recipe_object ) ) {
			return;
		}

		$trigger = $this->get_schedule_trigger( $recipe_object );

		// Maybe unschedule the triggers
		do_action( 'automator_pro_schedule_trigger', $trigger, $recipe_id );
	}

	/**
	 * @return array
	 */
	public function get_time_sepcific_field() {
		return array(
			'option_code'           => 'SPECIFIC_TIME',
			'label'                 => esc_attr_x( 'Time', 'schedule', 'uncanny-automator-pro' ),
			'placeholder'           => esc_attr_x( 'hh:mm', 'schedule', 'uncanny-automator-pro' ),
			'description'           => esc_html_x( 'The time at which this recipe will run.', 'schedule', 'uncanny-automator-pro' ),
			'input_type'            => 'time',
			'default_value'         => '12:00 AM',
			'required'              => true,
			'supports_custom_value' => false,
			'minute_increment'      => 1,
			'time_alt_format'       => 'g:i A',
		);
	}

	/**
	 * @return array
	 */
	public function get_repeat_field( $recurring_trigger = false ) {
		$v = '-1';
		if ( $recurring_trigger ) {
			$v = '300';
		}
		$options = array(
			array(
				'value' => $v,
				'text'  => esc_attr_x( 'Until cancelled', 'schedule', 'uncanny-automator-pro' ),
			),
			array(
				'value' => '1',
				'text'  => esc_attr_x( '1 time', 'schedule', 'uncanny-automator-pro' ),
			),
		);

		$count = apply_filters( 'automator_pro_schedule_repeat_count', 20 );

		// Starting from 2 since the first two are manually added
		for ( $i = 2; $i <= $count; $i ++ ) {
			$options[] = array(
				'value' => (string) $i,
				'text'  => sprintf( esc_attr_x( '%s times', 'schedule count', 'uncanny-automator-pro' ), $i ),
			);
		}

		return array(
			'option_code'              => 'REPEAT_TIMES',
			'label'                    => esc_attr_x( 'Repeat', 'schedule', 'uncanny-automator-pro' ),
			'description'              => esc_html__( 'The number of times the recipe will run.', 'uncanny-automator-pro' ),
			'input_type'               => 'select',
			'options_show_id'          => false,
			'options'                  => $options,
			'required'                 => true,
			'supports_custom_value'    => true,
			'custom_value_description' => esc_html__( 'Enter a numeric value, i.e., 30, etc.', 'uncanny-automator-pro' ),
		);
	}

	/**
	 * @param $hook_name
	 * @param $args
	 *
	 * @return int|null
	 */
	public static function get_completed_actions( $hook_name, $args ) {
		$store = \ActionScheduler::store();
		$query = array(
			'hook'    => $hook_name,
			'status'  => 'complete',
			'args'    => $args, // Optional: specify if you want to filter by specific arguments
			'orderby' => 'scheduled_date_gmt',
			'order'   => 'ASC',
		);

		$completed_action_ids = $store->query_actions( $query );

		return count( $completed_action_ids );
	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public function setup_a_schedule( $data ) {

		/**
		 * @var \DateTime $new_date
		 * @var int $trigger_id
		 * @var string $hook
		 * @var array $args
		 * @var int $duration
		 * @var string $modifier
		 */
		list( $new_date, $trigger_id, $hook, $args, $duration, $modifier ) = $data;
		// Convert the time to UTC
		$new_date->setTimezone( new \DateTimeZone( 'UTC' ) );

		// How many times should the schedule be repeated?
		$repeat_until = get_post_meta( $trigger_id, 'REPEAT_TIMES', true );

		if ( 'automator_custom_value' === $repeat_until ) {
			$repeat_until = (int) get_post_meta( $trigger_id, 'REPEAT_TIMES_custom', true );
		}

		// Set a recurring schedule if the repeat until is empty, -1, or 300
		if ( empty( $repeat_until ) || intval( '-1' ) === intval( $repeat_until ) || 300 === (int) $repeat_until ) {
			as_schedule_recurring_action( $new_date->getTimestamp(), $duration, $hook, $args, 'Uncanny Automator' );

			return;
		}

		for ( $i = 1; $i <= $repeat_until; $i ++ ) {
			if ( $i > 1 ) {
				$new_date->modify( $modifier );
			}
			as_schedule_single_action( $new_date->getTimestamp(), $hook, $args, 'Uncanny Automator' );
		}
	}

	/**
	 * @param $sort
	 *
	 * @return array
	 */
	public static function get_all_scheduled_actions( $sort = 'ASC' ) {
		$all_actions = array();
		$store       = \ActionScheduler::store();
		foreach ( self::$valid_hooks as $hook_name ) {
			$query       = array(
				'hook'     => $hook_name,
				'status'   => 'pending', // Can be 'complete', 'pending', etc.
				'orderby'  => 'scheduled_date_gmt',
				'per_page' => 9999,
			);
			$all_actions = array_merge( $all_actions, $store->query_actions( $query ) );
		}
		$sorted_actions = array();
		foreach ( $all_actions as $action_id ) {
			$action                       = $store->fetch_action( $action_id );
			$scheduled_date_gmt           = $action->get_schedule()->get_date()->format( 'U' );
			$sorted_actions[ $action_id ] = $scheduled_date_gmt;
		}
		if ( 'asc' === strtolower( $sort ) ) {
			asort( $sorted_actions ); // Sort actions by scheduled date in ascending order
		} else {
			arsort( $sorted_actions ); // Sort actions by scheduled date in descending order
		}

		return $sorted_actions;
	}

	/**
	 * @param $hook
	 * @param $args
	 *
	 * @return array
	 */
	public static function normalize_args_for_table( $hook, $args ) {
		$new_args = array();
		$repeat   = get_post_meta( $args[0], 'REPEAT_TIMES', true );

		if ( 'automator_custom_value' === $repeat ) {
			$repeat = get_post_meta( $args[0], 'REPEAT_TIMES_custom', true );
		}

		if ( isset( $args[5] ) && 'automator_pro_recurring_trigger' === $hook ) {
			$repeat = $args[5];
		}

		// 300 is the default value for Until cancelled
		if ( 300 === (int) $repeat ) {
			$repeat = '-1';
		}

		$times = intval( $repeat ) === intval( '-1' ) ? 'until cancelled' : 'for ' . $repeat . ' time(s)';

		switch ( $hook ) {
			case 'automator_pro_recurring_weekday_trigger':
				$new_args = array(
					esc_html_x( 'Every', 'Scheduled Recipe', 'uncanny-automator-pro' ) . ' ',
					// repeat
					ucfirst( strtolower( $args['day'] ) ),
					// weekday
					' at ' . $args['time'],
					$times,
					// time
				);
				break;
			case 'automator_pro_recurring_trigger':
				$new_args = array(
					esc_html_x( 'Every', 'Scheduled Recipe', 'uncanny-automator-pro' ) . ' ' . $args[2],
					// repeat
					ucfirst( strtolower( $args[3] ) ) . '(s)',
					// weekday
					' at ' . $args[4],
					$times,
					// time
				);
				break;
			case 'automator_pro_trigger_on_specific_date':
				$new_args = array(
					esc_html_x( 'Run on', 'Scheduled Recipe', 'uncanny-automator-pro' ),
					$args[2],
					// weekday
					' at ' . $args[3],
				);
				break;
		}

		return $new_args;   // Return the normalized args.
	}

	/**
	 * @param $trigger
	 *
	 * @return int|mixed
	 */
	public function get_repeat_times( $trigger ) {
		$repeat_times = ! empty( $trigger['meta']['REPEAT_TIMES'] ) ? $trigger['meta']['REPEAT_TIMES'] : 1;
		if ( 'automator_custom_value' === $repeat_times && isset( $trigger['meta']['REPEAT_TIMES_custom'] ) ) {
			$repeat_times = $trigger['meta']['REPEAT_TIMES_custom'];
		}

		return $repeat_times;
	}

	/**
	 * @param $trigger_code
	 *
	 * @return string
	 */
	public function get_support_link( $trigger_code ) {
		$support_link = 'https://automatorplugin.com/knowledge-base/schedule/';

		return $support_link;
	}
}
