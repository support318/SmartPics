<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;
use Uncanny_Automator\Ws_Form_Lite_Helpers;

/**
 * Class WSF_SPECIFIC_FIELD_VALUE
 *
 * @package Uncanny_Automator_Pro
 */
class WSF_SPECIFIC_FIELD_VALUE {

	use Recipe\Triggers;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'Uncanny_Automator\Ws_Form_Lite_Helpers' ) ) {
			return;
		}
		$this->set_helper( new Ws_Form_Lite_Helpers() );
		$this->set_tokens_class( new Ws_Form_Lite_Pro_Tokens() );
		$this->setup_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function setup_trigger() {
		$this->set_integration( 'WSFORMLITE' );
		$this->set_trigger_code( 'WSFORM_FROM_FIELDVALUE' );
		$this->set_trigger_meta( 'WSFORM_FORMS' );
		$this->set_is_login_required( true );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/ws-form/' ) );
		$this->set_sentence(
		/* Translators: Trigger sentence */
			sprintf(
				esc_html__( 'A user submits {{a form:%1$s}} with {{a specific value:%2$s}} in {{a specific field:%3$s}}', 'uncanny-automator-pro' ),
				$this->get_trigger_meta(),
				'SPECIFIC_VALUE' . ':' . $this->get_trigger_meta(),
				'SPECIFIC_FIELD' . ':' . $this->get_trigger_meta()
			)
		);
		// Non-active state sentence to show
		$this->set_readable_sentence( esc_attr__( 'A user submits {{a form}} with {{a specific value}} in {{a specific field}}', 'uncanny-automator-pro' ) );
		// Which do_action() fires this trigger.
		$this->set_action_hook( 'wsf_submit_post_complete' );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_trigger();

	}

	/**
	 * @return array
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_trigger_meta() => array(
						$this->get_helper()->get_ws_all_forms(
							null,
							$this->get_trigger_meta(),
							array(
								'is_ajax'      => true,
								'target_field' => 'SPECIFIC_FIELD',
								'endpoint'     => 'select_form_fields_WSFORMS',
							)
						),
						Automator()->helpers->recipe->field->select(
							array(
								'option_code' => 'SPECIFIC_FIELD',
								'label'       => esc_attr__( 'Field', 'uncanny-automator-pro' ),
							)
						),
						Automator()->helpers->recipe->field->text(
							array(
								'option_code' => 'SPECIFIC_VALUE',
								'label'       => esc_attr__( 'Value', 'uncanny-automator-pro' ),
							)
						),
					),
				),
			)
		);

	}

	/**
	 * Validate the trigger.
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	protected function validate_trigger( ...$args ) {
		list( $ws_form_submitted ) = array_shift( $args );

		if ( empty( $ws_form_submitted->form_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare to run the trigger.
	 *
	 * @param $data
	 *
	 * @return void
	 */
	public function prepare_to_run( $data ) {
		$this->set_conditional_trigger( true );
	}

	/**
	 * Check contact status against the trigger meta
	 *
	 * @param $args
	 */
	public function validate_conditions( ...$args ) {
		list( $ws_form_submitted ) = $args[0];
		$recipes                   = $this->trigger_recipes();

		// Bail early if no recipes - Triggering from submit form trigger.
		if ( empty( $recipes ) ) {
			return;
		}

		$field = array_values( (array) current( Automator()->get->meta_from_recipes( $recipes, 'SPECIFIC_FIELD' ) ) );
		// Bail early if no field is selected - Triggering from submit form trigger.
		if ( empty( $field ) || ! is_array( $field ) || empty( $field[0] ) ) {
			return false;
		}

		$value = $ws_form_submitted->meta[ $field[0] ]['value'];
		$value = is_array( $value ) ? implode( ', ', $value ) : $value;

		return $this->find_all( $this->trigger_recipes() )
					->where( array( $this->get_trigger_meta(), 'SPECIFIC_VALUE' ) )
					->match( array( $ws_form_submitted->form_id, $value ) )
					->format( array( 'intval', 'trim' ) )
					->get();
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

		return $this->get_tokens_class()->wsform_field_specific_tokens( $parsed, $args, $trigger );

	}

}
