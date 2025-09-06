<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use FluentBooking\App\Http\Controllers\IntegrationManagerController;
use FluentBooking\Framework\Support\Arr;
use FluentBooking\App\Models\Meta;
use FluentBooking\App\Services\EditorShortCodeParser;
use FluentBooking\App\Services\Integrations\GlobalNotificationService;

/**
 * Class WPF_FluentBooking.
 *
 * @since 3.41.39
 */
class WPF_FluentBooking extends IntegrationManagerController {

	/**
	 * Has global menu.
	 *
	 * @var bool Has global menu.
	 * Whether or not the integration has a global menu.
	 *
	 * @since 3.41.39
	 */
	public $hasGlobalMenu = false;

	/**
	 * Disable global setting.
	 *
	 * @var string Disable global setting.
	 * Disable the global settings.
	 *
	 * @since 3.41.39
	 */
	public $disableGlobalSettings = 'yes';


	/**
	 * Gets things started.
	 *
	 * @since 3.41.39
	 */
	public function __construct() {

		parent::__construct(
			__( 'WP Fusion', 'wp-fusion' ),
			'wpfusion',
			'_fluent_booking_wpfusion_settings',
			'wpfusion_feeds',
			10
		);

		$this->logo = WPF_DIR_URL . 'assets/img/logo.png';

		$this->description = sprintf( __( 'Connect FluentBooking with %s with WP Fusion', 'wp-fusion' ), wp_fusion()->crm->name );

		$this->registerAdminHooks();

		// Add hook for rescheduling.
		add_action( 'fluent_booking/after_booking_rescheduled', array( $this, 'handle_rescheduled' ), 10, 3 );

		/*
		 * For Remote Connections you may set it to
		 * add_filter('fluent_booking/notifying_async_wpfusion', '__return_true');
		 * if you set it to true the integration will run on background.
		 */
		add_filter( 'fluent_booking/notifying_async_wpfusion', '__return_false' );
	}

	/**
	 * Set integration to configured.
	 *
	 * @since 3.41.39
	 *
	 * @return bool Configured
	 */
	public function isConfigured() {
		return true;
	}

	/**
	 * Register the integration.
	 *
	 * @since 3.41.39
	 *
	 * @param array $integrations Integrations.
	 * @param int   $calendar_event_id Calendar Event ID.
	 * @return array Integrations.
	 */
	public function pushIntegration( $integrations, $calendar_event_id ) {

		$integrations[ $this->integrationKey ] = array(
			'title'                 => $this->title . ' Integration',
			'logo'                  => $this->logo,
			'is_active'             => $this->isConfigured(),
			'configure_title'       => __( 'Configuration not required!', 'fluent_booking' ),
			'global_configure_url'  => '#',
			'configure_message'     => __( 'It\s configured!', 'fluent_booking' ),
			'configure_button_text' => __( 'Set WP Fusion API', 'fluent_booking' ),
		);

		return $integrations;
	}

	/**
	 * Get integration defaults
	 *
	 * @since 3.41.39
	 *
	 * @param array $settings Settings.
	 * @param int   $calendar_event_id Calendar Event ID.=
	 * @return array<string,mixed> Defaults.
	 */
	public function getIntegrationDefaults( $settings, $calendar_event_id ) {

		return array(
			'name'                    => '',
			'first_name'              => '{{guest.first_name}}',
			'last_name'               => '{{guest.last_name}}',
			'email'                   => '{{guest.email}}',
			'other_fields'            => array(
				array(
					'item_value' => '',
					'label'      => '',
				),
			),
			'note'                    => '',
			'tags'                    => '',
			'conditionals'            => array(
				'conditions' => array(),
				'status'     => false,
				'type'       => 'all',
			),
			'instant_responders'      => false,
			'last_broadcast_campaign' => false,
			'enabled'                 => true,
			'run_events_only'         => array(),
		);
	}

