<?php
/** @noinspection PhpInArrayCanBeReplacedWithComparisonInspection */

namespace Tomi\AffiliateWpActivator\Utils;

use DOMDocument;
use DOMXPath;
use function apply_filters;
use function constant;
use function defined;
use function get_current_blog_id;
use function get_option;
use function is_array;
use function is_multisite;
use function maybe_unserialize;
use function preg_replace;
use function site_url;
use function strtolower;
use function wp_cache_get;
use function wp_parse_url;
use const PHP_URL_HOST;

/**
 * Format the HTTP response
 */
function formated_pre_response( $body ): array {

	return [
		'headers'  => [],
		'body'     => json_encode( $body, JSON_FORCE_OBJECT ),
		'response' => [ 'code' => 200, 'message' => 'OK' ],
		'cookies'  => [],
		'filename' => null,
	];
}


// Get the MD5 hash of the input string formatted as UUID
function md5ToUuid( $input ): string {

	$hash = md5( $input );

	// Format the first half into a UUID structure
	// Return the formatted UUID
	return sprintf( '%08s-%04s-%04s-%04s-%012s',
		substr( $hash, 0, 8 ),
		substr( $hash, 8, 4 ),
		substr( $hash, 12, 4 ),
		substr( $hash, 16, 4 ),
		substr( $hash, 20, 12 ) );
}


/**
 * Custom function to safely perform wp_remote_request inside a pre_http_request filter.
 * Suppresses cycling risks by using a unique marker in the request arguments.
 */
function custom_wp_remote_request( $url, $args = [] ) {

	// Add a custom marker to identify this request and prevent recursion.
	$custom_marker = 'x-custom-no-pre-http';

	if ( ! isset( $args['headers'] ) ) {
		$args['headers'] = [];
	}

	// Add the marker header.
	$args['headers'][ $custom_marker ] = 'true';

	// the type of request is got from $args['method']
	return wp_remote_request( $url, $args );
}


/**
 * Custom function to check if a request should bypass pre_http_request filter.
 * @return bool True if the request should bypass, false otherwise.
 */
function is_custom_pre_http_request( array $args = [] ): bool {

	if ( empty( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
		return false;
	}

	return ! empty( $args['headers']['x-custom-no-pre-http'] )
	       && $args['headers']['x-custom-no-pre-http'] === 'true';
}


/**
 * Get current home url, normalized without schema, `www` subdomain and path.
 * This avoids general conflicts in situations when customers move their
 * HTTP site to HTTPS.
 *
 * @return string Can be empty, e.g., for WP CLI and WP Cronjob when Object Cache is active
 */
function getCurrentHostname(): string {

	// Multisite subdomain installations are forced to use the `home_url` option
	// See also https://github.com/WordPress/WordPress/blob/4e4016f61fa40abda4c0a0711496f2ba50a10563/wp-includes/ms-blogs.php#L249
	$isMultisiteSubdomainInstallation = is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) && constant( 'SUBDOMAIN_INSTALL' );
	if ( ! $isMultisiteSubdomainInstallation && defined( 'WP_SITEURL' ) ) {
		// Constant is defined (https://wordpress.org/support/article/changing-the-site-url/#edit-wp-config-php)
		$site_url = constant( 'WP_SITEURL' );
	} else {
		$site_url = getOptionRaw( 'siteurl', site_url() );
	}
	$url = wp_parse_url( $site_url, PHP_URL_HOST );
	$url = preg_replace( '/^www\\./', '', $url );
	// Remove default ports (https://regex101.com/r/eyxvPE/1)
	$url = preg_replace( '/:(80|443)$/', '', $url );
	$url = strtolower( $url );

	return apply_filters( 'DevOwl/RealProductManager/HostMap/ConnectThematic', $url, get_current_blog_id() );
}


/**
 * Get the raw value of a `wp_options` value by respecting the object cache. It is not modified
 * through option-filters.
 *
 * @param string $optionName
 * @param mixed $default
 *
 * @return mixed|string
 */
