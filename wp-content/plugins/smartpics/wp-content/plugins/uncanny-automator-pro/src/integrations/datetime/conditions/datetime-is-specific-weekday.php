<?php

namespace Uncanny_Automator_Pro;

/**
 * Class DATETIME_IS_SPECIFIC_WEEKDAY
 *
 * @package Uncanny_Automator_Pro
 */
class DATETIME_IS_SPECIFIC_WEEKDAY extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'DATETIME';
		$this->name         = __( '{{The day}} {{is}} {{a specific day of the week}}', 'uncanny-automator-pro' );
		$this->code         = 'IS_SPECIFIC_WEEKDAY';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( '{{The day:%1$s}} {{is:%2$s}} {{a specific day of the week:%3$s}}', 'uncanny-automator-pro' ),
			'DATE',
			'CRITERIA',
			'WEEKDAY'
		);
		$this->requires_user = false;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {
		$conditions = array(
			'option_code'           => 'CRITERIA',
			'label'                 => esc_html__( 'Criteria', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => array(
				array(
					'value' => 'is',
					'text'  => esc_html__( 'is', 'uncanny-automator-pro' ),
				),
				array(
					'value' => 'is_not',
					'text'  => esc_html__( 'is not', 'uncanny-automator-pro' ),
				),
				array(
					'value' => 'is_before',
					'text'  => esc_html__( 'is before', 'uncanny-automator-pro' ),
				),
				array(
					'value' => 'is_after',
					'text'  => esc_html__( 'is after', 'uncanny-automator-pro' ),
				),
			),
			'supports_custom_value' => false,
		);

		$weekdays = array(
			'option_code'           => 'WEEKDAY',
			'label'                 => esc_html__( 'Day', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_weekday_options(),
			'supports_custom_value' => false,
		);

		return array(
			// Token field
			$this->field->text(
				array(
					'option_code' => 'DATE',
					'label'       => esc_html__( 'Date', 'uncanny-automator-pro' ),
					'placeholder' => '{{current_date_and_time}}',
					'default'     => '{{current_date_and_time}}',
					'input_type'  => 'text',
					'required'    => true,
				)
			),
			// Criteria field
			$this->field->select_field_args( $conditions ),
			// Month field
			$this->field->select_field_args( $weekdays ),
		);
	}

	public function get_weekday_options() {
		$months = array(
			array(
				'text'  => __( 'Monday', 'uncanny-automator-pro' ),
				'value' => '1',
			),
			array(
				'text'  => __( 'Tuesday', 'uncanny-automator-pro' ),
				'value' => '2',
			),
			array(
				'text'  => __( 'Wednesday', 'uncanny-automator-pro' ),
				'value' => '3',
			),
			array(
				'text'  => __( 'Thursday', 'uncanny-automator-pro' ),
				'value' => '4',
			),
			array(
				'text'  => __( 'Friday', 'uncanny-automator-pro' ),
				'value' => '5',
			),
			array(
				'text'  => __( 'Saturday', 'uncanny-automator-pro' ),
				'value' => '6',
			),
			array(
				'text'  => __( 'Sunday', 'uncanny-automator-pro' ),
				'value' => '7',
			),
		);

		return apply_filters( 'automator_datetime_conditions_day_options', $months );
	}

	/**
	 * Evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the condition is not met.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		try {
			$input_date = $this->get_parsed_option( 'DATE' );

			$day_a = DateTime_Helpers::get_weekday( $input_date );

			$day_b = (int) $this->get_parsed_option( 'WEEKDAY' );

			$day_b = DateTime_Helpers::shift_weekday( $day_b );

			$day_b_readable = $this->get_parsed_option( 'WEEKDAY_readable' );

		} catch ( \Exception $e ) {
			$this->condition_failed( $e->getMessage() );
			return;
		}

		$criteria = $this->get_parsed_option( 'CRITERIA' );

		$error = $input_date . ' %s ' . $day_b_readable;

		switch ( $criteria ) {
			case 'is':
				if ( $day_a !== $day_b ) {
					$this->condition_failed( sprintf( $error, __( ' is not ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_not':
				if ( $day_a === $day_b ) {
					$this->condition_failed( sprintf( $error, __( ' is ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_before':
				if ( $day_a === $day_b || $day_a > $day_b ) {
					$this->condition_failed( sprintf( $error, __( ' is not before ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_after':
				if ( $day_a === $day_b || $day_a < $day_b ) {
					$this->condition_failed( sprintf( $error, __( ' is not after ', 'uncanny-automator-pro' ) ) );
				}
				break;
		}

	}
}
