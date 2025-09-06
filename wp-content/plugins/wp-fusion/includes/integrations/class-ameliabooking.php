<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WPF_AmeliaBooking extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.42.10
	 * @var string $slug
	 */

	public $slug = 'ameliabooking';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.42.10
	 * @var string $name
	 */
	public $name = 'Amelia';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.42.10
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/events/amelia/';

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */
	public function init() {

		// Admin settings.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 40 );

		// Sync new appointments.
		add_action( 'amelia_after_booking_added', array( $this, 'process_booking' ) );

		// Add settings.
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		// Batch operations.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_amelia_booking_appointments_init', array( $this, 'batch_init_appointments' ) );
		add_action( 'wpf_batch_amelia_booking_appointments', array( $this, 'batch_step_appointments' ) );
		add_filter( 'wpf_batch_amelia_booking_events_init', array( $this, 'batch_init_events' ) );
		add_action( 'wpf_batch_amelia_booking_events', array( $this, 'batch_step_events' ) );
	}


	/**
	 * Booking Created.
	 *
	 * Syncs new appointments and events to CRM.
	 *
	 * @since 3.42.10
	 *
	 * @param array $data The booking data.
	 */
	public function process_booking( $data ) {

		if ( ! wpf_get_option( 'amelia_guests', true ) && ! wpf_is_user_logged_in() ) {
			return;
		}

		// Get the customer data from the booking
		$customer = $data['customer'];
		$booking  = $data['booking'];

		$customer_data = array(
			'user_email'   => $customer['email'],
			'first_name'   => $customer['firstName'],
			'last_name'    => $customer['lastName'],
			'phone_number' => $customer['phone'],
		);

		// Add type-specific data
		if ( 'appointment' === $data['type'] ) {
			$customer_data['appointment_date_time'] = $data['appointment']['bookingStart'];
			$customer_data['service_name']          = $data['bookable']['name'];
			$settings                               = json_decode( $data['bookable']['settings'], true );
		} else {
			$customer_data['event_date_time'] = $data['event']['periods'][0]['periodStart'];
			$customer_data['event_name']      = $data['event']['name'];
			$settings                         = json_decode( $data['event']['settings'], true );
		}

		// Add custom fields if they exist
		if ( ! empty( $booking['customFields'] ) ) {
			$custom_fields = json_decode( $booking['customFields'], true );
			foreach ( $custom_fields as $field ) {
				$key                   = str_replace( ' ', '_', strtolower( $field['label'] ) );
				$customer_data[ $key ] = $field['value'];
			}
		}

		// Create/update contact
		$user = get_user_by( 'email', $customer_data['user_email'] );

		if ( false !== $user && wpf_get_contact_id( $user->ID ) ) {
			// Registered users
			wp_fusion()->user->push_user_meta( $user->ID, $customer_data );
			$contact_id = wp_fusion()->user->get_contact_id( $user->ID );
		} else {
			// Guest checkout
			$contact_id = $this->guest_registration( $customer_data['user_email'], $customer_data );
		}

		// Apply tags if configured
		if ( ! empty( $settings['apply_tags'] ) ) {
			if ( empty( $user ) ) {
				// Guest checkout
				$type = 'appointment' === $data['type'] ? 'appointment' : 'event';
				wpf_log( 'info', 0, "Applying tags for guest {$type} booking to contact #{$contact_id}: ", array( 'tag_array' => $settings['apply_tags'] ) );
				wp_fusion()->crm->apply_tags( $settings['apply_tags'], $contact_id );
			} else {
				// Registered users
				wp_fusion()->user->apply_tags( $settings['apply_tags'], $user->ID );
			}
		}

		$this->mark_complete( $booking['id'], $contact_id );
	}


	/**
	 * Mark Event Complete
	 * Marks an event booking as complete in Amelia.
	 *
	 * @since 3.44.19
	 *
	 * @param int $booking_id The booking ID.
	 */
	public function mark_complete( $booking_id, $contact_id ) {

		global $wpdb;

		$table_name = $wpdb->prefix . 'amelia_customer_bookings';
		$booking    = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT info FROM $table_name WHERE id = %d",
				$booking_id
			)
		);

		if ( ! $booking ) {
			return;
		}

		$info = json_decode( $booking->info, true );

		if ( ! is_array( $info ) ) {
			$info = array();
		}

		$info['wpf_synced']     = current_time( 'mysql' );
		$info['wpf_contact_id'] = $contact_id;

		$wpdb->update(
			$table_name,
			array(
				'info' => wp_json_encode( $info ),
			),
			array(
				'id' => $booking_id,
			)
		);
	}

	/**
	 * Add Meta Field Group
	 * Adds field group to meta fields list.
	 *
	 * @since 3.42.10
	 *
	 * @param array $field_groups Field groups.
	 *
	 * @return array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['ameliabooking'] = array(
			'title' => __( 'Amelia Booking', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/events/amelia/',
		);

		return $field_groups;
	}


	/**
	 * Prepare Meta Fields
	 * Adds meta fields to WPF contact fields list.
	 *
	 * @since 3.42.10
	 *
	 * @param array $meta_fields Meta fields.
	 *
	 * @return array Meta Fields
	 */
	public function add_meta_fields( $meta_fields = array() ) {

		global $wpdb;

		$meta_fields['phone_number'] = array(
			'label' => 'Phone Number',
			'type'  => 'text',
			'group' => 'ameliabooking',
		);

		$meta_fields['service_name'] = array(
			'label' => 'Service Name',
			'type'  => 'text',
			'group' => 'ameliabooking',
		);

		$meta_fields['appointment_date_time'] = array(
			'label' => 'Appointment Date / Time',
			'type'  => 'date',
			'group' => 'ameliabooking',
		);

		$meta_fields['event_name'] = array(
			'label' => 'Event Name',
			'type'  => 'text',
			'group' => 'ameliabooking',
		);

		$meta_fields['event_date_time'] = array(
			'label' => 'Event Date / Time',
			'type'  => 'date',
			'group' => 'ameliabooking',
		);

		// Get all of the Amelia custom fields.
		$table = $wpdb->prefix . 'amelia_custom_fields';

		$query         = $wpdb->prepare( "SELECT id, label, type FROM $table" );
		$custom_fields = $wpdb->get_results( $query );

		foreach ( $custom_fields as $id => $field ) {
			$key = str_replace( ' ', '_', strtolower( $field->label ) );

			if ( 'datetime' === $field->type ) {
				$field->type = 'date';
			}

			$meta_fields[ $key ] = array(
				'label' => $field->label,
				'type'  => $field->type,
				'group' => 'ameliabooking',
			);
		}

		return $meta_fields;
	}

	/**
	 * Admin Menu
	 * Creates WPF submenu item.
	 *
	 * @since 3.42.10
	 */
	public function admin_menu() {

		$id = add_submenu_page(
			'amelia',
			/* translators: %s: CRM Name */
			sprintf( __( '%s Integration', 'wp-fusion' ), wp_fusion()->crm->name ),
			__( 'WP Fusion', 'wp-fusion' ),
			'manage_options',
			'wpamelia-wpf-settings',
			array( $this, 'render_admin_menu' ),
			14,
		);

		add_action( 'load-' . $id, array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue Scripts
	 * Enqueues WPF scripts and styles.
	 *
	 * @since 3.42.10
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css' );
		wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css' );
	}

	/**
	 * Get User ID.
	 * Gets a customer's user ID from customer ID.
	 *
	 * @since 3.42.9
	 *
	 * @param int $customer_id The customer ID.
	 *
	 * @return int|null The user ID or null if the customer doesn't have a user account.
	 */
	public function get_user_id( $customer_id ) {

		global $wpdb;

		$amelia_users_table = $wpdb->prefix . 'amelia_users';

		// Get the customer's user ID.
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT externalId FROM {$amelia_users_table} WHERE id = %d",
				$customer_id
			)
		);
	}

	/**
	 * Get Service ID.
	 * Gets the service ID by appointment ID.
	 *
	 * @since 3.42.9
	 *
	 * @param int $appointment_id The appointment ID.
	 *
	 * @return int $service_id The service ID.
	 */
	public function get_service_id( $appointment_id ) {

		global $wpdb;

		$appointments_table = $wpdb->prefix . 'amelia_appointments';

		// Get the service ID.
		$service_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT serviceId FROM {$appointments_table} WHERE id = %d",
				$appointment_id
			)
		);

		return $service_id;
	}

	/**
	 * Get Service Name
	 * Gets the service name by service ID.
	 *
	 * @since 3.42.9
	 *
	 * @param int $service_id The service ID.
	 *
	 * @return string $service_name The service name.
	 */
	public function get_service_name( $service_id ) {

		global $wpdb;

		$services_table = $wpdb->prefix . 'amelia_services';

		// Get the name of the service.
		$service_name = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT name FROM {$services_table} WHERE id = %d",
				$service_id
			)
		);

		return $service_name;
	}

	/**
	 * Get Service Settings
	 * Gets the tags to apply for a service by service ID.
	 *
	 * @since 3.42.9
	 *
	 * @param int $service_id The service ID.
	 *
	 * @return array $settings The service settings.
	 */
	public function get_service_settings( $service_id ) {

		global $wpdb;

		$table = $wpdb->prefix . 'amelia_services';

		// Get the settings for the service.
		$service_settings = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT settings FROM {$table} WHERE id = %d",
				$service_id
			)
		);

		$settings = json_decode( $service_settings, true );

		return $settings;
	}

	/**
	 * Render Admin Menu
	 * Renders WPF submenu item.
	 *
	 * @since 3.42.10
	 */
	public function render_admin_menu() {

		// Get the Amelia Services and Events
		global $wpdb;
		$services_table = $wpdb->prefix . 'amelia_services';
		$events_table   = $wpdb->prefix . 'amelia_events';

		$services = $wpdb->get_results( "SELECT id, name FROM {$services_table}" );
		$events   = $wpdb->get_results( "SELECT id, name, settings FROM {$events_table}" );

		?>

		<div class="wrap">
			<h1><?php printf( esc_html__( '%s Integration', 'wp-fusion' ), wp_fusion()->crm->name ); ?></h1>

			<?php

			// Save settings
			if ( isset( $_POST['wpf_amelia_settings_nonce'] ) && wp_verify_nonce( $_POST['wpf_amelia_settings_nonce'], 'wpf_amelia_settings' ) ) {

				if ( isset( $_POST['wpf_amelia_sync_settings'] ) ) {
					wp_fusion()->settings->set( 'amelia_guests', true );
				} else {
					wp_fusion()->settings->set( 'amelia_guests', false );
				}

				// Check all the services for any apply tags settings
				foreach ( $services as $service ) {
					$service_settings = $this->get_service_settings( $service->id );

					if ( array_key_exists( 'apply_tags', $service_settings ) ) {
						unset( $service_settings['apply_tags'] );
					}

					if ( isset( $_POST['wpf_amelia_settings_services'] ) && isset( $_POST['wpf_amelia_settings_services'][ $service->id ]['apply_tags'] ) ) {
						$service_settings['apply_tags'] = $_POST['wpf_amelia_settings_services'][ $service->id ]['apply_tags'];
					}

					$wpdb->update(
						$services_table,
						array(
							'settings' => wp_json_encode( $service_settings ),
						),
						array(
							'id' => $service->id,
						)
					);
				}

				// Check all the events for any apply tags settings
				foreach ( $events as $event ) {
					$event_settings = json_decode( $event->settings, true );
					if ( ! is_array( $event_settings ) ) {
						$event_settings = array();
					}

					if ( array_key_exists( 'apply_tags', $event_settings ) ) {
						unset( $event_settings['apply_tags'] );
					}

					if ( isset( $_POST['wpf_amelia_settings_events'] ) && isset( $_POST['wpf_amelia_settings_events'][ $event->id ]['apply_tags'] ) ) {
						$event_settings['apply_tags'] = $_POST['wpf_amelia_settings_events'][ $event->id ]['apply_tags'];
					}

					$wpdb->update(
						$events_table,
						array(
							'settings' => wp_json_encode( $event_settings ),
						),
						array(
							'id' => $event->id,
						)
					);
				}

				echo '<div id="message" class="updated fade"><p><strong>' . esc_html__( 'Settings Saved', 'wp-fusion' ) . '</strong></p></div>';

				// Get the latest data from the DB for the settings.
				$events = $wpdb->get_results( "SELECT id, name, settings FROM {$events_table}" );

			}

			?>

			<form id="wpf-amelia-settings" action="" method="post">
				<?php wp_nonce_field( 'wpf_amelia_settings', 'wpf_amelia_settings_nonce' ); ?>

				<input type="hidden" name="action" value="update">

				<table class="table table-hover wpf-settings-table">
					<thead>
						<tr>
							<th scope="row">
								<?php printf( esc_html__( 'Sync guest bookings to %s', 'wp-fusion' ), wp_fusion()->crm->name ); ?>
								<p class="description" style="font-weight: 500;"><?php esc_html_e( 'Bookings by registered users will always be synced.', 'wp-fusion' ); ?></p>
							</th>
							<td><input type="checkbox" name="wpf_amelia_sync_settings" <?php checked( wpf_get_option( 'amelia_guests' ) ); ?> /></td>
						</tr>
					</thead>
				</table>

				<h4><?php esc_html_e( 'Apply Tags For Services', 'wp-fusion' ); ?></h4>
				<p class="description"><?php esc_html_e( 'You can automate the application of tags when a user books a service in Amelia. For each service, select one or more tags to be applied when the service is booked.', 'wp-fusion' ); ?></p>
				<br/>

				<table class="table table-hover wpf-settings-table">
					<thead>
						<tr>
							<th scope="row"><?php esc_html_e( 'Amelia Service', 'wp-fusion' ); ?></th>
							<th scope="row"><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $services as $service ) :
							$service_settings = $this->get_service_settings( $service->id );
							if ( ! isset( $service_settings['apply_tags'] ) ) {
								$service_settings['apply_tags'] = array();
							}
							?>
							<tr>
								<td><?php echo esc_html( $service->name ); ?></td>
								<td>
									<?php
									$args = array(
										'setting'   => $service_settings['apply_tags'],
										'meta_name' => "wpf_amelia_settings_services[{$service->id}][apply_tags]",
									);
									wpf_render_tag_multiselect( $args );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h4><?php esc_html_e( 'Apply Tags For Events', 'wp-fusion' ); ?></h4>
				<p class="description"><?php esc_html_e( 'You can automate the application of tags when a user registers for an event in Amelia. For each event, select one or more tags to be applied when someone registers.', 'wp-fusion' ); ?></p>
				<br/>

				<table class="table table-hover wpf-settings-table">
					<thead>
						<tr>
							<th scope="row"><?php esc_html_e( 'Amelia Event', 'wp-fusion' ); ?></th>
							<th scope="row"><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $events as $event ) :
							$event_settings = json_decode( $event->settings, true );
							if ( ! is_array( $event_settings ) ) {
								$event_settings = array();
							}
							if ( ! isset( $event_settings['apply_tags'] ) ) {
								$event_settings['apply_tags'] = array();
							}
							?>
							<tr>
								<td><?php echo esc_html( $event->name ); ?></td>
								<td>
									<?php
									$args = array(
										'setting'   => $event_settings['apply_tags'],
										'meta_name' => "wpf_amelia_settings_events[{$event->id}][apply_tags]",
									);
									wpf_render_tag_multiselect( $args );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="submit"><input name="Submit" type="submit" class="button-primary" value="Save Changes"/></p>

			</form>
		</div>
		<?php
	}


	/**
	 * Add custom fields to the Integrations tab in the WP Fusion settings.
	 *
	 * @since  3.38.10
	 *
	 * @param  array $settings The registered settings.
	 * @param  array $options  The options in the database.
	 * @return array The registered settings.
	 */
	public function register_settings( $settings, $options ) {

		$settings['amelia_header'] = array(
			'title'   => __( 'Amelia Integration', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['amelia_guests'] = array(
			'title'   => __( 'Sync Guests', 'wp-fusion' ),
			/* translators: %s: CRM Name */
			'desc'    => sprintf( __( 'Sync guest bookings with %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'integrations',
			'tooltip' => __( 'Bookings by registered users will always be synced.', 'wp-fusion' ),
		);

		return $settings;
	}

	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds AmeliaBooking to available export options
	 *
	 * @since  3.42.10
	 *
	 * @param  array $options The export options.
	 * @return array The export options.
	 */
	public function export_options( $options ) {

		$options['amelia_booking_appointments'] = array(
			'label'         => __( 'Amelia Booking appointments', 'wp-fusion' ),
			'title'         => __( 'Appointments', 'wp-fusion' ),
			'process_again' => true,
			/* translators: %s: CRM Name */
			'tooltip'       => sprintf( __( 'For each appointment, syncs any available fields to %s, and applies any configured appointment tags.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$options['amelia_booking_events'] = array(
			'label'         => __( 'Amelia Booking events', 'wp-fusion' ),
			'title'         => __( 'Events', 'wp-fusion' ),
			'process_again' => true,
			/* translators: %s: CRM Name */
			'tooltip'       => sprintf( __( 'For each event registration, syncs any available fields to %s, and applies any configured event tags.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		return $options;
	}

	/**
	 * Batch Init Appointments.
	 * Get all appointments to be processed.
	 *
	 * @since  3.42.10
	 *
	 * @param  array $args The batch arguments.
	 *
	 * @return array|bool The IDs to be processed, or false if the sync setting is off.
	 */
	public function batch_init_appointments( $args ) {

		global $wpdb;

		// Get all customer bookings that have an appointment ID
		$query = $wpdb->prepare(
			"SELECT DISTINCT cb.id 
			 FROM {$wpdb->prefix}amelia_customer_bookings cb
			 INNER JOIN {$wpdb->prefix}amelia_appointments a 
				ON a.id = cb.appointmentId
			 WHERE cb.appointmentId IS NOT NULL"
		);

		// Skip any bookings that have been processed if the skip processed entries is on
		if ( isset( $args['skip_processed'] ) ) {
			$query .= " AND (cb.info IS NULL OR cb.info NOT LIKE '%wpf_synced%')";
		}

		$items = $wpdb->get_results( $query );

		return wp_list_pluck( $items, 'id' );
	}

	/**
	 * Batch Init Events.
	 * Get all events to be processed.
	 *
	 * @since  3.44.19
	 *
	 * @param  array $args The batch arguments.
	 * @return array|bool The IDs to be processed, or false if the sync setting is off.
	 */
	public function batch_init_events( $args ) {

		global $wpdb;

		// Join through the events_periods table to get valid event bookings
		$query = $wpdb->prepare(
			"SELECT DISTINCT cb.id 
			 FROM {$wpdb->prefix}amelia_customer_bookings cb
			 INNER JOIN {$wpdb->prefix}amelia_customer_bookings_to_events_periods cbep 
				ON cbep.customerBookingId = cb.id
			 INNER JOIN {$wpdb->prefix}amelia_events_periods ep 
				ON ep.id = cbep.eventPeriodId
			 INNER JOIN {$wpdb->prefix}amelia_events e 
				ON e.id = ep.eventId
			 WHERE cb.id IS NOT NULL"
		);

		// Skip any bookings that have been processed if the skip processed entries is on
		if ( isset( $args['skip_processed'] ) ) {
			$query .= " AND (cb.info IS NULL OR cb.info NOT LIKE '%wpf_synced%')";
		}

		$items = $wpdb->get_results( $query );

		return wp_list_pluck( $items, 'id' );
	}

	/**
	 * Batch Step Appointments.
	 * Processes appointments in batches.
	 *
	 * @since 3.42.10
	 *
	 * @param int $booking_id The booking ID.
	 */
	public function batch_step_appointments( $booking_id ) {

		global $wpdb;

		// Get the booking data
		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}amelia_customer_bookings WHERE id = %d",
				$booking_id
			),
			ARRAY_A
		);

		if ( ! empty( $booking ) ) {
			// Get the appointment data
			$appointment = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}amelia_appointments WHERE id = %d",
					$booking['appointmentId']
				),
				ARRAY_A
			);

			// Format data like the webhook
			$data = array(
				'type'        => 'appointment',
				'appointment' => $appointment,
				'customer'    => $this->get_customer_data( $booking_id ),
				'booking'     => $booking,
				'bookable'    => $this->get_service_data( $appointment['serviceId'] ),
			);

			$this->process_booking( $data );
		}
	}

	/**
	 * Batch Step Events.
	 * Processes events in batches.
	 *
	 * @since 3.42.10
	 *
	 * @param int $booking_id The booking ID.
	 */
	public function batch_step_events( $booking_id ) {

		global $wpdb;

		// Get the event booking data with period and event info
		$booking = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT cb.*, ep.eventId, ep.periodStart, ep.periodEnd 
				 FROM {$wpdb->prefix}amelia_customer_bookings cb
				 INNER JOIN {$wpdb->prefix}amelia_customer_bookings_to_events_periods cbep 
					ON cbep.customerBookingId = cb.id
				 INNER JOIN {$wpdb->prefix}amelia_events_periods ep 
					ON ep.id = cbep.eventPeriodId
				 WHERE cb.id = %d",
				$booking_id
			),
			ARRAY_A
		);

		if ( ! empty( $booking ) ) {
			// Get the full event data
			$event = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}amelia_events WHERE id = %d",
					$booking['eventId']
				),
				ARRAY_A
			);

			// Add the period data to the event
			$event['periods'] = array(
				array(
					'periodStart' => $booking['periodStart'],
					'periodEnd'   => $booking['periodEnd'],
				),
			);

			// Remove period data from booking array
			unset( $booking['periodStart'], $booking['periodEnd'], $booking['eventId'] );

			// Format data like the webhook
			$data = array(
				'type'     => 'event',
				'event'    => $event,
				'customer' => $this->get_customer_data( $booking_id ),
				'booking'  => $booking,
			);

			$this->process_booking( $data );
		}
	}

	/**
	 * Get customer data for batch processing.
	 *
	 * @since  3.44.19
	 *
	 * @param int $booking_id The booking ID
	 * @return array The customer data
	 */
	private function get_customer_data( $booking_id ) {
		global $wpdb;

		$customer = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT u.* FROM {$wpdb->prefix}amelia_users u
				 INNER JOIN {$wpdb->prefix}amelia_customer_bookings cb ON cb.customerId = u.id
				 WHERE cb.id = %d",
				$booking_id
			),
			ARRAY_A
		);

		return $customer;
	}

	/**
	 * Get service data for batch processing.
	 *
	 * @since  3.44.19
	 *
	 * @param int $service_id The service ID
	 * @return array The service data
	 */
	private function get_service_data( $service_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}amelia_services WHERE id = %d",
				$service_id
			),
			ARRAY_A
		);
	}
}

new WPF_AmeliaBooking();
