<?php
namespace Uncanny_Automator_Pro;

/**
 * Class ANON_KONNECTZ_IT_WEBHOOKS
 */
class ANON_KONNECTZ_IT_WEBHOOKS {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'KONNECTZ_IT';

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

		$this->trigger_code = 'ANON_KONNECTZ_IT_WEBHOOKS';
		$this->trigger_meta = 'ANON_KONNECTZ_IT_WEBHOOKS_META';

		// Add trigger_code to Webhook_Rest_Handler
		Webhook_Rest_Handler::set_trigger_codes( $this->trigger_code );

		$this->define_trigger();

	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/konnectz-it/' ),
			'is_pro'              => true,
			'integration'         => self::$integration,
			'code'                => $this->trigger_code,
			/* translators: Anonymous trigger - KonnectzIT */
			'sentence'            => sprintf( __( 'Receive data from KonnectzIT {{webhook:%1$s}}', 'uncanny-automator-pro' ), 'WEBHOOK_DATA' ),
			/* translators: Anonymous trigger - KonnectzIT */
			'select_option_name'  => __( 'Receive data from KonnectzIT {{webhook}}', 'uncanny-automator-pro' ),
			'action'              => 'automator_pro_run_webhook',
			'priority'            => 10,
			'accepted_args'       => 3,
			'type'                => 'anonymous',
			'validation_function' => array( $this, 'run_konnectz_it_webhook' ),
			'options_group'       => Webhook_Common_Options::get_webhook_options_group(),
			'buttons'             => Webhook_Common_Options::get_webhook_get_sample_button(),
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
	 */
	public function run_konnectz_it_webhook( $param, $recipe, $request ) {

		Webhook_Common_Options::run_webhook( $this->trigger_code, $this->trigger_meta, $param, $recipe, $request );

	}

}
