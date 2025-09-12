<?php
/**
 * Integrations: Give
 *
 * @package     AffiliateWP
 * @subpackage  Integrations
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

#[\AllowDynamicProperties]

/**
 * Implements an integration for Give.
 *
 * @since 1.2
 *
 * @see Affiliate_WP_Base
 */
class Affiliate_WP_Give extends Affiliate_WP_Base {

	/**
	 * The context for referrals. This refers to the integration that is being used.
	 *
	 * @var string
	 * @access  public
	 * @since   1.2
	 */
	public $context = 'give';

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function init() {

		$this->context = 'give';

		add_action( 'give_insert_payment', [ $this, 'add_pending_referral' ], 99999, 2 );

		add_action( 'give_complete_form_donation', [ $this, 'mark_referral_complete' ], 10, 3 );
		add_action( 'give_complete_form_donation', [ $this, 'insert_payment_note' ], 10, 3 );

		add_action( 'give_update_payment_status', [ $this, 'revoke_referral_on_refund' ], 10, 3 );
		add_action( 'give_payment_delete', [ $this, 'revoke_referral_on_delete' ], 10 );

		add_filter( 'affwp_referral_reference_column', [ $this, 'reference_link' ], 10, 2 );

		// Per donation form referral rates.
		add_filter( 'give_metabox_form_data_settings', [ $this, 'donation_settings' ], 99 );

		// GiveWP FormBuilder support (v3+).
		if ( $this->supports_form_builder() ) {
			// Hook to save settings when form is updated.
			add_action( 'givewp_form_builder_updated', [ $this, 'save_formbuilder_settings' ], 10, 2 );

			// Enqueue FormBuilder scripts.
			add_action( 'givewp_form_builder_enqueue_scripts', [ $this, 'enqueue_formbuilder_scripts' ] );

			// Add support for modern donation created hook.
			add_action( 'givewp_donation_created', [ $this, 'handle_donation_created_event' ], 10, 1 );
		}
	}

	/**
	 * Records a pending referral when a pending payment is created.
	 *
	 * @param int   $payment_id The payment ID.
	 * @param array $payment_data The payment data.
	 * @access  public
	 * @since   2.0
	 */
	public function add_pending_referral( $payment_id = 0, $payment_data = [] ) {

		// Check if referred.
		if ( ! $this->was_referred() ) {
			return false;
		}

		// Get Affiliate ID.
		$affiliate_id = $this->get_affiliate_id( $payment_id );

		// Get customer email.
		$customer_email = give_get_payment_user_email( $payment_id );

		// Get referral description.
		$desc = $this->get_referral_description( $payment_id );

		// Create draft referral.
		$referral_id = $this->insert_draft_referral(
			$this->affiliate_id,
			[
				'reference'   => $payment_id,
				'description' => $desc,
			]
		);
		if ( ! $referral_id ) {
			$this->log( 'Draft referral creation failed.' );
			return;
		}

		// Block referral if donation form does not allow it.
		// In v3+, form_id is passed as 'give_form_id', in v2 it might be 'form_id'.
		$form_id = isset( $payment_data['give_form_id'] ) ? $payment_data['give_form_id'] : ( isset( $payment_data['form_id'] ) ? $payment_data['form_id'] : 0 );

		// If form_id not found in payment data, try to get it from payment meta.
		if ( empty( $form_id ) ) {
			$form_id = get_post_meta( $payment_id, '_give_payment_form_id', true );
		}

		if ( ! $this->form_allows_referrals( $form_id ) ) {
			$this->log( 'Draft referral rejected because donation form does not allow it.' );
			$this->mark_referral_failed( $referral_id );
			return false;
		}

		// Customers cannot refer themselves.
		if ( $this->is_affiliate_email( $customer_email, $affiliate_id ) ) {
			$this->log( 'Referral not created because affiliate\'s own account was used.' );
			$this->mark_referral_failed( $referral_id );
			return false;
		}

		// Check if it has description.
		if ( empty( $desc ) ) {
			$this->log( 'Referral not created due to empty description.' );
			$this->mark_referral_failed( $referral_id );
			return;
		}

		// Get referral total.
		$referral_total = $this->get_referral_total( $payment_id, $affiliate_id );

		// Hydrates the previously created referral.
		$this->hydrate_referral(
			$referral_id,
			[
				'status' => 'pending',
				'amount' => $referral_total,
			]
		);
		$this->log( sprintf( 'Give referral #%d updated to pending successfully.', $referral_id ) );
	}

