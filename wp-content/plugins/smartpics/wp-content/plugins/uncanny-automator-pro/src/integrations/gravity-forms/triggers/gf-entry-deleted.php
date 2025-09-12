<?php

namespace Uncanny_Automator_Pro;

use GFAPI;
use Uncanny_Automator\Recipe\Trigger;

/**
 * Class GF_ENTRY_DELETED
 *
 * @pacakge Uncanny_Automator_Pro
 */
class GF_ENTRY_DELETED extends Trigger {

	/**
	 * @return mixed
	 */
	protected function setup_trigger() {
		$this->set_integration( 'GF' );
		$this->set_trigger_code( 'GF_ENTRY_DELETED' );
		$this->set_trigger_meta( 'GF_FORM' );
		$this->set_is_pro( true );
		$this->set_helper( new Gravity_Forms_Pro_Helpers() );
		$this->set_trigger_type( 'anonymous' );
		$this->set_sentence( sprintf( esc_attr_x( 'An entry is deleted from {{a form:%1$s}}', 'Gravity Forms', 'uncanny-automator-pro' ), $this->get_trigger_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'An entry is deleted from {{a form}}', 'Gravity Forms', 'uncanny-automator-pro' ) );
		$this->add_action( 'gform_delete_entry', 10, 1 );
	}

	/**
	 * @return array
	 */
	public function options() {
		$options = array();
		$forms   = Automator()->helpers->recipe->gravity_forms->options->list_gravity_forms( null, '', array(), true );
		foreach ( $forms['options'] as $k => $form ) {
			$options[] = array(
				'value' => $k,
				'text'  => $form,
			);
		}

		return array(
			array(
				'input_type'  => 'select',
				'option_code' => $this->get_trigger_meta(),
				'label'       => __( 'Form', 'uncanny-automator-pro' ),
				'options'     => $options,
				'required'    => true,
			),
		);
	}

	/**
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		if ( ! isset( $trigger['meta'][ $this->get_trigger_meta() ] ) ) {
			return false;
		}

		$entry = GFAPI::get_entry( $hook_args[0] );
		if ( is_wp_error( $entry ) ) {
			return false;
		}

		// Form ID associated with the entry
		$form_id          = $entry['form_id'];
		$selected_form_id = $trigger['meta'][ $this->get_trigger_meta() ];

		return ( intval( '-1' ) === intval( $selected_form_id ) ) || ( absint( $selected_form_id ) === absint( $form_id ) );
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
		return array(
			array(
				'tokenId'   => 'ENTRY_ID',
				'tokenName' => __( 'Entry ID', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'ENTRY_DATE_SUBMITTED',
				'tokenName' => __( 'Entry submission date', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'ENTRY_DATE_UPDATED',
				'tokenName' => __( 'Entry date updated', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'ENTRY_URL_SOURCE',
				'tokenName' => __( 'Entry source URL', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'USER_IP',
				'tokenName' => __( 'User IP', 'uncanny-automator-pro' ),
			),
			array(
				'tokenId'   => 'USER_ID',
				'tokenName' => __( 'User ID', 'uncanny-automator-pro' ),
			),
		);
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
		$entry_id = $hook_args[0];
		$entry    = GFAPI::get_entry( $entry_id );

		return array(
			'ENTRY_DATE_SUBMITTED' => $entry['date_created'],
			'ENTRY_DATE_UPDATED'   => $entry['date_updated'],
			'ENTRY_URL_SOURCE'     => $entry['source_url'],
			'ENTRY_ID'             => $entry_id,
			'USER_IP'              => $entry['ip'],
			'USER_ID'              => $entry['created_by'],
			'GF_FORM'              => $entry['form_id'],
		);
	}
}
