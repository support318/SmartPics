<?php
/**
 * Affiliate Area Creatives
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateArea
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.16.0
 * @author      Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP;

use AffWP\Creative;
use AffiliateWP\Utils\Icons;
use InvalidArgumentException;

#[\AllowDynamicProperties]

/**
 * Affiliate Area Creatives
 *
 * Responsible for rendering the Affiliate Area visual components for Creatives.
 *
 * @since 2.16.0
 */
class Affiliate_Area_Creatives {

	/**
	 * The current page.
	 *
	 * @since 2.16.0
	 *
	 * @var int
	 */
	private int $page = 1;

	/**
	 * Total of items to fetch per page.
	 *
	 * @since AFFEPNEXT
	 *
	 * @var int
	 */
	private int $per_page = 30;

	/**
	 * Default arguments to query creatives from DB.
	 *
	 * @since 2.16.0
	 *
	 * @var array|string[]
	 */
	private array $query_args = array(
		'orderby' => 'date_updated',
		'order'   => 'desc',
	);

	/**
	 * The collection of retrieved creatives.
	 *
	 * @since 2.16.0
	 *
	 * @var array
	 */
	private array $creatives = array();

	/**
	 * Parameters used to build URLs.
	 *
	 * @since 2.16.0
	 *
	 * @var string[]
	 */
	private array $allowed_url_args = array(
		'type',
		'page',
		'cat',
		'order',
		'orderby',
		'view_type',
	);

	/**
	 * The view type options.
	 *
	 * @since 2.16.0
	 *
	 * @var string[]
	 */
	private array $view_types = array(
		'list',
		'grid',
	);

	/**
	 * Kses for content filtering.
	 *
	 * @since 2.16.0
	 *
	 * @var array
	 */
	private array $content_kses = array();

	/**
	 * Construct.
	 *
	 * @since 2.16.0
	 */
	public function __construct() {

		$this->content_kses = affwp_kses(
			array(
				'span' => array(
					'data-url'      => true,
					'data-settings' => true,
				),
			)
		);

		$this->hooks();
	}

