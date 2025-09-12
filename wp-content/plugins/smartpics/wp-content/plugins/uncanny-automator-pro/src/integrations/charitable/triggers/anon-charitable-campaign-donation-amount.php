<?php

namespace Uncanny_Automator_Pro\Integrations\Charitable;

/**
 * Class ANON_CHARITABLE_CAMPAIGN_DONATION_AMOUNT
 */
class ANON_CHARITABLE_CAMPAIGN_DONATION_AMOUNT extends \Uncanny_Automator\Recipe\Trigger {

	/**
	 * Charitable_Integration Instance.
	 *
	 * @var object
	 */
	private $charitable;

	/**
	 * Anonymous trigger that will fire even if no user is logged in.
	 *
	 * @return void
	 */
	protected function setup_trigger() {

		$this->charitable = array_shift( $this->dependencies );

		$this->set_trigger_type( 'anonymous' );
		$this->set_integration( 'CHARITABLE' );
		$this->set_trigger_code( 'ANON_MADE_CAMPAIGN_DONATION_AMOUNT' );
		$this->set_trigger_meta( 'CAMPAIGN_DONATION_AMOUNT' );
		$this->set_is_pro( true );

		// Active State Sentence.
		$this->set_sentence(
			sprintf(
				/* translators: Anonymous trigger : %1$s is Charitable Campaign Name : %2$s is Number Condition : %3$s is Donation Amount*/
				esc_attr_x(
					'A donation to {{a campaign:%1$s}} is made for an amount {{greater than, less than, or equal:%2$s}} to {{an amount:%3$s}}',
					'Charitable',
					'uncanny-automator-pro'
				),
				'CHARITABLE_CAMPAIGN',
				'NUMBERCOND',
				'DONATION_AMOUNT' //charitable_format_money()
			)
		);
		// Non Active State Sentence.
		$this->set_readable_sentence(
			esc_attr_x(
				'A donation is made via {{a campaign}} for an amount {{greater than, less than, or equal to}} {{an amount}}',
				'Charitable',
				'uncanny-automator-pro'
			)
		);
		$this->add_action( 'automator_charitable_donation_made', 10, 1 );

	}

	/**
	 * Load Options.
	 *
	 * @return array
	 */
	public function load_options() {
		return array(
			'options' => array(
				$this->charitable->helpers()->campaign_select(),
				$this->charitable->helpers()->donation_amount_conditions_select(),
				$this->charitable->helpers()->donation_amount_input(),
			),
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

		// Ensure sure the trigger has our selected options.
		$meta = ! empty( $trigger['meta'] ) ? $trigger['meta'] : array();
		if ( ! isset( $meta['CHARITABLE_CAMPAIGN'] ) || ! isset( $meta['NUMBERCOND'] ) || ! isset( $meta['DONATION_AMOUNT'] ) ) {
			return false;
		}

		// Validate the donation.
		$donation = $this->charitable->helpers()->validate_approved_donation( $hook_args[0] );

		// Bail early if not an approved donation.
		if ( ! $donation ) {
			return false;
		}

		// Validate the campaign.
		$campaign = $this->charitable->helpers()->get_donation_campaign( $donation );
		if ( ! $campaign ) {
			return false;
		}
		$campaign_id = (int) $campaign->get_campaign_id();

		// Get our Selected Values.
		$check_campaign_id = (int) $meta['CHARITABLE_CAMPAIGN'];
		$number_cond       = $meta['NUMBERCOND'];
		$donation_amount   = (int) $meta['DONATION_AMOUNT'];
		$amount_donated    = $donation->get_total_donation_amount();

		// Validate campaign ID passes our criteria.
		if ( $check_campaign_id !== $campaign_id && -1 !== $check_campaign_id ) {
			return false;
		}

		// Validate if the amount donated passes our criteria.
		if ( ! Automator()->utilities->match_condition_vs_number( $number_cond, $donation_amount, $amount_donated ) ) {
			return false;
		}

		return true;
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
		return array_merge( $tokens, $this->charitable->helpers()->get_donation_tokens_config() );
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
		// Hydrate Donation Tokens.
		$tokens = $this->charitable->helpers()->hydrate_donation_tokens( $hook_args[0] );
		// Add Condition Tokens Values.
		$tokens['NUMBERCOND']      = $trigger['meta']['NUMBERCOND'];
		$tokens['DONATION_AMOUNT'] = charitable_format_money( $trigger['meta']['DONATION_AMOUNT'] );

		return $tokens;
	}
}
