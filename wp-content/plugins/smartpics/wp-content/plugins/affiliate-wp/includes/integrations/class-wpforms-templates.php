<?php
if ( class_exists( 'WPForms_Template', false ) ) :

	#[\AllowDynamicProperties]

	/**
	 * Affiliate Registration Template for WPForms.
	 */
	class WPForms_Template_Affiliate_Registration extends WPForms_Template {

		/**
		 * Primary class constructor.
		 *
		 * @since 2.11.0
		 */
		public function init() {

			// Template name
			$this->name = __( 'Affiliate Registration Form', 'affiliate-wp' );

			// Template slug
			$this->slug = 'affiliate_registration';

			// Template description
			$this->description = __( 'Create an affiliate registration form using AffiliateWP.', 'affiliate-wp' );

			// Template field and settings
			$this->data = array (
				'fields' => array (
					0 => array (
						'id' => '0',
						'type' => 'name',
						'label' => __( 'Your Name', 'affiliate-wp' ),
						'format' => 'first-last',
						'size' => 'medium',
						'first_default' => '{user_first_name}',
						'last_default' => '{user_last_name}',
					),
					7 => array (
						'id' => '7',
						'type' => 'text',
						'label' => __( 'Username', 'affiliate-wp' ),
						'required' => '1',
						'size' => 'medium',
						'limit_count' => '1',
						'limit_mode' => 'characters',
						'map_position' => 'above',
						'default_value' => '{affiliate_user_login}',
					),
					1 => array (
						'id' => '1',
						'type' => 'email',
						'label' => __( 'Account Email', 'affiliate-wp' ),
						'required' => '1',
						'size' => 'medium',
						'default_value' => '{user_email}',
					),
					4 => array (
						'id' => '4',
						'type' => 'email',
						'label' => __( 'Payment Email', 'affiliate-wp' ),
						'size' => 'medium',
						'default_value' => false,
					),
					10 => array (
						'id' => '10',
						'type' => class_exists( 'WPForms_Lite' ) ? 'text' : 'url',
						'label' => __( 'Website URL', 'affiliate-wp' ),
						'size' => 'medium',
					),
					8 => array (
						'id' => '8',
						'type' => 'textarea',
						'label' => __( 'How will you promote us?', 'affiliate-wp' ),
						'size' => 'medium',
						'limit_count' => '1',
						'limit_mode' => 'characters',
					),
				),
				'field_id' => 15,
				'settings' => array (
					'form_title' => __( 'Affiliate Registration', 'affiliate-wp' ),
					'submit_text' => __( 'Register', 'affiliate-wp' ),
					'submit_text_processing' => __( 'Registering...', 'affiliate-wp' ),
					'ajax_submit' => '1',
					'notification_enable' => '1',
					'notifications' => array (
						1 => array (
							'notification_name' => __( 'Affiliate Manager Notification', 'affiliate-wp' ),
							'email' => '{affiliate_manager_email}',
							'subject' => __( 'New Affiliate Registration on {site_name}', 'affiliate-wp' ),
							'sender_name' => get_bloginfo( 'name' ),
							'sender_address' => '{admin_email}',
							'message' => 'A new affiliate has registered on your site, {site_name}.

{all_fields}',
							'file_upload_attachment_fields' => array (
							),
							'entry_csv_attachment_entry_information' => array (
							),
							'entry_csv_attachment_file_name' => 'entry-details',
						),
					),
					'confirmations' => array (
						1 => array (
							'name' => __( 'Redirect to Affiliate Area', 'affiliate-wp' ),
							'type' => 'page',
							'page' => affiliate_wp()->settings->get( 'affiliates_page' ),
							'message_entry_preview_style' => 'basic',
						),
					),
					'affwp_referral_type' => 'lead',
					'affwp_affiliate_registration_enable' => '1',
					'affwp_name' => '0',
					'affwp_username' => '7',
					'affwp_email' => '1',
					'affwp_payment_email' => '4',
					'affwp_website' => '10',
					'affwp_promotion_method' => '8',
					'form_tags' => array (
					),
				),
				'meta' => array (
					'template' => 'affiliate_registration',
				),
			);
		}

	}
	new WPForms_Template_Affiliate_Registration();
	endif;
