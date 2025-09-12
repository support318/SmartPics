<?php

namespace Uncanny_Automator_Pro;

/**
 * Class DATETIME_IS_SPECIFIC_DATE
 *
 * @package Uncanny_Automator_Pro
 */
class DATETIME_IS_SPECIFIC_DATE extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'DATETIME';
		$this->name         = __( '{{The date}} {{is}} {{a specific date}}', 'uncanny-automator-pro' );
		$this->code         = 'IS_SPECIFIC_DATE';
		$this->dynamic_name = sprintf(
		/* translators: Email address */
			esc_html__( '{{The date:%1$s}} {{is:%2$s}} {{a specific date:%3$s}}', 'uncanny-automator-pro' ),
			'DATE_A',
			'CRITERIA',
			'DATE_B'
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
					'label'       => esc_html__( 'Date', 'uncanny-automator-pro' ),
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
			$date_a = DateTime_Helpers::get_date( $this->get_parsed_option( 'DATE_A' ) );
			$date_b = DateTime_Helpers::get_date( $this->get_parsed_option( 'DATE_B' ) );

		} catch ( \Exception $e ) {
			$this->condition_failed( $e->getMessage() );
			return;
		}

		$criteria = $this->get_parsed_option( 'CRITERIA' );

		$format = get_option( 'date_format' );

		$error = $input . ' %s ' . $date_b->format( $format );

		switch ( $criteria ) {
			case 'is':
				if ( $date_a != $date_b ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is not ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_not':
				if ( $date_a == $date_b ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_before':
				if ( $date_a == $date_b || $date_a > $date_b ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is not before ', 'uncanny-automator-pro' ) ) );
				}
				break;
			case 'is_after':
				if ( $date_a == $date_b || $date_a < $date_b ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					$this->condition_failed( sprintf( $error, __( ' is not after ', 'uncanny-automator-pro' ) ) );
				}
				break;
		}

	}


}
