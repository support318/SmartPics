<?php

namespace Uncanny_Automator_Pro\Integrations\Schedule;

/**
 *
 */
class Trigger_Specific_Date extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * @var
	 */
	public $helpers;

	/**
	 * @var string
	 */
	public $trigger_hook = 'automator_pro_trigger_on_specific_date';

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
		$this->set_trigger_code( 'TRIGGER_ON_SPECIFIC_DATE' );
		$this->set_trigger_meta( 'TRIGGER_SPECIFIC_DATE' );
		$this->set_is_pro( true );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( 'Run on {{a specific date:%1$s}} and {{a specific time:%2$s}}', 'schedule', 'uncanny-automator-pro' ), 'TRIGGER_DATE:' . $this->get_trigger_meta(), 'SPECIFIC_TIME:' . $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Run on {{a specific date}} and {{a specific time}}', 'schedule', 'uncanny-automator-pro' ) );
		$this->add_action( $this->trigger_hook, 10, 4 );
		$this->set_support_link( $this->helpers->get_support_link( $this->trigger_code ) );
	}

	/**
	 * @return \array[][]
	 */
	public function options() {
		return array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code'           => 'TRIGGER_DATE',
					'label'                 => esc_attr_x( 'Date', 'schedule', 'uncanny-automator-pro' ),
					'placeholder'           => esc_attr_x( 'yyyy-mm-dd', 'schedule', 'uncanny-automator-pro' ),
					'input_type'            => 'date',
					'required'              => true,
					'supports_custom_value' => false,
					'description'           => esc_html_x( 'The date at which this recipe will run.', 'schedule', 'uncanny-automator-pro' ),
				)
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

		$trigger_id = $this->helpers->get_trigger_id( $trigger );
		$hook       = $this->trigger_hook;

		$date = $this->get_date( $trigger );
		$time = $this->get_time( $trigger );

		if ( empty( $date ) || empty( $time ) ) {
			return;
		}

		$args = array( $trigger_id, $recipe_id, $date, $time );

		// Create a DateTime object with EST time zone
		$new_date = new \DateTime( "$date $time", wp_timezone() );

		// Convert the time to UTC
		$new_date->setTimezone( new \DateTimeZone( 'UTC' ) );

		// Schedule the recurring action to run every week on the specified day.
		as_schedule_single_action( $new_date->getTimestamp(), $hook, $args, 'Uncanny Automator' );
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
	public function get_date( $trigger ) {

		return ! empty( $trigger['meta']['TRIGGER_DATE'] ) ? $trigger['meta']['TRIGGER_DATE'] : date( 'Y-m-d', current_time( 'timestamp' ) );
	}

	/**
	 * @param $trigger
	 *
	 * @return mixed|string
	 */
	public function get_time( $trigger ) {

		return ! empty( $trigger['meta']['SPECIFIC_TIME'] ) ? $trigger['meta']['SPECIFIC_TIME'] : '00:00';
	}

	/**
	 * @param $completed_trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		$tokens = array(
			'TRIGGER_DATE'  => $this->get_date( $completed_trigger ),
			'SPECIFIC_TIME' => $this->get_time( $completed_trigger ),
		);

		return $tokens;
	}
}
