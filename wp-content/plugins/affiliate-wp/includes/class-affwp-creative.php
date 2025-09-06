<?php
/**
 * Objects: Creative
 *
 * @package     AffiliateWP
 * @subpackage  Database
 * @copyright   Copyright (c) 2017, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
 */

namespace AffWP;

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, Squiz.Strings.DoubleQuoteUsage.NotRequired -- We escape properly.

#[\AllowDynamicProperties]

/**
 * Implements a creative object.
 *
 * @since 1,9
 *
 * @see AffWP\Base_Object
 * @see affwp_get_creative()
 *
 * @property-read int $ID Alias for `$creative_id`
 */
final class Creative extends Base_Object {

	/**
	 * Creative ID.
	 *
	 * @since 1.9
	 * @access public
	 * @var int
	 */
	public $creative_id = 0;

	/**
	 * Name of the creative.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $name;

	/**
	 * Type of the creative.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var string
	 */
	public string $type;

	/**
	 * Description for the creative.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $description;

	/**
	 * URL for the creative.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $url;

	/**
	 * Text for the creative.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $text;

	/**
	 * Image URL for the creative.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $image;

	/**
	 * Creative attachment ID.
	 *
	 * @since 2.14.0
	 * @access public
	 * @var int
	 */
	public int $attachment_id = 0;

	/**
	 * Status for the creative.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $status;

	/**
	 * Creation date for the creative.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $date;

	/**
	 * Last update date for the creative.
	 *
	 * @since 2.16.0
	 * @access public
	 * @var string
	 */
	public string $date_updated;

	/**
	 * Notes for the creative.
	 *
	 * @since 2.16.0
	 * @access public
	 * @var string
	 */
	public string $notes;

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
	public static $cache_token = 'affwp_creatives';

	/**
	 * Database group.
	 *
	 * Used in \AffWP\Base_Object for accessing the creatives DB class methods.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public static $db_group = 'creatives';

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
	public static $object_type = 'creative';

	/**
	 * Sanitizes a creative object field.
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
		if ( in_array( $field, array( 'creative_id', 'ID' ) ) ) {
			$value = (int) $value;
		}
		return $value;
	}

	/**
	 * Returns if the creative was created/updated before the public name update occurred.
	 *
	 * @since 2.16.0
	 *
	 * @param string $date_field Accepts: date and date_updated. Default: date.
	 *
	 * @return bool If creative is prior name migration.
	 *
	 * @throws InvalidArgumentException Wrong date field name.
	 */
	public function is_before_migration_time( string $date_field = 'date' ) : bool {

		$accepted_fields = array( 'date', 'date_updated' );

		if ( ! in_array( $date_field, $accepted_fields, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'Invalid argument $date_field, expected %s, got %s',
					implode( ' or ', $accepted_fields ),
					$date_field
				)
			);
		}

