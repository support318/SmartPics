<?php
/**
 * Class BookingPress_WP_Fusion
 *
 * Handles integration between BookingPress and WP Fusion
 * Allows mapping BookingPress form fields to WP Fusion fields
 * Sends customer data to WP Fusion when appointments are booked
 *
 * @since 3.45.4
 */
class BookingPress_WP_Fusion_Integration extends BookingPress_Core {

	/**
	 * Constructor
	 *
	 * Registers all hooks and filters needed for the WP Fusion integration
	 *
	 * @since 3.45.4
	 */
	public function __construct() {
		add_action( 'bookingpress_add_optin_settings_section', array( $this, 'add_settings_section' ) );
		add_action( 'wp_ajax_bookingpress_delete_wpfusion_configuration', array( $this, 'delete_config' ) );
		add_filter( 'bookingpress_modify_capability_data', array( $this, 'addon_caps' ), 11, 1 );
		add_filter( 'bookingpress_available_optins_addon_list', array( $this, 'add_integration_list' ) );
		add_filter( 'bookingpress_addon_list_data_filter', array( $this, 'add_plugin_to_addon_list' ) );
		add_filter( 'bookingpress_add_integration_debug_logs', array( $this, 'add_logs' ) );

		add_filter( 'bookingpress_add_setting_dynamic_data_fields', array( $this, 'add_dynamic_data' ) );
		add_action( 'bookingpress_add_setting_dynamic_vue_methods', array( $this, 'add_vue_settings' ) );
		add_action( 'wp_ajax_bookingpress_get_wpfusion_field_list', array( $this, 'get_field_lists' ) );
		add_filter( 'bookingpress_modify_save_setting_data', array( $this, 'save_settings' ), 10, 2 );
		add_filter( 'bookingpress_modify_get_settings_data', array( $this, 'get_settings_data' ), 10, 2 );
		add_action( 'bookingpress_load_optin_settings_data', array( $this, 'load_settings_data' ) );

		// Sending data after appointment
		add_action( 'bookinpgress_after_front_book_appointment', array( $this, 'send_data_after_appointment' ) );
		add_action( 'bookingpress_after_approve_appointment', array( $this, 'send_data_awaiting_approval' ), 11, 1 );
	}


	/**
	 * Add WP Fusion to integration debug logs
	 *
	 * Adds WP Fusion as an option in the debug logs section
	 *
	 * @since 3.45.4
	 *
	 * @param array $bookingpress_integration_debug_logs_arr Existing debug logs
	 *
	 * @return array Modified debug logs
	 */
	function add_logs( $bookingpress_integration_debug_logs_arr ) {
		$bookingpress_integration_debug_logs_arr[] = array(
			'integration_name' => __( 'WP Fusion Debug Logs', 'wp-fusion' ),
			'integration_key'  => 'wpfusion_debug_logs',
		);

		return $bookingpress_integration_debug_logs_arr;
	}

	/**
	 * Modify capabilities for WP Fusion integration
	 *
	 * Adds specific capabilities for WP Fusion settings access
	 *
	 * @since 3.45.4
	 *
	 * @param array $bpa_caps Existing capabilities
	 *
	 * @return array Modified capabilities
	 */
	function addon_caps( $bpa_caps ) {
		$bpa_caps['bookingpress_settings'][] = 'get_wpfusion_field_list_details';
		$bpa_caps['bookingpress_settings'][] = 'bpa_delete_wpfusion_config';
		return $bpa_caps;
	}

	/**
	 * Add WP Fusion to the available integrations list
	 *
	 * @since 3.45.4
	 *
	 * @param array $bookingpress_integration_addon_list Existing integrations
	 *
	 * @return array Modified integrations list
	 */
	function add_integration_list( $bookingpress_integration_addon_list ) {
		$bookingpress_integration_addon_list[] = 'wpfusion';
		return $bookingpress_integration_addon_list;
	}