function getOptionRaw( string $optionName, $default = false ) {

	// Force so the options cache is filled
	get_option( $optionName );
	// Directly read from our cache because we want to skip `site_url` / `option_site_url` filters (https://git.io/JOnGV)
	// Why `alloptions`? Since `siteurl` is `autoloaded=yes`, it is loaded via `wp_load_alloptions` and filled
	// to the cache key `alloptions`. The filters are used by WPML and PolyLang, but we do not care about them. Non-autoloaded
	// options are read from `notoptions`.
	foreach ( [ 'alloptions', 'notoptions' ] as $cacheKey ) {
		$cache = wp_cache_get( $cacheKey, 'options' );
		if ( is_array( $cache ) && isset( $cache[ $optionName ] ) ) {
			return maybe_unserialize( $cache[ $optionName ] );
		}
	}
	// Fallback to directly read the option from the `options` cache
	$directFromCache = wp_cache_get( $optionName, 'options' );
	if ( $directFromCache !== false ) {
		return maybe_unserialize( $directFromCache );
	}

	return $default;
}


/**
 * Function to fetch and extract content by XPath
 */
function get_from_xpath( $html, $xpath_query ): string {

	// Load HTML into DOMDocument
	$dom = new DOMDocument();

	// Suppress warnings from malformed HTML
	libxml_use_internal_errors( true );
	$dom->loadHTML( $html );
	libxml_clear_errors();

	// Create a new DOMXPath instance
	$xpath = new DOMXPath( $dom );

	// Query the DOM for the desired XPath
	$elements = $xpath->query( $xpath_query );

	// Check if the query returned any results
	if ( $elements->length === 0 ) {
		return 'No content found for the provided XPath';
	}

	// Extract and return the content as a string
	$content = '';
	foreach ( $elements as $element ) {
		$content .= $dom->saveHTML( $element );
	}

	return $content;
}


/*
 * function that extracts and returns the changelog for all releases newer than the currently installed version ($actual_version).
 * It iterates over the changelog entries, extracts version numbers, and includes only those that are greater than $actual_version.
**/
function get_newer_changelog_entries( $html, $actual_version = '1.0' ): string {

	$changelog_html = ''; // To store the final changelog output
	$index          = 1;
	$regex          = '/Version\s+([0-9.]+)/';
	$xpath_         = "//*[@id='main']/article/div/div[2]";

	while ( true ) {
		// Get release title

		$version_string = get_from_xpath( $html, "$xpath_/h3[$index]" );

		if ( empty( $version_string ) ) {
			break; // Stop when there are no more versions
		}

		// Extract version number
		$version = preg_match( $regex, wp_strip_all_tags( $version_string ), $m ) ? $m[1] : '';

		if ( empty( $version ) ) {
			$index ++;
			continue;
		}

		// Compare version numbers
		if ( version_compare( $version, $actual_version, '<=' ) ) {
			break; // Stop when reaching an older one then current version
		}

		// Append release
		$changelog_html .= $version_string;

		// Get the corresponding changelog content
		$changes = get_from_xpath( $html, "$xpath_/ul[$index]" );

		// Append changelog to output
		$changelog_html .= $changes;

		$index ++;
	}

	return $changelog_html;
}


/**
 * Determines if a given JSON string is valid and decodes to the expected type.
 *
 * This function attempts to decode a JSON string and then verifies if the decoded value
 * is either a valid JSON object or an associative array based on the provided flag.
 *
 * @param string $json The JSON string to validate.
 * @param bool $as_assoc Optional. Whether to decode the JSON as an associative array.
 *                          Defaults to false (returns an object).
 *
 * @return bool Returns true if the JSON string is valid and decodes to the expected type,
 *              otherwise false.
 */
function is_valid_json_object( string $json, bool $as_assoc = false ): bool {

	// Decode the JSON string.
	$decoded = json_decode( $json, $as_assoc );

	// Check for errors in decoding.
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return false;
	}

	// Verify that the decoded value matches the expected type.
	if ( $as_assoc && ! is_array( $decoded ) ) {
		return false;
	} elseif ( ! $as_assoc && ! is_object( $decoded ) ) {
		return false;
	}

	return true;
}


