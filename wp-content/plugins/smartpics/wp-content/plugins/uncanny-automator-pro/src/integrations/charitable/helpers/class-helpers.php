<?php

namespace Uncanny_Automator_Pro\Integrations\Charitable;

/**
 * Class CHARITABLE_HELPERS
 *
 * @package Uncanny_Automator
 */
class CHARITABLE_HELPERS_PRO extends \Uncanny_Automator\Integrations\Charitable\CHARITABLE_HELPERS {

	/**
	 * CHARITABLE_HELPERS constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get Recurring Campaign Select.
	 *
	 * @param string $code
	 *
	 * @return array
	 */
	public function recurring_campaign_select( $code ) {
		return array(
			'input_type'  => 'select',
			'option_code' => $code,
			'label'       => __( 'Campaign', 'uncanny-automator' ),
			'required'    => true,
			'options'     => $this->get_recurring_campaign_options(),
		);
	}

	/**
	 * Get Recurring Campaign Options for Select.
	 *
	 * @return array
	 */
	public function get_recurring_campaign_options() {

		// Return cached options.
		static $options = null;
		if ( null !== $options ) {
			return $options;
		}

		// Build options.
		$options   = array();
		$campaigns = $this->get_recurring_campaigns();
		if ( ! empty( $campaigns ) ) {
			$options[] = array(
				'text'  => _x( 'Any Recurring campaign', 'Charitable', 'uncanny-automator-pro' ),
				'value' => -1,
			);
			foreach ( $campaigns as $campaign ) {
				$options[] = array(
					'text'  => $campaign->post_title,
					'value' => $campaign->ID,
				);
			}
		}

		return $options;
	}

