<?php


namespace Uncanny_Automator_Pro;

use Forminator_API;
use Uncanny_Automator\Forminator_Helpers;

/**
 * Class Forminator_Pro_Helpers
 * @package Uncanny_Automator_Pro
 */
class Forminator_Pro_Helpers extends Forminator_Helpers {
	/**
	 * Forminator_Pro_Helpers constructor.
	 */
	public function __construct() {
		// Selectively load options
		if ( property_exists( '\Uncanny_Automator\Forminator_Helpers', 'load_options' ) ) {

			$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
		}

		add_action( 'wp_ajax_select_form_fields_FRFORMS', array( $this, 'select_form_fields_func' ) );
	}

	/**
	 * @param Forminator_Pro_Helpers $pro
	 */
	public function setPro( Forminator_Pro_Helpers $pro ) {
		parent::setPro( $pro );
	}

	/**
	 * Return all the specific fields of a form ID provided in ajax call
	 */
	public function select_form_fields_func() {

		Automator()->utilities->ajax_auth_check( $_POST );

		$fields = array();
		if ( isset( $_POST ) ) {
			$form_id = absint( automator_filter_input( 'value', INPUT_POST ) );

			$form = Forminator_API::get_form_fields( $form_id );
			if ( is_array( $form ) ) {
				foreach ( $form as $field ) {
					$input_id    = $field->slug;
					$input_title = $field->raw['field_label'];
					$fields[]    = array(
						'value' => $input_id,
						'text'  => $input_title,
					);
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

}
