<?php /** @noinspection GrazieInspection */

/** @noinspection SpellCheckingInspection */

namespace Tomi\AffiliateWpActivator;

use WP_Error;
use function Tomi\AffiliateWpActivator\Utils\{affwp_set_addon_path as affwp_set_addon_path_Alias,
	affwp_unset_addon_path,
	check_r2_object,
	custom_wp_remote_request,
	formated_pre_response,
	get_from_xpath,
	is_custom_pre_http_request};

define( 'TARGET_URL', 'https://affiliatewp.com' ); // The domain to intercept

trait Affiliate_Wp_Activator_Traits {

	/**
	 * @param $pre
	 * @param $args
	 * @param $url
	 *
	 * @return array|mixed|WP_Error
	 */
	public static function manage_affiliatewp_license_api_requests( $pre, $args, $url ) {

		if ( is_custom_pre_http_request( $args ) ) {
			return $pre;
		}

		if ( ! class_exists( 'Affiliate_WP' ) ) {
			return $pre;
		}

		// Block check-in (telemetry) endpoint.
		if ( strpos( $url, 'https://usg.affiliatewp.com/v1/checkin/' ) === 0 ) {
			return formated_pre_response( array() );
		}

		if ( strpos( $url, TARGET_URL ) !== 0 ) {
			return $pre;
		}

		// get addons info
		if ( strpos( $url, 'https://affiliatewp.com/wp-content/addons.json' ) === 0 ||
		     strpos( $url, 'https://affiliatewp.com/wp-content/email-summaries.json' ) === 0 ) {
			return $pre;
		}

		return self::handle_affiliatewp_requests( $pre, $args, $url );
	}

	/**
	 * @param $pre
	 * @param $args
	 * @param $url
	 *
	 * @return array|false|mixed|null
	 */
	protected static function handle_affiliatewp_requests( $pre, $args, $url ) {

		$body           = $args['body'] ?? [];
		$license        = $body['license'] ?? '';
		$item_id        = (int) isset( $body['id'] ) ? $body['id'] : 0;
		$item_name      = $body['item_name'] ?? '';
		$edd_action     = $body['edd_action'] ?? '';
		$affwp_action   = $body['affwp_action'] ?? '';
		$is_main_plugin = ( $item_name === 'AffiliateWP' );

		if ( $is_main_plugin ) {
			$item_id = 17;
		}

		if ( strpos( $url, TARGET_URL . '/edd-sl-api' ) === 0 && $args['method'] === 'GET' ) {
			return self::handle_license_api( $license, $item_name, $edd_action, $is_main_plugin, $body ); // local
		}

		// main plugin
		if ( strpos( $url, TARGET_URL ) === 0 && $args['method'] === 'POST' && 'get_version' === $edd_action ) {
			return self::handle_plugin_version_check();
		}

		if ( strpos( $url, TARGET_URL ) === 0 && $args['method'] === 'POST' && $affwp_action && $item_id ) {

			$data = [];
			if ( $affwp_action === 'get_addon_download' ) {
				$addon   = self::get_addon_data( $item_id );
				$package = $addon ? self::get_package_url( $addon['slug'], $addon['version'] ) : '';
				$data    = [ 'download_link' => $package ];
			}

			if ( $affwp_action === 'get_version' ) {
				$data = self::handle_addon_version_check( $item_id );
			}

			return formated_pre_response( $data );
		}

		return $pre;
	}

	/**
	 * @param $license
	 * @param $item_name
	 * @param $edd_action
	 * @param $is_main_plugin
	 * @param $body
	 *
	 * @return array|false
	 */
	protected static function handle_license_api( $license, $item_name, $edd_action, $is_main_plugin, $body ) {

		if ( empty( $license ) ) {

			$body = [ 'success' => false, 'license' => 'invalid', 'item_id' => false, 'item_name' => $item_name, ];

			switch ( $edd_action ) {
				case 'check_license':
					return formated_pre_response( $body );
				case 'activate_license':
					return formated_pre_response( array_merge( $body, [ 'error' => 'missing' ] ) );
			}

			return false;
		}

		if ( $edd_action === 'activate_license' ) {
			update_option( 'affwp_drm_current_state', 'valid' );

			return formated_pre_response( [
				'success'          => true,
				'license'          => 'valid',
				'item_name'        => $item_name,
				'item_id'          => $is_main_plugin ? 17 : ( $body['item_id'] ?? 0 ),
				'license_limit'    => 10,
				'site_count'       => 1,
				'expires'          => 'lifetime',
				'activations_left' => 9,
				'payment_id'       => 2444,
				'customer_name'    => 'John Doe',
				'customer_email'   => 'john@sample.com',
				'price_id'         => '2'
			] );
		}

		if ( $edd_action === 'check_license' && $is_main_plugin ) {
			return formated_pre_response( [ 'license' => 'valid', ] );
		}

		if ( $edd_action === 'deactivate_license' && $is_main_plugin ) {
			return formated_pre_response( [
				'success'        => true,
				'license'        => 'deactivated',
				'item_name'      => $item_name,
				'expires'        => 'lifetime',
				'payment_id'     => 2444,
				'customer_name'  => 'John Doe',
				'customer_email' => 'john@sample.com'
			] );
		}

		return false;
	}