	/**
	 * Get settings fields
	 *
	 * @since 3.41.39
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param int                 $calendar_event_id Calendar Event ID.
	 * @return array<string,mixed> Settings.
	 */
	public function getSettingsFields( $settings, $calendar_event_id ) {
		$fields = array();

		// Name field
		$fields[] = array(
			'key'         => 'name',
			'label'       => 'Name',
			'required'    => true,
			'placeholder' => 'Your Feed Name',
			'component'   => 'text',
		);

		// Custom Fields mapping
		$fields[] = array(
			'key'                => 'CustomFields',
			'require_list'       => false,
			'label'              => __( 'Map Primary Fields', 'wp-fusion' ),
			'tips'               => sprintf( __( 'Associate your %s merge tags to the appropriate FluentBooking fields by selecting a form field from the list.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'component'          => 'map_fields',
			'field_label_remote' => sprintf( __( '%s Field', 'wp-fusion' ), wp_fusion()->crm->name ),
			'field_label_local'  => __( 'Booking Field', 'fluent_booking' ),
			'primary_fields'     => array(
				array(
					'key'           => 'email',
					'label'         => __( 'Email Address', 'fluent_booking' ),
					'required'      => true,
					'input_options' => 'emails',
				),
			),
		);

		// Other fields mapping
		$fields[] = array(
			'key'                => 'other_fields',
			'require_list'       => false,
			'label'              => __( 'Other Fields', 'fluent_booking' ),
			'tips'               => sprintf( __( 'Select which FluentBooking fields pair with their respective fields in %s.', 'fluent_booking' ), wp_fusion()->crm->name ),
			'component'          => 'dropdown_many_fields',
			'field_label_remote' => wp_fusion()->crm->name . ' Field',
			'field_label_local'  => __( 'FluentBooking Field', 'fluent_booking' ),
			'options'            => $this->getMergeFields( false, false, $calendar_event_id ),
		);

		// Tags field
		$fields[] = array(
			'key'          => 'tags',
			'require_list' => false,
			'label'        => __( 'Tags', 'wp-fusion' ),
			'tips'         => __( 'Associate tags to your contacts with a comma separated list (e.g. New Booking, FluentForms, web source).', 'wp-fusion' ),
			'component'    => 'value_text',
			'inline_tip'   => __( 'Enter tag names or tag IDs, separated by commas', 'wp-fusion' ),
		);

		if ( wp_fusion()->crm->supports( 'lists' ) ) {

			$fields[] = array(
				'key'          => 'lists',
				'require_list' => false,
				'label'        => __( 'Lists', 'wp-fusion' ),
				'tips'         => sprintf( __( 'Select one or more %s lists to add contacts to.', 'wp-fusion' ), wp_fusion()->crm->name ),
				'component'    => 'select',
				'is_multiple'  => true,
				'options'      => wpf_get_option( 'available_lists' ),
			);
		}

		// Event trigger field
		$fields[] = array(
			'require_list'   => false,
			'required'       => true,
			'key'            => 'event_trigger',
			'options'        => array(
				'after_booking_scheduled'    => __( 'Booking Confirmed', 'fluent_booking' ),
				'booking_schedule_completed' => __( 'Booking Completed', 'fluent_booking' ),
				'after_booking_rescheduled'  => __( 'Booking Rescheduled', 'fluent_booking' ),
				'booking_schedule_cancelled' => __( 'Booking Canceled', 'fluent_booking' ),
			),
			'tips'           => __( 'Select in which booking stage you want to trigger this feed.', 'fluent_booking' ),
			'label'          => __( 'Event Trigger', 'fluent_booking' ),
			'component'      => 'checkbox-multiple-text',
			'checkbox_label' => __( 'Event Trigger For This Feed', 'fluent_booking' ),
		);

		// Enabled status field
		$fields[] = array(
			'require_list'   => false,
			'key'            => 'enabled',
			'label'          => 'Status',
			'component'      => 'checkbox-single',
			'checkbox_label' => 'Enable This feed',
		);

		return array(
			'fields'              => $fields,
			'button_require_list' => false,
			'integration_title'   => $this->title,
		);
	}

	/**
	 * Get CRM fields.
	 *
	 * @since 3.41.39
	 *
	 * @param int|bool $list The list.
	 * @param int|bool $list_id List ID.
	 * @param int      $calendar_event_id Calendar Event ID.
	 * @return array The CRM fields.
	 */
	public function getMergeFields( $list, $list_id, $calendar_event_id ) {

		return wp_fusion()->settings->get_crm_fields_flat();
	}


	/**
	 * Handle rescheduled booking.
	 *
	 * @since 3.45.7
	 *
	 * @param object $booking          The booking object.
	 * @param object $previous_booking The previous booking object.
	 * @param object $calendar_event   The calendar event object.
	 */
	public function handle_rescheduled( $booking, $previous_booking, $calendar_event ) {

		$feeds = Meta::where( 'object_id', $calendar_event->id )->where( 'key', $this->settingsKey )->get();

		if ( empty( $feeds ) ) {
			return;
		}

		$notification_service = new GlobalNotificationService();
		$enabled_feeds        = $notification_service->getEnabledFeeds( $feeds, $booking );

		if ( empty( $enabled_feeds ) ) {
			return;
		}

		foreach ( $enabled_feeds as $feed ) {
			$settings = $feed['settings'];

			if ( empty( $settings['event_trigger'] ) ) {
				continue;
			}

			// Check if this feed should be triggered for rescheduling.
			if ( ! in_array( 'after_booking_rescheduled', $settings['event_trigger'] ) ) {
				continue;
			}

			$feed_data = array(
				'id'              => $feed['id'],
				'settings'        => $settings,
				'processedValues' => EditorShortCodeParser::parse( $settings, $booking ),
				'key'             => $this->settingsKey,
			);

			$this->notify( $feed_data, $booking, $calendar_event );
		}
	}


	/**
	 * Handle form submission
	 *
	 * @since 3.41.39
	 *
	 * @param array<string,mixed>                   $feed Feed.
	 * @param FluentBooking\App\Models\Booking      $booking Booking.
	 * @param FluentBooking\App\Models\CalendarSlot $calendar_event Calendar Event.
	 */
	public function notify( $feed, $booking, $calendar_event ) {

		$data           = $feed['processedValues'];
		$primary_values = Arr::only( $data, array( 'first_name', 'last_name', 'email' ) );
		$other_values   = wp_list_pluck( $data['other_fields'], 'item_value', 'label' );
		$all_values     = wp_parse_args( $primary_values, $other_values );

		$input_tags = array_filter( str_getcsv( $data['tags'], ',' ) );

		$apply_tags = array();

		// Get tags to apply.
		foreach ( $input_tags as $tag ) {

			$tag_id = wp_fusion()->user->get_tag_id( $tag );

			if ( false === $tag_id ) {
				wpf_log( 'notice', 0, 'Warning: ' . $tag . ' is not a valid tag name or ID.' );
				continue;
			}

			$apply_tags[] = $tag_id;
		}

		if ( isset( $data['lists'] ) ) {
			$lists = array_filter( $data['lists'] );
		} else {
			$lists = array();
		}

		$args = array(
			'email_address'    => $all_values['email'],
			'update_data'      => $all_values,
			'apply_tags'       => $apply_tags,
			'apply_lists'      => $lists,
			'add_only'         => false,
			'integration_slug' => 'fluent_booking',
			'integration_name' => 'FluentBooking',
			'form_id'          => $calendar_event->id,
			'form_title'       => $calendar_event->title,
			'form_edit_link'   => admin_url( 'a?page=fluent-booking#/scheduled-events?period=upcoming&booking_id=' . $booking->id ),
			'entry_id'         => $booking->id,
		);

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

		if ( is_wp_error( $contact_id ) ) {
			$this->addLog( 'WP Fusion Integration Error', $contact_id->get_error_message(), $booking->id, 'error' );
		} else {
			$this->addLog( 'WP Fusion Integration Success', 'Contact added to WP Fusion Feed. Contact ID: ' . $contact_id, $booking->id, 'info' );
			$booking->updateMeta( 'wpf_contact_id', $contact_id );
		}
	}
}

new WPF_FluentBooking();