		return strtotime( $this->$date_field ) <= strtotime( affwp_get_creative_name_upgrade_date() );
	}

	/**
	 * Return the creative name.
	 *
	 * @since 2.16.0
	 *
	 * @return string The Creative name or the word `Creative` if the name migration still pending.
	 */
	public function get_name() : string {

		if ( $this->is_name_pending_review() ) {
			return __( 'Creative', 'affiliate-wp' );
		}

		return $this->name;
	}

	/**
	 * Check if the creative name is in a pending review state.
	 *
	 * Pending state occurs when users were notified with the name upgrade notice but haven't taken a decision yet.
	 * Creatives created or updated after the upgrade date can be displayed because users are aware of the upgrade.
	 *
	 * @since 2.16.0
	 *
	 * @return bool Whether the name can be used on the front-end or not.
	 */
	public function is_name_pending_review() : bool {

		$privacy_status = get_option( 'affwp_creative_name_privacy', 'public' );

		// Check if is pending and if it's not an admin page and if it's not updated before the migration time.
		return (
			'pending' === $privacy_status &&
			! affwp_is_admin_page() &&
			$this->is_before_migration_time( 'date_updated' )
		);
	}

	/**
	 * Return the creative type from DB or checking for specific conditions.
	 *
	 * @since 2.14.0
	 *
	 * @return string
	 */
	public function get_type() : string {

		if ( ! empty( $this->type ) ) {
			return $this->type;
		}

		// Fallback for users that have not run the upgrade routine yet.
		return '' === $this->image ? 'text_link' : 'image';
	}

	/**
	 * Return the creative type label.
	 *
	 * @since 2.16.0
	 *
	 * @return string The human-readable label.
	 */
	public function get_type_label() : string {

		$types = affwp_get_creative_types();

		return array_key_exists( $this->type, $types ) ? $types[ $this->type ] : '';
	}

	/**
	 * Returns the formatted date_updated string for the current creative.
	 *
	 * @param string $format If supplied, will override the default system format.
	 * @return string The formatted date_updated string.
	 */
	public function get_date_updated( string $format = '' ) : string {

		return affiliate_wp()->utils
			->date( $this->date_updated )
			->format(
				empty( $format )
					? affiliate_wp()->utils->date_format
					: $format
			);
	}

	/**
	 * Return a creative preview.
	 *
	 * @since 2.16.0
	 * @param string $thumbnail_size The size of the preview image.
	 * @param string $link_type Wrap the creative in a link. Options: none, referral_url, or creative_url.
	 * @param array  $link_attrs Allow to override the link html attributes.
	 * @return string The preview string.
	 */
	public function get_preview(
		string $thumbnail_size = 'medium',
		string $link_type = 'none',
		array $link_attrs = array()
	) : string {

		switch ( $this->get_type() ) {
			case 'image':
				$preview_html = $this->get_image( 'tag', $thumbnail_size );
				break;
			case 'qr_code':

				$preview_html = sprintf(
					'<span class="affwp-qrcode-preview" data-url="%s" data-settings="%s"></span>',
					esc_url_raw(
						affwp_is_admin_page()
							? $this->get_url()
							: affwp_get_current_user_affiliate_referral_url( $this->get_url() )
					),
					esc_attr( wp_json_encode( $this->get_qrcode_settings() ) )
				);

				break;
			default:
				$preview_html = $this->text;
				break;
		}

		if ( ! in_array( $link_type, array( 'referral_url', 'creative_url' ), true ) ) {
			return $preview_html;
		}

		$link_attrs = array_filter(
			wp_parse_args(
				$link_attrs,
				array(
					'target' => '',
					'class'  => 'creative-preview-link',
				)
			)
		);

		return sprintf(
			'<a href="%s"%s>%s</a>',
			'referral_url' === $link_type
				? affwp_get_affiliate_referral_url(
					array(
						'base_url'     => $this->get_url(),
						'affiliate_id' => affwp_get_affiliate_id( get_current_user_id() ),
					)
				)
				: $this->get_url(),
			implode(
				'',
				array_map(
					function ( $key, $value ) {
						return " {$key}=\"{$value}\"";
					},
					array_keys( $link_attrs ),
					array_values( $link_attrs )
				)
			),
			$preview_html
		);
	}

	/**
	 * Retrieve the QR Code settings.
	 *
	 * Currently, support only custom colors.
	 * The array keys will be returned to the correct format to be used with the JS script.
	 *
	 * @since 2.17.0
	 *
	 * @return array[] An array of hexadecimal colors.
	 */
	public function get_qrcode_settings() : array {

		return array(
			'color' => array(
				'dark'  => $this->get_qrcode_color( 'code' ),
				'light' => $this->get_qrcode_color( 'bg' ),
			),
		);
	}

	/**
	 * Return the QR Code color.
	 *
	 * @since 2.17.0
	 *
	 * @param string $type The color type to return: 'code' or 'bg'.
	 * @throws http\Exception\InvalidArgumentException Wrong type error.
	 *
	 * @return string The QR Code color.
	 */
	public function get_qrcode_color( string $type ) : string {

		if ( ! in_array( $type, array( 'code', 'bg' ), true ) ) {
			throw new http\Exception\InvalidArgumentException( "Type must be either code or bg, got {$type}" );
		}

		global $wpdb;

		$color = $wpdb->get_var(
			$wpdb->prepare(
				str_replace(
					'{table_name}',
					affiliate_wp()->creative_meta->table_name,
					"SELECT `meta_value` FROM `{table_name}` WHERE `creative_id` = %d and `meta_key` = %s"
				),
				$this->creative_id,
				sprintf(
					'qrcode_%s_color',
					$type
				)
			)
		);

		if ( ! is_string( $color ) ) {
			return '';
		}

		return stristr( $color, '#' ) ? $color : '';
	}

	/**
	 * Return the creative image for a given size.
	 *
	 * @since 2.16.0
	 *
	 * @param string $return_format Can be url or tag.
	 * @param string $size Any valid WordPress image size.
	 *
	 * @return string The url for the give image size.
	 */
	public function get_image( string $return_format = 'url', string $size = 'full' ) : string {

		$return_format = in_array( $return_format, array( 'url', 'tag' ), true )
			? $return_format
			: 'url';

		// Self-hosted image.
		if ( ! empty( $this->attachment_id ) ) {

			$image = wp_get_attachment_image_src( attachment_url_to_postid( $this->image ), $size );

			if ( ! is_array( $image ) ) {
				return ''; // Image can not be found.
			}

			return 'url' === $return_format
				? reset( $image )
				: sprintf(
					'<img src="%1$s" alt="%2$s" title="%2$s" width="%3$d" height="%4$d">',
					$image[0],
					$this->text,
					$image[1],
					$image[2]
				);
		}

		// Hosted externally.
		if ( filter_var( $this->image, FILTER_VALIDATE_URL ) ) {

			return 'url' === $return_format
				? $this->image
				: sprintf(
					'<img src="%1$s" alt="%2$s" title="%2$s">',
					$this->image,
					$this->text
				);
		}

		return '';
	}

	/**
	 * Return the extension, filesize and dimensions from the creative media.
	 *
	 * @since 2.16.0
	 * @return array Array with the media metadata.
	 */
	public function get_media_metadata() : array {

		$metadata = array();

		// Extension.
		$mimes = ! empty( $this->attachment_id )
			? wp_check_filetype( get_attached_file( $this->attachment_id ) )
			: wp_check_filetype( $this->image );

		$metadata['ext'] = $mimes ? strtoupper( $mimes['ext'] ) : __( 'Image', 'affiliate-wp' );

		// Check for the filesize.
		if ( ! empty( $this->attachment_id ) ) {
			$metadata['filesize'] = size_format( filesize( get_attached_file( $this->attachment_id ) ), 2 );
		} else {
			// Externally hosted image (no attachment ID).
			$headers = ! empty( $this->image ) ? get_headers( $this->image, true ) : array();

			$metadata['filesize'] = isset( $headers['Content-Length'] )
				? size_format( $headers['Content-Length'] )
				: '';
		}

		// Image Size.
		$metadata['size'] = empty( $this->attachment_id )
			? affwp_get_image_size( $this->get_image( 'url', 'full' ) )
			: affwp_get_image_size( $this->attachment_id );

		return $metadata;
	}

	/**
	 * Return the Creative URL, site URL if the creative has no URL.
	 *
	 * @since 2.16.0
	 * @return string The creative URL.
	 */
	public function get_url() : string {

		if ( empty( $this->url ) ) {
			return get_site_url();
		}

		return $this->url;
	}
}
