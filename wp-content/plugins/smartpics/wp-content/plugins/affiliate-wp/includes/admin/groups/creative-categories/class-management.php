<?php
/**
 * Creatives Grouping (Categories) Admin Screen Management
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Creatives
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort -- No need to re-document some methods and properties.

namespace AffiliateWP\Admin\Groups\Creative_Categories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/class-management.php';
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/meta/trait-description.php';

/**
 * Creatives Grouping (Categories) Admin Screen Management
 *
 * @since 2.12.0
 */
final class Management extends \AffiliateWP\Admin\Groups\Management {

	use \AffiliateWP\Admin\Affiliates\Groups\Meta\Description;

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $capability = 'manage_creatives';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $group_type = 'creative-category';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $item = 'creative';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $menu_slug = 'creatives-categories';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $object_id_property = 'creative_id';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $parent = 'affiliate-wp-creatives';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $position = 0;

	/**
	 * Construct
	 *
	 * @param string $connector_id The Connector ID if there is one.
	 *
	 * @since 2.18.0 (Aubrey Portwood) Updated to accept a matching connector ID if there is one.
	 *
	 * @since 2.12.0
	 */
	public function __construct( string $connector_id = '' ) {

		$this->item_plural  = __( 'Creatives', 'affiliate-wp' );
		$this->item_single  = __( 'Creative', 'affiliate-wp' );
		$this->menu_title   = __( 'Categories', 'affiliate-wp' );
		$this->page_title   = __( 'Creative Categories', 'affiliate-wp' );
		$this->plural_title = __( 'Categories', 'affiliate-wp' );
		$this->single_title = __( 'Category', 'affiliate-wp' );

		$this->description_column_width = '550px';

		$this->meta_fields = array(

			// Description field.
			'description' => array(
				'main'          => array( $this, 'description_main' ),
				'edit'          => array( $this, 'description_edit' ),
				'save'          => array( $this, 'description_save' ),
				'column_header' => array( $this, 'description_column_header' ),
				'column_value'  => array( $this, 'description_column_value' ),
			),
		);

		parent::__construct( $connector_id );
	}
}
