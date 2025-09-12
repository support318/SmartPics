<?php
/**
 * Admin: Creatives List Table
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Creatives
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- For code formatting.

use AffWP\Admin\List_Table;
use AffWP\Creative;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

#[\AllowDynamicProperties]

/**
 * AffWP_Creatives_Table Class
 *
 * Renders the Affiliates table on the Affiliates page
 *
 * @since 1.2
 *
 * @see \AffWP\Admin\List_Table
 */
class AffWP_Creatives_Table extends List_Table {

	/**
	 * Default number of items to show per page
	 *
	 * @var string
	 * @since 1.2
	 */
	public $per_page = 30;

	/**
	 * Total number of creatives found
	 *
	 * @var int
	 * @since 1.0
	 */
	public $total_count;

	/**
	 * Number of active creatives found
	 *
	 * @var string
	 * @since 1.2
	 */
	public $active_count;

	/**
	 * Number of inactive creatives found
	 *
	 * @var string
	 * @since 1.2
	 */
	public $inactive_count;

	/**
	 * Number of scheduled creatives found
	 *
	 * @var string
	 * @since 2.15.0
	 */
	private string $scheduled_count = '';

	/**
	 * Number of creatives with start and/or end dates.
	 *
	 * @var string
	 * @since 2.15.0
	 */
	private string $has_schedule_count = '';

	/**
	 * Number of text_link type creatives.
	 *
	 * @var string
	 * @since 2.14.0
	 */
	public string $text_link_type_count;

	/**
	 * Number of image type creatives.
	 *
	 * @var string
	 * @since 2.14.0
	 */
	public string $image_type_count;

	/**
	 * Number of QR Code type creatives.
	 *
	 * @var string
	 * @since 2.17.0
	 */
	private string $qr_code_type_count = '';

