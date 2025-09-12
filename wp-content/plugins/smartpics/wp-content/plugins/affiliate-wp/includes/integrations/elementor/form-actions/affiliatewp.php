<?php
/**
 * AffiliateWP Action After Submit for Elementor
 *
 * @package    AffiliateWP
 * @subpackage Integrations
 * @copyright  Copyright (c) 2023, Sandhills Development, LLC
 * @since      2.19.0
 */

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Integration_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor form AffiliateWP action.
 *
 * Custom Elementor form action which will register an affiliate after form submission.
 *
 * @since 2.19.0
 */
class AffiliateWP_Action_After_Submit extends Integration_Base {

	use Elementor_Shared_Utils;

	/**
	 * Temporary storage for password.
	 *
	 * @since 2.19.0
	 *
	 * @var string
	 */
	private static $password = '';

	/**
	 * Set user password.
	 *
	 * @since 2.19.0
	 *
	 * @param string $password Password.
	 */
	public static function set_password( $password ) : void {
		self::$password = $password;
	}

	/**
	 * Get user password.
	 *
	 * @since 2.19.0
	 *
	 * @return string
	 */
	public static function get_password() : string {

		if ( ! empty( self::$password ) ) {
			return self::$password;
		}

		$password = wp_generate_password( 18 );

		self::set_password( $password );

		return $password;
	}

	/**
	 * Get action name.
	 *
	 * Retrieve AffiliateWP action name.
	 *
	 * @since 2.19.0
	 * @access public
	 * @return string
	 */
	public function get_name() : string {
		return 'affiliatewp';
	}

	/**
	 * Get action label.
	 *
	 * Retrieve AffiliateWP action label.
	 *
	 * @since 2.19.0
	 * @access public
	 * @return string
	 */
	public function get_label() : string {
		return esc_html__( 'AffiliateWP', 'affiliate-wp' );
	}