	/**
	 * Get the referral total.
	 *
	 * @param int $payment_id The payment ID.
	 * @param int $affiliate_id The affiliate ID.
	 * @access  public
	 * @since   2.0
	 */
	public function get_referral_total( $payment_id = 0, $affiliate_id = 0 ) {

		$form_id = get_post_meta( $payment_id, '_give_payment_form_id', true );

		$payment_amount = give_get_payment_total( $payment_id );
		$referral_total = $this->calculate_referral_amount( $payment_amount, $payment_id, $form_id, $affiliate_id );

		return $referral_total;
	}

	/**
	 * Get the referral description.
	 *
	 * @param int $payment_id The payment ID.
	 * @access  public
	 * @since   2.0
	 */
	public function get_referral_description( $payment_id = 0 ) {

		$payment_meta = give_get_payment_meta( $payment_id );

		$form_id  = isset( $payment_meta['form_id'] ) ? $payment_meta['form_id'] : 0;
		$price_id = isset( $payment_meta['price_id'] ) ? $payment_meta['price_id'] : null;

		// Get the actual form title instead of the hardcoded 'form_title' from payment meta.
		$referral_description = '';
		if ( $form_id ) {
			$referral_description = get_the_title( $form_id );
		}

		// Fallback to payment meta form title if we couldn't get the post title.
		if ( empty( $referral_description ) ) {
			$referral_description = isset( $payment_meta['form_title'] ) ? $payment_meta['form_title'] : '';
		}

		// For GiveWP FormBuilder forms, check the form settings for a custom title.
		if ( $this->supports_form_builder() && $this->is_formbuilder_form( $form_id ) ) {
			$form_title = give_get_meta( $form_id, 'formTitle', true, false, 'form' );
			if ( ! empty( $form_title ) ) {
				$referral_description = $form_title;
			}
		}

		$separator = ' - ';

		// If multi-level, append to the form title.
		if ( give_has_variable_prices( $form_id ) ) {

			if ( 'custom' === $price_id ) {

				$custom_amount_text = get_post_meta( $form_id, '_give_custom_amount_text', true );
				$price_option_text  = ! empty( $custom_amount_text ) ? $custom_amount_text : __( 'Custom Amount', 'affiliate-wp' );

			} else {

				// Get price option name, but strip any amount formatting.
				$price_option_name = give_get_price_option_name( $form_id, $price_id );

				$price_option_text = '';
				// Remove any currency symbols and amounts from the price option name.
				if ( ! empty( $price_option_name ) ) {
					// Remove common currency patterns: $10.00, £10.00, €10.00, ¥10, etc.
					$price_option_name = preg_replace( '/[\$£€¥₹₽¢]\s*[\d,]+\.?\d*/', '', $price_option_name );
					// Remove standalone numbers that might be amounts: 10.00, 25, etc.
					$price_option_name = preg_replace( '/\b\d+\.?\d*\b/', '', $price_option_name );
					// Clean up extra spaces.
					$price_option_text = trim( preg_replace( '/\s+/', ' ', $price_option_name ) );
				}
			}

			// Only add separator and price option if there's meaningful text.
			if ( ! empty( $referral_description ) && ! empty( $price_option_text ) ) {
				$referral_description .= $separator . $price_option_text;
			}
		}

		return $referral_description;
	}

	/**
	 * Sets a referral to unpaid when payment is completed
	 *
	 * @param int   $form_id The form ID.
	 * @param int   $payment_id The payment ID.
	 * @param array $payment_meta The payment meta.
	 * @access  public
	 * @since   2.0
	 */
	public function mark_referral_complete( $form_id, $payment_id, $payment_meta ) {
		$this->complete_referral( $payment_id );
	}

