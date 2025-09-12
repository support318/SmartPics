<?php // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
/**
 * Group Admin UI Management
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 */

namespace AffiliateWP\Admin\Groups;

if ( class_exists( '\AffiliateWP\Admin\Groups' ) ) {
	return;
}

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

affwp_require_util_traits( 'data', 'nonce' );

#[AllowDynamicProperties]

/**
 * Group Admin UI Management
 *
 * This helps you add a Grouping screen for managing groups
 * by a group type.
 *
 * Just extend it and setup the correct properties.
 *
 * == Adding Meta Fields: ==
 *
 * Assign `$this->meta_fields` with an array of callbacks for each meta field:
 *
 *    // Meta fields.
 *    $this->meta_fields = array(
 *
 *        // Rate.
 *        'rate' => array(
 *
 *            // Views (returns markup with fields).
 *            'main' => function() : string {},
 *            'edit' => function( \AffiliateWP\Groups\Group $group ) : string {},
 *
 *            // Saving (add your own errors, can use $group->update( array( 'meta' => array() ) ) to save meta).
 *            'save' => function( \AffiliateWP\Groups\Group $group ) : bool {},
 *
 *            // Show in columns (returns markup, can use $group->get_meta() to get values).
 *            'column_header' => function( \AffiliateWP\Groups\Group $group ) {},
 *            'column_value'  => function( \AffiliateWP\Groups\Group $group ) {},
 *
 *           // You can also specify a method to fire for adding hooks, etc.
 *           'hooks' => function() {},
 *        ),
 *    );
 *
 * Here you can see that we added a meta field for rate that has a field when adding a group,
 * a field when editing, and a save function. It also includes functions for showing
 * markup for the column headers and values.
 *
 * @see includes/admin/affiliates/groups/class-management.php For example implimentation.
 *
 * @since 2.12.0
 * @since 2.13.0 Added extensibility for meta fields.
 */
abstract class Management {

	use \AffiliateWP\Utils\Data;
	use \AffiliateWP\Utils\Nonce;

	/**
	 * Prefix for all the hooks in this class.
	 *
	 * @since 2.14.0
	 *
	 * @var string
	 */
	private $hook_prefix = 'affwp_admin_groups_management';

	/**
	 * Errors
	 *
	 * @since 2.12.0
	 *
	 * @var null
	 */
	private $errors = null;

	/**
	 * Capability for add_submenu_page().
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $capability = 'administrator';

	/**
	 * The name of the items you can group.
	 *
	 * E.g. for grouping creatives, this would be 'cretives'.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $group_plural = '';

	/**
	 * The item a connector would be applied to.
	 *
	 * E.g. creative, or affiliate.
	 *
	 * @var string
	 */
	protected $item = '';

	/**
	 * The name of the item you can group.
	 *
	 * E.g. for grouping creatives, this would be 'cretive'.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $item_single = '';

	/**
	 * The name of the item you can group.
	 *
	 * E.g. for grouping creatives, this would be 'cretive'.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $item_plural = '';

	/**
	 * The group type.
	 *
	 * This coorilates to the value for `type` in the Grouping
	 * API database. This is used to register the group type with
	 * the Grouping API.
	 *
	 * It must be a valid `sanitize_key` value,
	 * or we might throw an exception.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $group_type = '';

	/**
	 * Submenu.
	 *
	 * @since 2.13.0
	 *
	 * @var null
	 */
	protected $menu = null;

	/**
	 * The menu slug used for add_submenu_page().
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $menu_slug = '';

	/**
	 * The menu title used for add_submenu_page().
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $menu_title = '';

	/**
	 * Meta fields.
	 *
	 * @since 2.13.0
	 *
	 * @var array
	 */
	protected $meta_fields = array();

	/**
	 * The page title for the screen.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $page_title = '';

	/**
	 * The parent menu item this groupign screen will be under.
	 *
	 * For grouping creatives, we used `affiliate-wp-creatives` as
	 * this is the slug for that main menu item.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $parent = '';

	/**
	 * The plural name of the group.
	 *
	 * E.g. for creative grouping we used `creatives`.
	 *
	 * Note, we automatically convert these to upercase and lowercase
	 * depending on the context.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $plural_title = '';

	/**
	 * The position under the parent parent menu to place screen.
	 *
	 * If you are adding a second item to e.g. Creatives,
	 * use this position to place it under Creatives
	 * before or after other screens.
	 *
	 * @since 2.12.0
	 *
	 * @var int
	 */
	protected $position = 0;

	/**
	 * The single name of the group.
	 *
	 * E.g. for creative grouping we used `creative`.
	 *
	 * Note, we automatically convert these to upercase and lowercase
	 * depending on the context.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $single_title = '';

	/**
	 * Successes
	 *
	 * @since 2.12.0
	 *
	 * @var array
	 */
	private $successes = array();

	/**
	 * The forced view.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	private $view = '';

	/**
	 * Connector ID.
	 *
	 * @since Unknown
	 *
	 * @var string
	 */
	private string $connector_id = '';

	/**
	 * Construct.
	 *
	 * @param string $connector_id The same id as the connector, if there is one.
	 *
	 * @since 2.12.0
	 * @since 2.18.0 Introduced matching connector ID.
	 */
	public function __construct( string $connector_id = '' ) {

		$this->errors = new \WP_Error();

		$this->connector_id = $connector_id;

		$this->validate_properties();

		/**
		 * Filter meta fields.
		 *
		 * @since 2.13.2
		 *
		 * @param array                                $meta_fields Meta fields.
		 * @param string                               $group_type  The group type.
		 * @param string                               $item        The item.
		 * @param string                               $menu_slug   The menu slug.
		 * @param \AffiliateWP\Admin\Groups\Management $management  This object.
		 *
		 * @var array
		 */
		$this->meta_fields = apply_filters(
			'affwp_group_managment_meta_fields',
			$this->meta_fields,
			$this->group_type,
			$this->item,
			$this->menu_slug,
			$this
		);

		$this->register_group_type();
		$this->register_group_connectable();

		$this->actions();
		$this->hooks();
	}

