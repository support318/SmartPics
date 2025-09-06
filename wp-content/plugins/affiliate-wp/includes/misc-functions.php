<?php
/**
 * Formatting functions for taking care of proper number formats and such
 *
 * @package     AffiliateWP
 * @subpackage  Functions/Formatting
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Currencies
 *
 * @since 1.0
 * @since 2.26.1 Added symbol and currency codes, removed the Croatian Kuna option.
 *
 * @return array Key/value pairs of currencies where the key is the currency slug
 *               and the value is the translatable labels.
 */
function affwp_get_currencies() : array {
	/**
	 * Filters the list of supported currencies.
	 *
	 * @since 1.0
	 *
	 * @param array $currencies Key/value pairs of currencies where the key is the currency slug
	 *                          and the value is the translatable labels.
	 */
	return apply_filters(
		'affwp_currencies',
		[
			'USD' => __( 'US Dollar ($) — USD', 'affiliate-wp' ),
			'EUR' => __( 'Euro (€) — EUR', 'affiliate-wp' ),
			'ARS' => __( 'Argentine Peso (ARS$) — ARS', 'affiliate-wp' ),
			'AUD' => __( 'Australian Dollar (A$) — AUD', 'affiliate-wp' ),
			'BDT' => __( 'Bangladeshi Taka (৳) — BDT', 'affiliate-wp' ),
			'BTC' => __( 'Bitcoin (₿) — BTC', 'affiliate-wp' ),
			'BRL' => __( 'Brazilian Real (R$) — BRL', 'affiliate-wp' ),
			'BGN' => __( 'Bulgarian Lev (лв) — BGN', 'affiliate-wp' ),
			'CAD' => __( 'Canadian Dollar (C$) — CAD', 'affiliate-wp' ),
			'CLP' => __( 'Chilean Peso (CLP$) — CLP', 'affiliate-wp' ),
			'CNY' => __( 'Chinese Yuan (¥) — CNY', 'affiliate-wp' ),
			'COP' => __( 'Colombian Peso (COP$) — COP', 'affiliate-wp' ),
			'CZK' => __( 'Czech Koruna (Kč) — CZK', 'affiliate-wp' ),
			'DKK' => __( 'Danish Krone (kr) — DKK', 'affiliate-wp' ),
			'DOP' => __( 'Dominican Peso (RD$) — DOP', 'affiliate-wp' ),
			'EGP' => __( 'Egyptian Pound (£E) — EGP', 'affiliate-wp' ),
			'HKD' => __( 'Hong Kong Dollar (HK$) — HKD', 'affiliate-wp' ),
			'HUF' => __( 'Hungarian Forint (Ft) — HUF', 'affiliate-wp' ),
			'ISK' => __( 'Icelandic Krona (kr) — ISK', 'affiliate-wp' ),
			'IDR' => __( 'Indonesian Rupiah (Rp) — IDR', 'affiliate-wp' ),
			'INR' => __( 'Indian Rupee (₹) — INR', 'affiliate-wp' ),
			'ILS' => __( 'Israeli Shekel (₪) — ILS', 'affiliate-wp' ),
			'IRR' => __( 'Iranian Rial (﷼) — IRR', 'affiliate-wp' ),
			'JPY' => __( 'Japanese Yen (¥) — JPY', 'affiliate-wp' ),
			'KES' => __( 'Kenyan Shilling (KSh) — KES', 'affiliate-wp' ),
			'KZT' => __( 'Kazakhstani Tenge (₸) — KZT', 'affiliate-wp' ),
			'KIP' => __( 'Lao Kip (₭) — KIP', 'affiliate-wp' ),
			'MYR' => __( 'Malaysian Ringgit (RM) — MYR', 'affiliate-wp' ),
			'MXN' => __( 'Mexican Peso (Mex$) — MXN', 'affiliate-wp' ),
			'NPR' => __( 'Nepali Rupee (₨) — NPR', 'affiliate-wp' ),
			'NGN' => __( 'Nigerian Naira (₦) — NGN', 'affiliate-wp' ),
			'NOK' => __( 'Norwegian Krone (kr) — NOK', 'affiliate-wp' ),
			'NZD' => __( 'New Zealand Dollar (NZ$) — NZD', 'affiliate-wp' ),
			'PKR' => __( 'Pakistani Rupee (₨) — PKR', 'affiliate-wp' ),
			'PYG' => __( 'Paraguayan Guaraní (₲) — PYG', 'affiliate-wp' ),
			'PHP' => __( 'Philippine Peso (₱) — PHP', 'affiliate-wp' ),
			'PLN' => __( 'Polish Zloty (zł) — PLN', 'affiliate-wp' ),
			'GBP' => __( 'Pound Sterling (£) — GBP', 'affiliate-wp' ),
			'RON' => __( 'Romanian Leu (lei) — RON', 'affiliate-wp' ),
			'RUB' => __( 'Russian Ruble (₽) — RUB', 'affiliate-wp' ),
			'SAR' => __( 'Saudi Arabian Riyal (﷼) — SAR', 'affiliate-wp' ),
			'SGD' => __( 'Singapore Dollar (S$) — SGD', 'affiliate-wp' ),
			'ZAR' => __( 'South African Rand (R) — ZAR', 'affiliate-wp' ),
			'KRW' => __( 'South Korean Won (₩) — KRW', 'affiliate-wp' ),
			'SEK' => __( 'Swedish Krona (kr) — SEK', 'affiliate-wp' ),
			'CHF' => __( 'Swiss Franc (CHF) — CHF', 'affiliate-wp' ),
			'TWD' => __( 'Taiwan New Dollar (NT$) — TWD', 'affiliate-wp' ),
			'THB' => __( 'Thai Baht (฿) — THB', 'affiliate-wp' ),
			'TND' => __( 'Tunisian Dinar (DT) — TND', 'affiliate-wp' ),
			'TRY' => __( 'Turkish Lira (₺) — TRY', 'affiliate-wp' ),
			'AED' => __( 'United Arab Emirates Dirham (د.إ) — AED', 'affiliate-wp' ),
			'UAH' => __( 'Ukrainian Hryvnia (₴) — UAH', 'affiliate-wp' ),
			'VND' => __( 'Vietnamese Dong (₫) — VND', 'affiliate-wp' ),
		]
	);
}

/**
 * The time (timestamp) HRK is deprecated.
 *
 * @since  2.11.0
 *
 * @see affwp_maybe_remove_hrk().
 * @see Affiliate_WP_Admin_Notices::hrk_notice().
 *
 * @TODO Remove after 1/1/2023.
 *
 * @return int
 */
function affwp_hrk_time() {
	return strtotime( defined( 'AFFWP_HRK_TIME' ) ? AFFWP_HRK_TIME : '1/1/2023' );
}

/**
 * Remove HRK currency on 1/1/2023.
 *
 * @since  2.11.0
 *
 * @see Affiliate_WP_Admin_Notices::hrk_notice().
 * @see affwp_hrk_time().
 *
 * @TODO Remove after 1/1/2023.
 *
 * @param  array $currencies Currencies.
 * @return array             Currencies.
 */
function affwp_maybe_remove_hrk( $currencies ) {

	if ( ! is_array( $currencies ) ) {
		return $currencies; // Should be an array.
	}

	if ( ! isset( $currencies['HRK'] ) ) {
		return $currencies; // No longer there (this function should have been deleted).
	}

	if ( time() < affwp_hrk_time() ) {
		return $currencies; // Keep HRK until January 1st 2023.
	}

	unset( $currencies['HRK'] );

	return $currencies;
}
add_filter( 'affwp_currencies', 'affwp_maybe_remove_hrk' );

/**
 * Retrieves the store's set currency.
 *
 * @since 1.0
 *
 * @return string The currency code.
 */
function affwp_get_currency() {
	$currency = affiliate_wp()->settings->get( 'currency', 'USD' );

	/**
	 * Filters the currency.
	 *
	 * @since 1.0
	 *
	 * @param string $currency Slug for the current currency.
	 */
	return apply_filters( 'affwp_currency', $currency );
}

/**
 * Sanitizes an amount.
 *
 * Returns a sanitized amount by stripping out thousands separators.
 *
 * @since 1.0
 *
 * @param string $amount Amount to format.
 * @return string Newly sanitized amount.
 */
function affwp_sanitize_amount( $amount ) {

	$is_negative   = false;
	$thousands_sep = affiliate_wp()->settings->get( 'thousands_separator', ',' );
	$decimal_sep   = affiliate_wp()->settings->get( 'decimal_separator', '.' );

	// Sanitize the amount
	if ( $decimal_sep == ',' && false !== ( $found = strpos( $amount, $decimal_sep ) ) ) {
		if ( ( $thousands_sep == '.' || $thousands_sep == ' ' ) && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
			$amount = str_replace( $thousands_sep, '', $amount );
		} elseif ( empty( $thousands_sep ) && false !== ( $found = strpos( $amount, '.' ) ) ) {
			$amount = str_replace( '.', '', $amount );
		}

		$amount = str_replace( $decimal_sep, '.', $amount );
	} elseif ( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( $thousands_sep, '', $amount );
	}

	if ( $amount < 0 ) {
		$is_negative = true;
	}

	$amount = preg_replace( '/[^0-9\.]/', '', $amount );

	/**
	 * Filter number of decimals to use for prices
	 *
	 * @since 1.0
	 *
	 * @param int        $number Number of decimals
	 * @param int|string $amount Price
	 */
	$decimals = apply_filters( 'affwp_sanitize_amount_decimals', affwp_get_decimal_count(), $amount );
	$amount   = number_format( (float) $amount, $decimals, '.', '' );

	if ( $is_negative ) {
		$amount *= -1;
	}

	/**
	 * Filter the sanitized price before returning
	 *
	 * @since 1.0
	 *
	 * @param string $amount Price
	 */
	return apply_filters( 'affwp_sanitize_amount', $amount );
}

