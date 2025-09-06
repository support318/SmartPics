<?php
namespace Uncanny_Automator_Pro;

class Jetfb_Tokens_Specific {

	/**
	 * The `Field` and `Value` tokens.
	 *
	 * @trigger {{A form}} is submitted with {{a specific value}} in {{a specific field}}
	 * @trigger A user submits {{a form}} with {{a specific value}} in {{a specific field}}
	 *
	 * @return array
	 */
	public function field_tokens_specific() {
		return array(
			'FIELD' => array(
				'name' => __( 'Field', 'uncanny-automator-pro' ),
			),
			'VALUE' => array(
				'name' => __( 'Value', 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * Hydrate this specific tokens.
	 *
	 * @return array
	 */
	public function hydrate_tokens_specific( $parsed, $args, $trigger ) {

		return $parsed + array(
			// Returns the _readable format from option field.
			'FIELD' => $this->get_trigger_option_selected_value( $args['trigger_entry']['trigger_to_match'], 'FIELD_readable' ),
			'VALUE' => $this->get_trigger_option_selected_value( $args['trigger_entry']['trigger_to_match'], 'VALUE' ),
		);

	}

	/**
	 * Directly fetches the value from db.
	 *
	 * @return string The field value.
	 */
	private function get_trigger_option_selected_value( $trigger_id = 0, $meta_key = '' ) {

		if ( empty( $trigger_id ) || empty( $meta_key ) ) {
			return null;
		}

		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s LIMIT 1",
				$trigger_id,
				$meta_key
			)
		);

	}
}