	/**
	 * Add configuration URL to WP Fusion addon in the addon list
	 *
	 * @since 3.45.4
	 *
	 * @param array $bookingpress_body_res Addon list data
	 *
	 * @return array Modified addon list data
	 */
	function add_plugin_to_addon_list( $bookingpress_body_res ) {
		global $bookingpress_slugs;
		if ( ! empty( $bookingpress_body_res ) ) {
			foreach ( $bookingpress_body_res as $bookingpress_body_res_key => $bookingpress_body_res_val ) {
				$bookingpress_setting_page_url = add_query_arg( 'page', $bookingpress_slugs->bookingpress_settings, esc_url( admin_url() . 'admin.php?page=bookingpress' ) );
				$bookingpress_config_url       = add_query_arg( 'setting_page', 'optin_settings', $bookingpress_setting_page_url );
				$bookingpress_config_url       = add_query_arg( 'setting_tab', 'wpfusion', $bookingpress_config_url );
				if ( $bookingpress_body_res_val['addon_key'] == 'bookingpress_wpfusion' ) {
					$bookingpress_body_res[ $bookingpress_body_res_key ]['addon_configure_url'] = $bookingpress_config_url;
				}
			}
		}
		return $bookingpress_body_res;
	}

	/**
	 * Modify settings data for WP Fusion integration
	 *
	 * Handles serialized data and removes unnecessary fields
	 *
	 * @since 3.45.4
	 *
	 * @param array $bookingpress_setting_return_data Settings data
	 * @param array $post_data POST data
	 *
	 * @return array Modified settings data
	 */
	function get_settings_data( $bookingpress_setting_return_data, $post_data ) {
		$setting_type = sanitize_text_field( $post_data['setting_type'] );
		if ( $setting_type == 'wpfusion_setting' && isset( $bookingpress_setting_return_data['wpfusion_selected_fields'] ) ) {
			if ( empty( $bookingpress_setting_return_data['wpfusion_selected_fields'] ) ) {
				$bookingpress_setting_return_data['wpfusion_selected_fields'] = array();
			} else {
				$bookingpress_setting_return_data['wpfusion_selected_fields'] = maybe_unserialize( $bookingpress_setting_return_data['wpfusion_selected_fields'] );
			}
		}
		if ( $setting_type == 'wpfusion_setting' ) {
			if ( isset( $bookingpress_setting_return_data['wpfusion_field_list'] ) ) {
				unset( $bookingpress_setting_return_data['wpfusion_field_list'] );
			}
			if ( isset( $bookingpress_setting_return_data['wpfusion_tags'] ) ) {
				$tags = maybe_unserialize( $bookingpress_setting_return_data['wpfusion_tags'] );
				if ( ! is_array( $tags ) ) {
					$tags = array();
				}
				$bookingpress_setting_return_data['wpfusion_tags'] = array_map( 'strval', $tags );
			}
			if ( isset( $bookingpress_setting_return_data['wpfusion_lists'] ) ) {
				$lists = maybe_unserialize( $bookingpress_setting_return_data['wpfusion_lists'] );
				if ( ! is_array( $lists ) ) {
					$lists = array();
				}
				$bookingpress_setting_return_data['wpfusion_lists'] = array_map( 'strval', $lists );
			}
		}

		return $bookingpress_setting_return_data;
	}

