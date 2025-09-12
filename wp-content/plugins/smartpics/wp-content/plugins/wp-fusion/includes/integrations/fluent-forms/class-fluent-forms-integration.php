<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_FluentForms_Integration extends \FluentForm\App\Http\Controllers\IntegrationManagerController {

	/**
	 * The integration logo.
	 *
	 * @since 3.41.17
	 * @var string $logo
	 */
	public $logo = WPF_DIR_URL . 'assets/img/logo-wide-color.png';

	/**
	 * Get things started.
	 */
	public function __construct() {

		parent::__construct(
			false,
			__( 'WP Fusion', 'wp-fusion' ),
			'wpfusion',
			'_fluentform_wpfusion_settings',
			'fluentform_wpfusion_feed',
			16
		);

		// translators: %s is the CRM name.
		$this->description = sprintf( __( 'WP Fusion syncs your Fluent Forms entries to %s.', 'wp-fusion' ), wp_fusion()->crm->name );

		$this->registerAdminHooks();
	}

	/**
	 * Get global fields
	 *
	 * @access public
	 * @return array Fields
	 */
	public function getGlobalFields( $fields ) {
		return array(
			'logo'             => $this->logo,
			'menu_title'       => __( 'WP Fusion Settings', 'wp-fusion' ),
			// translators: %s is the CRM name.
			'menu_description' => sprintf( __( 'Fluent Forms is already connected to %s by WP Fusion, there\'s nothing to configure here. You can set up WP Fusion your individual forms under Settings &raquo; Marketing &amp; CRM Integrations. For more information <a href="https://wpfusion.com/documentation/lead-generation/fluent-forms/" target="_blank">see the documentation</a>.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'valid_message'    => __( 'Your Mailchimp API Key is valid', 'fluentform' ),
			'invalid_message'  => ' ',
			'save_button_text' => ' ',
		);
	}

	/**
	 * Set integration to configured
	 *
	 * @access public
	 * @return bool Configured
	 */
	public function isConfigured() {
		return true;
	}

	/**
	 * Set integration to enabled.
	 *
	 * @since 3.41.18
	 * @return bool Enabled.
	 */
	public function isEnabled() {
		return true;
	}

	/**
	 * Register the integration
	 *
	 * @access public
	 * @return array Integrations
	 */
	public function pushIntegration( $integrations, $form_id ) {

		$integrations[ $this->integrationKey ] = array(
			'title'                 => __( 'WP Fusion Integration', 'wp-fusion' ),
			'logo'                  => $this->logo,
			'is_active'             => true,
			'configure_title'       => 'Configuration required!',
			'global_configure_url'  => admin_url( 'admin.php?page=fluent_forms_settings#general-wpfusion-settings' ),
			'configure_message'     => 'WP Fusion is not configured yet! Please configure your WP Fusion API first',
			'configure_button_text' => 'Set WP Fusion API',
		);

		return $integrations;
	}

	/**
	 * Get integration defaults
	 *
	 * @access public
	 * @return array Defaults
	 */
	public function getIntegrationDefaults( $settings, $form_id ) {

		return array(
			'name'                    => '',
			'fieldEmailAddress'       => '',
			'custom_field_mappings'   => (object) array(),
			'default_fields'          => (object) array(),
			'note'                    => '',
			'tag_ids'                 => array(),
			'tag_ids_selection_type'  => 'simple',
			'tag_routers'             => array(),
			'conditionals'            => array(
				'conditions' => array(),
				'status'     => false,
				'type'       => 'all',
			),
			'instant_responders'      => false,
			'last_broadcast_campaign' => false,
			'enabled'                 => true,
		);
	}

	/**
	 * Get settings fields
	 *
	 * @access public
	 * @return array Settings
	 */
	public function getSettingsFields( $settings, $form_id ) {
		$settings = array(
			'fields'              => array(
				array(
					'key'         => 'name',
					'label'       => __( 'Name', 'wp-fusion' ),
					'required'    => true,
					'placeholder' => __( 'Your Feed Name', 'wp-fusion' ),
					'component'   => 'text',
				),
				array(
					'key'                => 'custom_field_mappings',
					'require_list'       => false,
					'label'              => __( 'Map Fields', 'wp-fusion' ),
					// translators: The CRM name.
					'tips'               => sprintf( __( 'Select which Fluent Form fields pair with their respective %s fields.', 'wp-fusion' ), wp_fusion()->crm->name ),
					'component'          => 'map_fields',
					// translators: The CRM name.
					'field_label_remote' => sprintf( __( '%s Field', 'wp-fusion' ), wp_fusion()->crm->name ),
					'field_label_local'  => __( 'Form Field', 'wp-fusion' ),
					'default_fields'     => $this->getMergeFields( false, false, $form_id ),
				),
				array(
					'key'                => 'tag_ids',
					'require_list'       => false,
					'label'              => __( 'Apply Tags', 'wp-fusion' ),
					'placeholder'        => __( 'Select Tags', 'wp-fusion' ),
					'component'          => 'selection_routing',
					'simple_component'   => 'select',
					'routing_input_type' => 'select',
					'routing_key'        => 'tag_ids_selection_type',
					'settings_key'       => 'tag_routers',
					'is_multiple'        => true,
					'labels'             => array(
						'choice_label'      => __( 'Enable Dynamic Tag Selection', 'wp-fusion' ),
						'input_label'       => '',
						'input_placeholder' => __( 'Set Tag', 'wp-fusion' ),
					),
					'options'            => $this->getTags(),
				),
				array(
					'key'          => 'tags',
					'require_list' => false,
					'label'        => __( 'Tags', 'wp-fusion' ),
					'tips'         => __( 'Associate tags to your contacts with a comma separated list (e.g. new lead, FluentForms, web source).', 'wp-fusion' ),
					'component'    => 'value_text',
					'inline_tip'   => __( 'Enter tag names or tag IDs, separated by commas', 'wp-fusion' ),
				),
				array(
					'key'         => 'list_ids',
					'label'       => __( 'Apply Lists', 'wp-fusion' ),
					'placeholder' => __( 'Select Lists', 'wp-fusion' ),
					// translators: The CRM name.
					'tips'        => sprintf( __( 'Select %s lists to add new contacts to.', 'wp-fusion' ), wp_fusion()->crm->name ),
					'component'   => 'select',
					'is_multiple' => true,
					'required'    => false,
					'options'     => $this->getLists(),
				),
				array(
					'require_list' => false,
					'key'          => 'conditionals',
					'label'        => __( 'Conditional Logic', 'wp-fusion' ),
					'tips'         => __( 'Allow WP Fusion integration conditionally based on your submission values', 'wp-fusion' ),
					'component'    => 'conditional_block',
				),
				array(
					'require_list'    => false,
					'key'             => 'enabled',
					'label'           => __( 'Status', 'wp-fusion' ),
					'component'       => 'checkbox-single',
					'checkobox_label' => __( 'Enable This feed', 'wp-fusion' ),
				),
			),
			'button_require_list' => false,
			'integration_title'   => $this->title,
		);

		$meta = FluentForm\App\Helpers\Helper::getFormMeta( $form_id, 'fluentform_wpfusion_feed', array() );

		// Hide the old tags field if it's not in use.
		if ( empty( $meta ) || empty( $meta['tags'] ) ) {
			foreach ( $settings['fields'] as $key => $field ) {
				if ( 'tags' === $field['key'] ) {
					unset( $settings['fields'][ $key ] );
				}
			}
		}

		// Hide the list field if the CRM doesn't support it.
		if ( ! in_array( 'lists', wp_fusion()->crm->supports, true ) ) {
			foreach ( $settings['fields'] as $key => $field ) {
				if ( 'list_ids' === $field['key'] ) {
					unset( $settings['fields'][ $key ] );
				}
			}
		}

		return $settings;
	}

	/**
	 * Get CRM fields
	 *
	 * @access public
	 * @return array Fields
	 */
	public function getMergeFields( $list, $list_id, $form_id ) {

		$fields = array();

		$available_fields = wp_fusion()->settings->get_crm_fields_flat();

		foreach ( $available_fields as $field_id => $field_label ) {

			$remote_required = false;

			if ( 'Email' === $field_label ) {
				$remote_required = true;
			}

			$fields[] = array(
				'name'     => $field_id,
				'label'    => $field_label,
				'required' => $remote_required,
			);

		}

		return $fields;
	}

	/**
	 * Get available tags
	 *
	 * @access protected
	 * @return array Tags
	 */
	protected function getTags() {
		return wp_fusion()->settings->get_available_tags_flat();
	}

	/**
	 * Get available lists.
	 *
	 * @since 3.44.12
	 *
	 * @access protected
	 * @return array Lists
	 */
	protected function getLists() {
		return wpf_get_option( 'available_lists', array() );
	}

	/**
	 * Handle form submission
	 *
	 * @access public
	 * @return void
	 */
	public function notify( $feed, $form_data, $entry, $form ) {

		$email_address = false;

		$update_data = array();

		foreach ( $feed['processedValues']['default_fields'] as $field => $value ) {

			if ( false !== strpos( $field, 'add_tag_' ) ) {

				// Don't run the filter on dynamic tagging inputs.
				$update_data[ $field ] = $value;
				continue;

			}

			$value = apply_filters( 'wpf_format_field_value', $value, 'text', $field );

			if ( ! empty( $value ) || 0 === $value || '0' === $value ) {

				// Don't sync empty values unless they're actually the number 0.
				$update_data[ $field ] = $value;
			}

			if ( false === $email_address && is_email( $value ) ) {
				$email_address = $value;
			}
		}

		$apply_tags = array();

		if ( ! empty( $feed['processedValues']['tags'] ) ) {

			// Original string-based tags field.

			// str_getcsv to preserve tags in quotes.

			$input_tags = array_filter( str_getcsv( $feed['processedValues']['tags'], ',' ) );

			// Get tags to apply
			foreach ( $input_tags as $tag ) {

				$tag_id = wp_fusion()->user->get_tag_id( $tag );

				if ( false === $tag_id ) {

					wpf_log( 'notice', 0, 'Warning: ' . $tag . ' is not a valid tag name or ID.' );
					continue;

				}

				$apply_tags[] = $tag_id;

			}
		}

		if ( ! empty( $feed['processedValues']['tag_ids'] ) ) {

			// New dynamic tag selection field.
			$apply_tags = array_merge( $apply_tags, $feed['processedValues']['tag_ids'] );

		}

		if ( ! empty( $feed['processedValues']['tag_routers'] ) ) {

			// Conditional tagging.
			$apply_tags = array_merge( $apply_tags, $this->get_eligible_tags( $feed['processedValues']['tag_routers'], $form_data ) );

		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => $apply_tags,
			'apply_lists'      => isset( $feed['processedValues']['list_ids'] ) ? $feed['processedValues']['list_ids'] : array(),
			'add_only'         => false,
			'integration_slug' => 'fluent_forms',
			'integration_name' => 'Fluent Forms',
			'form_id'          => $form->id,
			'form_title'       => $form->title,
			'form_edit_link'   => admin_url( 'admin.php?page=fluent_forms&route=editor&form_id=' . $form->id ),
			'entry_id'         => $entry->id,
		);

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

		if ( is_wp_error( $contact_id ) ) {
			do_action( 'ff_integration_action_result', $feed, 'failed', $contact_id->get_error_message() );
		} else {

			do_action( 'ff_integration_action_result', $feed, 'success', 'Entry synced to ' . wp_fusion()->crm->name . ' (contact ID ' . $contact_id . ')' );
			FluentForm\App\Helpers\Helper::setSubmissionMeta( $entry->id, 'wpf_contact_id', $contact_id );

		}
	}

	/**
	 * Determines which tags are eligible to be applied based on the tag routers and form data.
	 *
	 * @since 3.44.11
	 *
	 * @param array $tag_routers The tag routers from the feed.
	 * @param array $form_data   The submitted form data.
	 * @return array An array of tags eligible to be applied.
	 */
	private function get_eligible_tags( $tag_routers, $form_data ) {
		$eligible_tags = array();

		foreach ( $tag_routers as $router ) {
			$field          = $router['field'];
			$operator       = $router['operator'];
			$expected_value = $router['value'];
			$tag            = $router['input_value'];

			// Get the actual form value, handling nested arrays like multiselects.
			$actual_values = (array) $form_data[ $field ];

			foreach ( $actual_values as $actual_value ) {

				$condition_met = false;

				switch ( $operator ) {
					case '=':
						$condition_met = ( $actual_value === $expected_value );
						break;
					case '!=':
						$condition_met = ( $actual_value !== $expected_value );
						break;
					case '>':
						$condition_met = ( $actual_value > $expected_value );
						break;
					case '<':
						$condition_met = ( $actual_value < $expected_value );
						break;
					case 'contains':
						$condition_met = ( strpos( $actual_value, $expected_value ) !== false );
						break;
					case 'starts_with':
						$condition_met = ( strpos( $actual_value, $expected_value ) === 0 );
						break;
					case 'ends_with':
						$condition_met = ( substr( $actual_value, -strlen( $expected_value ) ) === $expected_value );
						break;
					// Add more operators as needed
				}

				if ( $condition_met ) {
					$eligible_tags[] = $tag;
				}
			}
		}

		return $eligible_tags;
	}
}
