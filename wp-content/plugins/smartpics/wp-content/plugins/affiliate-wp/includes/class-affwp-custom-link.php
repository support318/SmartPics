<?php
/**
 * Objects: Custom Link
 *
 * @package     AffiliateWP
 * @subpackage  Database
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.14
 */

namespace AffWP;

#[\AllowDynamicProperties]

/**
 * Implements a custom link object.
 *
 * @since 2.14
 *
 * @see AffWP\Base_Object
 * @see affwp_get_custom_link()
 *
 * @property-read int $ID Alias for `$custom_link_id`
 */
final class CustomLink extends Base_Object {

	/**
	 * Custom Link ID.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var int
	 */
	public int $custom_link_id = 0;

	/**
	 * Affiliate ID.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var int
	 */
	public int $affiliate_id;

	/**
	 * Custom link url.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var string
	 */
	public string $link;

	/**
	 * Custom link campaign.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var string
	 */
	public string $campaign;

	/**
	 * Creation date for the custom link.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var string
	 */
	public string $date_created;

	/**
	 * Token to use for generating cache keys.
	 *
	 * @since 2.14.0
	 * @access public
	 * @static
	 * @var string
	 *
	 * @see AffWP\Base_Object::get_cache_key()
	 */
	public static string $cache_token = 'affwp_custom_links';

	/**
	 * Database group.
	 *
	 * Used in \AffWP\Base_Object for accessing the custom links DB class methods.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var string
	 */
	public static string $db_group = 'custom_links';

	/**
	 * Object type.
	 *
	 * Used as the cache group and for accessing object DB classes in the parent.
	 *
	 * @since 2.14.0
	 * @access public
	 * @static
	 * @var string
	 */
	public static string $object_type = 'custom_link';

	/**
	 * Sanitizes a custom link object field.
	 *
	 * @since 2.14.0
	 * @access public
	 * @static
	 *
	 * @param string $field Object field.
	 * @param mixed  $value Field value.
	 * @return mixed Sanitized field value.
	 */
	public static function sanitize_field( $field, $value ) {
		return in_array( $field, array( 'custom_link_id', 'ID', 'affiliate_id' ), true )
			? (int) $value
			: $value;
	}

	/**
	 * Return the formatted date created.
	 *
	 * @since 2.14.0
	 * @access public
	 *
	 * @return string The formatted date.
	 */
	public function get_formatted_date_created() : string {
		return affwp_date_i18n( strtotime( $this->date_created ) );
	}

	/**
	 * Build and return the custom link.
	 *
	 * @since 2.14.0
	 * @access public
	 *
	 * @return string The custom link.
	 */
	public function get_custom_link() : string {

		return urldecode(
			affwp_get_affiliate_referral_url(
				array_filter(
					array(
						'base_url'     => empty( $this->campaign )
							? $this->link
							: sprintf(
								'%1$s%2$s%3$s',
								$this->link,
								str_contains( $this->link, '?' ) ? '&' : '?',
								rawurlencode(
									http_build_query(
										array(
											'campaign' => $this->campaign,
										)
									)
								)
							),
						'affiliate_id' => $this->affiliate_id,
						'format'       => affwp_get_referral_format()
					)
				)
			)
		);
	}

}
