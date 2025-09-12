<?php
/**
 * Integrations: Elementor
 *
 * This file contains the class and methods necessary for integrating AffiliateWP
 * with the Elementor page builder. It includes functions for form handling,
 * affiliate registration, and additional Elementor controls specific to AffiliateWP.
 *
 * @package    AffiliateWP
 * @subpackage Integrations
 * @copyright  Copyright (c) 2023, Sandhills Development, LLC
 * @since      2.19.0
 */

require_once 'elementor/trait-elementor-shared-utils.php';

#[\AllowDynamicProperties]

/**
 * Implements an integration for Elementor.
 *
 * @since 2.19.0
 *
 * @see Affiliate_WP_Base
 */
class Affiliate_WP_Elementor extends Affiliate_WP_Base {

	use Elementor_Shared_Utils;

	/**
	 * The context for referrals. This refers to the integration that is being used.
	 *
	 * @access  public
	 * @since 2.22.0
	 */
	public $context = 'elementor';

	/**
	 * The lead's email address.
	 *
	 * @access public
	 * @since 2.24.0
	 */
	public $lead_email = '';

	/**
	 * The lead's first name.
	 *
	 * @access private
	 * @since 2.24.0
	 */
	private $lead_first_name = '';

	/**
	 * The lead's last name.
	 *
	 * @access private
	 * @since 2.24.0
	 */
	private $lead_last_name = '';

	/**
	 * Get things started
	 *
	 * @access  public
	 *
	 * @since 2.19.0
	*/
	public function init() : void {
		add_action( 'elementor_pro/forms/actions/register', array( $this, 'add_new_action' ) );

		// Affiliate registration.
		add_filter( 'elementor_pro/forms/render/item', array( $this, 'disable_logged_in_fields' ), 10, 3 );
		add_action( 'elementor/controls/register',  array( $this, 'register_controls' ) );
		add_action( 'elementor_pro/forms/new_record', array( $this, 'affiliate_email' ), 10, 2 );
		add_action( 'elementor_pro/forms/validation/email', array( $this, 'validate_user_email_field' ), 10, 3 );
		add_action( 'elementor_pro/forms/validation/text', array( $this, 'validate_user_login_field' ), 10, 3 );
		add_action( 'elementor-pro/forms/pre_render', array( $this, 'pre_render_form' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'hide_registration_form' ) );

		// Referral tracking.
		add_filter( 'affwp_referral_table_description', array( $this, 'referral_table_description' ), 10, 2 );
		add_action( 'affwp_edit_referral_end', array( $this, 'lead_information_table' ), 10, 1 );
		add_action( 'elementor_pro/forms/new_record', array( $this, 'add_referral' ), 10, 2 );

		// Filter the custom registration fields.
		add_filter( 'affwp_get_affiliate_custom_registration_fields', array( $this, 'filter_registration_fields' ), 10, 2 );
	}

