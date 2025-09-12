<?php

namespace Uncanny_Automator_Pro;

/**
 * Class ANON_GOOGLE_SHEETSWEBHOOKS
 */
class ANON_GOOGLE_SHEETSWEBHOOKS {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'GOOGLE_SHEETS';

	/**
	 * Trigger Code
	 *
	 * @var string
	 */
	private $trigger_code;

	/**
	 * Trigger Meta
	 *
	 * @var string
	 */
	private $trigger_meta;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		if ( ! class_exists( '\Uncanny_Automator_Pro\Webhook_Rest_Handler' ) ) {
			return;
		}

		$this->trigger_code = 'ANON_GOOGLE_SHEETSWEBHOOKS';
		$this->trigger_meta = 'WEBHOOKID';
		// Add trigger_code to Webhook_Rest_Handler
		Webhook_Rest_Handler::set_trigger_codes( $this->trigger_code );
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$options = array(
			'format'             => false,
			'headers'            => false,
			'fields_label'       => esc_html__( 'Sheet columns', 'uncanny-automator-pro' ),
			'url_description'    => sprintf( __( 'Copy and paste this Webhook URL into the "Automator Webhook URL" field of the %s settings.', 'uncanny-automator-pro' ), '<a href="https://workspace.google.com/marketplace/app/uncanny_automator_webhook_addon/591136700300" target="_blank">Google Sheets Web App</a>' ),
			'fields_description' => __( 'Manually specify the column names or click "Get row" to listen for sample data.', 'uncanny-automator-pro' ),
		);

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/google-sheets/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Anonymous trigger - GOOGLE_SHEETS */
			'sentence'            => sprintf( __( 'Receive data from Google Sheets {{Web App:%1$s}}', 'uncanny-automator-pro' ), 'WEBHOOK_DATA' ),
			/* translators: Anonymous trigger - GOOGLE_SHEETS */
			'select_option_name'  => __( 'Receive data from Google Sheets {{Web App}}', 'uncanny-automator-pro' ),
			'action'              => 'automator_pro_run_webhook',
			'priority'            => 10,
			'accepted_args'       => 3,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'run_google_sheets_webhook' ),
			'options_group'       => Webhook_Common_Options::get_webhook_options_group( $options ),
			'buttons'             => Webhook_Common_Options::get_webhook_get_sample_button( __( 'Get row', 'uncanny-automator-pro' ) ),
			'inline_css'          => Webhook_Static_Content::inline_css(),
			'can_log_in_new_user' => false,
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $param
	 * @param $recipe
	 * @param $request
	 */
	public function run_google_sheets_webhook( $param, $recipe, $request ) {
		Webhook_Common_Options::run_webhook( $this->trigger_code, $this->trigger_meta, $param, $recipe, $request );
	}
}
