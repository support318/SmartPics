<?php

namespace Uncanny_Automator_Pro\Integrations\Sure_Forms;

class Sure_Forms_Helpers extends \Uncanny_Automator\Integrations\Sure_Forms\Sure_Forms_Helpers {

	/**
	 * Get all sure fields by form id.
	 *
	 * @return void
	 */
	public function get_all_sure_fields_by_form_id() {
		Automator()->utilities->verify_nonce();
		// Ignore nonce, already handled above.
		$values      = automator_filter_input_array( 'values', INPUT_POST );
		$form_id     = isset( $values['SURE_FORMS'] ) ? absint( $values['SURE_FORMS'] ) : 0;
		$options     = array();
		$form_fields = $this->get_all_form_fields( intval( $form_id ) );

		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $field_id => $field ) {
				$options[] = array(
					'value' => $field['slug'],
					'text'  => sprintf( '%s (%s)', $field['label'], $field['type'] ),
				);
			}
		}

		$response = array(
			'success' => true,
			'options' => $options,
		);

		wp_send_json( $response );
	}
}
