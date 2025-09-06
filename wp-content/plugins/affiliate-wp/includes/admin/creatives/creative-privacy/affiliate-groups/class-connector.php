<?php
/**
 * Connecting Creatives to Affiliate Groups (Privacy).
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Creatives\Affiliates
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.13.0
 * @since       2.15.0 File moved to new location.
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort -- No need to re-document some methods and properties.
// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket, PEAR.Functions.FunctionCallSignature.MultipleArguments -- Formatting ok.
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Formatting OK.
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- Alignment not required for this file.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- For code formatting.

namespace AffiliateWP\Admin\Creatives\Creative_Privacy\Affiliate_Groups;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

affwp_admin_require_connector();

/**
 * Connecting Creatives to Affiliate Groups.
 *
 * @since 2.13.0
 */
final class Connector extends \AffiliateWP\Admin\Connector {

	use \AffiliateWP\Utils\Data;

	/** @var string This is documented in includes/admin/class-connector.php */
	protected array $connectable_args = array(
		'creative' => array(
			'column_before' => null,
		),
		'group'    => array(
			'group_type' => 'affiliate-group',
		),
	);

	/** @var string This is documented in includes/admin/class-connector.php */
	protected $capability = 'manage_creatives';

	/** @var string This is documented in includes/admin/class-connector.php */
	protected $selector_type = 'multiple';

	/**
	 * Construct
	 *
	 * @since 2.13.0
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
				array(
					'form'       => 'tr',
					'form_class' => 'form-row creative-privacy_affiliate-groups',
					'row_tag'    => 'div',
					'row_class'  => '',
				)
			),
		) );

		$this->update_connectable_args( 'group', array(
			'lang' => array(
				'plural'      => __( 'Affiliate Groups', 'affiliate-wp' ),
				'single'      => __( 'Affiliate Group', 'affiliate-wp' ),

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

	/**
	 * Set the input description (of the selector) with custom language.
	 *
	 * @since 2.15.0
	 *
	 * @param string $item_connectable The connectable of the item being edited/added.
	 *
	 * @return string
	 */
	protected function get_selector_description( string $item_connectable ) : string {
		return __( 'Select one or more affiliate groups to share this creative with privately.', 'affiliate-wp' );
	}

	/**
	 * Set the column value on the list table to Privacy.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_list_table_column_title( string $connectable ) : string {
		return __( 'Group Privacy', 'affiliate-wp' );
	}

	/**
	 * Set the filter option (text) for All Affiliate Groups.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_all_items_filter_option_text( string $connectable ) : string {
		return __( 'All Affiliate Group Privacy', 'affiliate-wp' );
	}

	/**
	 * Set the filter option (text) for No Affiliate Groups.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_no_items_filter_option_text( string $connectable ) : string {
		return __( 'No Group Privacy (Public)', 'affiliate-wp' );
	}

	/**
	 * Set selector label text to Privacy.
	 *
	 * @since 2.15.0
	 *
	 * @param string $opposing_connectable The opposing connectable (unused).
	 *
	 * @return string
	 */
	protected function get_selector_label_text( string $opposing_connectable ) : string {
		return __( 'Affiliate Group Privacy', 'affiliate-wp' );
	}

	/**
	 * Show tooltips for all the affiliate groups that list all the affiliates.
	 *
	 * @since 2.15.0
	 *
	 * @param string $value The normal value the connector would show.
	 * @param mixed  $item  The item type (unused).
	 *
	 * @return string New column value.
	 */
	public function column_value( $value, $item ) : string {

		$value = parent::column_value( $value, $item );

		$titles = explode( ',', $value );

		$new_titles = array();

		foreach ( $titles as $title ) {
			$new_titles[] = $this->show_affiliates_in_affiliate_group( $title );
		}

		return implode( ',', $new_titles );
	}

	/**
	 * Show affiliates in affiliate group (tittle).
	 *
	 * @since 2.15.0
	 *
	 * @param string $title The title (HTML) of the affiliate group going to the list table column value.
	 *
	 * @return string Modified titles that show a tooltip with all the affiliates in that group.
	 */
	private function show_affiliates_in_affiliate_group( string $title ) : string {

		$affiliate_group_id = affiliate_wp()->groups->get_group_id_by_title(
			wp_strip_all_tags( $title ),
			'affiliate-group'
		);

		if ( false === $affiliate_group_id ) {
			return $title;
		}

		$connected_affiliate_ids = affiliate_wp()->connections->get_connected(
			'affiliate',
			'group',
			$affiliate_group_id
		);


		return affwp_text_tooltip(
			" <strong>{$title}</strong>",
			empty( $connected_affiliate_ids )

				// No affiliates in this affiliate group.
				? __( 'No affiliates in this group.', 'affiliate-wp' )

				// Add affiliates to the column values.
				: implode(
					', ',
					array_map(
						function( $affiliate_id ) {
							return $this->get_affiliate_option_text_format( $affiliate_id );
						},
						$connected_affiliate_ids
					)
				),
			false
		);
	}

	/**
	 * Get format for affiliate name - email.
	 *
	 * @since 2.15.0
	 *
	 * @param int $affiliate_id The Affiliate ID.
	 *
	 * @return string
	 */
	private function get_affiliate_option_text_format( int $affiliate_id ) : string {

		$name = affiliate_wp()->affiliates->get_affiliate_name( $affiliate_id );

		$email = affwp_get_affiliate_email( $affiliate_id );

		return "{$name}&nbsp;&mdash;&nbsp;{$email}";
	}

	/**
	 * Set the None text (for column) to Public.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable Connectable (unused).
	 *
	 * @return string
	 */
	protected function get_column_value_none_text( string $connectable ) : string {
		return __( 'Public', 'affiliate-wp' );
	}

	/**
	 * Set the text when there are no items to select.
	 *
	 * @since 2.15.0
	 *
	 * @param string $item_connectable The connectable of the item being edited/added.
	 * @param string $management_link  Management link to create them.
	 *
	 * @return string
	 */
	protected function get_selector_create_text( string $item_connectable, string $management_link ) : string {

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		return sprintf(

			/* Translators: %1$s is the grouping singular, %2$s is the item singlular. */
			__( '%1$sCreate%2$s %3$s %4$s to share this %5$s with privately.', 'affiliate-wp' ),
			sprintf(
				'<a href="%s">',
				$management_link
			),
			'</a>',
			'multiple' === $this->selector_type
				? __( 'one or more', 'affiliate-wp' )
				: __( 'a', 'affiliate-wp' ),
			'multiple' === $this->selector_type
				? strtolower( $this->get_connectable_lang( 'plural', $opposing_connectable ) )
				: strtolower( $this->get_connectable_lang( 'single', $opposing_connectable ) ),
			strtolower( $this->get_connectable_lang( 'single', $item_connectable ) )
		);
	}

	/**
	 * Don't show the filter on creatives list table.
	 *
	 * @since 2.15.0
	 *
	 * @param string $which Ignored.
	 *
	 * @return void
	 */
	public function display_filter_selector_for_creative( string $which ) : void {}
}