	/**
	 * @return array
	 */
	protected static function handle_plugin_version_check(): array {

		$info = self::get_version_info();
		if ( is_wp_error( $info ) ) {
			// Log the error.
			error_log(
				sprintf(
					'[AffiliateWP Activator] Version info error: %s (%s)',
					$info->get_error_message(),
					$info->get_error_code()
				)
			);
			// Optionally, display an admin notice here for visibility.

			// Return an empty, but correctly formatted response.
			return formated_pre_response( [] );
		}

		$details = array(
			'name'         => 'AffiliateWP',
			'slug'         => 'affiliate-wp',
			// 'version'      => $info['version'],
			'new_version'  => $info['new_version'],
			// 'author'         => '',
			// 'author_profile' => '',
			'requires'     => '5.2',
			'requires_php' => '7.4',
			'tested'       => '6.7.2',
			'plugin'       => 'affiliate-wp/affiliate-wp.php',
			'id'           => 'affiliatewp.com/affiliate-wp',
			'url'          => 'https://affiliatewp.com/',
			'package'      => self::get_package_url( 'affiliate-wp', $info['new_version'] ),
			'icons'        => [
				'1x'  => 'https://affiliatewp.com/wp-content/uploads/2022/06/logomark-affiliatewp.svg',
				'2x'  => 'https://affiliatewp.com/wp-content/uploads/2022/06/logomark-affiliatewp.svg',
				'svg' => 'https://affiliatewp.com/wp-content/uploads/2022/06/logomark-affiliatewp.svg',
			],
			'sections'     => maybe_serialize( [
				'changelog' => "<a href='https://affiliatewp.com/changelog/' target='_blank'>View changelog</a>",
				// 'upgrade_notice' => '',
			] ),
			'external'     => 'https://affiliatewp.com/',
		);

		return formated_pre_response( $details );
	}

	/**
	 * @return array|WP_Error
	 */
	protected static function get_version_info() {

		// Most caches store pages based on the exact URL. Adding a random parameter forces a fresh request.
		$page_url      = 'https://affiliatewp.com/changelog/?nocache=';
		$transient_key = 'affwp_activator_changelog_' . md5( $page_url );
		$data          = get_transient( $transient_key );

		if ( false === $data ) {

			// Fetch the HTML content from the remote URL
			$response = custom_wp_remote_request( $page_url . time(),
				array(
					'sslverify' => false,
					// If WordPress stores a cached response, disable it using headers
					'headers'   => array(
						'Cache-Control' => 'no-cache, no-store, must-revalidate',
						'Pragma'        => 'no-cache',
						'Expires'       => '0'
					)
				) );

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'request_failed', "Failed to fetch the changelog page." );
			}
			$html = wp_remote_retrieve_body( $response );
			// Stop execution if the response is empty or broken
			if ( empty( $html ) ) {
				return new WP_Error( 'empty_response', "The changelog page returned an empty response." );
			}

			// Now `$html` is guaranteed to be valid content at this point
			$latest_version = get_from_xpath( $html, '//*[@id="main"]/article/div/div[2]/h3[1]' );
			$latest_version = preg_match( '/Version\s+([0-9.]+)/',
				wp_strip_all_tags( $latest_version ), $m ) ? $m[1] : '';

			$current_version = get_option( 'affwp_version' );

