<?php

namespace Uncanny_Automator_Pro\Integrations\Schedule;

/**
 *
 */
class Recurring_Weekday extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @var
	 */
	public $helpers;

	/**
	 * @var \Uncanny_Automator_Pro\Integrations\Schedule\Helpers\Schedule_Helpers
	 */
	public $trigger_hook = 'automator_pro_recurring_weekday_trigger';

	/**
	 * @return void
	 */
	protected function setup_trigger() {
		add_action( 'automator_pro_schedule_trigger', array( $this, 'maybe_schedule_trigger' ), 10, 4 );
		add_action( 'automator_pro_schedule_trigger_option_updated', array( $this, 'trigger_option_updated' ), 10, 4 );

		/** @var \Uncanny_Automator_Pro\Integrations\Schedule\Helpers\Schedule_Helpers $helpers */
		$helpers       = array_shift( $this->dependencies );
		$this->helpers = $helpers;

		$this->set_integration( 'SCHEDULE' );
		$this->set_trigger_code( 'RECURRING_WEEKDAY_TRIGGER_CODE' );
		$this->set_trigger_meta( 'RECURRING_WEEKDAY_TRIGGER_META' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( '{{Repeat:%3$s}} every {{weekday:%1$s}} at {{a specific time:%2$s}}', 'schedule', 'uncanny-automator-pro' ), $this->get_trigger_meta(), 'SPECIFIC_TIME:' . $this->get_trigger_meta(), 'REPEAT_TIMES:' . $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{Repeat}} every {{weekday}} at {{a specific time}}', 'schedule', 'uncanny-automator-pro' ) );
		$this->add_action( $this->trigger_hook, 10, 4 );
		$this->set_support_link( $this->helpers->get_support_link( $this->trigger_code ) );
	}

	/**
	 * @return \array[][]
	 * @throws \Exception
	 */
	public function options() {
		return array(
			array(
				'option_code'              => $this->get_trigger_meta(),
				'label'                    => esc_attr_x( 'Weekday', 'schedule', 'uncanny-automator-pro' ),
				'input_type'               => 'select',
				'options_show_id'          => false,
				'supports_multiple_values' => true,
				'options'                  => array(
					array(
						'value' => 'MONDAY',
						'text'  => esc_attr_x( 'Monday', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'TUESDAY',
						'text'  => esc_attr_x( 'Tuesday', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'WEDNESDAY',
						'text'  => esc_attr_x( 'Wednesday', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'THURSDAY',
						'text'  => esc_attr_x( 'Thursday', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'FRIDAY',
						'text'  => esc_attr_x( 'Friday', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'SATURDAY',
						'text'  => esc_attr_x( 'Saturday', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'SUNDAY',
						'text'  => esc_attr_x( 'Sunday', 'schedule', 'uncanny-automator-pro' ),
					),
				),
				'required'                 => true,
				'supports_custom_value'    => false,
			),
			$this->helpers->get_time_sepcific_field(),
			$this->helpers->get_repeat_field(),
		);
	}

	/**
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		$this->set_user_id( 0 );

		return $this->helpers->validate_trigger( $trigger, $hook_args );
	}

	/**
	 * @param $trigger
	 * @param $recipe_id
	 * @param $meta_key
	 * @param $before_update_value
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function trigger_option_updated( $trigger, $recipe_id, $meta_key, $before_update_value ) {
		$trigger_code = $this->helpers->get_trigger_code( $trigger );

		if ( ! $this->is_current_trigger( $trigger_code ) ) {
			return;
		}

		// Unschedule existing jobs before scheduling new ones
		$this->helpers->unschedule_trigger( $trigger );

		// Schedule new jobs
		$this->schedule_trigger( $trigger, $recipe_id );
	}

	/**
	 * Check if the trigger is valid
	 *
	 * @param $trigger
	 * @param $recipe_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function maybe_schedule_trigger( $trigger, $recipe_id ) {
		$trigger_code = $this->helpers->get_trigger_code( $trigger );

		if ( ! $this->is_current_trigger( $trigger_code ) ) {
			return;
		}

		// If the recipe is set to draft and the trigger exists, unschedule it
		if ( 'draft' === get_post_status( $recipe_id ) ) {
			$this->helpers->unschedule_trigger( $trigger );

			return;
		}

		$this->schedule_trigger( $trigger, $recipe_id );
	}

	/**
	 * Schedule the trigger
	 *
	 * @param $trigger
	 * @param $recipe_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function schedule_trigger( $trigger, $recipe_id ) {

		$trigger_id = $this->helpers->get_trigger_id( $trigger );
		$hook       = $this->trigger_hook;

		$weekdays = $this->get_weekdays( $trigger );
		$time     = $this->get_time( $trigger );

		if ( empty( $weekdays ) || empty( $time ) ) {
			return;
		}

		$args = array( $trigger_id, $recipe_id );

		foreach ( $weekdays as $day ) {
			$args['day']  = $day;
			$args['time'] = $time;

			// Schedule the action if it's not already scheduled for this day.
			//if ( ! as_next_scheduled_action( $hook, $args, 'Uncanny Automator' ) ) {

			// Get the current time with WP time zone
			$current_date = new \DateTime( 'now', wp_timezone() );

			// Create a DateTime object for the specified day and time this week
			$specified_day_this_week = new \DateTime( "this $day $time", wp_timezone() );

			if ( $current_date >= $specified_day_this_week ) {
				// If the specified time has passed (or it's currently past that time on the specified day),
				// set the date for the next week's specified day and time.
				$new_date = new \DateTime( "next $day $time", wp_timezone() );
			} else {
				// If the current time is before the specified time on the specified day this week,
				// keep the date for this week.
				$new_date = $specified_day_this_week;
			}

			// Schedule the recurring action using Action Scheduler
			$this->helpers->setup_a_schedule(
				array(
					$new_date,
					$trigger_id,
					$hook,
					$args,
					WEEK_IN_SECONDS,
					'+1 week',
				)
			);
		}
	}

	/**
	 * @param $trigger_code
	 *
	 * @return bool
	 */
	public function is_current_trigger( $trigger_code ) {

		if ( $trigger_code !== $this->get_trigger_code() ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $trigger
	 *
	 * @return array|mixed|string
	 */
	public function get_weekdays( $trigger ) {

		return isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ? json_decode( $trigger['meta'][ $this->get_trigger_meta() ] ) : array();
	}

	/**
	 * @param $trigger
	 *
	 * @return mixed|string
	 */
	public function get_time( $trigger ) {
		return isset( $trigger['meta']['SPECIFIC_TIME'] ) ? $trigger['meta']['SPECIFIC_TIME'] : '00:00';
	}

	/**
	 * @param $trigger
	 *
	 * @return mixed|string
	 */
	public function get_repeat_until( $trigger ) {
		return $this->helpers->get_repeat_times( $trigger );
	}

	/**
	 * @param $completed_trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		$repeat_times = ! empty( $completed_trigger['meta']['REPEAT_TIMES'] ) ? $completed_trigger['meta']['REPEAT_TIMES'] : 1;
		if ( 'automator_custom_value' === $repeat_times && isset( $completed_trigger['meta']['REPEAT_TIMES_custom'] ) ) {
			$repeat_times = $completed_trigger['meta']['REPEAT_TIMES_custom'];
		}

		$weekdays = $this->get_weekdays( $completed_trigger );

		$weekdays = array_map(
			function ( $weekday ) {
				return ucfirst( strtolower( $weekday ) );
			},
			$weekdays
		);

		$weekdays = join( ', ', $weekdays );

		$tokens = array(
			$this->get_trigger_meta() => $weekdays,
			'SPECIFIC_TIME'           => $this->get_time( $completed_trigger ),
			'REPEAT_TIMES'            => $repeat_times,
		);

		return $tokens;
	}
}