	/**
	 * Add a group.
	 *
	 * @since  2.12.0
	 *
	 * @return void When unable to add group.
	 *              When the user cannot perform this action.
	 *              The action isn't being requested.
	 */
	private function add_group() {

		if ( ! $this->is_group_action( 'add' ) ) {
			return;
		}

		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		if ( ! $this->verify_nonce_action( 'add', 'group' ) ) {
			return;
		}

		check_admin_referer(
			$this->nonce_action( 'add', 'group' ),
			$this->nonce_action( 'add', 'group' )
		);

		if ( ! isset( $_POST['name'] ) ) {
			return;
		}

		$name = trim( wp_unslash( $_POST['name'] ) );

		if ( ! $this->is_string_and_nonempty( $name ) ) {

			$this->errors->add(
				'empty_name',
				__( 'Name cannot be empty.', 'affiliate-wp' )
			);

			return;
		}

		if ( sanitize_key( $this->group_type ) !== $this->group_type ) {

			$this->errors->add(
				'bad_group_type',
				__( 'Unknown error.', 'affiliate-wp' )
			);

			return;
		}

		$esc_name = esc_html( $name );

		$group_id = affiliate_wp()->groups->add_group(
			array(
				'type'  => $this->group_type,
				'name'  => sanitize_key( $name ),
				'title' => $esc_name,
			)
		);

		if ( is_wp_error( $group_id ) && 'group_exists' === $group_id->get_error_code() ) {

			$this->errors->add(
				'unexpected_results',
				sprintf(
					/* translators: %1$s is the name of the item you can act on %2$s is the name of the item. */
					__( 'Unable to add %1$s %2$s because it already exists.', 'affiliate-wp' ),
					strtolower( $this->single_title ),
					sprintf( '<strong>%s</strong>', $esc_name )
				)
			);

			return;
		}

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {

			$this->errors->add(
				'unexpected_results',
				sprintf(
					/* translators: %1$s is the name of the item you can act on %2$s is the name of the item. */
					__( 'Unable to add %1$s %2$s.', 'affiliate-wp' ),
					strtolower( $this->single_title ),
					sprintf( '<strong>%s</strong>', $esc_name )
				)
			);

			return;
		}

		// Save the meta: should show/add own errors.
		if ( ! $this->save_meta( $group_id ) ) {

			// Delete the group, you did something wrong.
			affiliate_wp()->groups->delete_group( $group_id );

			return;
		}

		$this->successes[] = sprintf(
			/* translators: %1$s is the name of the item you can act on %2$s is the name of the item. */
			__( 'Added %1$s %2$s.', 'affiliate-wp' ),
			strtolower( $this->single_title ),
			sprintf( '<strong>%s</strong>', $esc_name )
		);
	}

	/**
	 * Add an error.
	 *
	 * @since 2.13.0
	 *
	 * @param string $name    Name of the error (code).
	 * @param string $message The message for the error.
	 * @param mixed  $data    The data.
	 */
	public function add_error( string $name, string $message, $data = null ) : void {

		$this->errors->add(
			$name,
			$message,
			$data
		);
	}

	/**
	 * Ensure we have a non-translated body class to trigger in JS.
	 *
	 * @since  2.12.0
	 *
	 * @param string $classes Current classes.
	 *
	 * @return string Added classes.
	 */
	public function admin_body_classes( $classes ) {

		if ( ! is_string( $classes ) ) {
			return $classes;
		}

		if ( ! $this->is_management_page() ) {
			return $classes;
		}

		return "{$classes} affiliates_page_affiliate-wp-{$this->menu_slug}";
	}

	/**
	 * Delete a group.
	 *
	 * @since  2.12.0
	 *
	 * @return void When unable to delete.
	 *              If the action isn't requested.
	 *              If the user cannot perform the operation.
	 */
	private function delete_group() {

		if ( ! $this->is_group_action( 'delete' ) ) {
			return;
		}

		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		if ( ! $this->verify_nonce_action( 'delete', 'group' ) ) {

			return;
		}

		check_admin_referer(
			$this->nonce_action( 'delete', 'group' ),
			$this->nonce_action( 'delete', 'group' )
		);

		$group_id = $this->get_group_id();

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {

			$this->errors->add(
				'bad_group_id',
				sprintf(
					/* Translators: . */
					__( 'Unable to delete %s.', 'affiliate-wp' ),
					strtolower( $this->single_title )
				)
			);

			return;
		}

		if ( sanitize_key( $this->group_type ) !== $this->group_type ) {

			$this->errors->add(
				'bad_group_type',
				__( 'Unknown error.', 'affiliate-wp' )
			);

			return;
		}

		$group = affiliate_wp()->groups->get_group( $group_id );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {

			$this->errors->add(
				'not_exists',
				sprintf(
					/* Translators: . */
					__( '%s does not exist.', 'affiliate-wp' ),
					ucfirst( $this->single_title )
				)
			);

			return;
		}

		$group_title = $group->get_title();

		if ( ! is_string( $group_title ) ) {

			$this->errors->add(
				'unexpected_db_results',
				sprintf(
					/* Translators: . */
					__( 'Unknown error deleting %s.', 'affiliate-wp' ),
					ucfirst( $this->single_title )
				)
			);

			return;
		}

		$delete = $group->delete();

		if ( affiliate_wp()->groups->group_exists( $group_id ) ) {

			$this->errors->add(
				'group_exists_after_delete',
				sprintf(
					// Translators: .
					__( 'Unable to delete %1$s %2$s.', 'affiliate-wp' ),
					strtolower( $this->single_title ),
					sprintf(
						'<strong>"%s"</strong>',
						$group_title
					)
				)
			);

			return;
		}

		if ( ! is_bool( $delete ) ) {

			$this->errors->add(
				'group_deleted_with_unexpected_results',
				sprintf(
					// Translators: .
					__( 'Unknown error while deleting %s.', 'affiliate-wp' ),
					strtolower( $this->single_title )
				)
			);

			return;
		}

		$this->successes[] = sprintf(
			/* translators: %1$s is the name of the item you can act on %2$s is the name of the item. */
			__( 'Deleted %1$s %2$s.', 'affiliate-wp' ),
			strtolower( $this->single_title ),
			sprintf(
				'<strong>%s</strong>',
				$group_title
			)
		);
	}

	/**
	 * Get the count for a specific group and the items that are in it.
	 *
	 * @since  2.12.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group.
	 *
	 * @return string|int Count of the items in the group.
	 *                    If an `AffiliateWP\Admin\Groups\Connector` is setup
	 *                    for the item, we will automatically convert the count
	 *                    into a link that filters the list view of the items.
	 *
	 * @throws \InvalidArgumentException If you do not supply a valid group object.
	 * @throws \InvalidArgumentException If you have not set the item for this class to a non-empty string.
	 * @throws \Exception                If the item is not registered as a connectable.
	 * @throws \Exception                If group is not a registered connectable.
	 */
	private function get_group_items_count( $group ) {

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {
			throw new \InvalidArgumentException( '$group must be a valid \AffiliateWP\Groups\Group group object.' );
		}

		if ( ! $this->is_string_and_nonempty( $this->item ) ) {
			throw new \InvalidArgumentException( '$this->item must be a non-empty string.' );
		}

		if ( ! affiliate_wp()->connections->is_registered_connectable( $this->item ) ) {
			throw new \Exception( '$this->item is not a registered connectable.' );
		}

		if ( ! affiliate_wp()->connections->is_registered_connectable( 'group' ) ) {
			throw new \Exception( 'group is not a registered connectable.' );
		}

		$connected_count = affiliate_wp()->connections->get_connected(
			$this->item,
			'group',
			$group->group_id,
			'count'
		);

		if ( ! is_numeric( $connected_count ) ) {
			return 0; // Fail gracefully.
		}

		/**
		 * Check for connector.
		 *
		 * If you configure a `AffiliateWP\Admin\Groups\Connector` this filter will automatically
		 * get signaled to return a link.
		 *
		 * There should be no need to filter this value manually.
		 *
		 * Setting this to true will result in a link to the list view for filtering, which
		 * requires the implementation of a Connector for the same item(s) here.
		 *
		 * @since 2.12.0
		 *
		 * @param $has_connector Set to true to allow a link to the list view.
		 */
		if ( ! $this->has_connector() ) {

			// Just return the count, we have nowhere to link to (via the connector).
			return intval( $connected_count );
		}

		// Form a valid nonce for linking to filtered view.
		$filter_items_nonce_name = $this->nonce_action( 'filter', 'items' );
		$filter_items_nonce      = wp_create_nonce( $filter_items_nonce_name );

		$connector_id = $this->get_connector_id();

		// Return a link to the list view.
		return sprintf(
			'<a href="%s">%s</a>',
			"?page=affiliate-wp-{$this->item}s&filter-{$connector_id}-top={$group->group_id}&{$filter_items_nonce_name}={$filter_items_nonce}",
			$connected_count
		);
	}

