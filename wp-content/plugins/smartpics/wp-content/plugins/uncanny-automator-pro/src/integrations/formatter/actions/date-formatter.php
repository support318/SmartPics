<?php

namespace Uncanny_Automator_Pro\Integrations\Formatter;

class Date_Formatter extends \Uncanny_Automator\Recipe\Action {

	/**
	 * setup_action
	 *
	 * @return void
	 */
	protected function setup_action() {

		// Define the Actions's info
		$this->set_integration( 'FORMATTER' );
		$this->set_action_code( 'DATE' );
		$this->set_action_meta( 'INPUT' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		// Define the Action's sentence
		// translators: input date, from format, to format
		$this->set_sentence( sprintf( esc_attr__( 'Convert {{date:%1$s}} from {{format:%2$s}} into {{format:%3$s}}', 'uncanny-automator-pro' ), $this->get_action_meta(), 'FROM_FORMAT:' . $this->get_action_meta(), 'TO_FORMAT:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr__( 'Convert {{date}} into {{format}}', 'uncanny-automator-pro' ) );

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
					'label'       => 'Input',
					'placeholder' => _x( 'Enter date', 'Date formatter', 'uncanny-automator-pro' ),
					'input_type'  => 'text',
				)
			),
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'              => 'FROM_FORMAT',
					'custom_value_description' => _x( 'Enter a custom date format', 'Date formatter', 'uncanny-automator-pro' ),
					'supports_custom_value'    => _x( 'Custom format', 'Date formatter', 'uncanny-automator-pro' ),
					'label'                    => _x( 'Input format', 'Date formatter', 'uncanny-automator-pro' ),
					'options'                  => $this->input_date_format_options(),
				)
			),
			Automator()->helpers->recipe->field->select(
				array(
					'option_code'              => 'TO_FORMAT',
					'custom_value_description' => _x( 'Enter a custom date format', 'Date formatter', 'uncanny-automator-pro' ),
					'supports_custom_value'    => _x( 'Custom format', 'Date formatter', 'uncanny-automator-pro' ),
					'label'                    => _x( 'Output format', 'Date formatter', 'uncanny-automator-pro' ),
					'options'                  => $this->date_format_options(),
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
				'name' => _x( 'Output', 'Date formatter', 'uncanny-automator-pro' ),
				'type' => 'date',
			),
		);
	}

	/**
	 * process_action
	 *
	 * @param mixed $user_id
	 * @param mixed $action_data
	 * @param mixed $recipe_id
	 * @param mixed $args
	 * @param mixed $parsed
	 *
	 * @return bool
	 */
	public function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$input         = $this->get_parsed_meta_value( 'INPUT' );
		$input_format  = $this->get_parsed_meta_value( 'FROM_FORMAT' );
		$output_format = $this->get_parsed_meta_value( 'TO_FORMAT' );

		$this->hydrate_tokens(
			array(
				'OUTPUT' => $this->format( $input, $input_format, $output_format ),
			)
		);

		return true;
	}

	/**
	 * format
	 *
	 * @param mixed $input
	 * @param mixed $from_format
	 * @param mixed $to_format
	 *
	 * @return string
	 */
	public function format( $input, $from_format, $to_format ) {

		$date_object = $this->date_object( $input, $from_format );

		$timestamp = $date_object->getTimestamp();

		$site_locale = \get_locale();

		// The current locale is set to user's locale by default and used in the wp_date function. We want to force site locale instead for consistency.
		$current_locale = \determine_locale();

		// Force site locale but allow users to override this.
		\switch_to_locale( apply_filters( 'automator_pro_formatter_locale', $site_locale, $from_format, $to_format ) );

		$output = \wp_date( $to_format, $timestamp );

		//Switch back to the initial locale.
		\switch_to_locale( $current_locale );

		return apply_filters( 'automator_pro_formatter_format_date', $output, $input, $from_format, $to_format, $date_object );
	}

	/**
	 * date_object
	 *
	 * @param mixed $input
	 * @param mixed $format
	 *
	 * @return DateTime
	 */
	public function date_object( $input, $format = 'auto' ) {

		$timezone = wp_timezone();

		if ( 'auto' === $format ) {
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		}

		$date = \DateTime::createFromFormat( $format, $input, $timezone );

		if ( false === $date ) {
			if ( is_numeric( $input ) ) {
				$date = \DateTime::createFromFormat( 'U', $input );
				$date->setTimezone( $timezone );

				return $date;
			}
			$date = new \DateTime( $input, $timezone );
		}

		if ( ! $date instanceof \DateTime ) {
			throw new \Exception( __( 'Unable to parse the date/time', 'uncanny-automator-pro' ) );
		}

		return $date;
	}

	/**
	 * common_date_formats
	 *
	 * @return array
	 */
	public static function common_date_formats() {

		$formats = array(
			_x( 'Mmm DD, YYYY', 'Date formatter', 'uncanny-automator-pro' )                                                                            => 'M d, Y',
			_x( 'MM/DD/YYYY', 'Date formatter', 'uncanny-automator-pro' )                                                                              => 'm/d/Y',
			_x( 'MM/DD/YY', 'Date formatter', 'uncanny-automator-pro' )                                                                                => 'm/d/y',
			_x( 'DD/MM/YY', 'Date formatter', 'uncanny-automator-pro' )                                                                                => 'd/m/y',
			_x( 'DD/MM/YYYY', 'Date formatter', 'uncanny-automator-pro' )                                                                              => 'd/m/Y',
			_x( 'ISO 8601', 'Date formatter', 'uncanny-automator-pro' )                                                                                => 'c',
			_x( 'RFC 2822', 'Date formatter', 'uncanny-automator-pro' )                                                                                => 'r',
			_x( 'Unix timestamp', 'Date formatter', 'uncanny-automator-pro' )                                                                          => 'U',
			_x( 'A full numeric representation of a year, at least 4 digits, with - for years BCE', 'Date formatter', 'uncanny-automator-pro' )        => 'Y',
			_x( 'The day of the month (from 01 to 31)', 'Date formatter', 'uncanny-automator-pro' )                                                    => 'd',
			_x( 'A textual representation of a day (three letters)', 'Date formatter', 'uncanny-automator-pro' )                                       => 'D',
			_x( 'The day of the month without leading zeros (1 to 31)', 'Date formatter', 'uncanny-automator-pro' )                                    => 'j',
			_x( 'A full textual representation of a day', 'Date formatter', 'uncanny-automator-pro' )                                                  => 'l',
			_x( 'Simple time (12 hour format)', 'Date formatter', 'uncanny-automator-pro' )                                                            => 'g:i a',
			_x( 'Time with seconds (12 hour format)', 'Date formatter', 'uncanny-automator-pro' )                                                      => 'g:i:s a',
			_x( 'Simple time (24 hour format)', 'Date formatter', 'uncanny-automator-pro' )                                                            => 'H:i',
			_x( 'Time with seconds (24 hour format)', 'Date formatter', 'uncanny-automator-pro' )                                                      => 'H:i:s',
			_x( 'Timezone offset in seconds', 'Date formatter', 'uncanny-automator-pro' )                                                              => 'Z',
			_x( 'Timezone abbreviation', 'Date formatter', 'uncanny-automator-pro' )                                                                   => 'T',
			_x( 'Difference to Greenwich time (GMT) in hours:minutes', 'Date formatter', 'uncanny-automator-pro' )                                     => 'P',
			_x( 'Difference to Greenwich time (GMT) in hours', 'Date formatter', 'uncanny-automator-pro' )                                             => 'O',
			_x( 'Whether the date is in daylights savings time (1 if Daylight Savings Time, 0 otherwise)', 'Date formatter', 'uncanny-automator-pro' ) => 'I',
			_x( 'The ISO-8601 numeric representation of a day (1 for Monday, 7 for Sunday)', 'Date formatter', 'uncanny-automator-pro' )               => 'N',
			_x( 'A full textual representation of a month (January through December)', 'Date formatter', 'uncanny-automator-pro' )                     => 'F',
			_x( 'A numeric representation of a month (from 01 to 12)', 'Date formatter', 'uncanny-automator-pro' )                                     => 'm',
			_x( 'A short textual representation of a month (three letters)', 'Date formatter', 'uncanny-automator-pro' )                               => 'M',
		);

		return $formats;
	}

	/**
	 * date_format_options
	 *
	 * @return array
	 */
	public function date_format_options() {

		$formats = self::common_date_formats();

		$base_date = new \DateTime();

		$options = array();

		foreach ( $formats as $title => $format ) {
			$options[] = array(
				'text'  => $title . ' (' . $base_date->format( $format ) . ')',
				'value' => $format,
			);
		}

		return apply_filters( 'automator_pro_formatter_output_date_formats', $options );
	}

	/**
	 * input_date_format_options
	 *
	 * @return array
	 */
	public function input_date_format_options() {

		$options = array();

		$options[] = array(
			'text'  => __( 'Automatic format recognition (recommended)', 'uncanny-automator-pro' ),
			'value' => 'auto',
		);

		$options[] = array(
			'text'  => __( 'Timestamp', 'uncanny-automator-pro' ),
			'value' => 'U',
		);

		return apply_filters( 'automator_pro_formatter_input_date_formats', $options );

	}
}