	/**
	 * Hide the registration form if the user is already an affiliate.
	 *
	 * @since AFFPWN
	 *
	 * @return void
	 */
	public function hide_registration_form() : void {
		// Return early if the user is not logged in or is not an affiliate.
		if ( ! ( is_user_logged_in() && affwp_is_affiliate() ) ) {
			return;
		}
		?>
		<style>
			.elementor-form:has(.affwp-elementor-registration-form)  {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Hide form and show notice to affiliates who are already registered.
	 *
	 * @since AFFPWN
	 *
	 * @param array $instance The form instance.
	 * @param Form  $form The form object.
	 *
	 * @return void
	 */
	public function pre_render_form( $instance, $form ) : void {

		// Return early if this form isn't an affiliate registration form.
		if ( ! $this->is_affiliate_registration( $this->get_affiliate_registration_setting( $instance ) ) ) {
			return;
		}

		// Return early if the user is not logged in or is not an affiliate.
		if ( ! ( is_user_logged_in() && affwp_is_affiliate() ) ) {
			return;
		}

		$affiliate_area_page_id = affwp_get_affiliate_area_page_id();
		$affiliate_notice       = esc_html__( 'You are already registered as an affiliate.', 'affiliate-wp' );

		// Add a link to the affiliate area page if it exists.
		if ( $affiliate_area_page_id ) {
			$affiliate_notice .= ' ' . sprintf(
				// translators: %1$s is the opening anchor tag, %2$s is the closing anchor tag.
				esc_html__( 'Visit your %1$sAffiliate Area%2$s for more information.', 'affiliate-wp' ),
				'<a href="' . esc_url( get_permalink( $affiliate_area_page_id ) ) . '">',
				'</a>'
			);
		}
		?>
			<p class="affwp-notice"><?php echo wp_kses_post( $affiliate_notice ); ?></p>
		<?php

		// Add a class to the form wrapper. We'll use this to hide the form.
		$form->add_render_attribute( 'wrapper', 'class', 'affwp-elementor-registration-form' );

		// Disable the submit button on the form.
		$form->add_render_attribute( 'button', 'disabled', 'disabled' );
	}

	/**
	 * Validate the user email field on the affiliate registration form.
	 *
	 * @since 2.25.0
	 *
	 * @param array                                           $field The field data.
	 * @param ElementorPro\Modules\Forms\Classes\Form_Record  $record The form record.
	 * @param ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler The ajax handler.
	 *
	 * @return void
	 */
	public function validate_user_email_field( $field, $record, $ajax_handler ) : void {

		// Return early if this form isn't an affiliate registration form.
		if ( ! $this->is_affiliate_registration( $this->get_affiliate_registration_setting( $record ) ) ) {
			return;
		}

		// Return early if the user is logged in.
		if ( is_user_logged_in() ) {
			return;
		}

		$user_data = $this->form_data(
			$record,
			array(
				'user_email',
			),
			'affiliatewp_fields_map'
		);

		$fields_map = $this->get_fields_map( $record, 'affiliatewp_fields_map' );

		// Checks the user_email field (and not payment_email) to see if the email address is already registered to a WP user account.
		if ( $fields_map['user_email'] === $field['id'] && email_exists( $user_data['user_email'] ) ) {
			$ajax_handler->add_error( $field['id'], esc_html__( 'An affiliate with that email already exists.', 'affiliate-wp' ) );
			return;
		}
	}

	/**
	 * Validate the username field on the affiliate registration form.
	 *
	 * @since 2.25.0
	 *
	 * @param array                                           $field The field data.
	 * @param ElementorPro\Modules\Forms\Classes\Form_Record  $record The form record.
	 * @param ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler The ajax handler.
	 *
	 * @return void
	 */
	public function validate_user_login_field( $field, $record, $ajax_handler ) : void {

		if ( ! $this->is_affiliate_registration( $this->get_affiliate_registration_setting( $record ) ) ) {
			return;
		}

		$fields_map = $this->get_fields_map( $record, 'affiliatewp_fields_map' );

		// Checks the user_email field (and not payment_email) to see if the email address is already registered to a WP user account.
		if ( ! empty( $field['value'] ) && $fields_map['user_login'] === $field['id'] ) {

			if ( ! is_user_logged_in() && username_exists( $field['value'] ) ) {
				$ajax_handler->add_error( $field['id'], esc_html__( 'An account with that username already exists.', 'affiliate-wp' ) );
			}

			if ( ! validate_username( $field['value'] ) ) {
				$ajax_handler->add_error( $field['id'], esc_html__( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'affiliate-wp' ) );
			}

			return;
		}

	}

	/**
	 * Get the lead's details.
	 *
	 * @since 2.22.0
	 *
	 * @param \AffWP\Referral $referral The referral object.
	 *
	 * @return array
	 */
	private function lead_details( $referral ) : array {
		// Get lead details from referral meta.

		return array(
			'email' => array(
				'name'  => esc_html__( 'Email Address', 'affiliate-wp' ),
				'value' => affwp_get_referral_meta( $referral->referral_id, 'lead_email', true ),
			),
			'first_name' => array(
				'name'  => esc_html__( 'First Name', 'affiliate-wp' ),
				'value' => affwp_get_referral_meta( $referral->referral_id, 'lead_first_name', true ),
			),
			'last_name' => array(
				'name'  => esc_html__( 'Last Name', 'affiliate-wp' ),
				'value' => affwp_get_referral_meta( $referral->referral_id, 'lead_last_name', true ),
			),
		);
	}

	/**
	 * Display the lead information table on the edit referral screen.
	 *
	 * @since 2.22.0
	 *
	 * @param \AffWP\Referral $referral The referral object.
	 *
	 * @return void
	 */
	public function lead_information_table( $referral ) : void {

		if ( 'elementor' !== $referral->context ) {
			return;
		}

		// Only for lead and opt-in referral types.
		if ( $referral->type === 'sale' ) {
			return;
		}

		$lead_details = $this->lead_details( $referral );
		?>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Lead Details', 'affiliate-wp' ); ?>
			</th>
			<td>
				<table class="widefat striped" style="max-width: 740px;">
					<tbody>
					<?php
						foreach ( $lead_details as $lead_detail ) :
							$field_value = isset( $lead_detail['value'] ) ? $lead_detail['value'] : '';
							$field_value = wp_strip_all_tags( $field_value );
						?>
						<tr class="form-row">
							<th style="max-width: 200px; padding-left: 1rem;">
								<?php if ( ! empty( $lead_detail['name'] ) ) : ?>
									<?php echo esc_html( wp_strip_all_tags( $lead_detail['name'] ) ); ?>
								<?php endif; ?>
							</th>
							<td>
								<?php if ( $field_value ) : ?>
									<?php echo wp_kses_post( nl2br( make_clickable( $field_value ) ) ); ?>
								<?php else : ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
	}

	/**
	 * Modifies the Description column value to show the lead's email.
	 *
	 * @since 2.22.0
	 *
	 * @param string           $value    Data shown in the Description column.
	 * @param \AffWP\Referral  $referral The referral data.
	 *
	 * @return string The value of the column.
	 */
	public function referral_table_description( $value, $referral ) : string {
		if ( 'elementor' === $referral->context ) {
			$lead_email = $this->lead_details( $referral )['email']['value'];
			$value      = "{$value} <div style='color: rgba(0,0,0,0.5); font-size: 13px;'>{$lead_email}</div>";
		}

		return $value;
	}

	/**
	 * Create a Reference to use for the Referral.
	 *
	 * This uses a counter to generate an ID to use for Reference in the DB.
	 *
	 * @since 2.22.0
	 *
	 * @return string Returns a reference based on a counter we will
	 *                maintain in the database.
	 */
	private function create_reference() : string {

		// Where we store the reference counter.
		$option_key = 'affwp_elementor_ref_counter';

		// Get the last ID we generated (default counting at 1).
		$reference = get_option( $option_key, 1 );

		// Iterate the counter for next time and write that to the DB.
		update_option( $option_key, $reference + 1, false );

		return $reference;
	}

	/**
	 * Adds a referral when a form is submitted.
	 *
	 * @since 2.22.0
	 *
	 * @param ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param ElementorPro\Modules\Forms\Classes\Ajax_Handler $handler
	 *
	 * @return void
	 */
	public function add_referral( $record, $handler ) : void {

		// Ensure $record is an instance of Form_Record
		if ( ! $record instanceof \ElementorPro\Modules\Forms\Classes\Form_Record ) {
			return;
		}

		$enable_referrals = $record->get_form_settings( 'affwp_enable_referrals' );
		if ( empty( $enable_referrals ) || 'yes' !== $enable_referrals ) {
			return;
		}

		// Return if the customer was not referred or the affiliate ID is empty.
		if ( ! $this->was_referred() && empty( $this->affiliate_id ) ) {
			return; // Referral not created because affiliate was not referred.
		}

		// Get form data.
		$form_data = $this->form_data(
			$record,
			array( 'first_name', 'last_name', 'email' ),
			'affiliatewp_referral_fields_map'
		);

		// Set lead email.
		$this->lead_email = $form_data['email'] ?? '';

		// Set lead's first and last name.
		$this->lead_first_name = $form_data['first_name'] ?? '';
		$this->lead_last_name  = $form_data['last_name'] ?? '';

		// Get the form ID.
		$form_id = $handler->get_current_form()['id'];

		// Referral description.
		$description = ! empty( $record->get_form_settings( 'form_name' ) ) ? $record->get_form_settings( 'form_name' ) : esc_html__( 'Elementor Form', 'affiliate-wp' );
		$description = "$description ({$form_id})";

		// Create reference.
		$reference = $this->create_reference();

		// Set referral type.
		$this->referral_type = $record->get_form_settings( 'affwp_referral_type' );

		// Create draft referral.
		$referral_id = $this->insert_draft_referral(
			$this->affiliate_id,
			array(
				'reference'   => $reference,
				'description' => $description
			)
		);

		if ( ! $referral_id ) {
			$this->log( 'Draft referral creation failed.' );
			return;
		}

		// Customers cannot refer themselves.
		if ( $this->is_affiliate_email( $this->lead_email, $this->affiliate_id ) ) {
			$this->log( 'Referral not created because affiliate\'s own account was used.' );
			$this->mark_referral_failed( $referral_id );
			return;
		}

		// Hydrates the previously created referral.
		$this->hydrate_referral(
			$referral_id,
			array(
				'status'      => 'pending',
				'amount'      => $record->get_form_settings( 'affwp_referral_amount' ),
				'order_total' => 0,
				'campaign'    => affiliate_wp()->tracking->get_campaign(),
				'products'    => '',
				'context'     => $this->context,
			)
		);

		$this->log( sprintf( 'Elementor referral #%d updated to pending successfully.', $referral_id ) );

		if ( 'unpaid' === $record->get_form_settings( 'affwp_referral_status' ) ) {
			$this->complete_referral( $reference );
			$this->log( sprintf( 'Elementor referral #%d updated to unpaid successfully.', $referral_id ) );
		}

		// Add lead data to referral meta.
		affwp_update_referral_meta( $referral_id, 'lead_email', $this->lead_email );
		affwp_update_referral_meta( $referral_id, 'lead_first_name', $this->lead_first_name ?? '' );
		affwp_update_referral_meta( $referral_id, 'lead_last_name', $this->lead_last_name ?? '' );
	}

	/**
	 * Retrieves the customer details.
	 *
	 * @since 2.24.0
	 *
	 * @param int $order_id The ID of the order to retrieve customer details for.
	 * @return array $customer An array of the customer details.
	 */
	public function get_customer( $order_id = 0 ): array {

		return array(
			'first_name'   => $this->lead_first_name,
			'last_name'    => $this->lead_last_name,
			'email'        => $this->lead_email,
			'user_id'      => get_current_user_id(),
			'ip'           => affiliate_wp()->tracking->get_ip(),
			'affiliate_id' => $this->affiliate_id
		);
	}

	/**
	 * Disable logged in fields.
	 *
	 * @access public
	 * @since 2.19.0
	 * @param array $item       The field value.
	 * @param int   $item_index The field index.
	 * @param Form  $form An instance of the form.
	 */
	public function disable_logged_in_fields( $item, $item_index, $form ) : array {

		$settings = $form->get_settings_for_display();

		// Return early if this form isn't an affiliate registration form.
		if ( ! ( ! empty( $settings['affiliate_registration'] ) && 'yes' === $settings['affiliate_registration'] ) ) {
			return $item;
		}

		// Return early if user is not logged in or if in admin area.
		if ( ! is_user_logged_in() || is_admin() ) {
			return $item;
		}

		// Get logged in WP user details.
		$current_user = wp_get_current_user();

		$user_attributes = array(
			'user_login' => $current_user->user_login,
			'name'       => $current_user->display_name,
			'user_email' => $current_user->user_email,
			'user_url'   => $current_user->user_url,
		);

		foreach ( $form->get_settings( 'affiliatewp_fields_map' ) as $mapped_field ) {

			if ( $mapped_field['local_id'] !== $item['custom_id'] ) {
				continue;
			}

			$attributes = 'user_url' === $mapped_field['remote_id'] ? array() : array(
				'readonly' => 'readonly',
				'style'    => 'background-color: #f7f7f7; opacity: 0.5; cursor: not-allowed;'
			);

			if ( isset( $user_attributes[ $mapped_field['remote_id'] ] ) ) {
				$field_value = $user_attributes[ $mapped_field['remote_id'] ];

				$attributes['value'] = $field_value;

				if ( ! empty( $field_value ) ) {
					$form->add_render_attribute(
						"input{$item_index}",
						$attributes
					);
				}
			}

			if ( 'user_pass' === $mapped_field['remote_id'] ) {
				$attributes['value'] = '************'; // Set dummy value to bypass validation (if required)

				$form->add_render_attribute(
					"input{$item_index}",
					$attributes
				);
			}

			break;
		}

		return $item;
	}

	/**
	 * Register new field mapping control.
	 *
	 * @since 2.19.0
	 * @param $controls Controls_Manager
	 *
	 * @return void
	 */
	public function register_controls( $controls ) : void {
		include_once( __DIR__ .  '/elementor/field-mapping.php' );
		$controls->register( new Field_Mapping() );

		include_once( __DIR__ .  '/elementor/referral-field-mapping.php' );
		$controls->register( new Referral_Field_Mapping() );
	}

	/**
	 * Add new form action after form submission.
	 *
	 * @since 2.19.0
	 *
	 * @param ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar $form_actions_registrar
	 * @return void
	 */
	public function add_new_action( $form_actions_registrar ) : void {
		include_once( __DIR__ .  '/elementor/form-actions/affiliatewp.php' );
		$form_actions_registrar->register( new \AffiliateWP_Action_After_Submit() );
	}

	/**
	 * Set up a new custom action to email the affiliate after registration.
	 *
	 * @since 2.19.0
	 *
	 * @param ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param ElementorPro\Modules\Forms\Classes\Ajax_Handler $handler
	 *
	 * @return void
	 */
	public function affiliate_email( $record, $handler ) : void {
		include_once( __DIR__ . '/elementor/form-actions/affiliate-email.php' );

		// Return early if this form isn't an affiliate registration form.
		if ( ! $this->is_affiliate_registration( $this->get_affiliate_registration_setting( $record ) ) ) {
			return;
		}

		$affiliate_email = new Affiliate_Email_After_Registration();
		$affiliate_email->run( $record, $handler );
	}

	/**
	 * Runs the check necessary to confirm this plugin is active.
	 *
	 * @since 2.19.0
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	function plugin_is_active() : bool {
		return class_exists( 'Elementor\Plugin' );
	}

	/**
	 * Filter the custom registration fields.
	 *
	 * @since 2.27.1
	 *
	 * @param array $fields       The custom registration fields.
	 * @param int   $affiliate_id The affiliate ID.
	 * @return array $fields The custom registration fields.
	 */
	public function filter_registration_fields( $fields, $affiliate_id ) : array {
		$elementor_data = affwp_get_affiliate_meta( $affiliate_id, 'elementor_affiliate_registration_data', true );

		if ( ! empty( $elementor_data ) && is_array( $elementor_data ) ) {
			foreach ( $elementor_data as $field_id => $field_data ) {
				$key            = 'elementor_' . $field_id;
				$fields[ $key ] = array(
					'name'       => ! empty( $field_data['title'] ) ? $field_data['title'] : $field_id,
					'type'       => $field_data['type'],
					'meta_value' => $field_data['value'],
				);

				// Convert checkbox "on" value to "Yes".
				if ( 'acceptance' === $field_data['type'] && 'on' === $field_data['value'] ) {
					$fields[ $key ]['meta_value'] = 'Yes';
				}
			}
		}

		return $fields;
	}

}