	/**
	 * Get things started
	 *
	 * @access public
	 * @since  1.2
	 *
	 * @see WP_List_Table::__construct()
	 *
	 * @param array $args Optional. Arbitrary display and query arguments to pass through
	 *                    the list table. Default empty array.
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => 'creative',
			'plural'   => 'creatives',
		) );

		parent::__construct( $args );

		$this->get_creative_counts();
	}

	/**
	 * Retrieve the view types.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @return array $views All the views available.
	 */
	public function get_views() {
		$base = affwp_admin_url( 'creatives' );

		$current = ( isset( $_GET['status'] ) || isset( $_GET['type'] ) || isset( $_GET['scheduled'] ) )
			? sanitize_text_field( $_GET['status'] ?? $_GET['type'] ?? $_GET['scheduled'] )
			: '';

		$total_count          = "&nbsp;<span class='count'>({$this->total_count})</span>";
		$text_link_type_count = "&nbsp;<span class='count'>({$this->text_link_type_count})</span>";
		$image_type_count     = "&nbsp;<span class='count'>({$this->image_type_count})</span>";
		$qr_code_count        = "&nbsp;<span class='count'>({$this->qr_code_type_count})</span>";
		$active_count         = "&nbsp;<span class='count'>({$this->active_count})</span>";
		$inactive_count       = "&nbsp;<span class='count'>({$this->inactive_count})</span>";
		$has_schedule_count	  = "&nbsp;<span class='count'>({$this->has_schedule_count})</span>";

		$views = array(
			'all'            => sprintf( '<a href="%s"%s>%s</a>', remove_query_arg( 'status', $base ), $current === 'all' || $current == '' ? ' class="current"' : '', __('All', 'affiliate-wp') . $total_count ),
			'image_type'     => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'type', 'image', $base ), 'image' === $current ? ' class="current"' : '', __( 'Images', 'affiliate-wp' ) . $image_type_count ),
			'text_link_type' => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'type', 'text_link', $base ), 'text_link' === $current ? ' class="current"' : '', __( 'Text Links', 'affiliate-wp' ) . $text_link_type_count ),
			'qr_code_type'   => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'type', 'qr_code', $base ), 'qr_code' === $current ? ' class="current"' : '', __( 'QR Codes', 'affiliate-wp' ) . $qr_code_count ),
			'active'	     => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'status', 'active', $base ), $current === 'active' ? ' class="current"' : '', __('Active', 'affiliate-wp') . $active_count ),
			'inactive'       => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'status', 'inactive', $base ), $current === 'inactive' ? ' class="current"' : '', __('Inactive', 'affiliate-wp') . $inactive_count ),
			'scheduled'      => sprintf( '<a href="%s"%s>%s</a>', add_query_arg( 'scheduled', 'true', $base ), $current === 'true' ? ' class="current"' : '', __('Scheduled', 'affiliate-wp') . $has_schedule_count ),
		);

		// Don't show for non-pro users.
		if ( affwp_is_upgrade_required( 'pro' ) ) {
			unset( $views['scheduled'] );
		}

		// Even for non-pro users we will show QR Codes menu, but only while they still have QR Codes in their DB.
		if ( affwp_is_upgrade_required( 'pro' ) && $this->qr_code_type_count <= 0 ) {
			unset( $views['qr_code_type'] );
		}

		return $views;
	}

	/**
	 * Retrieve the table columns.
	 *
	 * @access public
	 * @since 1.2
	 *
	 * @return array $columns Array of all the list table columns.
	 */
	public function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'name'        => __( 'Name', 'affiliate-wp' ),
			'creative_id' => __( 'ID', 'affiliate-wp' ),
			'type'        => __( 'Type', 'affiliate-wp' ),
			'url'         => __( 'URL', 'affiliate-wp' ),
			'status'      => __( 'Status', 'affiliate-wp' ),
			'preview'     => __( 'Preview', 'affiliate-wp' ),
		);

		/**
		 * Filters the creatives list table columns.
		 *
		 * @since 1.2
		 *
		 * @param array                  $prepared_columns Prepared columns.
		 * @param array                  $columns          The columns for this list table.
		 * @param \AffWP_Creatives_Table $this             List table instance.
		 */
		return apply_filters( 'affwp_creative_table_columns', $this->prepare_columns( $columns ), $columns, $this );
	}

	/**
	 * Retrieve the table's sortable columns.
	 *
	 * @access public
	 * @since 1.2
	 *
	 * @return array Array of all the sortable columns.
	 */
	public function get_sortable_columns() {
		$columns = array(
			'creative_id' => array( 'creative_id', false ),
			'name'        => array( 'name', false ),
			'type'        => array( 'type', false ),
			'status'      => array( 'status', false ),
		);

		/**
		 * Filters the creatives list table sortable columns.
		 *
		 * @since 1.2
		 *
		 * @param array                  $columns          The sortable columns for this list table.
		 * @param \AffWP_Creatives_Table $this             List table instance.
		 */
		return apply_filters( 'affwp_creative_table_sortable_columns', $columns, $this );
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 1.2
	 *
	 * @param array  $creative Contains all the data of the creatives.
	 * @param string $column_name The name of the column.
	 *
	 * @return string Column Name
	 */
	function column_default( $creative, $column_name ) {
		switch( $column_name ){
			default:
				$value = isset( $creative->$column_name ) ? $creative->$column_name : '';
				break;
		}

		/**
		 * Filters the default value for each creatives list table column.
		 *
		 * @since 2.12.0 This was missing in 2.11.0+ but is present in e.g.
		 *               `affiliates/class-list-table.php` so added similar one here.
		 *
		 * This dynamic filter is appended with a suffix of the column name, for example:
		 *
		 *     `affwp_creative_table_referrals`
		 *
		 * @param string           $value     The column data.
		 * @param \AffWP\Affiliate $creative The current creative object
		 */
		return apply_filters( 'affwp_creative_table_' . $column_name, $value, $creative );
	}

	/**
	 * Renders the checkbox column in the creatives list table.
	 *
	 * @access public
	 * @since  2.2
	 *
	 * @param Creative $creative The current creative object.
	 *
	 * @return string Displays a checkbox.
	 */
	function column_cb( $creative ) {
		return '<input type="checkbox" name="creative_id[]" value="' . absint( $creative->creative_id ) . '" />';
	}

	/**
	 * Get the privacy information for a creative.
	 *
	 * @since 2.15.0
	 *
	 * @param object $creative The creative object.
	 *
	 * @return string
	 */
	private function get_privacy_info( $creative ) : string {

		if ( ! affwp_creative_is_private( $creative ) ) {
			return '';
		}

		return affwp_icon_tooltip(
			__( 'This creative can only be seen by specific affiliates and/or affiliate groups.', 'affiliate-wp' ),
			'locked',
			false,
			'privacy-status'
		);
	}

	/**
	 * Renders the "Name" column in the creatives list table.
	 *
	 * @access public
	 * @since  2.14.0
	 *
	 * @param Creative $creative The current creative object.
	 *
	 * @return string Data shown in the Name column.
	 */
	public function column_name( Creative $creative ) : string {
		$row_actions = array();

		$base_query_args = array(
			'page'        => 'affiliate-wp-creatives',
			'creative_id' => $creative->ID,
		);

		$value = sprintf(
			'<span class="name-wrap"><a href="%1$s">%2$s</a><span class="affwp-cretive-warnings">%3$s%4$s</span></span>',
			esc_url(
				add_query_arg(
					array_merge(
						$base_query_args,
						array(
							'affwp_notice' => false,
							'action'       => 'edit_creative',
						)
					),
					admin_url( 'admin.php' )
				)
			),
			$creative->get_name(),
			$this->get_privacy_info( $creative ),
			( 'private' === get_option( 'affwp_creative_name_privacy', '' ) && $creative->is_before_migration_time( 'date_updated' ) )
				? affwp_icon_tooltip(
					__( 'Edit this creative and enter a more descriptive name. The original name can be found in the Notes field.', 'affiliate-wp' ),
					'warning',
					false,
					'affwp-admin-creative-name-warning'
				)
				: '',
		);

		// Edit.
		$row_actions['edit'] = $this->get_row_action_link(
			__( 'Edit', 'affiliate-wp' ),
			array_merge(
				$base_query_args,
				array(
					'affwp_notice' => false,
					'action'       => 'edit_creative'
				)
			)
		);

		if ( strtolower( $creative->status ) == 'active' ) {

			$deactivate_label = true === affwp_has_scheduling_feature( $creative ) ? __( 'Deactivate Now', 'affiliate-wp' ) : __( 'Deactivate', 'affiliate-wp' );

			// Deactivate.
			$row_actions['deactivate'] = $this->get_row_action_link(
				$deactivate_label,
				array_merge(
					$base_query_args,
					array(
						'affwp_notice' => 'creative_deactivated',
						'action'       => 'deactivate',
					)
				),
				array( 'nonce' => 'affwp-creative-nonce' )
			);

		} else {
			$activate_label = true === affwp_has_scheduling_feature( $creative ) ? __( 'Activate Now', 'affiliate-wp' ) : __( 'Activate', 'affiliate-wp' );

			// Activate.
			$row_actions['activate'] = $this->get_row_action_link(
				$activate_label,
				array_merge(
					$base_query_args,
					array(
						'affwp_notice' => 'creative_activated',
						'action'       => 'activate',
					)
				),
				array( 'nonce' => 'affwp-creative-nonce' )
			);

		}

		if ( true === affwp_has_scheduling_feature( $creative ) ) {
			// Edit Schedule.
			$row_actions['edit_schedule'] = $this->get_row_action_link(
				__( 'Edit Schedule', 'affiliate-wp' ),
				array_merge(
					$base_query_args,
					array(
						'affwp_notice' => false,
						'action'       => 'edit_creative'
					)
				),
				array( 'base_uri' => '#affwp-creative-schedule' )
			);
		}

		// Delete.
		$row_actions['delete'] = $this->get_row_action_link(
			__( 'Delete', 'affiliate-wp' ),
			array_merge(
				$base_query_args,
				array(
					'affwp_notice' => false,
					'action'       => 'delete',
				)
			),
			array( 'nonce' => 'affwp-creative-nonce' )
		);

		/**
		 * Filters the row actions array for the Creatives list table.
		 *
		 * @since 2.14.0
		 *
		 * @param array            $row_actions Row actions array.
		 * @param \AffWP\Affiliate $affiliate   Current creative.
		 */
		$row_actions = apply_filters( 'affwp_creative_row_actions', $row_actions, $creative );

		$value .= '<div class="row-actions">' . $this->row_actions( $row_actions, true ) . '</div>';

		/**
		 * Filters the name column data for the creatives list table.
		 *
		 * @since 2.14.0
		 *
		 * @param string          $value    Data shown in the Name column.
		 * @param Creative $creative The current creative object.
		 */
		return apply_filters( 'affwp_creative_table_name', $value, $creative );
	}

	/**
	 * Render the Type column.
	 *
	 * @since 2.14.0
	 *
	 * @access public
	 * @param Creative $creative Creative object.
	 *
	 * @return string URL
	 */
	public function column_type( Creative $creative ) : string {
		return sprintf(
			'<a href="%1$s">%2$s</a>',
			add_query_arg( 'type', $creative->type, affwp_admin_url( 'creatives' ) ),
			$creative->get_type_label()
		);
	}

	/**
	 * Render the URL column.
	 *
	 * @access public
	 * @since 1.2
	 *
	 * @return string URL
	 */
	function column_url( $creative ) : string {
		return $creative->url;
	}

	/**
	 * Renders the Status column in the creatives list table.
	 *
	 * @since 2.15.0
	 * @since 2.16.0 Added html to color-code the status label.
	 *
	 * @param AffWP\Creative $creative The current creative object.
	 * @return string Status label.
	 */
	public function column_status( $creative ) {
		// Label text.
		$label = affwp_get_creative_status_label( $creative );

		// Status.
		$status = isset( $creative->status ) ? $creative->status : '';

		// If the creative is not scheduled, return the color-coded label.
		if ( false === affwp_has_scheduling_feature( $creative ) ) {
			return sprintf( '<span class="affwp-status %1$s">%2$s</span>',
				esc_attr( $status ),
				affwp_get_creative_status_label( $creative )
			);
		}

		// For creatives with a schedule, it should be color-coded with a clock icon.
		switch ( $status ) {
			case 'active':
				$label = sprintf( '<span class="affwp-status %1$s">%2$s<span class="dashicons dashicons-clock"></span></span>',
					esc_attr( $status ),
					$label
				);
				break;
			case 'inactive':
				$label = sprintf( '<span class="affwp-status %1$s">%2$s<span class="dashicons dashicons-clock"></span></span>',
					esc_attr( $status ),
					$label
				);
				break;
			case 'scheduled':
				$label = sprintf( '<span class="affwp-status %1$s">%2$s<span class="dashicons dashicons-clock"></span></span>',
					esc_attr( $status ),
					$label
				);
				break;
			default:
				// If the creative status is not recognized, return the label as-is.
				return $label;
		}

		// Add tooltips to explain the schedule.
		return $this->affwp_add_status_tooltips( $label, $creative, $status );
	}

	/**
	 * Add status tooltips to explain the schedule.
	 *
	 * @since 2.16.0
	 *
	 * @param string $label    The creative status label.
	 * @param object $creative The creative object.
	 * @param string $status   The creative status.
	 * @return string The color-coded creative status label with tooltips.
	 */
	private function affwp_add_status_tooltips( $label, $creative, $status ) {

		// Get the start and end dates.
		$start_date = '0000-00-00 00:00:00' === $creative->start_date ? false : $creative->start_date;
		$end_date   = '0000-00-00 00:00:00' === $creative->end_date ? false : $creative->end_date;

		// If the creative is not scheduled, return the default label.
		if ( false === $start_date && false === $end_date ) {
			return $label;
		}

		// If creative is active and has both a start AND end date.
		if ( 'active' === $status && false !== $start_date && false !== $end_date ) {
			return affwp_text_tooltip(
				$label,
				sprintf( '<div>%1$s %2$s</div><div>%3$s %4$s</div>',
					__( 'Started:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $start_date ), 'Y-m-d' ) ),
					__( 'Ends:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $end_date ), 'Y-m-d' ) ),
				),
				false
			);
		}

		// If a creative is active and only has a start date.
		if ( 'active' === $status && false !== $start_date && false === $end_date ) {
			return affwp_text_tooltip(
				$label,
				sprintf( '<div>%1$s %2$s</div>',
					__( 'Started:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $start_date ), 'Y-m-d' ) ),
				),
				false
			);
		}

		// If a creative is active and only has an end date.
		if ( 'active' === $status && false === $start_date && false !== $end_date ) {
			return affwp_text_tooltip(
				$label,
				sprintf( '<div>%1$s %2$s</div>',
					__( 'Ends:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $end_date ), 'Y-m-d' ) ),
				),
				false
			);
		}

		// If a creative is inactive and has both a start AND end date.
		if ( 'inactive' === $status && false !== $start_date && false !== $end_date ) {
			return affwp_text_tooltip(
				$label,
				sprintf( '<div>%1$s %2$s</div><div>%3$s %4$s</div>',
					__( 'Started:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $start_date ), 'Y-m-d' ) ),
					__( 'Ended:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $end_date ), 'Y-m-d' ) ),
				),
				false
			);
		}

		// If a creative is inactive and only has an end date.
		if ( 'inactive' === $status && false === $start_date && false !== $end_date ) {
			return affwp_text_tooltip(
				$label,
				sprintf( '<div>%1$s %2$s</div>',
					__( 'Ended:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $end_date ), 'Y-m-d' ) ),
				),
				false
			);
		}

		// If a creative is scheduled and has both a start AND end date.
		if ( 'scheduled' === $status && false !== $start_date && false !== $end_date ) {
			return affwp_text_tooltip(
				$label,
				sprintf( '<div>%1$s %2$s</div><div>%3$s %4$s</div>',
					__( 'Starts:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $start_date ), 'Y-m-d' ) ),
					__( 'Ends:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $end_date ), 'Y-m-d' ) ),
				),
				false
			);
		}

		// If a creative is scheduled and only has a start date.
		if ( 'scheduled' === $status && false !== $start_date && false === $end_date ) {
			return affwp_text_tooltip(
				$label,
				sprintf( '<div>%1$s %2$s</div>',
					__( 'Starts:', 'affiliate-wp' ),
					esc_html( affwp_date_i18n( strtotime( $start_date ), 'Y-m-d' ) ),
				),
				false
			);
		}

		return $label;
	}

	/**
	 * Render the preview column.
	 *
	 * @access public
	 *
	 * @since 2.14.0 Show the preview column for different types of creative.
	 *
	 * @param AffWP\Creative $creative Bulk actions.
	 * @return string preview.
	 */
	public function column_preview( $creative ) : string {

		return affwp_admin_link(
			'creatives',
			$creative->get_preview(),
			array(
				'creative_id' => $creative->ID,
				'action'      => 'edit_creative',
			)
		);

	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.2
	 * @access public
	 */
	function no_items() {
		_e( 'No creatives found.', 'affiliate-wp' );
	}

	/**
	 * Retrieve the bulk actions
	 *
	 * @access public
	 * @since 2.2
	 * @return array $actions Array of the bulk actions
	 */
	public function get_bulk_actions() {

		$actions = array(
			'activate'   => __( 'Activate', 'affiliate-wp' ),
			'deactivate' => __( 'Deactivate', 'affiliate-wp' ),
			'delete'     => __( 'Delete', 'affiliate-wp' ),
		);

		/**
		 * Filters the bulk actions to return in the creatives list table.
		 *
		 * @since 2.1.7
		 *
		 * @param array $actions Bulk actions.
		 */
		return apply_filters( 'affwp_creative_bulk_actions', $actions );
	}

	/**
	 * Process the bulk actions
	 *
	 * @access public
	 * @since 1.2
	 * @since 2.15.0 Added actions for scheduled creatives.
	 * @return void
	 */
	public function process_bulk_action() {

		if ( empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-creatives' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'affwp-creative-nonce' ) ) {
		 	return;
		}

		$ids = isset( $_GET['creative_id'] ) ? $_GET['creative_id'] : false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		if ( empty( $ids ) ) {
			return;
		}

		foreach ( $ids as $id ) {

			if ( 'delete' === $this->current_action() ) {
				affiliate_wp()->creatives->delete( $id );
			}

			if ( 'activate' === $this->current_action() ) {
				if ( true === affwp_has_scheduling_feature( $id ) ) {
					// Immediately activates the Scheduled or Inactive-Scheduled creative.
					// Update the start date to today to ensure the creative has the correct “started on” date.
					affwp_set_creative_start_date( $id, gmdate( 'Y-m-d H:i:s', strtotime( 'today' ) ) );
				}

				affwp_set_creative_status( $id, 'active' );
			}

			if ( 'deactivate' === $this->current_action() ) {
				if ( true === affwp_has_scheduling_feature( $id ) ) {
					// Immediately deactivates the Active-Scheduled creative.
					// Update the end date to today to ensure the creative has the correct “ended on” date.
					affwp_set_creative_end_date( $id, gmdate( 'Y-m-d H:i:s', strtotime( 'today' ) ) );
				}

				affwp_set_creative_status( $id, 'inactive' );
			}

			/**
			 * Fires after a creative bulk action is performed.
			 *
			 * The dynamic portion of the hook name, `$this->current_action()` refers
			 * to the current bulk action being performed.
			 *
			 * @since 2.1.7
			 *
			 * @param int $id The ID of the object.
			 */
			do_action( 'affwp_creatives_do_bulk_action_' . $this->current_action(), $id );

		}

	}

	/**
	 * Retrieve the creative counts.
	 *
	 * @access public
	 * @since 1.2
	 * @since 2.14.0 Added creative type text-link and image counters.
	 *
	 * @return void
	 */
	public function get_creative_counts() : void {
		$this->active_count = affiliate_wp()->creatives->count(
			array_merge(
				$this->query_args,
				array(
					'status' => 'active'
				)
			)
		);

		$this->inactive_count = affiliate_wp()->creatives->count(
			array_merge(
				$this->query_args,
				array(
					'status' => 'inactive'
				)
			)
		);

		$this->text_link_type_count = affiliate_wp()->creatives->count(
			array_merge(
				$this->query_args,
				array(
					'type' => 'text_link'
				)
			)
		);

		$this->image_type_count = affiliate_wp()->creatives->count(
			array_merge(
				$this->query_args,
				array(
					'type' => 'image'
				)
			)
		);

		$this->qr_code_type_count = affiliate_wp()->creatives->count(
			array_merge(
				$this->query_args,
				array(
					'type' => 'qr_code',
				)
			)
		);

		$this->scheduled_count = affiliate_wp()->creatives->count(
			array_merge(
				$this->query_args,
				array(
					'status' => 'scheduled'
				)
			)
		);

		$this->has_schedule_count = affiliate_wp()->creatives->count(
			array_merge(
				$this->query_args,
				array(
					'scheduled' => 'true'
				)
			)
		);

		$this->total_count = $this->active_count + $this->inactive_count + $this->scheduled_count;
	}

	/**
	 * Retrieve all the data for all the Creatives.
	 *
	 * @access public
	 * @since 1.2
	 * @since 2.14.0 Better args sanitization.
	 *
	 * @return array $creatives_data Array of all the data for the Creatives.
	 */
	public function creatives_data() {

		$page     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page = $this->get_items_per_page( 'affwp_edit_creatives_per_page', $this->per_page );

		$args = wp_parse_args(
			$this->query_args,
			array(
				'number'  => $per_page,
				'offset'  => $per_page * ( $page - 1 ),
				'status'  => isset( $_GET['status'] ) && is_string( $_GET['status'] ) && in_array( $_GET['status'], array( 'active', 'inactive', 'scheduled' ), true )
					? sanitize_text_field( $_GET['status'] )
					: '',
				'type'    => isset( $_GET['type'] ) && in_array( $_GET['type'], array_keys( affwp_get_creative_types() ), true )
					? sanitize_text_field( $_GET['type'] )
					: 'any',
				'orderby' => isset( $_GET['orderby'] )
					? sanitize_text_field( $_GET['orderby'] )
					: 'creative_id',
				'order'   => isset( $_GET['order'] ) && 'ASC' === strtoupper( sanitize_text_field( $_GET['order'] ) )
					? 'ASC'
					: 'DESC',
				'scheduled' => isset( $_GET['scheduled'] ) && is_string( $_GET['scheduled'] ) && 'true' === sanitize_text_field( $_GET['scheduled'] )
					? true
					: false,
			)
		);

		/**
		 * Filters the arguments used to retrieve creatives for the Creatives list table.
		 *
		 * @since 2.13.0
		 *
		 * @param array                  $args Arguments passed to get_creatives() to retrieve
		 *                                     the creative records for display.
		 * @param \AffWP_Creatives_Table $this Creatives list table instance.
		 */
		$args = apply_filters( 'affwp_creative_table_get_creatives', $args, $this );

		$creatives = affiliate_wp()->creatives->get_creatives( $args );

		// Retrieve the "current" total count for pagination purposes.
		$args['number']      = -1;
		$this->current_count = affiliate_wp()->creatives->count( $args );

		return $creatives;

	}

	/**
	 * Setup the final data for the table.
	 *
	 * @access public
	 * @since 1.2
	 *
	 * @uses AffWP_Creatives_Table::get_columns()
	 * @uses AffWP_Creatives_Table::get_sortable_columns()
	 * @uses AffWP_Creatives_Table::process_bulk_action()
	 * @uses AffWP_Creatives_Table::creatives_data()
	 * @uses WP_List_Table::get_pagenum()
	 * @uses WP_List_Table::set_pagination_args()
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'affwp_edit_creatives_per_page', $this->per_page );

		$this->get_column_info();

		$this->process_bulk_action();

		$data = $this->creatives_data();

		$current_page = $this->get_pagenum();

		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'any';

		switch ( $status ) {
			case 'active':
				$total_items = $this->active_count;
				break;
			case 'inactive':
				$total_items = $this->inactive_count;
				break;
			case 'scheduled':
				$total_items = $this->scheduled_count;
				break;
			case 'any':
				$total_items = $this->current_count;
				break;
		}

		$type = isset( $_GET['type'] ) && in_array( $_GET['type'], array_keys( affwp_get_creative_types() ), true )
			? $_GET['type']
			: 'any';

		switch ( $type ) {
			case 'text_link':
				$total_items = $this->text_link_type_count;
				break;
			case 'image':
				$total_items = $this->image_type_count;
				break;
			case 'qr_code':
				$total_items = $this->qr_code_type_count;
				break;
			case 'any':
				$total_items = $this->current_count;
				break;
		}

		$this->items = $data;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );

	}
}
