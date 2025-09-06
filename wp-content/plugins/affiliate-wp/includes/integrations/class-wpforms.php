<?php

use WPForms\Emails\Mailer;
use WPForms\Emails\Templates;

/**
 * Integrations: WPForms
 *
 * @package     AffiliateWP
 * @subpackage  Integrations
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

#[\AllowDynamicProperties]

/**
 * Implements an integration for WPForms.
 *
 * @since 1.2
 *
 * @see Affiliate_WP_Base
 */
class Affiliate_WP_WPForms extends Affiliate_WP_Base {

	/**
	 * The context for referrals. This refers to the integration that is being used.
	 *
	 * @access  public
	 *
	 * @since   1.2
	 */
	public $context = 'wpforms';

	/**
	 * List of submitted fields.
	 *
	 * @since 2.11.0
	 *
	 * @var array
	 */
	private $fields = [];

	/**
	 * Form data.
	 *
	 * @since 2.11.0
	 *
	 * @var array
	 */
	private $form_data = [];

	/**
	 * Entry id.
	 *
	 * @since 2.11.0
	 *
	 * @var int
	 */
	private $entry_id;

	/**
	 * Temporary storage for password.
	 *
	 * @since 2.11.0
	 *
	 * @var string
	 */
	private static $password = '';

	/**
	 * Set user password.
	 *
	 * @since 2.11.0
	 *
	 * @param string $password Password.
	 */
	public static function set_password( $password ) {

		self::$password = $password;
	}

	/**
	 * Get user password.
	 *
	 * @since 2.11.0
	 *
	 * @return string
	 */
	public static function get_password() {

		if ( ! empty( self::$password ) ) {
			return self::$password;
		}

		$password = wp_generate_password( 18 );

		self::set_password( $password );

		return $password;
	}

	/**
	 * Get things started
	 *
	 * @access  public
	 *
	 * @since   2.0
	*/
	public function init() {

		// Referral creation.
		add_action( 'wpforms_process_complete', array( $this, 'add_pending_referral' ), 10, 4 );
		add_action( 'wpforms_paypal_standard_process_complete', array( $this, 'mark_referral_complete' ), 10, 4 );
		add_action( 'wpforms_stripe_process_complete', array( $this, 'mark_referral_complete' ), 10, 4 );
		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );

		// Filter the custom registration fields.
		add_filter( 'affwp_get_affiliate_custom_registration_fields', array( $this, 'filter_registration_fields' ), 10, 2 );

		// Review/Edit Affiliate.
		add_action( 'affwp_review_affiliate_end', array( $this, 'add_entry_link' ) );
		add_action( 'affwp_edit_affiliate_end', array( $this, 'add_entry_link' ) );

