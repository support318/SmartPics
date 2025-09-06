<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Recipe\Log_Properties;

/**
 * Class UOA_GENERATE_RANDOM_EMAIL
 *
 * @package Uncanny_Automator_Pro
 */
class UOA_GENERATE_RANDOM_EMAIL {

	use Recipe\Actions;
	use Recipe\Action_Tokens;
	use Log_Properties;

	/**
	 * UOA_GENERATE_RANDOM_EMAIL constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Setup the action's details.
	 */
	protected function setup_action() {
		$this->set_integration( 'AUTOMATOR_GENERATOR' );
		$this->set_is_pro( true );
		$this->set_action_meta( 'GENERATE_RAND_EMAIL_META' );
		$this->set_action_code( 'GENERATE_RAND_EMAIL' );
		$this->set_requires_user( false );
		$this->set_sentence( sprintf( esc_html_x( 'Generate a {{random email:%1$s}}', 'Uncanny Automator', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_html_x( 'Generate a random {{email}}', 'Uncanny Automator', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'RANDOM_EMAIL' => array(
					'name' => _x( 'Generated email', 'Uncanny Automator', 'uncanny-automator-pro' ),
					'type' => 'email',
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
							'option_code' => 'RAND_EMAIL_DOMAIN',
							'label'       => esc_html_x( 'Domain name', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'description' => esc_html_x( 'Enter the domain name for the email (e.g., example.com)', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'required'    => true,
							'input_type'  => 'text',
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'RAND_EMAIL_PREFIX',
							'label'       => esc_html_x( 'Prefix', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'description' => esc_html_x( 'Enter the prefix for the email (e.g., john+ to create a random alias).', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'text',
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'RAND_EMAIL_NUMS',
							'required'    => false,
							'label'       => esc_html_x( 'Include numbers', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'input_type'  => 'checkbox',
							'is_toggle'   => true,
						)
					),
				),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
	}

	/**
	 * Process the action to generate a random email.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param array $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$domain          = sanitize_text_field( $parsed['RAND_EMAIL_DOMAIN'] );
		$include_numbers = sanitize_text_field( $parsed['RAND_EMAIL_NUMS'] ) === 'true';
		$prefix          = sanitize_text_field( $parsed['RAND_EMAIL_PREFIX'] );

		if ( empty( $domain ) ) {
			$domain = apply_filters( 'automator_pro_generate_random_email_domain', 'example.com', $user_id, $action_data, $recipe_id, $args, $parsed, $this );
		}

		$random_email = $this->generate_random_email( $domain, $include_numbers, $prefix );

		$this->hydrate_tokens(
			array(
				'RANDOM_EMAIL' => $random_email,
			)
		);

		// Set log properties.
		$this->set_log_properties(
			array(
				'type'  => 'email',
				'label' => __( 'Generated email', 'uncanny-automator-pro' ),
				'value' => $random_email,
			)
		);
		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * Generate a random email address.
	 *
	 * @param string $domain
	 * @param bool $include_numbers
	 *
	 * @return string
	 */
	public function generate_random_email( $domain, $include_numbers = false, $prefix = '' ) {

		$numeric_part  = $include_numbers ? wp_rand( 1000, 9999 ) : '';
		$allowed_chars = apply_filters( 'automator_pro_generate_random_email_lower_alphabets', 'abcdefghijklmnopqrstuvwxyz' );
		$ran_string    = substr( str_shuffle( str_repeat( $allowed_chars, ceil( 6 / strlen( $allowed_chars ) ) ) ), 1, 6 );

		return "{$prefix}{$ran_string}{$numeric_part}@{$domain}";
	}
}
