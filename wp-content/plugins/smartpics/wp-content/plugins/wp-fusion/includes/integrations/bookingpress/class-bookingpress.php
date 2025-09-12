<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WP Fusion BookingPress integration.
 *
 * @since 3.45.4
 */
class WPF_BookingPress extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.45.4
	 * @var string $slug
	 */

	public $slug = 'bookingpress';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.45.4
	 * @var string $name
	 */
	public $name = 'BookingPress';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.45.4
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/events/bookingpress/';

	/**
	 * The integration class.
	 *
	 * @since 3.45.4
	 * @var BookingPress_WP_Fusion_Integration $integration
	 */
	public $integration;

	/**
	 * Gets things started
	 *
	 * @since 3.45.4
	 */
	public function init() {

		require_once __DIR__ . '/class-bookingpress-wp-fusion-integration.php';
		$this->integration = new BookingPress_WP_Fusion_Integration();

		add_action( 'bookingpress_add_service_field_outside', array( $this, 'add_service_field' ) );

		add_filter( 'bookingpress_modify_edit_service_data', array( $this, 'get_service_data' ), 10, 2 );

		add_filter( 'bookingpress_after_add_update_service', array( $this, 'save_service_settings' ), 10, 3 );

		add_action( 'bookingpress_edit_service_more_vue_data', array( $this, 'add_service_data' ) );

		add_action( 'bookingpress_after_open_add_service_model', array( $this, 'clear_service_apply_tags' ) );

		add_filter( 'bookingpress_modify_service_data_fields', array( $this, 'bookingpress_service_vue_data_fields' ) );

		add_action( 'bookingpress_after_book_appointment', array( $this, 'after_appointment_booked' ) );

		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_fields' ) );
	}

	/**
	 * Clear the service apply tags.
	 *
	 * @since 3.45.4
	 *
	 * @return void
	 */
	public function clear_service_apply_tags() {
		?>
			if(action == 'add') {
				vm.service.wpf_apply_tags = [];        
			}            
		<?php
	}


	/**
	 * Add the service apply tags to the service data fields.
	 *
	 * @since 3.45.4
	 *
	 * @param array $bookingpress_services_vue_data_fields The service data fields.
	 * @return array The modified service data fields.
	 */
	public function bookingpress_service_vue_data_fields( $bookingpress_services_vue_data_fields ) {
		$tags                             = wp_fusion()->settings->get_available_tags_flat( false );
		$bookingpress_wpf_apply_tags_item = array_map(
			function ( $key, $label ) {
				return array(
					'value' => $key,
					'label' => $label,
				);
			},
			array_keys( $tags ),
			array_values( $tags )
		);

		$bookingpress_services_vue_data_fields['wpfApplyTagsOptions']       = $bookingpress_wpf_apply_tags_item;
		$bookingpress_services_vue_data_fields['service']['wpf_apply_tags'] = array();

		return $bookingpress_services_vue_data_fields;
	}


	/**
	 * Add the service data to the vue service data fields.
	 *
	 * @since 3.45.4
	 *
	 * @return void
	 */
	public function add_service_data() {
		?>
		// Convert the raw values to the proper format
		if (response.data.wpf_apply_tags) {
			const selectedTags = response.data.wpf_apply_tags.map(tagId => tagId.toString());
			vm2.service.wpf_apply_tags = selectedTags;
		} else {
			vm2.service.wpf_apply_tags = [];
		}
		vm2.wpfApplyTagsOptions = response.data.wpfApplyTagsOptions;
		<?php
	}

	/**
	 * Add service field.
	 *
	 * @since 3.45.4
	 *
	 * @return void
	 */
	public function add_service_field() {
		?>
		<div class="bpa-form-body-row bpa-deposit-payment__heading"></div>
			<div class="db-sec-left bpa-service-section-heading">
				<span class="bpa-serv__heading"> <?php esc_html_e( 'WP Fusion Integration', 'wp-fusion' ); ?> </span>
			</div>
			<div class="bpa-form-body-row">
			<el-row :gutter="32">


				<el-col :xs="24" :sm="24" :md="24" :lg="08" :xl="08">
					<el-form-item prop="wpf_apply_tags">
						<template #label>
							<span class="bpa-form-label"><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?></span>
						</template>

						<el-select 
							class="bpa-form-control" 
							v-model="service.wpf_apply_tags" 
							multiple 
							filterable
							placeholder="<?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?>"
							popper-class="bpa-el-select--is-with-modal">
							<el-option
								v-for="item in wpfApplyTagsOptions"
								:key="item.value"
								:label="item.label"
								:value="item.value">
								{{ item.label }}
							</el-option>
						</el-select>
					</el-form-item>
				</el-col>


			</el-row>
		</div>	
		<?php
	}

	/**
	 * Add service data.
	 * The data for each service is loaded from the database and passed through this filter.
	 *
	 * @since 3.45.4
	 *
	 * @param array $response The response data.
	 * @param int   $service_id The service ID.
	 *
	 * @return array The modified response data.
	 */
	public function get_service_data( $response, $service_id ) {
		global $bookingpress_services;
		$apply_tags_value = $bookingpress_services->bookingpress_get_service_meta( $service_id, 'wpf_apply_tags' );

		$apply_tags_value = maybe_unserialize( $apply_tags_value );
		if ( empty( $apply_tags_value ) ) {
			$apply_tags_value = array();
		}

		$tags = wp_fusion()->settings->get_available_tags_flat( false );

		// Ensure we're passing string values.
		$response['wpf_apply_tags'] = array_map( 'strval', $apply_tags_value );

		$response['wpfApplyTagsOptions'] = array_map(
			function ( $key, $label ) {
				return array(
					'value' => (string) $key, // Ensure value is a string
					'label' => $label,
				);
			},
			array_keys( $tags ),
			array_values( $tags )
		);

		return $response;
	}

	/**
	 * Save service settings.
	 *
	 * @since 3.45.4
	 *
	 * @param array $response The response data.
	 * @param int   $service_id The service ID.
	 * @param array $posted_data The posted data.
	 *
	 * @return array The modified response data.
	 */
	public function save_service_settings( $response, $service_id, $posted_data ) {
		global $bookingpress_services;
		$wpf_apply_tags = ! empty( $posted_data['wpf_apply_tags'] ) ? serialize( $posted_data['wpf_apply_tags'] ) : '';
		$bookingpress_services->bookingpress_add_service_meta( $service_id, 'wpf_apply_tags', $wpf_apply_tags );

		return $response;
	}

	/**
	 * Apply tags after an appointment is booked.
	 *
	 * @since 3.45.4
	 *
	 * @param int $appointment_id The appointment ID.
	 */
	public function after_appointment_booked( $appointment_id ) {

		global $wpdb;
		$appointment_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
			 FROM {$wpdb->prefix}bookingpress_appointment_bookings 
			 WHERE bookingpress_appointment_booking_id = %d",
				$appointment_id
			),
			ARRAY_A
		);

		if ( empty( $appointment_data ) ) {
			return;
		}

		$service_id     = $appointment_data['bookingpress_service_id'];
		$customer_email = $appointment_data['bookingpress_customer_email'];

		if ( empty( $service_id ) ) {
			return;
		}

		$apply_tags = $wpdb->get_var( $wpdb->prepare( "SELECT bookingpress_servicemeta_value FROM {$wpdb->prefix}bookingpress_servicesmeta WHERE bookingpress_servicemeta_name = 'wpf_apply_tags' AND bookingpress_service_id = %d", $service_id ) );

		if ( empty( $apply_tags ) ) {
			return;
		}

		$apply_tags = maybe_unserialize( $apply_tags );

		if ( empty( $apply_tags ) ) {
			return;
		}

		$update_data = array(
			'first_name' => ( isset( $appointment_data['bookingpress_customer_firstname'] ) ? $appointment_data['bookingpress_customer_firstname'] : '' ),
			'last_name'  => ( isset( $appointment_data['bookingpress_customer_lastname'] ) ? $appointment_data['bookingpress_customer_lastname'] : '' ),
			'user_email' => $customer_email,
			'phone'      => ( isset( $appointment_data['bookingpress_customer_phone'] ) ? $appointment_data['bookingpress_customer_phone'] : '' ),
		);

		$update_data = array_merge( $update_data, $appointment_data );

		switch ( intval( $appointment_data['bookingpress_appointment_status'] ) ) {
			case 1:
				$appointment_data['bookingpress_appointment_status'] = 'Approved';
				break;
			case 2:
				$appointment_data['bookingpress_appointment_status'] = 'Pending';
				break;
			case 3:
				$appointment_data['bookingpress_appointment_status'] = 'Canceled';
				break;
			case 4:
				$appointment_data['bookingpress_appointment_status'] = 'Rejected';
				break;
			case 5:
				$appointment_data['bookingpress_appointment_status'] = 'No Show';
				break;
			case 6:
				$appointment_data['bookingpress_appointment_status'] = 'Completed';
				break;
		}

		$user = get_user_by( 'email', $customer_email );

		if ( $user ) {

			wp_fusion()->user->push_user_meta( $user->ID, $update_data );

			wp_fusion()->user->apply_tags( $apply_tags, $user->ID );

		} else {

			// Guests, create a new contact.

			$contact_id = $this->guest_registration( $customer_email, $update_data );

			if ( false === $contact_id ) {
				return;
			}

			wpf_log( 'info', 0, 'Applying tags for appointment #' . $appointment_id . ' to contact #' . $contact_id . ': ', array( 'tag_array' => $apply_tags ) );

			wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

		}
	}

	/**
	 * Adds BookingPress field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['bookingpress_appointment'] = array(
			'title'  => __( 'BookingPress Appointment', 'wp-fusion' ),
			'fields' => array(),
		);

		$field_groups['bookingpress_service'] = array(
			'title'  => __( 'BookingPress Service', 'wp-fusion' ),
			'fields' => array(),
		);

		return $field_groups;
	}


	/**
	 * Adds BookingPress meta fields to meta fields list
	 *
	 * @access  public
	 * @return  array Meta fields
	 */
	public function add_meta_fields( $meta_fields ) {

		// Appointment fields
		$meta_fields['bookingpress_appointment_date'] = array(
			'label'  => 'Appointment Date',
			'type'   => 'date',
			'group'  => 'bookingpress_appointment',
			'pseudo' => true,
		);

		$meta_fields['bookingpress_appointment_time'] = array(
			'label'  => 'Appointment Time',
			'type'   => 'text',
			'group'  => 'bookingpress_appointment',
			'pseudo' => true,
		);

		$meta_fields['bookingpress_appointment_status'] = array(
			'label'  => 'Appointment Status',
			'type'   => 'text',
			'group'  => 'bookingpress_appointment',
			'pseudo' => true,
		);

		$meta_fields['bookingpress_appointment_payment_status'] = array(
			'label'  => 'Payment Status',
			'type'   => 'text',
			'group'  => 'bookingpress_appointment',
			'pseudo' => true,
		);

		$meta_fields['bookingpress_appointment_amount'] = array(
			'label'  => 'Appointment Amount',
			'type'   => 'text',
			'group'  => 'bookingpress_appointment',
			'pseudo' => true,
		);

		$meta_fields['bookingpress_appointment_note'] = array(
			'label'  => 'Appointment Note',
			'type'   => 'text',
			'group'  => 'bookingpress_appointment',
			'pseudo' => true,
		);

		// Service fields
		$meta_fields['bookingpress_service_name'] = array(
			'label'  => 'Service Name',
			'type'   => 'text',
			'group'  => 'bookingpress_service',
			'pseudo' => true,
		);

		$meta_fields['bookingpress_service_duration'] = array(
			'label'  => 'Service Duration',
			'type'   => 'text',
			'group'  => 'bookingpress_service',
			'pseudo' => true,
		);

		$meta_fields['bookingpress_service_price'] = array(
			'label'  => 'Service Price',
			'type'   => 'text',
			'group'  => 'bookingpress_service',
			'pseudo' => true,
		);

		$meta_fields['bookingpress_service_category'] = array(
			'label'  => 'Service Category',
			'type'   => 'text',
			'group'  => 'bookingpress_service',
			'pseudo' => true,
		);

		return $meta_fields;
	}
}

new WPF_BookingPress();
