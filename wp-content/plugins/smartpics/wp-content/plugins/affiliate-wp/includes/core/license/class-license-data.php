<?php
/**
 * License
 *
 * Handles the license functionality.
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

namespace AffWP\Core\License;

require_once AFFILIATEWP_PLUGIN_DIR . 'includes/core/license/license-data-functions.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Core class used to handle the License functionality from EDD.
 *
 * @since 2.9
 */
class License_Data {
	/**
	 * Returns the license ID if it was verified recently.
	 *
	 * @since 2.9.5
	 *
	 * @return int|null
	 */
	public function get_license_id() {
		// Get license data.
		$license_data = affiliate_wp()->settings->get( 'license_status', array() );

		if ( empty( $license_data ) && ! is_object( $license_data ) ) {
			return;
		}

		$license_id = isset( $license_data->price_id ) ? intval( $license_data->price_id ) : null;

		return $license_id;
	}

	/**
	 * Returns the activation status for the given license key.
	 *
	 * @since 2.9 Adapted from the Settings class, save functionality extracted to other functions, and added license key param.
	 * @since 2.21.1 Added $force parameter.
	 *
	 * @param string $license_key License key.
	 * @return array Returns status with error info or license data.
	 */
	public function activation_status( string $license_key, bool $force = false ) {

		if ( ! $force ) {

			// Retrieve the license status from the database.
			$status = affiliate_wp()->settings->get( 'license_status' );

			if ( isset( $status->license ) ) {
				$status = $status->license;
			}

			if ( 'valid' === $status ) {
				return false; // License already activated and valid.
			}
		}

		$license_key = sanitize_text_field( $license_key );

		// Data to send in our API request.
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license_key,
			'item_name'  => 'AffiliateWP',
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_get( 'https://affiliatewp.com/edd-sl-api', array(
			'timeout'   => 35,
			'sslverify' => false,
			'body'      => $api_params,
		) );

		$response_code = wp_remote_retrieve_response_code( $response );

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) ) {
			return array(
				'license_status' => false,
				'affwp_notice'   => 'license-http-failure',
				'affwp_message'  => $response->get_error_message(),
			);
		}

		// Check response error code.
		if ( 200 !== $response_code ) {
			return array(
				'license_status' => false,
				'affwp_notice'   => 'license-http-failure',
				'affwp_message'  => wp_remote_retrieve_response_message( $response ),
			);
		}

		// Decode the license data.
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// Default to invalid when there is an error and license status isn't set.
		if ( isset( $license_data->error ) && ! isset( $license_data->license ) ) {
			// Note: this seems to happen when testing random strings.
			$license_data->error   = 'item_name_mismatch';
			$license_data->license = 'invalid';
		}

		// Save license data and key.
		affiliate_wp()->settings->set( array(
			'license_status' => $license_data,
			'license_key'    => $license_key,
		), true );

		// Set license check transient.
		if ( isset( $license_data->license ) ) {
			set_transient( 'affwp_license_check', $license_data->license, DAY_IN_SECONDS );
		}

		// Return license data.
		return array(
			'license_status' => true,
			'license_data'   => $license_data,
			'license_key'    => $license_key,
		);
	}

	/**
	 * Returns the deactivation status for the given license key.
	 *
	 * @since 2.9 Adapted from the Settings class and save functionality extracted to other functions.
	 * @return bool|array Returns true or array with error info.
	 */
	public function deactivation_status() {
		// Retrieve the license status from the database.
		$status = affiliate_wp()->settings->get( 'license_status' );

		if ( isset( $status->license ) ) {
			$status = $status->license;
		}

		if ( 'valid' !== $status ) {
			return false; // License already deactivated.
		}

		$license_key = self::get_license_key();

		// Data to send in our API request.
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license_key,
			'item_name'  => 'AffiliateWP',
			'url'        => home_url(),
		);

		// Call the custom API.
		$response = wp_remote_get( 'https://affiliatewp.com/edd-sl-api', array(
			'timeout'   => 35,
			'sslverify' => false,
			'body'      => $api_params,
		) );

		// Make sure the response came back okay.
		if ( is_wp_error( $response ) ) {
			return array(
				'license_status' => false,
				'message'        => $response->get_error_message(),
			);
		}

		// Save updated license status.
		affiliate_wp()->settings->set( array( 'license_status' => 0 ), true );

		return true;
	}

	/**
	 * Return if the license is active (saved) in our setting and is active.
	 *
	 * When using $this->check_status() the license will return as valid, even if
	 * the license is not saved. This method is an extra validation, so we can ensure
	 * that the license is actually saved in our DB.
	 *
	 * @since 2.21.1
	 *
	 * @return bool Whether the license is activated on this site or not.
	 */
	public function is_license_site_activated() : bool {

		$status = affiliate_wp()->settings->get( 'license_status' );

		return is_object( $status ) && isset( $status->success );
	}

	/**
	 * Retrieves the license key.
	 *
	 * If the `AFFILIATEWP_LICENSE_KEY` constant is defined, it will override values
	 * stored in the database.
	 *
	 * @since 1.9
	 * @access public
	 * @static
	 *
	 * @param string $key    Optional. License key to check. Default empty.
	 * @param bool   $saving Optional. Whether a saving operation is being performed. If true,
	 *                       the already-saved key value will be ignored. Default false.
	 * @return string License key.
	 */
	public static function get_license_key( $key = '', $saving = false ) {
		if ( defined( 'AFFILIATEWP_LICENSE_KEY' ) && AFFILIATEWP_LICENSE_KEY ) {
			$license = AFFILIATEWP_LICENSE_KEY;
		} elseif ( ! empty( $key ) || true === $saving ) {
			$license = $key;
		} else {
			$license = affiliate_wp()->settings->get( 'license_key' );
		}

		return trim( $license );
	}

	/**
	 * Checks validity of the license key.
	 *
	 * @since 1.0.0
	 * @since 2.9.0  Extracted this from the Settings class and updated name.
	 * @since 2.24.2 If the response fails, resulting in a `\WP_Error`, we've
	 *               fixed the transient for checking again to not store the
	 *               `\WP_Error` object, but store the current status until the
	 *               site finally responds.
	 *
	 * @param bool $force Optional. Whether to force checking the license (bypass caching).
	 * @return bool|mixed|void
	 */
	public function check_status( $force = false ) {
		$status = get_transient( 'affwp_license_check' );

		$request_url = 'https://affiliatewp.com/edd-sl-api';

		// Run the license check a maximum of once per day.
		if ( ( false === $status || $force ) && site_url() !== $request_url ) {

			// data to send in our API request.
			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => self::get_license_key(),
				'item_name'  => 'AffiliateWP',
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_get( $request_url, array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			) );

			// Make sure the response came back okay.
			if ( is_wp_error( $response ) ) {

				// Connection failed, try again in three hours.
				set_transient( 'affwp_license_check', $status, 3 * HOUR_IN_SECONDS );

				return false;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( isset( $license_data->license ) ) {
				$status = $license_data->license;
			} else {
				$status = 'invalid';
			}

			affiliate_wp()->settings->set( array( 'license_status' => $license_data ) );

			set_transient( 'affwp_license_check', $status, DAY_IN_SECONDS );

		}

		return $status;
	}

	/**
	 * Returns whether the license key is valid or not.
	 *
	 * @since 2.9 Extracted this from the Settings class.
	 * @return bool
	 */
	public function is_license_valid() {
		return 'valid' === $this->check_status();
	}

	/**
	 * Returns the type of the license.
	 *
	 * @since 2.9
	 *
	 * @param int $license_id License id.
	 * @return string Personal, Plus, Professional, or Ultimate
	 */
	public function get_license_type( $license_id ) {
		if ( 0 === $license_id ) {
			$license_type = 'Personal';
		} elseif ( 1 === $license_id ) {
			$license_type = 'Plus';
		} elseif ( 2 === $license_id ) {
			$license_type = 'Professional';
		} elseif ( 3 === $license_id ) {
			$license_type = 'Ultimate';
		} else {
			$license_type = '';
		}

		return $license_type;
	}

}
