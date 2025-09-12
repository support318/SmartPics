<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Fluent_Forms extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'fluent-forms';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'Fluent Forms';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/lead-generation/fluent-forms/';

	/**
	 * The integration instance.
	 *
	 * @since 3.45.2
	 * @var WPF_FluentForms_Integration $integration
	 */
	public $integration;

	/**
	 * Gets things started
	 *
	 * @since 3.38.14
	 */
	public function init() {

		// Load the integration.
		require_once __DIR__ . '/class-fluent-forms-integration.php';
		$this->integration = new WPF_FluentForms_Integration();

		add_filter( 'fluentform_notifying_async_wpfusion', array( $this, 'maybe_async' ) );
		add_filter( 'fluentform_user_registration_feed', array( $this, 'merge_registration_data' ), 10, 3 );
		add_action( 'fluentform/user_registration_completed', array( $this, 'save_user_fields' ), 20, 3 );
		add_action( 'fluentform_user_update_completed', array( $this, 'save_user_fields' ), 20, 3 );

		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_fluent_forms_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_fluent_forms', array( $this, 'batch_step' ) );
	}

	/**
	 * If we're using form-auto login or tracking leadsources, the form can't be
	 * processed asynchronously.
	 *
	 * @since 3.42.0
	 *
	 * @param bool $async_enabled Whether or not async is enabled.
	 * @return bool Whether or not async is enabled.
	 */
	public function maybe_async( $async_enabled ) {

		if ( wpf_get_option( 'auto_login_forms' ) || wp_fusion()->lead_source_tracking->is_tracking_leadsource() ) {
			return false;
		}

		return $async_enabled;
	}

	/**
	 * Adds FE field group to meta fields list
	 *
	 * @since  3.38.22
	 *
	 * @param  array $field_groups The field groups.
	 * @return array The field groups.
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['fluent_forms_user_reg'] = array(
			'title' => __( 'Fluent Forms User Registration', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/lead-generation/fluent-forms/',
		);

		return $field_groups;
	}

	/**
	 * Detect any FF user registration fields and make them available for
	 * mapping via the WPF Contact Fields list.
	 *
	 * @since  3.38.22
	 *
	 * @param  array $meta_fields The meta fields.
	 * @return array  The meta fields.
	 */
	public function add_meta_fields( $meta_fields ) {

		$settings = get_option( 'fluentform_global_modules_status' );

		if ( isset( $settings['UserRegistration'] ) && 'yes' === $settings['UserRegistration'] ) {

			$meta_fields['ff_generated_password'] = array(
				'label'  => 'Generated Password',
				'type'   => 'text',
				'group'  => 'fluent_forms_user_reg',
				'pseudo' => true,
			);

			$forms = wpFluent()->table( 'fluentform_forms' )
			->select( array( 'id' ) )
			->get();

			if ( empty( $forms ) ) {
				return $meta_fields;
			}

			foreach ( $forms as $form ) {

				$id    = $form->id;
				$feeds = wpFluent()->table( 'fluentform_form_meta' )
				->select( array( 'value' ) )
				->where( 'meta_key', 'user_registration_feeds' )
				->where( 'form_id', $id )
				->get();

				if ( empty( $feeds ) ) {
					continue;
				}
				foreach ( $feeds as $feed ) {

					$meta = json_decode( $feed->value );

					if ( empty( $meta->{'userMeta'} ) ) {
						continue;
					}

					foreach ( $meta->{'userMeta'}  as $meta_key => $val ) {

						if ( empty( $val->label ) ) {
							continue;
						}

						$meta_fields[ $val->label ] = array(
							'label' => $val->label,
							'type'  => 'text',
							'group' => 'fluent_forms_user_reg',
						);
					}
				}
			}
		}

		return $meta_fields;
	}

	/**
	 * Syncs the custom usermeta and generated password fields on a registration
	 * form.
	 *
	 * @since  3.38.32
	 *
	 * @param  array $feed   The feed.
	 * @param  array $entry  The entry.
	 * @param  array $form   The form.
	 * @return array The feed.
	 */
	public function merge_registration_data( $feed, $entry, $form ) {

		$merge = array();

		if ( empty( $feed['processedValues']['password'] ) ) {

			$feed['processedValues']['password'] = wp_generate_password( 8 );

			$merge['ff_generated_password'] = $feed['processedValues']['password'];

		}

		if ( ! empty( $feed['processedValues']['userMeta'] ) ) {

			foreach ( $feed['processedValues']['userMeta'] as $meta ) {
				$merge[ $meta['label'] ] = $meta['item_value'];
			}
		}

		if ( ! empty( $feed['processedValues']['first_name'] ) ) {
			$merge['first_name'] = $feed['processedValues']['first_name'];
		}

		if ( ! empty( $feed['processedValues']['last_name'] ) ) {
			$merge['last_name'] = $feed['processedValues']['last_name'];
		}

		if ( ! empty( $merge ) ) {

			add_filter(
				'wpf_user_register',
				function ( $user_meta ) use ( &$merge ) {

					$user_meta = array_merge( $user_meta, $merge );

					return $user_meta;
				}
			);
		}

		return $feed;
	}

	/**
	 * Saves user fields on form submission.
	 *
	 * @since 3.41.10
	 * @param int   $user_id The user ID.
	 * @param array $feed The feed.
	 * @param array $entry The entry.
	 * @return void
	 */
	public function save_user_fields( $user_id, $feed, $entry ) {

		$prefixed_fields = array();
		$xprofile_fields = \FluentForm\Framework\Helpers\ArrayHelper::get( $feed, 'processedValues.bboss_profile_fields' );

		if ( ! empty( $xprofile_fields ) ) {

			foreach ( $xprofile_fields as $field ) {
				$prefixed_fields[ 'bbp_field_' . trim( $field['label'] ) ] = $field['item_value'];

			}

			wp_fusion()->user->push_user_meta( $user_id, $prefixed_fields );

		}
	}

	/**
	 * Create new Batch Operation option
	 *
	 * @since 3.41.9
	 * @param mixed $options The options for the operation.
	 * @return mixed $options Options
	 */
	public function export_options( $options ) {

		$options['fluent_forms'] = array(
			'label'         => 'Fluent Forms entries',
			'process_again' => true,
			'title'         => 'Entries',
			'tooltip'       => 'Find Fluent Forms entries that have not been successfully processed by WP Fusion and syncs them to ' . wp_fusion()->crm->name . ' based on their configured feeds.',
		);

		return $options;
	}

	/**
	 * Gets total list of entries to be processed
	 *
	 * @since 3.41.9
	 * @param array $args Array key ['skip_processed'] Is an entry already exported.
	 * @return array Entry IDs.
	 */
	public function batch_init( $args ) {

		$formapi = fluentFormApi( 'forms' );
		$forms   = $formapi->forms();

		$entry_ids = array();

		foreach ( $forms['data'] as $form ) {

			$formapi = fluentFormApi( 'forms' )->entryInstance( $form->id );
			$atts    = array(
				'per_page'   => 10,
				'page'       => 1,
				'search'     => '',
				'sort_by'    => 'DESC',
				'entry_type' => 'all',
			);
			$entries = $formapi->entries( $atts, false );

			foreach ( $entries['data'] as $entry ) {

				if ( ! empty( $args['skip_processed'] ) ) {
					$contact_id = FluentForm\App\Helpers\Helper::getSubmissionMeta( $entry->id, 'wpf_contact_id' );
				}
				if ( empty( $contact_id ) ) {
					$entry_ids[] = $entry->id;
				}
			}
		}

		return $entry_ids;
	}

	/**
	 * Processes entry feeds.
	 *
	 * @since 3.41.9
	 * @param int $entry_id The ID of the entry to process.
	 * @return void
	 */
	public function batch_step( $entry_id ) {

		$notification_manager = new \FluentForm\App\Services\Integrations\GlobalNotificationManager( wpFluentForm() );

		$form     = fluentFormApi( 'submissions' )->find( $entry_id );
		$form_api = fluentFormApi( 'forms' )->entryInstance( $form->form_id );
		$entry    = $form_api->entry( $entry_id );

		$feed_keys      = apply_filters( 'fluentform_global_notification_active_types', array(), $form->id );
		$feed_meta_keys = array_keys( $feed_keys );

		$feeds = wpFluent()->table( 'fluentform_form_meta' )
		->where( 'form_id', $form->id )
		->whereIn( 'meta_key', $feed_meta_keys )
		->orderBy( 'id', 'ASC' )
		->get();

		$enabled_feeds = $notification_manager->getEnabledFeeds( $feeds, $form, $entry_id );

		foreach ( $enabled_feeds as $feed ) {
			wp_fusion()->integrations->{'fluent-forms-integration'}->notify( $feed, false, $entry, $form );
		}
	}
}

new WPF_Fluent_Forms();