	/**
	 * Insert payment note
	 *
	 * @param int   $form_id The form ID.
	 * @param int   $payment_id The payment ID.
	 * @param array $payment_meta The payment meta.
	 * @access  public
	 * @since   2.0
	 */
	public function insert_payment_note( $form_id, $payment_id, $payment_meta ) {

		$referral = affwp_get_referral_by( 'reference', $payment_id, $this->context );

		if ( is_wp_error( $referral ) ) {
			affiliate_wp()->utils->log( 'insert_payment_note: The referral could not be found.', $referral );

			return;
		}

		$amount       = affwp_currency_filter( affwp_format_amount( $referral->amount ) );
		$affiliate_id = $referral->affiliate_id;
		$name         = affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );

		/* translators: 1: Referral ID, 2: Formatted referral amount, 3: Affiliate name */
		give_insert_payment_note( $payment_id, sprintf( __( 'Referral #%1$d for %2$s recorded for %3$s', 'affiliate-wp' ), $referral->referral_id, $amount, $name ) );
	}

	/**
	 * Revokes a referral when donation is refunded
	 *
	 * @param int    $payment_id The payment ID.
	 * @param string $new_status The new status.
	 * @param string $old_status The old status.
	 * @access  public
	 * @since   2.0
	 */
	public function revoke_referral_on_refund( $payment_id, $new_status, $old_status ) {

		if ( 'publish' !== $old_status && 'revoked' !== $old_status ) {
			return;
		}

		if ( 'refunded' !== $new_status ) {
			return;
		}

		if ( ! affiliate_wp()->settings->get( 'revoke_on_refund' ) ) {
			return;
		}

		$this->reject_referral( $payment_id );
	}

	/**
	 * Revokes a referral when a donation is deleted
	 *
	 * @param int $payment_id The payment ID.
	 * @access  public
	 * @since   2.0
	 */
	public function revoke_referral_on_delete( $payment_id = 0 ) {

		if ( ! affiliate_wp()->settings->get( 'revoke_on_refund' ) ) {
			return;
		}

		$this->reject_referral( $payment_id );
	}

	/**
	 * Sets up the reference link in the Referrals table
	 *
	 * @param string $reference The reference ID.
	 * @param object $referral  The referral object.
	 * @access  public
	 * @since   2.0
	 */
	public function reference_link( $reference, $referral ) {

		if ( empty( $referral->context ) || 'give' !== $referral->context ) {
			return $reference;
		}

		$url = admin_url( 'edit.php?post_type=give_forms&page=give-payment-history&view=view-payment-details&id=' . $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

	/**
	 * Adds Give settings, using the Give Settings API.
	 *
	 * @param  array $settings Give form settings.
	 * @return array $settings  Modified settings.
	 * @since  2.0
	 */
	public function donation_settings( $settings ) {
		$settings_fields = [
			[
				'name' => esc_html__( 'Allow Referrals', 'affiliate-wp' ),
				'desc' => esc_html__( 'Enable affiliate referral creation for this donation form', 'affiliate-wp' ),
				'id'   => '_affwp_give_allow_referrals',
				'type' => 'checkbox',
			],
			[
				'name'        => esc_html__( 'Affiliate Rate', 'affiliate-wp' ),
				'description' => esc_html__( 'This setting will be used to calculate affiliate earnings per-donation. Leave blank to use default affiliate rates.', 'affiliate-wp' ),
				'id'          => '_affwp_give_product_rate',
				'type'        => 'text_small',
			],
		];

		$settings['affiliatewp'] = [
			'id'     => 'affiliatewp',
			'title'  => __( 'AffiliateWP', 'affiliate-wp' ),
			'fields' => $settings_fields,
		];

		return $settings;
	}

	/**
	 * Retrieves the customer details for a donation.
	 *
	 * @since 2.2
	 *
	 * @param int $payment_id The ID of the payment to retrieve customer details for.
	 * @return array An array of the customer details
	 */
	public function get_customer( $payment_id = 0 ) {

		$customer = [];

		if ( class_exists( 'Give_Donor' ) ) {

			$donor      = new Give_Donor( give_get_payment_donor_id( $payment_id ) );
			$names      = explode( ' ', $donor->name );
			$first_name = $names[0];
			$last_name  = '';
			if ( ! empty( $names[1] ) ) {
				unset( $names[0] );
				$last_name = implode( ' ', $names );
			}

			$customer['user_id']    = $donor->user_id;
			$customer['email']      = $donor->email;
			$customer['first_name'] = $first_name;
			$customer['last_name']  = $last_name;
			$customer['ip']         = give_get_payment_user_ip( $payment_id );

		}

		return $customer;
	}


	/**
	 * Runs the check necessary to confirm this plugin is active.
	 *
	 * @since 2.5
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	public function plugin_is_active() {
		return class_exists( 'Give' );
	}

	/**
	 * Get the referral rate for a Give form.
	 *
	 * Overrides the base method to use give_get_meta which handles both storage locations.
	 *
	 * @since 2.28.2
	 *
	 * @param int   $product_id Form ID.
	 * @param array $args       Optional arguments.
	 * @return string The product rate.
	 */
	public function get_product_rate( $product_id = 0, $args = [] ) {
		// For FormBuilder forms (v3+), use 'form' meta type to check give_formmeta table.
		if ( $this->is_formbuilder_form( $product_id ) ) {
			$rate = give_get_meta( $product_id, '_affwp_give_product_rate', true, false, 'form' );
			if ( ! empty( $rate ) ) {
				return $rate;
			}
		}

		// Fall back to parent method which checks postmeta (for v2 forms).
		return parent::get_product_rate( $product_id, $args );
	}

	/**
	 * Handle modern donation created event
	 *
	 * @since 2.28.2
	 * @param object $donation The GiveWP Donation model (v3+).
	 */
	public function handle_donation_created_event( $donation ) {
		// Check if a referral already exists for this donation.
		$existing_referral = affwp_get_referral_by( 'reference', $donation->id, $this->context );
		if ( $existing_referral && ! is_wp_error( $existing_referral ) ) {
			return;
		}

		// Build payment data array in legacy format for compatibility.
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$payment_data = [
			'give_form_id'    => $donation->formId,
			'price'           => $donation->amount->formatToDecimal(),
			'give_form_title' => $donation->formTitle,
			'date'            => $donation->createdAt,
			'user_email'      => $donation->email,
			'purchase_key'    => $donation->purchaseKey,
			'currency'        => $donation->amount->getCurrency()->getCode(),
			'status'          => $donation->status->getValue(),
		];
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Call the legacy method to create the referral.
		$this->add_pending_referral( $donation->id, $payment_data );
	}

	/**
	 * Check if GiveWP supports FormBuilder (v3.0+)
	 *
	 * @since 2.28.2
	 * @return bool
	 */
	private function supports_form_builder() {
		if ( ! defined( 'GIVE_VERSION' ) ) {
			return false;
		}
		return version_compare( GIVE_VERSION, '3.0', '>=' );
	}

	/**
	 * Check if a form uses the FormBuilder (v3+)
	 *
	 * @since 2.28.2
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	private function is_formbuilder_form( $form_id ) {
		// FormBuilder forms have 'formBuilderSettings' meta in give_formmeta table.
		return (bool) give_get_meta( $form_id, 'formBuilderSettings', true, false, 'form' );
	}

	/**
	 * Check if a form allows referrals (supports both legacy and FormBuilder forms)
	 *
	 * @since 2.28.2
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	private function form_allows_referrals( $form_id ) {
		// For FormBuilder forms, check give_formmeta.
		if ( $this->is_formbuilder_form( $form_id ) ) {
			return (bool) give_get_meta( $form_id, '_affwp_give_allow_referrals', true, false, 'form' );
		}

		// For legacy forms, check post meta.
		return (bool) get_post_meta( $form_id, '_affwp_give_allow_referrals', true );
	}

	/**
	 * Save affiliate settings when v3+ FormBuilder form is updated
	 *
	 * @since 2.28.2
	 * @param object $form    The DonationForm object.
	 * @param object $request The REST request object.
	 */
	public function save_formbuilder_settings( $form, $request ) {
		// Get settings from the request.
		$settings = $request->get_param( 'settings' );

		// If settings is a JSON string, decode it.
		if ( is_string( $settings ) ) {
			$settings = json_decode( $settings, true );
		}

		// Save allow referrals setting.
		if ( isset( $settings['affiliateWPAllowReferrals'] ) ) {
			$value = $settings['affiliateWPAllowReferrals'];
			// Handle both boolean and string values.
			if ( true === $value || 'true' === $value || '1' === $value || 1 === $value ) {
				// Save to form meta for v3+ forms.
				give_update_meta( $form->id, '_affwp_give_allow_referrals', '1', '', 'form' );
			} else {
				// Delete from form meta for v3+ forms.
				give_delete_meta( $form->id, '_affwp_give_allow_referrals', '', 'form' );
			}
		}

		// Save affiliate rate setting.
		if ( isset( $settings['affiliateWPRate'] ) ) {
			$rate = sanitize_text_field( $settings['affiliateWPRate'] );
			if ( ! empty( $rate ) ) {
				// Save to form meta for v3+ forms.
				give_update_meta( $form->id, '_affwp_give_product_rate', $rate, '', 'form' );
			} else {
				// Delete from form meta for v3+ forms.
				give_delete_meta( $form->id, '_affwp_give_product_rate', '', 'form' );
			}
		}
	}

	/**
	 * Enqueue scripts for v3+ FormBuilder integration
	 *
	 * @since 2.28.2
	 */
	public function enqueue_formbuilder_scripts() {
		// Check if we're on the GiveWP v3+ FormBuilder page.
		if ( ! is_admin() ) {
			return;
		}

		// Check if we're on the FormBuilder page.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_formbuilder_page = (
			isset( $_GET['post_type'] ) &&
			'give_forms' === $_GET['post_type'] &&
			isset( $_GET['page'] ) &&
			'givewp-form-builder' === $_GET['page']
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $is_formbuilder_page ) {
			return;
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Enqueue the Give integration script.
		wp_enqueue_script(
			'affwp-give-integration',
			plugin_dir_url( AFFILIATEWP_PLUGIN_FILE ) . "assets/js/give{$min}.js",
			[ 'wp-hooks', 'wp-i18n', 'wp-element', 'wp-components', 'react', 'react-dom' ],
			AFFILIATEWP_VERSION,
			true
		);

		// Get current form settings.
		$form_id = 0;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['donationFormID'] ) ) {
			$form_id = absint( $_GET['donationFormID'] );
		} elseif ( isset( $_GET['post'] ) ) {
			$form_id = absint( $_GET['post'] );
		} elseif ( isset( $_GET['id'] ) ) {
			$form_id = absint( $_GET['id'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$allow_referrals = false;
		$affiliate_rate  = '';

		if ( $form_id ) {
			$allow_referrals = give_get_meta( $form_id, '_affwp_give_allow_referrals', true, false, 'form' );
			$affiliate_rate  = give_get_meta( $form_id, '_affwp_give_product_rate', true, false, 'form' );
		}

		wp_localize_script(
			'affwp-give-integration',
			'affwpGiveSettings',
			[
				'allowReferrals' => $allow_referrals ? true : false,
				'affiliateRate'  => $affiliate_rate ? $affiliate_rate : '',
				'nonce'          => wp_create_nonce( 'affwp_give_save_settings' ),
				'formId'         => $form_id,
				'strings'        => [
					'sectionTitle'        => __( 'AffiliateWP', 'affiliate-wp' ),
					'allowReferralsLabel' => __( 'Allow Referrals', 'affiliate-wp' ),
					'allowReferralsDesc'  => __( 'Enable affiliate referral creation for this donation form', 'affiliate-wp' ),
					'affiliateRateLabel'  => __( 'Affiliate Rate', 'affiliate-wp' ),
					'affiliateRateDesc'   => __( 'This setting will be used to calculate affiliate earnings per-donation. Leave blank to use default affiliate rates.', 'affiliate-wp' ),
				],
			]
		);
	}
}

new Affiliate_WP_Give();
