<?php
/**
 * Affiliate Functions
 *
 * @package     AffiliateWP
 * @subpackage  Core/Functions
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 *
 * phpcs:disable PSR2.Namespaces.NamespaceDeclaration.BlankLineAfter -- Spaces before comments okay.
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Empty spaces before comments below, easier for reading.
// phpcs:disable Squiz.Commenting.BlockComment.HasEmptyLineBefore -- Empty lines before comment blocks okay.

/**
 * Prefixed Functions.
 *
 * These functions are prefixed with affwp_, affiliatewp_, etc and are
 * accessible without the need for `use function` imports.
 *
 * When adding new functions, consider adding them to the \AffiliateWP\Affiliates\Function
 * namespace in this file and using `use function` to import them.
 *
 * @since 2.26.0
 */
namespace {

	/**
	 * Determines if the specified user ID is an affiliate.
	 *
	 * If no user ID is given, it will check the currently logged in user
	 *
	 * @param int $user_id The User ID.
	 *
	 * @since 1.0
	 * @return bool
	 */
	function affwp_is_affiliate( $user_id = 0 ) {
		return (bool) affwp_get_affiliate_id( $user_id );
	}

	/**
	 * Retrieves the affiliate ID of the specified user
	 *
	 * If no user ID is given, it will check the currently logged in user
	 *
	 * @since 1.0
	 * @since 2.0.3  The current user is no longer taken into consideration in the admin
	 *               or if doing Ajax when `$user_id` is empty.
	 * @since 2.18.2 Added $affiliate_id to wp cache.
	 * @since 2.19.1 Removed WP Cache, see https://github.com/awesomemotive/affiliate-wp/issues/5003
	 *
	 * @param  int $user_id    Optional. User ID. Default is the ID of the current user.
	 * @return int|string|null The value for affiliate_id in the database for the given user_id,
	 *                         or false if the current user isn't logged-in or `$user_id` is empty.
	 */
	function affwp_get_affiliate_id( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			$is_admin_doing_ajax = is_admin() || wp_doing_ajax();

			if ( ! $is_admin_doing_ajax && ! is_user_logged_in() ) {
				return false;
			} elseif ( ! $is_admin_doing_ajax && is_user_logged_in() ) {
				$user_id = get_current_user_id();
			}
		}