/**
 * Formats an amount.
 *
 * @since 1.0
 *
 * @param string $amount   Price amount to format
 * @param string $decimals Whether or not to use decimals.  Useful when set to false for non-currency numbers.
 * @return string $amount Newly formatted amount or Price Not Available
 */
function affwp_format_amount( $amount, $decimals = true ) {
	global $affwp_options;

	$thousands_sep = affiliate_wp()->settings->get( 'thousands_separator', ',' );
	$decimal_sep   = affiliate_wp()->settings->get( 'decimal_separator', '.' );

	// Format the amount
	if ( $decimal_sep == ',' && false !== ( $sep_found = strpos( $amount, $decimal_sep ) ) ) {
		$whole  = substr( $amount, 0, $sep_found );
		$part   = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
		$amount = $whole . '.' . $part;
	}

	// Strip , from the amount (if set as the thousands separator)
	if ( $thousands_sep == ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = floatval( str_replace( ',', '', $amount ) );
	}

	if ( empty( $amount ) ) {
		$amount = 0;
	}

	if ( $decimals ) {
		/**
		 * Filters the number of decimals to use when formatting amounts.
		 *
		 * @since 1.0
		 *
		 * @param int   $decimals Number of decimals to use.
		 * @param float $amount   Amount to format.
		 */
		$decimals = apply_filters( 'affwp_format_amount_decimals', affwp_get_decimal_count(), $amount );
	} else {
		$decimals = 0;
	}

	$formatted = number_format( $amount, $decimals, $decimal_sep, $thousands_sep );

	/**
	 * Filters the formatted amount.
	 *
	 * @since 1.0
	 *
	 * @param string $formatted     Formatted amount.
	 * @param float  $amount        Amount to format.
	 * @param int    $decimals      Number of decimals used to format the amount.
	 * @param string $decimal_sep   Decimal separator used when formatting the amount.
	 * @param string $thousands_sep Thousands separator used when formatting the amount.
	 */
	return apply_filters( 'affwp_format_amount', $formatted, $amount, $decimals, $decimal_sep, $thousands_sep );
}

/**
 * Retrieves the number of decimals to round to.
 *
 * @since 1.8
 *
 * @return int Number of decimal places.
 */
function affwp_get_decimal_count() {
	/**
	 * Filter the number decimals to round to.
	 *
	 * @since 1.8
	 *
	 * @param int $decimals Number of decimals. Default 2.
	 */
	return apply_filters( 'affwp_decimal_count', 2 );
}

/**
 * Formats referral rate based on the given type.
 *
 * @since 1.9
 *
 * @param int    $rate   Referral rate.
 * @param string $type   Optional. Rate type. Accepts 'percentage' or 'flat'. Default 'percentage'.
 * @return string Formatted rate string.
 */
function affwp_format_rate( $rate, $type = 'percentage' ) {
	if ( 'percentage' === $type ) {
		$rate = affwp_abs_number_round( $rate * 100 ) . '%';
	} else {
		$rate = affwp_currency_filter( $rate );
	}

	/**
	 * Filter the rate format.
	 *
	 * @since 1.9
	 *
	 * @param string $rate Formatted rate.
	 * @param string $type Rate type.
	 */
	return apply_filters( 'affwp_format_rate', $rate, $type );
}

/**
 * Formats the currency display.
 *
 * @since 1.0
 * @param string $amount Amount.
 * @return array Correctly-formatted currency.
 */
function affwp_currency_filter( $amount ) {

	$currency = affwp_get_currency();
	$position = affiliate_wp()->settings->get( 'currency_position', 'before' );

	$negative = $amount < 0;

	if ( $negative ) {
		$amount = substr( $amount, 1 ); // Remove proceeding "-" -
	}

	if ( $position == 'before' ) :
		switch ( $currency ) :
			case 'GBP':
				$formatted = '&pound;' . $amount;
				break;
			case 'BRL':
				$formatted = 'R&#36;' . $amount;
				break;
			case 'EUR':
				$formatted = '&euro;' . $amount;
				break;
			case 'USD':
			case 'AUD':
			case 'CAD':
			case 'HKD':
			case 'MXN':
			case 'SGD':
				$formatted = '&#36;' . $amount;
				break;
			case 'RON':
				$formatted = 'lei' . $amount;
				break;
			case 'UAH':
				$formatted = '&#8372;' . $amount;
				break;
			case 'JPY':
				$formatted = '&yen;' . $amount;
				break;
			case 'KRW':
				$formatted = '&#8361;' . $amount;
				break;
			case 'PKR':
				$formatted = '&#8360;' . $amount;
				break;
			default:
				$formatted = $currency . ' ' . $amount;
				break;
		endswitch;

		/**
		 * Filters the formatted amount when the currency is displayed before the amount.
		 *
		 * The dynamic portion of the hook, `$currency`, refers to the currency.
		 *
		 * @since 1.0
		 *
		 * @param string $formatted The formatted amount.
		 * @param string $currency  Currency used to format the amount.
		 * @param float  $amount    Amount to be formatted.
		 */
		$formatted = apply_filters( 'affwp_' . strtolower( $currency ) . '_currency_filter_before', $formatted, $currency, $amount );
	else :
		switch ( $currency ) :
			case 'GBP':
				$formatted = $amount . '&pound;';
				break;
			case 'BRL':
				$formatted = $amount . 'R&#36;';
				break;
			case 'EUR':
				$formatted = $amount . '&euro;';
				break;
			case 'USD':
			case 'AUD':
			case 'CAD':
			case 'HKD':
			case 'MXN':
			case 'SGD':
				$formatted = $amount . '&#36;';
				break;
			case 'RON':
				$formatted = $amount . 'lei';
				break;
			case 'UAH':
				$formatted = $amount . '&#8372;';
				break;
			case 'JPY':
				$formatted = $amount . '&yen;';
				break;
			case 'KRW':
				$formatted = $amount . '&#8361;';
				break;
			case 'IRR':
				$formatted = $amount . '&#65020;';
			case 'RUB':
				$formatted = $amount . '&#8381;';
				break;
			default:
				$formatted = $amount . ' ' . $currency;
				break;
		endswitch;

		/**
		 * Filters the formatted amount when the currency is displayed following the amount.
		 *
		 * The dynamic portion of the hook, `$currency`, refers to the currency.
		 *
		 * @since 1.0
		 *
		 * @param string $formatted The formatted amount.
		 * @param string $currency  Currency used to format the amount.
		 * @param float  $amount    Amount to be formatted.
		 */
		$formatted = apply_filters( 'affwp_' . strtolower( $currency ) . '_currency_filter_after', $formatted, $currency, $amount );
	endif;

	if ( $negative ) {
		// Prepend the mins sign before the currency sign
		$formatted = '-' . $formatted;
	}

	return $formatted;
}

/**
 * Sets the number of decimal places per currency.
 *
 * @since 1.4.2
 *
 * @param int $decimals Optional. Number of decimal places. Default 2.
 * @return int Currency filtered for the number of decimals.
 */
function affwp_currency_decimal_filter( $decimals = 2 ) {
	global $affwp_options;

	$currency = affwp_get_currency();

	switch ( $currency ) {
		case 'RIAL':
		case 'JPY':
		case 'TWD':
		case 'KRW':
			$decimals = 0;
			break;
	}

	return $decimals;
}
add_filter( 'affwp_decimal_count', 'affwp_currency_decimal_filter' );

/**
 * Converts an object to an associative array.
 *
 * Can handle multidimensional arrays.
 *
 * @since 1.0
 *
 * @param array|object $data Object or array to process.
 * @return array Array.
 */
function affwp_object_to_array( $data ) {
	if ( is_array( $data ) || is_object( $data ) ) {
		$result = [];
		foreach ( $data as $key => $value ) {
			$result[ $key ] = affwp_object_to_array( $value );
		}
		return $result;
	}
	return $data;
}

/**
 * Outputs the localized month name for a given month number.
 *
 * Takes a month number and returns the name three letter name of it.
 *
 * @since 1.0
 *
 * @param int $n Month number. Use 1 through 12 for the current year, or 0 through -11
 *               for the previous year.
 * @return string Short month name.
 */
function affwp_month_num_to_name( $n ) {
	$timestamp = mktime( 0, 0, 0, $n, 1, 2005 );

	return date_i18n( 'M', $timestamp );
}

/**
 * Checks whether a given PHP function is disabled.
 *
 * @since 1.0
 *
 * @param string $function Name of the function.
 * @return bool Whether or not the PHP function is disabled.
 */
