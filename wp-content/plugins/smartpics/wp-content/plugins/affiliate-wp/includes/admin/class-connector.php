<?php
/**
 * UI Connector.
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Groups
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.12.0
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 *
 * phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Empty lines okay for formatting here.
 * phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- Empty lines are okay.
 * phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket, PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Allow surrounding code w/out line breaks.
 * phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- Alignment OK.
 */

namespace AffiliateWP\Admin;

affwp_require_util_traits(
	'nonce',
	'data',
	'select2',
	'hooks',
	'db'
);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UI Connector.
 *
 * All the UI elements and functionality needed to connect items
 * (like creatives) to other items (like affiliates and groups).
 *
 * Begin by extending this class and setting connectable_args like so:
 *
 *     protected array $connectable_args = array(
 *       'creative' => array(
 *         'column_before' => 'shortcode',
 *       ),
 *       'group'    => array(
 *         'group_type' => 'creative-category',
 *       ),
 *     );
 *
 * In this example you can see this connects a creative with a group. Groups require
 * a group_type, unlike other items.
 *
 * You will then need to set some of the arguments in the construct, like lang, form_tags, etc:
 *
 *     $this->update_connectable_args( 'creative', array(
 *       'lang' => array(
 *         'plural' => __( 'Creatives', 'affiliate-wp' ),
 *         'single' => __( 'Creative', 'affiliate-wp' ),
 *       ),
 *       'form_tags' => $this->get_form_tags(
 *         array(
 *           'form'       => 'tr',
 *           'form_class' => 'form-row',
 *           'row_tag'    => 'div',
 *           'row_class'  => '',
 *         )
 *       ),
 *     ) );
 *
 * Here we want to translate items like lang, etc. Also form tags are added by merging the default tags
 * with the desired tags. If you don't want to, you don't have to set form_tags here, and
 * just use the default ones.
 *
 * This isn't guaranteed to work and may require adding missing filters that
 * other items may not have. Right now this supports:
 *
 * - affiliate
 * - creative
 * - group
 *
 * Note, this will not add any UI elements to the group UI (other than flagging to it to add a count column).
 *
 * What is a "Connectable"? What is an "Item"?
 *
 * It is important, also, to understand what an "item" and a "connectable" is. These terms
 * are used here interchangeably, but basically they are any of the items above (e.g. creative).
 * What makes the "connectable" is that they are being used by this connector class to connect
 * the two items, e.g. a creative and an affiliate.
 *
 * So you might set up two items (affiliate and a creative) to be "connectable".
 * So, when you connect an affiliate to e.g. 3 creatives you would say that we are connecting
 * the item (creative) to e.g. 3 items (affiliates). The creative and affiliate types are
 * "connectable" while the many items are what are actually connected via the
 * Connections API.
 *
 * @todo Dropdowns (selector) are still not alpha-numeric.
 *
 * @since 2.12.0
 * @since 2.15.0 Re-factored to allow item-to-item instead of just item-to-group.
 */
abstract class Connector {

	use \AffiliateWP\Utils\Data;
	use \AffiliateWP\Utils\Nonce;
	use \AffiliateWP\Utils\Select2;
	use \AffiliateWP\Utils\Hooks;
	use \AffiliateWP\Utils\DB;

	/**
	 * ID of the connector.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Connectable(s) arguments.
	 *
	 * This argument tells the Connector API what two items
	 * are being connected and arguments about them.
	 *
	 * E.g.
	 *
	 *    'creative' => array(
	 *      'column_before' => 'shortcode',
	 *      'lang' => array(
	 *        'plural' => __( 'Creatives', 'affiliate-wp' ),
	 *        'single' => __( 'Creative', 'affiliate-wp' ),
	 *      )
	 *    ),
	 *    'group'    => array(
	 *      'group_type' => 'creative-category',
	 *      'lang' => array(
	 *        'plural' => __( 'Categories', 'affiliate-wp' ),
	 *        'single' => __( 'Category', 'affiliate-wp' ),
	 *      )
	 *    ),
	 *
	 * @since 2.15.0 Added connectable arguments for connecting items-to-items
	 *                  instead of just item-to-groups.
	 *
	 * @var array
	 */
	protected array $connectable_args = [];

	/**
	 * Capability for UI pages.
	 *
	 * This capability is required for data changes to be made.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $capability = 'administrator';

	/**
	 * Selector type.
	 *
	 * Can be multiple or single. For multi you can connect an item with multiple
	 * items. With single you can only connect one item to another item.
	 *
	 * @since 2.13.0
	 *
	 * @var string
	 */
	protected $selector_type = 'multiple';

	/**
	 * Cache for storing the selected items on a new item.
	 *
	 * When we create a new one of the items in `$connectable_args`
	 * we store the selected items here for a later hook.
	 *
	 * @since 2.15.0
	 *
	 * @var array
	 */
	private array $selected_opposing_items_for_new_item = [];

	/**
	 * Cache for storing the connectable for the new item.
	 *
	 * When we create a new one of the items in `$connectable_args`
	 * we store the connectable being added here for a later hook.
	 *
	 * @since 2.15.0
	 *
	 * @var string
	 */
	private string $connectable_for_new_item = '';

	/**
	 * Construct.
	 *
	 * @since 2.12.0
	 *
	 * @param string $id A unique ID for the connector.
	 */
	public function __construct( string $id ) {

		$this->id = $id;

		$this->validate_properties();
		$this->validate_connectable_args();
		$this->register_connectables();
		$this->hooks();

		if ( wp_doing_ajax() ) {
			$this->ajax_hooks();
		}
	}

	/**
	 * Validate the connectable arguments.
	 *
	 * @since 2.15.0
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException If you mess up the arguments.
	 */
	private function validate_connectable_args() : void {

		if ( count( $this->connectable_args ) <= 1 || count( $this->connectable_args ) > 2 ) {
			throw new \InvalidArgumentException( 'You can only have (and must have) only two connectables.' );
		}

		foreach ( $this->connectable_args as $connectable => $args ) {

			if ( ! $this->string_is_one_of( $connectable, $this->get_supported_connectables() ) ) {
				throw new \InvalidArgumentException( "{$connectable} is not a supported connectable." );
			}

			if ( ! $this->connectable_argument_has_required_lang_args( $args ) ) {
				throw new \InvalidArgumentException( "args[lang[single]], args[lang[plural]], args[lang[placeholder]] arguments are required for {$connectable}." );
			}

			$list_table_connectable = $this->is_connectable_list_table_type( $connectable );

			if ( ! is_null( $args[ $connectable ]['form_tags'] ?? null ) ) {

				// Assign default form_tag arguments.
				$this->connectable_args[ $connectable ]['form_tags'] = $this->get_form_tags(
					$this->connectable_args[ $connectable ]['form_tags'] ?? []
				);

				if ( $list_table_connectable && ! $this->connectable_args_have_form_tag_args( $args ) ) {
					throw new \InvalidArgumentException( "{$connectable} does not have the required args[form_tags] arguments." );
				}
			}

			if ( 'group' === $connectable && ! $this->group_connectable_args_have_group_type( $args ) ) {
				throw new \InvalidArgumentException( "{$connectable} requires a group_type argument." );
			}
		}
	}

	/**
	 * Does a group connectable argument also have a group type (required)?
	 *
	 * @since 2.15.0
	 *
	 * @param array $args Connectable argument.
	 *
	 * @return bool
	 */
	private function group_connectable_args_have_group_type( array $args ) : bool {

		return isset( $args['group_type'] )
			&& $this->is_string_and_nonempty( $args['group_type'] );
	}

	/**
	 * Does a connectable argument have the required language arguments?
	 *
	 * @since 2.15.0
	 *
	 * @param array $args Connectable arguments.
	 *
	 * @return bool
	 */
	private function connectable_argument_has_required_lang_args( array $args ) : bool {

		if ( ! isset( $args['lang'] ) ) {
			return false;
		}

		if ( ! is_array( $args['lang'] ) ) {
			return false;
		}

		foreach ( $args['lang'] as $key => $value ) {

			if ( ! is_string( $value ) ) {
				return false; // The value must be a string.
			}
		}

		$lang_keys = array_keys( $args['lang'] );

		return in_array(
			'single',
			$lang_keys,
			true
		)
		&& in_array(
			'plural',
			$lang_keys,
			true
		)
		&& in_array(
			'placeholder',
			$lang_keys,
			true
		)
		&& in_array(
			'none',
			$lang_keys,
			true
		);
	}

	/**
	 * Does connectable arguments have all the required form tag arguments?
	 *
	 * @since 2.15.0
	 *
	 * @param array $args Connectable arguments.
	 *
	 * @return bool
	 */
	private function connectable_args_have_form_tag_args( array $args ) : bool {

		if ( ! isset( $args['form_tags'] ) ) {
			return false; // You don't have tags.
		}

		if ( ! is_array( $args['form_tags'] ) ) {
			return false; // Tags must be an array.
		}

		foreach ( $args['form_tags'] as $index => $tag ) {

			if ( ! is_string( $tag ) ) {
				return false; // All tags must be a string.
			}
		}

		return array_keys( $args['form_tags'] ) === array_keys( $this->get_form_tags() );
	}

	/**
	 * Get supported connectables for use in this connector API.
	 *
	 * @since 2.15.0
	 *
	 * @return array
	 */
	public function get_supported_connectables() : array {

		return [
			'creative',
			'group',
			'affiliate',
		];
	}

	/**
	 * Is a connectable a list table type connectable?
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable, e.g. creative, affiliate, group, etc.
	 *
	 * @return bool
	 */
	public function is_connectable_list_table_type( string $connectable ) : bool {
		return in_array( $connectable, $this->get_list_table_connectables(), true );
	}

	/**
	 * Connectables that have list tables.
	 *
	 * @since 2.15.0
	 *
	 * @return array Items (connectables) that have list tables in the admin UI.
	 *               Note, `groups` is not in the list as it does not and uses an entirely
	 *               different UI.
	 */
	public function get_list_table_connectables() : array {

		return [
			'affiliate',
			'creative',
		];
	}

	/**
	 * AJAX hooks.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Re-factored to use new item-to-item hook names.
	 */
	private function ajax_hooks() : void {

		foreach ( $this->get_supported_connectables() as $connectable ) {

			add_action( $this->filter_hook_name( "wp_ajax_{$this->get_select2_selector_ajax_action( $connectable )}" ), [ $this, $this->require_dynamic_method( "selector_ajax_response_for_{$connectable}" ) ] );
			add_action( $this->filter_hook_name( "wp_ajax_{$this->get_selector_filter_select2_ajax_action( $connectable )}" ), [ $this, $this->require_dynamic_method( "filter_ajax_response_for_{$connectable}" ) ] );
		}
	}