	/**
	 * Add dynamic data fields for WP Fusion settings
	 *
	 * Adds form fields, field lists, and validation rules for WP Fusion settings
	 *
	 * @since 3.45.4
	 *
	 * @param array $bookingpress_dynamic_setting_data_fields Existing data fields
	 *
	 * @return array Modified data fields
	 */
	function add_dynamic_data( $bookingpress_dynamic_setting_data_fields ) {
		global $BookingPress;

		// Initialize wpfusion_setting_form
		$bookingpress_dynamic_setting_data_fields['wpfusion_setting_form'] = array(
			'wpfusion_selected_fields' => (object) array(),
			'wpfusion_add_only'        => false,
			'wpfusion_tags'            => array(), // Initialize as empty array.
			'wpfusion_lists'           => array(), // Initialize as empty array.
		);

		// Get WP Fusion available tags
		$available_tags = wp_fusion()->settings->get_available_tags_flat( false );
		$tag_options    = array();
		foreach ( $available_tags as $tag_id => $tag_name ) {
			$tag_options[] = array(
				'value' => (string) $tag_id,
				'label' => $tag_name,
			);
		}
		$bookingpress_dynamic_setting_data_fields['wpfusion_tags_options'] = $tag_options;

		// Get and set saved tags
		$saved_tags = $BookingPress->bookingpress_get_settings( 'wpfusion_tags', 'wpfusion_setting' );
		if ( ! empty( $saved_tags ) ) {
			$saved_tags = maybe_unserialize( $saved_tags );
			if ( is_array( $saved_tags ) ) {
				// Convert all values to strings
				$saved_tags = array_map( 'strval', $saved_tags );
				$bookingpress_dynamic_setting_data_fields['wpfusion_setting_form']['wpfusion_tags'] = array_values( $saved_tags );
			}
		}

		// Get WP Fusion available lists
		$available_lists = wpf_get_option( 'available_lists', array() );
		$list_options    = array();
		foreach ( $available_lists as $list_id => $list_name ) {
			$list_options[] = array(
				'value' => (string) $list_id,
				'label' => $list_name,
			);
		}
		$bookingpress_dynamic_setting_data_fields['wpfusion_lists_options'] = $list_options;

		// Get and set saved lists
		$saved_lists = $BookingPress->bookingpress_get_settings( 'wpfusion_lists', 'wpfusion_setting' );
		if ( ! empty( $saved_lists ) ) {
			$saved_lists = maybe_unserialize( $saved_lists );
			if ( is_array( $saved_lists ) ) {
				$saved_lists = array_map( 'strval', $saved_lists );
				$bookingpress_dynamic_setting_data_fields['wpfusion_setting_form']['wpfusion_lists'] = array_values( $saved_lists );
			}
		}

		$bookingpress_dynamic_setting_data_fields['bpa_optin_active_tab'] = '';

		$bookingpress_field_list_data = method_exists( $BookingPress, 'bookingpress_get_form_field_list' ) ? $BookingPress->bookingpress_get_form_field_list() : $this->bookingpress_get_form_field_list();

		$bookingpress_field_list[] = array(
			'field_id'   => '',
			'field_name' => __( 'Select field', 'wp-fusion' ),
		);

		if ( ! empty( $bookingpress_field_list_data ) ) {
			foreach ( $bookingpress_field_list_data as $key => $val ) {
				if ( $val['bookingpress_field_type'] != 'checkbox' ) {
					$bookingpress_field_list[] = array(
						'field_id'   => sanitize_text_field( $val['bookingpress_field_meta_key'] ),
						'field_name' => sanitize_text_field( $val['bookingpress_field_label'] ),
					);
				}
			}
		}
		$bookingpress_dynamic_setting_data_fields['bookingpress_wpfusion_field_list'] = $bookingpress_field_list;

		$wpfusion_rules = array();

		$bookingpress_dynamic_setting_data_fields['wpfusion_setting_rule']                         = $wpfusion_rules;
		$bookingpress_dynamic_setting_data_fields['debug_log_setting_form']['wpfusion_debug_logs'] = false;
		$bookingpress_dynamic_setting_data_fields['bookingpress_optin_tab_list'][]                 = array(
			'tab_value' => 'wpfusion',
			'tab_name'  => esc_html__( 'WP Fusion', 'wp-fusion' ),
		);

		return $bookingpress_dynamic_setting_data_fields;
	}

