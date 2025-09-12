<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_TYPEFORM_WEBHOOKS
 */
class ANON_TYPEFORM_WEBHOOKS {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'TYPEFORM';

	/**
	 * Trigger code
	 *
	 * @var string
	 */
	private $trigger_code;

	/**
	 * Trigger meta
	 *
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code = 'ANON_TYPEFORM_WEBHOOKS';
		$this->trigger_meta = 'WEBHOOKID';//'TYPEFORM_WEBHOOK';
		Webhook_Rest_Handler::set_trigger_codes( $this->trigger_code );
		$this->define_trigger();

		add_filter(
			'automator_pro_webhook_params_pre_handle_params',
			array(
				$this,
				'handle_params',
			),
			10,
			3
		);

		add_filter(
			'automator_pro_webhook_rest_route_permission_callback',
			array(
				$this,
				'maybe_handle_typeform_signature',
			),
			10,
			3
		);
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/typeform/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Anonymous trigger - Typeform */
			'sentence'            => sprintf( __( 'Receive data from Typeform {{webhook:%1$s}}', 'uncanny-automator-pro' ), 'WEBHOOK_DATA' ),
			/* translators: Anonymous trigger - Typeform */
			'select_option_name'  => __( 'Receive data from Typeform {{webhook}}', 'uncanny-automator-pro' ),
			'action'              => 'automator_pro_run_webhook',
			'priority'            => 10,
			'accepted_args'       => 3,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'run_typeform_webhook' ),
			'options_group'       => Webhook_Common_Options::get_webhook_options_group( array( 'format' => false ) ),
			'buttons'             => Webhook_Common_Options::get_webhook_get_sample_button(),
			'inline_css'          => Webhook_Static_Content::inline_css(),
			'can_log_in_new_user' => false,
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * Parse the params for the trigger to give them named keys.
	 *
	 * @param array $params
	 * @param array $trigger_meta
	 * @param \WP_REST_Request $data
	 *
	 * @return array
	 */
	public function handle_params( $params, $trigger_meta, $rest_request ) {
		// Bail if not the right trigger.
		if ( $this->trigger_code !== $trigger_meta['code'] ) {
			return $params;
		}

		// Bail if not the right event type REVIEW - Only form_response is supported for now.
		if ( 'form_response' !== $params['event_type'] ) {
			return $params;
		}

		// Parse Definitions ( Questions )
		foreach ( $params['form_response']['definition']['fields'] as $field ) {
			// Add ID and Title.
			$params['form_response']['definition']['fields'][ $field['id'] ] = $field['title'];
			if ( ! empty( $field['ref'] ) ) {
				// Add ref and Title if ref was set.
				$params['form_response']['definition']['fields'][ $field['ref'] ] = $field['title'];
			}
		}

		// Parse Answers
		foreach ( $params['form_response']['answers'] as $answer ) {
			$value = $answer[ $answer['type'] ];
			if ( is_array( $value ) ) {
				$k     = isset( $value['label'] ) ? 'label' : 'labels';
				$value = $value[ $k ];
			}
			// Add ID and Value.
			$params['form_response']['answers'][ $answer['field']['id'] ] = $value;
			// Maybe Add ref and Value if ref was set.
			if ( ! empty( $answer['field']['ref'] ) ) {
				$params['form_response']['answers'][ $answer['field']['ref'] ] = $value;
			}
		}

		return $params;
	}

	/**
	 * Maybe handle Typeform signature if set.
	 *
	 * @param bool $valid
	 * @param array $attributes
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function maybe_handle_typeform_signature( $valid, $attributes, $request ) {

		$request_headers = $request->get_headers();
		$request_body    = $request->get_body();
		$custom_headers  = ! empty( $attributes['custom_headers'] ) ? $attributes['custom_headers'] : array();
		$type_form_key   = 'typeform_signature';

		// Key not set for validation.
		if ( ! isset( $custom_headers[ $type_form_key ] ) || ! isset( $request_headers[ $type_form_key ] ) ) {
			return $valid;
		}

		// Get the secret.
		$secret = $custom_headers[ $type_form_key ];
		// Get the signature.
		$signature = $request_headers[ $type_form_key ][0];
		// Generate base64 encoded hash for comparison.
		$hashed_payload = hash_hmac( 'sha256', $request_body, $secret, true );
		$base64encoded  = 'sha256=' . base64_encode( $hashed_payload );

		// Compare the signatures.
		return $base64encoded === $signature;
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $param
	 * @param $recipe
	 */
	public function run_typeform_webhook( $param, $recipe, $request ) {
		Webhook_Common_Options::run_webhook( $this->trigger_code, $this->trigger_meta, $param, $recipe, $request );
	}
}
