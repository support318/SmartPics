<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Contact_Form_7 extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'cf7';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'Contact form 7';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/lead-generation/contact-form-7/';

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */
	public function init() {

		add_filter( 'wpcf7_editor_panels', array( $this, 'add_panel' ) );
		add_action( 'wpcf7_after_save', array( $this, 'save_form' ) );
		add_action( 'wpcf7_mail_sent', array( $this, 'send_data' ), 5 );
		add_action( 'init', array( $this, 'maybe_process_paypal_payment' ) );
	}

	/**
	 * Adds panel to CF7 settings page
	 *
	 * @access  public
	 * @return  array Panels
	 */
	public function add_panel( $panels ) {

		$panels['wp-fusion-tab'] = array(
			'title'    => 'WP Fusion',
			'callback' => array( $this, 'add_form' ),
		);

		return $panels;
	}

	/**
	 * Add Form
	 * Adds form content to panel.
	 *
	 * @since unknown
	 * @since 3.43.5 Added ZealousWeb Payment Types integration.
	 *
	 * @param object $info Contact Form 7 settings form object.
	 *
	 * @return  mixed Panel content
	 */
	public function add_form( $info ) {

		wp_nonce_field( 'cf7_wpf_nonce', 'cf7_wpf_nonce' );

		$post_id = $info->id();
		$content = $info->prop( 'form' );
		$inputs  = array();
		$methods = $this->get_active_payment_methods( $post_id );

		// Get inputs from saved form config
		preg_match_all( '/\[.*\]/', $content, $matches );

		foreach ( $matches[0] as $input ) {
			$input    = substr( $input, 1, - 1 );
			$input    = str_replace( '*', '', $input );
			$elements = explode( ' ', $input );

			if ( isset( $elements[1] ) && $elements[1] != '"Send"' ) {
				$inputs[ $elements[1] ] = $elements[0];
			}
		}

		$defaults = array(
			'tags'                  => array(),
			'payment_received_tags' => array(),
			'add_only'              => false,
		);

		$settings = get_post_meta( $post_id, 'cf7_wpf_settings', true );
		$settings = wp_parse_args( $settings, $defaults );

		if ( empty( $settings ) ) {
			$settings = array();
		}

		echo '<h2>' . wp_fusion()->crm->name . ' Settings</h2>';

		echo '<fieldset><legend>' . sprintf( __( 'For each field in the form, select a field to sync with in %s.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</legend>';

		echo '<table id="wpf-cf7-table">';
		echo '<tbody id="wp-fusion-inputs">';
		foreach ( $inputs as $name => $type ) {

			$capital_name = str_replace( '-', '  ', $name );

			if ( ! isset( $settings[ $name ] ) ) {
				$settings[ $name ] = array( 'crm_field' => '' );
			}

			if ( ! isset( $settings[ $name ]['crm_field'] ) ) {
				$settings[ $name ]['crm_field'] = '';
			}

			if ( 'checkbox' === $type ) {
				$type = 'multiselect';
			}

			echo '<tr id="input-row">';
			echo '<td><label> ' . ucwords( $capital_name ) . ' <label></td>';
			echo '<td><label for ="cf7_wpf_settings"> ' . $type . ' <label></td>';
			echo '<td class="crm-field">';
			wpf_render_crm_field_select( $settings[ $name ]['crm_field'], 'cf7_wpf_settings', $name );
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';

		echo '<p class="description"><label for="tags">' . sprintf( __( 'Select tags to be applied in %s when this form is submitted.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</label><br />';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['tags'],
				'meta_name' => 'cf7_wpf_settings',
				'field_id'  => 'tags',
			)
		);

		// ZealousWeb Payment Types integration.
		if ( ! empty( $methods ) ) {
			echo '<br /><label for="payment_tags">';
			echo '<p class="description" id="payment_tags"><label for="payment_received_tags">' . sprintf( __( 'Select tags to be applied in %s when payment is received.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</label><br />';

			wpf_render_tag_multiselect(
				array(
					'setting'   => $settings['payment_received_tags'],
					'meta_name' => 'cf7_wpf_settings',
					'field_id'  => 'payment_received_tags',
				)
			);
		}

		echo '</p>';

		echo '<br /><label for="wpf-add-only">';

		echo '<input type="checkbox" id="wpf-add-only" name="cf7_wpf_settings[add_only]" ' . checked( $settings['add_only'], 1, false ) . ' class="toggle-form-table" value="1">';

		_e( 'Add Only', 'wp-fusion' );

		echo '</label>';

		echo '<p class="description">' . __( 'Only add new contacts, don\'t update existing ones.', 'wp-fusion' ) . '</p>';

		echo '</fieldset>';
	}

	/**
	 * Save WPF settings fields
	 *
	 * @access public
	 *
	 * @param unknown $contact_form
	 */
	public function save_form( $contact_form ) {

		if ( empty( $_POST ) || ! isset( $_POST['cf7_wpf_nonce'] ) ) {
			return;
		}

		$post_id = $contact_form->id();

		update_post_meta( $post_id, 'cf7_wpf_settings', $_POST['cf7_wpf_settings'] );
	}

	/**
	 * Send data to CRM on form submission.
	 *
	 * @since unknown
	 * @since 3.43.5 Added support for ZealousWeb Payment Methods integration.
	 *
	 * @param object $contact_form The contact form object.
	 *
	 * @return  array Classes
	 */
	public function send_data( $contact_form ) {

		$contact_form_id = $contact_form->id();
		$submission      = WPCF7_Submission::get_instance();
		$posted_data     = $submission->get_posted_data();

		$wpf_settings = get_post_meta( $contact_form_id, 'cf7_wpf_settings', true );

		if ( empty( $wpf_settings ) ) {
			return;
		}

		$email_address = false;
		$update_data   = array();

		foreach ( $posted_data as $key => $value ) {

			if ( ! isset( $wpf_settings[ $key ] ) || empty( $wpf_settings[ $key ]['crm_field'] ) || empty( $value ) ) {
				continue;
			}

			if ( false !== strpos( $wpf_settings[ $key ]['crm_field'], 'add_tag_' ) ) {

				// Don't run the filter on dynamic tagging inputs.
				$update_data[ $wpf_settings[ $key ]['crm_field'] ] = $value;
				continue;
			}

			$type = 'text';

			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
				$type  = 'multiselect';
			}

			if ( is_email( $value ) ) {
				$email_address = $value;
			}

			$value = apply_filters( 'wpf_format_field_value', $value, $type, $wpf_settings[ $key ]['crm_field'] );

			$update_data[ $wpf_settings[ $key ]['crm_field'] ] = $value;

		}

		if ( empty( $update_data ) && empty( $wpf_settings['tags'] ) ) {
			return; // no fields mapped.
		}

		if ( ! isset( $wpf_settings['tags'] ) ) {
			$wpf_settings['tags'] = array();
		}

		/**
		 * ZealousWeb Payment Methods integration.
		 */

		if ( ! empty( $wpf_settings['payment_received_tags'] ) ) {

			foreach ( $this->get_active_payment_methods( $contact_form_id ) as $key => $method ) {

				// Get the most recent transaction for this payment type.
				$query = new WP_Query(
					array(
						'post_type'      => $method['prefix'] . 'data',
						'posts_per_page' => 1,
					)
				);

				// If we couldn't find a transaction post for this method try the paypal method. Otherwise, skip it.
				if ( empty( $query->post ) && 'cf7pe_' === $method['prefix'] ) {

					// Save the form ID, API context and email address for the PayPal payment in the session.
					// We'll use this to process the payment later.
					$_SESSION['wpf_cf7pe_data'] = array(
						'form_id'       => $contact_form_id,
						'api_context'   => $_SESSION[ $method['prefix'] . 'context_' . $contact_form_id ], // phpcs:ignore
						'email_address' => isset( $email_address ) ? $email_address : '',
					);
					continue;
				} elseif ( empty( $query->post ) ) {
					continue;
				}

				$tx_status = get_post_meta( $query->post->ID )['_transaction_status'][0];

				$success_phrases = array(
					'succeeded',
					1,
					'success',
				);

				if ( in_array( $tx_status['_transaction_status'][0], $success_phrases, false ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$wpf_settings['tags'] = array_merge( $wpf_settings['tags'], $wpf_settings['payment_received_tags'] );
				}
			}
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => $wpf_settings['tags'],
			'add_only'         => ! empty( $wpf_settings['add_only'] ) ? true : false,
			'integration_slug' => 'cf7',
			'integration_name' => 'Contact Form 7',
			'form_id'          => $contact_form_id,
			'form_title'       => get_the_title( $contact_form_id ),
			'form_edit_link'   => admin_url( 'admin.php?page=wpcf7&post=' . $contact_form_id . '&action=edit' ),
		);

		$contact_id = WPF_Forms_Helper::process_form_data( $args );
	}

	/**
	 * Maybe Process Paypal Payment.
	 *
	 * Process paypal payments if we're returning from a paypal session.
	 *
	 * @since 3.43.5
	 */
	public function maybe_process_paypal_payment() {

		if ( ! function_exists( 'CF7PE' ) ) {
			return;
		}

		// Start a session if it's not already started.
		if ( ! isset( $_SESSION ) || session_status() === PHP_SESSION_NONE ) {
			if ( ! headers_sent() ) {
				session_start();
			}
		}

		// We saved our form ID, API context, and email address in the session.
		// If it's there, then we're returning from the paypal gateway, so process it.
		if ( isset( $_SESSION['wpf_cf7pe_data'] ) ) {

			$form_id       = $_SESSION['wpf_cf7pe_data']['form_id']; // phpcs:ignore
			$api_context   = $_SESSION[ 'wpf_cf7pe_data' ][ 'api_context' ]; // phpcs:ignore
			$email_address = $_SESSION['wpf_cf7pe_data']['email_address']; // phpcs:ignore

			$this->process_paypal_payment( $form_id, $api_context, $email_address );
		}
	}

	/**
	 * Process PayPal payments.
	 *
	 * @since 3.43.5
	 *
	 * @param int    $form_id The form ID.
	 * @param array  $api_context The API context.
	 * @param string $email_address The email address.
	 */
	public function process_paypal_payment( $form_id, $api_context, $email_address ) {

		// Check for the required data.
		// We need the payment ID  to retrieve the transaction using the PayPal Payment API.
		// phpcs:ignore
		if ( ! class_exists( 'PayPal\Api\Payment' ) || empty( $_GET ) || ! isset( $_GET['paymentId'] ) ) {
			return;
		}

		// Get the payment object.
		// phpcs:ignore
		$payment = PayPal\Api\Payment::get( $_GET['paymentId'], $api_context );

		if ( empty( $payment ) ) {
			return;
		}

		// Get the state of the sale from the related resources in the transaction object.
		$transaction       = $payment->getTransactions();
		$related_resources = $transaction[0]->getRelatedResources();
		$sale              = $related_resources[0]->getSale();
		$state             = $sale->getState();

		// If the transaction was successful, apply the payment received tags.
		if ( 'completed' === $state ) {

			$wpf_settings = get_post_meta( $form_id, 'cf7_wpf_settings', true );
			$user         = get_user_by( 'email', $email_address );

			if ( ! empty( $wpf_settings ) && ! empty( $wpf_settings['payment_received_tags'] ) ) {

				wpf_log(
					'info',
					wpf_get_current_user_id(),
					'Paypal payment received. Applying tags to user <strong>' . $email_address . '</strong>.',
					array(
						'tag_array' => $wpf_settings['payment_received_tags'],
						'source'    => 'wpf-cf7-paypal-payment',
					)
				);

				// Apply the tags to the user or the contact record.
				if ( ! $user ) {

					$contact_id = wp_fusion()->crm->get_contact_id( $email_address );

					if ( empty( $contact_id ) ) {
						return;
					}

					wp_fusion()->crm->apply_tags( $wpf_settings['payment_received_tags'], $contact_id );
				} else {

					wp_fusion()->user->apply_tags( $wpf_settings['payment_received_tags'], $user->ID );
				}
			}
		}

		// Unset our session data so we don't process the same form twice.
		unset( $_SESSION['wpf_cf7pe_data'] );
	}

	/**
	 * Get Active Payment Methods
	 * Get the active ZealousWeb Payment Types.
	 *
	 * @since 3.43.5
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array $methods
	 */
	public function get_active_payment_methods( $post_id = null ) {

		$payment_methods = array();

		// Get all the activated ZealousWeb payment methods from the constants.
		foreach ( get_defined_constants() as $constant => $value ) {

			// Skip the Contact Form 7 plugin constants.
			if ( false !== strpos( $constant, 'WPCF7' ) ) {
				continue;
			}

			// All ZealousWeb Payment Types integration constants contain 'CF' and 'META_PREFIX'.
			if ( false !== strpos( $constant, 'CF' ) && false !== strpos( $constant, 'META_PREFIX' ) ) {
				$payment_methods[ $constant ] = array(
					'prefix' => $value,
					'name'   => constant( strtoupper( $value ) . 'PLUGIN_BASENAME' ),
				);
			}
		}

		if ( empty( $payment_methods ) ) {
			return array();
		}

		// Filter the active payment methods.
		$post_meta = get_post_meta( $post_id );

		if ( is_array( $post_meta ) ) {

			// Check the post meta for the payment method use keys.
			foreach ( $post_meta as $key => $data ) {

				foreach ( $payment_methods as $constant => $value ) {

					// Check the use keys.
					if ( false !== strpos( $key, $value['prefix'] . 'use' ) ) {

						// If the payment method is not active, remove it from the active payment methods list.
						if ( '1' !== $data[0] ) {
							unset( $payment_methods[ $constant ] );
						}
					}
				}
			}
		}

		return $payment_methods;
	}
}

new WPF_Contact_Form_7();
