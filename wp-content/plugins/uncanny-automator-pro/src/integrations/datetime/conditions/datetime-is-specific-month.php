<?php

namespace Uncanny_Automator_Pro;

/**
 * Class DATETIME_IS_SPECIFIC_MONTH
 *
 * @package Uncanny_Automator_Pro
 */
class DATETIME_IS_SPECIFIC_MONTH extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'DATETIME';
		$this->name         = __( '{{The date}} {{is}} {{a specific month}}', 'uncanny-automator-pro' );
		$this->code         = 'IS_SPECIFIC_MONTH';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( '{{The date:%1$s}} {{is:%2$s}} {{a specific month:%3$s}}', 'uncanny-automator-pro' ),
			'DATE',
			'CRITERIA',
			'MONTH'
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

		$months = array(
			'option_code'           => 'MONTH',
			'label'                 => esc_html__( 'Month', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_months_options(),
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
			$this->field->select_field_args( $months ),
		);
	}

	public function get_months_options() {
		$months = array(
			array(
				'text'  => __( 'January', 'uncanny-automator-pro' ),
				'value' => '1',
			),
			array(
				'text'  => __( 'February', 'uncanny-automator-pro' ),
				'value' => '2',
			),
			array(
				'text'  => __( 'March', 'uncanny-automator-pro' ),
				'value' => '3',
			),
			array(
				'text'  => __( 'April', 'uncanny-automator-pro' ),
				'value' => '4',
			),
			array(
				'text'  => __( 'May', 'uncanny-automator-pro' ),
				'value' => '5',
			),
			array(
				'text'  => __( 'June', 'uncanny-automator-pro' ),
				'value' => '6',
			),
			array(
				'text'  => __( 'July', 'uncanny-automator-pro' ),
				'value' => '7',
			),
			array(
				'text'  => __( 'August', 'uncanny-automator-pro' ),
				'value' => '8',
			),
			array(
				'text'  => __( 'September', 'uncanny-automator-pro' ),
				'value' => '9',
			),
			array(
				'text'  => __( 'October', 'uncanny-automator-pro' ),
				'value' => '10',
			),
			array(
				'text'  => __( 'November', 'uncanny-automator-pro' ),
				'value' => '11',
			),
			array(
				'text'  => __( 'December', 'uncanny-automator-pro' ),
				'value' => '12',
			),
		);

		return apply_filters( 'automator_datetime_conditions_months_options', $months );
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

			$month_a = DateTime_Helpers::get_month( $input_date );

			$month_b = (int) $this->get_parsed_option( 'MONTH' );

			$month_b_readable = $this->get_parsed_option( 'MONTH_readable' );

		} catch ( \Exception $e ) {
			$this->condition_failed( $e->getMessage() );
			return;
		}

		$criteria = $this->get_parsed_option( 'CRITERIA' );

		$error = $input_date . ' %s ' . $month_b_readable;

		switch ( $criteria ) {
			case 'is':
				if ( $month_a !== $month_b ) {
					$this->condition_failed( sprintf( $error, __( ' is not ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_not':
				if ( $month_a === $month_b ) {
					$this->condition_failed( sprintf( $error, __( ' is ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_before':
				if ( $month_a === $month_b || $month_a > $month_b ) {
					$this->condition_failed( sprintf( $error, __( ' is not before ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_after':
				if ( $month_a === $month_b || $month_a < $month_b ) {
					$this->condition_failed( sprintf( $error, __( ' is not after ', 'uncanny-automator-pro' ) ) );
				}
				break;
		}

	}
}
