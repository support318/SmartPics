<?php
namespace Uncanny_Automator_Pro;

/**
 *
 */
class AMELIABOOKING_PRO_CUSTOM_FIELDS_TOKENS {

	/**
	 *
	 */
	const CUSTOM_FIELD_RELATED_TRIGGERS = array(
		'AMELIA_USER_APPOINTMENT_BOOKED_SERVICE',
		'AMELIA_USER_APPOINTMENT_BOOKED_SERVICE_SPECIFIC_STATUS',
		'AMELIA_USER_APPOINTMENT_BOOKED_SERVICE_CANCELLED',
		'AMELIA_USER_APPOINTMENT_BOOKED_SERVICE_RESCHEDULED',
		// Everyone triggers
		'AMELIA_APPOINTMENT_BOOKED_SERVICE',
		'AMELIA_APPOINTMENT_BOOKED_SERVICE_SPECIFIC_STATUS',
		'AMELIA_APPOINTMENT_BOOKED_SERVICE_CANCELLED',
		'AMELIA_APPOINTMENT_BOOKED_SERVICE_RESCHEDULED',
	);

	/**
	 *
	 */
	const TOKEN_META = 'AMELIA_CUSTOM_FIELDS';

	/**
	 *
	 */
	public function __construct() {

		// Bailout if user is running amelia lite.
		if ( defined( 'AMELIA_LITE_VERSION' ) ) {
			if ( true === AMELIA_LITE_VERSION ) {
				return;
			}
		}

		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );

		foreach ( self::CUSTOM_FIELD_RELATED_TRIGGERS as $trigger ) {

			add_filter( 'automator_maybe_trigger_ameliabooking_' . strtolower( $trigger ) . '_tokens', array( $this, 'register_tokens' ), 40, 2 );

			add_filter( 'automator_parse_token_for_trigger_ameliabooking_' . strtolower( $trigger ), array( $this, 'parse_custom_fields_tokens' ), 20, 6 );

		}

	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|mixed
	 */
	public function register_tokens( $tokens = array(), $args = array() ) {

		$service_id = isset( $args['triggers_meta'][ $args['triggers_meta']['code'] . '_META' ] ) ? absint( $args['triggers_meta'][ $args['triggers_meta']['code'] . '_META' ] ) : 0;

		$custom_fields = $this->get_service_custom_fields( $service_id );

		if ( ! empty( $custom_fields ) ) {

			foreach ( $custom_fields as $field ) {
				$tokens[] = array(
					'tokenIdentifier' => $args['triggers_meta']['code'],
					'tokenId'         => $field['type'] . '|' . $field['customFieldId'],
					'tokenName'       => ! empty( $field['label'] ) ? esc_html( $field['label'] ) : '',
				);
			}
		}

		return $tokens;

	}

	/**
	 * @param $service_id
	 *
	 * @return array|object|\stdClass[]|null
	 */
	private function get_service_custom_fields( $service_id ) {

		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}amelia_custom_fields as fields
                    INNER JOIN {$wpdb->prefix}amelia_custom_fields_services as services
                    ON services.customFieldId = fields.id
                    WHERE services.serviceId = %d",
				$service_id
			),
			ARRAY_A
		);

	}

	/**
	 * @param $args
	 * @param $trigger
	 *
	 * @return void
	 */
	public function save_token_data( $args, $trigger ) {

		if ( ! isset( $args['trigger_args'] ) || ! isset( $args['entry_args']['code'] ) ) {
			return;
		}

		// Check if trigger code is for Amelia.
		if ( in_array( $args['entry_args']['code'], self::CUSTOM_FIELD_RELATED_TRIGGERS, true ) ) {

			$booking = array_shift( $args['trigger_args'] );

			$custom_fields = null;

			if ( ! empty( $booking ) ) {

				$custom_fields = isset( $booking['booking']['customFields'] ) ? $booking['booking']['customFields'] : null;

				// Other hooks stores the custom fields in bookings[0]['customFields].
				if ( empty( $custom_fields ) ) {
					$custom_fields = $booking['bookings'][0]['customFields'];
				}
			}

			if ( ! empty( $custom_fields ) ) {

				Automator()->db->token->save( self::TOKEN_META, $custom_fields, $args['trigger_entry'] );

			}
		}

	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string
	 */
	public function parse_custom_fields_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		$trigger_code = isset( $trigger_data[0]['meta']['code'] ) ? $trigger_data[0]['meta']['code'] : null;

		if ( empty( $trigger_code ) || ! in_array( $trigger_code, self::CUSTOM_FIELD_RELATED_TRIGGERS, true ) ) {

			return $value;

		}

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {

			return $value;

		}

		// The $pieces[2] is the token id.
		$parts = explode( '|', $pieces[2] );

		if ( 2 !== count( $parts ) ) {
			return $value;
		}

		list( $field_type, $field_id ) = $parts;

		// Get the meta from database record.
		$entry_custom_fields = json_decode( Automator()->db->token->get( self::TOKEN_META, $replace_args ), true );

		if ( ! empty( $entry_custom_fields[ $field_id ]['value'] ) ) {

			// Support attachment field type.
			if ( 'file' === $field_type ) {

				return implode( ', ', array_column( $entry_custom_fields[ $field_id ]['value'], 'fileName' ) );

			}

			// Support checkbox.
			if ( 'checkbox' === $field_type ) {

				return implode( ', ', $entry_custom_fields[ $field_id ]['value'] );

			}

			$value = $entry_custom_fields[ $field_id ]['value'];

			return esc_html( $value );

		}

		return $value;

	}

}
