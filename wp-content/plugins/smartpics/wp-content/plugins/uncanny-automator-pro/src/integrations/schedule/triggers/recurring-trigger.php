<?php

namespace Uncanny_Automator_Pro\Integrations\Schedule;

/**
 *
 */
class Recurring_Trigger extends \Uncanny_Automator\Recipe\Trigger {
	/**
	 * @var \Uncanny_Automator_Pro\Integrations\Schedule\Helpers\Schedule_Helpers
	 */
	public $helpers;

	/**
	 * @var string
	 */
	public $trigger_hook = 'automator_pro_recurring_trigger';

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
		$this->set_trigger_code( 'RECURRING_TRIGGER_CODE' );
		$this->set_trigger_meta( 'RECURRING_TRIGGER_META' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( '{{Repeat:%4$s}} {{every:%1$s}} {{unit:%2$s}} (starting) at {{a specific time:%3$s}}', 'schedule', 'uncanny-automator-pro' ), 'RECURRING_NUMBER:' . $this->get_trigger_meta(), 'RECURRING_UNIT:' . $this->get_trigger_meta(), 'SPECIFIC_TIME:' . $this->get_trigger_meta(), 'REPEAT_TIMES:' . $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{Repeat}} {{every}} {{hour, day, week, month or year}} at {{a specific time}}', 'schedule', 'uncanny-automator-pro' ) );
		$this->add_action( $this->trigger_hook, 10, 6 );
		$this->set_support_link( $this->helpers->get_support_link( $this->trigger_code ) );
	}

	/**
	 * @return \array[][]
	 */
	public function options() {

		$month = array();
		for ( $i = 1; $i <= 31; $i ++ ) {
			$month[] = array(
				'value' => $i,
				'text'  => $i,
			);
		}

		$month_with_last   = $month;
		$month_with_last[] = array(
			'value' => 'LAST',
			'text'  => esc_attr_x( 'Last day of the month', 'schedule', 'uncanny-automator-pro' ),
		);

		return array(
			array(
				'option_code'           => 'RECURRING_NUMBER',
				'label'                 => esc_attr_x( 'Every', 'schedule', 'uncanny-automator-pro' ),
				'input_type'            => 'float',
				'min_number'            => 0.0833, // 5 minutes
				'required'              => true,
				'supports_custom_value' => false,
				'description'           => esc_html__( 'The number of units between each execution of the recipe.', 'uncanny-automator-pro' ),
			),
			array(
				'option_code'            => 'RECURRING_UNIT',
				'label'                  => esc_attr_x( 'Unit', 'schedule', 'uncanny-automator-pro' ),
				'input_type'             => 'select',
				'show_label_in_sentence' => false,
				'options_show_id'        => false,
				'options'                => array(
					array(
						'value' => 'HOUR',
						'text'  => esc_attr_x( 'Hour(s)', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'DAY',
						'text'  => esc_attr_x( 'Day(s)', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'WEEK',
						'text'  => esc_attr_x( 'Week(s)', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'MONTH',
						'text'  => esc_attr_x( 'Month(s)', 'schedule', 'uncanny-automator-pro' ),
					),
					array(
						'value' => 'YEAR',
						'text'  => esc_attr_x( 'Year(s)', 'schedule', 'uncanny-automator-pro' ),
					),
				),
				'required'               => true,
				'supports_custom_value'  => false,
			),
			$this->helpers->get_repeat_field( true ),
			array(
				'option_code'           => 'SPECIFIC_DAY',
				'label'                 => esc_attr_x( 'Specific day', 'schedule', 'uncanny-automator-pro' ),
				'input_type'            => 'select',
				'options_show_id'       => false,
				'dynamic_visibility'    => array(
					'default_state'    => 'hidden',
					'visibility_rules' => array(
						array(
							'operator'             => 'AND',
							'rule_conditions'      => array(
								array(
									'option_code' => 'RECURRING_UNIT',
									'compare'     => '==',
									'value'       => 'MONTH',
								),
								array(
									'option_code' => 'REPEAT_TIMES',
									'compare'     => '!=',
									'value'       => '-1',
								),
							),
							'resulting_visibility' => 'show',
						),
					),
				),
				'options'               => $month_with_last,
				'description'           => esc_html__( "The day of the month on which the recipe will run. For dates later in the month, the recipe will run on the 1st of the following month if the current month doesn't have that date. Consider using the \"Last day of the month\" instead to target the end of the month.", 'uncanny-automator-pro' ),
				'required'              => true,
				'supports_custom_value' => false,
				//				'default_value'         => date( 'j', strtotime( 'tomorrow' ) ),
			),
			array(
				'option_code'           => 'START_DAY',
				'label'                 => esc_attr_x( 'Start day', 'schedule', 'uncanny-automator-pro' ),
				'description'           => esc_html__( 'The day of the month on which the first run of the recipe should occur. The recipe will then run every 30 days times the number of units in the Every field.', 'uncanny-automator-pro' ),
				'input_type'            => 'select',
				'options_show_id'       => false,
				'dynamic_visibility'    => array(
					'default_state'    => 'hidden',
					'visibility_rules' => array(
						array(
							'operator'             => 'AND',
							'rule_conditions'      => array(
								array(
									'option_code' => 'RECURRING_UNIT',
									'compare'     => '==',
									'value'       => 'MONTH',
								),
								array(
									'option_code' => 'REPEAT_TIMES',
									'compare'     => '==',
									'value'       => '-1',
								),
							),
							'resulting_visibility' => 'show',
						),
					),
				),
				'options'               => $month,
				'required'              => true,
				'supports_custom_value' => false,

			),
			$this->helpers->get_time_sepcific_field(),
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

		$unit_type = $this->get_recurring_unit( $trigger );

		switch ( $unit_type ) {
			case 'HOUR':
			case 'DAY':
			case 'WEEK':
			case 'YEAR':
				$this->schedule_non_monthly_trigger( $trigger, $recipe_id, $unit_type );
				break;
			case 'MONTH':
				$this->schedule_monthly_trigger( $trigger, $recipe_id );
				break;
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
	 * @return int|mixed
	 */
	public function get_recurring_number( $trigger ) {
		return ! empty( $trigger['meta']['RECURRING_NUMBER'] ) ? floatval( $trigger['meta']['RECURRING_NUMBER'] ) : 1;
	}

	/**
	 * @param $trigger
	 *
	 * @return mixed|string
	 */
	public function get_recurring_unit( $trigger ) {
		return ! empty( $trigger['meta']['RECURRING_UNIT'] ) ? sanitize_text_field( $trigger['meta']['RECURRING_UNIT'] ) : 'DAY';
	}

	/**
	 * @param $trigger
	 *
	 * @return int|mixed
	 */
	public function get_specific_day( $trigger ) {
		return ! empty( $trigger['meta']['SPECIFIC_DAY'] ) ? $trigger['meta']['SPECIFIC_DAY'] : date( 'j', current_time( 'timestamp' ) ) + 1;
	}

	/**
	 * @param $trigger
	 *
	 * @return int
	 */
	public function get_start_day( $trigger ) {
		return ! empty( $trigger['meta']['START_DAY'] ) ? absint( $trigger['meta']['START_DAY'] ) : (int) date( 'j', current_time( 'timestamp' ) ) + 1;
	}

	/**
	 * @param $trigger
	 *
	 * @return mixed|string
	 */
	public function get_recurring_time( $trigger ) {
		return ! empty( $trigger['meta']['SPECIFIC_TIME'] ) ? $trigger['meta']['SPECIFIC_TIME'] : '00:00';
	}

	/**
	 * @param $trigger
	 *
	 * @return int|mixed
	 */
	public function get_repeat_times( $trigger ) {
		return $this->helpers->get_repeat_times( $trigger );
	}

	/**
	 * @param $trigger
	 * @param $recipe_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function schedule_monthly_trigger( $trigger, $recipe_id ) {
		$repeat = $this->get_repeat_times( $trigger );

		if ( intval( '-1' ) === intval( $repeat ) ) {
			$this->schedule_until_cancelled( $trigger, $recipe_id );

			return;
		}

		$this->schedule_repeated_events( $trigger, $recipe_id );
	}

	/**
	 * @param $trigger
	 * @param $recipe_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function schedule_until_cancelled( $trigger, $recipe_id ) {
		$trigger_id  = $this->helpers->get_trigger_id( $trigger );
		$hook        = $this->trigger_hook;
		$time        = $this->get_recurring_time( $trigger );
		$number      = $this->get_recurring_number( $trigger );
		$unit        = strtolower( $this->get_recurring_unit( $trigger ) );
		$repeat      = $this->get_repeat_times( $trigger );
		$start_day   = $this->get_start_day( $trigger );
		$args        = array( $trigger_id, $recipe_id, $number, $unit, $time, $repeat );
		$current_day = (int) date( 'j' );

		// Adjust for the first occurrence
		$string = 'first day of this month';
		// If start date is before the current day, so we need to adjust the start date.
		if ( $start_day < $current_day ) {
			$string = "first day of +{$number} months";
		}

		$start_date = new \DateTime( $string, wp_timezone() );
		// Set the start date
		$add_days = $start_day - 1;
		$start_date->modify( "+{$add_days} days" );

		// Set the time
		$start_date->modify( "$time" );

		// Schedule the recurring action using Action Scheduler
		as_schedule_recurring_action( $start_date->getTimestamp(), MONTH_IN_SECONDS * $number, $hook, $args, 'Uncanny Automator' );
	}

	/**
	 * @param $trigger
	 * @param $recipe_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function schedule_repeated_events( $trigger, $recipe_id ) {
		$trigger_id  = $this->helpers->get_trigger_id( $trigger );
		$hook        = $this->trigger_hook;
		$time        = $this->get_recurring_time( $trigger );
		$number      = $this->get_recurring_number( $trigger );
		$unit        = strtolower( $this->get_recurring_unit( $trigger ) );
		$repeat      = $this->get_repeat_times( $trigger );
		$day         = $this->get_specific_day( $trigger );
		$args        = array( $trigger_id, $recipe_id, $number, $unit, $time, $repeat );
		$current_day = (int) date( 'j' );

		// Adjust for the first occurrence
		$string = 'first day of this month';
		// If start date is before the current day, so we need to adjust the start date.
		if ( $day < $current_day ) {
			$string = "first day of +{$number} months";
		}

		// If last day is selected, we need to adjust the start date.
		if ( 'last' === strtolower( $day ) ) {
			$string = 'last day of this month';
		}

		$start_date = new \DateTime( $string, wp_timezone() );

		if ( 'last' !== strtolower( $day ) ) {
			// Set the start date
			$add_days = $day - 1;
			$start_date->modify( "+{$add_days} days" );
		}

		// Set the time
		$start_date->modify( "$time" );

		for ( $i = 1; $i <= $repeat; $i ++ ) {
			if ( $i > 1 ) {
				if ( 'last' === strtolower( $day ) ) {
					$start_date->modify( 'last day of next month' );
				} else {
					$start_date->modify( "+{$number} {$unit}" );
				}
			}
			as_schedule_single_action( $start_date->getTimestamp(), $hook, $args, 'Uncanny Automator' );
		}
	}

	/**
	 * @param $trigger
	 * @param $recipe_id
	 * @param $unit
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function schedule_non_monthly_trigger( $trigger, $recipe_id, $unit ) {
		$trigger_id = $this->helpers->get_trigger_id( $trigger );
		$hook       = $this->trigger_hook;
		$number     = $this->get_recurring_number( $trigger );
		$time       = $this->get_recurring_time( $trigger );

		$args = array( $trigger_id, $recipe_id, $number, $unit, $time );

		// Create a DateTime object with EST time zone
		$new_date = new \DateTime( date( 'Y-m-d', current_time( 'timestamp' ) ) . ' ' . $time, wp_timezone() );

		// Set th default duration to 1 day
		$duration = DAY_IN_SECONDS * $number;

		switch ( $unit ) {
			case 'HOUR':
				$duration = HOUR_IN_SECONDS * $number;
				$unit     = 'hour';
				break;
			case 'DAY':
				$duration = DAY_IN_SECONDS * $number;
				$unit     = 'day';
				break;
			case 'WEEK':
				$duration = WEEK_IN_SECONDS * $number;
				$unit     = 'week';
				break;
			case 'YEAR':
				$duration = YEAR_IN_SECONDS * $number;
				$unit     = 'year';
				break;
		}

		if ( time() >= $new_date->getTimestamp() ) {
			$new_date->modify( "+{$number} {$unit}" );
		}

		$interval = "+{$number} {$unit}";

		// 0.5 hour is not adding 30 minutes to the schedule
		if ( $number < 1 && 'hour' === $unit ) {
			$number   = round( ( HOUR_IN_SECONDS * $number ) / 60, 0 );
			$interval = "+{$number} minute";
		}

		$this->helpers->setup_a_schedule(
			array(
				$new_date,
				$trigger_id,
				$hook,
				$args,
				$duration,
				$interval,
			)
		);
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

		$repeat_times = sprintf( esc_html__( '%1$s time(s)', 'uncanny-automator-pro' ), $repeat_times );

		if ( intval( '-1' ) === intval( $repeat_times ) ) {
			$repeat_times = esc_html__( 'Until cancelled', 'uncanny-automator-pro' );
		}

		$tokens = array(
			$this->get_trigger_meta() => $completed_trigger['meta'][ $this->get_trigger_meta() ],
			'RECURRING_NUMBER'        => $this->get_recurring_number( $completed_trigger ),
			'RECURRING_UNIT'          => ucfirst( strtolower( $this->get_recurring_unit( $completed_trigger ) ) ),
			'SPECIFIC_TIME'           => $this->get_recurring_time( $completed_trigger ),
			'SPECIFIC_DAY'            => $this->get_specific_day( $completed_trigger ),
			'START_DAY'               => $this->get_start_day( $completed_trigger ),
			'REPEAT_TIMES'            => $repeat_times,
		);

		return $tokens;
	}
}