			if ( $latest_version ) {
				$data = [
					'version'     => $current_version,
					'new_version' => version_compare( $current_version, $latest_version, '<' ) ? $latest_version : '',
				];
				set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );
			}
		}

		return $data;
	}

	/**
	 * @param $slug
	 * @param $version
	 *
	 * @return string
	 */
	protected static function get_package_url( $slug, $version ): string {

		if ( ! $slug ) {
			return '';
		}

		if ( ! $version ) {
			$available = self::get_available_versions();
			if ( ! $available ) {
				return '';
			}
			$version = $available[ $slug ] ?? '';
			if ( ! $version ) {
				return '';
			}
		}

		$key    = '68747470733a2f2f616666696c6961746577702e3630333036302e78797a2f';
		$object = hex2bin( $key ) . "{$slug}_v$version.zip";
		$exists = check_r2_object( $object );

		return $exists ? hex2bin( $key ) . "download/{$slug}_v$version.zip" : '';
	}

	/**
	 * @return array
	 */
	protected static function get_available_versions(): array {

		$transient_key = 'affwp_activator_bucket_' . md5( 'versions' );
		$available     = get_transient( $transient_key );

		if ( empty( $available ) ) {

			$auth     = array(
				'Authorization' => 'Bearer 1b39a7f1-1426-48d4-b9e2-ca74a2da465e',
				'X-AFFWP-PSK'   => '3296ae43-74ca-492e-b50f-7900fd073410',
			);

			$key1     = '68747470733a2f2f616666696c6961746577702e';
			$key2     = '3630333036302e78797a2f66696c652d6c697374';
			$response = custom_wp_remote_request( hex2bin( $key1 . $key2 ), array(
				'method'  => 'GET',
				'headers' => $auth
			) );
			if ( ! is_wp_error( $response ) ) {
				$available = json_decode( wp_remote_retrieve_body( $response ), true ) ?? [];
			}

			if ( $available ) {
				set_transient( $transient_key, $available, 12 * HOUR_IN_SECONDS );
			}
		}

		return $available;
	}

	/**
	 * @param int $id
	 *
	 * @return array|false
	 */
	protected static function get_addon_data( int $id ) {

		if ( $id <= 0 ) {
			return false;
		}

		$page_url      = 'https://affiliatewp.com/wp-content/addons.json';
		$transient_key = 'affwp_activator_addons_' . md5( $page_url );
		$addons        = get_transient( $transient_key );

		if ( false === $addons ) {
			$response = custom_wp_remote_request( $page_url, array( 'sslverify' => false ) );
			$addons   = json_decode( wp_remote_retrieve_body( $response ), true ) ?? false;

			if ( $addons ) {
				set_transient( $transient_key, $addons, 12 * HOUR_IN_SECONDS );
			} else {
				return false;
			}
		}

		$item      = self::search_addon_by_id( $addons, $id );
		$available = self::get_available_versions();

		if ( empty( $item['version'] ) || version_compare( $item['version'], $available[ $item['slug'] ], '>' ) ) {
			$item['version'] = $available[ $item['slug'] ];
		}

		return $item;
	}

	/**
	 * @param array $items
	 * @param int $value
	 *
	 * @return array|false
	 */
	protected static function search_addon_by_id( array $items, int $value ) {

		foreach ( $items as $item ) {
			if ( ( isset( $item['id'] ) && (string) $item['id'] === (string) $value ) ) {
				$path           = affwp_set_addon_path_Alias( $item['slug'] );
				$item['plugin'] = $path;
				$item['slug']   = dirname( $path );

				return $item;
			}
		}

		return false; // no match is found
	}

	/**
	 * @param $item_id
	 *
	 * @return array
	 */
	protected static function handle_addon_version_check(
		$item_id
	): array {

		$info = self::get_addon_data( $item_id );
		if ( ! $info || ! is_array( $info ) ) {
			return formated_pre_response( [] );
		}

		// Handle case where affwp_unset_addon_path returns empty string
		$plugin_path = affwp_unset_addon_path( $info['plugin'] );

		// If plugin path is empty, use the original plugin path as fallback
		if ( empty( $plugin_path ) ) {
			$plugin_path = $info['plugin']; // Use original path as fallback
		}

		return array(
			'name'        => $info['title'],
			'slug'        => $info['slug'],  // from path
			'new_version' => $info['version'],
			'plugin'      => $info['plugin'],
			'id'          => 'affiliatewp.com/' . $plugin_path,
			'url'         => $info['url'],
			'package'     => self::get_package_url( $info['slug'], $info['version'] ),
			'icons'       => [
				'1x'      => $info['image'],
				'2x'      => $info['image'],
				'default' => $info['image'],
			],
			'sections'    => maybe_serialize( [
				'changelog' => "<a href='" . esc_url( $info['doc'] ) . "' target='_blank'>Read documentation</a>",
				// 'upgrade_notice' => '',
			] ),
			'external'    => 'https://affiliatewp.com/',
		);
	}

	/**
	 * @return void
	 */
	protected function init_hooks() {

		add_filter( 'pre_http_request', [ __CLASS__, 'manage_affiliatewp_license_api_requests' ], 10, 3 );
	}

}