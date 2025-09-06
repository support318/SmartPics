<?php

use AcVendor\GuzzleHttp\Promise\Is;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

GFForms::include_feed_addon_framework();

class WPF_GForms_Integration extends GFFeedAddOn {

	protected $_version                  = WP_FUSION_VERSION;
	protected $_min_gravityforms_version = '1.7.9999';
	protected $_slug                     = 'wpfgforms';
	protected $_full_path                = __FILE__;
	protected $_title                    = 'CRM Integration';
	protected $_short_title              = 'WP Fusion';
	protected $postvars                  = array();

	protected $_capabilities_settings_page = array( 'manage_options' );
	protected $_capabilities_form_settings = array( 'manage_options' );
	protected $_capabilities_plugin_page   = array( 'manage_options' );
	protected $_capabilities_app_menu      = array( 'manage_options' );
	protected $_capabilities_app_settings  = array( 'manage_options' );
	protected $_capabilities_uninstall     = array( 'manage_options' );

	protected $setting_key;

	/**
	 * Whether the form has payments.
	 *
	 * @since 3.44.22
	 * @var null|bool $has_payments
	 */
	protected $has_payments = null;

	/**
	 * The slug name for WP Fusion's module tracking.
	 *
	 * @since 3.36.5
	 * @var slug
	 */

	public $slug = 'gravity-forms';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.40.49
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/lead-generation/gravity-forms/';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.40.49
	 * @var string $name
	 */
	public $name = 'Gravity Forms';

