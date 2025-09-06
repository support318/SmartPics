<?php
namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 * Class OPEN_AI_TEXT_GENERATE_PRO
 *
 * @package Uncanny_Automator_Pro
 * @since 4.10
 */
class OPEN_AI_TEXT_GENERATE_PRO {

	use Recipe\Actions;

	use Recipe\Action_Tokens;

	public function __construct() {

		$this->setup_action();

		$this->set_helpers( new Open_AI_Pro_Helpers( false ) );

	}

	/**
	 * Setup Action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'OPEN_AI' );

		$this->set_action_code( 'OPEN_AI_TEXT_GENERATE_PRO' );

		$this->set_action_meta( 'OPEN_AI_TEXT_GENERATE_PRO_META' );

		$this->set_is_pro( true );

		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/open-ai/' ) );

		$this->set_requires_user( false );

		$this->set_sentence(
			sprintf(
				/* translators: Action sentence */
				esc_attr__( 'Use {{a prompt:%1$s}} to generate text with the Davinci model', 'uncanny-automator' ),
				$this->get_action_meta()
			)
		);

		/* translators: Action sentence */
		$this->set_readable_sentence( esc_attr__( 'Use {{a prompt}} to generate text with the Davinci model', 'uncanny-automator' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->set_wpautop( false );

		$this->set_background_processing( true );

		$this->set_action_tokens(
			array(
				'RESPONSE' => array(
					'name' => __( 'Response', 'uncanny-automator' ),
					'type' => 'text',
				),
			),
			$this->get_action_code()
		);

		$this->set_is_deprecated( true );

		$this->register_action();

	}

	/**
	 * Loads options.
	 *
	 * @return array The list of option fields.
	 */
	public function load_options() {

		$description = wp_kses_post(
			sprintf(
				/* translators: Action field description */
				__(
					'The maximum number of tokens. Tokens are shared between the prompt and the response. %1$sLearn more about tokens%2$s.',
					'uncanny-automator'
				),
				'<a href="https://help.openai.com/en/articles/4936856-what-are-tokens-and-how-to-count-them" target="_blank">',
				'</a>'
			)
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						array(
							'option_code' => 'TEMPERATURE',
							/* translators: Action field */
							'label'       => esc_attr__( 'Temperature', 'uncanny-automator' ),
							'input_type'  => 'text',
							'placeholder' => '0.7',
							'description' => esc_html__( 'Higher values mean the model will take more risks. Try 0.9 for more creative applications and a value closer to 0 for a well-defined answer.', 'uncanny-automator' ),
						),
						array(
							'option_code' => 'MAX_LEN',
							/* translators: Action field */
							'label'       => esc_attr__( 'Maximum length', 'uncanny-automator' ),
							'description' => $description,
							'input_type'  => 'text',
							'placeholder' => '256',
						),
						array(
							'option_code' => $this->get_action_meta(),
							/* translators: Action field */
							'label'       => esc_attr__( 'Prompt', 'uncanny-automator' ),
							'input_type'  => 'textarea',
							'required'    => true,
						),
					),
				),
			)
		);

	}


	/**
	 * Processes action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$temperature = isset( $parsed['TEMPERATURE'] ) ? sanitize_text_field( $parsed['TEMPERATURE'] ) : 0.7;
		$max_tokens  = isset( $parsed['MAX_LEN'] ) ? sanitize_text_field( $parsed['MAX_LEN'] ) : 256;
		$prompt      = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( $parsed[ $this->get_action_meta() ] ) : '';

		try {

			// Use the filter `automator_openai_send_request_payload` to overwrite this defaults.
			$args['body'] = array(
				'model'             => 'davinci-002',
				'prompt'            => $prompt,
				'temperature'       => 0.0 === floatval( $temperature ) ? 0.7 : floatval( $temperature ),
				'max_tokens'        => 0 === absint( $max_tokens ) ? 256 : absint( $max_tokens ),
				'top_p'             => 1,
				'frequency_penalty' => 0,
				'presence_penalty'  => 0,
			);

			$response = $this->get_helpers()
				->send_request( $args )
				->check_response()
				->get_response_body();

			$this->hydrate_tokens(
				array(
					'RESPONSE' => isset( $response['choices'][0]['text'] ) ? $response['choices'][0]['text'] : '',
				),
				$this->get_action_code()
			);

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

		} catch ( \Exception $e ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getMessage() );

		}

	}

}
