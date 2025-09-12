<?php
// phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
// phpcs:ignore PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro;

use Exception;

/**
 * Class MAILERLITE_SUBSCRIBER_UPSERT
 *
 * @package Uncanny_Automator
 */
class MAILERLITE_SUBSCRIBER_UPSERT {

	use \Uncanny_Automator\Recipe\Actions;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {

		$this->set_helpers( new Mailerlite_Helpers( false ) );

		$this->setup_action();
	}

	/**
	 * Set-up the action.
	 *
	 * @return void
	 */
	protected function setup_action() {

		$this->set_integration( 'MAILERLITE' );
		$this->set_action_code( 'MAILERLITE_SUBSCRIBER_UPSERT_CODE' );
		$this->set_action_meta( 'MAILERLITE_SUBSCRIBER_UPSERT_META' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/mailerlite/' ) );
		$this->set_requires_user( false );
		/* translators: Action - 1: Subscriber email */
		$this->set_sentence( sprintf( esc_attr_x( 'Create or update {{a subscriber:%1$s}}', 'Mailerlite', 'uncanny-automator-pro' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr_x( 'Create or update {{a subscriber}}', 'Mailerlite', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_background_processing( true );
		$this->register_action();
	}

	/**
	 * Load options.
	 *
	 * @return void
	 */
	public function load_options() {

		$email = array(
			'option_code' => $this->get_action_meta(),
			'label'       => esc_attr_x( 'Email', 'Mailerlite', 'uncanny-automator-pro' ),
			'input_type'  => 'email',
			'required'    => true,
		);

		$fields = array(
			'option_code'     => 'FIELDS',
			'label'           => esc_attr_x( 'Custom fields', 'Mailerlite', 'uncanny-automator-pro' ),
			'input_type'      => 'repeater',
			'relevant_tokens' => array(),
			'fields'          => array(
				array(
					'option_code' => 'FIELD_ID',
					'label'       => esc_html_x( 'Field name', 'MailerLite', 'uncanny-automator' ),
					'input_type'  => 'text',
					'read_only'   => true,
				),
				array(
					'option_code' => 'FIELD_NAME',
					'label'       => esc_html_x( 'Value', 'MailerLite', 'uncanny-automator' ),
					'input_type'  => 'text',
				),
			),
			'hide_actions'    => true,
			'ajax'            => array(
				'endpoint'       => 'automator_mailerlite_fetch_custom_fields',
				'event'          => 'on_load',
				'mapping_column' => 'FIELD_ID',
			),
		);

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						$email,
						$fields,
					),
				),
			)
		);
	}

	/**
	 * Run during action processing.
	 *
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param array $parsed
	 *
	 * @return void
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		$email  = $parsed[ $this->get_action_meta() ] ?? '';
		$fields = $parsed['FIELDS'] ?? '';

		$custom_fields  = (array) json_decode( $fields, true );
		$fields_payload = $this->construct_fields_body( $custom_fields );

		try {

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				throw new \Exception( 'Email is invalid.', 422 );
			}

			$json = wp_json_encode(
				array(
					'email'  => $email,
					'fields' => $fields_payload,
				)
			);

			$payload = array( 'body' => $json );

			$this->get_helpers()->http( 'POST', 'application/json' )->request( 'subscribers', $payload );

			Automator()->complete->action( $user_id, $action_data, $recipe_id );

		} catch ( \Exception $e ) {

			$action_data['complete_with_errors'] = true;

			Automator()->complete->action( $user_id, $action_data, $recipe_id, $e->getCode() . ':' . $e->getMessage() );

		}
	}

	/**
	 * Construct custom fields.
	 *
	 * @param array $custom_fields
	 *
	 * @return string[]
	 */
	private function construct_fields_body( $custom_fields = array() ) {

		$fields_body = array();

		foreach ( $custom_fields as $field ) {

			$is_field_name_valid = isset( $field['FIELD_NAME'] )
				&& is_scalar( $field['FIELD_NAME'] )
				&& strlen( (string) $field['FIELD_NAME'] ) > 0;

			if ( isset( $field['FIELD_ID'] ) && $is_field_name_valid ) {
				$fields_body[ $field['FIELD_ID'] ] = $field['FIELD_NAME'];
			}
		}

		return $fields_body;
	}
}