function check_r2_object( $object ): array {

	$contrib_name = $contrib_code = false;
	$response     = custom_wp_remote_request( $object, [ 'method' => 'HEAD', 'timeout' => 15, ] );

	if ( is_wp_error( $response ) ) {
		return [ false, false, false ];
	}

	$http_code = wp_remote_retrieve_response_code( $response );

	if ( 200 === $http_code ) {
		$headers      = wp_remote_retrieve_headers( $response );
		$contrib_name = $headers['x-amz-meta-contrib-name'] ?? false;
		$contrib_code = $headers['x-amz-meta-contrib-code'] ?? false;
	}

	return [ $http_code === 200, $contrib_name, $contrib_code ];
}


/**
 * Set addon path.
 * copied from affiliate-wp/includes/admin/add-ons.php:L402
 *
 * @param string $slug Addon slug.
 *
 * @return string Return the addon path.
 * @noinspection DuplicatedCode
 */
function affwp_set_addon_path( string $slug ): string {
	// Plugin paths that use 'affwp' instead of 'affiliatewp'.
	if ( in_array( $slug, array(
		'affiliate-dashboard-sharing',
	), true ) ) {
		return sprintf( 'affiliatewp-%1$s/affwp-%1$s.php', $slug );
	}

	// Plugins paths that use the 'affiliate-wp'.
	if ( in_array( $slug, array(
		'lifetime-commissions',
		'paypal-payouts',
		'recurring-referrals',
	), true ) ) {
		return sprintf( 'affiliate-wp-%1$s/affiliate-wp-%1$s.php', str_replace( '-for-affiliatewp', '', $slug ) );
	}

	// Plugins paths without the 'for-'.
	if ( in_array( $slug, array(
		'affiliate-forms-for-ninja-forms',
		'affiliate-forms-for-gravity-forms',
	), true ) ) {
		return sprintf( 'affiliatewp-%1$s/affiliatewp-%1$s.php', str_replace( 'for-', '', $slug ) );
	}

	// Plugins paths without the '-for-affiliatewp'.
	if ( in_array( $slug, array(
		'zapier-for-affiliatewp',
	), true ) ) {
		return sprintf( 'affiliatewp-%1$s/affiliatewp-%1$s.php', str_replace( '-for-affiliatewp', '', $slug ) );
	}

	// Plugins paths without the '-notifications'.
	if ( in_array( $slug, array(
		'pushover-notifications',
	), true ) ) {
		return sprintf( 'affiliate-wp-%1$s/affiliate-wp-%1$s.php', str_replace( '-notifications', '', $slug ) );
	}

	return sprintf( 'affiliatewp-%1$s/affiliatewp-%1$s.php', $slug );
}


/**
 * Retrieve the addon slug from its path.
 *
 * @param string|null $path Addon path.
 *
 * @return string Return the addon slug.
 */
function affwp_unset_addon_path( ?string $path ): string {
	// Handle null input
	if ( $path === null || $path === '' ) {
		return '';
	}

	// Extract the main part of the filename (without extension)
	$path_parts = explode( '/', trim( $path, '/' ) );
	$filename   = end( $path_parts ); // Get last element (filename)
	$dirname    = prev( $path_parts ); // Get second last element (folder name)

	// Remove file extension
	$filename = preg_replace( '/\.php$/', '', $filename );

	// Check specific transformations

	// Cases that use 'affwp-' instead of 'affiliatewp-'
	if ( $filename === 'affwp-affiliate-dashboard-sharing' ) {
		return 'affiliate-dashboard-sharing';
	}

	// Cases that use 'affiliate-wp-'
	if ( preg_match( '/^affiliate-wp-(.+)$/', $filename, $matches ) ) {
		return $matches[1];
	}

	// Cases that remove 'for-' prefix
	if ( preg_match( '/^affiliatewp-(.+)$/', $filename, $matches ) ) {
		$slug = $matches[1];
		if ( in_array( $slug, array( 'ninja-forms', 'gravity-forms' ) ) ) {
			return 'affiliate-forms-for-' . $slug;
		}

		return $slug;
	}

	// Cases that remove '-for-affiliatewp' suffix
	if ( preg_match( '/^affiliatewp-(.+)$/', $filename, $matches ) ) {
		return str_replace( '-for-affiliatewp', '', $matches[1] );
	}

	// Cases that remove '-notifications' suffix
	if ( preg_match( '/^affiliate-wp-(.+)$/', $filename, $matches ) ) {
		return str_replace( '-notifications', '', $matches[1] );
	}

	// Default return slug (assuming normal case)
	return $filename;
}