	/**
	 * Get form field list for mapping
	 *
	 * Retrieves BookingPress form fields for mapping to WP Fusion fields
	 *
	 * @since 3.45.4
	 *
	 * @return array Form fields
	 */
	function bookingpress_get_form_field_list() {
		global $tbl_bookingpress_form_fields, $wpdb;
		$bookingpress_field_list_data = $wpdb->get_results( $wpdb->prepare( 'SELECT bookingpress_field_label,bookingpress_field_meta_key,bookingpress_field_type FROM ' . $tbl_bookingpress_form_fields . ' WHERE bookingpress_is_customer_field = %d AND bookingpress_field_type != %s AND bookingpress_field_type != %s AND bookingpress_field_type != %s order by bookingpress_form_field_id ASC', 0, '2_col', '3_col', '4_col' ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --Reason: $tbl_bookingpress_form_fields is a table name. false alarm.

		return $bookingpress_field_list_data;
	}

	/**
	 * Add WP Fusion settings section to the optin settings page
	 *
	 * Renders the HTML for the WP Fusion settings tab
	 *
	 * @since 3.45.4
	 *
	 * @return void
	 */
	function add_settings_section() {
		?>
		<el-row type="flex" class="bpa-mlc-head-wrap-settings bpa-gs-tabs--pb__heading __bpa-is-groupping" v-if="bpa_optin_active_tab == 'wpfusion'">
			<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12" class="bpa-gs-tabs--pb__heading--left">
				<h1 class="bpa-page-heading"><?php esc_html_e( 'WP Fusion', 'wp-fusion' ); ?></h1>
			</el-col>
			<el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
				<div class="bpa-hw-right-btn-group bpa-gs-tabs--pb__btn-group">
					<el-button class="bpa-btn bpa-btn--primary" :class="(is_display_save_loader == '1') ? 'bpa-btn--is-loader' : ''" @click="saveSettingsData('wpfusion_setting_form','wpfusion_setting')" :disabled="is_disabled">
						<span class="bpa-btn__label"><?php esc_html_e( 'Save', 'wp-fusion' ); ?></span>
						<div class="bpa-btn--loader__circles">
							<div></div>
							<div></div>
							<div></div>
						</div>
					</el-button>
				</div>
			</el-col>
		</el-row>
		<el-form class="bpa-gs__wpfusion-form" id="wpfusion_setting_form" ref="wpfusion_setting_form" :rules="wpfusion_setting_rule" :model="wpfusion_setting_form" label-position="top" @submit.native.prevent v-if="bpa_optin_active_tab == 'wpfusion'">
			<div class="bpa-gs__cb--item">
				<div class="bpa-gs__cb--item-body">

					<?php if ( in_array( 'lists', wp_fusion()->crm->supports ) ) { ?>
		
						<el-row type="flex" class="bpa-gs--tabs-pb__cb-item-row">
							<el-col :xs="12" :sm="12" :md="12" :lg="08" :xl="08" class="bpa-gs__cb-item-left">
								<h4><?php esc_html_e( 'Apply Lists', 'wp-fusion' ); ?></h4>
								<label class="bpa-cb-il__desc"><?php printf( esc_html__( 'The selected lists will be applied in %s when an appointment is booked.', 'wp-fusion' ), wp_fusion()->crm->name ); ?></label>	
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="16" :xl="16" class="bpa-gs__cb-item-right">
								<el-form-item>
									<el-select
										class="bpa-form-control"
										v-model="wpfusion_setting_form.wpfusion_lists"
										multiple
										filterable
										placeholder="<?php esc_html_e( 'Select lists', 'wp-fusion' ); ?>">
										<el-option
											v-for="list in wpfusion_lists_options"
											:key="list.value"
											:label="list.label"
											:value="list.value">
											{{ list.label }}
										</el-option>
									</el-select>
								</el-form-item>
							</el-col>
						</el-row>
			
					<?php } ?>

					<el-row type="flex" class="bpa-gs--tabs-pb__cb-item-row">
						<el-col :xs="12" :sm="12" :md="12" :lg="08" :xl="08" class="bpa-gs__cb-item-left">
							<h4><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?></h4>
							<label class="bpa-cb-il__desc"><?php printf( esc_html__( 'The selected tags will be applied in %s when an appointment is booked.', 'wp-fusion' ), wp_fusion()->crm->name ); ?></label>	
						</el-col>
						<el-col :xs="12" :sm="12" :md="12" :lg="16" :xl="16" class="bpa-gs__cb-item-right">
							<el-form-item>
								<el-select
									class="bpa-form-control"
									v-model="wpfusion_setting_form.wpfusion_tags"
									multiple
									filterable
									placeholder="<?php esc_html_e( 'Select tags', 'wp-fusion' ); ?>">
									<el-option
										v-for="tag in wpfusion_tags_options"
										:key="tag.value"
										:label="tag.label"
										:value="tag.value">
										{{ tag.label }}
									</el-option>
								</el-select>
							</el-form-item>
						</el-col>
					</el-row>

					<div class="bpa-gs__cb--item-heading">
						<h4 class="bpa-sec--sub-heading"><?php esc_html_e( 'Map more fields with WP Fusion', 'wp-fusion' ); ?></h4>
					</div>
					<div v-if="wpfusion_field_list && wpfusion_field_list.length > 0">
						<el-row type="flex" class="bpa-gs--tabs-pb__cb-item-row" v-for="(items, item_key) in wpfusion_field_list" :key="item_key">
							<el-col :xs="12" :sm="12" :md="12" :lg="08" :xl="08" class="bpa-gs__cb-item-left">
								<h4>{{items.name}}</h4>
							</el-col>
							<el-col :xs="12" :sm="12" :md="12" :lg="16" :xl="16" class="bpa-gs__cb-item-right">
								<el-form-item>
									<el-select class="bpa-form-control" 
										v-model="wpfusion_setting_form.wpfusion_selected_fields[items.key]" 
										placeholder="<?php esc_html_e( 'Select field', 'wp-fusion' ); ?>"
										>
										<el-option v-for="item in bookingpress_wpfusion_field_list" 
											:key="item.field_id" 
											:label="item.field_name" 
											:value="item.field_id">
										</el-option>
									</el-select>
								</el-form-item>
							</el-col>
						</el-row>
					</div>
					<div v-else class="bpa-gs--tabs-pb__cb-item-row">
						<div class="bpa-gs__cb-item-left">
							<p><?php esc_html_e( 'Loading WP Fusion fields...', 'wp-fusion' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</el-form>
		<?php
	}

	/**
	 * Add Vue methods for WP Fusion settings
	 *
	 * Adds JavaScript methods for the Vue.js frontend
	 *
	 * @since 3.45.4
	 *
	 * @return void
	 */
	function add_vue_settings() {
		?>
			bookingpress_delete_wpfusion_configuration() {
				const vm = this;
				var postData = { action: 'bookingpress_delete_wpfusion_configuration', _wpnonce: '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>' };
				axios.post(appoint_ajax_obj.ajax_url, Qs.stringify(postData))
				.then(function(response) {
					vm.wpfusion_setting_form.wpfusion_selected_fields = [];
					vm.wpfusion_field_list = [];
				}.bind(this))
				.catch(function(error) {
					console.log(error);
				});
			},
			bookingpress_get_wpfusion_field_list() {
				const vm = this;
				vm.is_display_loader = 1;
				var postData = { action: 'bookingpress_get_wpfusion_field_list', _wpnonce: '<?php echo esc_html( wp_create_nonce( 'bpa_wp_nonce' ) ); ?>' };
				axios.post(appoint_ajax_obj.ajax_url, Qs.stringify(postData))
				.then(function(response) {
					vm.is_display_loader = 0;
					if(response.data.field_list) {
						vm.wpfusion_field_list = response.data.field_list;
						// Initialize selected fields as empty object if undefined
						if(typeof vm.wpfusion_setting_form.wpfusion_selected_fields === 'undefined') {
							vm.$set(vm.wpfusion_setting_form, 'wpfusion_selected_fields', {});
						}
					}
				}.bind(this))
				.catch(function(error) {
					console.log(error);
					vm.is_display_loader = 0;
				});
			},
		<?php
	}

	/**
	 * Delete WP Fusion configuration
	 *
	 * Removes all WP Fusion settings from the database
	 *
	 * @since 3.45.4
	 *
	 * @return void
	 */
	function delete_config() {
		global $wpdb, $BookingPress;
		$response                = array();
		$bpa_check_authorization = $this->bpa_check_authentication( 'bpa_delete_wpfusion_config', true, 'bpa_wp_nonce' );

		if ( preg_match( '/error/', $bpa_check_authorization ) ) {
			$bpa_auth_error = explode( '^|^', $bpa_check_authorization );
			$bpa_error_msg  = ! empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'wp-fusion' );

			$response['variant'] = 'error';
			$response['title']   = esc_html__( 'Error', 'wp-fusion' );
			$response['msg']     = $bpa_error_msg;

			wp_send_json( $response );
			die;
		}

		$BookingPress->bookingpress_update_settings( 'wpfusion_field_list', 'wpfusion_setting', '' );
		$BookingPress->bookingpress_update_settings( 'wpfusion_selected_fields', 'wpfusion_setting', '' );
		$BookingPress->bookingpress_update_settings( 'wpfusion_tags', 'wpfusion_setting', '' );
		$BookingPress->bookingpress_update_settings( 'wpfusion_lists', 'wpfusion_setting', '' );
		$BookingPress->bookingpress_update_settings( 'wpfusion_add_only', 'wpfusion_setting', '' );

		$response['variant'] = 'success';
		$response['title']   = esc_html__( 'Success', 'wp-fusion' );
		$response['msg']     = esc_html__( 'WP Fusion Configuration Delete successfully', 'wp-fusion' );

		echo json_encode( $response );
		exit;
	}

	/**
	 * Get WP Fusion field list via AJAX
	 *
	 * Retrieves available fields from WP Fusion CRM
	 *
	 * @since 3.45.4
	 *
	 * @return void
	 */
	function get_field_lists() {
		global $BookingPress;
		$response = array();

		$bpa_check_authorization = $this->bpa_check_authentication( 'get_wpfusion_field_list_details', true, 'bpa_wp_nonce' );

		if ( preg_match( '/error/', $bpa_check_authorization ) ) {
			$bpa_auth_error = explode( '^|^', $bpa_check_authorization );
			$bpa_error_msg  = ! empty( $bpa_auth_error[1] ) ? $bpa_auth_error[1] : esc_html__( 'Sorry. Something went wrong while processing the request', 'wp-fusion' );

			$response['variant'] = 'error';
			$response['title']   = esc_html__( 'Error', 'wp-fusion' );
			$response['msg']     = $bpa_error_msg;

			wp_send_json( $response );
			die;
		}

		// Get WP Fusion fields
		$crm_fields          = wp_fusion()->settings->get_crm_fields_flat();
		$wpfusion_field_list = array();

		if ( ! empty( $crm_fields ) ) {
			foreach ( $crm_fields as $field_key => $field_label ) {
				$wpfusion_field_list[] = array(
					'id'   => $field_key,
					'name' => $field_label, // Use the label instead of key for display
					'key'  => $field_key,
				);
			}

			// Store the field list in settings
			$BookingPress->bookingpress_update_settings( 'wpfusion_field_list', 'wpfusion_setting', maybe_serialize( $wpfusion_field_list ) );

			$response['field_list'] = $wpfusion_field_list;
			$response['variant']    = 'success';
			$response['title']      = esc_html__( 'Success', 'wp-fusion' );
			$response['msg']        = esc_html__( 'WP Fusion Field list Retrieved successfully', 'wp-fusion' );
		} else {
			// Return empty array instead of error to prevent JavaScript errors
			$response['field_list'] = array();
			$response['variant']    = 'warning';
			$response['title']      = esc_html__( 'Warning', 'wp-fusion' );
			$response['msg']        = esc_html__( 'No WP Fusion fields found', 'wp-fusion' );
		}

		wp_send_json( $response );
		exit;
	}

	/**
	 * Process WP Fusion integration after a waiting appointment is approved
	 *
	 * Sends customer data to WP Fusion when a waiting list appointment is approved
	 *
	 * @since 3.45.4
	 *
	 * @param int $appointment_id Appointment ID
	 *
	 * @return void
	 */
	function send_data_awaiting_approval( $appointment_id ) {
		global $wpdb, $tbl_bookingpress_appointment_bookings;

		// Get appointment data
		$appointment_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT bookingpress_customer_email as customer_email,
			bookingpress_appointment_internal_note as appointment_note,
			bookingpress_customer_phone_dial_code,
			bookingpress_customer_firstname as customer_firstname,
			bookingpress_customer_lastname as customer_lastname,
			bookingpress_customer_phone as customer_phone
			FROM {$tbl_bookingpress_appointment_bookings} 
			WHERE bookingpress_appointment_booking_id = %d",
				$appointment_id
			),
			ARRAY_A
		);

		if ( empty( $appointment_data ) ) {
			return;
		}

		// Get WP Fusion settings
		$wpfusion_settings = $this->get_wpfusion_settings();

		// Format appointment data to match form data structure
		$form_data = array(
			'customer_name'      => $appointment_data['customer_firstname'] . ' ' . $appointment_data['customer_lastname'],
			'customer_firstname' => $appointment_data['customer_firstname'],
			'customer_lastname'  => $appointment_data['customer_lastname'],
			'customer_email'     => $appointment_data['customer_email'],
			'customer_phone'     => $appointment_data['bookingpress_customer_phone_dial_code'] . $appointment_data['customer_phone'],
			'appointment_note'   => $appointment_data['appointment_note'],
		);

		// Get field mappings
		$field_data = $this->get_field_mappings();

		// Prepare form data
		$bookingpress_final_submitted_data = $this->prepare_form_data( $form_data, $field_data );

		// Process contact data and send to WP Fusion
		$this->process_wpfusion_contact(
			$bookingpress_final_submitted_data,
			$wpfusion_settings,
			$appointment_id
		);
	}

