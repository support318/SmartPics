<?php

namespace Uncanny_Automator_Pro\Integrations\Kadence;

use Uncanny_Automator\Recipe\Trigger;

/**
 * Class KADENCE_USER_SUBMITTED_FORM_WITH_SPECIFIC_VALUE
 *
 * @pacakge Uncanny_Automator
 */
class KADENCE_USER_SUBMITTED_FORM_WITH_SPECIFIC_VALUE extends Trigger {

	protected $helpers;

	/**
	 * @return mixed|void
	 */
	protected function setup_trigger() {
		$this->helpers = array_shift( $this->dependencies );
		$this->set_integration( 'KADENCE' );
		$this->set_trigger_code( 'KADENCE_FORM_SUBMITTED_WITH_SPECIFIC_VALUE' );
		$this->set_trigger_meta( 'KADENCE_FORMS' );
		$this->set_is_pro( true );
		$this->set_sentence( sprintf( esc_attr_x( 'A user submits {{a form:%1$s}} with {{a specific value:%3$s}} in {{a specific field:%2$s}}', 'Kadence', 'uncanny-automator-pro' ), $this->get_trigger_meta(), 'SPECIFIC_FIELD:' . $this->get_trigger_meta(), 'SPECIFIC_VALUE:' . $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'A user submits {{a form}} with {{a specific value}} in {{a specific field}}', 'Kadence', 'uncanny-automator-pro' ) );
		$this->add_action( 'automator_kadence_form_submitted', 10, 3 );
	}

	/**
	 * @return array[]
	 */
	public function options() {
		return array(
			array(
				'input_type'     => 'select',
				'option_code'    => $this->get_trigger_meta(),
				'label'          => _x( 'Form', 'Kadence', 'uncanny-automator-pro' ),
				'required'       => true,
				'options'        => $this->helpers->get_all_kadence_form_options( true ),
				'is_ajax'        => true,
				'fill_values_in' => 'SPECIFIC_FIELD',
				'endpoint'       => 'get_all_form_fields',
			),
			array(
				'input_type'  => 'select',
				'option_code' => 'SPECIFIC_FIELD',
				'label'       => _x( 'Field', 'Kadence', 'uncanny-automator-pro' ),
				'required'    => true,
				'options'     => array(),
			),
			array(
				'input_type'  => 'text',
				'option_code' => 'SPECIFIC_VALUE',
				'label'       => _x( 'Value', 'Kadence', 'uncanny-automator-pro' ),
				'required'    => true,
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		list( $fields_data, $unique_id, $post_id ) = $hook_args;

		$form_id = ( is_null( $unique_id ) ) ? $post_id : $unique_id;

		if ( ! isset( $form_id ) ) {
			return false;
		}

		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$selected_form_id = $trigger['meta'][ $this->get_trigger_meta() ];
		$compare_value    = $this->get_field_and_value( $trigger['meta']['SPECIFIC_FIELD'], $trigger['meta']['SPECIFIC_VALUE'], $fields_data );

		return ( ( ( intval( '-1' ) === intval( $selected_form_id ) ) || ( $selected_form_id === $form_id ) ) && true === $compare_value );
	}

	/**
	 * define_tokens
	 *
	 * @param mixed $tokens
	 * @param mixed $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		$tokens[] = array(
			'tokenId'   => 'KADENCE_FORM_ID',
			'tokenName' => __( 'Form ID', 'uncanny_automator' ),
			'tokenType' => 'int',
		);
		$tokens[] = array(
			'tokenId'   => 'KADENCE_FORM_TITLE',
			'tokenName' => __( 'Form title', 'uncanny_automator' ),
			'tokenType' => 'text',
		);
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) || intval( '-1' ) === intval( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return $tokens;
		}
		$form_id = $trigger['meta'][ $this->get_trigger_meta() ];

		return $this->helpers->get_kadence_form_tokens( $form_id, $tokens );
	}

	/**
	 * hydrate_tokens
	 *
	 * @param $trigger
	 * @param $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		list( $fields_data, $unique_id, $post_id ) = $hook_args;
		$form_id = ( is_null( $unique_id ) ) ? $post_id : $unique_id;
		if ( is_null( $unique_id ) ) {
			$form_id   = $post_id;
			$form_name = get_post( $post_id )->post_title;
		}
		if ( ! is_null( $unique_id ) ) {
			$form_uid  = explode( '_', $unique_id );
			$form_id   = $unique_id;
			$form_name = get_post( $form_uid[0] )->post_title . ' - ' . $unique_id;
		}
		$trigger_token_values = array(
			'KADENCE_FORM_ID'    => $form_id,
			'KADENCE_FORM_TITLE' => $form_name,
			'KADENCE_FORMS'      => $form_name,
			'SPECIFIC_FIELD'     => $trigger['meta'][ 'SPECIFIC_FIELD' ],
			'SPECIFIC_VALUE'     => $trigger['meta'][ 'SPECIFIC_VALUE' ],
		);
		foreach ( $fields_data as $field_data ) {
			$trigger_token_values[ 'KADENCE_' . str_replace( ' ', '_', $field_data['label'] ) ] = $field_data['value'];
		}

		return $trigger_token_values;
	}

	/**
	 * @param $field
	 * @param $value
	 * @param $submitted_data
	 *
	 * @return bool
	 */
	private function get_field_and_value( $field, $value, $submitted_data ) {
		foreach ( $submitted_data as $item ) {
			if ( str_replace( ' ', '_', strtolower( $item['label'] ) ) === $field ) {
				if ( $item['value'] === $value ) {
					return true;
				}
			}
		}

		return false;
	}
}
