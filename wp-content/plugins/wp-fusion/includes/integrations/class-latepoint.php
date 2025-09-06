<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_LatePoint extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.42.12
	 * @var string $slug
	 */

	public $slug = 'latepoint';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.42.12
	 * @var string $name
	 */
	public $name = 'LatePoint';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.42.12
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/events/latepoint/';

	/**
	 * Gets things started.
	 *
	 * @since 3.42.12
	 */
	public function init() {
		add_action( 'latepoint_service_saved', array( $this, 'service_saved' ), 10, 2 );
		add_action( 'latepoint_booking_created', array( $this, 'booking_created' ) );
		add_action( 'latepoint_booking_updated', array( $this, 'booking_updated' ) );

		add_action( 'latepoint_service_form_after', array( $this, 'add_service_meta_box' ) );
	}


	/**
	 * Booking is updated.
	 *
	 * @since 3.42.12
	 * @param object $booking
	 */
	public function booking_updated( $booking ) {

		if ( $booking->status !== 'completed' && $booking->status !== 'cancelled' ) {
			return;
		}

		// Apply tags.
		$apply_tags = array();

		$service_id = $booking->service_id;
		$service    = new OsServiceModel( $service_id );
		$settings   = $service->get_meta_by_key( 'wpf-settings', array() );
		$settings   = json_decode( $settings, true );

		if ( ! empty( $settings ) ) {
			if ( $booking->status === 'completed' && ! empty( $settings['apply_tags_completed'] ) ) {
				$apply_tags = array_merge( $apply_tags, $settings['apply_tags_completed'] );
			}

			if ( $booking->status === 'cancelled' && ! empty( $settings['apply_tags_cancelled'] ) ) {
				$apply_tags = array_merge( $apply_tags, $settings['apply_tags_cancelled'] );
			}
		}

		$user_id = $booking->customer->wordpress_user_id;

		if ( empty( $user_id ) ) {
			$contact_data = $this->get_contact_data( $booking );

			$contact_id = wp_fusion()->crm->get_contact_id( $contact_data['user_email'] );

			if ( false == $contact_id ) {

				$contact_id = wp_fusion()->crm->add_contact( $contact_data );

			}

			if ( is_wp_error( $contact_id ) ) {

				wpf_log( $contact_id->get_error_code(), 0, 'Error adding contact: ' . $contact_id->get_error_message() );
				return;

			}
		}

		if ( ! empty( $apply_tags ) ) {

			if ( ! empty( $user_id ) ) {

				wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			} else {

				wpf_log( 'info', $user_id, 'LatePoint guest booking applying tag(s): ', array( 'tag_array' => $apply_tags ) );

				wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

			}
		}
	}

	/**
	 * Get contact data from booking.
	 *
	 * @since 3.42.12
	 * @param object $booking
	 * @return array
	 */
	public function get_contact_data( $booking ) {
		$contact_data = array(
			'first_name'         => $booking->customer->first_name,
			'last_name'          => $booking->customer->last_name,
			'user_email'         => $booking->customer->email,
			'_lp_phone'          => $booking->customer->phone,
			'_lp_comments'       => $booking->customer->notes,
			'_lp_start_date'     => $booking->start_date,
			'_lp_end_date'       => $booking->end_date,
			'_lp_start_time'     => $booking->start_time,
			'_lp_end_time'       => $booking->end_time,
			'_lp_payment_status' => $booking->payment_status,
			'_lp_payment_method' => $booking->payment_method,
			'_lp_coupon_code'    => $booking->coupon_code,
			'_lp_price'          => $booking->price,
			'_lp_status'         => $booking->status,
			'_lp_duration'       => $booking->duration,
			'_lp_service_name'   => $booking->service->name,
			'_lp_agent_name'     => $booking->agent->first_name . ' ' . $booking->agent->last_name,
			'_lp_agent_email'    => $booking->agent->email,
		);

		// Booking Custom Fields.
		if ( class_exists( 'OsCustomFieldsHelper' ) ) {
			$custom_fields_for_booking = OsCustomFieldsHelper::get_custom_fields_arr( 'booking', 'all' );
			if ( ! empty( $custom_fields_for_booking ) ) {
				foreach ( $custom_fields_for_booking as $custom_field ) {
					$field_id = $custom_field['id'];
					if ( isset( $booking->custom_fields[ $field_id ] ) ) {
						$contact_data[ '_lp_' . $field_id ] = $booking->custom_fields[ $field_id ];
					}
				}
			}
		}

		if ( class_exists( 'OsCoreFieldsHelper' ) ) {
			$core_fields_for_customer = OsCoreFieldsHelper::get_core_fields_arr( 'customer', 'all' );
			foreach ( array( '_lp_AAAA', '_lp_BBBB', '_lp_CCCC' ) as $core_field ) {
				if ( isset( $core_fields_for_customer[ $core_field ] ) ) {
					$contact_data[ $core_field ] = $core_fields_for_customer[ $core_field ];
				}
			}
		}

		// Customer Custom Fields.
		if ( class_exists( 'OsCustomFieldsHelper' ) ) {
			$custom_fields_for_customer = OsCustomFieldsHelper::get_custom_fields_arr( 'customer', 'all' );
			if ( ! empty( $custom_fields_for_customer ) ) {
				foreach ( $custom_fields_for_customer as $custom_field ) {
					$field_id = $custom_field['id'];
					if ( isset( $booking->custom_fields[ $field_id ] ) ) {
						$contact_data[ '_lp_' . $field_id ] = $booking->customer->custom_fields[ $field_id ];
					}
				}
			}
		}

		return $contact_data;
	}

	/**
	 * Booking is created.
	 *
	 * @since 3.42.12
	 * @param object $booking
	 */
	public function booking_created( $booking ) {
		$user_id = $booking->customer->wordpress_user_id;

		$contact_data = $this->get_contact_data( $booking );

		if ( ! empty( $user_id ) ) {

			wp_fusion()->user->push_user_meta( $user_id, $contact_data );

		} else {

			wpf_log( 'info', 0, 'LatePoint guest booking:', array( 'meta_array' => $contact_data ) );

			$contact_id = wp_fusion()->crm->get_contact_id( $contact_data['user_email'] );

			if ( ! $contact_id ) {

				$contact_id = wp_fusion()->crm->add_contact( $contact_data );

			} else {

				wp_fusion()->crm->update_contact( $contact_id, $contact_data );

			}

			if ( is_wp_error( $contact_id ) ) {

				wpf_log( $contact_id->get_error_code(), 0, 'Error adding contact: ' . $contact_id->get_error_message() );
				return;

			}

			update_post_meta( $booking->id, WPF_CONTACT_ID_META_KEY, $contact_id );

		}

		// Apply tags.
		$apply_tags = array();

		$service_id = $booking->service_id;
		$service    = new OsServiceModel( $service_id );
		$settings   = $service->get_meta_by_key( 'wpf-settings', array() );

		if ( is_string( $settings ) ) { // settings are stored as json.
			$settings = json_decode( $settings, true );
		}

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_booked'] ) ) {
			$apply_tags = array_merge( $apply_tags, $settings['apply_tags_booked'] );
		}

		if ( ! empty( $apply_tags ) ) {

			if ( ! empty( $user_id ) ) {

				wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			} else {

				wpf_log( 'info', $user_id, 'LatePoint guest booking applying tag(s): ', array( 'tag_array' => $apply_tags ) );

				wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

			}
		}
	}

	/**
	 * Service is saved.
	 *
	 * @since 3.42.12
	 * @param object  $service
	 * @param boolean $is_new_record
	 */
	public function service_saved( $service, $is_new_record ) {
		$post_params = array();
		parse_str( $_POST['params'], $post_params );
		if ( ! isset( $post_params['wpf-settings'] ) ) {
			return;
		}

		$service->save_meta_by_key( 'wpf-settings', json_encode( $post_params['wpf-settings'] ) );
	}

	/**
	 * Adds LatePoint field group to meta fields list
	 *
	 * @since 3.42.12
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['latepoint'] = array(
			'title' => __( 'LatePoint', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/events/latepoint/',
		);

		return $field_groups;
	}

	/**
	 * Set field keys / labels for LatePoint fields
	 *
	 * @since 3.42.12
	 * @return array Settings
	 */
	public function add_meta_fields( $meta_fields ) {

		$meta_fields['_lp_start_date'] = array(
			'label'  => 'Booking Start Date',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_end_date'] = array(
			'label'  => 'Booking End Date',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_start_time'] = array(
			'label'  => 'Booking Start Time',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_end_time'] = array(
			'label'  => 'Booking End Time',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_payment_method'] = array(
			'label'  => 'Booking Payment Method',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_payment_status'] = array(
			'label'  => 'Booking Payment Status',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_booking_status'] = array(
			'label'  => 'Booking Status',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_booking_duration'] = array(
			'label'  => 'Booking Duration',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_coupon_code'] = array(
			'label'  => 'Booking Coupon Code',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_price'] = array(
			'label'  => 'Booking Price',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_subtotal'] = array(
			'label'  => 'Booking Subtotal',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_service_name'] = array(
			'label'  => 'Service Name',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_agent_name'] = array(
			'label'  => 'Agent Name',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_agent_email'] = array(
			'label'  => 'Agent Email Address',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_phone'] = array(
			'label'  => 'Customer Phone',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		$meta_fields['_lp_comments'] = array(
			'label'  => 'Customer Comments',
			'group'  => 'latepoint',
			'pseudo' => true,
		);

		// Booking Custom Fields.
		if ( class_exists( 'OsCustomFieldsHelper' ) ) {
			$custom_fields_for_booking = OsCustomFieldsHelper::get_custom_fields_arr( 'booking', 'all' );
			if ( ! empty( $custom_fields_for_booking ) ) {
				foreach ( $custom_fields_for_booking as $custom_field ) {
					$meta_fields[ '_lp_' . $custom_field['id'] ] = array(
						'label'  => 'Booking ' . $custom_field['label'],
						'group'  => 'latepoint',
						'pseudo' => true,
					);
				}
			}
		}

		// Customer Custom Fields.
		if ( class_exists( 'OsCustomFieldsHelper' ) ) {
			$custom_fields_for_customer = OsCustomFieldsHelper::get_custom_fields_arr( 'customer', 'all' );
			if ( ! empty( $custom_fields_for_customer ) ) {
				foreach ( $custom_fields_for_customer as $custom_field ) {
					$meta_fields[ '_lp_' . $custom_field['id'] ] = array(
						'label' => 'Customer ' . $custom_field['label'],
						'group' => 'latepoint',
					);
				}
			}
		}

		return $meta_fields;
	}


	/**
	 * Add service metabox in service type.
	 *
	 * @since 3.42.12
	 * @param object $service
	 */
	public function add_service_meta_box( $service ) {

		$settings = array(
			'apply_tags_booked'    => array(),
			'apply_tags_cancelled' => array(),
			'apply_tags_completed' => array(),
		);

		if ( $service->get_meta_by_key( 'wpf-settings' ) ) {
			$settings = array_merge( $settings, json_decode( $service->get_meta_by_key( 'wpf-settings' ), true ) );
		}

		?>
		<div class="white-box">
			<div class="white-box-header">
				<div class="os-form-sub-header"><h3><?php esc_html_e( 'WP Fusion', 'wp-fusion' ); ?></h3></div>
			</div>
			<div class="white-box-content">
				<div class="os-row">
				<div class="os-col-lg-12">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="apply_tags_booked"><?php esc_html_e( 'Apply Tags - Booked', 'wp-fusion' ); ?></label></th>
								<td>

								<?php
									$args = array(
										'setting'   => $settings['apply_tags_booked'],
										'meta_name' => 'wpf-settings',
										'field_id'  => 'apply_tags_booked',
									);

									wpf_render_tag_multiselect( $args );
									?>

								<span class="description"><?php esc_html_e( 'These tags will be applied when someone books this service.', 'wp-fusion' ); ?></span>
								</td>
							</tr>

							<tr>
								<th scope="row"><label for="apply_tags_cancelled"><?php esc_html_e( 'Apply Tags - Cancelled', 'wp-fusion' ); ?></label></th>
								<td>

								<?php
									$args = array(
										'setting'   => $settings['apply_tags_cancelled'],
										'meta_name' => 'wpf-settings',
										'field_id'  => 'apply_tags_cancelled',
									);

									wpf_render_tag_multiselect( $args );
									?>

								<span class="description"><?php esc_html_e( 'These tags will be applied when someone cancels their booking to this service.', 'wp-fusion' ); ?></span>
								</td>
							</tr>


							<tr>
								<th scope="row"><label for="apply_tags_completed"><?php esc_html_e( 'Apply Tags - Completed', 'wp-fusion' ); ?></label></th>
								<td>

								<?php
									$args = array(
										'setting'   => $settings['apply_tags_completed'],
										'meta_name' => 'wpf-settings',
										'field_id'  => 'apply_tags_completed',
									);

									wpf_render_tag_multiselect( $args );
									?>

								<span class="description"><?php esc_html_e( 'These tags will be applied when a booking to this service is marked completed.', 'wp-fusion' ); ?></span>
								</td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
}

new WPF_LatePoint();
