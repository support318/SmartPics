<?php

namespace Uncanny_Automator_Pro\Integrations\Everest_Forms;

/**
 * Class ANON_FORM_SUBMITTED_WITH_SPECIFIC_FIELD_VALUE
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_FORM_SUBMITTED_WITH_SPECIFIC_FIELD_VALUE extends \Uncanny_Automator\Recipe\Trigger {

	protected $helper;

	/**
	 * @return mixed
	 */
	protected function setup_trigger() {
		$this->helper = array_shift( $this->dependencies );
		$this->set_integration( 'EVEREST_FORMS' );
		$this->set_trigger_code( 'ANON_SUBMITTED_FORM_WITH_SPECIFIC_FIELD' );
		$this->set_trigger_meta( 'EVF_FORMS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( '{{A form:%1$s}} is submitted with {{a specific value:%2$s}} in {{a specific field:%3$s}}', 'Everest Forms', 'uncanny-automator-pro' ), $this->get_trigger_meta(), 'EVF_VALUE:' . $this->get_trigger_meta(), 'EVF_FIELD:' . $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( '{{A form}} is submitted with {{a specific value}} in {{a specific field}}', 'Everest Forms', 'uncanny-automator-pro' ) );
		$this->add_action( 'everest_forms_process_complete', 10, 4 );
	}

	/**
	 * options
	 *
	 * Override this method to display a default option group
	 *
	 * @return array
	 */
	public function options() {
		return array(
			array(
				'option_code'     => $this->get_trigger_meta(),
				'input_type'      => 'select',
				'required'        => true,
				'label'           => _x( 'Form', 'Everest Forms', 'uncanny-automator-pro' ),
				'options'         => $this->helper->get_all_everest_forms( false ),
				'relevant_tokens' => array(),
			),
			array(
				'input_type'  => 'select',
				'option_code' => 'EVF_FIELD',
				'required'    => true,
				'label'       => _x( 'Field', 'Everest Forms', 'uncanny-automator-pro' ),
				'ajax'        => array(
					'endpoint'      => 'get_all_evf_fields_by_form_id',
					'event'         => 'parent_fields_change',
					'listen_fields' => array( $this->get_trigger_meta() ),
				),
			),
			array(
				'input_type'  => 'text',
				'option_code' => 'EVF_VALUE',
				'required'    => true,
				'label'       => _x( 'Value', 'Everest Forms', 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * define_tokens
	 *
	 * Override this method if you want to add recipe-specific tokens such as form fields etc.
	 *
	 * @param mixed $tokens
	 * @param mixed $args
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		return array_merge( $this->helper->get_evf_form_tokens(), $this->helper->get_evf_form_field_tokens( $trigger['meta'][ $this->get_trigger_meta() ] ) );
	}

	/**
	 * validate
	 *
	 * @param mixed $trigger
	 * @param mixed $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ], $hook_args ) ) {
			return false;
		}

		$selected_form       = $trigger['meta'][ $this->get_trigger_meta() ];
		$selected_form_field = $trigger['meta']['EVF_FIELD'];
		$selected_form_value = $trigger['meta']['EVF_VALUE'];

		return ( ( absint( $selected_form ) === absint( $hook_args[2]['id'] ) || intval( '-1' ) === intval( $selected_form ) ) && (string) $hook_args[0][ $selected_form_field ]['value'] === (string) $selected_form_value );
	}

	/**
	 * hydrate_tokens
	 *
	 * @param mixed $completed_trigger
	 * @param mixed $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $completed_trigger, $hook_args ) {
		$token_values              = $this->helper->parse_token_values( $hook_args[2]['id'], $hook_args[0] );
		$token_values['EVF_FIELD'] = $completed_trigger['meta']['EVF_FIELD_readable'];
		$token_values['EVF_VALUE'] = $completed_trigger['meta']['EVF_VALUE'];

		return $token_values;
	}

}