function affwp_is_func_disabled( $function ) {
	$disabled = explode( ',', ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}

if ( ! function_exists( 'cal_days_in_month' ) ) {
	// Fallback in case the calendar extension is not loaded in PHP
	// Only supports Gregorian calendar
	function cal_days_in_month( $calendar, $month, $year ) {
		return date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
	}
}

/**
 * Gets the value of the referral variable.
 *
 * If no format is specified, the default will be used.
 *
 * @since 1.6
 *
 * @param string $format       Optional. Referral format passed in via the `[affiliate_referral_url]`
 *                             shortcode. Default empty.
 * @param int    $affiliate_id Optional. Affiliate ID or username. Default 0 (current affiliate).
 * @return string Value of the referral variable.
 */
function affwp_get_referral_format_value( $format = '', $affiliate_id = 0 ) {

	// get affiliate's user ID
	$user_id = affwp_get_affiliate_user_id( $affiliate_id );

	if ( ! $format ) {
		$format = affwp_get_referral_format();
	}

	switch ( $format ) {

		case 'username':
			$value = urlencode( affwp_get_affiliate_username( $affiliate_id ) );
			break;

		case 'id':
		default:
			$value = affwp_get_affiliate_id( $user_id );
			break;

	}

	/**
	 * Filters the value of the given referral format.
	 *
	 * @since 1.6
	 *
	 * @param int|string $value        Affiliate field.
	 * @param string     $format       Affiliate format (field). Accepts 'username' or 'id'.
	 * @param int        $affiliate_id Affiliate ID.
	 */
	return apply_filters( 'affwp_get_referral_format_value', $value, $format, $affiliate_id );
}

/**
 * Gets the referral format from Affiliates -> Settings -> General.
 *
 * @since 1.6
 *
 * @return string Value of the global referral format setting. Will be either 'id' or 'username'.
 */
function affwp_get_referral_format() {

	$referral_format = affiliate_wp()->settings->get( 'referral_format', 'id' );

	return $referral_format;
}

/**
 * Checks whether displaying pretty referral URLs is enabled from Affiliates -> Settings -> General.
 *
 * @since 1.6
 *
 * @return bool True if displaying pretty referral URLs is enabled otherwise false.
 */
function affwp_is_pretty_referral_urls() {

	$is_pretty_affiliate_urls = affiliate_wp()->settings->get( 'referral_pretty_urls' );

	if ( $is_pretty_affiliate_urls ) {
		return (bool) true;
	}

	return (bool) false;
}

/**
 * Checks whether reCAPTCHA is enabled since it requires three options
 *
 * @since 1.7
 * @since 2.18.0 No longer check for enabled checkbox, now use affwp_recaptcha_type() instead.
 * @since 2.28.0 Refactored to use AffWP_Captcha_Manager for consistency.
 *
 * @return bool True if reCAPTCHA is enabled, otherwise false.
 */
function affwp_is_recaptcha_enabled() : bool {
	return AffWP_Captcha_Manager::is_enabled( 'recaptcha' );
}

/**
 * The reCAPTCHA type.
 *
 * @since 2.10.0
 *
 * @return string reCAPTCHA Type.
 */
function affwp_recaptcha_type() {
	return affiliate_wp()->settings->get( 'captcha_type', 'none' );
}

/**
 * Checks if Turnstile is enabled.
 *
 * @since 2.28.0
 *
 * @return bool True if Turnstile is enabled, otherwise false.
 */
function affwp_is_turnstile_enabled() : bool {
	return AffWP_Captcha_Manager::is_enabled( 'turnstile' );
}

/**
 * Checks if hCaptcha is enabled.
 *
 * @since 2.28.0
 *
 * @return bool True if hCaptcha is enabled, otherwise false.
 */
function affwp_is_hcaptcha_enabled() : bool {
	return AffWP_Captcha_Manager::is_enabled( 'hcaptcha' );
}

/**
 * Checks if any CAPTCHA system is enabled.
 *
 * @since 2.28.0
 *
 * @return bool True if any CAPTCHA system is enabled, otherwise false.
 */
function affwp_is_captcha_enabled() : bool {
	return AffWP_Captcha_Manager::is_any_enabled();
}

/**
 * Check if CAPTCHA is enabled for login forms.
 *
 * @since 2.28.0
 *
 * @return bool True if CAPTCHA is enabled for login forms.
 */
function affwp_is_login_captcha_enabled() : bool {
	return AffWP_Captcha_Manager::is_login_enabled();
}

/**
 * Check if reCAPTCHA v2 is enabled.
 *
 * @since 2.28.0
 *
 * @return bool True if reCAPTCHA v2 is enabled, otherwise false.
 */
function affwp_is_recaptcha_v2_enabled() : bool {
	return AffWP_Captcha_Manager::is_enabled( 'recaptcha' ) && 'v2' === affiliate_wp()->settings->get( 'recaptcha_type', 'v2' );
}

/**
 * Sanitizes values to an absolute number, rounded to the required decimal place.
 *
 * Allows zero values, but ignores truly empty values.
 *
 * The correct type will be used automatically, depending on its value:
 *
 * - Whole numbers (including numbers with a 0 value decimal) will be return as ints
 * - Decimal numbers will be returned as floats
 * - Decimal numbers ending with 0 will be returned as strings
 *
 * 1     => (int) 1
 * 1.0   => (int) 1
 * 0.00  => (int) 0
 * 1.01  => (float) 1.01
 * 1.019 => (float) 1.02
 * 1.1   => (string) 1.10
 * 1.10  => (string) 1.10
 * 1.199 => (string) 1.20
 *
 * @since 1.7
 *
 * @param mixed $val       Value to normalize and round.
 * @param int   $precision Optional. Number of decimal places to round to. Default 2.
 * @return mixed Returns an int, float or string on success, null when empty.
 */
function affwp_abs_number_round( $val, $precision = 2 ) {

	// 0 is a valid value so we check only for other empty values
	if ( is_null( $val ) || '' === $val || false === $val ) {

		return;
	}

	$period_decimal_sep         = preg_match( '/\.\d{1,2}$/', $val );
	$comma_decimal_sep          = preg_match( '/\,\d{1,2}$/', $val );
	$period_space_thousands_sep = preg_match( '/\d{1,3}(?:[.|\s]\d{3})+/', $val );
	$comma_thousands_sep        = preg_match( '/\d{1,3}(?:,\d{3})+/', $val );

	// Convert period and space thousand separators.
	if ( $period_space_thousands_sep && 0 === preg_match( '/\d{4,}$/', $val ) ) {
		$val = str_replace( ' ', '', $val );

		if ( ! $comma_decimal_sep ) {
			if ( ! $period_decimal_sep ) {
				$val = str_replace( '.', '', $val );
			}
		} else {
			$val = str_replace( '.', ':', $val );
		}
	}

	// Convert comma decimal separators.
	if ( $comma_decimal_sep ) {
		$val = str_replace( ',', '.', $val );
	}

	// Clean up temporary replacements.
	if ( $period_space_thousands_sep && $comma_decimal_sep || $comma_thousands_sep ) {
		$val = str_replace( [ ':', ',' ], '', $val );
	}

	// Value cannot be negative
	$val = abs( floatval( $val ) );

	// Decimal precision must be a absolute integer
	$precision = absint( $precision );

	// Enforce the number of decimal places required (precision)
	$val = sprintf( ( round( $val, $precision ) == intval( $val ) ) ? '%d' : "%.{$precision}f", $val );

	// Convert number to the proper type (int, float, or string) depending on its value
	if ( false === strpos( $val, '.' ) ) {

		$val = absint( $val );

	}

	return $val;
}

/**
 * Makes a URL more "human-readable" by removing unnecessary elements.
 *
 * @since 1.8
 *
 * @param string $url URL to parse.
 * @return string "Human readable" URL.
 */
function affwp_make_url_human_readable( $url ) {
	$parts = parse_url( $url );

	if ( ! $parts ) {
		return $url;
	}

	$path_with_prefixed_slash = empty( $parts['path'] ) ? '' : $parts['path'];
	$path_without_prefix      = substr( $path_with_prefixed_slash, 1 );

	if ( ! empty( $parts['query'] ) ) {

		parse_str( $parts['query'], $query_vars );

		/** @var WP $wp */
		global $wp;

		$public_query_vars = $wp->public_query_vars;

		$query_vars_to_keep = [];

		// Whitelist against public (registered) query vars.
		foreach ( $query_vars as $var => $value ) {

			if ( in_array( $var, $public_query_vars ) ) {
				$query_vars_to_keep[ $var ] = $value;
			}
		}
	}

	if ( ! empty( $query_vars_to_keep ) ) {
		$query_string = '?' . http_build_query( $query_vars_to_keep );
	} else {
		$query_string = '';
	}

	if ( empty( $path_without_prefix ) ) {
		$human_readable = $parts['host'];

		if ( ! empty( $query_string ) ) {
			$human_readable = trailingslashit( $human_readable ) . $query_string;
		}
	} else {
		$human_readable = '../' . trailingslashit( $path_without_prefix ) . $query_string;
	}

	return $human_readable;
}

/**
 * Returns a URL filtering query parameters from a whitelist.
 *
 * This function takes all the public query vars, plus AffiliateWP vars and filter the
 * given url, returning only the query string with params within the whitelist.
 *
 * @since 2.12.0
 *
 * @param string $url URL to parse.
 * @param bool   $suppress_host If true, the final result will return only the path + query strings.
 * @return string The filtered url.
 */
function affwp_get_filtered_url( string $url, bool $suppress_host = false ): string {

	// Breakdown the url.
	$parsed_url = wp_parse_url( $url );

	wp_parse_str( isset( $parsed_url['query'] ) ? $parsed_url['query'] : [], $query_vars );

	if ( ! is_array( $query_vars ) || empty( $query_vars ) ) {
		return $url; // Url has no query params, just return the original url.
	}

	global $wp;

	// Allow other plugins to hook in and change the list of allowed vars.
	$allowed_query_vars = apply_filters(
		'affwp_get_filtered_url_vars',
		array_merge(
			( isset( $wp->public_query_vars ) && is_array( $wp->public_query_vars ) )
					? $wp->public_query_vars
					: [],
			[ 'ref', 'campaign' ]
		)
	);

	// Build the new query string.
	$parsed_url['query'] = http_build_query(
		array_filter(
			$query_vars,
			function ( $var ) use ( $allowed_query_vars ) {
				return in_array( $var, $allowed_query_vars, true );
			},
			ARRAY_FILTER_USE_KEY
		)
	);

	$host = '';

	// Suppress host returning only the path and query string (if exists).
	if (
		false === $suppress_host &&
		isset( $parsed_url['scheme'] ) &&
		isset( $parsed_url['host'] )
	) {

		$port = isset( $parsed_url['port'] ) ? ":{$parsed_url['port']}" : '';
		$host = "{$parsed_url['scheme']}://{$parsed_url['host']}{$port}";
	}

	$path  = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
	$query = isset( $parsed_url['query'] ) ? "?{$parsed_url['query']}" : '';

	if ( empty( $path ) && empty( $query ) ) {
		return $url; // Probably could not be parsed, just return the original.
	}

	return empty( $host )
		? "{$path}{$query}" // Host suppressed.
		: "{$host}{$path}{$query}";
}

/**
 * Cleans the cache for a given object.
 *
 * @since 1.9
 *
 * @param \AffWP\Base_Object $object Base_Object.
 * @return bool True if the item cache was cleaned, false otherwise.
 */
function affwp_clean_item_cache( $object ) {
	if ( ! is_object( $object ) ) {
		return false;
	}

	if ( ! method_exists( $object, 'get_cache_key' ) ) {
		return false;
	}

	$Object_Class = get_class( $object );
	$cache_key    = $Object_Class::get_cache_key( $object->ID );
	$cache_group  = $Object_Class::$object_type;

	// Individual object.
	wp_cache_delete( $cache_key, $cache_group );

	// Prime the item cache.
	$Object_Class::get_instance( $object->ID );

	$db_groups      = $Object_Class::get_db_groups();
	$db_cache_group = isset( $db_groups->secondary ) ? $db_groups->secondary : $db_groups->primary;

	$last_changed = microtime();

	// Invalidate core object queries.
	wp_cache_set( 'last_changed', $last_changed, $db_cache_group );

	// Explicitly invalidate the campaigns cache.
	wp_cache_set( 'last_changed', $last_changed, affiliate_wp()->campaigns->cache_group );
}

/**
 * Outputs AffiliateWP postbox nonces used for visual positioning.
 *
 * @since 1.9
 */
function affwp_add_screen_options_nonces() {

	if ( ! affwp_is_admin_page() ) {
		return;
	}

	wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
	wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
}
add_action( 'admin_footer', 'affwp_add_screen_options_nonces' );

/**
 * Retrieves the logout URL.
 *
 * @since 1.8.8
 * @since 2.25.0 Logout defaults to the Login page.
 *
 * @return string Logout URL.
 */
function affwp_get_logout_url() : string {

	/**
	 * Filters the URL to log out the current user.
	 *
	 * @since 1.8.8
	 *
	 * @param string $logout_url URL to log out the current user.
	 */
	return apply_filters(
		'affwp_logout_url',
		wp_logout_url(
			empty( affiliatewp_get_affiliate_login_page_id() )
				? get_bloginfo( 'url' ) // It is an edge case, but ensure that we have a safe place to redirect if there's no Affiliate Area set.
				: get_permalink( affiliatewp_get_affiliate_login_page_id() )
		)
	);
}

/**
 * Retrieves a list of all published pages.
 *
 * On large sites this can be expensive, so only load if on the settings page
 * or `$force` is set to true.
 *
 * @internal See #1431 and #1038 for more information.
 *
 * @since 1.0
 * @since 1.8.8 Moved to `misc-functions.php` to prevent fatal errors with other plugins
 *              incorrectly loading admin code without actually loading WP admin.
 * @since 2.18.1 Updated to show 'None' for the empty option.
 *
 * @param bool $force Optional. Force the pages to be loaded even if not on settings.
 *                    Default false.
 * @return array A keyed array of published pages where the key is the page ID
 *               and the value is the page title.
 */
function affwp_get_pages( $force = false ) {

	$pages_options = [ 0 => __( 'None', 'affiliate-wp' ) ]; // Blank option

	if ( ( ! isset( $_GET['page'] ) || 'affiliate-wp-settings' != $_GET['page'] ) && ! $force ) {
		return $pages_options;
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}

	return $pages_options;
}

/**
 * Retrieves the current AffiliateWP admin screen.
 *
 * @since 1.9.1
 *
 * @return string|false Current admin page slug, otherwise false.
 */
function affwp_get_current_screen() {

	if ( ! affwp_is_admin_page() ) {
		return false;
	}

	$page_now = false;

	$page_now = ( isset( $_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : false;

	return $page_now;
}

/**
 * Outputs navigation tabs markup in core screens.
 *
 * @since 1.9.5
 *
 * @param array  $tabs       Navigation tabs.
 * @param string $active_tab Active tab slug.
 * @param array  $query_args Optional. Query arguments used to build the tab URLs. Default empty array.
 */
function affwp_navigation_tabs( $tabs, $active_tab, $query_args = [] ) {
	$tabs = (array) $tabs;

	if ( empty( $tabs ) ) {
		return;
	}

	/**
	 * Filters the navigation tabs immediately prior to output.
	 *
	 * @since 1.9.5
	 *
	 * @param array  $tabs       Tabs array.
	 * @param string $active_tab Active tab slug.
	 * @param array  $query_args Query arguments used to build the tab URLs.
	 */
	$tabs = apply_filters( 'affwp_navigation_tabs', $tabs, $active_tab, $query_args );

	foreach ( $tabs as $tab_id => $tab_name ) {
		$query_args = array_merge( $query_args, [ 'tab' => $tab_id ] );
		$tab_url    = add_query_arg( $query_args );

		printf(
			'<a href="%1$s" alt="%2$s" class="%3$s">%4$s</a>',
			esc_url( $tab_url ),
			esc_attr( $tab_name ),
			$active_tab == $tab_id ? 'nav-tab nav-tab-active' : 'nav-tab',
			esc_html( $tab_name )
		);
	}

	/**
	 * Fires immediately after the navigation tabs output.
	 *
	 * @since 1.9.5
	 *
	 * @param array  $tabs Tabs array.
	 * @param string $active_tab Active tab slug.
	 * @param array  $query_args Query arguments used to build the tab URLs.
	 */
	do_action( 'affwp_after_navigation_tabs', $tabs, $active_tab, $query_args );
}

/**
 * Enables stylesheet queue manipulation by wrapping wp_enqueue_style() with added context.
 *
 * @since 1.9.5
 *
 * @param string $handle  Registered stylesheet handle.
 * @param string $context Optional. Context under which to enqueue the stylesheet.
 *                        Default empty.
 */
function affwp_enqueue_style( $handle, $context = '' ) {
	/**
	 * Filters whether to enqueue the given stylesheet.
	 *
	 * The dynamic portion of the hook name, `$handle` refers to the stylesheet handle.
	 *
	 * @since 1.9.5
	 *
	 * @see wp_enqueue_style()
	 *
	 * @param bool   $enqueue Whether to enqueue the stylesheet. Default true.
	 * @param string $context Context (if set) under which to enqueue the stylesheet.
	 */
	if ( true === apply_filters( "affwp_enqueue_style_{$handle}", true, $context ) ) {
		wp_enqueue_style( $handle );
	}
}

/**
 * Enables script queue manipulation by wrapping wp_enqueue_style() with added context.
 *
 * @since 1.9.5
 *
 * @param string $handle  Registered script handle.
 * @param string $context Optional. Context under which to enqueue the script.
 */
function affwp_enqueue_script( $handle, $context = '' ) {
	/**
	 * Filters whether to enqueue the given script.
	 *
	 * The dynamic portion of the hook name, `$handle` refers to the script handle.
	 *
	 * @since 1.9.5
	 *
	 * @see wp_enqueue_script()
	 *
	 * @param bool   $enqueue Whether to enqueue the script. Default true.
	 * @param string $context Context (if set) under which to enqueue the script.
	 */
	if ( true === apply_filters( "affwp_enqueue_script_{$handle}", true, $context ) ) {
		wp_enqueue_script( $handle );
	}
}

/**
 * Controls what forms are shown on the Affiliate Area page.
 *
 * @since 2.0
 */
function affwp_filter_shown_affiliate_area_forms() {

	$form = affiliate_wp()->settings->get( 'affiliate_area_forms' );

	switch ( $form ) {

		case 'registration':
			add_filter( 'affwp_affiliate_area_show_login', '__return_false' );
			break;

		case 'login':
			add_filter( 'affwp_affiliate_area_show_registration', '__return_false' );
			break;

		case 'none':
			add_filter( 'affwp_affiliate_area_show_registration', '__return_false' );
			add_filter( 'affwp_affiliate_area_show_login', '__return_false' );
			break;

		default:
		case 'both':
			break;
	}
}
add_action( 'template_redirect', 'affwp_filter_shown_affiliate_area_forms' );

/**
 * Generates an AffiliateWP admin URL based on the given type.
 *
 * @since 2.0
 * @since 2.5.7 Added support for customers URLs
 *
 * @param string $type       Optional. Type of admin URL. Accepts:
 *                              'affiliates',
 *                              'creatives',
 *                              'payouts',
 *                              'referrals',
 *                              'visits',
 *                              'settings',
 *                              'tools',
 *                              'add-ons'
 *                              Default empty ('affiliate-wp').
 * @param array  $query_args Optional. Query arguments to append to the admin URL. Default empty array.
 * @return string Constructed admin URL.
 */
function affwp_admin_url( $type = '', $query_args = [] ) {
	$page = 'affiliate-wp';

	$whitelist = [
		'affiliates',
		'customers',
		'creatives',
		'payouts',
		'referrals',
		'visits',
		'reports',
		'settings',
		'tools',
		'add-ons',
		'setup-screen',
	];

	if ( in_array( $type, $whitelist, true ) ) {
		// Reroute customers requests.
		if ( 'customers' === $type ) {
			$type = 'affiliates';
		}

		$page = "affiliate-wp-{$type}";
	}

	$admin_query_args = array_merge( [ 'page' => $page ], $query_args );

	$url = add_query_arg( $admin_query_args, admin_url( 'admin.php' ) );

	/**
	 * Filters the AffiliateWP admin URL.
	 *
	 * @since 2.0
	 *
	 * @param string $url        Admin URL.
	 * @param string $type       Admin URL type.
	 * @param array  $query_args Query arguments originally passed to affwp_admin_url().
	 */
	return apply_filters( 'affwp_admin_url', $url, $type, $query_args );
}

/**
 * Generates an AffiliateWP admin link based on the given type.
 *
 * @since 2.0
 *
 * @param string $type       Admin link type.
 * @param string $label      Link label.
 * @param array  $query_args Optional. Query arguments used to build the admin URL.
 *                           Default empty array.
 * @param array  $attributes Optional. Link attributes as key/value pairs.
 *                           Default empty array.
 * @return string HTML markup for the admin link.
 */
function affwp_admin_link( $type, $label, $query_args = [], $attributes = [] ) {
	$attributes = wp_parse_args(
		$attributes,
		[
			'href' => esc_url( affwp_admin_url( $type, $query_args ) ),
		]
	);

	$output = '';
	$i      = 0;
	$count  = count( $attributes );

	foreach ( $attributes as $attribute => $value ) {
		$output .= sprintf( '%1$s="%2$s"', $attribute, esc_attr( $value ) );

		if ( ++$i !== $count ) {
			$output .= ' ';
		}
	}

	$link = sprintf( '<a %1$s>%2$s</a>', $output, $label );

	/**
	 * Filters the AffiliateWP admin link output.
	 *
	 * @since 2.0
	 *
	 * @param string $link       HTML markup for the admin link.
	 * @param string $type       Admin link type.
	 * @param string $label      Link label.
	 * @param array  $attributes Link attributes as key/value pairs.
	 * @param array  $query_args Query arguments used to build the admin URL.
	 */
	return apply_filters( 'affwp_admin_link', $link, $type, $label, $attributes, $query_args );
}

/**
 * Adds an upgrade action to the completed upgrades array.
 *
 * @since 2.0
 *
 * @param string $upgrade_action The action to add to the completed upgrades array.
 * @return bool True if the action was successfully added, otherwise false.
 */
function affwp_set_upgrade_complete( $upgrade_action ) {

	// Check for a valid upgrade action.
	if ( false === affiliate_wp()->utils->upgrades->get_routine( $upgrade_action ) ) {
		return false;
	}

	$completed_upgrades = affwp_get_completed_upgrades();

	$completed_upgrades[] = $upgrade_action;

	// Remove any blanks, and only show uniques.
	$completed_upgrades = array_unique( array_values( $completed_upgrades ) );

	return update_option( 'affwp_completed_upgrades', $completed_upgrades );
}

/**
 * Checks whether an upgrade routine has been run for a specific action.
 *
 * @since  2.0
 *
 * @param string $upgrade_action The upgrade action to check completion for.
 * @return bool True if the upgrade action has been completed, otherwise false.
 */
function affwp_has_upgrade_completed( $upgrade_action ) {

	$completed_upgrades = affwp_get_completed_upgrades();

	$has_completed = in_array( $upgrade_action, $completed_upgrades, true );
	// (Maybe) force an upgrade action to show.
	if ( true === affwp_is_upgrade_forced( $upgrade_action ) ) {
		$has_completed = false;
	}

	return $has_completed;
}

/**
 * Returns true if the specified upgrade action is being forced.
 *
 * @since 2.8
 *
 * @param string $upgrade_action The upgrade action to check.
 *
 * @return bool True if forced, otherwise false.
 */
function affwp_is_upgrade_forced( $upgrade_action ) {
	$is_forced = false;
	if ( isset( $_REQUEST['affwp_force_notice'] ) ) {
		$forced_upgrade_action = sanitize_key( $_REQUEST['affwp_force_notice'] );

		if ( $forced_upgrade_action === $upgrade_action ) {
			$is_forced = true;
		}
	}

	return $is_forced;
}

/**
 * Retrieves the list of completed upgrade actions.
 *
 * @since 2.0
 *
 * @return array The array of completed upgrades.
 */
function affwp_get_completed_upgrades() {

	$completed_upgrades = get_option( 'affwp_completed_upgrades', [] );

	return $completed_upgrades;
}

/**
 * Modifies the allowed mime types for uploads to include CSV.
 *
 * @since 2.1
 *
 * @param array $mime_types List of allowed mime types.
 * @return array Filtered list of allowed mime types.
 */
function affwp_allowed_mime_types( $mime_types = [] ) {
	$mime_types['csv'] = 'text/csv';

	return $mime_types;
}
add_filter( 'upload_mimes', 'affwp_allowed_mime_types' );

/**
 * Retrieves the list of affiliate import fields.
 *
 * @since 2.1
 *
 * @return array Array of affiliate import fields and associated labels.
 */
function affwp_get_affiliate_import_fields() {

	/**
	 * Filters the list of core affiliate import fields.
	 *
	 * @since 2.1
	 *
	 * @param array $fields List of affiliate import fields and associated labels.
	 */
	$fields = apply_filters(
		'affwp_affiliate_import_fields',
		[
			'email'           => __( 'Email (required)', 'affiliate-wp' ),
			'username'        => __( 'Username', 'affiliate-wp' ),
			'name'            => __( 'First/Full Name', 'affiliate-wp' ),
			'last_name'       => __( 'Last Name', 'affiliate-wp' ),
			'payment_email'   => __( 'Payment Email', 'affiliate-wp' ),
			'rate'            => __( 'Rate', 'affiliate-wp' ),
			'rate_type'       => __( 'Rate Type', 'affiliate-wp' ),
			'flat_rate_basis' => __( 'Flat Rate Basis', 'affiliate-wp' ),
			'earnings'        => __( 'Earnings', 'affiliate-wp' ),
			'unpaid_earnings' => __( 'Unpaid Earnings', 'affiliate-wp' ),
			'referrals'       => __( 'Referral Count', 'affiliate-wp' ),
			'visits'          => __( 'Visit Count', 'affiliate-wp' ),
			'status'          => __( 'Status', 'affiliate-wp' ),
			'website_url'     => __( 'Website', 'affiliate-wp' ),
			'date_registered' => __( 'Registration Date', 'affiliate-wp' ),
		]
	);

	// Ensure required fields are set.
	if ( empty( $fields['email'] ) ) {
		$fields['email'] = __( 'Email (required)', 'affiliate-wp' );
	}

	return $fields;
}

/**
 * Retrieves the list of referral import fields.
 *
 * @since 2.1
 * @since 2.6.4 Added support for mapping referral types.
 *
 * @return array Array of referral import fields and associated labels.
 */
function affwp_get_referral_import_fields() {

	/**
	 * Filters the list of core referral import fields.
	 *
	 * @since 2.1
	 * @since 2.6.4 Added support for the referral type field.
	 *
	 * @param array $fields List of referral import fields and associated labels.
	 */
	$fields = apply_filters(
		'affwp_referral_import_fields',
		[
			'affiliate'     => __( 'Affiliate ID or Username (required)', 'affiliate-wp' ),
			'amount'        => __( 'Amount (required)', 'affiliate-wp' ),
			'email'         => __( 'Affiliate Email', 'affiliate-wp' ),
			'username'      => __( 'Affiliate Username', 'affiliate-wp' ),
			'first_name'    => __( 'Affiliate First/Full Name', 'affiliate-wp' ),
			'last_name'     => __( 'Affiliate Last Name', 'affiliate-wp' ),
			'payment_email' => __( 'Payment Email', 'affiliate-wp' ),
			'currency'      => __( 'Currency', 'affiliate-wp' ),
			'description'   => __( 'Description', 'affiliate-wp' ),
			'campaign'      => __( 'Campaign', 'affiliate-wp' ),
			'reference'     => __( 'Reference', 'affiliate-wp' ),
			'context'       => __( 'Context', 'affiliate-wp' ),
			'status'        => __( 'Status', 'affiliate-wp' ),
			'flag'          => __( 'Flag', 'affiliate-wp' ),
			'type'          => __( 'Type', 'affiliate-wp' ),
			'date'          => __( 'Date', 'affiliate-wp' ),
		]
	);

	// Ensure required fields are set.
	if ( empty( $fields['affiliate'] ) ) {
		$fields['affiliate'] = __( 'Affiliate ID or Username (required)', 'affiliate-wp' );
	}

	if ( empty( $fields['amount'] ) ) {
		$fields['amount'] = __( 'Amount (required)', 'affiliate-wp' );
	}

	if ( empty( $fields['type'] ) ) {
		$fields['type'] = 'sale';
	}

	return $fields;
}

/**
 * Outputs import fields markup for the given import type.
 *
 * @since 2.1
 *
 * @param string $type Import fields type. Accepts 'affiliates' or 'referrals'.
 */
function affwp_do_import_fields( $type ) {
	$fields = [];

	switch ( $type ) {
		case 'affiliates':
			$fields = affwp_get_affiliate_import_fields();
			break;

		case 'referrals':
			$fields = affwp_get_referral_import_fields();
			break;

		default:
			break;
	}

	if ( ! empty( $fields ) ) {

		foreach ( $fields as $key => $label ) {
			?>
			<tr>
				<td><?php echo esc_html( $label ); ?></td>
				<td>
					<select name="affwp-import-field[<?php echo esc_attr( $key ); ?>]" class="affwp-import-csv-column">
						<option value=""><?php esc_html_e( '- Ignore this field -', 'affiliate-wp' ); ?></option>
					</select>
				</td>
				<td class="affwp-import-preview-field"><?php esc_html_e( '- Select field to preview data -', 'affiliate-wp' ); ?></td>
			</tr>
			<?php
		}
	}
}

/**
 * Retrieves an HTML5 required attribute if the given registration field is required.
 *
 * @since 2.1
 *
 * @param string $field Registration field to check.
 * @return string An HTML5 'required' attribute if required, otherwise an empty string.
 */
function affwp_required_field_attr( $field ) {
	$required_fields = affiliate_wp()->settings->get( 'required_registration_fields', [] );

	$required = __checked_selected_helper( array_key_exists( $field, $required_fields ), true, false, 'required' );

	return $required;
}

/**
 * Helper to unserialize values based on an object whitelist.
 *
 * @since 2.1.4.2
 *
 * @param string $original Maybe unserialized original, if is needed.
 * @return mixed Unserialized data of any type.
 */
function affwp_maybe_unserialize( $original ) {
	$value = $original;

	if ( is_serialized( $original ) ) {

		preg_match( '/[oO]\s*:\s*\d+\s*:\s*"\s*(?!(?i)(stdClass))/', $original, $matches );

		if ( ! empty( $matches ) ) {
			$value = '';
		} else {
			$value = maybe_unserialize( $original );
		}
	}

	return $value;
}

/**
 * Retrieves the current page number.
 *
 * @since 2.1.12
 *
 * @return int The current page number.
 */
function affwp_get_current_page_number() {
	if ( is_front_page() ) {
		$page = get_query_var( 'page', 1 );
	} else {
		$page = get_query_var( 'paged', 1 );
	}

	return max( $page, 1 );
}

/**
 * Retrieves Single Euro Payments Area (SEPA) countries.
 *
 * @since 2.4
 *
 * @return array A list of SEPA countries.
 */
function affwp_get_sepa_countries() {

	$sepa_countries = [
		'AT' => __( 'Austria', 'affiliate-wp' ),
		'BE' => __( 'Belgium', 'affiliate-wp' ),
		'DK' => __( 'Denmark', 'affiliate-wp' ),
		'FI' => __( 'Finland', 'affiliate-wp' ),
		'FR' => __( 'France', 'affiliate-wp' ),
		'DE' => __( 'Germany', 'affiliate-wp' ),
		'IE' => __( 'Ireland', 'affiliate-wp' ),
		'IT' => __( 'Italy', 'affiliate-wp' ),
		'LU' => __( 'Luxembourg', 'affiliate-wp' ),
		'NL' => __( 'Netherlands', 'affiliate-wp' ),
		'NO' => __( 'Norway', 'affiliate-wp' ),
		'PT' => __( 'Portugal', 'affiliate-wp' ),
		'ES' => __( 'Spain', 'affiliate-wp' ),
		'SE' => __( 'Sweden', 'affiliate-wp' ),
		'CH' => __( 'Switzerland', 'affiliate-wp' ),
		'GB' => __( 'United Kingdom', 'affiliate-wp' ),
	];

	return $sepa_countries;
}

/**
 * Retrieves the list of countries serviced by the Payouts Service.
 *
 * @since 2.4
 *
 * @return array A list of the countries supported by the Payouts Service.
 */
function affwp_get_payouts_service_country_list() {

	$countries = [
		'US' => __( 'United States', 'affiliate-wp' ),
		'CA' => __( 'Canada', 'affiliate-wp' ),
		'GB' => __( 'United Kingdom', 'affiliate-wp' ),
		'AR' => __( 'Argentina', 'affiliate-wp' ),
		'AU' => __( 'Australia', 'affiliate-wp' ),
		'AT' => __( 'Austria', 'affiliate-wp' ),
		'BE' => __( 'Belgium', 'affiliate-wp' ),
		'BR' => __( 'Brazil', 'affiliate-wp' ),
		'BG' => __( 'Bulgaria', 'affiliate-wp' ),
		'CR' => __( 'Costa Rica', 'affiliate-wp' ),
		'HR' => __( 'Croatia', 'affiliate-wp' ),
		'CY' => __( 'Cyprus', 'affiliate-wp' ),
		'CZ' => __( 'Czech Republic', 'affiliate-wp' ),
		'DK' => __( 'Denmark', 'affiliate-wp' ),
		'EG' => __( 'Egypt', 'affiliate-wp' ),
		'EE' => __( 'Estonia', 'affiliate-wp' ),
		'FI' => __( 'Finland', 'affiliate-wp' ),
		'FR' => __( 'France', 'affiliate-wp' ),
		'DE' => __( 'Germany', 'affiliate-wp' ),
		'GR' => __( 'Greece', 'affiliate-wp' ),
		'HK' => __( 'Hong Kong', 'affiliate-wp' ),
		'HU' => __( 'Hungary', 'affiliate-wp' ),
		'IS' => __( 'Iceland', 'affiliate-wp' ),
		'IN' => __( 'India', 'affiliate-wp' ),
		'ID' => __( 'Indonesia', 'affiliate-wp' ),
		'IL' => __( 'Israel', 'affiliate-wp' ),
		'JP' => __( 'Japan', 'affiliate-wp' ),
		'IE' => __( 'Ireland', 'affiliate-wp' ),
		'IT' => __( 'Italy', 'affiliate-wp' ),
		'LV' => __( 'Latvia', 'affiliate-wp' ),
		'LT' => __( 'Lithuania', 'affiliate-wp' ),
		'LU' => __( 'Luxembourg', 'affiliate-wp' ),
		'MY' => __( 'Malaysia', 'affiliate-wp' ),
		'MT' => __( 'Malta', 'affiliate-wp' ),
		'MX' => __( 'Mexico', 'affiliate-wp' ),
		'NL' => __( 'Netherlands', 'affiliate-wp' ),
		'NZ' => __( 'New Zealand', 'affiliate-wp' ),
		'NO' => __( 'Norway', 'affiliate-wp' ),
		'PE' => __( 'Peru', 'affiliate-wp' ),
		'PL' => __( 'Poland', 'affiliate-wp' ),
		'PT' => __( 'Portugal', 'affiliate-wp' ),
		'RO' => __( 'Romania', 'affiliate-wp' ),
		'SG' => __( 'Singapore', 'affiliate-wp' ),
		'SK' => __( 'Slovakia', 'affiliate-wp' ),
		'SI' => __( 'Slovenia', 'affiliate-wp' ),
		'ES' => __( 'Spain', 'affiliate-wp' ),
		'SE' => __( 'Sweden', 'affiliate-wp' ),
		'CH' => __( 'Switzerland', 'affiliate-wp' ),
		'TH' => __( 'Thailand', 'affiliate-wp' ),
		'UY' => __( 'Uruguay', 'affiliate-wp' ),
	];

	return $countries;
}

/**
 * Determines whether the given element is part of the database component.
 *
 * @since 2.5
 *
 * @param mixed $element Element to check.
 * @return bool Whether or not the element is an instance of `Affiliate_WP_DB`.
 */
function affwp_is_db( $element ) {
	return $element instanceof \Affiliate_WP_DB;
}

/**
 * Calculates the percentage of two values.
 *
 * Answers the question like so: $value is what percent of $divided_by?
 *
 * @since 2.5
 *
 * @param float $value      The base value to get percentage from.
 * @param float $divided_by What to divide the value by to get the percent.
 * @return float The percentage, as a float. Will return INF if `$divided_by` is 0.
 */
function affwp_calculate_percentage( $value, $divided_by ) {
	$value      = (float) $value;
	$divided_by = (float) $divided_by;

	// Return INF if dividing by zero.
	if ( $divided_by === 0.0 ) {
		return INF;
	}

	$percentage = ( $value / $divided_by ) * 100;

	return $percentage;
}

/**
 * Formats a number as a percentage.
 *
 * @since 2.5
 *
 * @param float|int $percentage The percentage to format.
 * @param int       $precision  Optional. The number of decimal places to round the percentage. Default 0.
 * @return string The formatted percentage, or an empty string if infinite.
 */
function affwp_format_percentage( $percentage, $precision = 0 ) {

	// Force the percentage to a float.
	$percentage = floatval( $percentage );

	if ( is_infinite( $percentage ) ) {
		return '';
	}

	$percentage = round( $percentage, $precision );

	/* translators: Formatted percentage value. If using '%' to format percentage, must be expressed as '%%' to avoid errors */
	return sprintf( __( '%s%%', 'affiliate-wp' ), $percentage );
}

/**
 * Determines if the given amount is something that can be converted to an amount.
 *
 * @since 2.5.5
 *
 * @param mixed $amount The amount to check.
 * @return true|WP_Error True if the amount if valid. Otherwise, WP_Error explaining why.
 */
function affwp_is_valid_amount( $amount ) {

	// If this is a number, then it's valid. Go ahead and return true.
	if ( is_numeric( $amount ) ) {
		return true;
	}

	// If this is a string, run a regex to see if it is valid.
	if ( is_string( $amount ) ) {

		$separator = affiliate_wp()->settings->get( 'decimal_separator', '.' );

		// Determine if the item is an intended zero string.
		$matched = preg_match( sprintf( '/\$%1$s[0-9]+$|^[0-9]+$|^[0-9]+[\%1$s]([0-9]+$|$)/', $separator ), $amount );

		if ( 1 === $matched ) {
			$valid = true;
		} else {
			$valid = new \WP_Error(
				'affwp_amount_is_malformed',
				'The provided amount is not a valid amount.',
				[ 'amount' => $amount ]
			);
		}
		// Otherwise, this is invalid. Return an error.
	} else {
		$valid = new \WP_Error(
			'affwp_amount_is_invalid_type',
			'The provided amount is not a valid type. Must be a valid numerical string, a float, or an integer.',
			[ 'amount' => $amount, 'type' => gettype( $amount ) ]
		);
	}

	return $valid;
}

/**
 * Determines whether the given integration is active.
 *
 * @since 2.6
 *
 * @param string $integration_slug Integration slug.
 * @return bool True if the integration is active, otherwise false.
 */
function affwp_is_integration_active( $integration_slug ) {
	$integration = affiliate_wp()->integrations->get( $integration_slug );

	if ( is_wp_error( $integration ) ) {
		$is_active = false;
	} else {
		$is_active = $integration->is_active();
	}

	return $is_active;
}

/**
 * Generates an HTML tag attribute as a string.
 *
 * This function creates an HTML attribute as a string with the provided name and value.
 * By default, if the value is empty, the attribute will not be included in the output.
 * This is particularly useful for avoiding unnecessary HTML generation when attribute
 * values may be empty or conditional.
 *
 * @since 2.18.0
 *
 * @param string $name Attribute name
 * @param mixed  $value Attribute value. Arrays will be automatically converted to JSON.
 * @param bool   $hide_empty Do not print the attribute if $value is empty.
 *
 * @return string The attribute to use in a HTML tag.
 */
function affiliatewp_tag_attr( string $name, $value, bool $hide_empty = true ) : string {

	if ( $hide_empty && empty( $value ) ) {
		return '';
	}

	return sprintf(
		' %s="%s"',
		esc_attr( $name ),
		is_array( $value )
			? esc_attr( wp_json_encode( $value ) )
			: esc_attr( $value )
	);
}

/**
 * Creates a 32 character hash from the provided value.
 *
 * @since 2.7
 *
 * @param mixed        $data The value to hash.
 * @param false|string $key  Optional. The secret key to provide. Required if hash needs to be secure.
 *
 * @return string a 32 character hash from the provided value.
 */
function affwp_get_hash( $data, $key = false ) {

	// If object, convert to array.
	if ( is_object( $data ) ) {
		$data = (array) $data;
	}

	// Normalize the array
	if ( is_array( $data ) ) {
		$data = affwp_normalize_array( $data );
	}

	if ( false === $key ) {
		return hash( 'md5', maybe_serialize( $data ) );
	} else {
		return hash_hmac( 'md5', maybe_serialize( $data ), $key );
	}
}

/**
 * Recursively sorts, and optionally mutates an array of arrays.
 *
 * @since 2.7
 *
 * @param array $array The array to sort.
 * @param array $args {
 *     Optional. List of arguments to pass
 *     @type bool $convert_closures If true, closures will be converted to an identifiable string. Default true.
 * }
 *
 * @return array The normalized array
 */
function affwp_normalize_array( array $array, $args = [] ) {
	$defaults = [
		'convert_closures' => true,
	];

	$args = wp_parse_args( $args, $defaults );

	foreach ( $array as $key => $value ) {
		if ( is_array( $value ) ) {
			$array[ $key ] = affwp_normalize_array( $value, $args );
		}

		// If closures need converted, and this is a closure, transform this into an identifiable string.
		if ( true === $args['convert_closures'] && $value instanceof Closure ) {
			$ref  = new ReflectionFunction( $value );
			$file = new SplFileObject( $ref->getFileName() );
			$file->seek( $ref->getStartLine() - 1 );
			$content = '';
			while ( $file->key() < $ref->getEndLine() ) {
				$content .= $file->current();
				$file->next();
			}
			$array[ $key ] = [
				$content,
				$ref->getStaticVariables(),
			];
		}
	}

	// Sorting behavior depends on if the array is associative, or not.
	$is_assoc = count( array_filter( array_keys( $array ), 'is_string' ) ) > 0;

	if ( $is_assoc ) {
		ksort( $array );
	} else {
		sort( $array );
	}

	return $array;
}

/**
 * Strips common AffiliateWP prefixes from a given string.
 *
 * @since 2.8
 *
 * @param string $prefixed_string The prefixed string to remove.
 *
 * @return array|string|string[]|null The unprefixed string.
 */
function affwp_remove_prefix( $prefixed_string ) {
	return preg_replace( '/^(affwp|affiliate_wp|affiliate-wp|affiliatewp)[-_]/i', '', $prefixed_string );
}

/**
 * Translate a string to the equivalent boolean value.
 *
 * Useful to translate some user-friendly values like "yes", "no", "on", "off"
 * commonly used on shortcodes and blocks to a boolean.
 *
 * Check for values like "yes", "1" and "on" then replace by true, other values to false.
 *
 * @since 2.15.0
 *
 * @param string $string A value to convert, like "yes", "1" or "on".
 * @return bool The converted value.
 */
function affwp_string_to_bool( string $string ) : bool {
	return in_array( strtolower( $string ), [ 'yes', '1', 'on' ], true );
}

if ( ! function_exists( 'affwp_kses' ) ) :
	/**
	 * AffiliateWP KSES.
	 *
	 * Our own list of tags to be used with wp_kses.
	 *
	 * @since 2.15.0
	 * @since AFFWP Added parameter to append/replace kses.
	 *
	 * @param array $additional_kses Kses to append or replace in the main array.
	 *
	 * @return array[] Array of default allowable HTML tags.
	 */
	function affwp_kses( array $additional_kses = [] ) : array {

		return apply_filters(
			'affwp_kses',
			array_merge_recursive(
				[
					'address'    => [],
					'a'          => [
						'href'     => true,
						'class'    => true,
						'rel'      => true,
						'rev'      => true,
						'name'     => true,
						'target'   => true,
						'download' => [
							'valueless' => 'y',
						],
					],
					'abbr'       => [],
					'acronym'    => [],
					'area'       => [
						'alt'    => true,
						'coords' => true,
						'href'   => true,
						'nohref' => true,
						'shape'  => true,
						'target' => true,
					],
					'article'    => [
						'align' => true,
					],
					'aside'      => [
						'align' => true,
					],
					'audio'      => [
						'autoplay' => true,
						'controls' => true,
						'loop'     => true,
						'muted'    => true,
						'preload'  => true,
						'src'      => true,
					],
					'b'          => [],
					'bdo'        => [],
					'big'        => [],
					'blockquote' => [
						'cite' => true,
					],
					'br'         => [],
					'button'     => [
						'disabled' => true,
						'name'     => true,
						'type'     => true,
						'value'    => true,
					],
					'caption'    => [
						'align' => true,
					],
					'cite'       => [],
					'code'       => [],
					'col'        => [
						'align'   => true,
						'char'    => true,
						'charoff' => true,
						'span'    => true,
						'valign'  => true,
						'width'   => true,
					],
					'colgroup'   => [
						'align'   => true,
						'char'    => true,
						'charoff' => true,
						'span'    => true,
						'valign'  => true,
						'width'   => true,
					],
					'del'        => [
						'datetime' => true,
					],
					'dd'         => [],
					'dfn'        => [],
					'details'    => [
						'align' => true,
						'open'  => true,
					],
					'div'        => [
						'align' => true,
					],
					'dl'         => [],
					'dt'         => [],
					'em'         => [],
					'fieldset'   => [],
					'figure'     => [
						'align' => true,
					],
					'figcaption' => [
						'align' => true,
					],
					'font'       => [
						'color' => true,
						'face'  => true,
						'size'  => true,
					],
					'footer'     => [
						'align' => true,
					],
					'h1'         => [
						'align' => true,
					],
					'h2'         => [
						'align' => true,
					],
					'h3'         => [
						'align' => true,
					],
					'h4'         => [
						'align' => true,
					],
					'h5'         => [
						'align' => true,
					],
					'h6'         => [
						'align' => true,
					],
					'header'     => [
						'align' => true,
					],
					'hgroup'     => [
						'align' => true,
					],
					'hr'         => [
						'align'   => true,
						'noshade' => true,
						'size'    => true,
						'width'   => true,
					],
					'i'          => [],
					'img'        => [
						'alt'      => true,
						'align'    => true,
						'border'   => true,
						'height'   => true,
						'hspace'   => true,
						'loading'  => true,
						'longdesc' => true,
						'vspace'   => true,
						'src'      => true,
						'usemap'   => true,
						'width'    => true,
					],
					'ins'        => [
						'datetime' => true,
						'cite'     => true,
					],
					'kbd'        => [],
					'label'      => [
						'for' => true,
					],
					'legend'     => [
						'align' => true,
					],
					'li'         => [
						'align' => true,
						'value' => true,
						'class' => true,
					],
					'main'       => [
						'align' => true,
					],
					'map'        => [
						'name' => true,
					],
					'mark'       => [],
					'menu'       => [
						'type' => true,
					],
					'nav'        => [
						'align' => true,
					],
					'object'     => [
						'data' => [
							'required'       => true,
							'value_callback' => '_wp_kses_allow_pdf_objects',
						],
						'type' => [
							'required' => true,
							'values'   => [ 'application/pdf' ],
						],
					],
					'p'          => [
						'align' => true,
					],
					'pre'        => [
						'width' => true,
					],
					'q'          => [
						'cite' => true,
					],
					'rb'         => [],
					'rp'         => [],
					'rt'         => [],
					'rtc'        => [],
					'ruby'       => [],
					's'          => [],
					'samp'       => [],
					'span'       => [
						'align' => true,
						'class' => true,
					],
					'section'    => [
						'align' => true,
					],
					'small'      => [],
					'strike'     => [],
					'strong'     => [],
					'style'      => [],
					'sub'        => [],
					'summary'    => [
						'align' => true,
					],
					'sup'        => [],
					'table'      => [
						'align'       => true,
						'bgcolor'     => true,
						'border'      => true,
						'cellpadding' => true,
						'cellspacing' => true,
						'rules'       => true,
						'summary'     => true,
						'width'       => true,
					],
					'tbody'      => [
						'align'   => true,
						'char'    => true,
						'charoff' => true,
						'valign'  => true,
					],
					'td'         => [
						'abbr'    => true,
						'align'   => true,
						'axis'    => true,
						'bgcolor' => true,
						'char'    => true,
						'charoff' => true,
						'colspan' => true,
						'headers' => true,
						'height'  => true,
						'nowrap'  => true,
						'rowspan' => true,
						'scope'   => true,
						'valign'  => true,
						'width'   => true,
					],
					'textarea'   => [
						'cols'     => true,
						'rows'     => true,
						'disabled' => true,
						'name'     => true,
						'readonly' => true,
					],
					'tfoot'      => [
						'align'   => true,
						'char'    => true,
						'charoff' => true,
						'valign'  => true,
					],
					'th'         => [
						'abbr'    => true,
						'align'   => true,
						'axis'    => true,
						'bgcolor' => true,
						'char'    => true,
						'charoff' => true,
						'colspan' => true,
						'headers' => true,
						'height'  => true,
						'nowrap'  => true,
						'rowspan' => true,
						'scope'   => true,
						'valign'  => true,
						'width'   => true,
					],
					'thead'      => [
						'align'   => true,
						'char'    => true,
						'charoff' => true,
						'valign'  => true,
					],
					'title'      => [],
					'tr'         => [
						'align'   => true,
						'bgcolor' => true,
						'char'    => true,
						'charoff' => true,
						'valign'  => true,
					],
					'track'      => [
						'default' => true,
						'kind'    => true,
						'label'   => true,
						'src'     => true,
						'srclang' => true,
					],
					'tt'         => [],
					'u'          => [],
					'ul'         => [
						'type'  => true,
						'class' => true,
					],
					'ol'         => [
						'start'    => true,
						'type'     => true,
						'reversed' => true,
					],
					'var'        => [],
					'video'      => [
						'autoplay'    => true,
						'controls'    => true,
						'height'      => true,
						'loop'        => true,
						'muted'       => true,
						'playsinline' => true,
						'poster'      => true,
						'preload'     => true,
						'src'         => true,
						'width'       => true,
					],
				],
				$additional_kses
			)
		);
	}

endif;

/**
 * Return the image size (width and height) of an image, based on an attachment_id or URL.
 *
 * @since 2.16.0
 * @param string|int $image_id_or_url An valid attachment_id or a URL.
 * @param string     $image_size Optional image size for an attachment. Only used when a attachment_id is supplied.
 * @return array Returns the width and height in an array.
 */
function affwp_get_image_size( $image_id_or_url, string $image_size = 'full' ) : array {

	if ( is_numeric( $image_id_or_url ) ) {

		$image_src = wp_get_attachment_image_src( $image_id_or_url, $image_size );

		return false === $image_src
			? []
			: [
				'width'  => $image_src[1] ?? 300,
				'height' => $image_src[2] ?? 300,
			];

	}

	if ( ! filter_var( $image_id_or_url, FILTER_VALIDATE_URL ) ) {
		return []; // Bail if it is not a valid URL.
	}

	try {

		$image_info = getimagesize( $image_id_or_url );

		return false === $image_info
			? []
			: [
				'width'  => $image_info[0],
				'height' => $image_info[1],
			];

	} catch ( Exception $e ) {

		return [];

	}

	return [];
}

/**
 * Return the default color values used with QR Codes.
 *
 * @since 2.17.0
 *
 * @return string[] The colors array.
 */
function affiliatewp_get_qrcode_default_colors() : array {

	return [
		'code' => '#444444',
		'bg'   => '#FFFFFF',
	];
}

/**
 * Used to decide if notices classes should be used for the give option or modal classes.
 *
 * @since 2.18.0
 *
 * @param string $option_to_check The desired option to validate.
 * @param string $option_key The current option key.
 * @param string $option_to_compare The option to compare against to decide if a notice should be displayed or modal.
 * @param string $default_class The default class name to return in case the user has access to the feature.
 *
 * @return string
 */
function affiliatewp_get_pro_feature_option_classes(
	string $option_to_check,
	string $option_key,
	string $option_to_compare,
	string $default_class = ''
) : string {

	if ( affwp_can_access_pro_features() || $option_to_check !== $option_key ) {
		return $default_class;
	}

	if ( $option_to_check === $option_to_compare ) {
		return 'addProBadge affwp-education-notice';
	}

	return 'addProBadge affwp-education-modal';
}

/**
 * Check if the current page is the AffiliateWP settings page.
 *
 * @param string $tab Optional. The specific tab to check for.
 * @return bool True if it's the AffiliateWP settings page, false otherwise.
 * @since 2.25.0
 */
function affiliatewp_is_settings_page( string $tab = '' ) : bool {
	// Check if we are in the admin area
	if ( ! is_admin() ) {
		return false;
	}

	$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

	if ( $page !== 'affiliate-wp-settings' ) {
		return false;
	}

	$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

	// If a specific tab is provided, check if it matches the 'tab' parameter
	if ( ! empty( $tab ) && $current_tab === $tab ) {
		return true;
	}

	// If no specific tab is provided, return true as it's the settings page
	if ( empty( $tab ) ) {
		return true;
	}

	return false;
}

/**
 * Get the value of a CONSTANT.
 *
 * @param string $constant The name of the CONSTANT.
 * @param mixed  $default  If you want to return something else other than null,
 *                         set that value here.
 *
 * @since 2.24.2
 *
 * @return mixed The value of the CONSTANT, or null if it doesn't exist.
 */
function affiliatewp_get_constant( string $constant, $default = null ) {
	return defined( $constant ) ? constant( $constant ) : $default;
}

/**
 * Get SALT for hashing.
 *
 * @since 2.24.2
 *
 * @return string Salt for hashing.
 */
function affiliatewp_get_salt() : string {
	return wp_salt( 'auth' );
}

/**
 * Returns HTML template string replacing {{var}} with the data provided.
 *
 * @since 2.26.1
 *
 * @param string $template The HTML template.
 * @param array  $data Key pair values to replace within the template.
 *
 * @return string The template with strings replaced.
 */
function affiliatewp_render_template( string $template, array $data = [] ) : string {
	if ( empty( $data ) ) {
		return $template;
	}

	foreach ( $data as $key => $value ) {
		$template = str_replace( "{{{$key}}}", $value, $template );
	}

	return $template;
}
