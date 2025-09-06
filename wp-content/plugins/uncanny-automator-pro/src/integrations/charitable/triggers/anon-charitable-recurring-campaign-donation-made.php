<?php

namespace Uncanny_Automator_Pro\Integrations\Charitable;

/**
 * Class ANON_CHARITABLE_RECURRING_CAMPAIGN_DONATION_MADE
 *
 * @package Uncanny_Automator_Pro
 */
class ANON_CHARITABLE_RECURRING_CAMPAIGN_DONATION_MADE extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * Charitable_Integration Instance.
	 *
	 * @var object
	 */
	private $charitable;

	/**
	 * Trigger code
	 *
	 * @var string
	 */
	const TRIGGER_CODE = 'RECURRING_DONATION_MADE';

	/**
	 * Trigger meta
	 *
	 * @var string
	 */
	const TRIGGER_META = 'CHARITABLE_RECURRING_CAMPAIGN';

	/**
	 * Anonymous trigger that will fire even if no user is logged in.
	 *
	 * @return void
	 */
	protected function setup_trigger() {

		$this->charitable = array_shift( $this->dependencies );

		$this->set_trigger_type( 'anonymous' );
		$this->set_integration( 'CHARITABLE' );
		$this->set_trigger_code( self::TRIGGER_CODE );
		$this->set_trigger_meta( self::TRIGGER_META );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( self::TRIGGER_CODE, 'integration/charitable/' ) );
		$this->add_action( 'charitable_recurring_donation_status_charitable-active', 999, 2 );

		// Non Active State Sentence.
		$this->set_readable_sentence(
			esc_attr_x(
				'A recurring donation to {{a campaign}} is made',
				'Charitable',
				'uncanny-automator-pro'
			)
		);

		// Active State Sentence.
		$this->set_sentence(
			sprintf(
				/* Translators: Trigger sentence - Charitable */
				esc_attr_x(
					'A recurring donation to {{a campaign:%1$s}} is made',
					'Charitable',
					'uncanny-automator-pro'
				),
				self::TRIGGER_META
			)
		);
	}

	/**
	 * Load Options.
	 *
	 * @return array
	 */
	public function options() {
		return array(
			$this->charitable->helpers()->recurring_campaign_select( self::TRIGGER_META ),
		);
	}

	/**
	 * Validate Trigger.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return bool
	 */
	public function validate( $trigger, $hook_args ) {
		return $this->charitable->helpers()->validate_recurring_campaign_donation_trigger( $trigger, $hook_args, self::TRIGGER_META );
	}

	/**
	 * Define Tokens.
	 *
	 * @param array $tokens
	 * @param array $trigger - options selected in the current recipe/trigger
	 *
	 * @return array
	 */
	public function define_tokens( $trigger, $tokens ) {
		return array_merge( $tokens, $this->charitable->helpers()->get_recurring_donation_tokens_config() );
	}

	/**
	 * Hydrate Tokens.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 *
	 * @return array
	 */
	public function hydrate_tokens( $trigger, $hook_args ) {
		return $this->charitable->helpers()->hydrate_recurring_donation_tokens( $hook_args[0] );
	}

}