	/**
	 * Modify save settings data for WP Fusion
	 *
	 * Handles serialization of field mappings when saving settings
	 *
	 * @since 3.45.4
	 *
	 * @param array $bookingpress_save_settings_data Settings data to save
	 * @param array $post_data POST data
	 *
	 * @return array Modified settings data
	 */
	function save_settings( $bookingpress_save_settings_data, $post_data ) {
		$bookingpress_setting_type = sanitize_text_field( $post_data['settingType'] );
		if ( ! empty( $bookingpress_setting_type ) && $bookingpress_setting_type == 'wpfusion_setting' ) {
			// Handle selected fields
			if ( isset( $bookingpress_save_settings_data['wpfusion_selected_fields'] ) ) {
				if ( is_object( $bookingpress_save_settings_data['wpfusion_selected_fields'] ) ) {
					$bookingpress_save_settings_data['wpfusion_selected_fields'] = (array) $bookingpress_save_settings_data['wpfusion_selected_fields'];
				}
				$bookingpress_save_settings_data['wpfusion_selected_fields'] = maybe_serialize( $bookingpress_save_settings_data['wpfusion_selected_fields'] );
			}

			// Handle tags
			if ( isset( $bookingpress_save_settings_data['wpfusion_tags'] ) ) {
				$tags = (array) $bookingpress_save_settings_data['wpfusion_tags'];
				// Ensure all values are strings and remove any empty values
				$tags = array_filter( array_map( 'strval', $tags ) );
				$bookingpress_save_settings_data['wpfusion_tags'] = maybe_serialize( array_values( $tags ) );
			}

			// handle lists
			if ( isset( $bookingpress_save_settings_data['wpfusion_lists'] ) ) {
				$lists = (array) $bookingpress_save_settings_data['wpfusion_lists'];
				// Ensure all values are strings and remove any empty values
				$lists = array_filter( array_map( 'strval', $lists ) );
				$bookingpress_save_settings_data['wpfusion_lists'] = maybe_serialize( array_values( $lists ) );
			}
		}
		return $bookingpress_save_settings_data;
	}

