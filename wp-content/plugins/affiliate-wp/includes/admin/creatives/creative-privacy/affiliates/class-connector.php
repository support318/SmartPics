<?php
/**
 * Connecting Creatives to Affiliates (Privacy).
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Creatives\Affiliates
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.15.0
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort -- No need to re-document some methods and properties.
// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket, PEAR.Functions.FunctionCallSignature.MultipleArguments -- Formatting ok.
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Formatting OK.
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- Alignment not required for this file.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- For code formatting.

namespace AffiliateWP\Admin\Creatives\Creative_Privacy\Affiliates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

affwp_admin_require_connector();

/**
 * Connecting Creatives to Affiliates.
 *
 * @since 2.15.0
 */
final class Connector extends \AffiliateWP\Admin\Connector {

	use \AffiliateWP\Utils\Data;

	/** @var string This is documented in includes/admin/class-connector.php */
	protected array $connectable_args = array(
		'creative' => array(
			'column_before' => null,
		),
		'affiliate' => array(
			'column_before' => null,
		),
	);

	/** @var string This is documented in includes/admin/class-connector.php */
	protected $capability = 'manage_creatives';

	/** @var string This is documented in includes/admin/class-connector.php */
	protected $selector_type = 'multiple';

	/**
	 * Construct
	 *
	 * @since 2.15.0
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
					'form_class' => 'form-row creative-privacy_affiliates',
					'row_tag'    => 'div',
					'row_class'  => '',
				)
			),
		) );

		$this->update_connectable_args( 'affiliate', array(
			'lang' => array(
				'plural'      => __( 'Affiliates', 'affiliate-wp' ),
				'single'      => __( 'Affiliate', 'affiliate-wp' ),

				// Translators: %s is the plural translations above.
				'placeholder' => __( 'Type to search all %s.', 'affiliate-wp' ),

				// Translators: %s is the plural or single translations above.
				'none'        => __( 'No %s', 'affiliate-wp' ),

				// Translators: %s is the plural translations above.
				'all'         => __( 'All %s', 'affiliate-wp' ),
			),
			'form_tags' => null,
		) );

		parent::__construct( $id );
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
		return __( 'Affiliate Privacy', 'affiliate-wp' );
	}

	/**
	 * Set the filter option (text) for All Affiliates.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_all_items_filter_option_text( string $connectable ) : string {
		return __( 'All Affiliate Privacy', 'affiliate-wp' );
	}

	/**
	 * Set the filter option (text) for No Affiliates.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_no_items_filter_option_text( string $connectable ) : string {
		return __( 'No Affiliate Privacy (Public)', 'affiliate-wp' );
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
		return __( 'Select one or more affiliates to share this creative with privately.', 'affiliate-wp' );
	}

	/**
	 * Don't show label text for Affiliate Group privacy.
	 *
	 * @since 2.15.0
	 *
	 * @param string $opposing_connectable The opposing connectable (unused).
	 *
	 * @return string
	 */
	protected function get_selector_label_text( string $opposing_connectable ) : string {
		return __( 'Affiliate Privacy', 'affiliate-wp' );
	}

	/**
	 * Instead of showing Name — email, show email in a tooltip.
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
			$new_titles[] = $this->show_affiliate_email_tooltip( $title );
		}

		return implode( ',', $new_titles );
	}

	/**
	 * Show the affiliates email in a tooltip instead.
	 *
	 * @since 2.15.0
	 *
	 * @param string $title The title (HTML) we would show or the affiliate.
	 *
	 * @return string
	 */
	private function show_affiliate_email_tooltip( string $title ) : string {

		$stripped = str_replace( '&nbsp;', '', wp_strip_all_tags( $title ) );

		$pieces = explode( '&mdash;', $stripped );

		$email = $pieces[1] ?? false;

		if ( ! $email ) {
			return $title;
		}

		return affwp_text_tooltip(
			str_replace(
				array(
					"&nbsp;{$email}",
					'&nbsp;&mdash;',
				),
				array(
					'',
					'',
				),
				$title
			),
			$email,
			false
		);
	}

	/**
	 * Display a connection selector only on creatives.
	 *
	 * @since 2.15.0
	 *
	 * @param mixed $item Possible item being edited (empty when new/adding).
	 *
	 * @return void Early bail (avoid selector) when editing/adding new affiliate screen.
	 */
	public function display_selector( $item ) {

		if ( $this->is_current_add_edit_page( 'affiliate' ) ) {
			return;
		}

		parent::display_selector( $item );
	}

	/**
	 * Don't show filter selector on affiliate list table for connecting creatives.
	 *
	 * @since 2.15.0
	 *
	 * @param string $which Top or bottom (unused).
	 *
	 * @return void Does nothing.
	 */
	public function display_filter_selector_for_affiliate( string $which ) : void {
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

			/* Translators: %1$s is the HTML opening tag, %2$s is the closing tag %3$s is the middle language, %4$s is the plural or single variant, and %5$s is the single variant. */
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
