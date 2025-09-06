<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 *
 */
class JETFB_EVERYONE_FORM_SPECIFIC_SUBMIT {

	use Recipe\Triggers;

	/**
	 * Trigger code.
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'JETFB_EVERYONE_FORM_SPECIFIC_SUBMIT';

	/**
	 * @var
	 */
	protected $helper;
	/**
	 * @var \Uncanny_Automator\Jetfb_Tokens
	 */
	protected $jetfb_tokens;
	/**
	 * @var Jetfb_Tokens_Specific
	 */
	protected $jetfb_tokens_specific;

	/**
	 * Trigger meta.
	 *
	 * @var string
	 */
	const TRIGGER_META = 'JETFB_EVERYONE_FORM_SPECIFIC_SUBMIT_META';

	/**
	 *
	 */
	public function __construct() {

		if ( class_exists( '\Uncanny_Automator\Jetfb_Tokens' ) && class_exists( '\Uncanny_Automator\Jetfb_Helpers' ) ) {

			$this->set_helper( new \Uncanny_Automator\Jetfb_Helpers() );

			$this->jetfb_tokens = new \Uncanny_Automator\Jetfb_Tokens();

			$this->jetfb_tokens_specific = new \Uncanny_Automator_Pro\Jetfb_Tokens_Specific();

			$this->setup_trigger();

		}

	}

	/**
	 * @param $helper
	 *
	 * @return void
	 */
	public function set_helper( $helper ) {

		$this->helper = $helper;

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {

		$this->set_integration( 'JET_FORM_BUILDER' );

		$this->set_trigger_code( self::TRIGGER_CODE );

		$this->set_trigger_meta( self::TRIGGER_META );

		$this->set_trigger_type( 'anonymous' );

		$this->set_is_pro( true );

		$this->set_is_login_required( false );

		/* Translators: Trigger sentence */
		$this->set_sentence(
			sprintf(
			/* Translators: Trigger sentence */
				esc_html__( '{{A form:%1$s}} is submitted with {{a specific value:%3$s}} in {{a specific field:%2$s}}', 'uncanny-automator-pro' ),
				$this->get_trigger_meta(),
				'FIELD:' . $this->get_trigger_meta(),
				'VALUE:' . $this->get_trigger_meta()
			)
		);

		$this->set_readable_sentence(
			esc_html__( '{{A form}} is submitted with {{a specific value}} in {{a specific field}}', 'uncanny-automator-pro' )
		);

		$this->add_action( 'jet-form-builder/form-handler/after-send' );

		if ( null !== $this->jetfb_tokens ) {

			$this->set_tokens(
				array_merge(
					$this->jetfb_tokens->common_tokens(),
					$this->jetfb_tokens_specific->field_tokens_specific()
				)
			);

		}

		$this->set_action_args_count( 2 );

		$this->set_options_callback( array( $this, 'load_options' ) );

		$this->register_trigger();

	}

	/**
	 * @return mixed
	 */
	public function load_options() {

		return $this->helper->get_option_field_group( $this );

	}

	/**
	 * @param ...$args
	 *
	 * @return mixed
	 */
	public function validate_trigger( ...$args ) {

		list( $form_handler, $is_success ) = $args[0];

		return $is_success;

	}

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {

		$this->set_conditional_trigger( true );

	}

	/**
	 * @param ...$args
	 *
	 * @return array
	 */
	public function validate_conditions( ...$args ) {

		list( $form_handler, $is_success ) = $args[0];

		if ( empty( $form_handler->action_handler->form_id || empty( $form_handler->action_handler->request_data ) ) ) {
			return array();
		}

		$recipes = $this->trigger_recipes();

		// Bail if recipes is empty.
		if ( empty( $recipes ) ) {
			return array();
		}

		$field = array_values( (array) current( Automator()->get->meta_from_recipes( $recipes, 'FIELD' ) ) );

		$request_data = $form_handler->action_handler->request_data;

		$value = $this->string_convert( $form_handler->action_handler->request_data, $field );

		if ( empty( $request_data[ end( $field ) ] ) ) {
			return array();
		}

		$matching_recipes_triggers = $this->find_all( $this->trigger_recipes() )
										  ->where( array( $this->get_trigger_meta(), 'VALUE' ) )
										  ->match( array( absint( $form_handler->action_handler->form_id ), $value ) )
										  ->format( array( 'intval', 'trim' ) )
										  ->get();

		return $matching_recipes_triggers;

	}

	/**
	 * Method parse_additional_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function parse_additional_tokens( $parsed, $args, $trigger ) {

		return array_merge(
			$this->jetfb_tokens->hydrate_tokens( $parsed, $args, $trigger ),
			$this->jetfb_tokens_specific->hydrate_tokens_specific( $parsed, $args, $trigger )
		);

	}

	/**
	 * @param ...$args
	 *
	 * @return true
	 */
	public function do_continue_anon_trigger( ...$args ) {

		return true;

	}

	/**
	 * Coverts all array values from field to string.
	 *
	 * @param array $data
	 * @param array $field
	 *
	 * @return string The value of the field.
	 */
	private function string_convert( $data = array(), $field = array() ) {

		if ( empty( $data[ end( $field ) ] ) ) {
			return array();
		}

		$requested_data_str_value = $data[ end( $field ) ];

		// Handles array data.
		if ( is_array( $requested_data_str_value ) ) {
			$requested_data_str_value = implode( ', ', $requested_data_str_value );
		}

		return $requested_data_str_value;

	}

}
