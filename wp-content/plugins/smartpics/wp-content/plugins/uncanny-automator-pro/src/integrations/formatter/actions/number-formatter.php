<?php

namespace Uncanny_Automator_Pro\Integrations\Formatter;

class Number_Formatter extends \Uncanny_Automator\Recipe\Action {

	/**
	 * setup_action
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Define the Actions's info
		$this->set_integration( 'FORMATTER' );
		$this->set_action_code( 'NUMBER' );
		$this->set_action_meta( 'INPUT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		// Define the Action's sentence
		// translators: input number, format
		$this->set_sentence( sprintf( esc_attr__( 'Convert {{number:%1$s}} into {{format:%2$s}}', 'uncanny-automator-pro' ), $this->get_action_meta(), 'TO_FORMAT:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr__( 'Convert {{number}} into {{format}}', 'uncanny-automator-pro' ) );

	}

	/**
	 * options
	 *
	 * @return array
	 */
	public function options() {

		return array(
			Automator()->helpers->recipe->field->text(
				array(
					'option_code' => $this->get_action_meta(),
					'label'       => _x( 'Input', 'Number formatter', 'uncanny-automator-pro' ),
					'placeholder' => _x( 'Enter number', 'Number formatter', 'uncanny-automator-pro' ),
					'input_type'  => 'text',
				)
			),
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'           => 'TO_FORMAT',
					'label'                 => _x( 'Output format', 'Number formatter', 'uncanny-automator-pro' ),
					'supports_custom_value' => false,
					'options'               => $this->number_format_options(),
				)
			),
		);
	}

	/**
	 * define_tokens
	 *
	 * @return array
	 */
	public function define_tokens() {
		return array(
			'OUTPUT' => array(
				'name' => _x( 'Output', 'Number formatter', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);
	}

	/**
	 * process_action
	 *
	 * @param  mixed $user_id
	 * @param  mixed $action_data
	 * @param  mixed $recipe_id
	 * @param  mixed $args
	 * @param  mixed $parsed
	 * @return bool
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$input = $this->get_parsed_meta_value( 'INPUT' );

		if ( ! is_numeric( $input ) ) {
			throw new \Exception( 'The incoming value "' . $input . '" is not numeric.' );
		}

		$input = $input + 0;

		$output_format = $this->get_parsed_meta_value( 'TO_FORMAT' );

		$this->hydrate_tokens(
			array(
				'OUTPUT' => $this->format( $input, $output_format ),
			)
		);

		return true;
	}

	/**
	 * format
	 *
	 * @param  mixed $input
	 * @param  mixed $format
	 * @return mixed
	 */
	public function format( $input, $format ) {

		$formats = $this->get_formats();

		if ( empty( $formats[ $format ]['callback'] ) ) {
			return $input;
		}

		$callback = $formats[ $format ]['callback'];

		if ( ! is_callable( $callback ) ) {
			return $input;
		}

		$output = call_user_func( $formats[ $format ]['callback'], $input );

		return $output;
	}

	/**
	 * number_format_options
	 *
	 * @return array
	 */
	public function number_format_options() {

		$formats = $this->get_formats();

		$options = array();

		foreach ( $formats as $id => $format ) {
			$options[] = array(
				'value' => $id,
				'text'  => $format['name'],
			);
		}

		return $options;
	}

	/**
	 * get_formats
	 *
	 * @return array
	 */
	public function get_formats() {

		$formats = array();

		$formats['round'] = array(
			'name' => _x( 'Round to integer', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['round_to_one'] = array(
			'name' => _x( 'Round to one decimal place', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['round_to_two'] = array(
			'name' => _x( 'Round to two decimal places', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['currency_english'] = array(
			'name' => _x( 'Currency English notation (1,234.56)', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['currency_french'] = array(
			'name' => _x( 'Currency French notation (1 234,56)', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['currency_dot'] = array(
			'name' => _x( 'Currency with dot decimals (1 234.56)', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['floor'] = array(
			'name' => _x( 'Round down (floor)', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['ceil'] = array(
			'name' => _x( 'Round up (ceil)', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['is_even'] = array(
			'name' => _x( 'Is even (returns 1 for even values, 0 for odd)', 'Number formatter', 'uncanny-automator-pro' ),
		);

		$formats['default_zero'] = array(
			'name' => _x( 'Default zero (replace empty value with zero)', 'Number formatter', 'uncanny-automator-pro' ),
		);

		foreach ( $formats as $id => &$format ) {
			$format['callback'] = array( $this, 'format_' . $id );
		}

		return apply_filters( 'automator_pro_formatter_number_formats', $formats );
	}

	/**
	 * format_round
	 */
	public function format_round( $input ) {
		return round( $input );
	}

	/**
	 * format_round_to_one
	 */
	public function format_round_to_one( $input ) {
		return round( $input, 1 );
	}

	/**
	 * format_round_to_two
	 */
	public function format_round_to_two( $input ) {
		return round( $input, 2 );
	}

	/**
	 * format_currency_english
	 */
	public function format_currency_english( $input ) {
		return number_format( $input, 2, '.', ',' );
	}

	/**
	 * format_currency_french
	 */
	public function format_currency_french( $input ) {
		return number_format( $input, 2, ',', ' ' );
	}

	/**
	 * format_currency_dot
	 */
	public function format_currency_dot( $input ) {
		return number_format( $input, 2, '.', ' ' );
	}

	/**
	 * format_floor
	 */
	public function format_floor( $input ) {
		return floor( $input );
	}

	/**
	 * format_ceil
	 */
	public function format_ceil( $input ) {
		return ceil( $input );
	}

	/**
	 * format_is_even
	 */
	public function format_is_even( $input ) {

		$remainder = $input % 2;

		if ( 0 === $remainder ) {
			return 1;
		}

		return 0;
	}

	/**
	 * format_default_zero
	 */
	public function format_default_zero( $input ) {

		if ( ! empty( $input ) ) {
			return $input;
		}

		return 0;
	}
}