	/**
	 * Get the matching connector ID.
	 *
	 * @since 2.18.0
	 *
	 * @return string
	 */
	private function get_connector_id() : string {
		return $this->connector_id;
	}

	/**
	 * Update a group.
	 *
	 * @since 2.12.0
	 *
	 * @return void If the action isn't being requested.
	 *              If the user cannot peform the action.
	 *              When there's an error.
	 */
	private function update_group() {

		if ( ! $this->is_group_action( 'update' ) ) {
			return;
		}

		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		if ( ! $this->verify_nonce_action( 'update', 'group' ) ) {
			return;
		}

		check_admin_referer(
			$this->nonce_action( 'update', 'group' ),
			$this->nonce_action( 'update', 'group' )
		);

		$group_id = $this->get_group_id();

		if ( ! isset( $_POST['name'] ) ) {
			return;
		}

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {

			$this->errors->add(
				'bad_group_id',
				sprintf(
					/* Translators: . */
					__( 'Unable to update %s.', 'affiliate-wp' ),
					strtolower( $this->single_title )
				)
			);

			$this->update_view( 'main' );

			return;
		}

		// Save meta, should add/show own errors.
		if ( ! $this->save_meta( $group_id ) ) {
			return;
		}

		// Don't use filter_input as it strips characters we want to keep.
		$name = trim( wp_unslash( $_POST['name'] ) );

		if ( ! $this->is_string_and_nonempty( $name ) ) {

			$this->errors->add(
				'empty_name',
				__( 'Name cannot be empty.', 'affiliate-wp' )
			);

			$this->update_view( 'edit' );

			return;
		}

		if ( sanitize_key( $this->group_type ) !== $this->group_type ) {

			$this->errors->add(
				'bad_group_type',
				__( 'Group type error.', 'affiliate-wp' )
			);

			$this->update_view( 'main' );

			return;
		}

		$group = affiliate_wp()->groups->get_group( $group_id );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {

			$this->errors->add(
				'not_exists',
				sprintf(
					/* Translators: . */
					__( '%s does not exist.', 'affiliate-wp' ),
					ucfirst( $this->single_title )
				)
			);

			$this->update_view( 'main' );

			return;
		}

		if ( trim( $group->get_title() ) === trim( $name ) ) {

			// Try and save the meta if the name is unchanged.
			if ( ! $this->save_meta( $group_id ) ) {
				return;
			}

			$this->update_view( 'main' );

			$this->successes[] = sprintf(
				/* translators: %1$s is the name of the group type. */
				__( 'Updated %1$s.', 'affiliate-wp' ),
				strtolower( $this->single_title )
			);

			return;
		}

		// Update the title.
		$update = $group->update(
			array(
				'title' => $name,
				'type'  => $this->group_type,
			)
		);

		if ( is_wp_error( $update ) && 'group_exists' === $update->get_error_code() ) {

			$this->errors->add(
				'conflict',
				sprintf(
					// Translators: .
					__( 'A %1$s with the name %2$s already exists.', 'affiliate-wp' ),
					strtolower( $this->single_title ),
					sprintf(
						'<strong>"%s"</strong>',
						$name
					)
				)
			);

			$this->update_view( 'edit' );

			return;
		}

		if ( ! is_bool( $update ) ) {

			$this->errors->add(
				'error_updating_group',
				sprintf(
					// Translators: .
					__( 'Unable to update %s.', 'affiliate-wp' ),
					strtolower( $this->single_title )
				)
			);

			$this->update_view( 'main' );

			return;
		}

		$this->successes[] = sprintf(
			/* translators: %1$s is the name of the group tyoe and %2$s is the value it was updated to. */
			__( 'Updated %1$s to %2$s.', 'affiliate-wp' ),
			strtolower( $this->single_title ),
			sprintf( '<strong>%s</strong>', $name )
		);

		$this->update_view( 'main' );
	}

	/**
	 * Main (body) content of the screen.
	 *
	 * @since  2.12.0
	 *
	 * @throws \InvalidArgumentException If somehow the forced view is not a string.
	 *
	 * @return void If a view is being forced, we load that.
	 *              If the edit screen is activated, we load that.
	 *              Otherwise the main view.
	 */
	private function body() {

		if ( ! is_string( $this->view ) ) {
			throw new \InvalidArgumentException( '$this->view should be a string.' );
		}

		if (
			! empty( $this->view ) && method_exists( $this, $this->view )
		) {

			$view = $this->view;

			// Use the forced view.
			$this->$view();

			return;
		}

		if ( $this->is_group_action( 'edit' ) ) {

			$this->edit();

			return;
		}

		$this->main();
	}

	/**
	 * Set the management link.
	 *
	 * To capture this value use, e.g.
	 *
	 *     apply_filters( "affwp_connector_creative_href", 'default' );
	 *
	 * @since  2.12.0
	 *
	 * @param string $link Link.
	 *
	 * @return string Link to our management page.
	 */
	public function broadcast_management_link( $link ) {
		return "admin.php?page=affiliate-wp-{$this->menu_slug}";
	}

	/**
	 * Add the screen to the submenu.
	 *
	 * @since 2.12.0
	 *
	 * @return void If our item's parent page(s) aren't loaded.
	 */
	public function add_submenu() {

		if ( ! $this->is_parent() && ! $this->is_management_page() ) {
			return; // Don't show unless we are on our own page or the parent.
		}

		// Add a sub-menu page for this group.
		$this->menu = add_submenu_page(
			'affiliate-wp',
			$this->page_title,
			"↳ {$this->menu_title}",
			$this->capability,
			$this->get_page(),
			array( $this, 'screen' ),
			$this->get_menu_position()
		);
	}

