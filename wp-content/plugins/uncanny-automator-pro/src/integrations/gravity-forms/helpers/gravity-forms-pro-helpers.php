<?php


namespace Uncanny_Automator_Pro;

use GFAPI;
use GFCommon;
use RGFormsModel;
use Uncanny_Automator\Gravity_Forms_Helpers;

/**
 * Class Gravity_Forms_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Gravity_Forms_Pro_Helpers extends Gravity_Forms_Helpers {
	/**
	 * Gravity_Forms_Pro_Helpers constructor.
	 */
	public function __construct( $load_actions = true ) {

		if ( $load_actions ) {

			// Include the trigger code in common tokens so it automatically rendered and parsed.
			// @see (base plugin) uncanny-automator-src/integrations/gravity-forms/tokens/gf-common-tokens.php
			add_filter(
				'automator_gf_common_tokens_form_tokens',
				function( $triggers ) {
					$triggers[] = 'ANON_GF_FORM_FIELD_MATCHABLE';
					return $triggers;
				},
				20,
				1
			);

			add_action( 'wp_ajax_select_form_fields_ANONGFFORMS', array( $this, 'select_form_fields_func' ) );

			add_action( 'wp_ajax_select_form_fields_GFFORMS', array( $this, 'select_form_fields_func' ) );

			add_action( 'wp_ajax_get_form_fields_GFFORMS', array( $this, 'get_fields_rows_gfforms' ) );

			add_action( 'wp_ajax_retrieve_fields_from_form_id', array( $this, 'get_fields_from_form_id' ) );

		}

	}

	/**
	 * @param Gravity_Forms_Pro_Helpers $pro
	 */
	public function setPro( Gravity_Forms_Pro_Helpers $pro ) {
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

			$form = RGFormsModel::get_form_meta( $form_id );

			if ( is_array( $form['fields'] ) ) {
				foreach ( $form['fields'] as $field ) {
					if ( isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
						foreach ( $field['inputs'] as $input ) {
							$fields[] = array(
								'value' => $input['id'],
								'text'  => GFCommon::get_label( $field, $input['id'] ),
							);
						}
					} elseif ( ! rgar( $field, 'displayOnly' ) ) {
						$fields[] = array(
							'value' => $field['id'],
							'text'  => GFCommon::get_label( $field ),
						);
					}
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}


	/**
	 * @return void
	 */
	public function get_fields_rows_gfforms() {

		// Nonce and post object validation
		Automator()->utilities->ajax_auth_check();

		$response = (object) array(
			'success' => false,
			'fields'  => array(),
		);

		$fields = array();

		if ( isset( $_POST ) ) {
			$form_id = absint( automator_filter_input( 'form_id', INPUT_POST ) );

			$form = RGFormsModel::get_form_meta( $form_id );

			if ( is_array( $form['fields'] ) ) {
				foreach ( $form['fields'] as $field ) {

					if ( isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
						foreach ( $field['inputs'] as $input ) {
							$fields[] = array(
								'key'  => $input['id'] . ' - ' . GFCommon::get_label( $field, $input['id'] ),
								'type' => 'text',
								'data' => $input['id'] . ' - ' . GFCommon::get_label( $field, $input['id'] ),
							);
						}
					} elseif ( ! rgar( $field, 'displayOnly' ) ) {
						$fields[] = array(
							'value' => $field['id'],
							'text'  => $field['id'] . ' - ' . GFCommon::get_label( $field ),
						);
					}
				}

				$response = (object) array(
					'success' => true,
					'fields'  => array( $fields ),
				);

				return wp_send_json_success( $response );
			}
		}

		$response = (object) array(
			'success' => false,
			'error'   => "Couldn't fetch fields",
		);

		return wp_send_json_success( $response );
	}

	/**
	 * Retrieves all forms as option fields.
	 *
	 * @return array The list of option fields from Gravity forms.
	 */
	public function get_forms_as_option_fields() {

		if ( ! class_exists( '\GFAPI' ) || ! is_admin() ) {
			return array();
		}

		$forms = GFAPI::get_forms();

		foreach ( $forms as $form ) {
			$options[ absint( $form['id'] ) ] = $form['title'];
		}

		return ! empty( $options ) ? $options : array();

	}

	/**
	 * Retrieves all forms as option fields in a new format.
	 *
	 * @return array The list of option fields from Gravity forms.
	 */
	public function get_forms_as_options( $add_any = true ) {

		if ( ! class_exists( '\GFAPI' ) ) {
			return array();
		}

		$options = array();

		if ( $add_any ) {
			$options[] = array(
				'value' => '-1',
				'text'  => __( 'Any form', 'uncanny-automator-pro' ),
			);
		}

		$forms = GFAPI::get_forms();

		foreach ( $forms as $form ) {
			$options[] = array(
				'value' => absint( $form['id'] ),
				'text'  => $form['title'],
			);
		}

		return $options;

	}

	/**
	 * Retrieves all form fields from specific form using form ID.
	 *
	 * Callback method to wp_ajax_retrieve_fields_from_form_id.
	 *
	 * @return void
	 */
	public function get_fields_from_form_id() {

		Automator()->utilities->ajax_auth_check();

		if ( ! class_exists( '\GFAPI' ) ) {
			return array();
		}

		$form_id = absint( automator_filter_input( 'value', INPUT_POST ) );

		$form_selected = GFAPI::get_form( $form_id );

		$fields = ! empty( $form_selected['fields'] ) ? $form_selected['fields'] : array();

		foreach ( $fields as $field ) {
			$options[] = array(
				'text'  => ! empty( $field['label'] ) ? esc_html( $field['label'] ) : 'Field: ' . absint( $field['id'] ),
				'value' => absint( $field['id'] ),
			);
		}

		wp_send_json( isset( $options ) ? $options : array() );

		die;

	}

	/**
	 * Anonymous JS function invoked as callback when clicking
	 * the custom button "Send test". The JS function requires
	 * the JS module "modal". Make sure it's included in
	 * the "modules" array
	 *
	 * @return string The JS code, with or without the <script> tags
	 */
	public static function get_fields_js() {
		// Start output
		ob_start();

		// It's optional to add the <script> tags
		// This must have only one anonymous function
		?>

		<script>

			// Do when the user clicks on send test
			function ($button, data, modules) {

				// Create a configuration object
				let config = {
					// In milliseconds, the time between each call
					timeBetweenCalls: 1 * 1000,
					// In milliseconds, the time we're going to check for fields
					checkingTime: 60 * 1000,
					// Links
					links: {
						noResultsSupport: 'https://automatorplugin.com/knowledge-base/gravity-forms/'
					},
					// i18n
					i18n: {
						checkingHooks: "<?php printf( esc_html__( "We're checking for fields. We'll keep trying for %s seconds.", 'uncanny-automator' ), '{{time}}' ); ?>",
						noResultsTrouble: "<?php esc_html_e( 'We had trouble finding fields.', 'uncanny-automator' ); ?>",
						noResultsSupport: "<?php esc_html_e( 'See more details or get help', 'uncanny-automator' ); ?>",
						fieldsModalTitle: "<?php esc_html_e( "Here is the data we've collected", 'uncanny-automator' ); ?>",
						fieldsModalWarning: "<?php /* translators: 1. Button */ printf( esc_html__( 'Clicking on \"%1$s\" will remove your current fields and will use the ones on the table above instead.', 'uncanny-automator' ), '{{confirmButton}}' ); ?>",
						fieldsTableValueType: "<?php esc_html_e( 'Value type', 'uncanny-automator' ); ?>",
						fieldsTableReceivedData: "<?php esc_html_e( 'Received data', 'uncanny-automator' ); ?>",
						fieldsModalButtonConfirm: "<?php /* translators: Non-personal infinitive verb */ esc_html_e( 'Use these fields', 'uncanny-automator' ); ?>",
						fieldsModalButtonCancel: "<?php /* translators: Non-personal infinitive verb */ esc_html_e( 'Do nothing', 'uncanny-automator' ); ?>",
					}
				}

				// Create the variable we're going to use to know if we have to keep doing calls
				let foundResults = false;

				// Get the date when this function started
				let startDate = new Date();
				// console.log( data );
				// Create array with the data we're going to send
				let dataToBeSent = {
					action: 'get_form_fields_GFFORMS',
					nonce: UncannyAutomator._site.rest.nonce,
					recipe_id: UncannyAutomator._recipe.recipe_id,
					form_id: data.values.GFFORMS,
				};

				// Add notice to the item
				// Create notice
				let $notice = jQuery('<div/>', {
					'class': 'item-options__notice item-options__notice--warning'
				});

				// Add notice message
				$notice.html(config.i18n.checkingHooks.replace('{{time}}', parseInt(config.checkingTime / 1000)));

				// Get the notices container
				let $noticesContainer = jQuery('.item[data-id="' + data.item.id + '"] .item-options__notices');

				// Add notice
				$noticesContainer.html($notice);

				// Create the function we're going to use recursively to
				// do check for the fields
				var getGfFields = function () {
					// Do AJAX call
					jQuery.ajax({
						method: 'POST',
						dataType: 'json',
						url: ajaxurl,
						data: dataToBeSent,

						// Set the checking time as the timeout
						timeout: config.checkingTime,

						success: function (response) {
							// Get new date
							let currentDate = new Date();
							// Define the default value of foundResults
							let foundResults = false;

							// Check if the response was successful
							if (response.success) {

								// Check if we got the rows from a sample
								if (response.data.fields.length > 0) {
									// Update foundResults
									foundResults = true;
								}
							}

							// Check if we have to do another call
							let shouldDoAnotherCall = false;

							// First, check if we don't have results
							if (!foundResults) {
								// Check if we still have time left
								if ((currentDate.getTime() - startDate.getTime()) <= config.checkingTime) {
									// Update result
									shouldDoAnotherCall = true;
								}
							}

							if (shouldDoAnotherCall) {
								// Wait and do another call
								setTimeout(function () {
									// Invoke this function again
									getGfFields();
								}, config.timeBetweenCalls);
							} else {

								// Add loading animation to the button
								$button.removeClass('uap-btn--loading uap-btn--disabled');
								// Iterate fields and create an array with the rows
								let rows = [];
								let keys = {}

								jQuery.each(response.data.fields, function (index, field) {
									// Iterate keys
									jQuery.each(field, function (index, row) {
										if (row.value !== 'undefined') {
											keys[row.value] = rows.push(row);
										}
									});
								});

								// Get the field with the fields (AJAX_DATA)
								let gfFields = data.item.options.GFFORMS.fields[1];

								gfFields.fieldRows = [];

								// Add new rows. Iterate rows from the sample
								jQuery.each(rows, function (index, row) {

									if (typeof row.key !== 'undefined') {
										// Add row
										gfFields.addRow({
											GF_COLUMN_NAME: row.key
										}, false);
									} else {
										// Add row
										gfFields.addRow({
											GF_COLUMN_NAME: row.text
										}, false);
									}

								});

								// Render again
								gfFields.reRender();

								// Check if it has results
								if (foundResults) {
									// Remove notice
									$notice.remove();
								} else {
									// Change the notice type
									$notice.removeClass('item-options__notice--warning').addClass('item-options__notice--error');

									// Create a new notice message
									let noticeMessage = config.i18n.noResultsTrouble;

									// Change the notice message
									$notice.html(noticeMessage + ' ');

									// Add help link
									let $noticeHelpLink = jQuery('<a/>', {
										target: '_blank',
										href: config.links.noResultsSupport
									}).text(config.i18n.noResultsSupport);
									$notice.append($noticeHelpLink);
								}
							}
						},

						statusCode: {
							403: function () {
								location.reload();
							}
						},

						fail: function (response) {
						}
					});
				}

				// Add loading animation to the button
				$button.addClass('uap-btn--loading uap-btn--disabled');

				// Try to get fields
				getGfFields();
			}

		</script>

		<?php

		// Get output
		$output = ob_get_clean();

		// Return output
		return $output;
	}

	/**
	 * Retrieves the entry url by entry id and form id.
	 *
	 * @todo Move this helper method to the helpers file if it grows.
	 *
	 * @param int $entry_id The entry ID.
	 * @param int $form_id The form ID.
	 *
	 * @return string The entry URL.
	 */
	public static function get_entry_url( $entry_id = 0, $form_id = 0 ) {

		return add_query_arg(
			array(
				'id'   => $form_id,
				'lid'  => $entry_id,
				'page' => 'gf_entries',
				'view' => 'entry',
			),
			admin_url( 'admin.php' )
		);

	}

	/**
	 * format_input_values
	 *
	 * @param  mixed $field_values
	 * @param  mixed $recipe_id
	 * @param  mixed $user_id
	 * @param  mixed $args
	 * @return void
	 */
	public static function format_input_values( $field_values, $recipe_id, $user_id, $args, $prefix = '' ) {

		$input_values = array();

		foreach ( $field_values as $field ) {

			if ( empty( $field->GF_COLUMN_NAME ) ) {
				continue;
			}

			$t_values                                    = explode( '-', $field->GF_COLUMN_NAME );
			$field_id                                    = reset( $t_values );
			$input_values[ $prefix . trim( $field_id ) ] = sanitize_text_field( Automator()->parse->text( $field->GF_COLUMN_VALUE, $recipe_id, $user_id, $args ) );
		}

		return $input_values;
	}

}
