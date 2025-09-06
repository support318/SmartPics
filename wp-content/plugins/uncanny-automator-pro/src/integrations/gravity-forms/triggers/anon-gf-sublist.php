<?php

namespace Uncanny_Automator_Pro;

use GFAPI;

/**
 * Class ANON_GF_SUBLIST
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_GF_SUBLIST extends \Uncanny_Automator\Recipe\Trigger {

	public $helpers;
	/**
	 *
	 */
	public function setup_trigger() {

		$this->helpers = new Gravity_Forms_Pro_Helpers();

		$this->set_integration( 'GF' );
		$this->set_trigger_code( 'ANONSUBLIST' );
		$this->set_trigger_meta( 'ANONGFFORMS' );
		$this->set_support_link( Automator()->get_author_support_link( $this->trigger_code, 'integration/gravity-forms/' ) );
		$this->set_author( Automator()->get_author_name( $this->trigger_code ) );

		$this->set_is_pro( true );
		$this->set_sentence(
			sprintf(
			/* translators: list of forms */
				esc_attr__( '{{A list:%2$s}} row is submitted in {{a form:%1$s}}', 'uncanny-automator-pro' ),
				$this->trigger_meta,
				'FIELD:' . $this->trigger_meta
			)
		);

		$this->set_readable_sentence( esc_attr__( '{{A list}} row is submitted in {{a form}}', 'uncanny-automator-pro' ) );

		add_action( 'gform_after_submission', array( $this, 'inject_action' ), 10, 2 );

		$this->set_trigger_type( 'anonymous' );

		$this->set_is_login_required( false );

		$this->add_action( 'uap_gf_list_row_submitted', 10, 4 );

		add_action( 'wp_ajax_retrieve_list_fields_from_form_id', array( $this, 'get_list_fields_from_form_id' ) );
	}

	/**
	 * get_list_fields_from_form_id
	 *
	 * @return void
	 */
	public function get_list_fields_from_form_id() {

		Automator()->utilities->ajax_auth_check();

		if ( ! class_exists( '\GFAPI' ) ) {
			return array();
		}

		$form_id = absint( automator_filter_input( 'value', INPUT_POST ) );

		$form_selected = \GFAPI::get_form( $form_id );

		$fields = ! empty( $form_selected['fields'] ) ? $form_selected['fields'] : array();

		foreach ( $fields as $field ) {

			if ( 'list' !== $field->type ) {
				continue;
			}

			$options[] = array(
				'text'  => ! empty( $field['label'] ) ? esc_html( $field['label'] ) : 'Field: ' . absint( $field['id'] ),
				'value' => absint( $field['id'] ),
			);
		}

		wp_send_json( isset( $options ) ? $options : array() );

		die;
	}

	/**
	 * inject_action
	 *
	 * @param  mixed $entry
	 * @param  mixed $form
	 * @return void
	 */
	public function inject_action( $entry, $form ) {

		foreach ( $form['fields'] as $field ) {

			if ( 'list' !== $field->type ) {
				continue;
			}

			$this->process_list_rows( $field, $entry, $form );
		}
	}

	/**
	 * process_list_rows
	 *
	 * @param  mixed $field
	 * @param  mixed $entry
	 * @param  mixed $form
	 * @return void
	 */
	public function process_list_rows( $field, $entry, $form ) {

		$rows = unserialize( rgar( $entry, $field->id ) );

		if ( empty( $rows ) ) {
			return;
		}

		foreach ( $rows as $row_number => $values ) {

			$row = array(
				'row_number' => $row_number,
				'values'     => $values,
			);

			do_action( 'uap_gf_list_row_submitted', $entry, $form, $row, $field );
		}
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->trigger_meta => array(
						Automator()->helpers->recipe->gravity_forms->options->list_gravity_forms(
							null,
							$this->trigger_meta,
							array(
								'token'        => false,
								'is_ajax'      => true,
								'target_field' => 'FIELD',
								'endpoint'     => 'retrieve_list_fields_from_form_id',
							)
						),
						Automator()->helpers->recipe->field->select_field_args(
							array(
								'option_code'           => 'FIELD',
								'options'               => array(),
								'label'                 => esc_attr__( 'List field', 'uncanny-automator-pro' ),
								'required'              => true,
								'supports_tokens'       => false,
								'supports_custom_value' => false,
								'token_name'            => esc_attr__( 'List field', 'uncanny-automator-pro' ),
							)
						),
					),
				),
			)
		);
	}

	/**
	 * define_tokens
	 *
	 * @param  array $trigger
	 * @param  array $tokens
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {

		$form_id  = $trigger['meta'][ $this->trigger_meta ];
		$field_id = $trigger['meta']['FIELD'];

		$field = GFAPI::get_field( $form_id, $field_id );

		if ( ! $field ) {
			return $tokens;
		}

		$tokens[] = array(
			'tokenId'   => 'ROW_NUMBER',
			'tokenName' => esc_attr__( 'Row number', 'uncanny-automator-pro' ),
			'tokenType' => 'text',
		);

		if ( empty( $field->choices ) ) {

			$tokens[] = array(
				'tokenId'   => 'ROW_VALUE',
				'tokenName' => esc_attr__( 'Row value', 'uncanny-automator-pro' ),
				'tokenType' => 'text',
			);

			return $tokens;
		}

		foreach ( $field->choices as $key => $column ) {
			$tokens[] = array(
				'tokenId'   => $column['value'],
				'tokenName' => $column['text'],
				'tokenType' => 'text',
			);
		}

		return $tokens;
	}

	/**
	 * validate
	 *
	 * @param  array $trigger
	 * @param  array $hook_args
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {

		list( $entry, $form, $row, $field ) = $hook_args;

		$form_id = absint( $trigger['meta'][ $this->trigger_meta ] );

		if ( $form_id !== absint( $form['id'] ) ) {
			return false;
		}

		$field_id = absint( $trigger['meta']['FIELD'] );

		if ( $field_id !== absint( $field->id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Method hydrate_tokens.
	 *
	 * @param $parsed
	 * @param $args
	 * @param $trigger
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {

		$legacy_tokens = new \Uncanny_Automator\Integrations\Gravity_Forms\Gravity_Forms_Tokens();

		list( $entry, $form, $row ) = $hook_args;

		$output = $row['values'];

		if ( ! is_array( $output ) ) {
			$output = array(
				'ROW_VALUE' => $output,
			);
		}

		$output['ROW_NUMBER'] = $row['row_number'] + 1;

		$output['ANONGFFORMS_ID'] = $form['id'];

		$legacy_tokens->save_legacy_trigger_tokens( $this->trigger_records, $entry, $form );

		return $output;
	}

}