	/**
	 * Get things running.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		parent::init();

		// Need ours to run before GForms User Registration and Event Tracking (on priority 10).
		// 9 so it's after GF_Field_Unique_ID::populate_field_value() on 9.
		remove_filter( 'gform_entry_post_save', array( $this, 'maybe_process_feed' ), 10, 2 );
		add_filter( 'gform_entry_post_save', array( $this, 'maybe_process_feed' ), 9, 2 );

		// Batch operations.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_gravity_forms_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_gravity_forms', array( $this, 'batch_step' ) );

		// Payments.
		add_action( 'gform_post_payment_action', array( $this, 'payment_completed' ), 10, 2 );

		// User registration.
		add_filter( 'wpf_user_register', array( $this, 'maybe_bypass_user_register' ) );
		add_action( 'gform_user_registered', array( $this, 'user_registered' ), 20, 4 ); // 20 so it runs after the BuddyPress actions in GF_User_Registration.
		add_action( 'gform_user_updated', array( $this, 'user_updated' ), 10, 4 );
		add_filter( 'gform_user_registration_update_user_id', array( $this, 'update_user_id' ) );

		// Add custom column to entry list.
		add_filter( 'gform_entry_list_columns', array( $this, 'add_entry_status_column' ) );
		add_filter( 'gform_entries_column_filter', array( $this, 'entry_status_column_content' ), 10, 5 );

		// Merge tag.
		add_action( 'gform_admin_pre_render', array( $this, 'add_merge_tags' ) );
		add_filter( 'gform_replace_merge_tags', array( $this, 'replace_merge_tags' ), 10, 7 );
		add_action( 'gform_form_args', array( $this, 'maybe_pre_fill_forms' ) );

		// Meta box.
		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'register_meta_box' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'maybe_process_entry' ) );

		// Conditional logic.
		add_action( 'admin_footer', array( $this, 'admin_conditional_operators' ) );
		add_action( 'gform_register_init_scripts', array( $this, 'frontend_conditional_operators' ) );
		add_filter( 'gform_is_valid_conditional_logic_operator', array( $this, 'valid_conditional_logic' ), 10, 2 );
		add_filter( 'gform_is_value_match', array( $this, 'validate_conditional_logic' ), 10, 6 );

		if ( version_compare( GFCommon::$version, '2.5' ) >= 0 ) {
			$this->setting_key = '_gform_setting'; // 2.5 and up.
		} else {
			$this->setting_key = '_gaddon_setting';
		}

		if ( class_exists( 'GP_Nested_Forms' ) ) {
			add_action( 'gform_after_submission', array( $this, 'process_nested_forms' ), 10, 2 );
		}
	}

	/**
	 * Triggered when form is submitted
	 *
	 * @access  public
	 * @return  void
	 */
	public function process_feed( $feed, $entry, $form ) {

		// Allows skipping feed processing for specific forms.
		if ( apply_filters( 'wpf_gforms_skip_feed_processing', false, $feed, $entry, $form ) ) {
			return;
		}

		gform_update_meta( $entry['id'], 'wpf_complete', false );

		$update_data = array();

		// Check payment status.
		if ( isset( $feed['meta']['payment_status'] ) && 'always' != $feed['meta']['payment_status'] ) {

			$paid_statuses = array( 'Paid', 'Approved', 'Active' );

			if ( 'paid_only' == $feed['meta']['payment_status'] ) {

				if ( empty( $entry['payment_status'] ) || ! in_array( $entry['payment_status'], $paid_statuses ) ) {
					// Form is set to Paid Only and payment status is not paid
					return;
				}
			} elseif ( 'fail_only' == $feed['meta']['payment_status'] && 'Processing' != $entry['payment_status'] ) {

				if ( ! empty( $entry['payment_status'] ) && in_array( $entry['payment_status'], $paid_statuses ) ) {
					// Form is set to Fail Only and payment status is not failed
					return;

				}
			}
		}

		// PDFs.

		if ( class_exists( 'GPDFAPI' ) ) {

			$model_pdf = \GPDFAPI::get_mvc_class( 'Model_PDF' );
			$pdfs      = $model_pdf->get_pdf_display_list( $entry );

			foreach ( $pdfs as $pdf ) {
				$entry[ $pdf['settings']['id'] ] = $pdf['view'];
			}
		}

		// Get the field mapping using the generic_map helper.

		$field_mapping = array();

		if ( empty( $feed['meta']['wpf_field_mapping'] ) && ! empty( $feed['meta']['wpf_fields'] ) ) {

			// Check for original field mapping.

			foreach ( $feed['meta']['wpf_fields'] as $field_id => $data ) {
				$field_mapping[ $data['crm_field'] ] = str_replace( '-', '.', $field_id );
			}
		} elseif ( ! empty( $feed['meta']['wpf_field_mapping'] ) ) {

			// Check for new field mapping.

			foreach ( $feed['meta']['wpf_field_mapping'] as $field_id => $data ) {

				if ( empty( $data['custom_value'] ) ) {
					$field_mapping[ $data['key'] ] = $data['value'];
				} elseif ( 'gf_custom' === $data['value'] ) {
					$field_mapping[ $data['key'] ] = $data['custom_value']; // custom text values.
				} elseif ( isset( $data['custom_key'] ) && ! is_numeric( $data['value'] ) ) {
					$field_mapping[ $data['key'] ] = '{' . $data['value'] . '}'; // merge tags.
				}
			}
		}

		// We'll determine the email address from the submitted data.
		$email_address = false;

		if ( ! empty( $field_mapping ) ) {

			foreach ( $field_mapping as $crm_field => $source_field_or_tag ) {

				$value = null;
				$type  = 'text'; // Default type.

				// Check if it's a merge tag.
				if ( false !== strpos( $source_field_or_tag, '{' ) ) {
					$value = GFCommon::replace_variables( $source_field_or_tag, $form, $entry );

					// Infer type for known merge tags.
					if ( false !== strpos( $source_field_or_tag, 'date_' ) ) {
						$type = 'date';
					}
				} else {
					// Assume it's a form field ID (potentially with subfield like 1.3).
					$field_id_parts = explode( '.', $source_field_or_tag );
					$main_field_id  = $field_id_parts[0];

					if ( isset( $entry[ $source_field_or_tag ] ) ) {
						$value = $entry[ $source_field_or_tag ];
						$field = GFAPI::get_field( $form, $main_field_id );

						if ( $field ) {
							$type = $field->type;

							// Adjust type for specific cases.
							if ( 'image_hopper' === $type ) {
								$type = 'multiselect';
							}

							// Check for labels sync.
							if ( ! empty( $feed['meta']['sync_labels'] ) ) {
								$value = $field->get_value_export( $entry, $source_field_or_tag, true );
							}
						}
					}
				}

				// Handle specific value formatting before general formatting.
				if ( ( 'multiselect' === $type || 'checkbox' === $type ) && is_string( $value ) && 0 === strpos( $value, '[' ) ) {
					// Convert multiselects/checkboxes string format to array.
					$value = str_replace( '"', '', $value );
					$value = str_replace( '[', '', $value );
					$value = str_replace( ']', '', $value );
					$value = preg_replace( "/\\\\u([0-9abcdef]{4})/", '&#x$1;', $value );
					$value = mb_convert_encoding( $value, 'UTF-8', 'HTML-ENTITIES' );
					$value = explode( ',', $value );
					$value = wp_unslash( $value );
				} elseif ( ( 'multiselect' === $type || 'checkbox' === $type ) && is_array( $value ) ) {
					// Ensure associative arrays are converted to indexed arrays.
					$value = array_values( $value );
				}

				// Apply dynamic tagging if the key is 'add_tags'.
				if ( 'add_tags' === $crm_field && ! empty( $value ) ) {
					// Assume value is a comma-separated string or an array of tags.
					if ( is_string( $value ) ) {
						$tags_to_apply = array_map( 'trim', explode( ',', $value ) );
					} elseif ( is_array( $value ) ) {
						$tags_to_apply = array_map( 'trim', $value );
					}
					// Merge with statically applied tags.
					if ( isset( $feed['meta']['wpf_tags'] ) ) {
						$feed['meta']['wpf_tags'] = array_unique( array_merge( (array) $feed['meta']['wpf_tags'], $tags_to_apply ) );
					}
					continue; // Don't add to update_data.
				}

				// Format the value for the CRM.
				$formatted_value = apply_filters( 'wpf_format_field_value', $value, $type, $crm_field );

				if ( ! empty( $formatted_value ) || 0 === $formatted_value || '0' === $formatted_value ) {

					if ( 'fileupload' === $type ) {
						$formatted_value = stripslashes( $formatted_value );
					}

					$update_data[ $crm_field ] = $formatted_value;

					// Determine email address.
					if ( is_string( $formatted_value ) && is_email( $formatted_value ) && wpf_get_lookup_field() === $crm_field ) {
						$email_address = $formatted_value;
					} elseif ( false === $email_address && 'email' === $type && is_string( $formatted_value ) && is_email( $formatted_value ) ) {
						$email_address = $formatted_value;
					}
				}
			}
		}

		if ( ! isset( $feed['meta']['wpf_tags'] ) ) {
			$feed['meta']['wpf_tags'] = array();
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => (array) $feed['meta']['wpf_tags'],
			'apply_lists'      => isset( $feed['meta']['wpf_lists'] ) ? $feed['meta']['wpf_lists'] : array(),
			'auto_login'       => ! empty( $feed['meta']['auto_login'] ),
			'integration_slug' => 'gform',
			'integration_name' => 'Gravity Forms',
			'form_id'          => $form['id'],
			'entry_id'         => $entry['id'],
			'form_title'       => $form['title'],
			'form_edit_link'   => admin_url( 'admin.php?page=gf_edit_forms&id=' . $form['id'] ),
		);

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

		if ( is_wp_error( $contact_id ) ) {

			$this->add_feed_error( $contact_id->get_error_message(), $feed, $entry, $form );

		} else {

			gform_update_meta( $entry['id'], 'wpf_complete', current_time( 'Y-m-d H:i:s' ) );

			gform_update_meta( $entry['id'], 'wpf_contact_id', $contact_id );

			// Note: can't link to contact ID here because GForms does an esc_html() on the note display.

			$this->add_note( $entry['id'], 'Entry synced to ' . wp_fusion()->crm->name . ' (contact ID #' . $contact_id . ')' );

		}

		// Return after login + auto login

		if ( isset( $_COOKIE['wpf_return_to'] ) && doing_wpf_auto_login() ) {

			$post_id = absint( $_COOKIE['wpf_return_to'] );
			$url     = get_permalink( $post_id );

			setcookie( 'wpf_return_to', '', time() - ( 15 * 60 ) );

			if ( ! empty( $url ) && wpf_user_can_access( $post_id ) ) {

				add_filter(
					'gform_confirmation',
					function ( $confirmation, $form, $entry ) use ( &$url ) {

						$confirmation = array( 'redirect' => $url );

						return $confirmation;
					},
					10,
					3
				);

			}
		}

		do_action( 'wpf_gforms_feed_complete', $feed, $entry, $form, $contact_id, $email_address );
	}

