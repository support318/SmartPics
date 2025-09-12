<?php

if ( ! isset( $_GET['wpf_action'] ) ) {
	exit();
}

if ( ! defined( 'ABSPATH' ) ) {

	// Normally ABSPATH isn't defined here, but this allows for overriding it with
	// auto_prepend_file or by including this file via a bootstrap.

	$full_path    = getcwd();
	$ar           = explode( 'wp-', $full_path );
	$wp_root_path = $ar[0];

	define( 'ABSPATH', $wp_root_path );

}

define( 'SHORTINIT', true ); // load the minumum files required to get to the database.

require ABSPATH . DIRECTORY_SEPARATOR . 'wp-load.php';

// WordPress is available now.

// Try to find the contact ID in the URL.

$contact_id = get_contact_id();

if ( ! $contact_id ) {
	wp_die( 'No contact ID specified.' );
}

$settings = get_option( 'wpf_options' );

if ( ! isset( $_GET['access_key'] ) || $_GET['access_key'] !== $settings['access_key'] ) {
	wp_die( 'Invalid access key' );
}

$action = sanitize_text_field( wp_unslash( $_GET['wpf_action'] ) );

// Now create the action to perform based on the wpf_action parameter.

if ( 'update' === $action || 'update_tags' === $action ) {

	$user_id = wp_cache_get( "wpf_cid_{$contact_id}" ); // try to get it from the cache.

	if ( false === $user_id ) {

		global $wpdb;

		// Update and Update Tags require a user ID.

		$sql     = $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s", "{$settings['crm']}_contact_id", $contact_id );
		$user_id = $wpdb->get_var( $sql );

		if ( null === $user_id ) {
			wp_die( 'No matching user found', 'Not Found', 200 );
		}

		wp_cache_set( "wpf_cid_{$contact_id}", $user_id );

	}

	$data = array(
		array(
			'users_tags_sync',
			array( $user_id ),
		),
	);

	if ( 'update' === $action ) {

		$data[] = array(
			'pull_users_meta',
			array( $user_id ),
		);

	}
} elseif ( 'add' === $action ) {


	if ( is_numeric( $contact_id ) ) {
		// Most platforms use numeric IDs but Drip, Mailchimp, and Salesforce use alphanumeric hashes.
		$contact_id = absint( $contact_id );
	}

	$data = array(
		array(
			'import_users',
			array(
				$contact_id,
				array(
					'role'              => isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : false,
					'send_notification' => isset( $_GET['send_notification'] ) && 'true' === $_GET['send_notification'] ? true : false,
				),
			),
		),
	);

} else {
	wp_die( 'Invalid action' );
}

// We have our data, now save it to the options table so the background worker can find it.

$unique  = md5( microtime() . rand() );
$prepend = 'wpf_background_process_';

$key = substr( $prepend . $unique, 0, 48 );

update_site_option( $key, $data );

// Make sure that the cron task is enabled.

if ( empty( $settings['enable_cron'] ) ) {
	$settings['enable_cron'] = true;
	update_option( 'wpf_options', $settings );
}

// All done!

if ( is_object( simplexml_load_string( file_get_contents( 'php://input' ) ) ) ) {

	// Salesforce:
	echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
	echo '<soap:Body>';
	echo '<notificationsResponse xmlns:ns2="urn:sobject.enterprise.soap.sforce.com" xmlns="http://soap.sforce.com/2005/09/outbound">';

	echo '<Ack>true</Ack>';

	echo '</notificationsResponse>';
	echo '</soap:Body>';
	echo '</soap:Envelope>';

	die();

} else {

	// Others:

	wp_die( 'Success. Saved <code>' . esc_html( $key ) . '</code> with <pre>' . print_r( $data, true ) . '</pre>', 'Success', 200 );

}

/**
 * Gets the contact ID from the request.
 *
 * @return int|false
 */
function get_contact_id() {

	$contact_id = false;

	if ( isset( $_REQUEST['contact']['id'] ) ) {

		// ActiveCampaign.
		$contact_id = absint( $_REQUEST['contact']['id'] );

	} elseif ( isset( $_REQUEST['contactId'] ) ) {

		// Infusionsoft.
		$contact_id = absint( $_REQUEST['contactId'] );

	} elseif ( isset( $_REQUEST['contact_id'] ) ) {

		// Default.
		$contact_id = sanitize_text_field( wp_unslash( $_REQUEST['contact_id'] ) );

	}

	$json_payload = json_decode( file_get_contents( 'php://input' ) );

	if ( $json_payload ) {

		if ( isset( $json_payload->event ) && ( $json_payload->event == 'subscriber.applied_tag' || $json_payload->event == 'subscriber.removed_tag' || $json_payload->event == 'subscriber.updated_custom_field' || $json_payload->event == 'subscriber.updated_email_address' ) ) {

			// Drip admin settings webhooks.
			$contact_id = sanitize_key( $json_payload->data->subscriber->id );

		} elseif ( isset( $json_payload->subscriber ) ) {

			// Drip automation / rules triggers.
			$contact_id = sanitize_key( $json_payload->subscriber->id );

		} elseif ( isset( $json_payload->id ) ) {

			// FluentCRM, Brevo, and others.
			$contact_id = absint( $json_payload->id );

		}
	}

	// Salesforce.
	$xml_payload = simplexml_load_string( file_get_contents( 'php://input' ) );

	if ( is_object( $xml_payload ) ) {

		$data = $xml_payload->children( 'http://schemas.xmlsoap.org/soap/envelope/' )->Body->children( 'http://soap.sforce.com/2005/09/outbound' )->notifications;

		if ( 1 === count( $data->{'Notification'} ) && isset( $data->{'Notification'}[0]->sObject->children( 'urn:sobject.enterprise.soap.sforce.com' )->Id ) ) {

			$contact_id = sanitize_text_field( (string) $data->{'Notification'}[0]->sObject->children( 'urn:sobject.enterprise.soap.sforce.com' )->Id );
		}
	}

	return $contact_id;
}