		// Note, adding a check to see if this is numeric, or casting as an int, can break the Affiliate Portal.
		return affiliate_wp()->affiliates->get_column_by( 'affiliate_id', 'user_id', $user_id );
	}


	/**
	 * Retrieves the username of the specified affiliate
	 *
	 * If no affiliate ID is given, it will check the currently logged in affiliate
	 *
	 * @since 1.6
	 * @since 1.9 The `$affiliate` parameter now accepts an affiliate object.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return string|false username if affiliate exists, boolean false otherwise
	 */
	function affwp_get_affiliate_username( $affiliate = 0 ) {

		$affiliate = affwp_get_affiliate( $affiliate );

		if ( $affiliate ) {
			$user_info = get_userdata( $affiliate->user_id );

			if ( $user_info ) {
				$username = $user_info->user_login;
				return esc_html( $username );
			}
		}

		return false;
	}

	/**
	 * Retrieves the affiliate first name and/or last name, if set.
	 *
	 * If only one name (first_name or last_name) is provided, this function will return
	 * only that name.
	 *
	 * @since 1.8
	 * @since 1.9 The `$affiliate` parameter now accepts an affiliate object.
	 *
	 * @uses affwp_get_affiliate_id
	 * @uses affwp_get_affiliate
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return string The affiliate user's first and/or last name  if set. An empty string if the affiliate ID
	 *                is invalid or neither first nor last name are set.
	 */
	function affwp_get_affiliate_name( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return '';
		}

		if ( ! $user_info = get_userdata( $affiliate->user_id ) ) {
			return '';
		}

		$first_name = esc_html( $user_info->first_name );
		$last_name  = esc_html( $user_info->last_name );

		// Check if both names are set first.
		if ( ! empty( $first_name ) && ! empty( $last_name ) ) {
			return $first_name . ' ' . $last_name;
		}

		// If neither are set, return an empty string.
		if ( empty( $first_name ) && empty( $last_name ) ) {
			return '';
		}

		// First name only
		if ( ! empty( $first_name ) && empty( $last_name ) ) {
			return $first_name;
		}

		// Last name only
		if ( empty( $first_name ) && ! empty( $last_name ) ) {
			return $last_name;
		}
	}

	/**
	 * Retrieves the affiliate first name, if set.
	 *
	 * @since 2.1.7
	 *
	 * @uses affwp_get_affiliate_id
	 * @uses get_userdata
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return string The affiliate user's first name  if set. An empty string if the affiliate ID
	 *                is invalid or first name is not set.
	 */
	function affwp_get_affiliate_last_name( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return '';
		}

		if ( ! $user_info = get_userdata( $affiliate->user_id ) ) {
			return '';
		}

		return esc_html( $user_info->last_name );
	}

	/**
	 * Retrieves the affiliate first name, if set.
	 *
	 * @since 2.1.7
	 *
	 * @uses affwp_get_affiliate_id
	 * @uses get_userdata
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return string The affiliate user's first name  if set. An empty string if the affiliate ID
	 *                is invalid or first name is not set.
	 */
	function affwp_get_affiliate_first_name( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return '';
		}

		if ( ! $user_info = get_userdata( $affiliate->user_id ) ) {
			return '';
		}

		return esc_html( $user_info->first_name );
	}

	/**
	 * Determines whether or not the affiliate is active.
	 *
	 * If no affiliate ID is given, it will check the currently logged in affiliate
	 *
	 * @since 1.6
	 * @since 1.9 The `$affiliate` parameter now accepts an affiliate object.
	 *
	 * @param int|AffWP\Affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return bool True if the affiliate has an 'active' status, false otherwise.
	 */
	function affwp_is_active_affiliate( $affiliate = 0 ) {

		if ( 'active' == affwp_get_affiliate_status( $affiliate ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves an affiliate's user ID.
	 *
	 * @since 1.0
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return int|false Affiliate user ID, otherwise false.
	 */
	function affwp_get_affiliate_user_id( $affiliate = 0 ) {

		$user_id = false;

		if ( $affiliate = affwp_get_affiliate( $affiliate ) ) {
			$user_id = $affiliate->user_id;
		}

		return $user_id;
	}

	/**
	 * Retrieve the affiliate's gravatar.
	 *
	 * @since 2.23.2
	 *
	 * @param int|AffWP\Affiliate|string $affiliate The affiliate ID or affiliate object.
	 * @param int                        $size The size to render the gravatar.
	 *
	 * @return string The user gravatar.
	 */
	function affwp_get_affiliate_gravatar( $affiliate = 0, int $size = 40 ) : string {

		if ( is_int( $affiliate ) ) {
			$affiliate = affwp_get_affiliate( $affiliate );
		}

		if ( is_a( $affiliate, '\AffWP\Affiliate' ) ) {
			return $affiliate->gravatar( $size );
		}

		return '';
	}

	/**
	 * Retrieves the affiliate object.
	 *
	 * @since 1.0
	 * @since 1.9 The `$affiliate` parameter was made optional. Affiliates can also now
	 *            be retrieved by username.
	 * @since 2.25.0 The `$affiliate` parameter now accepts a custom slug.
	 *
	 * @param int|AffWP\Affiliate|string $affiliate Optional. Affiliate ID, object, username, or custom slug. Default null.
	 * @return AffWP\Affiliate|false Affiliate object if found, otherwise false.
	 */
	function affwp_get_affiliate( $affiliate = 0 ) {

		if ( empty( $affiliate ) ) {
			$affiliate = affwp_get_affiliate_id();
		}

		if ( is_object( $affiliate ) && isset( $affiliate->affiliate_id ) ) {
			$affiliate_id = $affiliate->affiliate_id;
		} elseif ( is_numeric( $affiliate ) ) {
			$affiliate_id = absint( $affiliate );
		} elseif ( is_string( $affiliate ) ) {
			if ( $user = get_user_by( 'login', $affiliate ) ) {
				$affiliate_id = affiliate_wp()->affiliates->get_column_by( 'affiliate_id', 'user_id', $user->ID );

				if ( ! $affiliate_id ) {
					return false;
				}
			} else {
				// Check if the string is a custom slug.
				$meta         = affiliate_wp()->affiliate_meta->get_meta_by_value( 'custom_slug', $affiliate );
				$affiliate_id = $meta->affiliate_id ?? 0;

				if ( ! is_numeric( $affiliate_id ) ) {
					return false;
				}
			}
		} else {
			return false;
		}

		return affiliate_wp()->affiliates->get_object( $affiliate_id );
	}

	/**
	 * Retrieves the affiliate's status.
	 *
	 * @since 1.0
	 * @since 1.9 `$affiliate` was made optional.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return string|false Affiliate status, false otherwise.
	 */
	function affwp_get_affiliate_status( $affiliate = 0 ) {

		$status = false;

		if ( $affiliate = affwp_get_affiliate( $affiliate ) ) {
			$status = $affiliate->status;
		}

		return $status;
	}

	/**
	 * Sets the status for an affiliate
	 *
	 * @since 1.0
	 *
	 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
	 * @param string              $status    Optional. New affiliate status. Default empty.
	 * @return bool True if the new status was set, false otherwise.
	 */
	function affwp_set_affiliate_status( $affiliate, $status = '' ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$old_status = $affiliate->status;

		/**
		 * Fires just prior to update the affiliate status.
		 *
		 * @since 1.0
		 * @since 2.6 Now fires even if the affiliate status is unchanged.
		 *
		 * @param  string $status     The new affiliate status. Optional.
		 * @param  string $old_status The old affiliate status.
		 */
		do_action( 'affwp_set_affiliate_status', $affiliate->ID, $status, $old_status );

		if ( $status === $old_status ) {
			return false; // The old affiliate status is the same as the new status
		}

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'status' => $status ), '', 'affiliate' ) ) {

			return true;
		}
	}

	/**
	 * Retrieves the affiliate's status and returns a translatable string
	 *
	 * @since 1.8
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object and was made optional.
	 * @since 2.3 The `$affiliate` parameter was renamed to `$affiliate_or_status` and now also accepts
	 *            an affiliate status
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default current affiliate.
	 * @return string $status_label A translatable, filterable label indicating affiliate status
	 */
	function affwp_get_affiliate_status_label( $affiliate_or_status = 0 ) {

		if ( is_string( $affiliate_or_status ) ) {
			$affiliate = null;
			$status    = $affiliate_or_status;
		} else {
			$affiliate = affwp_get_affiliate( $affiliate_or_status );

			if ( isset( $affiliate->status ) ) {
				$status = $affiliate->status;
			} else {
				return '';
			}
		}

		$statuses = affwp_get_affiliate_statuses();
		$label    = array_key_exists( $status, $statuses ) ? $statuses[ $status ] : '';

		/**
		 * Filters the affiliate status label.
		 *
		 * @since 1.8
		 * @since 1.9 The `$affiliate` parameter was added.
		 * @since 2.3 The `$status` parameter was added.
		 *
		 * @param string               $label     Localized status label string.
		 * @param AffWP\Affiliate|null $affiliate Affiliate object or null.
		 * @param string               $status    Affiliate status.
		 */
		return apply_filters( 'affwp_get_affiliate_status_label', $label, $affiliate, $status );
	}

	/**
	 * Retrieves the list of affiliate statuses and corresponding labels.
	 *
	 * @since 2.3
	 *
	 * @return array Key/value pairs of statuses where key is the status and the value is the label.
	 */
	function affwp_get_affiliate_statuses() {
		return array(
			'active'   => __( 'Active', 'affiliate-wp' ),
			'inactive' => __( 'Inactive', 'affiliate-wp' ),
			'pending'  => __( 'Pending', 'affiliate-wp' ),
			'rejected' => __( 'Rejected', 'affiliate-wp' ),
		);
	}

	/**
	 * Retrieves the referral rate for an affiliate.
	 *
	 * @since 1.0
	 * @since 1.9 The `$affiliate` parameter now accepts an affiliate object.
	 *
	 * @param int|AffWP\Affiliate $affiliate    Optional. Affiliate ID or object. Default is the current affiliate.
	 * @param bool                $formatted    Optional. Whether to return a formatted rate with %/currency.
	 *                                          Default false.
	 * @param string              $product_rate Optional. A custom product rate that overrides site/affiliate settings.
	 *                                          Default empty.
	 * @param string              $reference    Optional. Reference. Default empty.
	 * @return string Affiliate rate, empty string otherwise.
	 */
	function affwp_get_affiliate_rate( $affiliate = 0, $formatted = false, $product_rate = '', $reference = '' ) {
		// Forward-compat with affiliate objects.
		if ( is_object( $affiliate ) ) {
			if ( isset( $affiliate->affiliate_id ) ) {
				$affiliate_id = $affiliate->affiliate_id;
			} else {
				$affiliate_id = 0;
			}
		} else {
			$affiliate_id = $affiliate;
		}

		// Global referral rate setting, fallback to 20
		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );
		$default_rate = affwp_abs_number_round( $default_rate );

		// Get product-specific referral rate, fallback to global rate
		$product_rate = affwp_abs_number_round( $product_rate );
		$product_rate = ( null !== $product_rate ) ? $product_rate : $default_rate;

		// Get affiliate-specific referral rate
		$affiliate_rate = affiliate_wp()->affiliates->get_column( 'rate', $affiliate_id );

		// Get rate in order of priority: Affiliate -> Product -> Global
		$rate = affwp_abs_number_round( $affiliate_rate );
		$rate = ( null !== $rate ) ? $rate : $product_rate;

		// Get the referral rate type
		$type = affwp_get_affiliate_rate_type( $affiliate_id );

		// Format percentage rates
		$rate = ( 'percentage' === $type ) ? $rate / 100 : $rate;

		/**
		 * Filters the affiliate rate.
		 *
		 * @since 1.0
		 *
		 * @param float  $rate         The affiliate rate.
		 * @param int    $affiliate_id Affiliate ID.
		 * @param string $type         Rate type, usually 'flat' or 'percentage'.
		 */
		$rate = (string) apply_filters( 'affwp_get_affiliate_rate', $rate, $affiliate_id, $type, $reference );

		// Return rate now if formatting is not required
		if ( ! $formatted ) {
			return $rate;
		}

		// Format the rate based on the type
		$rate = affwp_format_rate( $rate, $type );

		return $rate;
	}

	/**
	 * Determines if an affiliate has a custom rate.
	 *
	 * @since 1.5
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object and was made optional.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return bool Whether the affiliate has a custom rate, false if the affiliate doesn't exist.
	 */
	function affwp_affiliate_has_custom_rate( $affiliate = 0 ) {

		if ( $affiliate = affwp_get_affiliate( $affiliate ) ) {
			$custom_rate  = $affiliate->has_custom_rate();
			$affiliate_id = $affiliate->ID;
		} else {
			$custom_rate  = false;
			$affiliate_id = 0;
		}

		/**
		 * Filters whether the affiliate has a custom rate.
		 *
		 * @since 1.5
		 *
		 * @param bool $custom_rate  Whether the affiliate has a custom rate.
		 * @param int  $affiliate_id Affiliate ID.
		 */
		return apply_filters( 'affwp_affiliate_has_custom_rate', $custom_rate, $affiliate_id );
	}

	/**
	 * Retrieves the referral rate type for an affiliate.
	 *
	 * Either "flat" or "percentage"
	 *
	 * @since 1.1
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object and was made optional.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return string Affiliate rate type.
	 */
	function affwp_get_affiliate_rate_type( $affiliate = 0 ) {

		// Default rate type.
		$type = affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' );

		$affiliate_id = 0;

		if ( $affiliate = affwp_get_affiliate( $affiliate ) ) {
			$affiliate_id = $affiliate->ID;

			// Allowed types
			$types = affwp_get_affiliate_rate_types();

			$affiliate_rate_type = $affiliate->rate_type();

			if ( $affiliate_rate_type !== $type ) {
				$type = $affiliate_rate_type;
			}

			if ( ! array_key_exists( $type, $types ) ) {
				$type = 'percentage';
			}
		}

		/**
		 * Filters the affiliate rate type.
		 *
		 * @since 1.1
		 *
		 * @param string $type         Affiliate rate type. Default values will be 'percentage' or 'flat'.
		 * @param int    $affiliate_id Affiliate ID.
		 */
		return apply_filters( 'affwp_get_affiliate_rate_type', $type, $affiliate_id );
	}

	/**
	 * Retrieves the referral flat rate basis for an affiliate.
	 *
	 * Either "per_product" or "per_order"
	 *
	 * @since 2.3
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return string Affiliate flat rate basis.
	 */
	function affwp_get_affiliate_flat_rate_basis( $affiliate = 0 ) {

		// Default rate type.
		$type = affiliate_wp()->settings->get( 'flat_rate_basis', 'per_product' );

		$affiliate_id = 0;

		if ( $affiliate = affwp_get_affiliate( $affiliate ) ) {
			$affiliate_id = $affiliate->ID;

			// Allowed types
			$types = affwp_get_affiliate_flat_rate_basis_types();

			$affiliate_flat_rate_basis_type = $affiliate->flat_rate_basis();

			if ( $affiliate_flat_rate_basis_type !== $type ) {
				$type = $affiliate_flat_rate_basis_type;
			}

			if ( ! array_key_exists( $type, $types ) ) {
				$type = 'per_product';
			}
		}

		/**
		 * Filters the affiliate flat rate basis.
		 *
		 * @since 2.3
		 *
		 * @param string $type         Affiliate flat rate basis. Default values will be 'per_product' or 'per_order'.
		 * @param int    $affiliate_id Affiliate ID.
		 */
		return apply_filters( 'affwp_get_affiliate_flat_rate_basis', $type, $affiliate_id );
	}

	/**
	 * Check to see if a referral rate should be per-order, or per-product.
	 *
	 * Since 2.3
	 *
	 * @param int $affiliate Optional. The affiliate or Affiliate ID. If left blank, this will use the current affiliate.
	 * @return bool True if the rate type is flat, and flat rate basis is set to per order. False otherwise.
	 */
	function affwp_is_per_order_rate( $affiliate = 0 ) {
		$rate_type       = affwp_get_affiliate_rate_type( $affiliate );
		$flat_rate_basis = affwp_get_affiliate_flat_rate_basis( $affiliate );
		$is_order_rate   = 'flat' === $rate_type && 'per_order' === $flat_rate_basis;

		/**
		 * Filters the per order rate boolean.
		 *
		 * @since 2.3
		 *
		 * @param bool   $is_order_rate   True if the rate type is flat, and flat rate basis is set to per order. False otherwise.
		 * @param string $rate_type       The rate type for the current affiliate.
		 * @param string $flat_rate_basis The flat rate basis for the current affiliate.
		 * @param int    $affiliate_id    Affiliate ID.
		 */
		return (bool) apply_filters( 'affwp_is_per_order_rate', $is_order_rate, $rate_type, $flat_rate_basis, $affiliate );
	}

	/**
	 * Retrieves an array of allowed affiliate rate types.
	 *
	 * @since 1.1
	 *
	 * @return array Rate types.
	 */
	function affwp_get_affiliate_rate_types() {

		// Allowed types
		$types = array(
			'percentage' => __( 'Percentage (%)', 'affiliate-wp' ),
			/* translators: Currency name */
			'flat'       => sprintf( __( 'Flat %s', 'affiliate-wp' ), affwp_get_currency() ),
		);

		/**
		 * Filters the available rate types.
		 *
		 * @since 1.1
		 *
		 * @param array $types Array of key/value pairs of rate types.
		 */
		return apply_filters( 'affwp_get_affiliate_rate_types', $types );
	}

	/**
	 * Retrieves an array of allowed flat rate basis types.
	 *
	 * @since 2.3
	 *
	 * @return array flat rate basis types.
	 */
	function affwp_get_affiliate_flat_rate_basis_types() {

		// Allowed types
		$types = array(
			'per_product' => __( 'Flat Rate Commission Per Product Sold', 'affiliate-wp' ),
			'per_order'   => __( 'Flat Rate Commission Per Order', 'affiliate-wp' ),
		);

		/**
		 * Filters the available rate types.
		 *
		 * @since 1.1
		 *
		 * @param array $types Array of key/value pairs of rate types.
		 */
		return apply_filters( 'affwp_get_affiliate_flat_rate_basis_types', $types );
	}

	/**
	 * Retrieves the affiliate's email address.
	 *
	 * @since 1.0
	 *
	 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
	 * @param string|false        $default   Optional. Default email address. Default false.
	 * @return string|false Affiliate email, value of `$default`, or false.
	 */
	function affwp_get_affiliate_email( $affiliate, $default = false ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return $default;
		}

		$user = get_userdata( $affiliate->user_id );

		if ( empty( $user->user_email ) || ! is_email( $user->user_email ) ) {
			return $default;
		}

		return $user->user_email;
	}

	/**
	 * Retrieves the affiliate's payment email address.
	 *
	 * @since 1.7
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object and was made optional.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return string|false Affiliate payment email if set and valid, if not set, the user email. Otherwise false.
	 */
	function affwp_get_affiliate_payment_email( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		return $affiliate->payment_email();
	}

	/**
	 * Retrieves the affiliate's user login (username).
	 *
	 * @since 1.6
	 *
	 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
	 * @param string|false        $default   Optional. Default username. Default false.
	 * @return string|false Affiliate login, value of `$default`, or false.
	 */
	function affwp_get_affiliate_login( $affiliate, $default = false ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return $default;
		}

		$user = get_userdata( $affiliate->user_id );

		if ( empty( $user->user_login ) ) {
			return $default;
		}

		return $user->user_login;
	}

	/**
	 * Deletes an affiliate.
	 *
	 * @since 1.0
	 * @since 2.3 Deletion of all affiliate meta was added
	 * @since 2.6 Deletion of the affiliate coupon was added
	 *
	 * @param int|AffWP\Affiliate $affiliate   Affiliate ID or object.
	 * @param bool                $delete_data Whether to also delete affiliate meta, referral and visit data. Default false.
	 * @return bool True if the affiliate (and optionally data) was deleted, false otherwise.
	 */
	function affwp_delete_affiliate( $affiliate, $delete_data = false ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$affiliate_id = $affiliate->ID;

		$deleted = affiliate_wp()->affiliates->delete( $affiliate_id, 'affiliate' );

		if ( $deleted ) {

			if ( $delete_data ) {

				$affiliate_metas = affiliate_wp()->affiliate_meta->get_meta( $affiliate_id );

				foreach ( $affiliate_metas as $meta_key => $meta_value ) {
					affiliate_wp()->affiliate_meta->delete_meta( $affiliate_id, $meta_key );
				}

				$coupons = affiliate_wp()->affiliates->coupons->get_coupons(
					array(
						'affiliate_id' => $affiliate_id,
						'number'       => -1,
						'fields'       => 'ids',
					)
				);

				$referrals = affiliate_wp()->referrals->get_referrals(
					array(
						'affiliate_id' => $affiliate_id,
						'number'       => -1,
						'fields'       => 'ids',
					)
				);

				$visits = affiliate_wp()->visits->get_visits(
					array(
						'affiliate_id' => $affiliate_id,
						'number'       => -1,
						'fields'       => 'ids',
					)
				);

				foreach ( $coupons as $coupon_id ) {
					affiliate_wp()->affiliates->coupons->delete( $coupon_id );
				}

				foreach ( $referrals as $referral_id ) {
					affiliate_wp()->referrals->delete( $referral_id );
				}

				foreach ( $visits as $visit_id ) {
					affiliate_wp()->visits->delete( $visit_id );
				}

				delete_user_meta( $affiliate->user_id, 'affwp_referral_notifications' );
				delete_user_meta( $affiliate->user_id, 'affwp_promotion_method' );

			}

			/**
			 * Fires immediately after an affiliate is deleted.
			 *
			 * @since 1.0
			 *
			 * @param int             $affiliate_id The affiliate ID.
			 * @param bool            $delete_data  Whether the user data was also flagged for deletion.
			 * @param AffWP\Affiliate $affiliate    Affiliate object.
			 */
			do_action( 'affwp_affiliate_deleted', $affiliate_id, $delete_data, $affiliate );

		}

		return $deleted;
	}

	/**
	 * Retrieves the total paid earnings for an affiliate.
	 *
	 * @since 1.0
	 *
	 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
	 * @param bool                $formatted Optional. Whether to retrieve formatted earnings.
	 *                                       Default false.
	 * @return float|false Affiliate earnings, otherwise false.
	 */
	function affwp_get_affiliate_earnings( $affiliate, $formatted = false ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$earnings = $affiliate->earnings;

		if ( empty( $earnings ) ) {

			$earnings = 0;

		}

		if ( $formatted ) {

			$earnings = affwp_currency_filter( affwp_format_amount( $earnings ) );

		}

		return $earnings;
	}

	/**
	 * Retrieves the total unpaid earnings for an affiliate.
	 *
	 * @since 1.0
	 *
	 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
	 * @param bool                $formatted Optional. Whether to retrieve formatted unpaid earnings.
	 *                                       Default false.
	 * @return float|false Unpaid affiliate earnings, otherwise false.
	 */
	function affwp_get_affiliate_unpaid_earnings( $affiliate, $formatted = false ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$earnings = $affiliate->unpaid_earnings;

		if ( $formatted ) {

			$earnings = affwp_currency_filter( affwp_format_amount( $earnings ) );

		}

		return $earnings;
	}

	/**
	 * Increases an affiliate's total paid earnings by the specified amount.
	 *
	 * @since 1.0
	 *
	 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
	 * @param string|float        $amount    Optional. Amount to increase the affiliate's earnings. Default empty.
	 * @return float|false The affiliate's updated earnings, false otherwise.
	 */
	function affwp_increase_affiliate_earnings( $affiliate, $amount = '' ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		if ( empty( $amount ) || floatval( $amount ) <= 0 ) {
			return false;
		}

		$earnings  = affwp_get_affiliate_earnings( $affiliate->ID );
		$earnings += $amount;
		$earnings  = round( $earnings, affwp_get_decimal_count() );

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'earnings' => $earnings ), '', 'affiliate' ) ) {
			$alltime  = get_option( 'affwp_alltime_earnings' );
			$alltime += $amount;
			update_option( 'affwp_alltime_earnings', $alltime );

			return $earnings;

		} else {

			return false;

		}
	}

	/**
	 * Decreases an affiliate's total paid earnings by the specified amount.
	 *
	 * @since 1.0
	 *
	 * @param int|AffWP\Affiliate $affiliate Affiliate ID or object.
	 * @param string|float        $amount    Optional. Amount to decrease the affiliate's earnings. Default empty.
	 * @return float|false The affiliate's updated earnings, false otherwise.
	 */
	function affwp_decrease_affiliate_earnings( $affiliate, $amount = '' ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		if ( empty( $amount ) || floatval( $amount ) <= 0 ) {
			return false;
		}

		$earnings  = affwp_get_affiliate_earnings( $affiliate->ID );
		$earnings -= $amount;
		$earnings  = round( $earnings, affwp_get_decimal_count() );

		if ( $earnings < 0 ) {
			$earnings = 0;
		}

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'earnings' => $earnings ), '', 'affiliate' ) ) {

			$alltime  = get_option( 'affwp_alltime_earnings' );
			$alltime -= $amount;
			if ( $alltime < 0 ) {
				$alltime = 0;
			}
			update_option( 'affwp_alltime_earnings', $alltime );

			return $earnings;

		} else {

			return false;

		}
	}

	/**
	 * Increases an affiliate's unpaid earnings.
	 *
	 * @since 2.0
	 *
	 * @param \AffWP\Affiliate|int $affiliate Affiliate object or ID.
	 * @param float                $amount    Amount to increase unpaid earnings by.
	 * @param bool                 $replace   Optional. Whether to replace the current unpaid earnings count.
	 *                                        Default false.
	 * @return float|false New unpaid earnings value upon successful update, otherwise false.
	 */
	function affwp_increase_affiliate_unpaid_earnings( $affiliate, $amount, $replace = false ) {
		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		if ( empty( $amount ) || floatval( $amount ) <= 0 ) {
			return false;
		}

		if ( false === $replace ) {
			$unpaid_earnings = affwp_get_affiliate_unpaid_earnings( $affiliate );
		} else {
			$unpaid_earnings = 0;
		}

		$unpaid_earnings += $amount;
		$unpaid_earnings  = round( $unpaid_earnings, affwp_get_decimal_count() );

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'unpaid_earnings' => $unpaid_earnings ), '', 'affiliate' ) ) {

			return $unpaid_earnings;

		} else {

			return false;

		}
	}

	/**
	 * Decreases an affiliate's unpaid earnings.
	 *
	 * @since 2.0
	 *
	 * @param \AffWP\Affiliate|int $affiliate Affiliate object or ID.
	 * @param float                $amount    Amount to decrease unpaid earnings by.
	 * @return float|false New unpaid earnings value upon successful update, otherwise false.
	 */
	function affwp_decrease_affiliate_unpaid_earnings( $affiliate, $amount ) {
		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		if ( empty( $amount ) || floatval( $amount ) <= 0 ) {
			return false;
		}

		$unpaid_earnings  = affwp_get_affiliate_unpaid_earnings( $affiliate );
		$unpaid_earnings -= $amount;
		$unpaid_earnings  = round( $unpaid_earnings, affwp_get_decimal_count() );

		if ( $unpaid_earnings < 0 ) {
			$unpaid_earnings = 0;
		}

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'unpaid_earnings' => $unpaid_earnings ), '', 'affiliate' ) ) {

			return $unpaid_earnings;

		} else {

			return false;

		}
	}

	/**
	 * Retrieves the number of paid referrals for an affiliate.
	 *
	 * @since 1.0
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return int|false The affiliate's referral count, false otherwise.
	 */
	function affwp_get_affiliate_referral_count( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		return $affiliate->referrals;
	}

	/**
	 * Increases an affiliate's total paid referrals by 1.
	 *
	 * @since 1.0
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return float|false The updated referral count, otherwise false.
	 */
	function affwp_increase_affiliate_referral_count( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$referrals  = affwp_get_affiliate_referral_count( $affiliate->ID );
		$referrals += 1;

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'referrals' => $referrals ), '', 'affiliate' ) ) {

			return $referrals;

		} else {

			return false;

		}
	}

	/**
	 * Decreases an affiliate's total paid referrals by 1.
	 *
	 * @since 1.0
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return float|false The updated referral count, otherwise false.
	 */
	function affwp_decrease_affiliate_referral_count( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$referrals  = affwp_get_affiliate_referral_count( $affiliate->ID );
		$referrals -= 1;
		if ( $referrals < 0 ) {
			$referrals = 0;
		}

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'referrals' => $referrals ), '', 'affiliate' ) ) {

			return $referrals;

		} else {

			return false;

		}
	}

	/**
	 * Retrieves an affiliate's total visit count.
	 *
	 * @since 1.0
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return int|false The new affiliate visit count, otherwise false.
	 */
	function affwp_get_affiliate_visit_count( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$visits = $affiliate->visits;

		if ( $visits < 0 ) {
			$visits = 0;
		}

		return absint( $visits );
	}

	/**
	 * Increases an affiliate's total visit count by 1.
	 *
	 * @since 1.0
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return int|false The new affiliate visit count, otherwise false.
	 */
	function affwp_increase_affiliate_visit_count( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$visits  = affwp_get_affiliate_visit_count( $affiliate->ID );
		$visits += 1;

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'visits' => $visits ), '', 'affiliate' ) ) {

			return $visits;

		} else {

			return false;

		}
	}

	/**
	 * Decreases an affiliate's total visit count by 1.
	 *
	 * @since 1.0
	 * @since 1.9 The `$affiliate` parameter can now accept an affiliate object.
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return float|false The affiliate's updated visit count, otherwise false.
	 */
	function affwp_decrease_affiliate_visit_count( $affiliate = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$visits  = affwp_get_affiliate_visit_count( $affiliate->ID );
		$visits -= 1;

		if ( $visits < 0 ) {
			$visits = 0;
		}

		if ( affiliate_wp()->affiliates->update( $affiliate->ID, array( 'visits' => $visits ), '', 'affiliate' ) ) {

			return $visits;

		} else {

			return false;

		}
	}

	/**
	 * Retrieves the affiliate's conversion rate.
	 *
	 * @since 1.0
	 * @since 2.6.1 Added a `$date_range` parameter.
	 *
	 * @param int|AffWP\Affiliate $affiliate  Optional. Affiliate ID or object. Default is the current affiliate.
	 * @param string|array        $date_range {
	 *     Optional. Date string or start/end range to calculate for. Default empty array.
	 *
	 *     @type string $start Start date to calculate for.
	 *     @type string $end   End date to calculate for.
	 * }
	 * @return string|false The affiliate's conversion rate, otherwise false.
	 */
	function affwp_get_affiliate_conversion_rate( $affiliate, $date_range = array() ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$rate = 0;

		$referrals = affwp_count_referrals( $affiliate->ID, array( 'paid', 'unpaid' ), $date_range );
		$visits    = affwp_count_visits( $affiliate->ID, $date_range );

		if ( $visits > 0 ) {
			$rate = $referrals / $visits;
		}

		$rate = affwp_format_rate( $rate );

		/**
		 * Filters the conversion rate.
		 *
		 * @since 1.0
		 * @since 2.6.1 Added a `$date_range` parameter
		 *
		 * @param string $rate         Formatted conversion rate.
		 * @param int    $affiliate_id Affiliate ID.
		 * @param array  $date_range   Date range the conversion rate represents.
		 */
		return apply_filters( 'affwp_get_affiliate_conversion_rate', $rate, $affiliate->ID, $date_range );
	}

	/**
	 * Retrieves the affiliate's tracked campaigns.
	 *
	 * @since 1.7
	 * @since 2.3 The `$args` parameter was added
	 *
	 * @param int|AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @param array               $args      Optional. Arguments used to query an affiliate's campaigns.
	 *                                       Default empty array.
	 * @return array|false The affiliate's campaigns, otherwise false.
	 */
	function affwp_get_affiliate_campaigns( $affiliate = 0, $args = array() ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$defaults = array(
			'number' => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		$args['affiliate_id'] = $affiliate->ID;

		$campaigns = affiliate_wp()->campaigns->get_campaigns( $args );

		/**
		 * Filters the list of campaigns associated with an affiliate.
		 *
		 * @since 1.7
		 *
		 * @param array $campaigns    The affiliate's campaigns.
		 * @param int   $affiliate_id Affiliate ID.
		 */
		return apply_filters( 'affwp_get_affiliate_campaigns', $campaigns, $affiliate->ID );
	}

	/**
	 * Adds a new affiliate to the database.
	 *
	 * @since 1.0
	 * @since 2.6 Added support for a `$dynamic_coupon` argument.
	 * @since 2.18.0 Added support for `$registration_method` and `$registration_url` arguments.
	 *
	 * @see Affiliate_WP_DB_Affiliates::add()
	 *
	 * @param array $data {
	 *     Optional. Array of arguments for adding a new affiliate. Default empty array.
	 *
	 *     @type string $status              Affiliate status. Default 'active'.
	 *     @type string $date_registered     Date the affiliate was registered. Default is the current time.
	 *     @type string $rate                Affiliate-specific referral rate.
	 *     @type string $rate_type           Rate type. Accepts 'percentage' or 'flat'.
	 *     @type string $payment_email       Affiliate payment email.
	 *     @type int    $earnings            Affiliate earnings. Default 0.
	 *     @type int    $referrals           Number of affiliate referrals.
	 *     @type int    $visits              Number of visits.
	 *     @type int    $user_id             User ID used to correspond to the affiliate.
	 *     @type string $user_name           User login. Used to retrieve the affiliate ID if `affiliate_id` and
	 *                                       `user_id` not given.
	 *     @type string $notes               Notes about the affiliate for use by administrators.
	 *     @type string $website_url         The affiliate's website URL.
	 *     @type bool   $dynamic_coupon      Set to true if a dynamic coupon should be created for the new affiliate.
	 *     @type string $registration_method The method used to register the affiliate.
	 *     @type string $registration_url    The URL where the affiliate was registered.
	 * }
	 * @return int|false The ID for the newly-added affiliate, otherwise false.
	 */
	function affwp_add_affiliate( $data = array() ) {

		if ( ! empty( $data['status'] ) ) {
			$status = $data['status'];
		} elseif ( affiliate_wp()->settings->get( 'require_approval' ) ) {
			$status = 'pending';
		} else {
			$status = 'active';
		}

		$data = affiliate_wp()->utils->process_request_data( $data, 'user_name' );

		// If a user email is passed, then attempt to also create a new user.
		if ( ! empty( $data['user_email'] ) ) {

			if ( ! empty( $data['user_name'] ) ) {
				$username = sanitize_text_field( ( $data['user_name'] ) );
			} else {
				$username = sanitize_user( $data['user_email'] );
			}

			$user_args = array(
				'user_email' => sanitize_text_field( $data['user_email'] ),
				'user_login' => $username,
				'user_pass'  => wp_generate_password( 24 ),
			);

			/**
			 * Filters the arguments used for creating new users when adding an affiliate.
			 *
			 * @since 2.1.8
			 *
			 * @param array $user_args Arguments passed to wp_insert_user().
			 * @param array $data      Arguments passed to affwp_add_affiliate().
			 */
			$user_args = apply_filters( 'affwp_add_affiliate_user_args', $user_args, $data );

			// Create the user account.
			$user_id = wp_insert_user( $user_args );

			// Remember we generated a random password for this user.
			update_user_meta( $user_id, 'affwp_generated_pass', true );

			if ( is_wp_error( $user_id ) ) {
				return false;
			}

			$data['user_id'] = $user_id;
		}

		if ( empty( $data['user_id'] ) ) {
			return false;
		} else {
			$user_id = absint( $data['user_id'] );
		}

		$args = array(
			'user_id'             => $user_id,
			'status'              => $status,
			'rate'                => ! empty( $data['rate'] ) ? sanitize_text_field( $data['rate'] ) : '',
			'rate_type'           => ! empty( $data['rate_type'] ) ? sanitize_text_field( $data['rate_type'] ) : '',
			'flat_rate_basis'     => ! empty( $data['flat_rate_basis'] ) ? sanitize_text_field( $data['flat_rate_basis'] ) : '',
			'payment_email'       => ! empty( $data['payment_email'] ) ? sanitize_text_field( $data['payment_email'] ) : '',
			'notes'               => ! empty( $data['notes'] ) ? wp_kses_post( $data['notes'] ) : '',
			'website_url'         => ! empty( $data['website_url'] ) ? sanitize_text_field( $data['website_url'] ) : '',
			'date_registered'     => ! empty( $data['date_registered'] ) ? $data['date_registered'] : '',
			'dynamic_coupon'      => ! empty( $data['dynamic_coupon'] ) ? $data['dynamic_coupon'] : '',
			'registration_method' => ! empty( $data['registration_method'] ) ? sanitize_text_field( $data['registration_method'] ) : '',
			'registration_url'    => ! empty( $data['registration_url'] ) ? sanitize_text_field( $data['registration_url'] ) : '',
		);

		$affiliate_id = affiliate_wp()->affiliates->add( $args );

		if ( $affiliate_id ) {

			affwp_set_affiliate_status( $affiliate_id, $status );

			// Add or delete affiliate notes
			if ( ! empty( $args['notes'] ) ) {
				affwp_update_affiliate_meta( $affiliate_id, 'notes', $args['notes'] );
			}

			// Add registration method.
			affwp_update_affiliate_meta( $affiliate_id, 'registration_method', ! empty( $args['registration_method'] ) ? $args['registration_method'] : 'api' );

			// Add registration URL.
			if ( ! empty( $args['registration_url'] ) ) {
				affwp_update_affiliate_meta( $affiliate_id, 'registration_url', $args['registration_url'] );
			}

			// Enable referral notifications by default for new affiliates.
			affwp_update_affiliate_meta( $affiliate_id, 'referral_notifications', true );

			/**
			 * Fires after adding a user as an affiliate.
			 *
			 * @since 2.15.0
			 * @since 2.23.2 Added data attribute.
			 *
			 * @param int $affiliate_id Affiliate ID.
			 * @param array $data Submited data.
			 */
			do_action( 'affwp_add_new_affiliate', $affiliate_id, $data );

			return $affiliate_id;
		}

		return false;
	}

	/**
	 * Get the date an affiliate was registered.
	 *
	 * @since 2.9.7
	 *
	 * @param  int $affiliate_id The Affiliate's ID.
	 * @return string            Date registered, or empty string if none.
	 */
	function affwp_get_affiliate_date_registered( $affiliate_id = 0 ) {

		if ( false === affwp_get_affiliate_username( $affiliate_id ) ) {
			return ''; // Not an affiliate.
		}

		global $wpdb;

		$registered_by = $wpdb->get_var(
			$wpdb->prepare( "SELECT date_registered FROM {$wpdb->prefix}affiliate_wp_affiliates WHERE affiliate_id = %s", $affiliate_id )
		);

		return is_string( $registered_by )
			? $registered_by
			: '';
	}

	/**
	 * Updates an affiliate.
	 *
	 * @since 1.0
	 * @since 1.9   Support was added for updating an affiliate's status.
	 * @since 2.6.3 Support was added for directly updating an affiliate's earnings and unpaid earnings.
	 *
	 * @todo Document `$data` as a hash notation
	 *
	 * @param array $data Optional. Affiliate data array. Default empty array.
	 * @return bool True if the affiliate was updated, false otherwise.
	 */
	function affwp_update_affiliate( $data = array() ) {
		if ( empty( $data['affiliate_id'] ) ) {
			return false;
		}

		$args         = array();
		$affiliate_id = intval( $data['affiliate_id'] );

		if ( ! $affiliate = affwp_get_affiliate( $affiliate_id ) ) {
			return false;
		}

		$user_id = $affiliate->user_id;

		if ( ! empty( $data['user_id'] ) ) {
			$new_user_id = absint( $data['user_id'] );

			// If it's a new user ID and not already associated with an affiliate, replace it.
			if ( $affiliate->user_id !== $new_user_id
				&& is_wp_error( affwp_get_affiliate_by( 'user_id', $new_user_id ) )
			) {
				$user_id = $new_user_id;
			}
		}

		$args['payment_email']   = ! empty( $data['payment_email'] ) && is_email( $data['payment_email'] ) ? sanitize_text_field( $data['payment_email'] ) : $affiliate->payment_email;
		$args['rate']            = isset( $data['rate'] ) ? sanitize_text_field( $data['rate'] ) : $affiliate->rate;
		$args['rate_type']       = isset( $data['rate_type'] ) ? sanitize_text_field( $data['rate_type'] ) : $affiliate->rate_type;
		$args['earnings']        = ( isset( $data['earnings'] ) && is_numeric( $data['earnings'] ) ) ? floatval( $data['earnings'] ) : $affiliate->earnings;
		$args['unpaid_earnings'] = ( isset( $data['unpaid_earnings'] ) && is_numeric( $data['unpaid_earnings'] ) ) ? floatval( $data['unpaid_earnings'] ) : $affiliate->unpaid_earnings;
		$args['status']          = ! empty( $data['status'] ) ? sanitize_text_field( $data['status'] ) : $affiliate->status;
		$args['user_id']         = $user_id;

		// Reset flat rate basis if the incoming rate type isn't flat.
		if ( ! empty( $data['flat_rate_basis'] ) && 'flat' === $args['rate_type'] ) {
			$args['flat_rate_basis'] = sanitize_text_field( $data['flat_rate_basis'] );
		} else {
			$args['flat_rate_basis'] = '';
		}

		// Set a new date_registered (minus the offset) if it's different from the current date.
		if ( ! empty( $data['date_registered'] ) && $data['date_registered'] !== $affiliate->date_registered ) {
			$timestamp = strtotime( $data['date_registered'] ) - affiliate_wp()->utils->wp_offset;

			$args['date_registered'] = gmdate( 'Y-m-d H:i:s', $timestamp );
		}

		if ( ! empty( $data['rest_id'] ) ) {
			if ( affwp_validate_rest_id( $data['rest_id'] ) ) {
				$args['rest_id'] = sanitize_text_field( $data['rest_id'] );
			}
		}

		/**
		 * Fires immediately before data for the current affiliate is updated.
		 *
		 * @since 1.8
		 *
		 * @param stdClass $affiliate Affiliate object.
		 * @param array    $args      Prepared affiliate data.
		 * @param array    $data      Raw affiliate data.
		 */
		do_action( 'affwp_pre_update_affiliate', $affiliate, $args, $data );

		// Change the affiliate's status if different from their old status
		if ( $args['status'] !== $affiliate->status ) {
			$status = affwp_set_affiliate_status( $affiliate_id, $args['status'] );
		}

		//
		// Coupon Code
		//

		$coupon_args = array();

		if ( ! empty( $data['coupon_code'] ) ) {
			$coupon_code = affwp_sanitize_coupon_code( $data['coupon_code'] );

			if ( is_wp_error( affwp_get_coupon_by( 'coupon_code', $coupon_code ) ) ) {
				$coupon_args['coupon_code'] = $coupon_code;
			}
		}

		if ( ! empty( $data['coupon_code_status'] ) ) {
			$status = sanitize_key( $data['coupon_code_status'] );

			$coupon_args['status'] = $status;
		}

		if ( ! empty( $coupon_args ) ) {
			affiliate_wp()->affiliates->coupons->update_coupon( $affiliate_id, $coupon_args );
		}

		// Coupon templates.
		if ( ! empty( $data['coupon_templates'] ) ) {
			foreach ( $data['coupon_templates'] as $integration => $template_id ) {
				affwp_update_affiliate_meta( $affiliate_id, "{$integration}_coupon_template", $template_id );
			}
		}

		$updated = affiliate_wp()->affiliates->update( $affiliate_id, $args, '', 'affiliate' );

		/**
		 * Fires immediately after an affiliate has been updated.
		 *
		 * @since 1.8
		 *
		 * @param stdClass $affiliate Updated affiliate object.
		 * @param bool     $updated   Whether the update was successful.
		 */
		do_action( 'affwp_updated_affiliate', affwp_get_affiliate( $affiliate ), $updated );

		if ( $updated ) {

			// Add or update affiliate notes.
			if ( ! empty( $data['notes'] ) ) {
				$notes = wp_kses_post( $data['notes'] );

				affwp_update_affiliate_meta( $affiliate_id, 'notes', $notes );
			}

			// Maybe update affiliate's account email.
			if ( isset( $data['account_email'] )
				&& is_email( $data['account_email'] )
				&& $data['account_email'] !== $affiliate->user->user_email
			) {
				$account_email = sanitize_text_field( $data['account_email'] );

				wp_update_user(
					array(
						'ID'         => $user_id,
						'user_email' => $account_email,
					)
				);
			}

			return true;

		}
		return false;
	}

	/**
	 * Updates an affiliate's profile settings.
	 *
	 * @since 1.0
	 *
	 * @todo Document `$data` as a hash notation
	 *
	 * @return bool
	 */
	function affwp_update_profile_settings( $data = array() ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( empty( $data['affiliate_id'] ) ) {
			return false;
		}

		$affiliate_id = absint( $data['affiliate_id'] );
		$user_id      = affwp_get_affiliate_user_id( $affiliate_id );

		if ( $user_id !== get_current_user_id() && ! current_user_can( 'manage_affiliate' ) ) {
			return false;
		}

		if ( ! empty( $data['referral_notifications'] ) ) {

			update_user_meta( $user_id, 'affwp_referral_notifications', '1' );

		} else {

			delete_user_meta( $user_id, 'affwp_referral_notifications' );

		}

		if ( ! empty( $data['payment_email'] ) && is_email( $data['payment_email'] ) ) {
			affiliate_wp()->affiliates->update( $affiliate_id, array( 'payment_email' => $data['payment_email'] ), '', 'affiliate' );
		}

		/**
		 * Fires immediately after an affiliate's profile settings have been updated.
		 *
		 * @since 1.0
		 *
		 * @param array $data Affiliate profile data.
		 */
		do_action( 'affwp_update_affiliate_profile_settings', $data );

		if ( ! empty( $_POST['affwp_action'] ) ) {
			wp_redirect( add_query_arg( 'affwp_notice', 'profile-updated' ) );
			exit;
		}
	}

	/**
	 * Builds an affiliate's referral URL.
	 *
	 * Used by creatives, referral URL generator and [affiliate_referral_url] shortcode
	 *
	 * @since 1.6
	 *
	 * @param array $args {
	 *     Optional. Array of arguments for building an affiliate referral URL. Default empty array.
	 *
	 *     @type int          $affiliate_id Affiliate ID. Default is the current user's affiliate ID.
	 *     @type string|false $pretty       Whether to build a pretty referral URL. Accepts 'yes' or 'no'. False
	 *                                      disables pretty URLs. Default empty, see affwp_is_pretty_referral_urls().
	 *     @type string       $format       Referral format. Accepts 'id' or 'username'. Default empty,
	 *                                      see affwp_get_referral_format().
	 *     @type string       $base_url     Base URL to use for building a referral URL. If specified, should contain
	 *                                      'query' and 'fragment' query vars. 'scheme', 'host', and 'path' query vars
	 *                                      can also be passed as part of the base URL. Default empty.
	 * }
	 * @return string Trailing-slashed value of home_url() when `$args` is empty, built referral URL otherwise.
	 */
	function affwp_get_affiliate_referral_url( $args = array() ) {

		$defaults = array(
			'pretty' => '',
			'format' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		if ( isset( $args['affiliate_id'] ) ) {
			$affiliate = affwp_get_affiliate( $args['affiliate_id'] );
		} else {
			$affiliate = affwp_get_affiliate();
		}

		$affiliate_id = $affiliate ? $affiliate->ID : 0;

		// get format, username or id
		$format = isset( $args['format'] ) ? $args['format'] : affwp_get_referral_format();

		// pretty URLs
		if ( ! empty( $args['pretty'] ) && 'yes' == $args['pretty'] ) {
			// pretty URLS explicitly turned on
			$pretty = true;
		} elseif ( ( ! empty( $args['pretty'] ) && 'no' == $args['pretty'] ) || false === $args['pretty'] ) {
			// pretty URLS explicitly turned off
			$pretty = false;
		} else {
			// pretty URLs set from admin
			$pretty = affwp_is_pretty_referral_urls();
		}

		// get base URL
		if ( isset( $args['base_url'] ) ) {
			$base_url = $args['base_url'];
		} else {
			$base_url = affwp_get_affiliate_base_url();
		}

		// add trailing slash only if no query string exists and there's no fragment identifier
		if ( isset( $args['base_url'] ) && ! array_key_exists( 'query', parse_url( $base_url ) ) && ! array_key_exists( 'fragment', parse_url( $base_url ) ) ) {
			$base_url = trailingslashit( $args['base_url'] );
		}

		// the format value, either affiliate's ID or username
		$format_value = affwp_get_referral_format_value( $format, $affiliate_id );

		$url_parts = parse_url( $base_url );

		// if fragment identifier exists in base URL, strip it and store in variable so we can append it later
		$fragment = array_key_exists( 'fragment', $url_parts ) ? '#' . $url_parts['fragment'] : '';

		// if query exists in base URL, strip it and store in variable so we can append to the end of the URL
		$query_string = array_key_exists( 'query', $url_parts ) ? '?' . $url_parts['query'] : '';

		$url_scheme      = isset( $url_parts['scheme'] ) ? $url_parts['scheme'] : 'http';
		$url_host        = isset( $url_parts['host'] ) ? $url_parts['host'] : '';
		$url_path        = isset( $url_parts['path'] ) ? $url_parts['path'] : '';
		$constructed_url = $url_scheme . '://' . $url_host . $url_path;
		$base_url        = $constructed_url;

		// set up URLs
		$pretty_urls     = trailingslashit( $base_url ) . trailingslashit( affiliate_wp()->tracking->get_referral_var() ) . trailingslashit( $format_value ) . $query_string . $fragment;
		$non_pretty_urls = esc_url( add_query_arg( affiliate_wp()->tracking->get_referral_var(), $format_value, $base_url . $query_string . $fragment ) );

		if ( $pretty ) {
			$referral_url = $pretty_urls;
		} else {
			$referral_url = $non_pretty_urls;
		}

		return $referral_url;
	}

	/**
	 * Retrieves the base URL that is then displayed in the Page URL input field of the affiliate area.
	 *
	 * @since 1.6
	 *
	 * @return string Base URL.
	 */
	function affwp_get_affiliate_base_url() {

		if ( isset( $_GET['url'] ) && ! empty( $_GET['url'] ) ) {
			$base_url = urldecode( $_GET['url'] );
		} else {
			$base_url = home_url( '/' );
		}

		$default_referral_url = affiliate_wp()->settings->get( 'default_referral_url' );

		if ( $default_referral_url ) {
			$base_url = $default_referral_url;
		}

		/**
		 * Filters the referral base URL.
		 *
		 * @since 1.6
		 *
		 * @param string Base URL.
		 */
		return apply_filters( 'affwp_affiliate_referral_url_base', $base_url );
	}

	/**
	 * Retrieve an affiliate referral url for the current user.
	 *
	 * It can be safely used under AJAX requests.
	 *
	 * @since 2.17.0
	 *
	 * @param string $url The URL to append the referral data. Leave blank to use site default.
	 *
	 * @return string The final URL.
	 */
	function affwp_get_current_user_affiliate_referral_url( string $url = '' ) : string {

		return urldecode(
			affwp_get_affiliate_referral_url(
				array_filter(
					array(
						'base_url'     => $url,
						'affiliate_id' => affwp_get_affiliate_id( get_current_user_id() ),
						'format'       => affwp_get_referral_format(),
					)
				)
			)
		);
	}

	/**
	 * Retrieves the page ID for the Affiliate Area page.
	 *
	 * @since 1.8
	 * @since 2.25.0 Returns 0 as default value instead of false.
	 *
	 * @return int Affiliate Area page ID.
	 */
	function affwp_get_affiliate_area_page_id() : int {

		/**
		 * Filters the Affiliate Area page ID.
		 *
		 * @since 1.8
		 *
		 * @param int $affiliate_page_id Affiliate Area page ID.
		 */
		return apply_filters( 'affwp_affiliate_area_page_id', affiliate_wp()->settings->get( 'affiliates_page', 0 ) );
	}

	/**
	 * Retrieves the page ID for the Affiliate Login page.
	 *
	 * It returns the Affiliate Area page ID in case none is set.
	 *
	 * @since 2.25.0
	 *
	 * @return int Affiliate Login page ID.
	 */
	function affiliatewp_get_affiliate_login_page_id() : int {

		/**
		 * Filters the Affiliate Login page ID.
		 *
		 * @since 2.25.0
		 *
		 * @param int $affiliate_page_id Affiliate Login page ID.
		 */
		$page_id = apply_filters(
			'affiliatewp_affiliate_login_page_id',
			affiliate_wp()->settings->get( 'affiliates_login_page', affwp_get_affiliate_area_page_id() )
		);

		if ( is_numeric( $page_id ) ) {
			return intval( $page_id );
		}

		return 0;
	}

	/**
	 * Retrieves the page ID for the Affiliate Registration page.
	 *
	 * It returns the Affiliate Area page ID in case none is set.
	 *
	 * @since 2.25.0
	 *
	 * @return int Affiliate Registration page ID.
	 */
	function affiliatewp_get_affiliate_registration_page_id() : int {

		/**
		 * Filters the Affiliate Registration page ID.
		 *
		 * @since 2.25.0
		 *
		 * @param int $affiliate_page_id Affiliate Registration page ID.
		 */
		$page_id = apply_filters(
			'affiliatewp_affiliate_registration_page_id',
			affiliate_wp()->settings->get( 'affiliates_registration_page', affwp_get_affiliate_area_page_id() )
		);

		if ( is_numeric( $page_id ) ) {
			return intval( $page_id );
		}

		return 0;
	}

	/**
	 * Retrieves the page ID for the Affiliate Terms Of Use.
	 *
	 * @since 2.9.6
	 *
	 * @return int Affiliate Terms Of Use page ID.
	 */
	function affwp_get_affiliate_terms_of_use_page_id() {
		$affiliate_terms_page_id = affiliate_wp()->settings->get( 'terms_of_use', 0 );

		/**
		 * Filters the Affiliate Terms Of Use page ID.
		 *
		 * @since 2.9.6
		 *
		 * @param int $affiliate_terms_page_id Affiliate Terms Of Use page ID.
		 */
		return apply_filters( 'affwp_affiliate_terms_of_use_page_id', $affiliate_terms_page_id );
	}

	/**
	 * Retrieves the Affiliates Area page URL.
	 *
	 * @since 1.8
	 *
	 * @param string $tab Optional. Tab ID. Default empty.
	 * @return string If `$tab` is specified and valid, the URL for the given tab within the Affiliate Area page.
	 *                Otherwise the Affiliate Area page URL.
	 */
	function affwp_get_affiliate_area_page_url( $tab = '' ) {
		$affiliate_area_page_id = affwp_get_affiliate_area_page_id();

		$affiliate_area_page_url = get_permalink( $affiliate_area_page_id );

		if ( ! empty( $tab ) && array_key_exists( $tab, affwp_get_affiliate_area_tabs() ) ) {
			$affiliate_area_page_url = add_query_arg( array( 'tab' => $tab ), $affiliate_area_page_url );
		}

		/**
		 * Filters the Affiliate Area page URL.
		 *
		 * @since 1.8
		 *
		 * @param string $affiliate_area_page_url Page URL.
		 * @param int    $affiliate_area_page_id  Page ID.
		 * @param string $tab                     Page tab (if specified).
		 */
		return apply_filters( 'affwp_affiliate_area_page_url', $affiliate_area_page_url, $affiliate_area_page_id, $tab );
	}

	/**
	 * Retrieves an array of tabs for the affiliate area
	 *
	 * @since 2.1.7
	 *
	 * @return array $tabs Array of tabs.
	 */
	function affwp_get_affiliate_area_tabs() {

		/**
		 * Filters the Affiliate Area tabs list.
		 *
		 * @since 2.1.7
		 *
		 * @param array $tabs Array of tabs.
		 */
		$tabs = apply_filters(
			'affwp_affiliate_area_tabs',
			array(
				'urls'      => __( 'Affiliate URLs', 'affiliate-wp' ),
				'creatives' => __( 'Creatives', 'affiliate-wp' ),
				'stats'     => __( 'Statistics', 'affiliate-wp' ),
				'graphs'    => __( 'Graphs', 'affiliate-wp' ),
				'referrals' => __( 'Referrals', 'affiliate-wp' ),
				'payouts'   => __( 'Payouts', 'affiliate-wp' ),
				'visits'    => __( 'Visits', 'affiliate-wp' ),
				'coupons'   => __( 'Coupons', 'affiliate-wp' ),
				'settings'  => __( 'Settings', 'affiliate-wp' ),
			)
		);

		$affiliate_coupons = affwp_get_affiliate_coupons( affwp_get_affiliate_id() );

		if ( empty( $affiliate_coupons ) ) {
			unset( $tabs['coupons'] );
		}

		return $tabs;
	}

	/**
	 * Get the default first Affiliate Area tab slug.
	 *
	 * @since 2.17.0
	 * @since 2.27.8 The default is always the first tab configured in the list.
	 *
	 * @return string First tab
	 */
	function affwp_get_first_affiliate_area_tab() {
		return current( array_keys( affwp_get_affiliate_area_tabs() ) );
	}

	/**
	 * Retrieves the active Affiliate Area tab slug.
	 *
	 * @since 1.8.1
	 *
	 * @return string Active tab if valid, empty string otherwise.
	 */
	function affwp_get_active_affiliate_area_tab() {

		// Bail if not in the affiliate area.
		if ( false === affwp_is_affiliate_area() ) {
			return '';
		}

		// If empty, return default first tab.
		if ( empty( filter_input( INPUT_GET, 'tab', FILTER_UNSAFE_RAW ) ) ) {
			return affwp_get_first_affiliate_area_tab();
		}

		$active_tab = sanitize_text_field( $_GET['tab'] );
		$tabs       = affwp_get_affiliate_area_tabs();

		foreach ( $tabs as $tab_slug => $tab_title ) {

			// This ensures that tabs registered prior to 2.1.7 (when tab titles were added to the array) continue to function
			if ( is_int( $tab_slug ) ) {
				$tabs[ sanitize_key( $tab_title ) ] = $tab_title;
			}

			if ( false === affwp_affiliate_area_show_tab( $tab_slug ) ) {
				unset( $tabs[ $tab_slug ] );
			}
		}

		if ( $active_tab && array_key_exists( $active_tab, $tabs ) ) {
			$active_tab = $active_tab;
		} elseif ( ! empty( $tabs ) ) {
			$active_tab = reset( $tabs );
			$active_tab = key( $tabs );
		} else {
			$active_tab = '';
		}

		return $active_tab;
	}

	/**
	 * Determines whether to render a given Affiliate Area area tab.
	 *
	 * @since 1.8
	 *
	 * @param string $tab Optional. Affiliate Area tab slug. Default empty.
	 * @return bool True if the tab should be rendered, otherwise false.
	 */
	function affwp_affiliate_area_show_tab( $tab = '' ) {
		/**
		 * Filters whether to show a given Affiliate Area tab or not.
		 *
		 * @since 1.8
		 *
		 * @param bool   $show Whether to show the given tab.
		 * @param string $tab  The given tab slug.
		 */
		return apply_filters( 'affwp_affiliate_area_show_tab', true, $tab );
	}

	/**
	 * Renders a specified Affiliate Area tab.
	 *
	 * @since 2.1.7
	 *
	 * @param string $tab Optional. Slug for the Affiliate Area tab to render. Default empty.
	 * @return void
	 */
	function affwp_render_affiliate_dashboard_tab( $tab = '' ) {

		ob_start();

		affiliate_wp()->templates->get_template_part( 'dashboard-tab', $tab );

		$content = ob_get_clean();

		/**
		 * Filters the contents of a specific Affiliate Area tab.
		 *
		 * The dynamic portion of the hook name, `$tab`, refers to the current Affiliate Area
		 * tab slug.
		 *
		 * @since 2.1.7
		 *
		 * @param string $content Contents of the tab.
		 * @param string $tab     The tab slug.
		 */
		$content = apply_filters( 'affwp_render_affiliate_dashboard_tab_' . $tab, $content, $tab );

		/**
		 * Filters the contents of the current Affiliate Area tab.
		 *
		 * @since 2.1.7
		 *
		 * @param string $content Contents of the tab.
		 * @param string $tab     The tab slug.
		 */
		echo apply_filters( 'affwp_render_affiliate_dashboard_tab', $content, $tab ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped previously.
	}

	/**
	 * Retrieves an array of payouts for the given affiliate.
	 *
	 * @since 1.9
	 *
	 * @param int|\AffWP\Affiliate $affiliate Optional. Affiliate ID or object. Default is the current affiliate.
	 * @return array|false Array of payout objects for the given affiliate, otherwise false.
	 */
	function affwp_get_affiliate_payouts( $affiliate = 0 ) {
		if ( ! $affiliate = affwp_get_affiliate( $affiliate ) ) {
			return false;
		}

		$payouts = affiliate_wp()->affiliates->payouts->get_payouts(
			array(
				'affiliate_id' => $affiliate->ID,
			)
		);

		/**
		 * Filters the list of payouts associated with an affiliate.
		 *
		 * @since 1.9
		 *
		 * @param array $payouts      The affiliate's payouts.
		 * @param int   $affiliate_id Affiliate ID.
		 */
		return apply_filters( 'affwp_get_affiliate_payouts', $payouts, $affiliate->ID );
	}

	/**
	 * Get the account ID of the affiliate on the Payouts Service.
	 * Also checks if the account ID is valid on the Payouts Service.
	 *
	 * @since 2.4
	 *
	 * @param  int $affiliate_id Affiliate ID.
	 * @return array Payout service account details for the given affiliate.
	 */
	function affwp_get_payouts_service_account( $affiliate_id = 0 ) {

		if ( ! $affiliate = affwp_get_affiliate( $affiliate_id ) ) {
			$account_details = array(
				'status' => 'invalid_account',
				'valid'  => false,
			);

			return $account_details;
		}

		if ( ! $affiliate->user ) {
			$account_details = array(
				'status' => 'user_account_deleted',
				'valid'  => false,
			);

			return $account_details;
		}

		$payout_service_account_meta = affwp_get_affiliate_meta( $affiliate->affiliate_id, 'payouts_service_account', true );

		if ( ! $payout_service_account_meta || empty( $payout_service_account_meta['account_id'] ) ) {
			$account_details = array(
				'status' => 'no_ps_account',
				'valid'  => false,
			);

			return $account_details;
		}

		$headers = affwp_get_payouts_service_http_headers();

		$api_params = array(
			'account_id'    => $payout_service_account_meta['account_id'],
			'affwp_version' => AFFILIATEWP_VERSION,
		);

		$args = array(
			'body'      => $api_params,
			'headers'   => $headers,
			'timeout'   => 60,
			'sslverify' => false,
		);

		$request = wp_remote_get( PAYOUTS_SERVICE_URL . '/wp-json/payouts/v1/account/validate-account', $args );

		if ( is_wp_error( $request ) ) {

			$account_details = array(
				'status' => 'unable_to_retrieve_ps_account',
				'valid'  => false,
			);

		} else {

			$response      = json_decode( wp_remote_retrieve_body( $request ) );
			$response_code = wp_remote_retrieve_response_code( $request );

			if ( 200 === (int) $response_code ) {

				if ( $response->status ) {

					switch ( $payout_service_account_meta['status'] ) {

						case 'payout_method_added':
							$account_details = array(
								'account_id' => $payout_service_account_meta['account_id'],
								'valid'      => true,
							);

							break;

						case 'account_created':
							$account_details = array(
								'status' => 'no_ps_payout_method',
								'valid'  => false,
							);

							break;

						default:
							$account_details = array(
								'status' => 'unable_to_retrieve_ps_account',
								'valid'  => false,
							);

							break;
					}
				} else {

					$account_details = array(
						'status' => $response->reason,
						'valid'  => false,
					);

				}
			} else {

				$account_details = array(
					'status' => 'unable_to_retrieve_ps_account',
					'valid'  => false,
				);

			}
		}

		return $account_details;
	}

	/**
	 * Determines whether the current page is the Affiliate Area or not.
	 *
	 * @since 2.6.2
	 * @since 2.17.2 Updated to check for shortcode, not just the affiliate area
	 *               page. Some pages can contain the shortcode.
	 * @since 2.18.0 Updated in case `get_the_content()` is `null`.
	 *
	 * @return bool True if the current page is the Affiliate Area, false if not.
	 */
	function affwp_is_affiliate_area() {

		if ( get_the_ID() === affwp_get_affiliate_area_page_id() ) {
			return true; // It's the page set in the settings.
		}

		$the_content = get_the_content( null, false, get_post( get_the_ID() ) );

		if ( ! is_string( $the_content ) ) {
			return false; // No content, no shortcode.
		}

		return stristr( $the_content, '[affiliate_area' );
	}

	/**
	 * Retrieves an affiliate by a given field and value.
	 *
	 * @since 2.7
	 *
	 * @param string $field Affiliate object field.
	 * @param mixed  $value Field value.
	 * @return \AffWP\Affiliate|\WP_Error Affiliate object if found, otherwise a WP_Error object.
	 */
	function affwp_get_affiliate_by( $field, $value ) {
		$result = affiliate_wp()->affiliates->get_by( $field, $value );

		if ( is_object( $result ) ) {
			$affiliate = affwp_get_affiliate( intval( $result->affiliate_id ) );
		} else {
			$affiliate = new \WP_Error(
				'invalid_affiliate_field',
				sprintf( 'No affiliate could be retrieved with a(n) \'%1$s\' field value of %2$s.', $field, $value )
			);
		}

		return $affiliate;
	}

	/**
	 * Retrieves the list of fields that are in user meta, but should be in affiliate meta.
	 *
	 * @since 2.8
	 *
	 * @return string[] Array of meta field keys.
	 */
	function affwp_get_pending_migrated_user_meta_fields() {
		/**
		 * Filters the pending migrated user meta fields.
		 *
		 * Any meta that once used user meta instead of affiliate meta can be added to this filter. This will trigger
		 * AffiliateWP to automatically display a notice to migrate this data to affiliate meta.
		 *
		 * @since 2.8
		 *
		 * @param array $fields list of meta keys to migrate.
		 */
		return apply_filters(
			'affwp_pending_migrated_user_meta_fields',
			array(
				'affwp_promotion_method',
				'affwp_disable_affiliate_email',
				'affwp_referral_notifications',
			)
		);
	}

	/**
	 * Retrieves the list of fields that were once in user meta, but have been moved to affiliate meta.
	 *
	 * @since 2.8
	 *
	 * @return string[] Array of meta field keys.
	 */
	function affwp_get_current_migrated_user_meta_fields() {
		return get_option( 'affwp_migrated_meta_fields', array() );
	}

	/**
	 * Get the top earnings affiliates.
	 *
	 * @since 2.9.8
	 *
	 * @param int   $count How many of them.
	 * @param array $args {
	 *     Arguments.
	 *
	 *     @type string $fields Accepts 'all' which will return an array of affiliate
	 *                          objects or 'ids' which will return an array of affiliate ID's.
	 *     @type array  $date $date {
	 *         Date range for earnings.
	 *         Defaults to beginning of time until now.
	 *
	 *         @type string $start Start date.
	 *         @type string $end   End date.
	 *     }
	 *     @type string $status Status of affiliates retrieved, default to 'active'.
	 * }
	 * @return array Affiliates sorted by highest earning.
	 */
	function affwp_get_top_earning_affiliates( $count = 5, $args = array() ) {

		return array_map(
			function ( $affiliate ) use ( $args ) {

				if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
					return $affiliate->affiliate_id;
				}

				return affwp_get_affiliate( $affiliate->affiliate_id );
			},
			affiliate_wp()->referrals->get_referrals(
				array(
					'status'     => array(
						'paid',
						'unpaid',
					),
					'number'     => absint( $count ),
					'fields'     => array(
						'affiliate_id',
					),
					'date'       => $args['date'],
					'orderby'    => 'amount_sum',
					'groupby'    => 'affiliate_id',
					'sum_fields' => array(
						'amount',
					),
				)
			)
		);
	}

	/**
	 * Get the affiliate's name (or username).
	 *
	 * @since 2.9.8
	 *
	 * @param  int $affiliate_id Affiliate's ID.
	 * @return string
	 */
	function affwp_get_affiliate_full_name_or_display_name( $affiliate_id ) {

		$affiliate_name = affwp_get_affiliate_name( $affiliate_id );

		if ( empty( $affiliate_name ) ) {
			return affwp_get_affiliate_username( $affiliate_id );
		}

		return $affiliate_name;
	}

	/**
	 * Is an affiliate in an affiliate group that might override their referral rate settings?
	 *
	 * @since 2.13.0
	 *
	 * @param int    $affiliate_id The Affiliate ID.
	 * @param string $specific     The specific override to test for.
	 *
	 * @return bool
	 */
	function affwp_affiliate_has_affiliate_group_overrides( $affiliate_id = 0, string $specific = '' ) : bool {

		if ( intval( $affiliate_id ) <= 0 ) {
			return false; // Not a valid affiliate, fail gracefully.
		}

		$affiliate_group_id = affwp_get_affiliate_group_id( intval( $affiliate_id ) );

		if ( ! is_numeric( $affiliate_group_id ) || intval( $affiliate_group_id ) <= 0 ) {
			return false; // No affiliate group ID.
		}

		// The meta will tell us if this has potential overrides.
		$group_meta = affiliate_wp()->groups->get_group_meta(
			$affiliate_group_id
		);

		if ( ! is_array( $group_meta ) ) {
			return false; // This shouldn't happen, but fail gracefully.
		}

		if ( ! empty( $specific ) ) {
			return in_array( $specific, array_keys( $group_meta ), true );
		}

		return (

			// If any of these keys are set this group might have overrides.
			in_array( 'rate-type', array_keys( $group_meta ), true ) ||
			in_array( 'rate', array_keys( $group_meta ), true ) ||
			in_array( 'flat-rate-basis', array_keys( $group_meta ), true )
		);
	}

	/**
	 * Get the affiliates assigned affiliate group.
	 *
	 * @since 2.13.0
	 *
	 * @param int $affiliate_id The Affiliate ID.
	 *
	 * @return mixed `false` if there is none, or the group ID of the group they are assigned to.
	 *
	 * @throws \Exception If we find the affiliate is in more than one affiliate group.
	 */
	function affwp_get_affiliate_group_id( int $affiliate_id ) {

		if ( intval( $affiliate_id ) <= 0 ) {
			return false; // No affiliate group.
		}

		$connected_affiliate_groups = array_filter(
			affiliate_wp()->connections->get_connected(
				'group',
				'affiliate',
				intval( $affiliate_id )
			),

			// Validate that the group that's connected is an affiliate-group group type.
			function ( $group_id ) {

				global $wpdb;

				$validate_group_id = $wpdb->get_var(
					$wpdb->prepare(
						str_replace(
							'{table_name}',
							affiliate_wp()->groups->table_name,
							"SELECT `group_id` FROM {table_name} WHERE `type` = 'affiliate-group' AND group_id = %d"
						),
						$group_id
					)
				);

				if ( ! is_numeric( $validate_group_id ) ) {
					return false; // We should have got back an ID-like number.
				}

				// If we get an ID then the group types match.
				return intval( $group_id ) === intval( $validate_group_id );
			}
		);

		if ( count( $connected_affiliate_groups ) > 1 ) {

			// Programatic error, affiliates should not be in multiple groups at a time.
			throw new \Exception( 'Expected one affiliate group, got many. Affiliates can only be in one affiliate group at a time.' );
		}

		// Send back the single group the affiliate is in.
		return absint( current( $connected_affiliate_groups ) );
	}

	/**
	 * Get registration method totals.
	 *
	 * @since 2.18.0
	 *
	 * @return array|WP_Error Array of registration methods with a count of each or WP_Error on failure.
	 */
	function affiliatewp_get_registration_method_totals() {
		global $wpdb;

		$table_name = affiliate_wp()->affiliate_meta->table_name;

		// Prepare the SQL query to get the count of affiliates for each registration method.
		$results = $wpdb->get_results(
			"
				SELECT meta_value AS registration_method, COUNT(*) AS count
				FROM {$table_name}
				WHERE meta_key = 'registration_method'
				GROUP BY meta_value
			",
			ARRAY_A
		);

		// Check for any database errors.
		if ( $wpdb->last_error ) {
			// Create a WP_Error object and return it.
			return new WP_Error( 'database_error', "There was an error executing the query: {$wpdb->last_error}" );
		}

		// Initialize an empty array to hold the final result.
		$registration_method_totals = array();

		// Loop through the results and build the new array.
		foreach ( $results as $result ) {
			$registration_method_totals[ $result['registration_method'] ] = (int) $result['count'];
		}

		// Return the new array.
		return $registration_method_totals;
	}
}

/**
 * Namespaced Functions
 *
 * Use `use function \AffiliateWP\Affiliates\Functions\<function name>` to import these.
 *
 * @since 2.26.0
 */
namespace AffiliateWP\Functions\Affiliates {

	/**
	 * Get the count of pending affiliates in the system.
	 *
	 * @param integer|array $exclude Exclude affiliates, can be integer of an
	 *                               affiliate ID or array of affiliate ID's.
	 *
	 * @since 2.26.0
	 *
	 * @return integer
	 */
	function get_pending_affiliate_count( $exclude = 0 ) : int {

		return affiliate_wp()->affiliates->get_affiliates(
			array_merge(
				[
					'number' => apply_filters( 'affwp_unlimited', -1, __FUNCTION__ ),
					'status' => 'pending',
				],
				( $exclude > 0 || is_array( $exclude ) )

					// Exclude affiliates.
					? [
						'exclude' => is_array( $exclude )
							? array_filter(
								$exclude,
								function ( $value ) {

									return is_numeric( $value )
										? absint( $value )
										: false;
								}
							)
							: absint( $exclude ),
					]
					: [] // Don't exclude anyone.
			),
			true
		);
	}

	/**
	 * Get the next Pending Affiliate (ID).
	 *
	 * This always gets the most recent affilaite who applied and is pending.
	 *
	 * @param integer|array $exclude Exclude affiliates, can be integer of an
	 *                               affiliate ID or array of affiliate ID's.
	 * @since 2.26.0
	 *
	 * @return integer
	 */
	function get_next_pending_affiliate_id( $exclude = 0 ) : int {

		$affiliates = affiliate_wp()->affiliates->get_affiliates(
			array_merge(
				[
					'number'  => 1,
					'status'  => 'pending',
					'orderby' => 'date_registered',
					'order'   => 'desc',
					'fields'  => 'ids',
				],
				( $exclude > 0 || is_array( $exclude ) )

					// Exclude affiliates.
					? [
						'exclude' => is_array( $exclude )
							? array_filter(
								$exclude,
								function ( $value ) {

									return is_numeric( $value )
										? absint( $value )
										: false;
								}
							)
							: absint( $exclude ),
					]
					: [] // Don't exclude anyone.
			)
		);

		if ( empty( $affiliates ) || ! is_array( $affiliates ) ) {
			return 0;
		}

		return current( $affiliates );
	}

	/**
	 * Get the next pending affiliate Review URL.
	 *
	 * @since 2.26.0
	 *
	 * @param integer|array $affiliate_id Affiliates to exclude for the next affiliate.
	 *
	 * @return string The URL to review the affiliates.
	 */
	function get_next_pending_affiliate_review_url( $affiliate_id = 0 ) : string {

		return wp_specialchars_decode(
			wp_nonce_url(
				affwp_admin_url(
					'affiliates',
					array(
						'action'       => 'review_affiliate',
						'affiliate_id' => get_next_pending_affiliate_id( $affiliate_id ),
					)
				),
				'affiliate-nonce'
			)
		);
	}
}