	/**
	 * Edit view.
	 *
	 * @since  2.12.0
	 *
	 * @return void If there isn't a group id, we load the main view.
	 *              If the group doesn't exist, we load the main view.
	 *              If the group can't be converted to an object, we load the main view.
	 *              Otherwise we show this view.
	 *
	 * @throws \Exception If you supply meta fields that aren't callable for this view.
	 */
	private function edit() {

		$group_id = $this->get_group_id();

		// Can't edit something that has no hope of being in the database.
		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {

			$this->main();

			return;
		}

		// Can't edit something that is no longer in the database.
		if ( true !== affiliate_wp()->groups->group_exists( $group_id ) ) {
			?>

			<div class="notice notice-warning">
				<p><?php esc_html_e( 'Group no longer exists.', 'affiliate-wp' ); ?></p>
			</div>

			<?php

			$this->main();

			return;
		}

		$group = affiliate_wp()->groups->get_group( $group_id );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {
			?>

			<div class="notice notice-error">
				<p><?php esc_html_e( 'Unknown error trying to edit that group.', 'affiliate-wp' ); ?></p>
			</div>

			<?php

			$this->main();

			return;
		}

		?>

		<form
			name="edittag"
			id="edittag"
			method="post"
			action="<?php echo esc_attr( "admin.php?page=affiliate-wp-{$this->menu_slug}&action=update&group_id={$group->group_id}" ); ?>"
			class="validate"
			<?php
			/*
			 * x-data:
			 *
			 * AlpineJS (https://alpinejs.dev/directives/data)
			 *
			 * You can use data for elements within this container.
			 * There is also one for the main method.
			 *
			 * E.g.:
			 *
			 *     <... x-on:change="data.show = ( 3 > 2 );">
			 *     <... x-show="data.show">
			 */
			?>
			x-data="{ data: [] }">

			<table class="form-table" role="presentation">
				<tbody>
					<tr class="form-field form-required term-name-wrap">

						<th scope="row">
							<label for="name">
								<?php esc_html_e( 'Name', 'affiliate-wp' ); ?>
							</label>
						</th>

						<td>
							<input
								name="name"
								id="name"
								type="text"
								value="<?php echo esc_attr( $group->get_title() ); ?>"
								size="40"
								aria-required="true"
								required
								aria-describedby="name-description">

							<p class="description" id="name-description">
								<?php echo esc_html( $this->get_name_description() ); ?>
							</p>
						</td>
					</tr>

					<!-- Meta fields for edit view. -->
					<?php foreach ( $this->meta_fields as $meta_key => $meta_field ) : ?>
						<?php

						if ( ! isset( $meta_field['edit'] ) ) {
							continue; // No edit field.
						}

						if ( ! is_callable( $meta_field['edit'] ) ) {
							throw new \Exception( "\$meta_field['edit'] must be a public callable function." );
						}

						// Output the field from the function.
						echo filter_var( $meta_field['edit']( $group ), FILTER_UNSAFE_RAW );

						?>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="edit-tag-actions">
					<input
						type="submit"
						class="button button-primary"
						value="<?php esc_html_e( 'Update', 'affiliate-wp' ); ?>" />

					<a
					href="admin.php?page=<?php echo esc_attr( $this->get_page() ); ?>"
					class="button button-secondary">

						<?php esc_html_e( 'Cancel', 'affiliate-wp' ); ?>
					</a>
			</div>

			<?php

			wp_nonce_field(
				$this->nonce_action( 'update', 'group' ),
				$this->nonce_action( 'update', 'group' )
			);

			?>
		</form>

		<?php
	}

	/**
	 * Show runtime errors.
	 *
	 * @since  2.12.0
	 */
	private function errors() {

		foreach ( $this->errors->get_error_messages() as $message ) {
			?>

			<div class="notice notice-error">
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>

			<?php
		}
	}

	/**
	 * Get the current group id.
	 *
	 * @since  2.12.0
	 *
	 * @return int The value passed via GET.
	 */
	private function get_group_id() {
		return filter_input( INPUT_GET, 'group_id', FILTER_SANITIZE_NUMBER_INT );
	}

	/**
	 * Get the group ID's for the current group type.
	 *
	 * @since  2.12.0
	 * @since  2.13.0 Added caching option.
	 * @since  2.15.0 Added pagination.
	 *
	 * @param bool $cached Use caching.
	 *
	 * @return array
	 */
	protected function get_groups( $cached = true ) {

		$cache = null;

		if ( is_array( $cached ) ) {
			return $cache; // Use cache.
		}

		$groups = affiliate_wp()->groups->get_groups(
			array(
				'fields'  => 'objects',
				'number'  => apply_filters( 'affwp_unlimited', -1, 'abstract_groups_management_get_all_group_objects_number' ),
				'orderby' => 'title',
				'type'    => $this->group_type,

				// Pagination.
				'number'  => $this->get_groups_per_page(),
				'offset'  => ( 1 === $this->get_paged() )
					? 0
					: $this->get_paged_offset(),
			)
		);

		return is_array( $groups )
			? $cache = $groups // Save in the cache.
			: array(); // Fail gracefully when there are major erros.
	}

	/**
	 * Get the paginated offset.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	private function get_paged_offset() : int {
		return $this->get_paged() * $this->get_groups_per_page() - $this->get_groups_per_page();
	}

	/**
	 * Get the menu position of the parent.
	 *
	 * @since  2.12.0
	 *
	 * @return int The position of the parent plus one and a decimal coorilating
	 *             to the position specified in this class which results in
	 *             being able to position our menu just under the parent
	 *             and being able to position our sub-menus based on decimal.
	 *             Or zero plus decimal if there isn't a position.
	 *
	 * @throws \Exception If we can't access the submenu.
	 */
	private function get_menu_position() {

		global $submenu;

		if ( ! is_array( $submenu ) ) {
			throw new \Exception( "Please instanciate this during 'admin_menu' action." );
		}

		foreach ( $submenu['affiliate-wp'] as $position => $menu ) {
			foreach ( $menu as $item ) {

				if ( $item !== $this->parent ) {
					continue;
				}

				return $position + 1 + ( $this->position / 10 );
			}
		}

		// This only happens when there isn't a parent.
		return 0 + ( $this->position / 10 );
	}

	/**
	 * Do we have an associated connector?
	 *
	 * Used mostly for counts.
	 *
	 * @since 2.13.0
	 *
	 * @return bool
	 */
	private function has_connector() {

		if ( empty( $this->item ) || ! is_string( $this->item ) ) {
			return false; // No item to use for broadcasting filter below.
		}

		if ( empty( $this->get_connector_id() ) ) {
			return false;
		}

		// The connector should broadcast to use, through this filter, if a connector is connected.
		return apply_filters( "{$this->hook_prefix}_{$this->item}_has_connector", false );
	}

	/**
	 * Perform all the actions.
	 *
	 * @since  2.12.0
	 *
	 * @return void If we aren't on our page(s).
	 */
	private function actions() {

		if ( ! $this->is_management_page() ) {
			return;
		}

		$this->add_group();
		$this->delete_group();
		$this->update_group();
	}