	/* phpcs:ignore -- This is a dynamic function for discovering the connectable. See filter_ajax_response_for_connectable() for more. */
	public function filter_ajax_response_for_affiliate() {
		$this->filter_ajax_response_for_connectable( 'affiliate' );
	}

	/* phpcs:ignore -- This is a dynamic function for discovering the connectable. See filter_ajax_response_for_connectable() for more. */
	public function filter_ajax_response_for_creative() {
		$this->filter_ajax_response_for_connectable( 'creative' );
	}

	/* phpcs:ignore -- This is a dynamic function for discovering the connectable. See filter_ajax_response_for_connectable() for more. */
	public function filter_ajax_response_for_group() {
		$this->filter_ajax_response_for_connectable( 'group' );
	}

	/**
	 * Get the Select2 AJAX value for page.
	 *
	 * This value is passed over GET when Select2 performs an AJAX call.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	private function get_select2_page() : int {

		$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );

		if ( ! is_numeric( $page ) ) {
			return 1;
		}

		return absint( $page );
	}

	/**
	 * Select2 AJAX Response for filtering.
	 *
	 * @param string $item_connectable The item connectable.
	 *
	 * @since 2.13.2
	 */
	public function filter_ajax_response_for_connectable( string $item_connectable ) : void {

		if ( ! is_admin() ) {
			exit; // We only do this in the admin.
		}

		if ( ! current_user_can( $this->capability ) ) {
			exit; // We only allow users with right caps to do this.
		}

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		if ( ! wp_verify_nonce( $_GET['nonce'] ?? '', $this->nonce_action( 'filter', 'items' ) ) ) {
			exit;
		}

		$per_page = $this->get_select2_per_page();
		$page     = $this->get_select2_page();
		$search   = trim( $this->get_select2_ajax_search_term() );
		$offset   = $this->calc_offset( $per_page, $page );

		$opposing_items = $this->get_select2_opposing_item_objects(
			$offset,
			$per_page,
			$search,
			$opposing_connectable
		);

		$more = empty( $opposing_items ) || count( $opposing_items ) < $per_page
			? false
			: true;

		wp_send_json(

			[
				'pagination' => [
					'more' => $more,
				],

				// Results to show on the frontend (paginated).
				'results' => array_merge(
					$this->add_none_and_all_options_to_first_paged_select2_ajax_response(
						$page,
						$search,
						$opposing_connectable
					),
					array_values( $opposing_items )
				),
			]
		);
	}

	/**
	 * Add None and All Items options to the Select2 AJAX response (for dropdown).
	 *
	 * @since 2.15.0
	 *
	 * @param int    $page        The current page.
	 * @param string $search      The search, if any.
	 * @param string $connectable The (item) connectable.
	 */
	private function add_none_and_all_options_to_first_paged_select2_ajax_response(
		int $page = 1,
		string $search = '',
		string $connectable = ''
	) : array {

		// Show when there is no search and only on the first page.
		return ( empty( $search ) && 1 === $page )

			// Default options on page 1...
			? [
				$this->esc_select_2_json_item( [
					'id'   => 0,
					'text' => $this->get_all_items_filter_option_text( $connectable ),
				] ),
				$this->esc_select_2_json_item( [
					'id'   => $this->get_none_option_name(),
					'text' => $this->get_no_items_filter_option_text( $connectable ),
				] ),
			]

			// There's a search or it's paged, don't show All and None options.
			: [];
	}

	/**
	 * The option name for when None is selected.
	 *
	 * @since 2.15.0
	 *
	 * @return string
	 */
	private function get_none_option_name() : string {
		return 'none';
	}

	/* phpcs:ignore -- This is a dynamic method for discovering the connectable. See selector_ajax_respose_for_connectable(). */
	public function selector_ajax_response_for_affiliate() {
		$this->selector_ajax_respose_for_connectable( 'affiliate' );
	}

	/* phpcs:ignore -- This is a dynamic method for discovering the connectable. See selector_ajax_respose_for_connectable(). */
	public function selector_ajax_response_for_creative() {
		$this->selector_ajax_respose_for_connectable( 'creative' );
	}

	/* phpcs:ignore -- This is a dynamic method for discovering the connectable. See selector_ajax_respose_for_connectable(). */
	public function selector_ajax_response_for_group() {
		$this->selector_ajax_respose_for_connectable( 'group' );
	}

	/**
	 * Add None option to the first page of Select2 AJAX response.
	 *
	 * @since 2.15.0
	 *
	 * @param array  $results          The results to add it to.
	 * @param int    $page             The current page.
	 * @param string $none_connectable The connectable that will display None in the drop-down.
	 *
	 * @return array
	 */
	private function add_none_to_first_page_of_select2_ajax_response( array $results, int $page, string $none_connectable ) : array {

		return ( 'single' === $this->selector_type )

			// Add none to page 1 of single type selectors...
			? array_merge(

				( 1 === $page )

					// Add none option on the first page only on single selectors.
					? [
						$this->esc_select_2_json_item(
							[
								'id'   => $this->get_none_option_name(),
								'text' => $this->get_connectable_none_text( $none_connectable ),
							]
						),
					]

					// Don't add none option to any other pages though.
					: [],

				$results
			)

			// Don't add none to multiple selector types.
			: $results;
	}

	/**
	 * Select2 Selector (New/Edit) AJAX Response.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Re-factored to work with item-to-item connections.
	 *
	 * @param string $opposing_connectable The connectable of the opposing connection.
	 *
	 * @return void Sends back JSON data.
	 */
	public function selector_ajax_respose_for_connectable( string $opposing_connectable ) : void {

		if ( ! is_admin() ) {
			exit; // We only do this in the admin.
		}

		if ( ! current_user_can( $this->capability ) ) {
			exit; // We only allow users with right caps to do this.
		}

		if ( ! wp_verify_nonce( $_GET['nonce'] ?? '', $this->nonce_action( 'select', 'items' ) ) ) {
			exit;
		}

		$per_page = $this->get_select2_per_page();
		$page     = $this->get_select2_page();
		$offset   = $this->calc_offset( $per_page, $page );
		$search   = trim( $this->get_select2_ajax_search_term() );

		$opposing_items = $this->add_none_to_first_page_of_select2_ajax_response(
			$this->get_select2_opposing_item_objects(
				$offset,
				$per_page,
				$search,
				$opposing_connectable
			),
			$page,
			$opposing_connectable
		);

		$more = empty( $opposing_items ) || count( $opposing_items ) < $per_page
			? false
			: true;

		wp_send_json(
			[
				'pagination' => [
					'more' => $more,
				],
				'results'    => array_values( $opposing_items ),
			],
		);
	}

	/**
	 * Get items of the opposing connectable in Select2 JSON format.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Accepts `$offset` and `$per_page` options.
	 *
	 * @param int    $offset               Offset.
	 * @param int    $per_page             Per page.
	 * @param string $search               You can include a search term.
	 * @param string $opposing_connectable The opposing connectable.
	 *
	 * @return array
	 */
	private function get_select2_opposing_item_objects(
		int $offset = 0,
		int $per_page = -1,
		string $search = '',
		string $opposing_connectable = ''
	) : array {

		return array_map(

			// Format opposing items in Select2 JSON format.
			function ( $item ) use ( $opposing_connectable ) {

				$item_id = $this->get_id_of_connectable_item( $opposing_connectable, $item );

				// Convert each item to a Select2 JSON item.
				return $this->esc_select_2_json_item(
					[
						'id'   => $item_id,
						'text' => $this->get_title_of_connectable_item( $opposing_connectable, $item_id, 'opposing_select2_item_objects' ),
					]
				);
			},

			// Get the opposing items as Select2 JSON objects.
			$this->get_opposing_items_paginated(
				'title',
				$offset,
				$per_page,
				$search,
				$opposing_connectable
			)
		);
	}

	/**
	 * Add a column to the main list for showing groupings for each item.
	 *
	 * @since 2.12.0
	 * @since 2.15.0 Re-factored to work with item-to-item.
	 *
	 * @param array      $columns The columns from the list table.
	 * @param \WP_Screen $screen  The screen.
	 *
	 * @return array If our item's screen, the addition of the grouping column.
	 */
	public function add_list_table_columns( $columns, $screen ) : array {

		if ( ! is_a( $screen, '\WP_Screen' ) ) {
			return $columns;
		}

		foreach ( $this->connectable_args as $connectable => $args ) {

			if ( "affiliates_page_affiliate-wp-{$connectable}s" !== $screen->base ) {
				continue;
			}

			$opposing_connectable = $this->get_opposing_connectable( $connectable );

			$title = $this->get_list_table_column_title( $opposing_connectable );

			$connector_column_key = "{$this->get_connector_id()}_{$connectable}";

			if (
				isset( $args['column_before'] ) &&
				$this->is_string_and_nonempty( $args['column_before'] )
			) {

				foreach ( $columns as $column_key => $column ) {

					if ( $column_key !== $args['column_before'] ) {
						continue;
					}

					$columns = $this->array_insert_before(
						$connector_column_key,
						$title,
						$columns,
						$column_key
					);
				}
			}

			if ( ! array_key_exists( 'column_before', $args ) ) {

				$columns[ $connector_column_key ] = $title;

				continue;
			}

			if ( null === $args['column_before'] ) {
				continue;
			}
		}

		return $columns;
	}

	/**
	 * Get the title for the column for the connectable.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_list_table_column_title( string $connectable ) : string {

		return ucfirst(
			'multiple' === $this->selector_type
				? $this->connectable_args[ $connectable ]['lang']['plural'] ?? ''
				: $this->connectable_args[ $connectable ]['lang']['single'] ?? ''
		);
	}

	/**
	 * Insert a value with a specific array key before another array key in an array.
	 *
	 * @since 2.15.0
	 *
	 * @param string $new_array_key    The new array key.
	 * @param mixed  $new_value        The new value that is set for the new array key.
	 * @param array  $array            The array to add it to.
	 * @param string $before_array_key The array key to add it before.
	 *
	 * @return array
	 */
	private function array_insert_before(
		string $new_array_key,
		$new_value,
		array $array,
		string $before_array_key
	) : array {

		$reordered = [];

		foreach ( $array as $array_key => $old_value ) {

			if ( $array_key === $before_array_key ) {

				$reordered[ $new_array_key ] = $new_value;

				$reordered[ $array_key ] = $old_value;

				continue;
			}

			$reordered[ $array_key ] = $old_value;
		}

		return $reordered;
	}

	/**
	 * Hooks
	 *
	 * @since  2.12.0
	 * @since 2.15.0 Uses all new hooks (also dynamic) and methods.
	 */
	protected function hooks() {

		$displaying_list_table_page = $this->is_current_connectable_list_table_page();

		// Load our scripts and styles for select2, etc.
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );

		if ( $displaying_list_table_page ) {

			// Add column to items view to show the categories (for both connectables).
			add_filter( 'affwp_list_table_columns', [ $this, 'add_list_table_columns' ], 10, 2 );
		}

		foreach ( $this->connectable_args as $connectable => $args ) {

			if ( ! $this->is_supported_connectable( $connectable ) ) {
				continue;
			}

			if ( 'group' === $connectable ) {

				// Signal to management (AffiliateWP\Admin\Groups\Management) that we have a connector established for this group connectable.
				add_filter( "affwp_admin_groups_management_{$this->get_opposing_connectable( $connectable )}_has_connector", '__return_true' );
			}

			add_action(
				$this->filter_hook_name( "affwp_delete_{$connectable}" ),
				[ $this, $this->require_dynamic_method( "disconnect_{$connectable}" ) ]
			);

			add_action(
				$this->filter_hook_name( "affwp_delete_{$connectable}" ),
				[ $this, $this->require_dynamic_method( "disconnect_{$connectable}" ) ]
			);

			add_action(
				$this->filter_hook_name( "affwp_{$connectable}_deleted" ),
				[ $this, $this->require_dynamic_method( "disconnect_{$connectable}" ) ]
			);

			add_action(
				$this->filter_hook_name( "affwp_edit_{$connectable}_bottom" ),
				[ $this, 'display_selector' ],
				$this->filter_priority( 10 ), 1 // phpcs:ignore -- Priority.
			);

			add_action(
				$this->filter_hook_name( "affwp_new_{$connectable}_bottom" ),
				[ $this, 'display_selector' ],
				$this->filter_priority( 10 ), 1 // phpcs:ignore -- Priority.
			);

			add_action(
				$this->filter_hook_name( "affwp_insert_{$connectable}" ),
				[ $this, 'connect_new_item' ]
			);

			add_action(
				$this->filter_hook_name( "affwp_add_{$connectable}" ),
				[ $this, 'connect_updated_item' ],
				1, 1 // phpcs:ignore -- Priority.
			);

			add_action(
				$this->filter_hook_name( "affwp_update_{$connectable}" ),
				[ $this, 'connect_updated_item' ],
				1, 1 // phpcs:ignore -- Priority.
			);

			if ( ! $this->is_connectable_list_table_type( $connectable ) || ! $displaying_list_table_page ) {
				continue; // Everything below is for list table pages only.
			}

			add_filter(
				$this->filter_hook_name( "affwp_{$connectable}_table_get_{$connectable}s" ),
				[ $this, $this->require_dynamic_method( "display_filter_list_table_for_{$connectable}" ) ]
			);

			add_filter(
				$this->filter_hook_name( "affwp_{$connectable}_table_{$this->get_connector_id()}_{$connectable}" ),
				[ $this, 'column_value' ],
				10, 2 // phpcs:ignore -- Priority.
			);

			add_action(
				$this->filter_hook_name( "affwp_affiliates_page_affiliate-wp-{$connectable}s_extra_tablenav_after" ),
				[ $this, $this->require_dynamic_method( "display_filter_selector_for_{$connectable}" ) ]
			);

			add_action(
				$this->filter_hook_name( "affwp_{$connectable}_admin_page_actions" ),
				[ $this, "add_manage_{$connectable}_group_button" ]
			);
		}
	}

	/** phpcs:ignore -- This is just a placeholder to discover the connectable. */
	public function add_manage_affiliate_group_button() {
		$this->add_manage_group_button( 'affiliate' );
	}

	/** phpcs:ignore -- This is just a placeholder to discover the connectable. */
	public function add_manage_creative_group_button() {
		$this->add_manage_group_button( 'creative' );
	}

	/**
	 * Add a management link button.
	 *
	 * @param string $connectable The connectable source.
	 *
	 * @since 2.14.0
	 * @since 2.15.0 Added $connectable parameter.
	 */
	public function add_manage_group_button( string $connectable ) : void {

		$opposing_connectable = $this->get_opposing_connectable( $connectable );

		if ( 'group' === $connectable ) {
			return; // We'd never show this on the groups UI.
		}

		if ( ! $this->show_manage_group_button( $opposing_connectable ) ) {
			return;
		}

		$url = '';

		if ( 'affiliate' === $opposing_connectable ) {
			$url = admin_url( 'admin.php?page=affiliate-wp-affiliates' );
		}

		if ( 'group' === $opposing_connectable && 'affiliate-group' === $this->get_connectable_group_type( $opposing_connectable ) ) {
			$url = admin_url( 'admin.php?page=affiliate-wp-affiliate-groups' );
		}

		if ( 'group' === $opposing_connectable && 'creative-category' === $this->get_connectable_group_type( $opposing_connectable ) ) {
			$url = admin_url( 'admin.php?page=affiliate-wp-creatives-categories' );
		}

		if ( empty( $url ) ) {
			return;
		}

		$text = sprintf(
			'%1$s %2$s',
			__( 'Manage', 'affiliate-wp' ),
			ucfirst( $this->get_connectable_lang( 'plural', $opposing_connectable ) )
		);

		?>

		<a href="<?php echo esc_url( $url ); ?>" class="page-title-action"><?php echo esc_html( $text ); ?></a>

		<?php
	}

	/**
	 * Should we show the manage group button?
	 *
	 * @since 2.15.0
	 *
	 * @param string $opposing_connectable The opposing connectable.
	 *
	 * @return bool
	 */
	protected function show_manage_group_button( string $opposing_connectable ) : bool {

		$item_connectable = $this->get_opposing_connectable( $opposing_connectable );

		if ( 'affiliate' === $opposing_connectable ) {
			return false; // Never manage affiliates.
		}

		if (
			'creative' === $item_connectable &&
			'group' === $opposing_connectable &&
			'affiliate-group' === $this->get_connectable_group_type( $opposing_connectable )
		) {

			// Never manage affiliate groups from creatives.
			return false;
		}

		return true;
	}

	/**
	 * Require dynamic method.
	 *
	 * For some hooks, the only way to discover the connectable is by name,
	 * e.g. the filter `affwp_delete_creative` does not give context as to what
	 * the item is for, so we create dynamic methods on this class for items
	 * that don't give that context.
	 *
	 * @since 2.15.0
	 *
	 * @param string $method The method.
	 *
	 * @return string The method, otherwise throws errors if they are not set.
	 *
	 * @throws \Exception If the method is not in this class.
	 */
	private function require_dynamic_method( string $method ) : string {

		if ( method_exists( $this, $method ) ) {
			return $method;
		}

		throw new \Exception( "Connector requires that a public method '{$method}' exists." );
	}

	/**
	 * Get a connectable (creative, affiliate, group, etc) primary key.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string The primary key of that connectable.
	 *
	 * @throws \InvalidArgumentException If `$connectable` is not a valid non-empty string.
	 */
	protected function get_connectable_primary_key( string $connectable ) : string {

		if ( empty( $connectable ) ) {
			throw new \InvalidArgumentException( '$connectable must be a non-empty string.' );
		}

		$api = affiliate_wp()->connections->get_connectable_api( $connectable );

		return $api->primary_key;
	}

	/**
	 * Discover connectable by the item (object).
	 *
	 * @since 2.15.0
	 *
	 * @param mixed $item The item object (e.g. of a creative, affiliate, group, etc).
	 *
	 * @return string The associated connectable item name.
	 *                Empty string if no connectable.
	 */
	protected function get_connectable_by_item( $item ) : string {

		foreach ( $this->get_supported_connectables() as $connectable ) {

			$primary_key = $this->get_connectable_primary_key( $connectable );

			if ( isset( $item->$primary_key ) ) {
				return $connectable;
			}
		}

		return '';
	}

	/**
	 * Get the opposing connectable.
	 *
	 * This connector class (specifically the connectable args) consist
	 * of two items (or connectables), e.g. a creative and an affiliate,
	 * for example. When operations are ran we are often supplied
	 * with the initial item e.g. a creative. Based on how this connector
	 * class works, you can use this method to get the opposing connectable
	 * (affiliate in this example) to work with the two connectable items.
	 *
	 * @since 2.15.0
	 *
	 * @param string $of_connectable The initial connectable.
	 *
	 * @return string The opposing connectable.
	 */
	protected function get_opposing_connectable( string $of_connectable ) : string {

		foreach ( $this->connectable_args as $current_connectable => $args ) {

			if ( $current_connectable === $of_connectable ) {
				continue;
			}

			return $current_connectable;
		}

		return ''; // @todo maybe throw an error?
	}

	/**
	 * Get the title of an item (by connectable).
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable, e.g. `creative`.
	 * @param int    $item_id     The item ID.
	 * @param string $context     Context for the title.
	 *
	 * @return string The title of the item.
	 *                Empty string if un-discoverable.
	 */
	protected function get_title_of_connectable_item( string $connectable, int $item_id, string $context ) : string {

		if ( 'affiliate' === $connectable ) {

			$email = affwp_get_affiliate_email( $item_id );

			$name = affiliate_wp()->affiliates->get_affiliate_name( $item_id );

			return "{$name}&nbsp;&mdash;&nbsp;{$email}";
		}

		if ( 'creative' === $connectable ) {

			$creative = affwp_get_creative( $item_id );

			return $creative->name;
		}

		if ( 'group' === $connectable ) {
			return affiliate_wp()->groups->get_group_title( $item_id );
		}

		return '';
	}

	/**
	 * Column (in List Table) value.
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Re-factored to work with item-to-item.
	 *
	 * @param mixed $value The value.
	 * @param mixed $item  The item (must be object with ID property).
	 *                     We will discover the item and opposing connection via this object.
	 *
	 * @return string Content for the column in the list table.
	 *
	 * @throws \InvalidArgumentException If `$item` is not an object.
	 *                                   If `$item` does not have a valid ID stored.
	 *                                   If `$item`'s stored ID is not a valid ID.
	 */
	public function column_value( $value, $item ) : string {

		if ( ! is_object( $item ) ) {
			throw new \InvalidArgumentException( '$item needs to be an object.' );
		}

		$item_connectable = $this->get_connectable_by_item( $item );

		if ( empty( $item_connectable ) ) {
			return $value;
		}

		if ( ! $this->is_connectable_list_table_type( $item_connectable ) ) {
			return $value; // Only support this on items that support list tables.
		}

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		if ( empty( $opposing_connectable ) ) {
			return $value;
		}

		$property = $this->get_connectable_primary_key( $item_connectable );

		if ( ! isset( $item->$property ) ) {
			throw new \InvalidArgumentException( "\$item must contain a readable property called '{$property}'." );
		}

		if ( ! $this->is_numeric_and_gt_zero( $item->$property ) ) {
			throw new \InvalidArgumentException( "\$item must have a valid ID stored in object property '{$property}'." );
		}

		$none = $this->get_column_value_none_text( $item_connectable );

		$connected = $this->get_connected(
			$opposing_connectable,
			$item_connectable,
			$item->$property
		);

		if ( ! is_array( $connected ) ) {
			return $none;
		}

		$sorted_titles = [];

		foreach ( $connected as $connected_id ) {

			if ( ! $this->is_numeric_and_gt_zero( $connected_id ) ) {
				continue;
			}

			$title = $this->get_title_of_connectable_item( $opposing_connectable, $connected_id, 'list_table_column_value' );

			$title = str_replace( ',', ' ', $title ); // Just in case a name has a comma in it.

			if ( ! $this->is_string_and_nonempty( $title ) ) {
				continue;
			}

			$sorted_titles[ $title ] = $this->get_column_value_filterable_title( $title, $item, $connected_id );
		}

		if ( empty( $sorted_titles ) ) {
			return $none;
		}

		ksort( $sorted_titles );

		return implode( ', ', $sorted_titles );
	}

	/**
	 * Column Value: Filterable title.
	 *
	 * @since 2.15.0
	 *
	 * @param string $title        The title of the item we would show.
	 * @param object $item         The object of the main item.
	 * @param int    $connected_id ID of the item connected to the main item.
	 *
	 * @return string Link for filtering by that connected item.
	 */
	protected function get_column_value_filterable_title( string $title, $item, int $connected_id ) : string {

		$item_connectable = $this->get_connectable_by_item( $item );

		$nonce_name  = $this->nonce_action( 'filter', 'items' );
		$nonce_value = wp_create_nonce( $nonce_name );

		$url = "?page=affiliate-wp-{$item_connectable}s&{$this->get_filter_list_table_name()}={$connected_id}&{$nonce_name}={$nonce_value}";

		return "<a href='{$url}'>{$title}</a>";
	}

	/**
	 * None text for column value.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_column_value_none_text( string $connectable ) : string {
		return $this->get_connectable_none_text( $connectable );
	}

	/**
	 * When a new item (affiliate, creative, etc) is added.
	 *
	 * When the new item form is submitted, we cache the data submitted
	 * and, later, another hook (when adding to the DB) we use that cached
	 * data to connect the item to it's opposing items.
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Refactored to work item-to-item instead of item-to-group.
	 *
	 * @param int $item_id Item ID.
	 *
	 * @throws \InvalidArgumentException If `$item_id` is not a valid ID.
	 */
	public function connect_new_item( $item_id ) : void {

		if ( ! $this->is_numeric_and_gt_zero( $item_id ) ) {
			throw new \InvalidArgumentException( '$item_id must be a positive numeric value.' );
		}

		if ( ! is_array( $this->selected_opposing_items_for_new_item ) ) {
			return; // No data to save, make no changes.
		}

		if ( ! $this->is_string_and_nonempty( $this->connectable_for_new_item ) ) {
			return; // Needs to be valid string.
		}

		$this->disconnect_item_from_items(
			$this->connectable_for_new_item,
			intval( $item_id ),
			$this->selected_opposing_items_for_new_item
		);

		$this->connect_item_to_items(
			$this->connectable_for_new_item,
			intval( $item_id ),
			$this->selected_opposing_items_for_new_item
		);
	}

	/**
	 * Are we currently viewing the add/edit page of a connectable.
	 *
	 * Note, groups are not yet supported.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return bool
	 */
	public function is_current_connectable_add_edit_page( string $connectable = '' ) : bool {

		if ( empty( $connectable ) ) {

			foreach ( $this->connectable_args as $connectable => $args ) {

				if ( $this->is_current_add_edit_page( $connectable ) ) {
					continue;
				}

				return true;
			}

			return false;
		}

		return $this->is_current_add_edit_page( $connectable );
	}

	/**
	 * Are we displaying the connectables add/edit page?
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return bool
	 */
	protected function is_current_add_edit_page( string $connectable ) : bool {

		return $this->get_connectable_page_parameter( $connectable ) === filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW ) &&
			(
				"add_{$connectable}" === filter_input( INPUT_GET, 'action', FILTER_UNSAFE_RAW ) ||
				"edit_{$connectable}" === filter_input( INPUT_GET, 'action', FILTER_UNSAFE_RAW )
			);
	}

	/**
	 * Discover the connectable via the POST data.
	 *
	 * @since 2.15.0
	 *
	 * @param array $data The POST data from when e.g. an item (creative, affiliate, etc)
	 *                    is updated, added, etc.
	 *
	 * @return string The connectable associated with the saved POST data.
	 *                Empty string if none discoverable.
	 */
	protected function get_connectable_from_post_data( array $data ) {

		foreach ( $this->connectable_args as $connectable => $args ) {

			if ( $this->is_current_connectable_add_edit_page( $connectable ) ) {
				return $connectable;
			}

			$primary_key = $this->get_connectable_primary_key( $connectable );

			if (
				isset( $data[ $primary_key ] ) &&
				$this->is_numeric_and_gt_zero( $data[ $primary_key ] )
			) {
				return $connectable;
			}
		}

		return '';
	}

	/**
	 * Get the page parameter for a connectable.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string The name of the parameter for the admin page.
	 */
	protected function get_connectable_page_parameter( string $connectable ) : string {
		return "affiliate-wp-{$connectable}s";
	}

	/**
	 * Connected an updated (or new) item with the selected opposing items.
	 *
	 * When updating or adding a new item, we take the item's POST data,
	 * discover the selected opposing items, and connect the updated/new
	 * item with the desired selected opposing connectable items.
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param array $item_data The POST data for the item from update/add.
	 *
	 * @return array Item data.
	 */
	public function connect_updated_item( array $item_data ) : array {

		if ( ! $this->user_has_capability() ) {
			return $item_data;
		}

		if ( ! is_array( $item_data ) ) {
			return $item_data; // We expected an array, but fail gracefully (no changes).
		}

		$item_connectable = $this->get_connectable_from_post_data( $item_data );

		if ( ! $this->verify_nonce_action( 'update', 'item' ) ) {
			return $item_data; // Nonce expired.
		}

		check_admin_referer(
			$this->nonce_action( 'update', 'item' ),
			$this->nonce_action( 'update', 'item' )
		);

		if ( empty( $item_connectable ) ) {
			return $item_data;
		}

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		if ( empty( $opposing_connectable ) ) {
			return $item_data;
		}

		$primary_key = $this->get_connectable_primary_key( $item_connectable );

		$opposing_items_key = "{$this->get_connector_id( $item_connectable )}_items";

		if (
			! isset( $item_data[ $primary_key ] ) &&
			! isset( $item_data[ $opposing_items_key ] )
		) {

			// No items were selected for the new/updated item.
			return $item_data;
		}

		if (
			isset( $item_data[ $primary_key ] ) &&
			! isset( $item_data[ $opposing_items_key ] )
		) {

			$this->disconnect_item_from_items(
				$item_connectable,
				intval( $item_data[ $primary_key ] ),
				[]
			);

			return $item_data;
		}

		// New item.
		if ( ! isset( $item_data[ $primary_key ] ) ) {

			// Store what the connectable was temporarily.
			$this->connectable_for_new_item = $item_connectable;

			// Cache the data/opposing items for later (new item).
			$this->selected_opposing_items_for_new_item = $item_data[ $opposing_items_key ];

			// Stop here, another hook will update the data once we have an ID.
			return $item_data;
		}

		if ( ! $this->is_numeric_and_gt_zero( $item_data[ $primary_key ] ) ) {
			return $item_data; // Must be a valid ID to update the item, fail gracefully (no changes).
		}

		$this->disconnect_item_from_items(
			$item_connectable,
			$item_data[ $primary_key ],
			$item_data[ $opposing_items_key ],
		);

		$this->connect_item_to_items(
			$item_connectable,
			$item_data[ $primary_key ],
			$item_data[ $opposing_items_key ],
		);

		if ( isset( $item_data[ $opposing_items_key ] ) ) {

			// Clean out the other data.
			unset( $item_data[ $opposing_items_key ] );
		}

		return $item_data;
	}

	/**
	 * Connect opposing items with another item.
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $item_connectable  The item's connectable.
	 * @param int    $item_id           The item ID.
	 * @param array  $opposing_item_ids The ID's of the opposing items to connect to the main item.
	 *
	 * @throws \InvalidArgumentException If you do not supply valid parameters.
	 */
	private function connect_item_to_items( string $item_connectable, int $item_id, array $opposing_item_ids ) {

		if ( ! $this->user_has_capability() ) {
			return;
		}

		if ( ! $this->is_numeric_and_gt_zero( $item_id ) ) {
			throw new \InvalidArgumentException( '$item_id must be a positive numeric value.' );
		}

		if ( ! is_array( $opposing_item_ids ) ) {
			throw new \InvalidArgumentException( '$opposing_item_ids must be an array of positive numeric values.' );
		}

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		foreach ( $opposing_item_ids as $opposing_item_id ) {

			if ( ! $this->is_numeric_and_gt_zero( $opposing_item_id ) ) {
				continue;
			}

			affiliate_wp()->connections->connect(
				[
					$item_connectable     => intval( $item_id ),
					$opposing_connectable => intval( $opposing_item_id ),
				]
			);
		}
	}

	/* phpcs:ignore -- This is just a wrapper method for discovering the connectable, see disconnect_connectable_by_id() for more. */
	public function disconnect_creative( int $creative_id ) : void {
		$this->disconnect_connectable_by_id( 'creative', $creative_id );
	}

	/* phpcs:ignore -- This is just a wrapper method for discovering the connectable, see disconnect_connectable_by_id() for more. */
	public function disconnect_affiliate( int $affiliate_id ) : void {
		$this->disconnect_connectable_by_id( 'affiliate', $affiliate_id );
	}

	/* phpcs:ignore -- This is just a wrapper method for discovering the connectable, see disconnect_connectable_by_id() for more. */
	public function disconnect_group( int $group_id ) : void {
		$this->disconnect_connectable_by_id( 'group', $group_id );
	}

	/**
	 * Delete all connections to a deleted item.
	 *
	 * Yes, this is item/connectable agnostic. If you delete one of the supported
	 * connectables here, we will disconnect ANYTHING connected to it since it's
	 * now gone.
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $connectable The connectable of the item.
	 * @param int    $item_id     The ID of the item.
	 */
	protected function disconnect_connectable_by_id( string $connectable, int $item_id ) : void {

		if ( ! $this->user_has_capability() ) {
			return;
		}

		if ( ! $this->is_numeric_and_gt_zero( $item_id ) ) {
			return; // Fail gracefully, it's just a stray connection.
		}

		if ( ! $this->string_is_one_of( $connectable, $this->get_supported_connectables() ) ) {
			return; // Don't delete anything we don't officially support.
		}

		global $wpdb;

		$wpdb->delete(
			affiliate_wp()->connections->table_name,
			[
				$connectable => $item_id,
			],
			[
				$connectable => '%d',
			]
		);
	}

	/**
	 * Get connected items to an item.
	 *
	 * @since 2.15.0
	 *
	 * @param string $opposing_connectable The opposing item connectable (the items we retrieve).
	 * @param string $item_connectable     The connectable of the item we will get connections to.
	 * @param int    $item_id              The ID of the item the opposing items will be connected to.
	 *
	 * @return array ID's of the connected opposing items.
	 */
	protected function get_connected( string $opposing_connectable, string $item_connectable, int $item_id ) : array {

		$connected = affiliate_wp()->connections->get_connected(
			$opposing_connectable,
			$item_connectable,
			intval( $item_id )
		);

		// Groups are treated a bit differently because they must have a group type.
		return 'group' === $opposing_connectable
			? affiliate_wp()->groups->filter_groups_by_type(
				$connected,
				$this->get_connectable_group_type( $opposing_connectable )
			)
			: $connected;
	}

	/**
	 * Disconnect items from an item.
	 *
	 * This uses the selected opposing items to discover a delta of items
	 * currently connected in the DB and we disconnect those.
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $item_connectable         The connectable type for the item to disconnect items from.
	 * @param int    $item_id                  The ID of the item to disconnect opposing items from.
	 * @param array  $selected_opposing_items  The selected opposing items (we won't disconnect these).
	 *
	 * @throws \InvalidArgumentException If you do not supply valid parameters.
	 */
	protected function disconnect_item_from_items( string $item_connectable, int $item_id, array $selected_opposing_items ) {

		if ( ! $this->user_has_capability() ) {
			return;
		}

		if ( ! $this->is_numeric_and_gt_zero( $item_id ) ) {
			throw new \InvalidArgumentException( '$item_id must be a positive numeric value.' );
		}

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		foreach ( $this->get_connected(
			$opposing_connectable,
			$item_connectable,
			intval( $item_id )
		) as $opposing_item_id ) {

			if (
				in_array(
					intval( $opposing_item_id ),
					array_map(
						'intval',
						$selected_opposing_items
					),
					true
				)
			) {
				continue; // Keep this selected opposing item connected.
			}

			// Disconnect all other connected opposing items.
			affiliate_wp()->connections->disconnect(
				[
					$item_connectable     => intval( $item_id ),
					$opposing_connectable => intval( $opposing_item_id ),
				]
			);
		}
	}

	/**
	 * Get the name="" value for the selected opposing items from the filter dropdown.
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	private function get_filter_list_table_name() : string {
		return "filter-{$this->get_connector_id()}-top";
	}

	/* phpcs:ignore -- This is just a wrapper function for discovering the connectable, see display_filter_list_table_for_connectable() for more. */
	public function display_filter_list_table_for_affiliate( array $args ) : array {
		return $this->display_filter_list_table_for_connectable( 'affiliate', $args );
	}

	/* phpcs:ignore -- This is just a wrapper function for discovering the connectable, see display_filter_list_table_for_connectable() for more. */
	public function display_filter_list_table_for_creative( array $args ) : array {
		return $this->display_filter_list_table_for_connectable( 'creative', $args );
	}

	/**
	 * Display the dropdown for filtering the list table by opposing items.
	 *
	 * E.g. we might be showing creatives and we want to filter by creative category (group)
	 * of affiliate.
	 *
	 * @since 2.13.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $item_connectable The connectable of the items being shown in the list table.
	 * @param array  $args             Arguments (which we modify) that are used to show certain items.
	 *
	 * @return array Modified args to show the selected opposing item in the filter dropdown.
	 */
	protected function display_filter_list_table_for_connectable( string $item_connectable, array $args ) : array {

		if ( ! is_admin() ) {
			return $args;
		}

		if ( ! $this->is_current_connectable_list_table_page( $item_connectable ) ) {
			return $args;
		}

		if ( ! $this->verify_nonce_action( 'filter', 'items' ) ) {
			return $args;
		}

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		if ( $this->none_filter_item_is_selected() ) {

			return array_merge(
				$args,
				[
					'connected_to' => [
						'get_connectable'    => $item_connectable, // e.g. affiliate.
						'where_connectable'  => $opposing_connectable, // e.g. group.
						'where_group_type'   => $this->get_connectable_group_type( $opposing_connectable ), // Will be empty if not group opposing connection.
						'where_id'           => 0, // Zero means connected to no groups.
					],
				]
			);
		}

		$selected_item = $this->get_filters_selected_opposing_item();

		if ( ! $this->is_numeric_and_gt_zero( $selected_item ) ) {
			return $args;
		}

		return array_merge(
			$args,

			// Add arguments that will cause SQL to get items (e.g. affiliates) connected to (e.g. group).
			[
				'connected_to' => [
					'get_connectable'    => $item_connectable, // e.g. affiliate.
					'where_connectable'  => $opposing_connectable, // e.g. group.
					'where_group_type'   => $this->get_connectable_group_type( $opposing_connectable ), // Will be empty if not group opposing connection.
					'where_id'           => $selected_item,
				],
			],
		);
	}

	/**
	 * Get connected items (ids) connected to an item (by id).
	 *
	 * @since 2.13.0
	 * @since 2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param int    $item_id          The ID of the item (a creative, affiliate, group, etc).
	 * @param string $item_connectable The connectable associated with the ID of the item.
	 *
	 * @return array Items (ids) connected to the item (by id).
	 */
	protected function get_connected_item_ids_of( int $item_id, string $item_connectable ) : array {

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		return array_map(
			'intval',
			$this->get_connected(
				$opposing_connectable,
				$item_connectable,
				intval( $item_id )
			)
		);
	}

	/**
	 * Per page setting for Select2 AJAX.
	 *
	 * This usually correlates to how many items are sent back per AJAX call
	 * when Select2 dropdowns ask for items to populate in that dropdown.
	 *
	 * @since 2.15.0
	 *
	 * @return int The setting for how many items to show per-page in AJAX response.
	 */
	protected function get_select2_per_page() : int {
		return 20;
	}

	/**
	 * Get the selected item (for filtering).
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	private function get_filters_selected_opposing_item() : string {

		$get = filter_input( INPUT_GET, $this->get_filter_list_table_name(), FILTER_UNSAFE_RAW );

		return is_string( $get )
			? trim( $get )
			: '';
	}

	/**
	 * Was `none` selected in the filter dropdown?
	 *
	 * @since 2.13.0
	 *
	 * @return bool
	 */
	protected function none_filter_item_is_selected() : bool {
		return $this->get_none_option_name() === $this->get_filters_selected_opposing_item();
	}

	/**
	 * Get (opposing) items (ids) with connections (as opposed to with no connections).
	 *
	 * @since 2.13.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $item_connectable The connectable of the item.
	 *
	 * @return array ID's of items (opposing) with no connections.
	 */
	protected function get_items_with_opposing_connections( string $item_connectable ) : array {

		$items_with_opposing_connections = [];

		foreach (

			// All items...
			$this->get_connectable_items(
				$item_connectable,
				[
					'fields' => 'ids',
					'number' => -1,
				]
			) as $item_id
		) {

			$connected_opposing_items = $this->get_connected(
				$this->get_opposing_connectable( $item_connectable ),
				$item_connectable,
				$item_id
			);

			if ( empty( $connected_opposing_items ) ) {
				continue; // Item has connected items, don't include.
			}

			$items_with_opposing_connections[] = $item_id;
		}

		return $items_with_opposing_connections;
	}

	/**
	 * Get the count of items.
	 *
	 * @param string $connectable For this specific connectable.
	 * @param array  $args        Any arguments to override/include.
	 *
	 * @since 2.15.0
	 *
	 * @return int
	 */
	protected function get_count_of_connectable_items( $connectable, array $args = [] ) : int {

		return $this->get_connectable_items(
			$connectable,
			array_merge(
				[
					'fields' => 'ids',
					'number' => -1,
				],
				$args
			),
			true // The count.
		);
	}

	/**
	 * Get a list of opposing items (paginated).
	 *
	 * @since  2.12.0
	 * @since  2.13.0 Added `$sorted` option.
	 * @since  2.15.0 Added `$search` option and orderby is `title` instead of `alpha`.
	 *                   Also added `$offset` and `$number` options.
	 *                   Refactored to work for item-to-item not just item-to-group.
	 *
	 * @param string $orderby              Order by parameter, defaults to title.
	 * @param int    $offset               Offset.
	 * @param int    $number               The number of items to get.
	 * @param string $search               Search term, if any.
	 * @param string $opposing_connectable The connectable for the opposing items.
	 *
	 * @return array List of opposing items with offset and number taken into consideration.
	 */
	protected function get_opposing_items_paginated(
		string $orderby = 'title',
		int $offset = 0,
		int $number = 20,
		string $search = '',
		string $opposing_connectable = ''
	) {

		return $this->get_connectable_items(
			$opposing_connectable,
			array_merge(
				[
					'fields'  => 'objects',
					'orderby' => empty( trim( $orderby ) )
						? 'title'
						: $orderby,
					'offset'  => $offset,
					'number'  => $number,
				],
				empty( trim( $search ) )
					? []
					: [
						'search' => trim( $search ),
					]
			)
		);
	}

	/**
	 * The value for None in single type selectors.
	 *
	 * @param string $connectable The connectable.
	 *
	 * @since 2.13.3
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @return string Based on lang arguments of the connectable.
	 */
	private function get_connectable_none_text( string $connectable ) : string {
		return $this->connectable_args['lang'][ $this->get_none_option_name() ] ?? esc_html__( 'None', 'affiliate-wp' );
	}

	/**
	 * Get the group type of a connectable (only groups).
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string The group type for the connectable.
	 */
	public function get_connectable_group_type( string $connectable ) : string {
		return $this->connectable_args[ $connectable ]['group_type'] ?? '';
	}

	/**
	 * Get the specific language for a connectable.
	 *
	 * @since 2.15.0
	 *
	 * @param string $key         The key, e.g. `plural` or `single`.
	 * @param string $connectable The connectable.
	 *
	 * @return string The value for the requested lang argument.
	 */
	protected function get_connectable_lang( string $key, string $connectable ) : string {
		return $this->connectable_args[ $connectable ]['lang'][ $key ] ?? '';
	}

	/**
	 * Is a connectable a supported connectable of this connector.
	 *
	 * This means that support for that connectable (item) has been
	 * added system-wide for this connector to work.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable Connectable item, e.g. affiliate, creative, group, etc.
	 *
	 * @return bool
	 */
	private function is_supported_connectable( string $connectable ) : bool {
		return in_array( $connectable, $this->get_supported_connectables(), true );
	}

	/**
	 * Get items for a connectable.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable, e.g. `creative`, `affiliate` or `group`.
	 * @param array  $args        The arguments that would normally get passed to the DB class for the given connectable.
	 * @param bool   $count       Whether to just ask for the count.
	 *
	 * @return mixed The result of the correlating DB API call, e.g. `get_creatives( $args, $count )` or `get_affiliates( $args, $count )`, etc.
	 *
	 * @throws \Exception If we can't find the correlating DB API method to get items.
	 */
	protected function get_connectable_items( string $connectable = '', array $args = [], bool $count = false ) {

		$get_items = "get_{$connectable}s";

		$api = affiliate_wp()->connections->get_connectable_api( $connectable );

		if ( ! method_exists( $api, $get_items ) ) {
			throw new \Exception( "Cannot find API method {$get_items}( array ) for connectable: {$connectable}." );
		}

		return $api->$get_items(
			array_merge(
				$args,
				$this->get_orderby_for_connectable( $connectable ),
				'group' === $connectable
					? [
						'type' => $this->get_connectable_group_type( $connectable ),
					]
					: []
			),
			$count
		);
	}

	/**
	 * Get order/orderby args for connectable.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return array
	 *
	 * @throws \Exception If there isn't support for a given connectable.
	 */
	protected function get_orderby_for_connectable( string $connectable ) : array {

		if ( 'affiliate' === $connectable ) {

			return [
				'orderby' => 'name',
				'order'   => 'ASC',
			];
		}

		if ( 'group' === $connectable ) {

			return [
				'orderby' => 'title',
				'order'   => 'ASC',
			];
		}

		if ( 'creative' === $connectable ) {
			return [
				'orderby' => 'name',
				'order'   => 'ASC',
			];
		}

		throw new \Exception( "No orderby support for {$connectable}, please add it." );
	}

	/**
	 * Get an item object (by id).
	 *
	 * Uses the correlating DB classes `get_*` method to get an object, e.g.
	 * `get_creative( $item_id )` or `get_affiliate( $item_id )`.
	 *
	 * @since 2.15.0
	 *
	 * @param string $item_connectable The connectable for the item (creative, affiliate, etc).
	 * @param int    $item_id          The ID of the item.
	 *
	 * @return mixed Should result in an object for the given connectable and ID.
	 *
	 * @throws \Exception If we cannot find the `get_*()` method for the correlating DB API.
	 *                    As of May 31, 2023 all supported connectables have a correlating DB API/method.
	 */
	protected function get_connectable_item( string $item_connectable, int $item_id ) {

		$get_method = "get_{$item_connectable}";

		if ( 'affiliate' === $item_connectable ) {
			$get_method = 'get_object'; // The Affiliate_WP_DB_Affiliates class has no get_affiliate() method.
		}

		$api = affiliate_wp()->connections->get_connectable_api( $item_connectable );

		if ( ! method_exists( $api, $get_method ) ) {
			throw new \Exception( "Cannot find API method affiliate_wp()->{$get_method}( array ) for connectable: {$item_connectable}." );
		}

		return $api->$get_method( $item_id );
	}

	/**
	 * Does a connectable (creative, affiliate, etc) have items?
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return bool True if there are no items for the connectable, false otherwise.
	 *
	 * @throws \InvalidArgumentException If you pass an un-supported connectable, because
	 *                                   we cannot return true or false in the case it might
	 *                                   be a lie.
	 */
	protected function connectable_has_no_items( string $connectable ) : bool {

		if ( ! $this->is_supported_connectable( $connectable ) ) {
			throw new \InvalidArgumentException( "{$connectable} isn't a supported connectable." );
		}

		return 0 === $this->get_connectable_items(
			$connectable,
			[
				'number' => -1,
			],
			true
		);
	}

	/**
	 * Get a connectables form tag arguments.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 * @param string $tag         Specific tag.
	 *
	 * @return string The tag, empty if there is none set.
	 */
	protected function get_connectable_form_tag( string $connectable, string $tag ) : string {
		return $this->connectable_args[ $connectable ]['form_tags'][ $tag ] ?? '';
	}

	/**
	 * Generate a connector ID for this connector.
	 *
	 * @since 2.15.0
	 *
	 * @return string An ID generated for this specific connector.
	 */
	public function get_connector_id() : string {
		return $this->id;
	}

	/**
	 * Get the connectable when we're on it's add/edit screen.
	 *
	 * Note, groups are not yet supported.
	 *
	 * @since 2.15.0
	 *
	 * @return string The connectable of the current add/edit screen.
	 */
	protected function get_connectable_of_add_edit_screen() : string {

		$action = filter_input( INPUT_GET, 'action', FILTER_UNSAFE_RAW );

		if ( ! $this->is_string_and_nonempty( $action ) ) {
			return '';
		}

		$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );

		if ( ! $this->is_string_and_nonempty( $page ) ) {
			return '';
		}

		foreach ( $this->connectable_args as $connectable => $args ) {

			if ( $this->get_connectable_page_parameter( $connectable ) !== $page ) {
				continue; // Not even the right page in the admin.
			}

			if (
				"add_{$connectable}" !== $action &&
				"edit_{$connectable}" !== $action
			) {
				continue; // Not the the edit or add screen.
			}

			return $connectable;
		}

		return '';
	}

	/**
	 * Display a dropdown (selector) for selecting opposing items.
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param mixed $item Item object (we can discover the connectable based on the object).
	 *
	 * @throws \InvalidArgumentException If `$item` is not an object.
	 * @throws \InvalidArgumentException If `$item` does not have a proper ID property.
	 */
	public function display_selector( $item ) {

		$item_connectable = $this->get_connectable_by_item( $item );

		if ( ! $this->is_supported_connectable( $item_connectable ) ) {
			$item_connectable = $this->get_connectable_of_add_edit_screen();
		}

		$primary_key = $this->get_connectable_primary_key( $item_connectable );

		if ( is_object( $item ) && ! isset( $item->$primary_key ) ) {
			throw new \InvalidArgumentException( "\$item must contain a readable property called '{$primary_key}'." );
		}

		if ( is_object( $item ) && ! $this->is_numeric_and_gt_zero( $item->$primary_key ) ) {
			throw new \InvalidArgumentException( "\$item must have a valid ID stored in object property '{$primary_key}'." );
		}

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		$management_link = $this->get_opposing_connectable_management_link( $opposing_connectable );

		if ( empty( $management_link ) && $this->get_count_of_connectable_items( $opposing_connectable ) <= 0 ) {
			return; // No reason to display a selector when empty and no management link to create them.
		}

		$disabled = $this->connectable_has_no_items( $opposing_connectable );

		$connector_id = $this->get_connector_id( $item_connectable );

		?>

		<?php if ( ! empty( $this->get_connectable_form_tag( $item_connectable, 'form' ) ) ) : ?>
			<<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'form' ) ); ?> class="<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'form_class' ) ); ?>">
		<?php endif; ?>

			<?php if ( ! empty( $this->get_connectable_form_tag( $item_connectable, 'row_tag' ) ) ) : ?>
				<<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'row_tag' ) ); ?> class="<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'row_class' ) ); ?>">
			<?php endif; ?>

				<?php $this->selector_label( $item_connectable ); ?>

				<?php if ( ! empty( $this->get_connectable_form_tag( $item_connectable, 'content_tag' ) ) ) : ?>
					<<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'content_tag' ) ); ?>>
				<?php endif; ?>

					<!--

					Fix our multiselect select2.

					Because of how select2 formats their multiselects, it's larger
					than we want it to be. This fixes just our multiselect below.

					It's inline because it's a small fix and I didn't think it necessary
					to enqueue it.

					-->
					<style media="screen">

						.select2 .select2-selection.select2-selection--multiple {
							border: 1px solid #8c8f94 !important;
						}

						.select2 .select2-selection__choice,
						.select2 .select2-search.select2-search--inline {
							margin-bottom: 2px;
						}

						.select2 .select2-search.select2-search--inline input {
							min-height: 20px;
							height: 20px;
						}

					</style>

					<select
						name="<?php echo esc_attr( $connector_id ); ?>_items[]"
						id="<?php echo esc_attr( $connector_id ); ?>_items"
						style="min-width: 350px;"
						class="select2"
						data-label="<?php echo esc_attr( $connector_id ); ?>_items[]"
						data-placeholder="<?php /* Translators: %s is the group plural. */ echo esc_attr( $this->get_connectable_placeholder_text( $opposing_connectable ) ); ?>"
						data-args='<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen -- We want to avoid whitespace.

						// Use AJAX to populate the drop-downs.
						echo wp_json_encode(

							[
								'minimumInputLength' => 0,
								'disabled'           => $disabled ? true : false,

								// The groups that should be initially selected.
								'data'               => $this->get_select2_selected_opposing_items_json( $item, $opposing_connectable ),

								// Use AJAX to populate the drop-down w/ pagination.
								'ajax'               => [
									'url'      => admin_url( 'admin-ajax.php' ),
									'dataType' => 'json',
									'cache'    => true,
									'delay'    => 200,
									'data'     => [
										'action' => $this->get_select2_selector_ajax_action( $opposing_connectable ),
										'nonce'  => wp_create_nonce(
											$this->nonce_action( 'select', 'items' ),
											$this->nonce_action( 'select', 'items' )
										),
									],
								],
							]
						); // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect -- Indented correctly.

						// phpcs:ignore Generic.WhiteSpace.ScopeIndent.IncorrectExact, Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- Want to avoid whitespace.
						?>'
						<?php echo esc_attr( 'multiple' === $this->selector_type ? 'multiple' : '' ); ?>>
							<?php if ( 'single' === $this->selector_type ) : ?>
								<option value="none"><?php echo esc_html( $this->get_connectable_none_text( $opposing_connectable ) ); ?></option>
							<?php endif; ?>
					</select>

					<p class="description">
						<?php if ( $this->get_count_of_connectable_items( $opposing_connectable ) <= 0 && ! empty( $management_link ) ) : ?>
							<?php

							echo wp_kses(

								$this->get_selector_create_text( $item_connectable, $management_link ),

								// Allowed HTML.
								[
									'a' => [
										'href'   => true,
										'title'  => true,
										'target' => true,
									],
								]
							);

							?>
						<?php else : ?>
							<?php

							// Description.
							echo esc_html(
								$this->get_selector_description( $item_connectable )
							);

							?>
						<?php endif; ?>
					</p>

				<?php if ( ! empty( $this->get_connectable_form_tag( $item_connectable, 'content_tag' ) ) ) : ?>
					</<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'content_tag' ) ); ?>>
				<?php endif; ?>

			<?php if ( ! empty( $this->get_connectable_form_tag( $item_connectable, 'row_tag' ) ) ) : ?>
				</<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'row_tag' ) ); ?>>
			<?php endif; ?>

			<?php

			wp_nonce_field(
				$this->nonce_action( 'update', 'item' ),
				$this->nonce_action( 'update', 'item' )
			);

			?>
		<?php if ( ! empty( $this->get_connectable_form_tag( $item_connectable, 'form' ) ) ) : ?>
			</<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'form' ) ); ?>>
		<?php endif; ?>

		<?php
	}

	/**
	 * Get the link for creating opposing connectable items.
	 *
	 * Add your group types here.
	 *
	 * @since 2.15.0
	 *
	 * @param string $opposing_connectable Opposing connection.
	 *
	 * @return string
	 *
	 * @throws \Exception If you try and use a group type (group connectable) that doesn't yet have support
	 *                    in this method.
	 */
	protected function get_opposing_connectable_management_link( string $opposing_connectable ) : string {

		if ( 'group' === $opposing_connectable ) {

			$group_type = $this->get_connectable_group_type( $opposing_connectable );

			if ( 'creative-category' === $group_type ) {
				return admin_url( 'admin.php?page=affiliate-wp-creatives-categories' );
			}

			if ( 'affiliate-group' === $group_type ) {
				return admin_url( 'admin.php?page=affiliate-wp-affiliate-groups' );
			}

			throw new \Exception( "No support for group type {$group_type}, please add support for it." );
		}

		if ( 'affiliate' === $opposing_connectable ) {
			return admin_url( 'admin.php?page=affiliate-wp-affiliates' );
		}

		if ( 'creative' === $opposing_connectable ) {
			return admin_url( 'admin.php?page=affiliate-wp-creatives' );
		}

		return '';
	}

	/**
	 * Selector text for when there are no items to select.
	 *
	 * @since 2.15.0
	 *
	 * @param string $item_connectable The connectable.
	 * @param string $management_link  Management URI/Link.
	 *
	 * @return string
	 */
	protected function get_selector_create_text( string $item_connectable, string $management_link ) : string {

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		return sprintf(
			/* Translators: %1$s is the grouping singular, %2$s is the item singlular. */
			__( '%1$sCreate%2$s %3$s %4$s to assign to this %5$s.', 'affiliate-wp' ),
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
	 * Selector description (under input) text.
	 *
	 * @since 2.15.0
	 *
	 * @param string $item_connectable The connectable of the item being added/edited.
	 *
	 * @return string
	 */
	protected function get_selector_description( string $item_connectable ) : string {

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		return sprintf(
			/* Translators: %1$s is the language for plural or single selection,  %2$s is the grouping singular, %3$s is the item singlular. E.g.: 'Select a group for this affiliate.', or 'Select one or more groups for this affiliate.' */
			__( 'Select %1$s %2$s for this %3$s.', 'affiliate-wp' ),
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
	 * Seletor label.
	 *
	 * @since 2.15.0
	 *
	 * @param string $item_connectable The connectable of the item being edit/added.
	 *
	 * @return void Display only.
	 */
	protected function selector_label( string $item_connectable ) : void {

		$connector_id = $this->get_connector_id();

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		?>

		<<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'label_tag' ) ); ?> scope="row">
			<label for="<?php echo esc_attr( $connector_id ); ?>_items[]">
				<?php echo wp_kses_post( $this->get_selector_label_text( $opposing_connectable ) ); ?>
			</label>
		</<?php echo esc_attr( $this->get_connectable_form_tag( $item_connectable, 'label_tag' ) ); ?>>

		<?php
	}

	/**
	 * Get the label text for the selector on add/edit item.
	 *
	 * @since 2.15.0
	 *
	 * @param string $opposing_connectable The opposing connectable (for language).
	 *
	 * @return string
	 */
	protected function get_selector_label_text( string $opposing_connectable ) : string {
		return ucfirst( $this->get_connectable_lang( 'single', $opposing_connectable ) );
	}

	/**
	 * Get the placeholder text for the Select2 dropdown (selector).
	 *
	 * @since 2.13.2
	 *
	 * @param string $connectable What connectable to use.
	 *
	 * @return string
	 */
	protected function get_connectable_placeholder_text( string $connectable ) : string {

		if ( $this->get_connectable_items( $connectable, [ 'number' => -1 ], true ) <= 0 ) {

			return sprintf(

				// Translators: %s is the plural of the item, e.g. affiliate groups, affiliates, creatives, etc.
				__( 'No %s', 'affiliate-wp' ),
				ucwords( strtolower( $this->get_connectable_lang( 'plural', $connectable ) ) )
			);
		}

		return sprintf(

			// Translators: %s is the plural language of the item, e.g. affiliate groups, affiliates, creatives, etc.
			$this->get_connectable_lang( 'placeholder', $connectable ),
			strtolower( $this->get_connectable_lang( 'plural', $connectable ) )
		);
	}

	/**
	 * Get the action name for the AJAX selector/dropdown (for filtering).
	 *
	 * @param string $connectable The connectable.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @return string
	 */
	private function get_selector_filter_select2_ajax_action( string $connectable ) : string {
		return "affwp_{$this->get_connector_id()}_{$connectable}_filter";
	}

	/**
	 * Get the action name for the AJAX selector (not for filtering).
	 *
	 * Used, likely, on item edit/add screens.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	private function get_select2_selector_ajax_action( string $connectable = '' ) : string {
		return "affwp_{$this->get_connector_id()}_{$connectable}_selector";
	}

	/**
	 * Get the selected opposing items in Select2 JSON format.
	 *
	 * @since 2.13.2
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param mixed  $item                 The item object (we can get the main connectable from the object).
	 * @param string $opposing_connectable The opposing connectable.
	 *
	 * @return array
	 */
	private function get_select2_selected_opposing_items_json( $item, string $opposing_connectable ) : array {

		return array_values(
			array_map(
				function ( $opposing_item_id ) use ( $opposing_connectable ) {

					return $this->esc_select_2_json_item(
						[
							'id'       => $opposing_item_id,
							'text'     => $this->get_title_of_connectable_item(
								$opposing_connectable,
								$opposing_item_id,
								'selected_select2_json_items'
							),
							'selected' => true, // Selected.
						]
					);
				},
				$this->get_opposing_ids_connected_to_item(
					$item,
					$opposing_connectable
				)
			)
		);
	}

	/**
	 * Get the ID value of an item (affiliate, creative, etc) object.
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable of the item.
	 * @param mixed  $item        The item object (affiliate, creative, etc).
	 *
	 * @return int The ID set on the object if we can discover it.
	 */
	private function get_id_of_connectable_item( string $connectable, $item ) : int {

		$primary_key = $this->get_connectable_primary_key( $connectable );

		return $item->$primary_key;
	}

	/**
	 * Get the opposing connected IDs connected to an item.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param mixed  $item                 The item object (affiliate, creative, etc).
	 * @param string $opposing_connectable The opposing connectable.
	 *
	 * @return array Opposing items that are connected to the item.
	 */
	private function get_opposing_ids_connected_to_item( $item, string $opposing_connectable ) {

		$item_connectable = $this->get_connectable_by_item( $item );

		if ( ! $this->is_supported_connectable( $item_connectable ) ) {
			$item_connectable = $this->get_connectable_of_add_edit_screen();
		}

		$primary_key = $this->get_connectable_primary_key( $item_connectable );

		if ( ! isset( $item->$primary_key ) ) {
			return [];
		}

		if ( ! $this->is_numeric_and_gt_zero( $item->$primary_key ) ) {
			return [];
		}

		// Groups are treated a bit different because they require a group type.
		return ( 'group' === $opposing_connectable )
			? affiliate_wp()->groups->filter_groups_by_type(
				$this->get_connected(
					'group',
					$item_connectable,
					intval( $item->$primary_key )
				),
				$this->get_connectable_group_type( $opposing_connectable )
			)
			: $this->get_connected(
				$opposing_connectable,
				$item_connectable,
				intval( $item->$primary_key )
			);
	}

	/* phpcs:ignore -- These are dynamic filter methods for discovering the connectable, see display_filter_selector_for_connectable() for more. */
	public function display_filter_selector_for_creative( string $which ) : void {
		$this->display_filter_selector_for_connectable( 'creative', $which );
	}

	/* phpcs:ignore -- These are dynamic filter methods for discovering the connectable, see display_filter_selector_for_connectable() for more. */
	public function display_filter_selector_for_affiliate( string $which ) : void {
		$this->display_filter_selector_for_connectable( 'affiliate', $which );
	}

	/**
	 * Filter dropdown.
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $item_connectable The connectable of the item (of the list table).
	 * @param string $which            Either `top` or `bottom` (we only care about top).
	 * @return void
	 */
	private function display_filter_selector_for_connectable( string $item_connectable, string $which ) {

		if ( 'bottom' === $which ) {
			return;
		}

		if ( ! $this->is_current_connectable_list_table_page( $item_connectable ) ) {
			return;
		}

		$opposing_connectable = $this->get_opposing_connectable( $item_connectable );

		if ( $this->connectable_has_no_items( $opposing_connectable ) ) {
			return;
		}

		?>

		<style>

			/**
			 * These are here to help make the Select2
			 * look like WordPress in this position.
			 */

			.select2-container--default .select2-selection {
				height: 31px;
				border-color: #8c8f94;
			}

			.select2-search--dropdown .select2-search__field {
				padding: 0 4px;
			}

		</style>

		<select
			name="<?php echo esc_attr( $this->get_filter_list_table_name() ); ?>"
			id="<?php echo esc_attr( $this->get_filter_list_table_name() ); ?>"
			class="select2 filter-dropdown"
			style="min-width: 230px;"
			data-placeholder="<?php echo esc_attr( $this->get_connectable_placeholder_text( $opposing_connectable ) ); ?>"
			data-args='<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen -- We want to avoid whitespace.

			// Use AJAX to populate the drop-downs.
			echo wp_json_encode(
				[
					'minimumInputLength' => 0,

					// The groups that should be initially selected.
					'data' => $this->get_select2_selected_filter_items(
						$item_connectable,
						$opposing_connectable
					),

					// Use AJAX to populate the drop-down w/ pagination.
					'ajax' => [
						'url'      => admin_url( 'admin-ajax.php' ),
						'dataType' => 'json',
						'cache'    => true,
						'delay'    => 200,
						'data'     => [
							'action' => $this->get_selector_filter_select2_ajax_action( $item_connectable ),
							'nonce'  => wp_create_nonce(
								$this->nonce_action( 'filter', 'items' ),
								$this->nonce_action( 'filter', 'items' )
							),
						],
					],
				]
			); // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect -- Indented correctly.

			// phpcs:ignore Generic.WhiteSpace.ScopeIndent.IncorrectExact, Squiz.PHP.EmbeddedPhp.ContentAfterEnd -- Want to avoid whitespace.
			?>'><!-- Populated via AJAX for performance reasons.
		--></select>

		<?php

		wp_nonce_field(
			$this->nonce_action( 'filter', 'items' ),
			$this->nonce_action( 'filter', 'items' )
		);

		?>

		<input type="submit" style="margin-right: 10px; margin-left: 2px" class="button action" value="<?php esc_html_e( 'Filter', 'affiliate-wp' ); ?>">

		<?php
	}

	/**
	 * Option text for None.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_no_items_filter_option_text( string $connectable ) : string {

		return sprintf(
			$this->get_connectable_lang( 'none', $connectable ),
			'multiple' === $this->selector_type
				? ucfirst( strtolower( $this->get_connectable_lang( 'plural', $connectable ) ) )
				: ucfirst( strtolower( $this->get_connectable_lang( 'single', $connectable ) ) )
		);
	}

	/**
	 * Option text for All.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $connectable The connectable.
	 *
	 * @return string
	 */
	protected function get_all_items_filter_option_text( string $connectable ) : string {

		return sprintf(
			$this->get_connectable_lang( 'all', $connectable ),
			ucfirst( strtolower( $this->get_connectable_lang( 'plural', $connectable ) ) )
		);
	}

	/**
	 * Get the selected filter options selected on the list table.
	 *
	 * @since 2.13.2
	 * @since 2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $item_connectable     The connectable of the list table.
	 * @param string $opposing_connectable The connectable of the opposing items.
	 *
	 * @return array List of selected opposing items from the filter dropdown.
	 */
	private function get_select2_selected_filter_items( string $item_connectable, string $opposing_connectable ) : array {

		$selected_filter_option = filter_input( INPUT_GET, $this->get_filter_list_table_name(), FILTER_UNSAFE_RAW ) ?? '';

		if ( $this->get_none_option_name() === $selected_filter_option ) {

			return [
				$this->esc_select_2_json_item(
					[
						'id'       => $this->get_none_option_name(),
						'text'     => $this->get_no_items_filter_option_text( $opposing_connectable ),
						'selected' => true,
					]
				),
			];
		}

		if (
			empty( $selected_filter_option ) ||
			(
				is_numeric( $selected_filter_option ) && // Might be `none`.
				0 === intval( $selected_filter_option )
			)
		) {

			return [
				$this->esc_select_2_json_item(
					[
						'id'       => 0,
						'text'     => $this->get_all_items_filter_option_text( $opposing_connectable ), // Filtered.
						'selected' => true,
					]
				),
			];
		}

		if ( ! is_numeric( $selected_filter_option ) ) {
			return [];
		}

		$opposing_item_id = $this->get_id_of_connectable_item(
			$opposing_connectable,
			$this->get_connectable_item(
				$opposing_connectable,
				intval( $selected_filter_option )
			)
		);

		return [
			$this->esc_select_2_json_item(
				[
					'id'   => esc_html( $opposing_item_id ),
					'text' => esc_html(
						$this->get_title_of_connectable_item(
							$opposing_connectable,
							$opposing_item_id,
							'select2_selected_filte_items'
						)
					),
					'selected' => true, // Selected previously.
				]
			),
		];
	}

	/**
	 * Register connectables with the Connections API.
	 *
	 * @since 2.12.0
	 * @since 2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @throws \Exception If we can't register either connectable args as a connectable.
	 */
	private function register_connectables() {

		foreach ( $this->connectable_args as $connectable => $args ) {

			if ( affiliate_wp()->connections->is_registered_connectable( $connectable ) ) {
				continue;
			}

			if (
				is_multisite() &&
					! $this->table_exists( affiliate_wp()->connections->get_connectable_api( $connectable )->table_name ) &&
						is_callable( [ affiliate_wp()->connections->get_connectable_api( $connectable ), 'create_table' ] )
			) {

				// Sometimes, on multisite, the table hasn't been created yet, let's try and do that here directly.
				affiliate_wp()->connections->get_connectable_api( $connectable )->create_table();
			}

			affiliate_wp()->connections->register_connectable(
				[
					'name'   => $connectable,
					'table'  => affiliate_wp()->connections->get_connectable_api( $connectable )->table_name,
					'column' => affiliate_wp()->connections->get_connectable_api( $connectable )->primary_key,
				]
			);
		}
	}

	/**
	 * Validate instance properties.
	 *
	 * @since 2.12.0
	 *
	 * @throws \InvalidArgumentException When invalid properties are found.
	 */
	protected function validate_properties() {

		if ( ! $this->is_string_and_nonempty( $this->capability ) ) {
			throw new \InvalidArgumentException( '$this->capability must be a non-empty string.' );
		}

		if ( ! $this->string_is_one_of( $this->selector_type, [ 'multiple', 'single' ] ) ) {
			throw new \InvalidArgumentException( "\$this->selector_type must be either 'multiple' or 'single'." );
		}

		if ( ! isset( $this->connectable_args ) || ! is_array( $this->connectable_args ) ) {
			throw new \InvalidArgumentException( '$this->connectable_args must be an array (contents validated later).' );
		}

		if ( ! $this->is_string_and_nonempty( $this->id ) || ( esc_attr( $this->id ) !== $this->id ) ) {
			throw new \InvalidArgumentException( '$this->id must be a non-empty string and pass esc_attr().' );
		}
	}

	/**
	 * Get the search term passed over AJAX from Select2.
	 *
	 * @since 2.13.2
	 *
	 * @return string
	 */
	protected function get_select2_ajax_search_term() : string {

		$term = filter_input( INPUT_GET, 'term', FILTER_UNSAFE_RAW );

		if ( ! is_string( $term ) ) {
			return '';
		}

		return $this->strip_quotes( stripslashes_deep( trim( html_entity_decode( $term ) ) ) );
	}

	/**
	 * Strip quotes from string.
	 *
	 * @since 2.13.2
	 *
	 * @param string $string The string.
	 *
	 * @return string
	 */
	protected function strip_quotes( string $string ) : string {

		return str_replace(
			[
				"'",
				'"',
			],
			[
				'',
				'',
			],
			$string
		);
	}

	/**
	 * Calculate an offset.
	 *
	 * @since 2.15.0
	 *
	 * @param int $per_page Per page setting.
	 * @param int $page     Current page.
	 *
	 * @return int
	 */
	protected function calc_offset( int $per_page, int $page ) : int {
		return absint( ( $per_page * $page ) - $per_page );
	}

	/**
	 * Sanitize Select2 JSON object for output.
	 *
	 * @since 2.13.2
	 *
	 * @param array $item An individual item.
	 *
	 * @return array
	 */
	protected function esc_select_2_json_item( array $item = [] ) {

		$selected = isset( $item['selected'] )
			? true === $item['selected']
			: false;

		return array_merge(
			$item,
			[
				'id'       => esc_attr( $item['id'] ?? 0 ),
				'text'     => html_entity_decode(

					// Select2 doesn't like a tick mark ' so use a substitute.
					str_replace(
						'&#039;',
						'&#x2019;',
						esc_attr( esc_html( $item['text'] ?? '' ) )
					),
					ENT_COMPAT
				),
				'selected' => (bool) $selected,
			]
		);
	}

	/**
	 * Is search found in text?
	 *
	 * This is specifically for working with Select2 AJAX JSON values for "text",
	 * as they might have special characters that mess up HTML and JavaScript,
	 * especially over AJAX.
	 *
	 * @since 2.13.2
	 *
	 * @param string $text   The text.
	 * @param string $search The search.
	 *
	 * @return bool
	 */
	protected function stristr_search_select_2_text( string $text, string $search ) : bool {

		return stristr(
			$this->strip_quotes( html_entity_decode( strtolower( trim( $text ) ) ) ),
			$this->strip_quotes( strtolower( $search ) )
		);
	}

	/**
	 * Are we displaying the connectables list table page?
	 *
	 * @since  2.12.0
	 * @since  2.15.0 Re-factored to work with item-to-item, not just item-to-group.
	 *
	 * @param string $connectable The connectable in question.
	 *
	 * @return bool
	 *
	 * @throws \InvalidArgumentException If you pass a non-string to `$page`.
	 */
	protected function is_current_connectable_list_table_page( string $connectable = '' ) : bool {

		// Test for either page.
		if ( empty( $connectable ) ) {

			foreach ( $this->connectable_args as $connectable => $args ) {

				if ( ! $this->is_connectable_list_table_type( $connectable ) ) {
					continue;
				}

				$page_param = $this->get_connectable_page_parameter( $connectable );

				if ( filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW ) === $page_param ) {
					return true;
				}
			}

			return false;
		}

		if ( ! $this->is_connectable_list_table_type( $connectable ) ) {
			return false;
		}

		return filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW )
			=== $this->get_connectable_page_parameter( $connectable );
	}

	/**
	 * Scripts
	 *
	 * @since  2.12.0
	 *
	 * @return void Only happens on our items page(s).
	 */
	public function scripts() : void {

		if ( ! $this->is_current_connectable_list_table_page() ) {
			return;
		}

		if ( ! $this->is_current_connectable_add_edit_page() ) {
			return;
		}

		$this->enqueue_select2();
	}

	/**
	 * Does the user have the required capability.
	 *
	 * @since 2.13.0
	 *
	 * @return bool
	 */
	protected function user_has_capability() : bool {

		return current_user_can( $this->capability )

			// Admins always can.
			|| current_user_can( 'administrator' )

			// Super-admins always can.
			|| is_super_admin();
	}

	/**
	 * Get a connectables form tags.
	 *
	 * @since 2.15.0
	 *
	 * @param array $overrides Any overrides to perform.
	 *
	 * @return array
	 *
	 * @throws \InvalidArgumentException If invalid form tag is found in overrides.
	 */
	protected function get_form_tags( array $overrides = [] ) : array {

		$form_tags = [
			'form'        => 'table',
			'form_class'  => 'form-table',
			'row_tag'     => 'tr',
			'row_class'   => 'form-row',
			'label_tag'   => 'th',
			'content_tag' => 'td',
		];

		// Make sure they are only setting overrides for the above.
		foreach ( $overrides as $override => $value ) {

			if ( ! in_array( $override, array_keys( $form_tags ), true ) ) {

				$form_tags_string = implode( '|', $form_tags );

				throw new \InvalidArgumentException( "\$overrides can only contain overrides for {$form_tags_string}, found {$override}" );
			}
		}

		return array_merge(
			$form_tags,
			$overrides
		);
	}

	/**
	 * Set the connectable args (merge).
	 *
	 * @since 2.15.0
	 *
	 * @param string $connectable The connectable.
	 * @param array  $args        Overrides to merge.
	 */
	protected function update_connectable_args( string $connectable, array $args ) : void {

		$this->connectable_args[ $connectable ] = array_merge(
			$this->connectable_args[ $connectable ],
			$args
		);
	}
}