	/**
	 * Triggered when a payment is completed. Triggers any feeds that are set
	 * to run only on a successful payment.
	 *
	 * @since 3.38.43
	 *
	 * @see  GFPaymentAddOn::complete_payment()
	 * @link https://docs.gravityforms.com/gform_post_payment_completed/
	 *
	 * @param array $entry  The entry.
	 * @param array $action The Action Object
	 * $action = array(
	 *     'type' => 'cancel_subscription',   // See Below
	 *     'transaction_id' => '',            // What is the ID of the transaction made?
	 *     'subscription_id' => '',           // What is the ID of the Subscription made?
	 *     'amount' => '0.00',                // Amount to charge?
	 *     'entry_id' => 1,                   // What entry to check?
	 *     'transaction_type' => '',
	 *     'payment_status' => '',
	 *     'note' => ''
	 * );
	 *
	 * 'type' can be:
	 *
	 * - complete_payment
	 * - refund_payment
	 * - fail_payment
	 * - add_pending_payment
	 * - void_authorization
	 * - create_subscription
	 * - cancel_subscription
	 * - expire_subscription
	 * - add_subscription_payment
	 * - fail_subscription_payment.
	 */
	public function payment_completed( $entry, $action ) {

		if ( 'complete_payment' === $action['type'] || 'create_subscription' === $action['type'] ) {

			$feeds = $this->get_active_feeds( $entry['form_id'] );
			$form  = GFAPI::get_form( $entry['form_id'] );

			foreach ( $feeds as $feed ) {

				if ( isset( $feed['meta']['payment_status'] ) && 'always' !== $feed['meta']['payment_status'] ) {

					if ( $this->is_feed_condition_met( $feed, $form, $entry ) ) {
						$this->process_feed( $feed, $entry, $form );
					}
				}
			}
		}
	}

	/**
	 * Process nested forms (Gravity Perks Nested Forms addon).
	 *
	 * @since 3.37.21
	 * @since 3.37.29 Moved to gform_after_submission hook.
	 *
	 * @param array $entry  The entry.
	 * @param array $form   The form.
	 */
	public function process_nested_forms( $entry, $form ) {

		if ( gp_nested_forms()->is_nested_form_submission() || ! gp_nested_forms()->has_nested_form_field( $form ) ) {
			return;
		}

		$_entry        = new GPNF_Entry( $entry );
		$child_entries = $_entry->get_child_entries();
		foreach ( $child_entries as $child_entry ) {

			$form  = gp_nested_forms()->get_nested_form( $child_entry['form_id'] );
			$feeds = GFAPI::get_feeds( null, $form['id'], 'wpfgforms' );

			foreach ( $feeds as $feed ) {
				if ( $this->is_feed_condition_met( $feed, $form, $child_entry ) ) {
					$this->process_feed( $feed, $child_entry, $form );
				}
			}
		}
	}


	/**
	 * Renders tag multi select field
	 *
	 * @access  public
	 * @return  void
	 */
	public function settings_wpf_tags( $field ) {

		wpf_render_tag_multiselect(
			array(
				'setting'   => $this->get_setting( $field['name'] ),
				'meta_name' => $this->setting_key . '_' . $field['name'],
			)
		);
	}

