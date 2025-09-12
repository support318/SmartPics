<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Recipe\Log_Properties;

/**
 * Class UOA_GENERATE_NEW_DATETIME
 *
 * @package Uncanny_Automator_Pro
 */
class UOA_GENERATE_NEW_DATETIME {

	use Recipe\Actions;
	use Recipe\Action_Tokens;
	use Log_Properties;

	/**
	 * UOA_GENERATE_NEW_DATETIME constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Setup the action's details.
	 */
	protected function setup_action() {
		$this->set_integration( 'DATETIME' );
		$this->set_is_pro( true );
		$this->set_action_meta( 'GENERATE_NEW_DATETIME_META' );
		$this->set_action_code( 'GENERATE_NEW_DATETIME' );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_html_x( 'Generate a {{date and time:%1$s}}', 'Uncanny Automator', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_html_x( 'Generate a {{date and time}}', 'Uncanny Automator', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'NEW_DATETIME' => array(
					'name' => _x( 'Generated date and time', 'Uncanny Automator', 'uncanny-automator-pro' ),
					'type' => 'text',
				),
			),
			$this->get_action_code()
		);
		$this->register_action();
	}

	/**
	 * Define the options for this action.
	 *
	 * @return array
	 */
	public function load_options() {
		$options = array(
			'options_group' => array(
				$this->get_action_meta() => array(
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'DATE_MODIFIER',
							'label'       => esc_html_x( 'Date modifier', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'description' => esc_html_x( 'Enter a date modification string (e.g., "next Thursday", "+5 days")', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'required'    => true,
							'input_type'  => 'text',
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'TIME_MODIFIER',
							'label'       => esc_html_x( 'Time modifier', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'description' => esc_html_x( 'Enter a time modification string (e.g., "-5 hours", "+30 minutes")', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'text',
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
				),
			),
		);

		return $options;
	}

	/**
	 * @return mixed|null
	 */
	public function date_format_options() {

		//$formats   = $this->date_time_formats();
		$formats = array(
			'Mmm DD, YYYY 12-hour'            => 'M d, Y g:i a',
			'Mmm DD, YYYY 24-hour'            => 'M d, Y H:i',
			'MM/DD/YYYY 12-hour'              => 'm/d/Y g:i a',
			'MM/DD/YYYY 24-hour with seconds' => 'm/d/Y H:i:s',
			'DD/MM/YYYY 12-hour with seconds' => 'd/m/Y g:i:s a',
			'DD/MM/YYYY 24-hour'              => 'd/m/Y H:i',
			'YYYY-MM-DD 24-hour with seconds' => 'Y-m-d H:i:s',
			'DD-Mmm-YYYY 12-hour'             => 'd-M-Y g:i a',
			'YYYY/MM/DD 24-hour'              => 'Y/m/d H:i',
			// Additional formats
			'RFC 2822 Format'                 => 'r', // Example: Thu, 21 Dec 2000 16:01:07 +0200
			'ISO 8601 Format'                 => 'c', // Example: 2004-02-12T15:19:21+00:00
			'Unix Timestamp'                  => 'U', // Example: 161718
			// Considering adding UTC might mean showing the time in UTC, adding a specific format for it.
			'UTC Time'                        => 'Y-m-d\TH:i:s\Z', // ISO 8601 format in UTC
		);

		$base_date = new \DateTime();

		$options   = array();
		$options[] = array(
			'value' => 'site_format',
			'text'  => _x( "Site's date and time format", 'Date formatter', 'uncanny-automator-pro' ),
		);

		foreach ( $formats as $title => $format ) {
			$options[] = array(
				'text'  => $title . ' (' . $base_date->format( $format ) . ')',
				'value' => $format,
			);
		}

		return apply_filters( 'automator_pro_formatter_output_date_formats', $options );
	}

	/**
	 * Process the action to generate a new date and time considering the site's timezone.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param array $parsed
	 *
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$date_modifier = sanitize_text_field( $parsed['DATE_MODIFIER'] );
		$time_modifier = isset( $parsed['TIME_MODIFIER'] ) ? sanitize_text_field( $parsed['TIME_MODIFIER'] ) : '';
		$to_format     = $parsed['TO_FORMAT'];

		// Get the site's timezone
		$site_timezone_string = get_option( 'timezone_string' );
		$site_timezone        = new \DateTimeZone( $site_timezone_string ? $site_timezone_string : 'UTC' );

		// Create a DateTime object with the site's timezone
		$datetime = new \DateTime( 'now', $site_timezone );

		// Retain current time
		$current_time = $datetime->format( 'H:i:s' );

		$datetime->modify( $date_modifier );

		// Apply current time
		$datetime->modify( $current_time );

		// Now modify the time if provided
		if ( ! empty( $time_modifier ) ) {
			$datetime->modify( $time_modifier );
		}

		// Check if the user selected the site's date and time format
		if ( 'site_format' === $to_format ) {
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			$to_format   = $date_format . ' ' . $time_format;
		}

		// Format the datetime according to the selected format
		$new_datetime = $datetime->format( $to_format );

		$this->hydrate_tokens(
			array(
				'NEW_DATETIME' => $new_datetime,
			)
		);

		// Set log properties.
		$this->set_log_properties(
			array(
				'type'  => 'text',
				'label' => __( 'Generated date and time', 'uncanny-automator-pro' ),
				'value' => $new_datetime,
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

}
