<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Recipe\Log_Properties;

/**
 * Class UOA_GENERATE_RANDOM_STRING
 *
 * @package Uncanny_Automator_Pro
 */
class UOA_GENERATE_RANDOM_STRING {

	use Recipe\Actions;
	use Recipe\Action_Tokens;
	use Log_Properties;

	/**
	 * UOA_GENERATE_RANDOM_STRING constructor.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 *
	 */
	protected function setup_action() {
		$this->set_integration( 'AUTOMATOR_GENERATOR' );
		$this->set_is_pro( true );
		$this->set_action_meta( 'GENERATE_RAND_STR' );
		$this->set_action_code( 'GENERATE_RAND_STR_META' );
		$this->set_requires_user( false );
		/* translators: Action - Automator Core */
		$this->set_sentence( sprintf( esc_html_x( 'Generate a {{random string:%1$s}}', 'Uncanny Automator', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		/* translators: Action - Automator Core */
		$this->set_readable_sentence( esc_attr_x( 'Generate a random {{string}}', 'Uncanny Automator', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'RANDOM_STRING' => array(
					'name' => _x( 'Generated string', 'Uncanny Automator', 'uncanny-automator-pro' ),
					'type' => 'text',
				),
			),
			$this->get_action_code()
		);
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		$options = array(
			'options_group' => array(
				$this->get_action_meta() => array(
					Automator()->helpers->recipe->field->int(
						array(
							'option_code' => 'RAND_STR_LENGTH',
							/* translators: Length field */
							'label'       => esc_attr_x( 'Number of characters', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'description' => esc_attr_x( 'Enter a number between 1 and 64', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'required'    => true,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'RAND_STR_LC',
							/* translators: allow lowercase field */
							'required'    => false,
							'label'       => esc_attr_x( 'Include lowercase letters', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'input_type'  => 'checkbox',
							'is_toggle'   => true,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'RAND_STR_UC',
							/* translators: allow uppercase field */
							'required'    => false,
							'label'       => esc_attr_x( 'Include uppercase letters', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'input_type'  => 'checkbox',
							'is_toggle'   => true,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'RAND_STR_NUMS',
							/* translators: allow numbers field */
							'required'    => false,
							'label'       => esc_attr_x( 'Include numbers', 'Uncanny Automator', 'uncanny-automator-pro' ),
							'input_type'  => 'checkbox',
							'is_toggle'   => true,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'RAND_STR_SP_CHAR',
							/* translators: allow special characters field */
							'required'    => false,
							'label'       => esc_attr_x( 'Include special characters', 'Uncanny Automator', 'uncanny-automator-pro' ),
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
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param       $parsed
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		$length = isset( $parsed['RAND_STR_LENGTH'] ) ? sanitize_text_field( $parsed['RAND_STR_LENGTH'] ) : 16;
		$length = absint( $length );

		if ( empty( $length ) ) {
			$action_data['complete_with_errors'] = true;
			$action_data['do-nothing']           = true;
			$error_message                       = _x( 'Please enter a number between 1 to 64, length is required.', 'Uncanny Automator', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		if ( $length < 1 || $length > 64 ) {
			$action_data['complete_with_errors'] = true;
			$action_data['do-nothing']           = true;
			$error_message                       = _x( 'An invalid number of characters was entered. Enter a number between 1 and 64.', 'Uncanny Automator', 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$lc      = sanitize_text_field( $parsed['RAND_STR_LC'] );
		$uc      = sanitize_text_field( $parsed['RAND_STR_UC'] );
		$sp_char = sanitize_text_field( $parsed['RAND_STR_SP_CHAR'] );
		$nums    = sanitize_text_field( $parsed['RAND_STR_NUMS'] );

		$rand_str = $this->generate_random_string( $length, $nums, $lc, $uc, $sp_char );
		$this->hydrate_tokens(
			array(
				'RANDOM_STRING' => $rand_str,
			)
		);

		// Set log properties.
		$this->set_log_properties(
			array(
				'type'  => 'text',
				'label' => __( 'Generated string', 'uncanny-automator-pro' ),
				'value' => $rand_str,
			)
		);

		Automator()->complete->action( $user_id, $action_data, $recipe_id );

	}

	/**
	 * @param $length
	 * @param $nums
	 * @param $lower_abc
	 * @param $upper_abc
	 * @param $special_chars
	 *
	 * @return string
	 */
	public function generate_random_string( $length = 16, $nums = 'false', $lower_abc = 'false', $upper_abc = 'false', $special_chars = 'false' ) {
		if (
			'false' === $nums &&
			'false' === $lower_abc &&
			'false' === $upper_abc &&
			'false' === $special_chars
		) {
			$nums          = 'true';
			$lower_abc     = 'true';
			$upper_abc     = 'true';
			$special_chars = 'true';
		}

		$allowed_chars = '';

		if ( 'false' !== $nums ) {
			$allowed_chars .= apply_filters( 'automator_pro_generate_random_string_numbers', '0123456789' );
		}

		if ( 'false' !== $lower_abc ) {
			$allowed_chars .= apply_filters( 'automator_pro_generate_random_string_lower_alphabets', 'abcdefghijklmnopqrstuvwxyz' );
		}

		if ( 'false' !== $upper_abc ) {
			$allowed_chars .= apply_filters( 'automator_pro_generate_random_string_upper_alphabets', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' );
		}

		if ( 'false' !== $special_chars ) {
			$allowed_chars .= apply_filters( 'automator_pro_generate_random_string_special_chars', '!#$%&()*+,-./:;<=>?@[\]^_`{|}~' );
		}

		$allowed_chars = apply_filters( 'automator_pro_generate_random_string_allowed_characters', $allowed_chars );

		return substr( str_shuffle( str_repeat( $allowed_chars, ceil( $length / strlen( $allowed_chars ) ) ) ), 1, $length );
	}
}