	/**
	 * Screen callback for add_submenu_page().
	 *
	 * @since  2.12.0
	 */
	public function screen() {

		?>
		<div class="wrap nosubsub">
			<?php $this->header(); ?>
			<?php $this->body(); ?>
		</div>
		<?php
	}

	/**
	 * Header
	 *
	 * @since  2.12.0
	 */
	private function header() {
		?>

		<h1 class="wp-heading-inline">
			<?php echo esc_html( $this->page_title ); ?>
		</h1>

		<?php

		$this->errors();
		$this->successes();
	}

	/**
	 * Hooks
	 *
	 * @since 2.12.0
	 * @since 2.13.0 Added hooks that can be fired via meta fields.
	 *
	 * @throws \Exception If you add meta field with `hooks` and there isn't a public callable method associcated.
	 */
	public function hooks() {

		// Call meta hooks.
		foreach ( $this->meta_fields as $meta_field ) {

			if ( ! isset( $meta_field['hooks'] ) ) {
				continue; // No hooks method to call.
			}

			if ( ! is_callable( $meta_field['hooks'] ) ) {
				throw new \Exception( "\$meta_field['hooks'] must be a callable public function." );
			}

			$meta_field['hooks']();
		}

		add_action( 'admin_menu', array( $this, 'add_submenu' ), 20 ); // After our other menus are added on 10.

		if ( ! is_admin() ) {
			return;
		}

		// This is mostly used by the AffiliateWP\Admin\Groups connector to link to our management screen.
		add_filter( strtolower( "affwp_connector_{$this->item_single}_href" ), array( $this, 'broadcast_management_link' ) );

		if ( ! $this->is_management_page() ) {
			return; // The below only happens on the management page.
		}

		add_action( 'admin_body_class', array( $this, 'admin_body_classes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

		// Make AffiliateWP knows this is one of our admin pages.
		add_filter( 'affwp_is_admin_page', '__return_true' );
	}

	/**
	 * Is a specific action being fired?
	 *
	 * @since  2.12.0
	 *
	 * @param string $action Action.
	 *
	 * @return bool
	 */
	private function is_group_action( $action ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No need to validate, just want to see if isset.
		if ( ! isset( $_REQUEST['page'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No need to validate, just want to see if isset.
		if ( ! isset( $_REQUEST['action'] ) ) {
			return false;
		}

		return filter_input( INPUT_POST, 'action', FILTER_UNSAFE_RAW ) === $action ||
			filter_input( INPUT_GET, 'action', FILTER_UNSAFE_RAW ) === $action;
	}

	/**
	 * Are we on our admin page?
	 *
	 * @since  2.12.0
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you pass a non-string to `$page`.
	 */
	protected function is_management_page() {

		return $this->get_page()
			=== filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
	}

	/**
	 * Are we on the parent submenu page?
	 *
	 * @since  2.12.0
	 *
	 * @return bool
	 */
	private function is_parent() {

		return filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW )
			=== $this->parent;
	}

	/**
	 * What is the admin page?
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	private function get_page() : string {
		return "affiliate-wp-{$this->menu_slug}";
	}

	/**
	 * Get the URL to the page.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	private function get_page_url() : string {

		return add_query_arg(
			array(
				'page' => $this->get_page(),
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Get the paged URL.
	 *
	 * @param int $paged The page.
	 *
	 * @var string
	 */
	private function get_paged_url( int $paged = 1 ) : string {

		return add_query_arg(
			array(
				'paged' => intval( $paged ),
			),
			$this->get_page_url()
		);
	}

	/**
	 * Get the ?paged value.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	private function get_paged() : int {

		$paged_get = absint( filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) );

		$paged = ( $paged_get <= $this->get_total_paged() ) ? $paged_get : 1;

		return ( 0 === $paged )
			? 1
			: $paged;
	}

	/**
	 * Get next for ?paged.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	private function get_next_paged() : int {

		$next_paged = $this->get_paged() + 1;

		if ( $next_paged > $this->get_total_paged() ) {
			return $this->get_paged();
		}

		return $next_paged;
	}

	/**
	 * Get the total pagination pages.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	private function get_total_paged() : int {
		return ceil( $this->get_total_group_count() / $this->get_groups_per_page() );
	}

	/**
	 * Get the total number of groups.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	private function get_total_group_count() : int {

		return affiliate_wp()->groups->get_groups(
			array(
				'fields' => 'ids',
				'number' => apply_filters( 'affwp_unlimited', -1, 'get_total_groups' ),
				'type'   => $this->group_type,
			),
			true // Get just the count.
		);
	}

	/**
	 * Get previous for ?paged.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	private function get_prev_paged() : int {
		return absint( $this->get_paged() - 1 );
	}

	/**
	 * Is this the first paginated page?
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function is_first_paged() : bool {
		return $this->get_paged() === 1;
	}

	/**
	 * Is the is the last paginated page?
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function is_last_paged() : bool {
		return $this->get_paged() === $this->get_total_paged();
	}

	/**
	 * Pagination.
	 *
	 * @since 2.15.0
	 *
	 * @return void Display only.
	 */
	private function pagination() : void {

		if ( $this->is_one_paged() ) {
			return; // No pagination.
		}

		?>

		<form class="group-pagination" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get" style="display: inline-block;">

			<input type="hidden" name="page" value="<?php echo esc_attr( $this->get_page() ); ?>">

			<span class="pagination-links" style="display: inline-block;">
				<a href="<?php echo esc_url( $this->get_paged_url( 1 ) ); ?>"><!--
					--><span class="tablenav-pages-navspan button" aria-hidden="true">«</span><!--
				--></a>

				<a href="<?php echo esc_url( $this->get_paged_url( $this->get_prev_paged() ) ); ?>"><!--
					--><span class="tablenav-pages-navspan button <?php echo esc_attr( $this->is_first_paged() ? 'disabled' : '' ); ?>" aria-hidden="true">‹</span><!--
				--></a>

				<span class="paging-input">
					<input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo absint( $this->get_paged() ); ?>" size="1" aria-describedby="table-paging">

					<span class="tablenav-paging-text"> <?php echo esc_html_x( 'of', 'affiliate-wp' ); ?>
						<span class="total-pages"><?php echo absint( $this->get_total_paged() ); ?></span>
					</span>
				</span>

				<a class="next-page button <?php echo esc_attr( $this->is_last_paged() ? 'disabled' : '' ); ?>" href="<?php echo esc_url( $this->get_paged_url( $this->get_next_paged() ) ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Next page', 'affiliate-wp' ); ?></span>
					<span aria-hidden="true">›</span>
				</a>

				<a class="last-page button" href="<?php echo esc_url( $this->get_paged_url( $this->get_total_paged() ) ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Last page', 'affiliate-wp' ); ?></span>
					<span aria-hidden="true">»</span>
				</a>
			</span>

		</form>

		<?php
	}

	/**
	 * Get the per-page setting for groups.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	private function get_groups_per_page() : int {

		$cache = null;

		if ( is_int( $cache ) ) {
			return $cache;
		}

		// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found -- Used to cache at RUNTIME.
		return $cache = absint(
			/**
			 * Filter how many groups we show per page.
			 *
			 * @since 2.15.0
			 *
			 * @param int    $per_page   Number per page.
			 * @param string $group_type The group type.
			 * @param string $item       The item.
			 * @param string $menu_slug  The menu slug.
			 */
			apply_filters(
				'affwp_group_management_get_groups_per_page',
				10,
				$this->group_type,
				$this->item,
				$this->menu_slug
			)
		);
	}

	/**
	 * Are we able to show all groups on one paginated page?
	 *
	 * @since 2.15.0
	 *
	 * @return bool
	 */
	private function is_one_paged() : bool {
		return $this->get_total_paged() === 1;
	}

	/**
	 * Row Actions (Before).
	 *
	 * Override to implement your own, or use hooks.
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return void Display only.
	 */
	protected function row_actions_before( \AffiliateWP\Groups\Group $group ) : void {

		/**
		 * Add extra row actions (Before).
		 *
		 * @since 2.14.0
		 *
		 * @param \AffiliateWP\Groups\Group $group      The group object.
		 * @param string                    $group_type The group type.
		 * @param string                    $item       The item.
		 * @param string                    $menu_slug  The menu slug.
		 */
		do_action(
			"{$this->hook_prefix}_row_actions_before",
			$group,
			$this->group_type,
			$this->item,
			$this->menu_slug
		);
	}

	/**
	 * Row Actions (After).
	 *
	 * Override to implement your own, or use hooks.
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return void Display only.
	 */
	protected function row_actions_after( \AffiliateWP\Groups\Group $group ) : void {

		/**
		 * Add extra row actions (After).
		 *
		 * @since 2.14.0
		 *
		 * @param \AffiliateWP\Groups\Group $group      The group object.
		 * @param string                    $group_type The group type.
		 * @param string                    $item       The item.
		 * @param string                    $menu_slug  The menu slug.
		 */
		do_action(
			"{$this->hook_prefix}_row_actions_after",
			$group,
			$this->group_type,
			$this->item,
			$this->menu_slug
		);
	}

	/**
	 * Row Actions for the Group.
	 *
	 * @since 2.14.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group object.
	 *
	 * @return void Display only.
	 */
	protected function row_actions( \AffiliateWP\Groups\Group $group ) : void {
		?>

		<div class="row-actions">

			<?php $this->row_actions_before( $group ); ?>

			<span class="edit">
				<a
					href="<?php echo esc_url( $this->get_group_edit_url( $group->group_id ) ); ?>"
					aria-label="<?php /* Translators: */ echo esc_html( sprintf( __( 'Edit %s', 'affiliate-wp' ), $group->get_title() ) ); ?>">

						<?php echo esc_html_e( 'Edit', 'affiliate-wp' ); ?>
				</a>
				|
			</span>
			<span class="inline">
				<a
					href="<?php echo esc_url( wp_nonce_url( "admin.php?page=affiliate-wp-{$this->menu_slug}&action=delete&group_id={$group->group_id}", $this->nonce_action( 'delete', 'group' ), $this->nonce_action( 'delete', 'group' ) ) ); ?>"
					class="editinline delete group"
					aria-label="<?php /* Translators: */ echo esc_attr( sprintf( __( 'Delete %s', 'affiliate-wp' ), $group->get_title() ) ); ?>"
					aria-expanded="false"
					style="color: #b32d2e;">

					<?php esc_html_e( 'Delete', 'affiliate-wp' ); ?>
				</a>
			</span>

			<?php $this->row_actions_after( $group ); ?>
		</div>

		<?php
	}

	/**
	 * Get the group edit link.
	 *
	 * @since 2.14.0
	 *
	 * @param int $group_id The group id.
	 *
	 * @return string URL.
	 */
	protected function get_group_edit_url( int $group_id ) : string {

		return wp_nonce_url(
			"admin.php?page=affiliate-wp-{$this->menu_slug}&action=edit&group_id={$group_id}",
			$this->nonce_action( 'edit', 'group' ),
			'_wpnonce'
		);
	}

	/**
	 * Row classes for group.
	 *
	 * @since 2.14.0
	 *
	 * @param \AffiliateWP\Groups\Group $group The group object.
	 *
	 * @return string
	 */
	protected function row_classes( \AffiliateWP\Groups\Group $group ) : string {

		/**
		 * Add classes to each row.
		 *
		 * @since 2.14.0
		 *
		 * @param string                    $classes    The classes.
		 * @param \AffiliateWP\Groups\Group $group      The group object.
		 * @param string                    $group_type The group type.
		 * @param string                    $item       The item.
		 * @param string                    $menu_slug  The menu slug.
		 */
		return apply_filters(
			"{$this->hook_prefix}_row_classes",
			"group-{$group->get_id()}",
			$group,
			$this->group_type,
			$this->item,
			$this->menu_slug
		);
	}

	/**
	 * Main view for all groups.
	 *
	 * @since  2.12.0
	 *
	 * @throws \Exception If you supply meta fields that aren't callable for this view.
	 */
	private function main() {

		$groups = $this->get_groups();

		?>

		<div
			class="wp-clearfix"
			id="col-container"
			<?php
			/*
			 * x-data:
			 *
			 * AlpineJS (https://alpinejs.dev/directives/data)
			 *
			 * You can use data for elements within this container.
			 * There is also one for the edit method.
			 *
			 * E.g.:
			 *
			 *     <... x-on:change="data.show = ( 3 > 2 );">
			 *     <... x-show="data.show">
			 */
			?>
			x-data="{ data: {} }">

				<!-- Add New -->
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">

							<h2><?php /* translators: %s is the name of the group type. */ echo esc_html( sprintf( __( 'Add New %s', 'affiliate-wp' ), ucfirst( $this->single_title ) ) ); ?></h2>

							<form id="addtag" method="post" action="admin.php?page=<?php echo esc_attr( $this->get_page() ); ?>" class="validate">

								<div class="form-field form-required term-name-wrap">

									<label for="name">
										<?php esc_html_e( 'Name', 'affiliate-wp' ); ?>
									</label>

									<input
										name="name"
										id="name"
										type="text"
										value=""
										size="40"
										aria-required="true"
										required
										aria-describedby="name-description" />

									<p id="name-description"><?php echo esc_html( $this->get_name_description() ); ?></p>
								</div>

								<!-- Meta fields for main/add view. -->
								<?php foreach ( $this->meta_fields as $meta_key => $meta_field ) : ?>
									<?php

									if ( ! isset( $meta_field['main'] ) ) {
										continue; // No main view field.
									}

									if ( ! is_callable( $meta_field['main'] ) ) {
										throw new \Exception( "\$meta_field['main'] must be a callable public function." );
									}

									// Output the field from the function.
									echo filter_var( $meta_field['main'](), FILTER_UNSAFE_RAW );

									?>
								<?php endforeach; ?>

								<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php /* translators: %s is the name of the group type. */ echo esc_attr( sprintf( __( 'Add New %s', 'affiliate-wp' ), $this->single_title ) ); ?>" /></span></p>

								<?php

								wp_nonce_field(
									$this->nonce_action( 'add', 'group' ),
									$this->nonce_action( 'add', 'group' )
								);

								?>

								<input type="hidden" name="action" value="add">
							</form>

						</div>
					</div>
				</div>

				<!-- View -->
				<div id="col-right">
					<div class="col-wrap">

						<h2 class="screen-reader-text"><?php /* translators: %s is the plural title of this list. */ echo esc_html( sprintf( __( '%s list', 'affiliate-wp' ), ucfirst( $this->plural_title ) ) ); ?></h2>

						<div class="tablenav top">

							<div class="alignright">
								<div class="tablenav-pages <?php echo esc_attr( $this->is_one_paged() ? 'one-page' : '' ); ?>">
									<span class="displaying-num">
										<?php

										// Translators: %s is the number of groups.
										echo esc_html( sprintf( __( '%d items', 'affiliate-wp' ), $this->get_total_group_count() ) );

										?>
									</span>

									<?php $this->pagination(); ?>

								</div>
							</div>

							<br class="clear">
						</div>

						<table class="wp-list-table widefat fixed striped table-view-list tags groups">

							<thead>
								<tr>
									<th
										scope="col"
										id="name"
										class="manage-column column-name column-primary desc"
										style="width: 30%">

										<span><?php esc_html_e( 'Name', 'affiliate-wp' ); ?></span>
									</th>

									<!-- Meta fields column header (top). -->
									<?php foreach ( $this->meta_fields as $meta_key => $meta_field ) : ?>
										<?php

										if ( ! isset( $meta_field['column_header'] ) ) {
											continue; // No column_header view field.
										}

										if ( ! is_callable( $meta_field['column_header'] ) ) {
											throw new \Exception( "\$meta_field['column_header'] must be a callable public function." );
										}

										// Output the field header from the function.
										echo filter_var( $meta_field['column_header']( 'top' ), FILTER_UNSAFE_RAW );

										?>
									<?php endforeach; ?>

									<?php if ( $this->has_connector() ) : ?>
										<th scope="col"class="column-posts manage-column count num" style="width: 10%">
											<?php esc_html_e( 'Count', 'affiliate-wp' ); ?>
										</th>
									<?php endif; ?>
								</tr>
							</thead>

							<!-- List -->
							<tbody id="the-list" data-wp-lists="list:tag">

								<?php foreach ( $groups as $group ) : ?>

									<!-- Name -->
									<tr id="group-<?php echo absint( $group->get_id() ); ?>" class="level-0 name group-row <?php echo esc_attr( $this->row_classes( $group ) ); ?>">
										<td
											class="name column-name has-row-actions column-primary"
											data-colname="<?php esc_attr_e( 'Name', 'affiliate-wp' ); ?>">

											<strong>
												<a
														class="row-title"
														href="<?php echo esc_url( $this->get_group_edit_url( $group->group_id ) ); ?>"
														aria-label="<?php echo esc_html( $group->get_title() ); ?> (<?php esc_html_e( 'Edit', 'affiliate-wp' ); ?>)">

													<?php echo esc_html( wp_trim_words( $group->get_title(), 20 ) ); ?>
												</a>
											</strong>

											<?php

											/**
											 * Fire right after group title.
											 *
											 * @since 2.13.0
											 *
											 * @param string                    $group_type  Group type.
											 * @param \AffiliateWP\Groups\Group $group Group object.
											 */
											do_action( 'affwp_group_management_after_row_title', $this->group_type, $group );

											?>

											<br />

											<?php $this->row_actions( $group ); ?>
										</td>

										<!-- Meta field columns (values). -->
										<?php foreach ( $this->meta_fields as $meta_key => $meta_field ) : ?>
											<?php

											if ( ! isset( $meta_field['column_value'] ) ) {
												continue; // No column_value view field.
											}

											if ( ! is_callable( $meta_field['column_value'] ) ) {
												throw new \Exception( "\$meta_field['column_value'] must be a callable public function." );
											}

											// Output the field header from the function.
											echo filter_var( $meta_field['column_value']( $group ), FILTER_UNSAFE_RAW );

											?>
										<?php endforeach; ?>

										<?php if ( $this->has_connector() ) : ?>
											<td class="count column-posts" style="vertical-align: middle; text-align: center;" data-colname="<?php echo esc_attr_e( 'Count', 'affiliate-wp' ); ?>">
												<?php echo wp_kses( $this->get_group_items_count( $group ), array( 'a' => array( 'href' => true ) ) ); ?>
											</td>
										<?php endif; ?>
									</tr>

								<?php endforeach; // Groups. ?>
							</tbody>

							<tfoot>
								<tr>
									<th scope="col" class="manage-column column-name column-primary desc">
										<span><?php esc_html_e( 'Name', 'affiliate-wp' ); ?></span>
									</th>

									<!-- Meta field column header (bottom) -->
									<?php foreach ( $this->meta_fields as $meta_key => $meta_field ) : ?>
										<?php

										if ( ! isset( $meta_field['column_header'] ) ) {
											continue; // No column_header view field.
										}

										if ( ! is_callable( $meta_field['column_header'] ) ) {
											throw new \Exception( "\$meta_field['column_header'] must be a callable public function." );
										}

										// Output the field header from the function.
										echo filter_var( $meta_field['column_header']( 'bottom' ), FILTER_UNSAFE_RAW );

										?>
									<?php endforeach; ?>

									<?php if ( $this->has_connector() ) : ?>
										<th scope="col"class="manage-column count num column-posts">
											<?php esc_html_e( 'Count', 'affiliate-wp' ); ?>
										</th>
									<?php endif; ?>
								</tr>
							</tfoot>

						</table>

						<div class="tablenav bottom">

							<div class="alignright">
								<div class="tablenav-pages one-page">
									<span class="displaying-num">
										<?php

										// Translators: %s is the number of groups.
										echo esc_html( sprintf( __( '%d items', 'affiliate-wp' ), $this->get_total_group_count() ) );

										?>
									</span>
								</div>
							</div>

							<div class="alignleft">
								<?php /* translators: %1$s is the item you can delete, %2$s plural name of the items.  */ echo esc_html( sprintf( __( 'Deleting a %1$s does not delete the %2$s in that %1$s.', 'affiliate-wp' ), strtolower( $this->single_title ), strtolower( $this->item_plural ) ) ); ?>
							</div>

							<br class="clear">
						</div>
					</div>
				</div>
				<!-- /col-right -->
		</div>

		<?php
	}

	/**
	 * Register group as connectable.
	 *
	 * @since 2.12.0
	 *
	 * @return void If it's already registered.
	 *
	 * @throws \Exception If we cannot register groups as connectable with the connections API.
	 */
	private function register_group_connectable() {

		if ( affiliate_wp()->connections->is_registered_connectable( 'group' ) ) {
			return;
		}

		// Groups.
		$groups = affiliate_wp()->connections->register_connectable(
			array(
				'name'   => 'group',
				'table'  => affiliate_wp()->groups->table_name,
				'column' => affiliate_wp()->groups->primary_key,
			)
		);

		if ( true === $groups ) {
			return;
		}

		throw new \Exception( 'Unable to register group as a connectable.' );
	}

	/**
	 * Register the necessary group type.
	 *
	 * @since  2.12.0
	 *
	 * @throws \InvalidArgumentException If `self::$group_type` is not a valid string.
	 * @throws \Exception                If there were issues registering the group type.
	 *
	 * @return  bool If we succesfully register the group type, true (avoiding exceptions).
	 */
	private function register_group_type() {

		if ( ! $this->is_string_and_nonempty( $this->group_type ) ) {
			throw new \InvalidArgumentException( 'self::group_type must be a non-empty string. ' );
		}

		if ( sanitize_key( $this->group_type ) !== $this->group_type ) {
			throw new \InvalidArgumentException( 'self::group_type must be a string compatible with sanitize_key(). ' );
		}

		$register = affiliate_wp()->groups->register_group_type(
			$this->group_type,
			array(
				'title' => $this->page_title,
			)
		);

		if ( true === $register ) {
			return true; // We registered it.
		}

		throw new \Exception( $register->get_error_message() );
	}

	/**
	 * Save meta fields.
	 *
	 * @since 2.13.0
	 *
	 * @param int $group_id Group ID to update meta for.
	 *
	 * @throws \Exception If we cannot get the group to update.
	 * @throws \Exception If we cannot call the save function for a meta field.
	 */
	private function save_meta( int $group_id ) : bool {

		$group = affiliate_wp()->groups->get_group( $group_id );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {
			throw new \Exception( "Unable to save meta for group with ID 'P$group_id}'" );
		}

		// Save any meta fields.
		foreach ( $this->meta_fields as $meta_field ) {

			if ( ! isset( $meta_field['save'] ) ) {
				continue; // No save function.
			}

			if ( ! is_callable( $meta_field['save'] ) ) {
				throw new \Exception( "\$meta_field['save'] must be a callable public function." );
			}

			if ( true !== $meta_field['save']( $group ) ) {
				return false; // One of the meta fields didn't save (should have set it's own errors).
			}
		}

		return true; // They all saved.
	}

	/**
	 * Management scripts.
	 *
	 * @since  2.12.0
	 *
	 * @return void Only on our management page.
	 */
	public function scripts() {

		if ( ! $this->is_management_page() ) {
			return; // Don't do the below unless we're on our managment screen.
		}

		// Some management JS is in Alipne JS: https://alpinejs.dev/.
		wp_enqueue_script( 'alpinejs' );

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
			? ''
			: '.min';

		wp_enqueue_script(
			'affwp-group-management',
			AFFILIATEWP_PLUGIN_URL . "assets/js/admin-group-management{$min}.js",
			array( 'jquery' ),
			AFFILIATEWP_VERSION,
			true
		);

		wp_localize_script(
			'affwp-group-management',
			'affwpGroupManagment',
			array(
				'delete' => array(
					'selector' => "body.affiliates_page_affiliate-wp-{$this->menu_slug} .row-actions .editinline.delete.group",

					// Translators: %s is the single name of the grouping being managed here, e.g. "creative".
					'message'  => sprintf( __( "You are about to permanently delete this %s from your site. This action cannot be undone. 'Cancel' to stop, 'OK' to delete.", 'affiliate-wp' ), strtolower( $this->single_title ) ),
				),
			)
		);
	}

	/**
	 * Show any runtime success messages.
	 *
	 * @since  2.12.0
	 */
	private function successes() {

		foreach ( $this->successes as $message ) {
			?>

			<div class="notice notice-success">
				<p><?php echo wp_kses_post( $message ); ?></p>
			</div>

			<?php
		}
	}

	/**
	 * Force a certain view to load.
	 *
	 * @since  2.12.0
	 *
	 * @param string $view The view.
	 *
	 * @throws \InvalidArgumentException If you do not supply a valid view.
	 */
	protected function update_view( $view ) {

		if (
			! $this->is_string_and_nonempty( $view ) ||
			! $this->string_is_one_of( $view, array( 'main', 'edit' ) )
		) {
			throw new \InvalidArgumentException( "\$view must be set to 'main' or 'edit'." );
		}

		$this->view = $view;
	}

	/**
	 * Validate class properties.
	 *
	 * @since  2.12.0
	 *
	 * @throws \InvalidArgumentException If there are any issues with the class properties.
	 */
	private function validate_properties() {

		if ( ! is_string( $this->connector_id ) ) {
			throw new \InvalidArgumentException( 'self::connector_id must be a string. ' );
		}

		if ( ! $this->is_string_and_nonempty( $this->page_title ) ) {
			throw new \InvalidArgumentException( 'self::page_title must be a non-empty string. ' );
		}

		if ( ! $this->is_string_and_nonempty( $this->menu_title ) ) {
			throw new \InvalidArgumentException( 'self::menu_title must be a non-empty string. ' );
		}

		if ( ! $this->is_string_and_nonempty( $this->capability ) ) {
			throw new \InvalidArgumentException( 'self::capability must be a non-empty string. ' );
		}

		if ( ! $this->is_string_and_nonempty( $this->menu_slug ) ) {
			throw new \InvalidArgumentException( 'self::menu_slug must be a non-empty string. ' );
		}

		$this->menu_slug = sanitize_key( $this->menu_slug );

		if ( ! $this->is_string_and_nonempty( $this->parent ) ) {
			throw new \InvalidArgumentException( 'self::parent must be a non-empty string. ' );
		}

		if ( ! $this->is_numeric_and_at_least_zero( $this->position ) ) {
			throw new \InvalidArgumentException( 'self::position needs to be zero or greater. ' );
		}

		if ( ! $this->is_string_and_nonempty( $this->single_title ) ) {
			throw new \InvalidArgumentException( 'self::single_title must be a non-empty string. ' );
		}

		if ( ! $this->is_string_and_nonempty( $this->plural_title ) ) {
			throw new \InvalidArgumentException( 'self::plural_title must be a non-empty string. ' );
		}

		if ( ! $this->is_string_and_nonempty( $this->item_single ) ) {
			throw new \InvalidArgumentException( 'self::item_single must be a non-empty string. ' );
		}

		if ( ! $this->is_string_and_nonempty( $this->item_plural ) ) {
			throw new \InvalidArgumentException( 'self::item_plural must be a non-empty string. ' );
		}

		if ( ! is_array( $this->meta_fields ) ) {
			throw new \InvalidArgumentException( 'self::meta_fields must be an array. ' );
		}
	}

	/**
	 * Description for the name field.
	 *
	 * @since 2.14.0
	 *
	 * @return string
	 */
	private function get_name_description() : string {
		return __( 'The name is how it appears on your site.', 'affiliate-wp' );
	}
}