		/**
		 * Affiliate Registration.
		 *
		 * A lot of the affiliate registration functionality was made possible
		 * thanks to our friends at WPForms and their User Registration addon.
		 */
		add_action( 'wpforms_loaded', array( $this, 'load_custom_templates' ) );
		add_action( 'wpforms_builder_enqueues', array( $this, 'admin_enqueues' ) );
		add_action( 'wpforms_form_settings_panel_content', array( $this, 'panel_content' ), 10, 2 );
		add_action( 'wpforms_wp_footer_end', array( $this, 'disable_fields' ), 30 );
		add_action( 'wpforms_process_complete', array( $this, 'register_user_and_affiliate' ), 10, 4 );
		add_action( 'wpforms_process', array( $this, 'process' ), 9, 3 );
		add_filter( 'wpforms_builder_settings_sections', array( $this, 'settings_sections' ), 10, 2 );
		add_filter( 'wpforms_builder_strings', array( $this, 'builder_strings' ) );
		add_filter( 'wpforms_frontend_load', array( $this, 'display_form' ), 10, 2 );
		add_filter( 'wpforms_process_after_filter', array( $this, 'process_after_filter' ), 10, 3 );
		add_filter( 'wpforms_smart_tags', array( $this, 'smart_tags' ), 10, 1 );
		add_filter( 'wpforms_smart_tag_process', array( $this, 'process_smart_tags' ), 10, 2 );
	}

	/**
	 * Filter the custom registration fields.
	 *
	 * @since 2.27.1
	 *
	 * @param array $fields       The custom registration fields.
	 * @param int   $affiliate_id The affiliate ID.
	 * @return array $fields      The custom registration fields.
	 */
	public function filter_registration_fields( $fields, $affiliate_id ) : array {
		$wpforms_data = affwp_get_affiliate_meta( $affiliate_id, 'wpforms_affiliate_registration_data', true );
		$form_id      = affwp_get_affiliate_meta( $affiliate_id, 'wpforms_form_id', true );

		if ( ! empty( $wpforms_data ) && is_array( $wpforms_data ) ) {
			// Get the excluded field IDs.
			$excluded_field_ids = $this->excluded_field_ids( $form_id );

			foreach ( $wpforms_data as $field_id => $field_data ) {

				// Skip excluded fields.
				if ( in_array( (int) $field_id, array_map( 'intval', $excluded_field_ids ), true ) ) {
					continue;
				}

				$key = 'wpforms_' . $field_id;
				$fields[ $key ] = array(
					'name'       => ! empty( $field_data['name'] ) ? $field_data['name'] : sprintf( /* translators: %d - field ID. */
						esc_html__( 'Field ID #%d', 'affiliate-wp' ),
						absint( $field_id )
					),
					'type'       => $field_data['type'],
					'meta_value' => $field_data['value'],
				);
			}
		}

		return $fields;
	}

	/**
	 * Get required user fields.
	 *
	 * @since 2.11.0
	 *
	 * @param array $fields    The fields that have been submitted.
	 * @param array $form_data The information for the form.
	 *
	 * @return array
	 */
	private function get_required_fields( $fields, $form_data ) {

		$form_settings   = $form_data['settings'];

		$required_fields = array();

		foreach ( $fields as $field ) {
			if ( ! isset( $field['id'], $field['value'], $form_data['fields'][ $field['id'] ]['meta'] ) ) {
				continue;
			}

			$nickname = $form_data['fields'][ $field['id'] ]['meta']['nickname'];

			if ( empty( $nickname ) || ! in_array( $nickname, array( 'username', 'email' ), true ) ) {
				continue;
			}

			$required_fields[ $nickname ] = $field['value'];
		}

		// If a username was not set by field meta method then check for the mapped field.
		if ( isset( $form_settings['affwp_username'] ) && ! empty( $fields[ $form_settings['affwp_username'] ]['value'] ) ) {
			$required_fields['username'] = $fields[ $form_settings['affwp_username'] ]['value'];
		}

		// If a email was not set by field meta method then check for the mapped field.
		if ( isset( $form_settings['affwp_email'] ) && ! empty( $fields[ $form_settings['affwp_email'] ]['value'] ) ) {
			$required_fields['email'] = $fields[ $form_settings['affwp_email'] ]['value'];
		}

		// If we _still_ don't have a username, then fallback to using email.
		if ( ! isset( $required_fields['username'] ) && isset( $required_fields['email'] ) ) {
			$required_fields['username'] = $required_fields['email'];
		}

		return $required_fields;
	}

	/**
	 * Process a form.
	 *
	 * @since 2.11.0
	 *
	 * @param array $fields    The fields that have been submitted.
	 * @param array $entry     The post data submitted by the form.
	 * @param array $form_data The information for the form.
	 */
	public function process( $fields, $entry, $form_data ) {

		/**
		 * Bail if:
		 * 1) if it is not an affiliate registration form.
		 * 2) if form contains errors.
		 */
		if (
			! $this->is_affiliate_registration_enabled( $form_data ) ||
			! empty( wpforms()->get( 'process' )->errors[ $form_data['id'] ] )
		) {
			return;
		}

		$reg_fields = $this->get_required_fields( $fields, $form_data );

		// Check that we have all the required fields, if not abort.
		if ( empty( $reg_fields['email'] ) ) {
			wpforms()->get( 'process' )->errors[ $form_data['id'] ]['header'] = esc_html__( 'Email address is required', 'affiliate-wp' );

			return;
		}

		// Check that the username does not already exist for logged out users.
		if ( ! is_user_logged_in() && username_exists( $reg_fields['username'] ) ) {
			$message = esc_html__( 'An account with that username already exists.', 'affiliate-wp' );

			wpforms()->get( 'process' )->errors[ $form_data['id'] ]['header'] = $message;

			return;
		}

		/**
		 * Check if username is valid.
		 *
		 * The username is only validated if it was entered by the affiliate.
		 * When the username field is left blank, the email address is used as the username.
		 */
		$form_settings = $form_data['settings'];
		$username_field_populated = isset( $form_settings['affwp_username'] ) && ! empty( $fields[ $form_settings['affwp_username'] ]['value'] );

		if ( $username_field_populated && ! validate_username( $reg_fields['username'] ) ) {
			wpforms()->get( 'process' )->errors[ $form_data['id'] ]['header'] = esc_html__( 'This username is invalid because it uses illegal characters. Please enter a valid username.', 'affiliate-wp' );

			return;
		}

		// Check that email does not already exist.
		if ( ! is_user_logged_in() && email_exists( $reg_fields['email'] ) ) {
			$message = esc_html__( 'An affiliate with that email already exists.', 'affiliate-wp' );

			wpforms()->get( 'process' )->errors[ $form_data['id'] ]['header'] = $message;

			return;
		}

	}

	/**
	 * Check if affiliate registration enabled.
	 *
	 * @since 2.11.0
	 *
	 * @param array $form_data The information for the form.
	 *
	 * @return bool
	 */
	public function is_affiliate_registration_enabled( $form_data ) {
		return ! empty( $form_data['settings']['affwp_affiliate_registration_enable'] );
	}

	/**
	 * After processing a form.
	 *
	 * @since 2.11.0
	 *
	 * @param array $fields    Fields.
	 * @param array $entry     Entry.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function process_after_filter( $fields, $entry, $form_data ) {

		if ( ! $this->is_affiliate_registration_enabled( $form_data ) ) {
			return $fields;
		}

		$password_field_id = isset( $form_data['settings']['affwp_password'] ) && $form_data['settings']['affwp_password'] !== '' ? absint( $form_data['settings']['affwp_password'] ) : '';

		$this->set_password( ! empty( $fields[ $password_field_id ]['value'] ) ? $fields[ $password_field_id ]['value'] : '' );

		return $this->hide_password_value( $fields );
	}

	/**
	 * Hide password value.
	 *
	 * @since 2.11.0
	 *
	 * @param array $fields Fields.
	 *
	 * @return array $fields Fields.
	 */
	protected function hide_password_value( $fields ) {

		foreach ( $fields as $id => $field ) {
			if ( $field['type'] !== 'password' ) {
				continue;
			}

			$fields[ $id ]['value'] = '**********';
		}

		return $fields;
	}

	/**
	 * Load the templates.
	 * @access public
	 *
	 * @since 2.11.0
	 */
	public function load_custom_templates() {
		include_once( 'class-wpforms-templates.php' );
	}

	/**
	 * Add our localized strings to be available in the form builder.
	 *
	 * @since 2.11.0
	 *
	 * @param array $strings Form builder strings.
	 *
	 * @return array $strings Form builder strings.
	 */
	public function builder_strings( $strings ) {
		$strings['user_registration_conflict'] = sprintf( __( 'User Registration cannot be used with Affiliate Registration. Please disable the "Enable User Registration" option to allow affiliate registrations.', 'affiliate-wp' ) );

		return $strings;
	}

	/**
	 * Enqueue assets for the builder.
	 *
	 * @access public
	 *
	 * @since 2.11.0
	 */
	public function admin_enqueues() {
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script(
			'affiliate-wp-wpforms-admin-builder',
			AFFILIATEWP_PLUGIN_URL . "assets/js/wpforms-admin-builder{$min}.js",
			[ 'jquery' ],
			AFFILIATEWP_VERSION,
			false
		);
	}

	/**
	 * Settings register section.
	 *
	 * @since 2.11.0
	 *
	 * @param array $sections  Settings sections.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function settings_sections( $sections, $form_data ) {
		$sections['affiliatewp'] = esc_html__( 'AffiliateWP', 'affiliate-wp' );

		return $sections;
	}

	/**
	 * Add content for `AffiliateWP` panel.
	 *
	 * @since 2.11.0
	 *
	 * @param WPForms_Builder_Panel_Settings $instance Settings panel instance.
	 */
	public function panel_content( $instance ) {

		$this->form_data = $instance->form_data;

		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-affiliatewp">';
		echo '<div class="wpforms-panel-content-section-title">';
		esc_html_e( 'AffiliateWP', 'affiliate-wp' );
		echo '</div>';

		$hide = 'display:none;';

		wpforms_panel_field(
			'toggle',
			'settings',
			'affwp_allow_referrals',
			$instance->form_data,
			esc_html__( 'Enable Referrals', 'affiliate-wp' ),
			[
				'tooltip' => esc_html__( 'Allow this form to generate referrals.', 'affiliate-wp' ),
			]
		);

		echo '<div id="wpforms-affiliatewp-referrals-content-block" style="' . esc_attr( $hide ) . '">';

		wpforms_panel_fields_group(
			$this->referral_fields( $instance ),
			[
				'title'       => esc_html__( 'Referral creation', 'affiliate-wp' ),
				'description' => esc_html__( 'Allow this form to generate referrals.', 'affiliate-wp' ),
			]
		);

		echo '</div>';

		wpforms_panel_field(
			'toggle',
			'settings',
			'affwp_affiliate_registration_enable',
			$instance->form_data,
			esc_html__( 'Enable Affiliate Registration', 'affiliate-wp' ),
			[
				'tooltip' => esc_html__( 'Use this form for registering affiliates.', 'affiliate-wp' )
			]
		);

		echo '<div id="wpforms-affiliatewp-content-block" style="' . esc_attr( $hide ) . '">';

		wpforms_panel_fields_group(
			$this->affiliate_reg_fields( $instance ),
			[
				'title'       => esc_html__( 'Field Mapping', 'affiliate-wp' ),
				'description' => esc_html__( 'Connect your form fields to information in the affiliate\'s account.', 'affiliate-wp' ),
			]
		);

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Get optional user fields.
	 *
	 * @since 2.11.0
	 *
	 * @param array $fields    The fields that have been submitted.
	 * @param array $form_data The information for the form.
	 *
	 * @return array
	 */
	private function get_optional_fields( $fields, $form_data ) {

		$optional        = [ 'name', 'promotion_method', 'website', 'payment_email' ];
		$form_settings   = $form_data['settings'];
		$optional_fields = [];

		foreach ( $optional as $opt ) {

			$key = 'affwp_' . $opt;
			$id  = isset( $form_settings[ $key ] ) && $form_settings[ $key ] !== '' ? absint( $form_settings[ $key ] ) : '';

			if ( empty( $fields[ $id ]['value'] ) ) {
				continue;
			}

			if ( $opt === 'name' ) {

				$nkey                          = $form_data['fields'][ $id ]['format'] === 'simple' ? 'value' : 'first';
				$optional_fields['first_name'] = ! empty( $fields[ $id ][ $nkey ] ) ? $fields[ $id ][ $nkey ] : '';
				$optional_fields['last_name']  = ! empty( $fields[ $id ]['last'] ) ? $fields[ $id ]['last'] : '';
				$optional_fields['display_name']  = ! empty( $fields[ $id ]['value'] ) ? $fields[ $id ]['value'] : '';
			} else {
				$optional_fields[ $opt ] = $fields[ $id ]['value'];
			}
		}

		$optional_fields['password'] = $this->get_password();

		return $optional_fields;
	}

	/**
	 * Get registration data
	 *
	 * Attempts to retrieve fields based on the entry ID in WPForms Pro.
	 * Fields are pulled from the WPForms' entry and will stay up to date
	 * when edited. If no entry ID (E.g. WPForms Lite) it will fallback to using
	 * affiliate meta.
	 *
	 * @access public
	 *
	 * @since 2.11.0
	 *
	 * @param int    $affiliate_id Affiliate ID.
	 * @param int    $entry_id The entry ID submitted.
	 *
	 * @return array $field_data The field data.
	 */
	public function get_registration_data( $affiliate_id = 0, $entry_id = 0 ) {

		if ( ! $affiliate_id ) {
			return array();
		}

		if ( $entry_id && ! $this->is_wpforms_lite() ) {
			$entry = wpforms()->get( 'entry' )->get( absint( $entry_id ) );
			$field_data = ! empty( $entry ) ? wpforms_decode( $entry->fields ) : array();
		} else {
			// Retrieve fields from affiliate meta (WPForms Lite)
			$field_data = affwp_get_affiliate_meta( $affiliate_id, 'wpforms_affiliate_registration_data', true );
		}

		return $field_data;
	}

	/**
	 * Add entry link to the review affiliate screen.
	 *
	 * @access public
	 *
	 * @since 2.11.0
	 */
	public function add_entry_link( $affiliate ) {
		// Entry ID will only exist for registrations while WPForms Pro was installed.
		$entry_id = affwp_get_affiliate_meta( $affiliate->affiliate_id, 'wpforms_entry_id', true );

		if ( $entry_id ) :
			?>
			<tr>
				<th scope="row">
					<?php echo esc_html__( 'WPForms Entry', 'affiliate-wp' ); ?>
				</th>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpforms-entries&view=details&entry_id=' . $entry_id ) ); ?>"><?php esc_html_e( 'View Entry', 'affiliate-wp' ); ?></a>
					<p class="description">
						<?php esc_html_e( 'The complete entry submitted during affiliate registration.', 'affiliate-wp' ); ?>
					</p>
				</td>
			</tr>
			<?php
		endif;
	}

	/**
	 * Fields for the "Referral Creation" section.
	 *
	 * @access private
	 *
	 * @since 2.11.0
	 *
	 * @param \WPForms_Builder_Panel_Settings $instance Settings instance.
	 * @return string $output
	 */
	private function referral_fields( $instance ) {

		$output = '';

		$options = array();
		foreach( affwp_get_referral_types() as $type_id => $type ) {
			$options[ $type_id ] =  $type['label'];
		}

		$output .= wpforms_panel_field(
			'select',
			'settings',
			'affwp_referral_type',
			$instance->form_data,
			esc_html__( 'Referral type', 'affiliate-wp' ),
			[
				'options' => $options,
				'tooltip' => esc_html__( 'Select the type of referral this should be.', 'affiliate-wp' ),
			],
			false
		);

		return $output;
	}

	/**
	 * Affiliate registration fields page content.
	 *
	 * @since 2.11.0
	 *
	 * @param \WPForms_Builder_Panel_Settings $instance Settings instance.
	 *
	 * @return string
	 */
	private function affiliate_reg_fields( $instance ) {

		$output = '';

		// Name.
		$output .= wpforms_panel_field(
			'select',
			'settings',
			'affwp_name',
			$instance->form_data,
			esc_html__( 'Name', 'affiliate-wp' ),
			[
				'field_map'   => [ 'name' ],
				'placeholder' => esc_html__( '--- Select Field ---', 'affiliate-wp' ),
			],
			false
		);

		$username = wpforms_get_form_fields_by_meta( 'nickname', 'username', $instance->form_data );

		if ( empty( $username ) ) {
			// Username.
			$output .= wpforms_panel_field(
				'select',
				'settings',
				'affwp_username',
				$instance->form_data,
				esc_html__( 'Username', 'affiliate-wp' ),
				[
					'field_map'   => [ 'name', 'text' ],
					'placeholder' => esc_html__( '--- Select Field ---', 'affiliate-wp' ),
					'tooltip'     => esc_html__( 'If a username is not set or provided, the affiliate\'s email address will be used instead.', 'affiliate-wp' ),
				],
				false
			);
		}

		$email = wpforms_get_form_fields_by_meta( 'nickname', 'email', $instance->form_data );

		if ( empty( $email ) ) {

			// Account Email.
			$output .= wpforms_panel_field(
				'select',
				'settings',
				'affwp_email',
				$instance->form_data,
				esc_html__( 'Account Email', 'affiliate-wp' ),
				[
					'field_map'     => [ 'email' ],
					'placeholder'   => esc_html__( '--- Select Field ---', 'affiliate-wp' ),
					'after_tooltip' => '&nbsp;<span class="required">*</span>',
					'input_class'   => 'wpforms-required',
				],
				false
			);

		}

		// Payment Email.
		$output .= wpforms_panel_field(
			'select',
			'settings',
			'affwp_payment_email',
			$instance->form_data,
			esc_html__( 'Payment Email', 'affiliate-wp' ),
			[
				'field_map'   => [ 'email' ],
				'placeholder' => esc_html__( '--- Select Field ---', 'affiliate-wp' ),
			],
			false
		);

		// Website URL.
		$output .= wpforms_panel_field(
			'select',
			'settings',
			'affwp_website',
			$instance->form_data,
			esc_html__( 'Website URL', 'affiliate-wp' ),
			[
				'field_map'   => [ 'text', 'url' ],
				'placeholder' => esc_html__( '--- Select Field ---', 'affiliate-wp' ),
			],
			false
		);

		// Promotion method.
		$output .= wpforms_panel_field(
			'select',
			'settings',
			'affwp_promotion_method',
			$instance->form_data,
			esc_html__( 'Promotion Method', 'affiliate-wp' ),
			[
				'field_map'   => [ 'textarea', 'text' ],
				'placeholder' => esc_html__( '--- Select Field ---', 'affiliate-wp' ),
			],
			false
		);

		// Password.
		$output .= wpforms_panel_field(
			'select',
			'settings',
			'affwp_password',
			$instance->form_data,
			esc_html__( 'Password', 'affiliate-wp' ),
			[
				'field_map'   => [ 'password' ],
				'placeholder' => esc_html__( 'Auto generate', 'affiliate-wp' ),
			],
			false
		);

		return $output;
	}

	/**
	 * Get User.
	 *
	 * @param string $user_id User ID.
	 *
	 * @since 2.11.0
	 *
	 * @return false|\WP_User
	 */
	private function get_user( $user_id ) {
		return get_user_by( 'id', $user_id );
	}

	/**
	 * Get user data.
	 *
	 * @since 2.11.0
	 *
	 * @param array $fields    The fields that have been submitted.
	 * @param array $form_data The information for the form.
	 *
	 * @return array
	 */
	private function get_data( $fields, $form_data ) {

		$reg_fields = array_merge( $this->get_required_fields( $fields, $form_data ), $this->get_optional_fields( $fields, $form_data ) );

		// Required user information.
		$user_data = [
			'user_login' => $reg_fields['username'],
			'user_email' => $reg_fields['email'],
			'user_pass'  => $reg_fields['password'],
		];

		// Display name.
		if ( ! empty( $reg_fields['display_name'] ) ) {
			$user_data['display_name'] = $reg_fields['display_name'];
		}

		// Optional user information.
		if ( ! empty( $reg_fields['website'] ) ) {
			$user_data['user_url'] = $reg_fields['website'];
		}

		if ( ! empty( $reg_fields['first_name'] ) ) {
			$user_data['first_name'] = $reg_fields['first_name'];
		}

		if ( ! empty( $reg_fields['last_name'] ) ) {
			$user_data['last_name'] = $reg_fields['last_name'];
		}

		if ( ! empty( $reg_fields['payment_email'] ) ) {
			$user_data['payment_email'] = $reg_fields['payment_email'];
		}

		if ( ! empty( $reg_fields['promotion_method'] ) ) {
			$user_data['promotion_method'] = $reg_fields['promotion_method'];
		}

		return (array) $user_data;
	}

	/**
	 * Register the affiliate / user
	 *
	 * @access  public
	 *
	 * @since 2.11.0
	 * @since 2.11.1 https://github.com/awesomemotive/AffiliateWP/issues/4562
	 *
	 * @param array $fields The form fields.
	 * @param array $entry The form entry.
	 * @param array $form_data The form data.
	 * @param int   $entry_id The form entry ID.
	 *
	 * @return bool
	 */
	public function register_user_and_affiliate( $fields, $entry, $form_data, $entry_id ) {
		/*
		 * Bail early if this isn't an affiliate registration form or the
		 * User Registration option is enabled.
		 */
		if ( ! $this->is_affiliate_registration_enabled( $form_data ) || isset( $form_data['settings']['registration_enable'] ) ) {
			return false;
		}

		// Get user data.
		$user_data = $this->get_data( $fields, $form_data );

		if ( ! is_user_logged_in() ) {
			$new_user = true;
			$user_id = wp_insert_user( $user_data );
		} else {
			$new_user = false;
			$user_id = get_current_user_id();
		}

		// Affiliate Status.
		$status = affiliate_wp()->settings->get( 'require_approval' ) ? 'pending' : 'active';

		// Add the affiliate.
		affwp_add_affiliate( array(
			'user_id'             => $user_id,
			'payment_email'       => ! empty( $user_data['payment_email'] ) ? sanitize_text_field( $user_data['payment_email'] ) : '',
			'dynamic_coupon'      => affiliate_wp()->settings->get( 'require_approval' ) ? '' : 1,
			'status'              => $status,
			'website_url'         => ! empty( $user_data['user_url'] ) ? sanitize_text_field( $user_data['user_url'] ) : '',
			'registration_method' => 'affiliate_registration_form_wpforms',
			'registration_url'    => esc_url_raw( get_permalink( $entry['post_id'] ) )
		) );

		// Log user if is they aren't already.
		if ( ! is_user_logged_in() ) {
			$this->log_user_in( $user_id, $user_data['user_login'] );
		}

		// Retrieve affiliate ID. Resolves issues with caching on some hosts, such as GoDaddy
		$affiliate_id = affwp_get_affiliate_id( $user_id );

		if ( true === $new_user ) {
			// Enable referral notifications by default for new users.
			affwp_update_affiliate_meta( $affiliate_id, 'referral_notifications', true );
		}

		// Promotion Method.
		if ( ! empty( $user_data['promotion_method'] ) ) {
			// Update affiliate meta with Promotion Method.
			affwp_update_affiliate_meta( $affiliate_id, 'promotion_method', sanitize_text_field( $user_data['promotion_method'] ) );
		}

		// Store the form ID the affiliate registered from.
		affwp_update_affiliate_meta( $affiliate_id, 'wpforms_form_id', $form_data['id'] );

		// Store the entry ID as affiliate meta.
		$entry_id = isset( $_POST['wpforms']['entry_id'] ) ? $_POST['wpforms']['entry_id'] : false;

		if ( $entry_id ) {
			// Entry ID is only available in pro versions.
			affwp_update_affiliate_meta( $affiliate_id, 'wpforms_entry_id', $entry_id );
		}

		$meta = array();

		foreach ( $fields as $key => $field ) {
			$meta[$key]['name']  = $field['name'];
			$meta[$key]['value'] = $field['value'];
			$meta[$key]['type']  = $field['type'];
		}

		affwp_update_affiliate_meta( $affiliate_id, 'wpforms_affiliate_registration_data', $meta );

		// Send affiliate notification.
		if ( true === $new_user ) {
			// Current users don't need email with their username or password.
			$this->notification( $user_id, $user_data, $form_data, $fields, $entry_id );
		}

		/**
		 * Fires immediately after registering a user.
		 *
		 * @since 2.11.0
		 *
		 * @param int    $affiliate_id Affiliate ID.
		 * @param string $status       Affiliate status.
		 * @param array  $user_data    User data.
		 */
		do_action( 'affwp_register_user', $affiliate_id, $status, $user_data );

		return (int) $affiliate_id;

	}

	/**
	 * Send notifications.
	 *
	 * @since 2.11.0
	 *
	 * @param int   $user_id   The user id.
	 * @param array $user_data User data.
	 * @param array $form_data The information for the form.
	 * @param array $fields    The fields that have been submitted.
	 * @param int   $entry_id  The entry id.
	 */
	private function notification( $user_id, $user_data, $form_data, $fields, $entry_id ) {

		$this->form_data = $form_data;
		$this->fields    = $fields;
		$this->entry_id  = $entry_id;

		$user = $this->get_user( $user_id );

		if ( ! $user ) {
			return;
		}

		// Send affiliate email notification.
		$this->affiliate_notification( $user, $user_data['user_pass'] );
	}

	/**
	 * Get default affiliate subject.
	 *
	 * @since 2.11.0
	 *
	 * @return string
	 */
	private function default_affiliate_subject() {

		return sprintf( /* translators: %s - {site_name} smart tag. */
			esc_html__( '%s Your affiliate username and password info', 'affiliate-wp' ),
			'{site_name}'
		);
	}

	/**
	 * Get default affiliate message.
	 *
	 * @since 2.11.0
	 *
	 * @return string
	 */
	private function default_affiliate_message() {

		$default_message  = sprintf( /* translators: %s - {affiliate_user_login} smart tag. */
			esc_html__( 'Username: %s', 'affiliate-wp' ),
			'{affiliate_user_login}'
		);
		$default_message .= "\r\n";
		$default_message .= sprintf( /* translators: %s - {affiliate_password} smart tag. */
			esc_html__( 'Password: %s', 'affiliate-wp' ),
			'{affiliate_password}'
		);
		$default_message .= "\r\n\r\n";
		$default_message .= sprintf( /* translators: %s - {affiliate_password} smart tag. */
			esc_html__( 'Log into your affiliate area at: %s', 'affiliate-wp' ),
			'{affiliate_login_url}'
		);
		$default_message .= "\r\n\r\n";

		return $default_message;
	}

	/**
	 * Affiliate notification.
	 *
	 * @since 2.11.0
	 *
	 * @return string
	 */
	private function affiliate_notification( $user, $plaintext_pass ) {

		// To do: Allow message and subject to be configurable from the AffiliateWP settings screen.
		$message = $this->default_affiliate_message();
		$subject = $this->default_affiliate_subject();

		$email = array(
			'address'  => $user->user_email,
			'subject'  => $subject,
			'message'  => $message,
			'user'     => $user,
			'password' => $plaintext_pass,
		);

		// Send notification
		$this->send( $email );
	}

	/**
	 * Send Email.
	 *
	 * @since 2.11.0
	 *
	 * @param array $email Email data to send.
	 */
	private function send( $email ) {

		$email['message'] = wpforms_process_smart_tags( $email['message'], $this->form_data, $this->fields, $this->entry_id );
		$email['subject'] = wpforms_process_smart_tags( $email['subject'], $this->form_data, $this->fields, $this->entry_id );

		$args = [
			'body' => [
				'message' => str_replace( "\r\n", '<br/>', $email['message'] ),
			],
		];

		$template = ( new Templates\General() )->set_args( $args );

		$content = $template->get();

		if ( ! $content ) {
			return;
		}

		( new Mailer() )
			->template( $template )
			->subject( $email['subject'] )
			->to_email( $email['address'] )
			->send();
	}

	/**
	 * Logs the user in.
	 *
	 * @access private
	 *
	 * @since 2.11.0
	 *
	 * @param  $user_id    The user ID.
	 * @param  $user_login The `user_login` for the user.
	 * @param  $remember   Whether or not the browser should remember the user login.
	 */
	private function log_user_in( $user_id = 0, $user_login = '', $remember = false ) {

		$user = get_userdata( $user_id );
		if ( ! $user )
			return;

		wp_set_auth_cookie( $user_id, $remember );
		wp_set_current_user( $user_id, $user_login );

		/**
		 * The `wp_login` action is fired here to maintain compatibility and stability of
		 * any WordPress core features, plugins, or themes hooking onto it.
		 *
		 * @param  string   $user_login The `user_login` for the user.
		 * @param  stdClass $user       The user object.
		 */
		do_action( 'wp_login', $user_login, $user );

	}

	/**
	 * Records a pending referral when a pending payment is created
	 *
	 * @access  public
	 *
	 * @since   2.0
	 *
	 * @param array $fields The form fields.
	 * @param array $entry The form entry.
	 * @param array $form_data The form data.
	 * @param int $entry_id The form entry ID.
	*/
	public function add_pending_referral( $fields, $entry, $form_data, $entry_id ) {

		$affiliate_id = $this->affiliate_id;

		// Return if the customer was not referred or the affiliate ID is empty.
		if ( ! $this->was_referred() && empty( $affiliate_id ) ) {
			return; // Referral not created because affiliate was not referred.
		}

		// Entry ID.
		if ( ! $entry_id ) {
			$entry_id = strtolower( md5( uniqid() ) );
		}

		// get the customer email.
		foreach ( $fields as $field ) {
			if ( 'email' === $field['type'] ) {
				$this->email = $field['value'];
				break;
			}
		}

		// Get the referral type.
		$this->referral_type = isset( $form_data['settings']['affwp_referral_type'] ) ? strval( $form_data['settings']['affwp_referral_type'] ) : 'sale';

		// Create draft referral.
		$referral_id = $this->insert_draft_referral(
			$this->affiliate_id,
			array(
				'reference' => $entry_id,
			)
		);
		if ( ! $referral_id ) {
			$this->log( 'Draft referral creation failed.' );
			return;
		}

		// prevent referral creation unless referrals enabled for the form.
		if ( ! isset( $form_data['settings']['affwp_allow_referrals'] ) || ! $form_data['settings']['affwp_allow_referrals'] ) {
			$this->log( 'Referral not created because referrals are not enabled on form.' );
			$this->mark_referral_failed( $referral_id );
			return;
		}

		// Customers cannot refer themselves.
		if ( $this->is_affiliate_email( $this->email, $affiliate_id ) ) {
			$this->log( 'Referral not created because affiliate\'s own account was used.' );
			$this->mark_referral_failed( $referral_id );
			return;
		}

		// get referral total.
		$total = 0;
		if ( function_exists( 'wpforms_get_total_payment' ) ) {
			$total = wpforms_get_total_payment( $fields );
		}
		$referral_total = $this->calculate_referral_amount( $total, $entry_id, absint( $form_data['id'] ) );

		// use form title as description.
		$description = $form_data['settings']['form_title'];

		// use products purchased as description.
		if ( $this->get_product_description( $fields ) ) {
			$description = $this->get_product_description( $fields );
		}

		// Hydrates the previously created referral.
		$this->hydrate_referral(
			$referral_id,
			array(
				'status'      => 'pending',
				'amount'      => $referral_total,
				'description' => $description,
			)
		);
		$this->log( sprintf( 'WPForms referral #%d updated to pending successfully.', $referral_id ) );

		// set the referral to "unpaid" if there's no total.
		if ( empty( $referral_total ) || 0 == $total ) {
			$this->complete_referral( $entry_id );
		}
	}

	/**
	 * Sets a referral to unpaid when payment is completed
	 *
	 * @access  public
	 *
	 * @since   2.0
	*/
	public function mark_referral_complete( $fields, $form_data, $entry_id, $data ) {
		$this->complete_referral( $entry_id );
	}

	/**
	 * Sets up the reference link in the Referrals table
	 *
	 * @access  public
	 *
	 * @since   2.0
	*/
	public function reference_link( $reference, $referral ) {

		if ( empty( $referral->context ) || 'wpforms' != $referral->context ) {
			return $reference;
		}

		if ( ! $reference || 32 == strlen( $reference ) ) {
			return '';
		}

		$url = admin_url( 'admin.php?page=wpforms-entries&view=details&entry_id=' . $reference );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

	/**
	 * Builds an array of all the products purchased in the form
	 *
	 * @access  public
	 *
	 * @since   2.0
	*/
	public function get_product_description( $fields = array() ) {

		$description = array();

		// get the customer email
		foreach ( $fields as $field ) {

			// single items
			if ( $field['type'] === 'payment-single' ) {
				$description[] = $field['name'];
			}

			// multiple items
			if ( $field['type'] === 'payment-multiple' ) {
				$description[] = $field['name'] . ' | ' . $field['value_choice'];
			}

		}

		return implode( ', ', $description );

	}

	/**
	 * Smart Tags.
	 *
	 * @access public
	 *
	 * @since 2.11.0
	 */
	public function smart_tags( $tags ) {
		$tags['site_name']               = esc_html__( 'Site Name', 'affiliate-wp' );
		$tags['affiliate_manager_email'] = esc_html__( 'Affiliate Manager(s) Email Address', 'affiliate-wp' );
		$tags['affiliate_password']      = esc_html__( 'Affiliate Password', 'affiliate-wp' );
		$tags['affiliate_login_url']     = esc_html__( 'Affiliate Login URL', 'affiliate-wp' );
		$tags['affiliate_user_login']    = esc_html__( 'Affiliate User Login', 'affiliate-wp' );

		return $tags;
	}

	/**
	 * Process the Smart Tags.
	 *
	 * @access public
	 *
	 * @since 2.11.0
	 */
	public function process_smart_tags( $content, $tag ) {

		// Site name.
		if ( 'site_name' === $tag ) {
			$content = str_replace( '{site_name}', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ), $content );
		}

		// Login URL to the affiliate area.
		if ( 'affiliate_login_url' === $tag ) {
			$affiliate_login_url = get_permalink( affiliatewp_get_affiliate_login_page_id() );
			$content = str_replace( '{affiliate_login_url}', $affiliate_login_url, $content );
		}

		// Affiliate's username.
		if ( 'affiliate_user_login' === $tag ) {
			$current_user = wp_get_current_user();
			$content = str_replace( '{affiliate_user_login}', $current_user->user_login, $content );
		}

		// Affiliate's password.
		if ( 'affiliate_password' === $tag ) {
			$content = str_replace( '{affiliate_password}', $this->get_password(), $content );
		}

		// Affiliate Manager(s) email.
		if ( 'affiliate_manager_email' === $tag ) {
			$email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );
			$content = str_replace( '{affiliate_manager_email}', $email, $content );
		}

		return $content;
	}

	/**
	 * AffiliateWP fields.
	 *
	 * @access private
	 *
	 * @since 2.11.0
	 *
	 * @return array The fields related to AffiliateWP.
	 */
	private function affwp_fields() {
		return array(
			'affwp_name',
			'affwp_email',
			'affwp_username',
			'affwp_promotion_method',
			'affwp_payment_email',
			'affwp_website',
		);
	}

	/**
	 * Excluded field IDs.
	 *
	 * @access private
	 *
	 * @since 2.11.0
	 *
	 * @param int $form_id The WPForms form ID.
	 * @param array $fields An array of specific fields to exclude.
	 */
	private function excluded_field_ids( $form_id, $fields = array() ) {

		/**
		 * Loop through the form settings and create an array of IDs, based on
		 * the affwp_fields. IDs are used to exclude data which AffiliateWP
		 * already outputs on the review affiliate page.
		 */
		$form      = wpforms()->get( 'form' )->get( (int) $form_id );
		$form_data = wpforms_decode( $form->post_content );
		$settings  = $form_data['settings'];
		$ids       = array();

		$affwp_fields = $this->affwp_fields();

		if ( ! empty( $fields ) ) {
			$affwp_fields = $fields;
		}

		if ( ! empty( $affwp_fields ) ) {
			foreach ( $affwp_fields as $field_name ) {
				if ( isset( $settings[ $field_name ] ) && '' !== $settings[ $field_name ] ) {
					$ids[] = $settings[ $field_name ];
				}
			}
		}

		$email_field = wpforms_get_form_fields_by_meta( 'nickname', 'email', $form_data );
		$email_field = reset( $email_field );

		if ( $email_field ) {
			$ids[] = $email_field['id'];
		}

		return $ids;
	}

	/**
	 * Disable fields on the affiliate registration form for a logged-in user.
	 * Fields that are disabled are the name, username, account email.
	 *
	 * The only way to disable fields on the frontend is to use JS.
	 *
	 * @access public
	 *
	 * @since 2.11.0
	 *
	 * @param array $forms Current forms on the page.
	 */
	public function disable_fields( $forms ) {

		if ( ! is_user_logged_in() ) {
			return;
		}

		$form_ids = array();

		foreach ( $forms as $form_data ) {

			if ( ! $this->is_affiliate_registration_enabled( $form_data ) ) {
				continue;
			}

			if ( isset( $form_data['settings'] ) && '1' === $form_data['settings']['affwp_affiliate_registration_enable'] ) {
				$form_ids[] = $form_data['id'];
			}
		}

		if ( ! empty( $form_ids ) ) {
			$container_classes = array();

			foreach ( $form_ids as $form_id ) {
				$disabled_field_ids = $this->excluded_field_ids( $form_id, array( 'affwp_name', 'affwp_username', 'affwp_email', 'affwp_password' ) );

				if ( ! empty( $disabled_field_ids ) ) {
					foreach ( $disabled_field_ids as $field_id ) {
						$container_classes[] = '#wpforms-' . $form_id . '-field_' . $field_id . '-container input';
					}
				}
			}

			// Create a string of container class names which will be disabled.
			$classes_to_disable = implode( ', ', $container_classes );

			?>
			<script type="text/javascript">
			jQuery(function($) {
				$( '<?php echo $classes_to_disable; ?>' ).attr({
					disabled: "disabled",
					tabindex: "-1"
				});

				$( '.wpforms-form' ).on( 'wpformsBeforeFormSubmit', function() {
					$( '<?php echo $classes_to_disable; ?>' ).removeAttr( 'disabled' );
				})
			});
			</script>
			<?php
		}
	}

	/**
	 * Checking if is Gutenberg REST API call.
	 *
	 * @since 2.11.0
	 *
	 * @return bool True if is Gutenberg REST API call.
	 */
	private function is_gb_editor() {
		return defined( 'REST_REQUEST' ) && REST_REQUEST && ! empty( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit';
	}

	/**
	 * Hide the affiliate registration form if the user is already an affiliate.
	 *
	 * @access public
	 *
	 * @since 2.11.0
	 *
	 * @param boolean $display Value to determine if form should be displayed.
	 * @param array  $form_data The information for the form.
	 */
	public function display_form( $bool, $form_data ) {

		if ( ! $this->is_affiliate_registration_enabled( $form_data ) ) {
			return $bool;
		}

		if ( $this->is_gb_editor() || ! is_user_logged_in() ) {
			return $bool;
		}

		if ( is_user_logged_in() && affwp_is_affiliate() ) {
			return false;
		}

		return $bool;
	}

	/**
	 * Check to see if WPForms Lite is active.
	 *
	 * @access private
	 *
	 * @since 2.11.0
	 *
	 * @return boolean True is WPForms Lite is active, false otherwise.
	 */
	private function is_wpforms_lite() {
		return class_exists( 'WPForms_Lite' );
	}

	/**
	 * Runs the check necessary to confirm this plugin is active.
	 *
	 * @since 2.5
	 *
	 * @return bool True if the plugin is active, false otherwise.
	 */
	function plugin_is_active() {
		return function_exists( 'wpforms' );
	}
}

	new Affiliate_WP_WPForms;