	/**
	 * Execute all hooks.
	 *
	 * @since 2.16.0
	 */
	public function hooks() : void {

		// General purpose hooks.
		add_action( 'init', array( $this, 'save_view_type_cookie' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 5 ); // Must run before form.css.
		add_action( 'body_class', array( $this, 'body_class' ) );

		// Ajax hooks.
		add_action( 'wp_ajax_affwp_show_creative_modal', array( $this, 'handle_modal_request' ) );
		add_action( 'wp_ajax_affwp_creatives_load_more', array( $this, 'handle_infinite_scroll' ) );

		// UI hooks.
		add_action( 'wp_footer', array( $this, 'modal_container' ) );
		add_action( 'affwp_filter_creative_category_dropdown', array( $this, 'add_query_params_to_form' ), 10, 0 );
	}

	/**
	 * Add our classes to the body.
	 *
	 * @since 2.17.2
	 *
	 * @param array $classes Array of classes to append to the body.
	 *
	 * @return array
	 */
	public function body_class( array $classes ) : array {

		if ( ! affwp_is_affiliate_area() ) {
			return $classes;
		}

		return array_filter(
			array_merge(
				$classes,
				[
					$this->is_creatives_tab() ? 'affwp-affiliate-area-creatives' : '',
				]
			)
		);
	}

	/**
	 * Register and enqueue the creatives scripts.
	 *
	 * @since 2.16.0
	 */
	public function load_scripts() : void {

		if ( ! $this->is_creatives_tab() ) {
			return; // Bail if it is not the creative's page.
		}

		affiliate_wp()->scripts->enqueue(
			'affiliatewp-creatives',
			array(
				'affiliatewp-fancybox',
				'affiliatewp-modal',
				'affiliatewp-tooltip',
				'affiliatewp-infinite-scroll',
				'affiliatewp-qrcode',
			)
		);

		$json = wp_json_encode(
			array(
				'nonce'           => wp_create_nonce( 'affwp-creatives-load-more' ),
				'itemsPerPage'    => $this->per_page,
				'page'            => $this->page,
				'maxPages'        => $this->calc_max_pages(),
				'queryArgs'       => wp_parse_args(
					$this->get_args_from_url(),
					array(
						'view_type' => $this->get_user_view_type(),
					)
				),
				'creativeAjaxUrl' => sprintf(
					'%s?nonce=%s&action=affwp_show_creative_modal&creative_id=',
					admin_url( 'admin-ajax.php' ),
					wp_create_nonce( 'affwp-preview-creative' )
				),
				'i18n'            => array(
					'copyDisabled' => __( 'Error! Copy is not available.', 'affiliate-wp' ),
					'copySuccess'  => __( 'Code Copied!', 'affiliate-wp' ),
					'copyError'    => __( 'Error! Can not copy content.', 'affiliate-wp' ),
				),
			)
		);

		wp_add_inline_script( 'affiliatewp-creatives', "window.affiliatewpCreativesData={$json}", 'before' );
	}

	/**
	 * Return the query args.
	 *
	 * @since 2.16.0
	 *
	 * @param string $key If supplied, will return a single key.
	 * @return array|string[] The query args.
	 */
	public function get_query_args( string $key = '' ) {

		if ( '' !== $key && isset( $this->query_args[ $key ] ) ) {
			return $this->query_args[ $key ];
		}

		return $this->query_args;
	}

	/**
	 * Set the query args.
	 *
	 * Do not try to set "number" or "offset" using this method, use
	 * set_current_page() and set_items_per_page() in its place.
	 *
	 * @since 2.16.0
	 *
	 * @param array $args Query args.
	 * @return self This object, so it can be chainable.
	 */
	public function set_query_args( array $args ) : self {

		$this->query_args = wp_parse_args(
			$args,
			$this->query_args
		);

		return $this;

	}

	/**
	 * Return the max number of pages based on the visible creatives for the affiliate.
	 * Used for pagination.
	 *
	 * @since 2.16.0
	 *
	 * @return int The calculated page number.
	 */
	public function calc_max_pages() : int {

		$total = count(
			affiliate_wp()->creative->get_affiliate_creatives(
				/**
				 * Filter arguments used to show creatives on the affiliate area creatives tab.
				 *
				 * @since 2.16.0
				 *
				 * @param array $args Arguments.
				 */
				apply_filters(
					'affwp_affiliate_dashboard_creatives_args',
					wp_parse_args(
						$this->get_args_from_url(),
						array(
							'number' => apply_filters( 'affwp_unlimited', -1, 'creatives_type_count' ),
							'fields' => array( 'creative_id', 'type' ),
							'type'   => 'any',
						)
					)
				)
			)
		);

		return 0 === $total
			? $total
			: ceil( $total / $this->get_items_per_page() );

	}

	/**
	 * Set the current page number to fetch creatives.
	 *
	 * @since 2.16.0
	 *
	 * @param int $page The desired page.
	 * @throws InvalidArgumentException Wrong page number.
	 * @return $this This object, so it can be chainable.
	 */
	public function set_current_page( int $page ) : self {

		if ( $page > 0 ) {
			$this->page = $page;
			return $this;
		}

		throw new InvalidArgumentException( 'The page number must be greater than 0.' );
	}

	/**
	 * Return the current page number.
	 *
	 * @since 2.16.0
	 *
	 * @return int The current page.
	 */
	public function get_current_page() : int {
		return $this->page;
	}

	/**
	 * Set how many items must be returned when fetching creatives.
	 *
	 * @since 2.16.0
	 * @param int $per_page The number of items per page.
	 * @throws InvalidArgumentException Wrong items per page number.
	 * @return $this This object, so it can be chainable.
	 */
	public function set_items_per_page( int $per_page ) : self {

		if ( $per_page > 0 ) {
			$this->per_page = $per_page;
			return $this;
		}

		throw new InvalidArgumentException( 'The items per page number must be greater than 0.' );
	}

	/**
	 * Return the items page number.
	 *
	 * @since 2.16.0
	 *
	 * @return int Items per page number.
	 */
	public function get_items_per_page() : int {
		return $this->per_page;
	}

	/**
	 * Return an array of creatives objects for the current affiliate user.
	 *
	 * @since 2.16.0
	 *
	 * @return array The visible creatives for the current affiliate.
	 */
	public function get_creatives() : array {
		return $this->creatives;
	}

	/**
	 * Fetch creatives from database based on the current page.
	 *
	 * @return $this This object, so it can be chainable.
	 */
	public function fetch_creatives() : self {

		/**
		 * Filter arguments used to show creatives on the affiliate area creatives tab.
		 *
		 * @since 2.16.0
		 *
		 * @param array $args Arguments.
		 */
		$args = apply_filters(
			'affwp_affiliate_dashboard_creatives_args',
			wp_parse_args(
				$this->get_query_args(),
				array(
					'number' => $this->get_items_per_page(),
					'offset' => $this->get_items_per_page() * ( $this->get_current_page() - 1 ),
				)
			)
		);

		$this->creatives = affiliate_wp()->creative->get_affiliate_creatives( $args );

		return $this;
	}

	/**
	 * Set a cookie to remember user's choice for the view type.
	 *
	 * @since 2.16.0
	 */
	public function save_view_type_cookie() : void {

		$view_type = filter_input( INPUT_GET, 'view_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! in_array( $view_type, $this->view_types, true ) ) {
			return;
		}

		setcookie(
			'affiliatewp_creatives_view_type',
			$view_type,
			strtotime( '+10 years' ),
			'/'
		);
	}

	/**
	 * Check if user is in the creative's area.
	 *
	 * @since 2.16.0
	 * @since 2.18.0 Updated to also consider the Portal Creatives Tab.
	 *
	 * @return bool Whether is creative's tab or not.
	 */
	public function is_creatives_tab() : bool {

		if (
			function_exists( 'affwp_is_portal_enabled' ) &&
			function_exists( 'affwp_is_affiliate_portal' ) &&
			affwp_is_portal_enabled() &&
			affwp_is_affiliate_portal( 'creatives' )
		) {
			return true;
		}

		return affwp_is_affiliate_area() &&
			filter_input(
				INPUT_GET,
				'tab',
				FILTER_SANITIZE_FULL_SPECIAL_CHARS
			) === 'creatives';
	}

	/**
	 * Return the last view type definition.
	 *
	 * @since 2.16.0
	 *
	 * @return string The view type.
	 */
	public function get_user_view_type() : string {

		$view_type = filter_input( INPUT_GET, 'view_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( in_array( $view_type, $this->view_types, true ) ) {
			return $view_type;
		}

		$view_type = filter_input( INPUT_COOKIE, 'affiliatewp_creatives_view_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( in_array( $view_type, $this->view_types, true ) ) {
			return $view_type;
		}

		return 'list';
	}

	/**
	 * Displays the view switcher menu.
	 *
	 * @since 2.16.0
	 *
	 * @return void
	 */
	public function view_switcher() : void {

		if ( empty( affiliate_wp()->creatives_view->get_creatives() ) ) {
			return; // Do not show view switcher if there are no Creatives to display.
		}

		ob_start();

		$icons = array(
			'list' => __( 'List', 'affiliate-wp' ),
			'grid' => __( 'Grid', 'affiliate-wp' ),
		);
		?>

		<ul class="affwp-view-switcher">
			<?php foreach ( $this->view_types as $view_type ) : ?>

				<?php

				$class  = "affwp-view-switcher__{$view_type}";
				$class .= $this->get_user_view_type() === $view_type
					? ' active'
					: '';

				?>

				<li class="<?php echo esc_attr( $class ); ?>">

					<?php if ( $this->get_user_view_type() === $view_type ) : ?>

						<span class="affwp-view-switcher__button"><?php Icons::render( $view_type, $icons[ $view_type ] ); ?></span>

					<?php else : ?>

						<a
							class="affwp-view-switcher__button"
							href="<?php echo esc_url_raw( $this->generate_tab_url_from_current_url( "view_type={$view_type}" ) ); ?>">
							<?php Icons::render( $view_type, $icons[ $view_type ] ); ?>
						</a>

					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo ob_get_clean();
	}

	/**
	 * Return the count of each type where the type is the key and the count is the value.
	 *
	 * @since 2.16.0
	 *
	 * @return array An array with each type count.
	 */
	private function types_count() : array {

		// Return only active creatives available for the current affiliate.
		$creatives = affiliate_wp()->creative->get_affiliate_creatives(
			array(
				'number' => apply_filters( 'affwp_unlimited', -1, 'creatives_type_count' ),
				'fields' => array( 'creative_id', 'type' ),
				'type'   => 'any',
			)
		);

		if ( empty( $creatives ) ) {
			return array();
		}

		return array_count_values(
			array_map(
				function( $creative ) {
					return $creative->type;
				},
				$creatives
			)
		);

	}

	/**
	 * Display a menu to navigate through creative types.
	 *
	 * @since 2.16.0
	 * @since 2.25.2 Only display the menu if there are more than two types of creatives to show.
	 *
	 * @return void
	 */
	public function types_menu() : void {

		$types_count = $this->types_count();

		if ( count( $types_count ) <= 1 ) {
			return; // Bail if we have only one type to display.
		}

		$cat = (string) filter_input( INPUT_GET, 'cat', FILTER_SANITIZE_NUMBER_INT );

		ob_start();

		?>

		<ul class="affwp-creatives-types-menu">

			<li<?php echo $this->is_tab_active( '' ) ? ' class="active"' : ''; ?>>
				<a href="<?php echo esc_url_raw( $this->generate_tab_url_from_current_url( "type=&cat={$cat}" ) ); ?>">
					<?php
						echo sprintf(
							'%s <span>(%d)</span>',
							esc_html__( 'All', 'affiliate-wp' ),
							esc_html( array_sum( array_values( $types_count ) ) )
						);
					?>
				</a>
			</li>

			<?php foreach ( affwp_get_creative_types( true ) as $type_key => $type_label ) : ?>

				<?php

				$count = $types_count[ $type_key ] ?? 0;

				if ( 0 === $count ) {
					continue; // Do not display empty menus.
				}

				?>

				<li<?php echo $this->is_tab_active( $type_key ) ? ' class="active"' : ''; ?>>
					<a href="<?php echo esc_url_raw( $this->generate_tab_url_from_current_url( "type={$type_key}&cat={$cat}" ) ); ?>">
						<?php

						echo sprintf(
							'%s <span>(%d)</span>',
							esc_html( $type_label ),
							esc_html( $count )
						);

						?>
					</a>
				</li>

			<?php endforeach; ?>

		</ul>

		<?php

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo ob_get_clean();
	}

	/**
	 * Return the preview url for a creative.
	 *
	 * @since 2.16.0
	 *
	 * @param int $creative_id The creative ID.
	 * @return string The ajax url.
	 */
	private function creative_modal_url( int $creative_id ) : string {

		return sprintf(
			'%s?nonce=%s&action=affwp_show_creative_modal&creative_id=%d',
			admin_url( 'admin-ajax.php' ),
			wp_create_nonce( 'affwp-preview-creative' ),
			$creative_id
		);

	}

	/**
	 * Return the View Details button HTML.
	 *
	 * @since 2.16.0
	 *
	 * @param int    $creative_id The creative ID.
	 * @param string $button_text The button label. Also used as fallback text when using icons.
	 * @param string $use_icon If supplied, will use an icon instead of a text. Default `view`.
	 * @return string The button HTML.
	 */
	public function show_details_button(
		int $creative_id,
		string $button_text = '',
		string $use_icon = 'view'
	) : string {

		$button_text = empty( $button_text )
			? __( 'View Details', 'affiliate-wp' )
			: $button_text;

		$button_classes = sprintf(
			'affwp-creatives-list-action affwp-button affwp-button--as-%s',
			empty( $use_icon ) ? 'text' : 'icon'
		);

		ob_start();

		?>

		<button
			class="<?php echo esc_attr( $button_classes ); ?>"
			data-action="view-details"
			data-modal
			data-grouped
			data-type="ajax"
			data-src="<?php echo esc_url_raw( $this->creative_modal_url( $creative_id ) ); ?>"
			data-slug="<?php echo esc_attr( "creative-{$creative_id}" ); ?>"
		><?php Icons::render( $use_icon, $button_text ); ?></button>

		<?php

		return ob_get_clean();
	}

	/**
	 * Return the Copy button HTML.
	 *
	 * Optionally display a textarea with the Creative preview content.
	 *
	 * @since 2.16.0
	 *
	 * @param int     $creative_id The creative ID.
	 * @param string  $button_text The button label. Also used as fallback text when using icons.
	 * @param string  $use_icon If supplied, will use an icon instead of a text. Default `copy`.
	 * @param boolean $display_content Whether to display the textarea or not.
	 * @return string The button HTML.
	 */
	public function copy_button(
		int $creative_id,
		string $button_text = '',
		string $use_icon = 'copy',
		bool $display_content = false
	) : string {

		$creative    = affwp_get_creative( absint( $creative_id ) );
		$button_text = empty( $button_text ) ? __( 'Copy Code', 'affiliate-wp' ) : $button_text;

		$form_classes = $display_content
			? 'affwp-creative-copy-form'
			: 'affwp-creative-copy-form affwp-creative-copy-form--hide-content';

		$button_classes = sprintf(
			'affwp-creatives-list-action affwp-button affwp-button--as-%s',
			empty( $use_icon ) ? 'text' : 'icon'
		);

		ob_start();

		?>

		<div class="affwp-creative-section-code">
			<form class="<?php echo esc_attr( $form_classes ); ?>" name="affiliatewp_copy_form" method="post" action="">
				<div>
					<div class="affwp-creative-section-title"><?php esc_html_e( 'HTML Code', 'affiliate-wp' ); ?></div>
					<p><?php esc_html_e( 'Copy and paste the following', 'affiliate-wp' ); ?></p>
				</div>

				<textarea id="affwp-creative-html-code" readonly name="content" class="affwp-copy-textarea-content"><?php echo wp_kses( $creative->get_preview( 'full', 'referral_url', array( 'class' => '' ) ), $this->content_kses ); ?></textarea>
				<button data-action="copy" type="submit" class="<?php echo esc_attr( $button_classes ); ?>">
					<?php Icons::render( $use_icon, $button_text ); ?>
				</button>
			</form>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Return the Download button HTML.
	 *
	 * @since 2.16.0
	 * @since 2.17.0 Changed download strategy to use native browser downloads `<a download>`.
	 *
	 * @param int    $creative_id The creative ID.
	 * @param string $button_text The button label. Also used as fallback text when using icons.
	 * @param string $use_icon If supplied, will use an icon instead of a text. Default `download`.
	 * @return string The button HTML.
	 */
	private function download_button(
		int $creative_id,
		string $button_text = '',
		string $use_icon = 'download'
	) : string {

		$creative = affwp_get_creative( $creative_id ?? 0 );

		if ( ! $creative || $creative->get_type() === 'text' ) {
			return ''; // It is not a Creative or doesn't allow downloads.
		}

		$button_text = empty( $button_text )
			? __( 'Download', 'affiliate-wp' )
			: $button_text;

		$button_classes = sprintf(
			'button affwp-creatives-list-action affwp-button affwp-button--as-%s affwp-download-button',
			empty( $use_icon ) ? 'text' : 'icon'
		);

		ob_start();

		$creative_type = $creative->get_type();

		$download_filename = strtolower(
			sprintf(
				'%1$s.%2$s',
				sanitize_title( $creative->get_name() ),
				'qr_code' === $creative_type
					? 'png'
					: $creative->get_media_metadata()['ext']
			)
		);
		?>

		<button
			class="<?php echo esc_attr( $button_classes ); ?>"
			data-download="<?php echo esc_html( $download_filename ); ?>"
			data-type="<?php echo esc_attr( $creative_type ); ?>"
			data-href="<?php echo esc_url_raw( 'qr_code' === $creative_type ? '' : $creative->get_image() ); ?>"
		>
			<?php Icons::render( $use_icon, $button_text ); ?>
		</button>

		<?php

		return ob_get_clean();
	}

	/**
	 * Display the category dropdown.
	 *
	 * @since 2.16.0
	 *
	 * @return void
	 */
	public function category_dropdown() : void {

		if ( ! method_exists( affiliate_wp()->creative->groups, 'view' ) ) {
			return;
		}

		affiliate_wp()->creative->groups->view();
	}

	/**
	 * Render the entire UI.
	 *
	 * @since 2.16.0
	 *
	 * @return void
	 */
	public function render() : void {

		// Display the types sub-menu.
		$this->types_menu();

		?>

		<div class="affwp-creatives-filters">
			<?php $this->category_dropdown(); // Display the category dropdown. ?>
			<?php $this->view_switcher(); // Display the view switcher button. ?>
		</div>

		<?php

		// Display creatives using the Grid UI.
		if ( $this->get_user_view_type() === 'grid' ) {

			$this->grid( $this->creatives );
			return;

		}

		// Display creatives using the List UI.
		$this->list( $this->creatives );
	}

	/**
	 * Display creatives in a list format.
	 *
	 * @since 2.16.0
	 *
	 * @param array $creatives Creatives to display.
	 * @return void
	 */
	public function list( array $creatives ) : void {

		if ( empty( $creatives ) ) {
			return; // Nothing to display.
		}

		$order      = $this->get_query_args( 'order' );
		$ordered_by = $this->get_query_args( 'orderby' );

		$sortable_columns = array(
			'name'         => __( 'Name', 'affiliate-wp' ),
			'type'         => __( 'Type', 'affiliate-wp' ),
			'date_updated' => __( 'Last Updated', 'affiliate-wp' ),
		);

		ob_start();

		?>

		<div id="affwp-creatives-view" class="affwp-creatives-table affwp-creatives-view affwp-creatives-view--as-list">

			<div class="affwp-creatives-list-header affwp-creatives-table-row">
				<?php

				foreach ( $sortable_columns as $field_name => $field_text ) {

					$query_args = array(
						'order'   => in_array( $order, array( '', 'desc' ), true ) ? 'asc' : 'desc',
						'orderby' => $field_name,
					);

					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					echo sprintf(
						'<div class="affwp-creatives-table-cell" data-sorted="%s" data-column="%s" data-order="%s"><a href="%s">%s</a></div>',
						$ordered_by === $query_args['orderby'] ? 'true' : 'false',
						$query_args['orderby'],
						$ordered_by === $query_args['orderby'] && 'asc' === $order
							? 'asc'
							: 'desc',
						$this->generate_tab_url_from_current_url( $query_args ),
						esc_html( $field_text )
					);
				} // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

				?>
			</div>

			<?php foreach ( $creatives as $creative ) : ?>
				<?php $this->render_creative_list_row( $creative ); ?>
			<?php endforeach; ?>

		</div>

		<?php

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo ob_get_clean();
	}

	/**
	 * Display creatives in a list format.
	 *
	 * @since 2.16.0
	 *
	 * @param Creative $creative The creative object.
	 * @return void
	 */
	public function render_creative_list_row( Creative $creative ) : void {

		ob_start();

		?>

		<div class="affwp-creative affwp-creatives-list-body affwp-creatives-table-row" data-type="<?php echo esc_attr( $creative->get_type() ); ?>">

				<div data-column="name" class="affwp-creatives-table-cell">
					<div class="affwp-creative-name-wrap">
						<div class="affwp-creative-preview">
							<?php
							echo wp_kses(
								$creative->get_preview(),
								$this->content_kses,
							);
							?>
						</div>

						<div class="affwp-creative-name-details">
							<span class="affwp-creatives-list-column-label"><?php esc_html_e( 'Name', 'affiliate-wp' ); ?></span>
							<?php if ( ! empty( $creative->get_name() ) ) : ?>
								<div class="affwp-creative-name" data-excerpt><?php echo esc_html( $creative->get_name() ); ?></div>
							<?php endif; ?>

							<div class="affwp-creative-id" data-column="id">
								<span><?php echo esc_html( sprintf( 'ID: %d', $creative->ID ) ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div data-column="type" class="affwp-creatives-table-cell">
					<span class="affwp-creatives-list-column-label"><?php esc_html_e( 'Type', 'affiliate-wp' ); ?></span>
					<span class="affwp-creative-type" data-type-label><?php echo esc_html( $creative->get_type_label() ); ?></span>
					<?php if ( $creative->get_type() === 'image' ) : ?>

						<?php

						$image_size = empty( $creative->attachment_id )
							? affwp_get_image_size( $creative->get_image( 'url', 'full' ) )
							: affwp_get_image_size( $creative->attachment_id )

						?>

						<?php if ( ! empty( $image_size ) ) : ?>
							<div class="affwp-creative-size">
								<?php echo esc_html( sprintf( '%d &times; %d', $image_size['width'], $image_size['height'] ) ); ?>
							</div>
						<?php endif; ?>

					<?php endif; ?>
				</div>
				<div data-column="date_updated" class="affwp-creatives-table-cell">
					<span class="affwp-creatives-list-column-label"><?php esc_html_e( 'Last Updated', 'affiliate-wp' ); ?> </span>
					<span class="affwp-creative-last-updated"><?php echo esc_html( $creative->get_date_updated() ); ?></span>
			</div>
			<div class="affwp-creatives-item-actions">
				<?php echo $this->show_details_button( $creative->ID, '', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>
			</div>
		</div>

		<?php

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo ob_get_clean();
	}

	/**
	 * Display creatives in a grid format.
	 *
	 * @since 2.16.0
	 *
	 * @param array $creatives Creatives to display.
	 * @return void
	 */
	public function grid( array $creatives ) : void {

		if ( empty( $creatives ) ) {
			return; // Nothing to display.
		}

		ob_start();

		?>

		<div id="affwp-creatives-view" class="affwp-creatives-view affwp-creatives-view--as-grid">

			<?php foreach ( $creatives as $creative ) : ?>
				<?php $this->render_creative_grid_row( $creative ); ?>
			<?php endforeach; ?>

		</div>

		<?php

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo ob_get_clean();
	}

	/**
	 * Display a single grid row.
	 *
	 * @since 2.16.0
	 *
	 * @param Creative $creative The creative object.
	 * @return void
	 */
	public function render_creative_grid_row( Creative $creative ) : void {

		ob_start();

		?>

			<div class="affwp-creative" data-type="<?php echo esc_attr( $creative->get_type() ); ?>">
				<div
					data-modal
					data-grouped
					data-type="ajax"
					data-src="<?php echo esc_url_raw( $this->creative_modal_url( $creative->ID ) ); ?>"
					data-slug="<?php echo esc_attr( "creative-{$creative->ID}" ); ?>"
				>
					<div class="affwp-creative-preview" data-content="name">
						<?php
						echo wp_kses(
							$creative->get_preview(),
							$this->content_kses,
						);
						?>
					</div>

					<div class="affwp-creative-name">
						<?php echo esc_html( empty( $creative->get_name() ) ? '' : $creative->get_name() ); ?>
					</div>

					<div data-content="type">
						<span class="affwp-creative-type" data-type-label><?php echo esc_html( $creative->get_type_label() ); ?></span>
						<?php if ( $creative->get_type() === 'image' ) : ?>
							<?php

							// But maybe this could also be a method?
							$image_size = empty( $creative->attachment_id )
								? affwp_get_image_size( $creative->get_image( 'url', 'full' ) )
								: affwp_get_image_size( $creative->attachment_id )

							?>

							<?php if ( ! empty( $image_size ) ) : ?>
								<div class="affwp-creative-size">
									<?php echo esc_html( sprintf( '%d &times; %d', $image_size['width'] ?? 300, $image_size['height'] ?? 300 ) ); ?>
								</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>

		<?php

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Properly escaped later.
		echo ob_get_clean();
	}

	/**
	 * Used to show the creative information inside modals.
	 *
	 * @since 2.16.0
	 *
	 * @param int $creative_id The creative ID.
	 * @return string The HTML for preview.
	 */
	private function modal( int $creative_id ) : string {

		$creative = affwp_get_creative( absint( $creative_id ) );

		ob_start();

		?>

		<div id="affwp-creatives-modal" data-selectable data-type="<?php echo esc_attr( $creative->get_type() ); ?>">

			<div class="affwp-creative-section-header">
				<h1 class="affwp-creative-name">
					<?php echo empty( $creative->get_name() ) ? 'Creative' : esc_html( $creative->get_name() ); ?>
				</h1>

				<div class="affwp-creative-subtitle">
					<span><?php echo esc_html( sprintf( 'ID: %d', $creative->ID ) ); ?></span>

					<span class="affwp-creative-separator">&bull;</span>

					<span data-type-label><?php echo esc_html( $creative->get_type_label() ); ?></span>

					<span class="affwp-creative-separator">&bull;</span>

					<span data-column="date_updated">
						<?php esc_html_e( 'Last Updated on', 'affiliate-wp' ); ?>
						<?php echo esc_html( $creative->get_date_updated() ); ?>
					</span>
				</div>
			</div>

			<?php if ( ! empty( $creative->description ) ) : ?>
				<div class="affwp-creative-description"><?php echo wp_kses( $creative->description, $this->content_kses ); ?></div>
			<?php endif; ?>

			<div class="affwp-creative-section-preview">
				<div class="affwp-creative-section-title"><?php esc_html_e( 'Preview', 'affiliate-wp' ); ?></div>
				<div class="affwp-creative-preview">
					<?php if ( 'qr_code' === $creative->type ) : ?>

						<span
							class="affwp-qrcode-modal-preview"
							data-url="<?php echo esc_url_raw( affwp_get_current_user_affiliate_referral_url( $creative->get_url() ) ); ?>"
							data-settings="<?php echo esc_attr( wp_json_encode( $creative->get_qrcode_settings() ) ); ?>">
						</span>

					<?php else : ?>

						<?php

						echo wp_kses(
							$creative->get_preview( 'full' ),
							$this->content_kses,
						);

						?>

					<?php endif; ?>
				</div>

				<?php if ( $creative->get_type() === 'image' ) : ?>

					<?php $metadata = $creative->get_media_metadata(); ?>

					<div class="affwp-creative-details">
						<div>

							<div class="affwp-creative-file-extension">
								<?php echo esc_html( sprintf( '%s %s', esc_html( $metadata['ext'] ), __( 'File', 'affiliate-wp' ) ) ); ?>
							</div>

							<div class="affwp-creative-file-details">

								<?php if ( ! empty( $metadata['size'] ) ) : ?>
									<span class="affwp-creative-image-size"><?php echo esc_html( sprintf( '%d &times; %d', $metadata['size']['width'], $metadata['size']['height'] ) ); ?></span>
								<?php endif; ?>

								<?php if ( ! empty( $metadata['size'] ) && ! empty( $metadata['filesize'] ) ) : ?>
									<span class="affwp-creative-separator">•</span>
								<?php endif; ?>

								<?php if ( ! empty( $metadata['filesize'] ) ) : ?>
									<span class="affwp-creative-file-size"><?php echo esc_html( $metadata['filesize'] ); ?></span>
								<?php endif; ?>

							</div>

						</div>

						<?php echo $this->download_button( $creative->ID, 'Download Image', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>
					</div>

				<?php endif; // Is Image. ?>

				<?php if ( $creative->get_type() === 'qr_code' ) : ?>

					<div class="affwp-creative-details">
						<div>
							<div class="affwp-creative-file-extension">
								<?php esc_html_e( 'PNG File', 'affiliate-wp' ); ?>
							</div>
						</div>

						<button class="affwp-print-button">
							<?php esc_html_e( 'Print', 'affiliate-wp' ); ?>
						</button>

						<?php echo $this->download_button( $creative->ID, 'Download QR Code', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>
					</div>

				<?php endif; ?>
			</div>

			<?php if ( in_array( $creative->get_type(), array( 'text_link', 'image' ), true ) ) : ?>

				<?php echo $this->copy_button( $creative->ID, '', '', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped. ?>

			<?php endif; ?>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Container for modals.
	 *
	 * @since 2.16.0
	 *
	 * @return void
	 */
	public function modal_container() : void {

		if ( ! $this->is_creatives_tab() ) {
			return; // Bail if it is not the creative's page.
		}

		ob_start();

		?>

		<div id="affwp-modal-container"></div>

		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Properly escaped later.
		echo ob_get_clean();
	}

	/**
	 * Handle the modal request while previewing a creative.
	 *
	 * Mainly used to display creative details inside modals.
	 * It must print only HTML.
	 *
	 * @since 2.16.0
	 *
	 * @return void
	 */
	public function handle_modal_request() : void {

		$creative_id = absint( $_GET['creative_id'] ?? 0 );

		// Security check.
		if ( ! check_ajax_referer( 'affwp-preview-creative', 'nonce', false ) ) {

			esc_html_e( 'Please refresh the page and try again.', 'affiliate-wp' );

			exit;
		}

		// Check if user has access to this creative.
		if ( ! affiliate_wp()->creative->groups->affiliate_can_access( $creative_id ) ) {

			esc_html_e( 'Sorry! You do not have access to this creative.', 'affiliate-wp' );

			exit;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo $this->modal( $creative_id );

		exit;

	}

	/**
	 * Handle load more requests.
	 *
	 * @since 2.16.0
	 *
	 * @return void
	 */
	public function handle_infinite_scroll() : string {

		if ( ! check_ajax_referer( 'affwp-creatives-load-more', 'nonce', false ) ) {
			esc_html_e( 'Sorry! Invalid request. Please try again.', 'affiliate-wp' );
			exit;
		}

		$data = json_decode( stripslashes( $_POST['data'] ), true );

		$view_type = in_array( $data['view_type'], $this->view_types, true )
			? $data['view_type']
			: 'list';

		$this->set_current_page( absint( $_POST['page'] ) );
		$this->set_items_per_page( absint( $_POST['items_per_page'] ) );
		$this->set_query_args( $data );
		$this->fetch_creatives();

		if ( empty( $this->get_creatives() ) ) {
			exit;
		}

		foreach ( $this->get_creatives() as $creative ) {

			if ( 'grid' === $view_type ) {
				$this->render_creative_grid_row( $creative );
				continue;
			}

			$this->render_creative_list_row( $creative );

		}

		exit;
	}

	/**
	 * Return the creatives page URL, using the current URL query params.
	 *
	 * @since 2.16.0
	 *
	 * @param string|array $args_to_replace {
	 *     Optional. Query parameters.
	 *
	 *     @type string $type      The creative type.
	 *     @type string $cat       The category.
	 *     @type string $order     The order.
	 *     @type string $orderby   The orderby.
	 *     @type string $view_type The view type.
	 * }
	 * @return string Creative's page URL.
	 */
	public function generate_tab_url_from_current_url( $args_to_replace = array() ) : string {

		$parsed_url = wp_parse_url( esc_url_raw( $_SERVER['REQUEST_URI'] ) );
		$query      = $parsed_url['query'] ?? '';

		parse_str( $query, $query_args );

		foreach ( $query_args as $arg_key => $arg_value ) {

			if ( in_array( $arg_key, $this->allowed_url_args, true ) ) {
				continue;
			}

			unset( $query_args[ $arg_key ] );
		}

		if ( ! empty( $args_to_replace ) ) {
			$query_args = array_unique(
				wp_parse_args( $args_to_replace, $query_args )
			);
		}

		return $this->generate_tab_url( $query_args );

	}

	/**
	 * Parse query argument, to be used to build the page URL.
	 *
	 * @since 2.16.0
	 *
	 * @param string|array $query_args {
	 *     Optional. Query parameters.
	 *
	 *     @type string $type      The creative type.
	 *     @type string $cat       The category.
	 *     @type string $order     The order.
	 *     @type string $orderby   The orderby.
	 *     @type string $view_type The view type.
	 * }
	 *
	 * @return array The filtered args.
	 */
	public function parse_url_args( $query_args = array() ) : array {

		// Empty args don't need to be in the final URL.
		return array_filter(
			array_merge(
				// phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				$query_args = wp_parse_args(
					$query_args,
					array(
						'type'      => '',
						'cat'       => '',
						'order'     => '',
						'orderby'   => '',
						'view_type' => '',
					)
				),
				// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
				array(
					'type' => isset( $query_args['type'] ) && in_array( $query_args['type'], array_keys( affwp_get_creative_types() ), true )
						? sanitize_text_field( $query_args['type'] )
						: '',
					'cat' => absint( $query_args['cat'] ),
					'order' => isset( $query_args['order'] ) && 'asc' === strtolower( sanitize_text_field( $query_args['order'] ) )
						? 'asc'
						: '',
					'orderby' => isset( $query_args['orderby'] )
						? sanitize_text_field( $query_args['orderby'] )
						: '',
					'view_type' => isset( $query_args['view_type'] ) && in_array( $query_args['view_type'], array( 'list', 'grid' ), true )
						? sanitize_text_field( $query_args['view_type'] )
						: '',
				)
				// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
			)
		);
	}

	/**
	 * Builds and returns a creative URL for the Affiliate Area page.
	 *
	 * @since 2.16.0
	 *
	 * @param string|array $query_args {
	 *     Optional. Query parameters.
	 *
	 *     @type string $type      The creative type.
	 *     @type string $cat       The category.
	 *     @type string $order     The order.
	 *     @type string $orderby   The orderby.
	 *     @type string $view_type The view type.
	 * }
	 *
	 * @return string The URL for the creatives tab URL.
	 */
	public function generate_tab_url( $query_args = array() ): string {

		$base_url = affwp_get_affiliate_area_page_url( 'creatives' );

		if ( empty( $query_args ) ) {
			return $base_url;
		}

		$query_args = $this->parse_url_args( $query_args );

		return empty( $query_args )
			? $base_url
			: sprintf(
				'%1$s%2$s%3$s',
				$base_url,
				str_contains( $base_url, '?' ) ? '&' : '?',
				http_build_query( $query_args )
			);

	}

	/**
	 * Returns if a given tab is the active creatives tab for the types submenu.
	 *
	 * @since 2.16.0
	 *
	 * @param string $tab_to_compare A tab to compare with it.
	 * @return bool Whether tab is active or not.
	 */
	public function is_tab_active( string $tab_to_compare ) : bool {

		$active_tab = affwp_filter_creative_type_input();

		if ( empty( $active_tab ) && empty( $tab_to_compare ) ) {
			return true;
		}

		if ( ! empty( $active_tab ) && $active_tab === $tab_to_compare ) {
			return true;
		}

		return false;
	}

	/**
	 * Return an array of query args from the current request.
	 * Filter the args based on a safe list of args, can optionally remove args from then final array.
	 *
	 * @since 2.16.0
	 *
	 * @param array $ignore_list Arguments to ignore, will not be printed, use argument key names.
	 * @return array The query args.
	 */
	public function get_args_from_url( array $ignore_list = array() ) : array {

		$allowed_url_args = $this->allowed_url_args;
		$parsed_url       = wp_parse_url( esc_url_raw( $_SERVER['REQUEST_URI'] ) );
		$query            = $parsed_url['query'] ?? '';

		parse_str( $query, $query_args );

		return array_filter(
			array_unique( $query_args ),
			function( $arg_key ) use ( $allowed_url_args, $ignore_list ) {

				if ( ! in_array( $arg_key, $allowed_url_args, true ) ) {
					return false;
				}

				if ( in_array( $arg_key, $ignore_list, true ) ) {
					return false;
				}

				return true;
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Generate input values from URL query args to be used with forms.
	 *
	 * @since 2.16.0
	 *
	 * @param array $ignore_list Arguments to ignore, will not be printed, use argument key names.
	 * @return void
	 */
	public function add_query_params_to_form( array $ignore_list = array() ) : void {

		foreach ( $this->get_args_from_url( $ignore_list ) as $arg_key => $arg_value ) {

			echo sprintf(
				'<input type="hidden" name="%s" value="%s">',
				esc_attr( $arg_key ),
				esc_attr( $arg_value )
			);
		}
	}
}
