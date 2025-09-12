<?php

namespace Uncanny_Automator_Pro\Integrations\Sure_Forms;

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
		$this->set_integration( 'SURE_FORMS' );
		$this->set_trigger_code( 'ANON_SUBMITTED_FORM_WITH_SPECIFIC_FIELD' );
		$this->set_trigger_meta( 'SURE_FORMS' );
		$this->set_trigger_type( 'anonymous' );
		$this->set_is_pro( true );
		$this->set_sentence(
			// translators: %1$s: Form, %2$s: Value, %3$s: Field
			sprintf( esc_attr_x( '{{A form:%1$s}} is submitted with {{a specific value:%2$s}} in {{a specific field:%3$s}}', 'Sure Forms', 'uncanny-automator-pro' ), $this->get_trigger_meta(), 'SURE_VALUE:' . $this->get_trigger_meta(), 'SURE_FIELD:' . $this->get_trigger_meta() )
		);
		$this->set_readable_sentence(
			// translators: Trigger sentence - Sure Forms
			esc_attr_x( '{{A form}} is submitted with {{a specific value}} in {{a specific field}}', 'Sure Forms', 'uncanny-automator-pro' )
		);
		$this->add_action( 'srfm_form_submit', 10, 1 );
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
				'label'           => esc_html_x( 'Form', 'Sure Forms', 'uncanny-automator-pro' ),
				'options'         => $this->helper->get_all_sure_forms( false ),
				'relevant_tokens' => array(),
			),
			array(
				'input_type'  => 'select',
				'option_code' => 'SURE_FIELD',
				'required'    => true,
				'label'       => esc_html_x( 'Field', 'Sure Forms', 'uncanny-automator-pro' ),
				'ajax'        => array(
					'endpoint'      => 'get_all_sure_fields_by_form_id',
					'event'         => 'parent_fields_change',
					'listen_fields' => array( $this->get_trigger_meta() ),
				),
			),
			array(
				'input_type'  => 'text',
				'option_code' => 'SURE_VALUE',
				'required'    => true,
				'label'       => esc_html_x( 'Value', 'Sure Forms', 'uncanny-automator-pro' ),
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
		return array_merge( $this->helper->get_sure_form_tokens(), $this->helper->get_sure_form_field_tokens( $trigger['meta'][ $this->get_trigger_meta() ] ) );
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

		// Check if required array keys exist
		if ( ! isset( $hook_args[0], $hook_args[0]['form_id'], $hook_args[0]['data'] ) ) {
			return false;
		}

		$selected_form       = $trigger['meta'][ $this->get_trigger_meta() ];
		$selected_form_field = $trigger['meta']['SURE_FIELD'];
		$selected_form_value = $trigger['meta']['SURE_VALUE'];

		$form_data = $hook_args[0]['data'];
		$form_id   = $hook_args[0]['form_id'];

		$form_match = ( absint( $selected_form ) === absint( $form_id ) || intval( '-1' ) === intval( $selected_form ) );

		if ( ! $form_match ) {
			return false;
		}

		if ( ! isset( $form_data[ $selected_form_field ] ) ) {
			return false;
		}

		$value_match = (string) $form_data[ $selected_form_field ] === (string) $selected_form_value;

		return $value_match;
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
		$data = $hook_args[0] ?? array();

		$form_id   = $data['form_id'] ?? null;
		$form_data = $data['data'] ?? array();

		if ( empty( $form_id ) || empty( $form_data ) ) {
			return array();
		}

		$all_fields    = $this->helper->get_all_form_fields( $form_id );
		$parsed_fields = array();

		foreach ( $all_fields as $field_id => $field ) {
			$slug = $field['slug'];
			if ( ! isset( $form_data[ $slug ] ) ) {
				continue;
			}

			$parsed_fields[ $field_id ] = array(
				'type'      => $field['type'],
				'value'     => $form_data[ $slug ],
				'value_raw' => $form_data[ $slug ],
			);
		}

		$token_values               = $this->helper->parse_token_values( $form_id, $parsed_fields );
		$token_values['SURE_FIELD'] = $completed_trigger['meta']['SURE_FIELD_readable'];
		$token_values['SURE_VALUE'] = $completed_trigger['meta']['SURE_VALUE'];

		return $token_values;
	}
}
