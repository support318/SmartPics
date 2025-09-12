<?php
/**
 * Connecting Affiliates to Groups.
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Affiliates\Groups
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

namespace AffiliateWP\Admin\Groups\Affiliate_Groups\Affiliates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

affwp_admin_require_connector();

/**
 * Connecting Affiliates to Groups.
 *
 * @since 2.12.0
 * @since 2.15.0 Refactored to work with updated Connector class that allows
 *                  connecting item-to-item not just item-to-group.
 */
final class Connector extends \AffiliateWP\Admin\Connector {

	/** @var string This is documented in includes/admin/class-connector.php */
	protected array $connectable_args = array(
		'affiliate' => array(
			'column_before' => 'username',
		),
		'group'    => array(
			'group_type' => 'affiliate-group',
		),
	);

	/** @var string This is documented in includes/admin/class-connector.php */
	protected $capability = 'manage_affiliates';

	/** @var string This is documented in includes/admin/class-connector.php */
	protected $selector_type = 'single';

	/**
	 * Construct
	 *
	 * @since 2.12.0
	 *
	 * @param string $id The ID of the connector.
	 */
	public function __construct( string $id ) {

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

			// New/Edit screen tags/classes/etc.
			'form_tags' => $this->get_form_tags(
				array(
					'form'       => 'tr',
					'form_class' => 'form-row',
					'row_tag'    => '',
					'row_class'  => '',
				)
			),
		) );

		$this->update_connectable_args( 'group', array(
			'lang' => array(
				'plural'      => __( 'Groups', 'affiliate-wp' ),
				'single'      => __( 'Group', 'affiliate-wp' ),

				// Translators: %s is the plural translations above.
				'placeholder' => __( 'Type to search all %s.', 'affiliate-wp' ),

				// Translators: %s is the plural or single translations above.
				'none'        => __( 'No %s', 'affiliate-wp' ),

				// Translators: %s is the plural translations above.
				'all'         => __( 'All %s', 'affiliate-wp' ),
			),
		) );

		$this->modify_multiselect_position_to_after_status();

		parent::__construct( $id );
	}

	/**
	 * Add tooltip to filterable item links in the list table.
	 *
	 * This shows a tooltip with the custom rate of the affiliate group
	 * and whether or not it's the default group.
	 *
	 * @since 2.15.0
	 *
	 * @param string $title        The title we would normally show.
	 * @param object $item         The object of the item it's connected to.
	 * @param int    $connected_id The ID of the item that's connected.
	 *
	 * @return string
	 */
	protected function get_column_value_filterable_title( string $title, $item, int $connected_id ) : string {

		$title = parent::get_column_value_filterable_title(
			$title,
			$item,
			$connected_id
		);

		if ( ! is_a( $item, '\AffWP\Affiliate' ) ) {
			return $title;
		}

		if ( ! affiliate_wp()->groups->group_exists( $connected_id ) ) {
			return $title;
		}

		$group = affiliate_wp()->groups->get_group( $connected_id );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {
			return $title;
		}

		if ( $this->get_connectable_group_type( 'group' ) !== $group->get_type() ) {
			return $title;
		}

		$rate = $this->get_affiliate_group_rate_tooltip_text( $group );

		return affwp_text_tooltip(
			$title,
			sprintf(
				'%1$s %2$s',
				empty( $rate )
					? __( 'No custom rate', 'affiliate-wp' )

					// Translators: %s is the value for the custom rate, e.g. "Custom Rate: $19.96 Per Order".
					: sprintf( __( 'Custom Rate: %s', 'affiliate-wp' ), $rate ),
				( true === $group->get_meta( 'default-group', false ) )
					? sprintf( '<br> %s', __( 'Default Group', 'affiliate-wp' ) )
					: ''
			),
			false
		);
	}

	/**
	 * Get the rate for the tooltip.
	 *
	 * @since 2.15.0
	 *
	 * @param \AffiliateWP\Groups\Group $group The group object.
	 *
	 * @return string
	 */
	private function get_affiliate_group_rate_tooltip_text( \AffiliateWP\Groups\Group $group ) : string {

		$rate = $group->get_meta( 'rate', '' );

		if ( empty( $rate ) ) {
			return '';
		}

		$rate_type = $group->get_meta( 'rate-type', '' );

		if ( empty( $rate_type ) ) {
			return '';
		}

		if ( 'percentage' === $rate_type ) {
			return "{$rate}%";
		}

		if ( 'flat' !== $rate_type ) {
			return '';
		}

		$flat_rate_basis = $group->get_meta( 'flat-rate-basis', '' );

		if ( empty( $flat_rate_basis ) ) {
			return '';
		}

		$dollar_rate = affwp_currency_filter( $rate );

		if ( 'per-order' === $flat_rate_basis ) {
			return sprintf( "{$dollar_rate} %s", __( 'Per Order', 'affiliate-wp' ) );
		}

		if ( 'per-product' === $flat_rate_basis ) {
			return sprintf( "{$dollar_rate} %s", __( 'Per Product', 'affiliate-wp' ) );
		}

		return '';
	}

	/**
	 * Modify the positioning hooks for the add/new templates to after status field.
	 *
	 * Instead of last (default of the abstract class).
	 *
	 * @since  2.12.0
	 */
	private function modify_multiselect_position_to_after_status() {

		add_filter( 'affwp_filter_hook_name_affwp_edit_affiliate_bottom', array( $this, 'set_group_selector_to_after_status_field' ) );
		add_filter( 'affwp_filter_hook_name_affwp_new_affiliate_bottom', array( $this, 'set_group_selector_to_after_status_field' ) );
	}

	/**
	 * Modify the filter names for multiselect positioning.
	 *
	 * @since  2.12.0
	 *
	 * @param  string $filter_name Filter.
	 *
	 * @return string Our filter.
	 */
	public function set_group_selector_to_after_status_field( $filter_name ) {

		if ( 'affwp_edit_affiliate_bottom' === $filter_name ) {
			return 'affwp_edit_affiliate_after_status'; // We added these hooks specifically for affiliates.
		}

		if ( 'affwp_new_affiliate_bottom' === $filter_name ) {
			return 'affwp_new_affiliate_after_status'; // We added these hooks specifically for affiliates.
		}

		return $filter_name;
	}
}
