<?php

namespace Uncanny_Automator_Pro;

/**
 * Class DATETIME_IS_BETWEEN_SPECIFIC_DATES
 *
 * @package Uncanny_Automator_Pro
 */
class DATETIME_IS_BETWEEN_SPECIFIC_DATES extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'DATETIME';
		$this->name         = __( '{{The date}} {{is}} between {{a specific date}} and {{a specific date}}', 'uncanny-automator-pro' );
		$this->code         = 'IS_BETWEEN_SPECIFIC_DATES';
		$this->dynamic_name = sprintf(
		/* translators: 1. Date 2. Criteria 3. Date 4. Date */
			esc_html__( '{{The date:%1$s}} {{is:%2$s}} between {{a specific date:%3$s}} and {{a specific date:%4$s}}', 'uncanny-automator-pro' ),
			'DATE_A',
			'CRITERIA',
			'DATE_B',
			'DATE_C'
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
			),
			'supports_custom_value' => false,
		);

		return array(
			// Token field
			$this->field->text(
				array(
					'option_code' => 'DATE_A',
					'label'       => esc_html__( 'Date', 'uncanny-automator-pro' ),
					'placeholder' => '{{current_date_and_time}}',
					'default'     => '{{current_date_and_time}}',
					'input_type'  => 'text',
					'required'    => true,
				)
			),
			// Criteria field
			$this->field->select_field_args( $conditions ),
			// Value field
			$this->field->text(
				array(
					'option_code' => 'DATE_B',
					'label'       => esc_html__( 'Start date', 'uncanny-automator-pro' ),
					'input_type'  => 'date',
					'required'    => true,
				)
			),
			$this->field->text(
				array(
					'option_code' => 'DATE_C',
					'label'       => esc_html__( 'End date', 'uncanny-automator-pro' ),
					'input_type'  => 'date',
					'required'    => true,
				)
			),
		);
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
			$input  = $this->get_parsed_option( 'DATE_A' );
			$date_a = DateTime_Helpers::get_date( $input );
			$date_b = DateTime_Helpers::get_date( $this->get_parsed_option( 'DATE_B' ) );
			$date_c = DateTime_Helpers::get_date( $this->get_parsed_option( 'DATE_C' ) );

		} catch ( \Exception $e ) {
			$this->condition_failed( $e->getMessage() );
			return;
		}

		$criteria = $this->get_parsed_option( 'CRITERIA' );

		$date_format = get_option( 'date_format' );

		$error = $input . ' %s ' . $date_b->format( $date_format ) . ' %s ' . $date_c->format( $date_format );

		switch ( $criteria ) {
			case 'is':
				if ( ( $date_a != $date_b && $date_a < $date_b ) || ( $date_a != $date_c && $date_a > $date_c ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is not between', 'uncanny-automator-pro' ), __( ' and ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_not':
				if ( $date_a == $date_b || $date_a == $date_c || ( $date_a > $date_b && $date_a < $date_c ) ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is between', 'uncanny-automator-pro' ), __( ' and ', 'uncanny-automator-pro' ) ) );
				}
				break;
		}

	}
}