	/**
	 * Get Recurring Campaign Posts By Meta.
	 *
	 * @return array
	 */
	public function get_recurring_campaigns() {

		$campaigns = \Charitable_Campaigns::query(
			array(
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_campaign_end_date',
							'value'   => gmdate( 'Y-m-d H:i:s' ),
							'compare' => '>=',
							'type'    => 'datetime',
						),
						array(
							'key'     => '_campaign_end_date',
							'value'   => 0,
							'compare' => '=',
						),
					),
					array(
						'key'     => '_campaign_recurring_donation_mode',
						'value'   => array( 'simple', 'advanced' ),
						'compare' => 'IN',
					),
				),
			)
		);

		return ! empty( $campaigns->posts ) ? $campaigns->posts : array();
	}

	/**
	 * Get Charitable Recurring Donation Object.
	 *
	 * @param mixed $donation - Maybe Charitable_Recurring_Donation object or Recurring Donation Post ID.
	 *
	 * @return mixed - Charitable_Donation object or false.
	 */
	public function get_recurring_donation( $donation ) {

		// Already a Charitable_Recurring_Donation object.
		if ( is_a( $donation, 'Charitable_Recurring_Donation' ) ) {
			return $donation;
		}

		// Check By ID.
		if ( is_int( $donation ) ) {
			$donation_id = $donation;
			$donation    = charitable_get_donation( $donation_id );
			if ( ! $donation ) {
				return false;
			}
		}

		return is_a( $donation, 'Charitable_Recurring_Donation' ) ? $donation : false;
	}

	/**
	 * Get Charitable Campaign Object from Recurring Donation.
	 *
	 * @param mixed $donation - Maybe Charitable_Recurring_Donation object or Recurring Donation Post ID.
	 *
	 * @return mixed - Charitable_Campaign object or false.
	 */
	public function get_recurring_donation_campaign( $donation ) {

		// Validate donation.
		$donation = $this->get_recurring_donation( $donation );
		if ( ! $donation ) {
			return false;
		}

		// Get campaigns.
		$campaigns = $donation->get_campaign_donations();
		// Bail no campaigns.
		if ( empty( $campaigns ) ) {
			return false;
		}

		// TODO REVIEW - Handle Multiple Campaigns.
		$campaign_obj = reset( $campaigns );
		$campaign_id  = $campaign_obj->campaign_id;
		$campaign     = $this->get_campaign( $campaign_id );

		return $campaign ? $campaign : false;
	}

	/**
	 * Check if a campaign is recurring.
	 *
	 * @param int $campaign_id
	 *
	 * @return bool
	 */
	public function is_recurring_campaign( $campaign_id ) {
		$campaign_id = (int) $campaign_id;
		$campaigns   = $this->get_recurring_campaign_options();
		$campaigns   = wp_list_pluck( $campaigns, 'value' );

		return in_array( $campaign_id, $campaigns );
	}

	/**
	 * Check if a campaign is recurring and matches the given campaign ID.
	 *
	 * @param int $campaign_id
	 * @param int $check_campaign_id
	 *
	 * @return bool
	 */
	public function is_recurring_campaign_match( $campaign_id, $check_campaign_id ) {
		$campaign_id = (int) $campaign_id;
		if ( ! $this->is_recurring_campaign( $campaign_id ) ) {
			return false;
		}
		$check_campaign_id = (int) $check_campaign_id;
		if ( -1 === $check_campaign_id || $campaign_id === $check_campaign_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates Recurring Campaign Donation Trigger.
	 *
	 * @param array $trigger
	 * @param array $hook_args
	 * @param string $meta_key
	 *
	 * @return bool
	 */
	public function validate_recurring_campaign_donation_trigger( $trigger, $hook_args, $meta_key ) {

		// Ensure the trigger has our selected options.
		$meta = ! empty( $trigger['meta'] ) ? $trigger['meta'] : array();
		if ( ! isset( $meta[ $meta_key ] ) ) {
			return false;
		}

		// Validate the donation.
		$donation = $hook_args[0];

		// Bail early if not a recurring donation.
		if ( ! is_a( $donation, 'Charitable_Recurring_Donation' ) ) {
			return false;
		}

		// Validate the campaign.
		$campaign = $this->get_recurring_donation_campaign( $donation );
		if ( ! $campaign ) {
			return false;
		}
		$campaign_id = (int) $campaign->get_campaign_id();

		// Get our Selected Campaign ID.
		$check_campaign_id = (int) $meta[ $meta_key ];

		// Validate if campaign matches.
		return $this->is_recurring_campaign_match( $campaign_id, $check_campaign_id );
	}

	/**
	 * Get Recurring Donation Tokens Config.
	 *
	 * @return array
	 */
	public function get_recurring_donation_tokens_config() {
		//REVIEW - should we just merge in all default tokens from $this->get_donation_tokens_config()
		return array(
			// Campaign tokens.
			array(
				'tokenId'   => 'CAMPAIGN_TITLE',
				'tokenName' => __( 'Campaign title', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'CAMPAIGN_ID',
				'tokenName' => __( 'Campaign ID', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'CAMPAIGN_CREATOR_EMAIL',
				'tokenName' => __( 'Campaign creator email', 'uncanny-automator' ),
				'tokenType' => 'email',
			),
			// Donation tokens.
			array(
				'tokenId'   => 'DONATION_TITLE',
				'tokenName' => __( 'Donation title', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'DONATION_ID',
				'tokenName' => __( 'Donation ID', 'uncanny-automator' ),
				'tokenType' => 'int',
			),
			array(
				'tokenId'   => 'RECURRING_AMOUNT',
				'tokenName' => __( 'Recurring amount', 'uncanny-automator' ),
				'tokenType' => 'int', //round?
			),
			array(
				'tokenId'   => 'DONATION_DONOR_NAME',
				'tokenName' => __( 'Donor name', 'uncanny-automator' ),
				'tokenType' => 'text',
			),
			array(
				'tokenId'   => 'DONATION_DONOR_EMAIL',
				'tokenName' => __( 'Donor email', 'uncanny-automator' ),
				'tokenType' => 'email',
			),
		);
	}

	/**
	 * Populate Reccuring Donation Token Values.
	 *
	 * @param mixed $donation - Maybe Charitable_Recurring_Donation object or Recurring Donation Post ID.
	 *
	 * @return array
	 */
	public function hydrate_recurring_donation_tokens( $donation ) {

		// Generate array of empty default values.
		$defaults = wp_list_pluck( $this->get_donation_tokens_config(), 'tokenId' );
		$tokens   = array_fill_keys( $defaults, '' );

		// Get Recurring Charitable_Donation object.
		$donation = $this->get_recurring_donation( $donation );
		// Bail invalid donation.
		if ( ! $donation ) {
			return $tokens;
		}

		$donation_id = $donation->ID;

		// Donation Data.
		$tokens['DONATION_ID']      = $donation_id;
		$tokens['DONATION_TITLE']   = get_the_title( $donation_id );
		$tokens['RECURRING_AMOUNT'] = $donation->get_recurring_donation_amount( true );

		// Donor Data.
		$donor_data = $donation->get_donor_data();

		if ( ! empty( $donor_data ) ) {
			$tokens['DONATION_DONOR_NAME']  = $donor_data['first_name'] . ' ' . $donor_data['last_name'];
			$tokens['DONATION_DONOR_EMAIL'] = $donor_data['email'];
		}

		// Campaign Data.
		$campaign = $this->get_recurring_donation_campaign( $donation );
		// Bail invalid campaign.
		if ( ! $campaign ) {
			return $tokens;
		}

		$tokens['CAMPAIGN_ID']            = $campaign->get_campaign_id();
		$tokens['CAMPAIGN_TITLE']         = $campaign->post_title;
		$tokens['CAMPAIGN_CREATOR_EMAIL'] = $campaign->get_campaign_creator_email();

		return $tokens;
	}

	/**
	 * Get Donation Tokens.
	 *
	 * @param mixed $donation - Maybe Charitable_Recurring_Donation object or Recurring Donation Post ID.
	 *
	 * @return array
	 */
	function get_donation_options() {

		// Return cached options.
		static $options = null;
		if ( null !== $options ) {
			return $options;
		}

		// Build options.
		$options = array();

		// Get donations.
		global $wpdb;
		$sql       = "SELECT `ID`, `post_title` FROM {$wpdb->posts} WHERE `post_type` = 'donation' ORDER BY `post_date` DESC";
		$donations = $wpdb->get_results( $sql, OBJECT_K );

		if ( class_exists( 'Charitable_Recurring' ) ) {
			$sql                 = "SELECT `ID`, `post_title`, `post_parent` FROM {$wpdb->posts} WHERE `post_type` = 'recurring_donation' ORDER BY `post_date` DESC";
			$recurring_donations = $wpdb->get_results( $sql, OBJECT_K );
			if ( ! empty( $recurring_donations ) ) {
				foreach ( $recurring_donations as $recurring_donation ) {
					$donations[ $recurring_donation->ID ] = $recurring_donation;
				}
			}
		}

		if ( ! empty( $donations ) ) {
			foreach ( $donations as $donation ) {
				$options[] = array(
					'text'  => $donation->post_title,
					'value' => $donation->ID,
				);
			}
		}

		return $options;
	}
}