	/**
	 * Run action.
	 *
	 * Register an affiliate after form submission.
	 *
	 * @since 2.19.0
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record       Form record data.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler AJAX handler for the form.
	 */
	public function run( $record, $ajax_handler ) : void {
		if ( ! $this->is_affiliate_registration( $this->get_affiliate_registration_setting( $record ) ) ) {
			return;
		}

		// Get all submitted fields.
		$all_fields = $record->get( 'fields' );

		// Get data from mapped fields.
		$mapped_fields = $this->get_fields_map( $record, 'affiliatewp_fields_map' );

		// Get the data sent.
		$sent_data = $record->get( 'sent_data' );

		$user_data = $this->form_data(
			$record,
			array(
				'user_login',
				'user_pass',
				'user_email',
				'user_url',
				'payment_email',
				'promotion_method',
			),
			'affiliatewp_fields_map'
		);

		// Fallback to user_email if user_login is not mapped or empty
		if ( empty( $user_data['user_login'] ) && ! empty( $user_data['user_email'] ) ) {
			$user_data['user_login'] = $user_data['user_email'];
		}

		$random_pass = false;

		if ( empty( $user_data['user_pass'] ) ) {
			$user_data['user_pass'] = $this->get_password(); // Autogenerate password.
			$random_pass = true;
		}

		$name = ! empty( $sent_data[ $mapped_fields['name'] ] ) ? $sent_data[ $mapped_fields['name'] ] : '';

		if ( ! empty( $name ) ) {
			$name                    = explode( ' ', sanitize_text_field( $name ) );
			$user_data['first_name'] = array_shift( $name );
			$user_data['last_name']  = count( $name ) ? implode( ' ', $name ) : '';

		} else {
			$user_data['first_name'] = '';
			$user_data['last_name']  = '';
		}

		$user_data['display_name'] = trim( "{$user_data['first_name']} {$user_data['last_name']}" );

		$new_user = is_user_logged_in() ? false : true;
		$user_id  = is_user_logged_in() ? get_current_user_id() : wp_insert_user( $user_data );

		if ( $random_pass ) {
			// Remember that we generated the password for the user.
			update_user_meta( $user_id, 'affwp_generated_pass', true );
		}

		// Update first and last name.
		wp_update_user( array(
			'ID'         => $user_id,
			'first_name' => $user_data['first_name'],
			'last_name'  => $user_data['last_name']
		) );

		// Affiliate Status.
		$status = affiliate_wp()->settings->get( 'require_approval' ) ? 'pending' : 'active';

		affwp_add_affiliate( array(
			'user_id'             => $user_id,
			'payment_email'       => ! empty( $user_data['payment_email'] ) ? sanitize_text_field( $user_data['payment_email'] ) : '',
			'status'              => $status,
			'website_url'         => ! empty( $user_data['user_url'] ) ? sanitize_text_field( $user_data['user_url'] ) : '',
			'registration_method' => 'affiliate_registration_form_elementor',
			'registration_url'    => esc_url_raw( get_permalink( $record->get_form_settings( 'form_post_id' ) ?? null ) ),
		) );

		// Log user if is they aren't already.
		if ( ! is_user_logged_in() ) {
			$this->log_user_in( $user_id, $user_data['user_login'] );
		}

		// Retrieve affiliate ID. Resolves issues with caching on some hosts, such as GoDaddy.
		$affiliate_id = affwp_get_affiliate_id( $user_id );

		if ( true === $new_user ) {
			// Enable referral notifications by default for new users.
			affwp_update_affiliate_meta( $affiliate_id, 'referral_notifications', true );
		}

		// Promotion Method.
		if ( ! empty( $user_data['promotion_method'] ) ) {
			// Update affiliate meta with Promotion Method.
			affwp_update_affiliate_meta( $affiliate_id, 'promotion_method', sanitize_text_field( $user_data['promotion_method'] ), true );
		}

		// Initialize an array to hold all custom field data.
		$elementor_affiliate_registration_data = array();

		// Identify unmapped fields and store their values.
		foreach ( $all_fields as $field_id => $field_data ) {
			if ( in_array( $field_id, $mapped_fields ) ) {
				continue;
			}

			// Prepare the data for this field.
			$field_info = array(
				'title' => $field_data['title'] ?? $field_id,
				'value' => $field_data['value'] ?? '',
				'type'  => $field_data['type'] ?? 'unknown'
			);

			// Add this field's info to the main array.
			$elementor_affiliate_registration_data[$field_id] = $field_info;
		}

		// Save to affiliate meta.
		affwp_update_affiliate_meta( $affiliate_id, 'elementor_affiliate_registration_data', $elementor_affiliate_registration_data );

		/**
		 * Fires immediately after registering a user.
		 *
		 * @since 2.19.0
		 *
		 * @param int    $affiliate_id Affiliate ID.
		 * @param string $status       Affiliate status.
		 * @param array  $user_data    User data.
		 */
		do_action( 'affwp_register_user', $affiliate_id, $status, $user_data );

		// Check if a redirect URL is already set in the form settings.
		$redirect_to = $record->get_form_settings( 'redirect_to' );

		// If no custom redirect URL, get the Affiliate Area page URL.
		if ( empty( $redirect_to ) ) {
			$affiliate_area_page_id = affwp_get_affiliate_area_page_id();
			if ( ! empty( $affiliate_area_page_id ) ) {
				$redirect_to = get_permalink( $affiliate_area_page_id );
			}
		}

		$redirect_to = esc_url_raw( $redirect_to );

		if ( ! empty( $redirect_to ) && filter_var( $redirect_to, FILTER_VALIDATE_URL ) ) {
			$ajax_handler->add_response_data( 'redirect_url', $redirect_to );
		}

	}

