<?php
namespace Uncanny_Automator_Pro\Integrations\GravityForms\Utilities;

/**
 * Handle Gravity Forms TIME fields.
 *
 * – Flattens the three time sub-inputs into a single value  
 * – Works with both “123.1” and “input_123.1” key styles  
 * – No `else` branches; every path exits early for clarity
 */
class Time_Handler {

	/**
	 * Convert all TIME fields in a submission to "HH:MM AM/PM".
	 *
	 * @param array $input_values Raw $_POST-style input array.
	 * @param int   $form_id      Gravity Form ID.
	 *
	 * @return array Sanitised input array.
	 */
	public function format_time_fields( array $input_values, int $form_id ): array {

		$form = \GFAPI::get_form( $form_id );

		foreach ( $form['fields'] as $field ) {
			if ( $field->type !== 'time' ) {
				continue;                             // only care about TIME fields
			}

			$input_values = $this->format_single_time_field( $input_values, $field->id );
		}

		return $input_values;
	}

	/**
	 * Flatten one TIME field’s three parts into a single value.
	 *
	 * @param array $input_values Working copy of inputs.
	 * @param int   $field_id     Field ID to process.
	 *
	 * @return array Updated inputs.
	 */
	private function format_single_time_field( array $input_values, int $field_id ): array {

		$prefix = $this->detect_prefix( $input_values, $field_id ); // '' or 'input_'
		if ( $prefix === null ) {
			return $input_values;                // nothing to do
		}

		// Build full keys once, keeps code DRY
		$h_key = "{$prefix}{$field_id}.1";
		$m_key = "{$prefix}{$field_id}.2";
		$a_key = "{$prefix}{$field_id}.3";

		// Get components with defaults for missing values
		$hours   = $input_values[ $h_key ] ?? '12';
		$minutes = $input_values[ $m_key ] ?? '00';
		$ampm    = $input_values[ $a_key ] ?? 'AM';

		$formatted = $this->combine_time_components( $hours, $minutes, $ampm );

		// Store final value and clean up parts
		$input_values[ $prefix . $field_id ] = $formatted;
		unset( $input_values[ $h_key ], $input_values[ $m_key ], $input_values[ $a_key ] );

		return $input_values;
	}

	/**
	 * Detect whether keys use the Gravity “input_” prefix.
	 *
	 * @param array $input_values Submission data.
	 * @param int   $field_id     Field being checked.
	 *
	 * @return string|null 'input_' if prefixed, '' if not, null if parts missing.
	 */
	private function detect_prefix( array $input_values, int $field_id ): ?string {

		// Check prefixed style first - only hour component is required
		$prefixed = "input_{$field_id}.1";
		if ( isset( $input_values[ $prefixed ] ) ) {
			return 'input_';
		}

		// Check plain style - only hour component is required
		$plain = "{$field_id}.1";
		if ( isset( $input_values[ $plain ] ) ) {
			return '';
		}

		return null; // hour component not found in either style
	}

	/**
	 * Turn (hours, minutes, AM/PM) into "HH:MM AM/PM".
	 */
	private function combine_time_components( string $hours, string $minutes, string $ampm ): string {

		$hours   = str_pad( $hours,   2, '0', STR_PAD_LEFT );
		$minutes = str_pad( $minutes, 2, '0', STR_PAD_LEFT );

		return "{$hours}:{$minutes} {$ampm}";
	}
}
