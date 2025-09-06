<?php
/**
 * Creatives
 *
 * This class handles the asset management of affiliate banners/HTML/links etc
 *
 * @package     AffiliateWP
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-creatives-groups.php';

#[\AllowDynamicProperties]

class Affiliate_WP_Creatives {

	/**
	 * Grouping API.
	 *
	 * @since 2.13.1
	 *
	 * @var \AffiliateWP\Creatives\Dashboard\Groups
	 */
	public $groups;

	/**
	 * Construct
	 *
	 * @since 2.13.0
	 */
	public function __construct() {
		$this->groups = new \AffiliateWP\Creatives\Dashboard\Groups();
	}

	/**
	 * The [affiliate_creative] shortcode
	 *
	 * @since  1.2
	 * @return string
	 */
	public function affiliate_creative( $args = array() ) {

		// Creative's ID
		$id = isset( $args['id'] ) ? (int) $args['id'] : 0;

		if ( ! $creative = affwp_get_creative( $id ) ) {
			return '';
		}

		// creative's link/URL
		if ( ! empty( $args['link'] ) ) {
			// set link to shortcode parameter
			$link = $args['link'];
		} elseif ( $creative->url ) {
			// set link to creative's link from creatives section
			$link = $creative->url;
		} else {
			// set link to the site URL
			$link = get_site_url();
		}

		// creative's image link
		$image_link = ! empty( $args['image_link'] ) ? $args['image_link'] : $creative->image;

		// creative's text (shown in alt/title tags)
		if ( ! empty( $args['text'] ) ) {
			// set text to shortcode parameter if used
			$text = $args['text'];
		} elseif ( $creative->text ) {
			// set text to creative's text from the creatives section
			$text = $creative->text;
		} else {
			// set text to name of blog
			$text = get_bloginfo( 'name' );
		}

		// creative's description
		$description = ! empty( $args['description'] ) ? $args['description'] : $creative->description;

		// creative's preview parameter
		$preview = ! empty( $args['preview'] ) ? $args['preview'] : 'yes';

		// get the image attributes from image_id
		$attributes = ! empty( $args['image_id'] ) ? wp_get_attachment_image_src( $args['image_id'], 'full' ) : '';

		// load the HTML required for the creative
		return $this->html( $id, $link, $image_link, $attributes, $preview, $text, $description );

	}

	/**
	 * The [affiliate_creatives] shortcode
	 *
	 * @since 1.2
	 * @since 2.16.0 Refactored to use get_affiliate_creatives method.
	 * @param array $args Shortcode args.
	 * @return string
	 */
	public function affiliate_creatives( array $args = array() ) : string {

		$args = wp_parse_args(
			$args,
			array(
				'preview' => 'yes',
				'status' => 'active',
			)
		);

		$creatives = $this->get_affiliate_creatives( $args );

		if ( empty( $creatives ) ) {
			return ''; // Nothing to return.
		}

		ob_start();

		foreach ( $creatives as $creative ) {

			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Will be escaped later
			echo $this->html(
				$creative->creative_id,
				$creative->url,
				$creative->image,
				'',
				$args['preview'],
				$creative->text,
				empty( $creative->description ) ? '' : $creative->description
			);
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		}

		return ob_get_clean();
	}

	/**
	 * Used as same as get_creatives(), but additionally filter results by creative groups
	 * and privacy configuration.
	 *
	 * @since 2.16.0
	 * @param array $args Creative args.
	 * @return array Array of creatives.
	 */
	public function get_affiliate_creatives( array $args = array() ) : array {

		/**
		 * Filter the creatives shows on the screen.
		 *
		 * @param array $creatives The creatives.
		 * @since 2.16.0
		 */
		$creatives = apply_filters(
			'affwp_creatives',
			affiliate_wp()->creatives->get_creatives(
				wp_parse_args(
					$args,
					array(
						'status'     => 'active',
						'hide_empty' => true,
					)
				)
			)
		);

		if ( empty( $creatives ) ) {
			return array(); // No creatives to return.
		}

		return array_filter(
			$creatives,
			function ( $creative ) {

				// The get_creatives() can also return only IDs instead of objects.
				$creative_id = is_int( $creative )
					? $creative
					: $creative->creative_id;

				if (
					! isset( $creative_id ) ||
					! affiliate_wp()->creative->groups->affiliate_can_access( $creative_id )
				) {
					return false;
				}

				return true;
			}
		);
	}

	/**
	 * Returns the referral link to append to the end of a URL
	 *
	 * @since  1.2
	 * @return string Affiliate's referral link
	 */
	public function ref_link( $url = '' ) {
		return affwp_get_affiliate_referral_url( array( 'base_url' => $url ) );
	}

	/**
	 * Renders the HTML output for a given Creative.
	 *
	 * @since  1.2
	 *
	 * @param string $id               Creative ID.
	 * @param string $url              Creative URL.
	 * @param string $image_link       The image URL. Either the URL from the image column in DB
	 *                                 or external URL of image.
	 * @param array  $image_attributes Image attributes.
	 * @param string $preview          Creative's preview parameter. Usually 'yes' or 'no'.
	 * @param string $text             Text description for the Creative.
	 * @return string HTML output for the Creative.
	 */
	public function html( $id, $url, $image_link, $image_attributes, $preview, $text, $desc = '' ) {

		global $affwp_creative_atts;

		$id_class = $id ? ' creative-' . $id : '';

		$affwp_creative_atts = array(
			'id'               => $id,
			'url'              => $url,
			'id_class'         => $id_class,
			'desc'             => $desc,
			'preview'          => $preview,
			'image_attributes' => $image_attributes,
			'image_link'       => $image_link,
			'text'             => $text
		);

		ob_start();

		affiliate_wp()->templates->get_template_part( 'creative' );

		$html = ob_get_clean();

		/**
		 * Filters the HTML output for the current Creative.
		 *
		 * @since 1.2
		 *
		 * @param string $html             HTML output for the Creative.
		 * @param string $url              Creative URL.
		 * @param string $image_link       The image URL. Either the URL from the image column in DB
		 *                                 or external URL of image.
		 * @param array  $image_attributes Image attributes.
		 * @param string $preview          Creative's preview parameter. Usually 'yes' or 'no'.
		 * @param string $text             Text description for the Creative.
		 */
		return apply_filters( 'affwp_affiliate_creative_html', $html, $url, $image_link, $image_attributes, $preview, $text );
	}

}
