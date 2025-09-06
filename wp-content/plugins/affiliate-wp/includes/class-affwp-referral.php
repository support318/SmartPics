<?php
/**
 * Objects: Referral
 *
 * @package     AffiliateWP
 * @subpackage  Database
 * @copyright   Copyright (c) 2017, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
 */

namespace AffWP;

#[\AllowDynamicProperties]

/**
 * Implements a referral object.
 *
 * @since 1,9
 *
 * @see AffWP\Base_Object
 * @see affwp_get_referral()
 *
 * @property-read int $ID Alias for `$referral_id`
 */
final class Referral extends Base_Object {

	/**
	 * Referral ID.
	 *
	 * @since 1.9
	 * @access public
	 * @var int
	 */
	public $referral_id = 0;

	/**
	 * Affiliate ID.
	 *
	 * @since 1.9
	 * @access public
	 * @var int
	 */
	public $affiliate_id = 0;

	/**
	 * Visit ID.
	 *
	 * @since 1.9
	 * @access public
	 * @var int
	 */
	public $visit_id = 0;

	/**
	 * REST ID (site:referral ID combination).
	 *
	 * @since 2.2.2
	 * @var   string
	 */
	public $rest_id = '';

	/**
	 * Customer ID.
	 *
	 * @since 2.2
	 * @access public
	 * @var int
	 */
	public $customer_id = 0;

	/**
	 * Parent ID.
	 *
	 * @since 2.2.9
	 * @access public
	 * @var int
	 */
	public $parent_id = 0;

	/**
	 * Referral description.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $description;

	/**
	 * Referral status.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $status;

	/**
	 * Referral amount.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $amount;

	/**
	 * Referral currency.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $currency;

	/**
	 * Custom referral data.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $custom;

	/**
	 * Referral context (usually integration).
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $context;

	/**
	 * Referral campaign name.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $campaign;

	/**
	 * Referral reference.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $reference;

	/**
	 * Products associated with the referral.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $products;

	/**
	 * Date the referral was created.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $date;

	/**
	 * Referral type.
	 *
	 * @since 2.2
	 * @access public
	 * @var string
	 */
	public $type;

	/**
	 * Referral flag.
	 *
	 * @since 2.9.6
	 * @access public
	 * @var string
	 */
	public $flag;

	/**
	 * Token to use for generating cache keys.
	 *
	 * @since 1.9
	 * @access public
	 * @static
	 * @var string
	 *
	 * @see AffWP\Base_Object::get_cache_key()
	 */
	public static $cache_token = 'affwp_referrals';

	/**
	 * Database group.
	 *
	 * Used in \AffWP\Base_Object for accessing the referrals DB class methods.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public static $db_group = 'referrals';

	/**
	 * Object type.
	 *
	 * Used as the cache group and for accessing object DB classes in the parent.
	 *
	 * @since 1.9
	 * @access public
	 * @static
	 * @var string
	 */
	public static $object_type = 'referral';

	/**
	 * Sanitizes a referral object field.
	 *
	 * @since 1.9
	 * @access public
	 * @static
	 *
	 * @param string $field        Object field.
	 * @param mixed  $value        Field value.
	 * @return mixed Sanitized field value.
	 */
	public static function sanitize_field( $field, $value ) {
		if ( in_array( $field, array( 'referral_id', 'affiliate_id', 'visit_id', 'ID', 'parent_id' ) ) ) {
			$value = (int) $value;
		}

		if ( 'custom' === $field ) {
			$value = affwp_maybe_unserialize( affwp_maybe_unserialize( $value ) );
		}

		if ( in_array( $field, array( 'rest_id' ) ) ) {
			$value = sanitize_text_field( $value );
		}

		return $value;
	}

	/**
	 * Retrieves the referral type name.
	 *
	 * @since 2.2
	 * @since 2.6.4 Refactored to wrap affwp_get_referral_type_label()
	 *
	 * @return string Nice name of the referral type.
	 */
	public function type() {
		return affwp_get_referral_type_label( $this->type );
	}

	/**
	 * Retrieves the reference based on the context (integration) of the current referral.
	 *
	 * @since 2.3
	 *
	 * @return int (Maybe) processed reference, otherwise 0.
	 */
	public function reference() {
		$reference = $this->reference;

		$class = affiliate_wp()->integrations->get_integration_class( $this->context );

		if ( class_exists( $class ) ) {
			$reference = ( new $class )->parse_reference( $this->reference );
		}

		return $reference;
	}

	/**
	 * Invalidates the sales count cache for the referral's integration context.
	 *
	 * @since 2.5
	 *
	 * @return bool|\WP_Error True on successful removal, false on failure. WP_Error if integration is invalid.
	 */
	public function invalidate_sales_counts_cache() {
		// Invalidate the cache and force sync check to recount records. See self::$integration->needs_synced()
		return wp_cache_delete( affwp_get_sales_referrals_counts_cache_key( $this->context ), 'affwp_integrations' );
	}

	/**
	 * Retrieves the order date based on the context and reference of the referral.
	 *
	 * This method checks the context of the referral to determine whether it is associated
	 * with WooCommerce or Easy Digital Downloads (EDD). It then fetches the order date
	 * accordingly.
	 *
	 * @since 2.27.0
	 *
	 * @return string|false The order date in 'Y-m-d' format if available, otherwise false.
	 */
	public function order_date() {

		// Bail if no reference.
		if ( 0 === $this->reference ) {
			return false;
		}

		if ( 'woocommerce' === $this->context && function_exists( 'wc_get_order' ) ) {

			$order = wc_get_order( $this->reference );

			return $order ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : false;
		} elseif ( 'edd' === $this->context && function_exists( 'edd_get_payment' ) ) {

			$payment = edd_get_payment( $this->reference );

			return $payment ? gmdate( 'Y-m-d H:i:s', strtotime( $payment->date ) ) : false;
		}

		return false;
	}
}