	/**
	 * Renders tag multi select field
	 *
	 * @access  public
	 * @return  void
	 */
	public function settings_wpf_lists( $field ) {

		echo '<select multiple id="' . $this->setting_key . '_wpf_lists" class="select4" name="' . $this->setting_key . '_wpf_lists[]" data-placeholder="Select lists" tabindex="-1" aria-hidden="true">';

		$lists     = wpf_get_option( 'available_lists', array() );
		$selection = $this->get_setting( $field['name'] );

		if ( empty( $selection ) ) {
			$selection = array();
		} elseif ( ! is_array( $selection ) ) {
			$selection = array( $selection );
		}

		foreach ( $lists as $list_id => $label ) {
			echo '<option ' . selected( true, in_array( $list_id, $selection ), false ) . ' value="' . $list_id . '">' . $label . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Checks if a form has payments.
	 *
	 * @since  3.44.22
	 *
	 * @param  int $form_id The form ID.
	 * @return bool True if the form has payments, false otherwise.
	 */
	public function form_has_payments( $form_id ) {

		if ( ! is_null( $this->has_payments ) ) {
			return $this->has_payments;
		}

		foreach ( GFAPI::get_feeds( null, $form_id ) as $feed ) {

			if ( isset( $feed['addon_slug'] ) ) {
				if ( in_array( $feed['addon_slug'], array( 'gravityformsstripe', 'gravityformspaypal', 'gravityformsppcp', 'gs-product-configurator' ) ) ) {
					return true;
					break;
				}
			}
		}

		return false;
	}

	/**
	 * Defines settings for the feed
	 *
	 * @access  public
	 * @return  array Feed settings
	 */
	public function feed_settings_fields() {

		$fields = array(
			array(
				'title'       => esc_html__( 'Feed Settings', 'wp-fusion' ),
				'description' => '',
				'fields'      => array(
					array(
						'label'   => __( 'Feed Name', 'wp-fusion' ),
						'type'    => 'text',
						'name'    => 'feedName',
						'tooltip' => __( 'Enter a name to remember this feed by.', 'wp-fusion' ),
						'class'   => 'small',
					),
				),
			),
			array(
				'title'       => esc_html__( 'Field Mapping', 'wp-fusion' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'        => 'wpf_field_mapping',
						'label'       => esc_html__( 'Map Fields', 'wp-fusion' ),
						'type'        => 'dynamic_field_map',
						'key_field'   => array(
							'title'            => esc_html__( 'CRM Field', 'wp-fusion' ),
							'choices'          => $this->get_crm_fields(),
							'allow_custom'     => false, // Users must select a CRM field.
							'allow_duplicates' => true, // Allow mapping multiple GF fields to the same CRM field.
						),
						'value_field' => array(
							'title'        => esc_html__( 'Form Field / Value', 'wp-fusion' ),
							'allow_custom' => true, // Allow custom merge tags or text.
							'placeholder'  => esc_html__( 'Select a field or merge tag', 'wp-fusion' ),
							// 'choices' is intentionally omitted to let GF populate form fields/merge tags.
						),
						'value'       => $this->get_current_mapping_value(), // Load current or transformed old settings.
					),
				),
			),
		);

		// See if we need to show the Sync Labels setting.

		$options_found = false;

		foreach ( $this->get_current_form()['fields'] as $field ) {

			$types = array( 'checkbox', 'checkboxes', 'select', 'radio', 'multiselect' );

			if ( in_array( $field['type'], $types ) ) {
				$options_found = true;
				break;
			}
		}

		if ( $options_found ) {

			$fields[1]['fields'][] = array(
				'type'    => 'checkbox',
				'name'    => 'sync_labels',
				'label'   => __( 'Sync Labels', 'wp-fusion' ),
				'tooltip' => __( 'By default WP Fusion syncs the values of selected checkboxes, radios, and dropdowns. Enable this setting to sync the option labels instead.', 'wp-fusion' ),
				'choices' => array(
					array(
						'label' => __( 'Sync option labels instead of values', 'wp-fusion' ),
						'name'  => 'sync_labels',
					),
				),
			);

		}

		$fields[2] = array(
			'title'  => esc_html__( 'Additional Options', 'wp-fusion' ),
			'fields' => array(
				array(
					'name'    => 'wpf_tags',
					'label'   => __( 'Apply Tags', 'wp-fusion' ),
					'type'    => 'wpf_tags',
					'tooltip' => sprintf( __( 'Select tags to be applied in %s when this form is submitted.', 'wp-fusion' ), wp_fusion()->crm->name ),
				),
			),
		);

		if ( in_array( 'lists', wp_fusion()->crm->supports ) ) {

			$fields[2]['fields'][] = array(
				'name'    => 'wpf_lists',
				'label'   => 'Add to Lists',
				'type'    => 'wpf_lists',
				'tooltip' => sprintf( __( 'Select %s lists to add new contacts to.', 'wp-fusion' ), wp_fusion()->crm->name ),
			);
		}

		$fields[2]['fields'][] = array(
			'type'    => 'checkbox',
			'name'    => 'auto_login',
			'label'   => __( 'Auto Login', 'wp-fusion' ),
			'tooltip' => sprintf( __( 'Auto-login allows you track user activity and unlock site content based on a contact\'s tags in %s, without them needing a real user account on the site.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'choices' => array(
				array(
					'label' => __( 'Start an auto-login session when this form is submitted', 'wp-fusion' ),
					'name'  => 'auto_login',
				),
			),
		);

		$fields[3] = array(
			'title'  => esc_html__( 'Feed Conditions', 'wp-fusion' ),
			'fields' => array(),
		);

		$has_payments = $this->form_has_payments( $_GET['id'] );

		if ( $has_payments ) {

			$fields[3]['fields'][] = array(
				'name'          => 'payment_status',
				'label'         => 'Payment Status',
				'type'          => 'radio',
				'default_value' => 'always',
				'choices'       => array(
					array(
						'label' => esc_html__( 'Process this feed regardless of payment status', 'wp-fusion' ),
						'value' => 'always',
					),
					array(
						'label' => esc_html__( 'Process this feed only if the payment is successful', 'wp-fusion' ),
						'value' => 'paid_only',
					),
					array(
						'label' => esc_html__( 'Process this feed only if the payment fails', 'wp-fusion' ),
						'value' => 'fail_only',
					),
				),
			);

		}

		$fields[3]['fields'][] = array(
			'type'           => 'feed_condition',
			'name'           => 'condition',
			'label'          => esc_html__( 'Opt-In Condition', 'wp-fusion' ),
			'checkbox_label' => esc_html__( 'Enable Condition', 'wp-fusion' ),
			'instructions'   => esc_html__( 'Process this feed if', 'wp-fusion' ),
		);

		return apply_filters( 'wpf_gform_settings_fields', $fields, $has_payments );
	}

	/**
	 * Gets the CRM fields for the field map.
	 *
	 * @since 3.45.8
	 *
	 * @return array The CRM fields.
	 */
	public function get_crm_fields() {

		$crm_fields = wp_fusion()->settings->get( 'crm_fields', array() );

		$return = array();

		if ( is_array( reset( $crm_fields ) ) ) {

			foreach ( $crm_fields as $category => $fields ) {

				$choices = array();

				foreach ( $fields as $id => $label ) {

					if ( ! is_array( $label ) ) {
						$choices[] = array(
							'label' => $label,
							'value' => $id,
						);
					} else {
						// Infusionsoft and others that use the newer format including types.
						$choices[] = array(
							'label' => $label['crm_label'],
							'value' => $id,
						);
					}
				}

				$return[] = array(
					'label'   => $category,
					'choices' => $choices,
				);
			}
		} else {
			foreach ( $crm_fields as $id => $label ) {
				$return[] = array(
					'label' => $label,
					'value' => $id,
				);
			}
		}

		if ( wp_fusion()->crm->supports( 'add_tags' ) ) {
			$return[] = array(
				'label' => __( 'Create tag(s) from value', 'wp-fusion' ),
				'value' => 'add_tags',
			);
		}

		return $return;
	}

	/**
	 * Gets the current field mapping settings, transforming old settings if necessary.
	 *
	 * @since 3.45.8
	 *
	 * @return array The value array for the generic_map field.
	 */
	private function get_current_mapping_value() {
		$setting = $this->get_setting( 'wpf_field_mapping' );

		// If new setting exists, return it.
		if ( ! empty( $setting ) ) {
			return $setting;
		}

		$old_settings = $this->get_setting( 'wpf_fields' );

		// Check for old settings and transform them.
		if ( is_array( $old_settings ) ) {
			$transformed_mapping = array();
			foreach ( $old_settings as $form_field_key => $data ) {
				if ( ! empty( $data['crm_field'] ) ) {
					// The form field key might contain dashes for subfields (e.g., 1-3) or be a merge tag like {user_ip}.
					$value                 = str_replace( '-', '.', $form_field_key ); // Convert dashes back to dots for subfields.
					$transformed_mapping[] = array(
						'key'   => $data['crm_field'],
						'value' => $value,
					);
				}
			}

			return $transformed_mapping;
		}

		// No settings found.
		return array();
	}

	/**
	 * Creates columns for feed
	 *
	 * @access  public
	 * @return  array Feed settings
	 */
	public function feed_list_columns() {
		return array(
			'feedName' => __( 'Name', 'wp-fusion' ),
			'gftags'   => __( 'Applies Tags', 'wp-fusion' ),
		);
	}

	/**
	 * Override this function to allow the feed to being duplicated.
	 *
	 * @access public
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 * @return boolean|true
	 */
	public function can_duplicate_feed( $id ) {
		return true;
	}

	/**
	 * Displays tags in custom column
	 *
	 * @access  public
	 * @return  string Configured tags
	 */
	public function get_column_value_gftags( $feed ) {

		$tags = rgars( $feed, 'meta/wpf_tags' );

		if ( empty( $tags ) ) {
			return '<em>-none-</em>';
		}

		$tag_labels = array();
		foreach ( (array) $tags as $tag ) {
			$tag_labels[] = wp_fusion()->user->get_tag_label( $tag );
		}

		return '<b>' . implode( ', ', $tag_labels ) . '</b>';
	}

	/**
	 * Add custom column to entry list
	 *
	 * @since  3.41.46
	 *
	 * @param array $columns The columns.
	 * @return array The columns.
	 */
	public function add_entry_status_column( $columns ) {

		$custom_column = '<i class="icon-wp-fusion wpf-tip wpf-tip-bottom" style="left: -2px" data-tip="' . esc_attr__( 'WP Fusion Status', 'wp-fusion' ) . '"></i>';

		$columns = wp_fusion()->settings->insert_setting_before( 'column_selector', $columns, 'wp_fusion', $custom_column );

		return $columns;
	}

	/**
	 * Display custom column content
	 *
	 * @since  3.41.46
	 *
	 * @param string $value        The value
	 * @param int    $form_id      The form ID
	 * @param int    $field_id     The field ID
	 * @param array  $entry        The entry
	 * @param string $query_string The query string
	 */
	public function entry_status_column_content( $value, $form_id, $field_id, $entry, $query_string ) {

		if ( 'wp_fusion' !== $field_id ) {
			return $value;
		}

		$complete_data = array(
			'contact_id' => gform_get_meta( $entry['id'], 'wpf_contact_id' ),
			'complete'   => gform_get_meta( $entry['id'], 'wpf_complete' ),
		);

		if ( $this->form_has_payments( $form_id ) ) {
			$complete_data['ec_complete']   = gform_get_meta( $entry['id'], 'wpf_ec_complete' );
			$complete_data['ec_invoice_id'] = gform_get_meta( $entry['id'], 'wpf_ec_invoice_id' );
		}

		return wpf_status_icon( $complete_data );
	}

	/**
	 * Set WPF logo for note avatar.
	 *
	 * @since  3.37.6
	 *
	 * @return string URL to logo.
	 */
	public function note_avatar() {

		return WPF_DIR_URL . '/assets/img/logo-sm-trans.png';
	}

	/**
	 * Loads stylesheets
	 *
	 * @access  public
	 * @return  array Styles
	 */
	public function styles() {

		if ( ! is_admin() ) {
			return parent::styles();
		}

		$styles = array(
			array(
				'handle'  => 'wpf_gforms_css',
				'src'     => WPF_DIR_URL . 'assets/css/wpf-gforms.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'tab' => 'wpfgforms' ),
				),
			),
			array(
				'handle'  => 'select4',
				'src'     => WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.css',
				'version' => '4.0.1',
				'enqueue' => array(
					array( 'tab' => 'wpfgforms' ),
				),
			),
			array(
				'handle'  => 'wpf-admin',
				'src'     => WPF_DIR_URL . 'assets/css/wpf-admin.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'tab' => 'wpfgforms' ),
				),
			),
		);

		return array_merge( parent::styles(), $styles );
	}