	/**
	 * Send data to WP Fusion after an appointment is booked
	 *
	 * Maps BookingPress form fields to WP Fusion fields and sends data to the CRM
	 *
	 * @since 3.45.4
	 *
	 * @param array $appointment_data Appointment data
	 *
	 * @return void
	 */
	function send_data_after_appointment( $appointment_data ) {

		// Check if this is a waiting list appointment.
		if ( apply_filters( 'bookingpress_check_waiting_after_front_book_for_integration', false, $appointment_data ) ) {
			return;
		}

		// Get WP Fusion settings
		$wpfusion_settings = $this->get_wpfusion_settings();

		// Get submitted form data
		$bookingpress_final_submitted_data = $this->prepare_form_data(
			$appointment_data['form_fields'] ?? array(),
			$appointment_data['bookingpress_front_field_data'] ?? array()
		);

		// Process contact data and send to WP Fusion.
		$this->process_wpfusion_contact(
			$bookingpress_final_submitted_data,
			$wpfusion_settings,
			$appointment_data['bookingpress_appointment_id'] ?? 0
		);
	}

	/**
	 * Get WP Fusion settings
	 *
	 * Retrieves WP Fusion settings from the database
	 *
	 * @since 3.45.4
	 *
	 * @return array WP Fusion settings
	 */
	private function get_wpfusion_settings() {
		global $BookingPress;
		return array(
			'selected_fields' => maybe_unserialize( $BookingPress->bookingpress_get_settings( 'wpfusion_selected_fields', 'wpfusion_setting' ) ),
			'add_only'        => $BookingPress->bookingpress_get_settings( 'wpfusion_add_only', 'wpfusion_setting' ) == 'true',
			'lists'           => maybe_unserialize( $BookingPress->bookingpress_get_settings( 'wpfusion_lists', 'wpfusion_setting' ) ),
			'tags'            => maybe_unserialize( $BookingPress->bookingpress_get_settings( 'wpfusion_tags', 'wpfusion_setting' ) ),
		);
	}