	/**
	 * Logs the user in.
	 *
	 * @access private
	 *
	 * @since 2.19.0
	 *
	 * @param  $user_id    The user ID.
	 * @param  $user_login The `user_login` for the user.
	 * @param  $remember   Whether or not the browser should remember the user login.
	 */
	private function log_user_in( $user_id = 0, $user_login = '', $remember = false ) : void {

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

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
	 * Register action controls.
	 *
	 * AffiliateWP action has no input fields to the form widget.
	 *
	 * @since 2.19.0
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) : void {

		$widget->start_controls_section(
			'section_affiliatewp',
			array(
				'label'     => esc_html__( 'AffiliateWP', 'affiliate-wp' ),
				'condition' => array(
					'submit_actions' => $this->get_name(),
				),
			)
		);

		$widget->add_control(
			'affwp_enable_referrals',
			array(
				'label'        => esc_html__( 'Enable Referrals', 'affiliate-wp' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$options = array();
		foreach ( affwp_get_referral_types() as $type_id => $type ) {
			$options[ $type_id ] = $type['label'];
		}

		// Sale referrals are not supported.
		unset( $options['sale'] );

		$widget->add_control(
			'affwp_referral_type',
			array(
				'label'     => esc_html__( 'Referral Type', 'affiliate-wp' ),
				'type'      => \Elementor\Controls_Manager::SELECT2,
				'options'   => $options,
				'default'   => 'lead',
				'condition' => array(
					'affwp_enable_referrals' => 'yes',
				),
			)
		);

		$widget->add_control(
			'affwp_referral_status',
			array(
				'label'     => esc_html__( 'Referral Status', 'affiliate-wp' ),
				'type'      => \Elementor\Controls_Manager::SELECT2,
				'options'   => array(
					'pending' => esc_html__( 'Pending', 'affiliate-wp' ),
					'unpaid'  => esc_html__( 'Unpaid', 'affiliate-wp' ),
				),
				'default'   => 'pending',
				'condition' => array(
					'affwp_enable_referrals' => 'yes',
				),
			)
		);

		$widget->add_control(
			'affwp_referral_amount',
			array(
				'label'     => esc_html__( 'Referral Amount', 'affiliate-wp' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 0,
				'step'      => 0.1,
				'default'   => 0,
				'condition' => array(
					'affwp_enable_referrals' => 'yes',
				),
			)
		);

		$referral_fields_repeater = new \Elementor\Repeater();

		$referral_fields_repeater->add_control(
			'remote_id', array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => ''
			)
		);

		$referral_fields_repeater->add_control(
			'local_id', array(
				'type'    => Controls_Manager::SELECT,
				'default' => ''
			)
		);

		$widget->add_control(
			'affiliatewp_referral_fields_map',
			array(
				'label'       => esc_html__( 'Field Mapping', 'affiliate-wp' ),
				'type'        => Referral_Field_Mapping::CONTROL_TYPE,
				'separator'   => 'before',
				'render_type' => 'none',
				'fields'      => $referral_fields_repeater->get_controls(),
				'condition'   => array(
					'affwp_enable_referrals' => 'yes',
				),
			)
		);

		$widget->add_control(
			'affiliate_registration',
			array(
				'label'        => esc_html__( 'Enable Affiliate Registration', 'affiliate-wp' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$registration_fields_repeater = new \Elementor\Repeater();

		$registration_fields_repeater->add_control(
			'remote_id', array(
				'type'    => Controls_Manager::HIDDEN,
				'default' => ''
			)
		);

		$registration_fields_repeater->add_control(
			'local_id', array(
				'type'    => Controls_Manager::SELECT,
				'default' => ''
			)
		);

		$widget->add_control(
			'affiliatewp_fields_map',
			array(
				'label'       => esc_html__( 'Field Mapping', 'affiliate-wp' ),
				'type'        => Field_Mapping::CONTROL_TYPE,
				'separator'   => 'before',
				'render_type' => 'none',
				'fields'      => $registration_fields_repeater->get_controls(),
				'condition'   => array(
					'affiliate_registration' => 'yes',
				),
			)
		);

		$widget->end_controls_section();
	}

	/**
	 * On export.
	 *
	 * AffiliateWP action has no fields to clear when exporting.
	 *
	 * @since 2.19.0
	 * @access public
	 * @param array $element
	 */
	public function on_export( $element ) : void {}

}