	/**
	 * Loads scripts
	 *
	 * @access  public
	 * @return  array Scripts
	 */
	public function scripts() {

		$scripts = array(
			array(
				'handle'  => 'select4',
				'src'     => WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.js',
				'version' => '4.0.1',
				'deps'    => array( 'jquery' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'wpfgforms',
					),
				),
			),
			array(
				'handle'  => 'wpf-admin',
				'src'     => WPF_DIR_URL . 'assets/js/wpf-admin.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery', 'select4' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'wpfgforms',
					),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}


	/**
	 * We don't want to sync a user to the CRM until GForms User Registration
	 * has finished saving all the user meta. Saves an extra API call.
	 *
	 * @since  3.38.35
	 *
	 * @param  array $post_data The registration data.
	 * @return array The registration data.
	 */
	public function maybe_bypass_user_register( $post_data ) {

		if ( doing_action( 'gform_entry_post_save' ) && ! did_action( 'gform_user_registered' ) ) {
			return null;
		}

		return $post_data;
	}

	/**
	 * Push updated meta data after user registration.
	 *
	 * @access  public
	 * @return  void
	 */
	public function user_registered( $user_id, $feed, $entry, $password ) {

		$user_meta = array(
			'user_pass' => $password,
		);

		wp_fusion()->user->user_register( $user_id, $user_meta );
	}

