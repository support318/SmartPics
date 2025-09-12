<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Recipe\Log_Properties;

/**
 * Class UOA_GENERATE_NEW_DATE
 *
 * @package Uncanny_Automator_Pro
 */
class UOA_GENERATE_NEW_DATE {

	use Recipe\Actions;
	use Recipe\Action_Tokens;
	use Log_Properties;

	/**
	 * UOA_GENERATE_NEW_DATE constructor.
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
		$this->set_action_meta( 'GENERATE_NEW_DATE_META' );
		$this->set_action_code( 'GENERATE_NEW_DATE' );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_html_x( 'Generate a {{date:%1$s}}', 'Uncanny Automator', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_html_x( 'Generate a {{date}}', 'Uncanny Automator', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'NEW_DATE' => array(
					'name' => _x( 'Generated date', 'Uncanny Automator', 'uncanny-automator-pro' ),
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
		return array(
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
	}

	/**
	 * @return mixed|null
	 */
	public function date_format_options() {

		$formats   = \Uncanny_Automator_Pro\Integrations\Formatter\Date_Formatter::common_date_formats();
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
	 * Process the action to generate a new date considering the site's timezone.
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
		$date_format   = sanitize_text_field( $parsed['TO_FORMAT'] );

		if ( empty( $date_format ) ) {
			$date_format = get_option( 'date_format' );
		}

		// Get the site's timezone
		$site_timezone_string = get_option( 'timezone_string' );
		$site_timezone        = new \DateTimeZone( $site_timezone_string ? $site_timezone_string : 'UTC' );

		// Create a DateTime object with the site's timezone
		$date = new \DateTime( 'now', $site_timezone );
		$date->modify( $date_modifier );

		// Format the date to Y-m-d
		$new_date = $date->format( $date_format );

		$this->hydrate_tokens(
			array(
				'NEW_DATE' => $new_date,
			)
		);

		// Set log properties.
		$this->set_log_properties(
			array(
				'type'  => 'text',
				'label' => __( 'Generated date', 'uncanny-automator-pro' ),
				'value' => $new_date,
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

}
