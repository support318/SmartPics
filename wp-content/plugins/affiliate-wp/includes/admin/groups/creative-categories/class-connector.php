<?php
/**
 * Connecting Creatives to Groups.
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Creatives\Categories
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort -- No need to re-document some methods and properties.
// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket, PEAR.Functions.FunctionCallSignature.MultipleArguments -- Formatting ok.
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Formatting OK.
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- Alignment not required for this file.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition

namespace AffiliateWP\Admin\Groups\Creative_Categories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

affwp_admin_require_connector();

/**
 * Connecting Creatives to Creative Categories.
 *
 * @since 2.12.0
 * @since 2.15.0 Re-factored to work with new item-to-item Connector API.
 */
final class Connector extends \AffiliateWP\Admin\Connector {

	/** @var string This is documented in includes/admin/class-connector.php */
	protected array $connectable_args = array(
		'creative' => array(
			'column_before' => 'status',
		),
		'group'    => array(
			'group_type' => 'creative-category',
		),
	);

	/** @var string This is documented in includes/admin/class-connector.php */
	protected $capability = 'manage_creatives';

	/**
	 * Construct
	 *
	 * @since 2.12.0
	 * @since 2.15.0 Re-factored to work with new item-to-item Connector API.
	 *
	 * @param string $id The ID of the connector.
	 */
	public function __construct( string $id ) {

		$this->update_connectable_args( 'creative', array(
			'lang' => array(
				'plural'      => __( 'Creatives', 'affiliate-wp' ),
				'single'      => __( 'Creative', 'affiliate-wp' ),

				// Translators: %s is the plural translations above.
				'placeholder' => __( 'Type to search all %s.', 'affiliate-wp' ),

				// Translators: %s is the plural or single translations above.
				'none'        => __( 'No %s', 'affiliate-wp' ),

				// Translators: %s is the plural translations above.
				'all'         => __( 'All %s', 'affiliate-wp' ),
			),
			'form_tags' => $this->get_form_tags(

				// See includes/admin/creatives/creative-categories.php for where the input gets re-positioned.
				array(
					'form'       => 'tr',
					'form_class' => 'form-row',
					'row_tag'    => 'div',
					'row_class'  => '',
				)
			),
		) );

		$this->update_connectable_args( 'group', array(
			'lang' => array(
				'plural'      => __( 'Categories', 'affiliate-wp' ),
				'single'      => __( 'Category', 'affiliate-wp' ),

				// Translators: %s is the plural translations above.
				'placeholder' => __( 'Type to search all %s.', 'affiliate-wp' ),

				// Translators: %s is the plural or single translations above.
				'none'        => __( 'No %s', 'affiliate-wp' ),

				// Translators: %s is the plural translations above.
				'all'         => __( 'All %s', 'affiliate-wp' ),
			),
		) );

		parent::__construct( $id );
	}
}