	/**
	 * Push updated meta data after profile update
	 *
	 * @access  public
	 * @return  void
	 */
	public function user_updated( $user_id, $feed, $entry, $password ) {

		$user_meta   = array();
		$custom_meta = array();

		// Get the submitted metadata.

		foreach ( $entry as $field => $value ) {

			if ( is_numeric( $field ) ) {
				$custom_meta[ $field ] = $value;
			}
		}

		foreach ( $feed['meta'] as $field => $entry_id ) {

			if ( 'email' === $field ) {
				$field = 'user_email';
			} elseif ( 'displayname' === $field ) {
				$field = 'display_name';
			}

			// Normal fields, first_name, etc.
			if ( is_numeric( $entry_id ) && isset( $custom_meta[ $entry_id ] ) ) {
				$user_meta[ $field ] = $custom_meta[ $entry_id ];
			}
		}

		// Custom meta.
		// @see https://docs.gravityforms.com/user-registration-feed-meta/#custom-field-properties.

		if ( ! empty( $feed['meta']['userMeta'] ) ) {

			foreach ( $feed['meta']['userMeta'] as $meta ) {

				if ( 'gf_custom' === $meta['key'] ) {
					if ( isset( $custom_meta[ $meta['value'] ] ) ) {
						$user_meta[ $meta['custom_key'] ] = $custom_meta[ $meta['value'] ];
					}
				} elseif ( isset( $custom_meta[ $meta['value'] ] ) ) {
					$user_meta[ $meta['key'] ] = $custom_meta[ $meta['value'] ];
				}
			}
		}

		if ( ! empty( $password ) ) {
			$user_meta['user_pass'] = $password;
		}

		if ( ! empty( $user_meta ) ) {
			wp_fusion()->user->push_user_meta( $user_id, $user_meta );
		}
	}


	/**
	 * Disable user updating during auto login with GForms user registration
	 *
	 * @access  public
	 * @return  int User ID
	 */
	public function update_user_id( $user_id ) {

		if ( doing_wpf_auto_login() ) {
			$user_id = false;
		}

		return $user_id;
	}


	/**
	 * Add contact ID merge tag to dropdown
	 *
	 * @access  public
	 * @return  object Form
	 */
	public function add_merge_tags( $form ) {

		if ( ! did_action( 'admin_head' ) ) {
			return $form;
		}

		?>
		<script type="text/javascript">

			gform.addFilter('gform_merge_tags', 'wpf_add_merge_tags');

			function wpf_add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option){
				mergeTags["other"].tags.push({ tag: '{contact_id}', label: 'Contact ID' });
				return mergeTags;
			}
		</script>

		<?php