	/**
	 * Get field mappings
	 *
	 * Retrieves field mappings from the database
	 *
	 * @since 3.45.4
	 *
	 * @return array Field mappings
	 */
	private function get_field_mappings() {
		global $wpdb, $tbl_bookingpress_form_fields;

		$field_data  = array();
		$form_fields = $wpdb->get_results( "SELECT * FROM {$tbl_bookingpress_form_fields} ORDER BY bookingpress_field_position ASC", ARRAY_A );

		foreach ( $form_fields as $field ) {
			$meta_key                = $field['bookingpress_form_field_name'];
			$field_data[ $meta_key ] = $field['bookingpress_form_field_id'];
		}

		return $field_data;
	}

	/**
	 * Prepare form data
	 *
	 * Prepares form data for processing
	 *
	 * @since 3.45.4
	 *
	 * @param array $submitted_data Submitted form data
	 * @param array $field_mappings Field mappings
	 *
	 * @return array Prepared form data
	 */
	private function prepare_form_data( $submitted_data, $field_mappings ) {
		global $wpdb, $tbl_bookingpress_form_fields;

		$final_data = array();

		foreach ( $submitted_data as $meta_key => $value ) {
			if ( isset( $field_mappings[ $meta_key ] ) ) {
				$field_id                      = $field_mappings[ $meta_key ];
				$field_meta_key                = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT bookingpress_field_meta_key FROM `{$tbl_bookingpress_form_fields}` WHERE bookingpress_form_field_id = %d",
						$field_id
					)
				);
				$final_data[ $field_meta_key ] = $value;
			} else {
				$final_data[ $meta_key ] = $value;
			}