		// return the form object from the php hook
		return $form;
	}


	/**
	 * Add contact ID merge tag to dropdown
	 *
	 * @access  public
	 * @return  object Form
	 */
	public function replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {

		if ( false !== strpos( $text, '{contact_id}' ) ) {

			// Contact ID.

			$contact_id = gform_get_meta( $entry['id'], 'wpf_contact_id' );
			$text       = str_replace( '{contact_id}', $contact_id, $text );

		}

		return $text;
	}


	/**
	 * If we're in an auto-login session, set the $current_user global before
	 * the form is displayed so that {user:***} merge tags work automatically.
	 *
	 * @since  3.38.5
	 * @since  3.38.28 Attached to gform_form_args filter so it works with forms added via Elementor widget.
	 *
	 * @param  array $args   The form args.
	 * @return array The form args.
	 */
	public function maybe_pre_fill_forms( $args ) {

		if ( doing_wpf_auto_login() ) {

			global $current_user;
			// phpcs:ignore
			$current_user = wpf_get_current_user();

		}

		return $args;
	}


	/**
	 * Add a meta box to the entry with the sync status.
	 *
	 * @since  3.37.3
	 *
	 * @param  array $meta_boxes The properties for the meta boxes.
	 * @param  array $entry      The entry currently being viewed/edited.
	 * @param  array $form       The form object used to process the current
	 *                           entry.
	 *
	 * @uses   GFFeedAddOn::get_active_feeds()
	 * @uses   GFHelpScout::initialize_api()
	 * @return array
	 */
	public function register_meta_box( $meta_boxes, $entry, $form ) {

		if ( $this->get_active_feeds( $form['id'] ) ) {
			$meta_boxes[ $this->_slug ] = array(
				'title'    => esc_html__( 'WP Fusion', 'wp-fusion' ),
				'callback' => array( $this, 'add_details_meta_box' ),
				'context'  => 'side',
			);
		}

		return $meta_boxes;
	}

	/**
	 * The callback used to echo the content to the meta box.
	 *
	 * @since 3.37.3
	 *
	 * @param array $args   An array containing the form and entry objects.
	 * @return HTML output.
	 */
	public function add_details_meta_box( $args ) {

		?>

		<strong><?php printf( __( 'Synced to %s:', 'wp-fusion' ), wp_fusion()->crm->name ); ?></strong>&nbsp;

		<?php if ( gform_get_meta( $args['entry']['id'], 'wpf_complete' ) ) : ?>
			<span><?php _e( 'Yes', 'wp-fusion' ); ?></span>
			<span class="dashicons dashicons-yes-alt"></span>
		<?php else : ?>
			<span><?php _e( 'No', 'wp-fusion' ); ?></span>
			<span class="dashicons dashicons-no"></span>
		<?php endif; ?>

		<br /><br />

		<?php $contact_id = gform_get_meta( $args['entry']['id'], 'wpf_contact_id' ); ?>

		<?php if ( $contact_id ) : ?>

			<strong><?php _e( 'Contact ID:', 'wp-fusion' ); ?></strong>&nbsp;
			<span><?php echo $contact_id; ?></span>

			<?php $edit_url = wp_fusion()->crm->get_contact_edit_url( $contact_id ); ?>

			<?php if ( $edit_url ) : ?>
				- <a href="<?php echo esc_url_raw( $edit_url ); ?>" target="_blank"><?php _e( 'View', 'wp-fusion' ); ?> &rarr;</a>
			<?php endif; ?>

			<br /><br />

		<?php endif; ?>

		<?php

		$url_args = array(
			'gf_wpf' => 'process',
			'lid'    => $args['entry']['id'],
		);

		$url = add_query_arg( $url_args );

		?>

		<a href="<?php echo esc_url( $url ); ?>" class="button"><?php _e( 'Process WP Fusion actions again', 'wp-fusion' ); ?></a>

		<?php
	}

	/**
	 * Handle the Process WP Fusion actions again button.
	 *
	 * @since 3.37.3
	 *
	 * @uses  GFAddOn::get_current_entry()
	 * @uses  GFAPI::get_form()
	 * @uses  GFFeedAddOn::maybe_process_feed()
	 */
	public function maybe_process_entry() {

		// If we're not on the entry view page, return.
		if ( rgget( 'page' ) !== 'gf_entries' || rgget( 'view' ) !== 'entry' || rgget( 'gf_wpf' ) !== 'process' ) {
			return;
		}

		// Get the current form and entry.
		$form  = GFAPI::get_form( rgget( 'id' ) );
		$entry = GFAPI::get_entry( rgget( 'lid' ) );

		if ( is_wp_error( $form ) || is_wp_error( $entry ) ) {
			return;
		}

		add_filter( 'wpf_prevent_reapply_tags', '__return_false' ); // allow tags to be sent again despite the cache.

		// Process feeds.
		$this->maybe_process_feed( $entry, $form );
	}

	/**
	 * Delayed feeds.
	 *
	 * Some addons (like Gravity Perks product configurator) set the feed to run "delayed"
	 * so it isn't processed until the order is received. Let's WP Fusion run after
	 * those processes have completed.
	 *
	 * @since 3.41.45
	 *
	 * @link https://docs.gravityforms.com/add-delayed-payment-support-feed-add/
	 *
	 * @param array $feed  The feed object currently being processed.
	 * @param array $entry The entry currently being viewed/edited.
	 * @param array $form  The form object used to process the current entry.
	 */
	public function delay_feed( $feed, $entry, $form ) {

		$this->process_feed( $feed, $entry, $form );
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 3.37.21
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return wpf_logo_svg();
	}


	/**
	 * Runs after an entry update in backend or frontend in plugins like Gravity View.
	 *
	 * @since 3.43.3
	 *
	 * @param array   $form
	 * @param integer $entry_id
	 */
	public function after_update_entry( $form, $entry_id ) {

		$entry = GFAPI::get_entry( $entry_id );
		$feeds = GFAPI::get_feeds( null, $entry['form_id'], 'wpfgforms' );

		foreach ( $feeds as $feed ) {

			if ( $this->is_feed_condition_met( $feed, $form, $entry ) ) {
				$this->process_feed( $feed, $entry, $form );
			}
		}
	}




	/**
	 * //
	 * // CONDITIONAL LOGIC
	 * //
	 **/


	/**
	 * Add Tags logic to be valid for conditional logic.
	 *
	 * @since 3.40.22
	 *
	 * @param bool   $is_valid Is valid.
	 * @param string $operator The operator.
	 * @return bool Whether or not the operator is valid.
	 */
	public function valid_conditional_logic( $is_valid, $operator ) {

		if ( $operator === 'has_tag' || $operator === 'not_has_tag' ) {
			$is_valid = true;
		}

		return $is_valid;
	}


	/**
	 * Add conditional logic script to control fields based on tags.
	 *
	 * @since 3.40.22
	 *
	 * @return mixed JavaScript output.
	 */
	public function frontend_conditional_operators( $form ) {

		if ( ! wpf_is_user_logged_in() || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_is_json_request() ) {
			return;
		}

		$proceed = false;

		// Check if the form has any WP Fusion conditional logic rules.

		foreach ( $form['fields'] as $field ) {

			if ( ! empty( $field->{'conditionalLogic'} ) ) {

				foreach ( $field->{'conditionalLogic'}['rules'] as $rule ) {

					if ( 'wpfusion' === $rule['fieldId'] ) {
						$proceed = true;
						break 2;
					}
				}
			}
		}

		if ( ! $proceed ) {
			return;
		}

		$user_tags = wpf_get_tags();
		$override  = wpf_admin_override();

		$script = "
			gform.addFilter( 'gform_is_value_match', function (isMatch, formId, rule) {

				if ( rule.fieldId === 'wpfusion' ) {

					if( rule.operator === 'has_tag' || rule.operator === 'is' ) {

						" . ( $override ? 'return true; // Exclude Administrators enabled.' : '' ) . '

						var user_tags = ' . wp_json_encode( $user_tags ) . "

						if ( Array.isArray( user_tags ) && user_tags.includes( rule.value ) ) {
							return true;
						} else {
							return false;
						}

					} else if( rule.operator === 'not_has_tag' ) {

						" . ( $override ? 'return true; // Exclude Administrators enabled.' : '' ) . '

						var user_tags = ' . wp_json_encode( $user_tags ) . '

						if( Array.isArray( user_tags ) && ! user_tags.includes( rule.value ) ) {
							return true;
						} else {
							return false;
						}
					}
				}

				return isMatch;
			} );';

		GFFormDisplay::add_init_script( $form['id'], 'wp_fusion_conditional', GFFormDisplay::ON_PAGE_RENDER, $script );
	}

	/**
	 * Add WP Fusion tags and operators to conditional logic.
	 *
	 * @since 3.40.22
	 *
	 * @return mixed JavaScript output.
	 */
	public function admin_conditional_operators() {

		if ( method_exists( 'GFForms', 'is_gravity_page' ) && GFForms::is_gravity_page() ) {

			?>
			<script type="text/javascript">

				var crm_name = "<?php printf( __( '%s Tags', 'wp-fusion' ), wp_fusion()->crm->name ); ?>";
				gform.addFilter( 'gform_conditional_logic_fields', 'set_conditional_field' );
				function set_conditional_field( options, form, selectedFieldId ){
					options.push( {
						label: crm_name,
						value: 'wpfusion'
					});

					return options;
				}

				var tags = <?php echo json_encode( wp_fusion()->settings->get_available_tags_flat() ); ?>;

				gform.addFilter( 'gform_conditional_logic_values_input', 'set_rule_info' );

				function set_rule_info( str, objectType, ruleIndex, selectedFieldId, selectedValue ) {
					if ( selectedFieldId === 'wpfusion' ){

						str = `<select class="gfield_rule_select gfield_rule_value_dropdown_cl" id="feed_condition_rule_value_`+ruleIndex+`" name="feed_condition_rule_value_`+ruleIndex+`" onchange="SetRuleProperty('feed_condition', `+ruleIndex+`, 'value', jQuery(this).val());" onkeyup="SetRuleProperty('feed_condition', `+ruleIndex+`, 'value', jQuery(this).val());">`;
							jQuery.each( tags, function( index, tag ){
								str+= '<option ' + ( selectedValue === index ? 'selected="selected"' : '') +' value="' + index + '">' + tag + '</option>';
							});

						str+= `</select>`;
					}
					return str;
				}

				gform.addFilter('gform_conditional_logic_operators', function (operators, objectType, fieldId) {

					if(fieldId === 'wpfusion'){

						gf_vars['has_tag'] = '<?php _e( 'user has tag', 'wp-fusion' ); ?>';
						gf_vars['not_has_tag'] = '<?php _e( 'user does not have tag', 'wp-fusion' ); ?>';

						// For some reason this is different on the field conditional logic vs the feed conditional logic.

						if ( operators.isnot === 'isNot' ) {

							// Feed settings.
							operators = {
								'has_tag'     : 'has_tag',
								'not_has_tag' : 'not_has_tag'
							}
						} else {

							// Field settings.
							operators = {
								'has_tag'     : gf_vars['has_tag'],
								'not_has_tag' : gf_vars['not_has_tag']
							}
						}

					}

					return operators;
				});


			</script>
			<?php
		}
	}


	/**
	 * Validate wpf conditional logic in feeds.
	 *
	 * @since 3.40.22
	 *
	 * @param boolean $is_match
	 * @param string  $field_value
	 * @param string  $target_value
	 * @param string  $operation
	 * @param array   $source_field
	 * @param array   $rule
	 * @return boolean
	 */
	public function validate_conditional_logic( $is_match, $field_value, $target_value, $operation, $source_field, $rule ) {
		if ( $operation === 'has_tag' && wpf_is_user_logged_in() ) {
			$user_tags = wpf_get_tags();
			if ( ! empty( $user_tags ) && in_array( $target_value, $user_tags ) ) {
				$is_match = true;
			}
		}

		if ( $operation === 'not_has_tag' && wpf_is_user_logged_in() ) {
			$user_tags = wpf_get_tags();
			if ( ! empty( $user_tags ) && ! in_array( $target_value, $user_tags ) ) {
				$is_match = true;
			}
		}

		return $is_match;
	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds Woo Subscriptions checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */
	public function export_options( $options ) {

		$options['gravity_forms'] = array(
			'label'         => 'Gravity Forms entries',
			'process_again' => true,
			'title'         => 'Entries',
			'tooltip'       => 'Find Gravity Forms entries that have not been successfully processed by WP Fusion and syncs them to ' . wp_fusion()->crm->name . ' based on their configured feeds.',
		);

		return $options;
	}

	/**
	 * Gets total list of entries to be processed
	 *
	 * @access public
	 * @return array Subscriptions
	 */
	public function batch_init( $args ) {

		$entry_ids = array();

		$feeds = GFAPI::get_feeds( null, null, 'wpfgforms' );

		if ( empty( $feeds ) ) {
			return $entry_ids;
		}

		$form_ids = array();

		foreach ( $feeds as $feed ) {
			$form_ids[] = $feed['form_id'];
		}

		if ( ! empty( $args['skip_processed'] ) ) {
			$search_criteria = array(
				'field_filters' => array(
					array(
						'key'      => 'wpf_complete',
						'value'    => '1',
						'operator' => '!=',
					),
				),
			);
		} else {
			$search_criteria = array();
		}

		$entry_ids = GFAPI::get_entry_ids( $form_ids, $search_criteria );

		return $entry_ids;
	}

	/**
	 * Processes entry feeds
	 *
	 * @access public
	 * @return void
	 */
	public function batch_step( $entry_id ) {

		// Unlock the entry if we're exporting old ones.
		gform_delete_meta( $entry_id, 'wpf_complete' );

		$entry = GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) ) {
			wpf_log( 'error', 0, 'Error getting entry: ' . $entry->get_error_message() );
			return;
		}

		$form = GFAPI::get_form( $entry['form_id'] );

		if ( ! $form ) {
			return;
		}

		$feeds = GFAPI::get_feeds( null, $entry['form_id'], 'wpfgforms' );

		if ( is_wp_error( $feeds ) ) {
			wpf_log( 'error', 0, 'Error getting feeds from form ID ' . $entry['form_id'] . ': ' . $feeds->get_error_message() );
			return;
		}

		foreach ( $feeds as $feed ) {

			if ( $this->is_feed_condition_met( $feed, $form, $entry ) ) {
				$this->process_feed( $feed, $entry, $form );
			}
		}
	}
}

wp_fusion()->integrations->{'gravity-forms'} = new WPF_GForms_Integration();