			if ( $meta_key === 'customer_email' ) {
				$final_data['email_address'] = $value;
			}
		}

		return $final_data;
	}

	/**
	 * Process WP Fusion contact
	 *
	 * Processes WP Fusion contact data
	 *
	 * @since 3.45.4
	 *
	 * @param array $form_data Form data
	 * @param array $wpfusion_settings WP Fusion settings
	 * @param int   $appointment_id Appointment ID
	 *
	 * @return void
	 */
	private function process_wpfusion_contact( $form_data, $wpfusion_settings, $appointment_id ) {
		if ( empty( $wpfusion_settings['selected_fields'] ) ) {
			return;
		}

		// Map form fields to WP Fusion fields
		$update_data = array();
		foreach ( $wpfusion_settings['selected_fields'] as $crm_field => $form_field ) {
			if ( ! empty( $form_data[ $form_field ] ) ) {
				$update_data[ $crm_field ] = is_array( $form_data[ $form_field ] )
					? implode( ',', $form_data[ $form_field ] )
					: $form_data[ $form_field ];
			}
		}

		$email_address = $form_data['email_address'];

		if ( empty( $update_data ) || empty( $email_address ) ) {
			return;
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => (array) $wpfusion_settings['tags'],
			'apply_lists'      => isset( $wpfusion_settings['lists'] ) ? $wpfusion_settings['lists'] : array(),
			'add_only'         => $wpfusion_settings['add_only'],
			'integration_slug' => 'bookingpress',
			'integration_name' => 'BookingPress',
			'form_edit_link'   => admin_url( 'admin.php?page=bookingpress_settings&tab=wpfusion_setting' ),
		);

		$contact_id = WPF_Forms_Helper::process_form_data( $args );
	}

	/**
	 * Load WP Fusion optin settings data
	 *
	 * Loads settings data when the WP Fusion tab is selected
	 * Also triggers field list retrieval if needed
	 *
	 * @since 3.45.4
	 *
	 * @return void
	 */
	function load_settings_data() {
		?>
			// Initialize wpfusion_field_list as empty array if it's undefined
			if (typeof vm.wpfusion_field_list === 'undefined') {
				vm.wpfusion_field_list = [];
			}
			
			vm.getSettingsData('wpfusion_setting', 'wpfusion_setting_form')
			setTimeout(function() {
				if (vm.$refs.wpfusion_setting_form != undefined) {
					vm.$refs.wpfusion_setting_form.clearValidate();
				}
			}, 2000);
			
			// Load WP Fusion fields on tab load
			setTimeout(function() {
				if (!vm.wpfusion_field_list || vm.wpfusion_field_list.length === 0) {
					vm.bookingpress_get_wpfusion_field_list();
				}
			}, 500);
		<?php
	}
}
