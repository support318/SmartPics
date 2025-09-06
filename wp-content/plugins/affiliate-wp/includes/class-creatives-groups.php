<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Name OK.
/**
 * Group Filtering for the frontend.
 *
 * @since 2.12.0
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Creatives\Categories
 * @copyright   Copyright (c) 2023, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
 * @since       2.13.0 Moved file to new location.
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 *
 * phpcs:disable Squiz.PHP.DisallowMultipleAssignments.Found -- Used for caching.
 * phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Formatting OK.
 * phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- Formatting OK.
 * phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket, PEAR.Functions.FunctionCallSignature.CloseBracketLine -- Allow surrounding code w/out line breaks.
 */

namespace AffiliateWP\Creatives\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

affwp_require_util_traits(
	'nonce',
	'data',
	'select2'
);

#[\AllowDynamicProperties]

/**
 * Group Filtering.
 *
 * @since 2.12.0
 */
final class Groups {

	use \AffiliateWP\Utils\Nonce;
	use \AffiliateWP\Utils\Data;
	use \AffiliateWP\Utils\Select2;

	/**
	 * Nonce name/action for filtering creatives in this UI.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	private $filter_creatives_nonce = '';

	/**
	 * Plural name for the group.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	private $group_plural = '';

	/**
	 * Name for the group type.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	private $group_type = 'creative-category';

	/**
	 * The object property where the item stores it's ID.
	 *
	 * @since 2.12.0
	 *
	 * @var string
	 */
	protected $object_id_property = 'creative_id';

	/**
	 * Construct
	 *
	 * @since 2.12.0
	 */
	public function __construct() {

		$this->group_plural = __( 'Categories', 'affiliate-wp' );

		$this->filter_creatives_nonce = $this->nonce_action( 'filter', 'creatives' );

		$this->hooks();
	}

	/**
	 * Filter the creatives shown in the Affiliate Area.
	 *
	 * @since 2.13.0
	 * @since 2.16.0 Method now check for a cat parameter in args.
	 *
	 * @param array $args Arguments used to show creatives.
	 *
	 * @return array
	 */
	public function filter_affiliate_area_creatives( array $args ) : array {

		$group_id = $args['cat'] ?? 0;

		// No valid group selected.
		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {

			// Just get the creatives the affiliate has access too...
			$creatives_affiliate_has_access_to = $this->get_creatives_current_affiliate_has_access_to();

			return array_merge(
				$args,
				array(
					'creative_id' => empty( $creatives_affiliate_has_access_to )

						// Show no creatives if there are none.
						? array( 0 )

						// Show creatives that the affiliate has access to.
						: $creatives_affiliate_has_access_to,
				)
			);
		}

		// Filter the creatives in the selected group_id that the affiliate has access to...
		$creatives_in_group_affiliate_has_access_to = $this->filter_out_creatives_current_affiliate_has_no_access_to(

			// Get all the creatives in the selected group to be filtered...
			affiliate_wp()->connections->get_connected(
				'creative', // Get creatives.
				'group', // Where groups.
				$group_id // Are connected to this group ID.
			)
		);

		return array_merge(
			$args,
			array(
				'creative_id' => empty( $creatives_in_group_affiliate_has_access_to )

					// Show no creatives if there were none left..
					? array( 0 )

					// Show creatives that the affiliate can see (for the selected group).
					: $creatives_in_group_affiliate_has_access_to,
			)
		);
	}

	/**
	 * Is the affiliate in affiliate groups or affiliate ids?
	 *
	 * @since 2.13.0
	 *
	 * @param array $affiliate_groups The affiliate groups.
	 * @param array $affiliate_ids    A list of affiliate ids.
	 *
	 * @return bool
	 */
	private function is_current_affiliate_in( array $affiliate_groups, array $affiliate_ids ) : bool {

		// If the Logged in Affiliate ID is not...
		return in_array(

			// The logged in Affiliate ID, specifically.
			intval( affwp_get_affiliate_id( get_current_user_id() ) ),

			// Is not in the list of affiliate ID's from...
			array_map(
				'intval',
				array_unique(
					array_merge(

						// Individual affiliates assigned to the creative....
						$affiliate_ids,

						// Affiliates from affiliate groups assigned to the creative...
						$this->get_affiliates_from_affiliate_groups(
							$affiliate_groups
						)
					)
				)
			),
			true
		);
	}

	/**
	 * Get affiliates (connected) assigned to creative.
	 *
	 * @since 2.13.0
	 *
	 * @param int $creative_id The creative id.
	 *
	 * @return array
	 */
	private function get_affiliates_assigned_to_creative( int $creative_id ) : array {

		return affiliate_wp()->connections->get_connected(
			'affiliate',
			'creative',
			$creative_id
		);
	}

	/**
	 * Get connectee affiliates in groups (affiliate grouos).
	 *
	 * @since 2.13.0
	 *
	 * @param array $group_ids The group ids.
	 *
	 * @return array
	 */
	private function get_affiliates_from_affiliate_groups( array $group_ids ) : array {

		$affiliates = array();

		// Go over all the affiliate groups associated with the creative.
		foreach ( $group_ids as $group_id ) {

			if ( 'affiliate-group' !== affiliate_wp()->groups->get_group_type( $group_id ) ) {
				continue; // Do not include affiliates in this group.
			}

			foreach (

				// Get all the affiliates connected to the affiliate group.
				affiliate_wp()->connections->get_connected(
					'affiliate',
					'group',
					$group_id
				) as $affiliate_id
			) {

				if ( false === affwp_get_affiliate( $affiliate_id ) ) {
					continue; // Not an affiliate, do not include.
				}

				// Add the affiliate in the group to the list.
				$affiliates[] = $affiliate_id;

			}
		}

		return $affiliates;
	}

	/**
	 * Get all the groups (objects).
	 *
	 * @since 2.12.0
	 *
	 * @return mixed The groups, or the count of the groups.
	 *
	 * @throws \InvalidArgumentException If you do not supply a proper count parameter.
	 */
	private function get_all_non_empty_groups() {

		static $cache = null;

		if ( ! is_null( $cache ) ) {
			return $cache;
		}

		$groups = affiliate_wp()->groups->get_groups(
			array(
				'fields' => 'objects',
				'type'   => $this->group_type,
			),
			false
		);

		if ( ! is_array( $groups ) ) {
			return $cache = array(); // We couldn't use the DB API to get groups, fail gracefully.
		}

		if (
			! isset( $groups[0] ) ||
			! is_a(
				current( $groups ),
				'\AffiliateWP\Groups\Group'
			)
		) {
			return $cache = array(); // The first item should be a group, something wen't wrong, fail gracefully.
		}

		// Filter out non-empty groups (groups without connections to creatives).
		return $cache = array_filter(
			$groups,
			function( $group ) {

				if ( ! isset( $group->group_id ) || ! $this->is_numeric_and_gt_zero( $group->group_id ) ) {
					return false; // Broken group (likely not to happen).
				}

				$connected = array_filter(
					affiliate_wp()->connections->get_connected(
						'creative',
						'group',
						$group->group_id
					),
					function( $creative_id ) {

						$creative = affwp_get_creative( $creative_id );

						return isset( $creative->status )
						? 'active' === $creative->status
						: false;
					}
				);

				if ( ! is_array( $connected ) ) {
					return false; // Broken connetions, fail gracefully.
				}

				return count( $connected ) > 0 ? true : false;
			}
		);
	}

	/**
	 * Hooks
	 *
	 * @since  2.12.0
	 */
	private function hooks() {

		// Filter creatives.
		add_filter( 'affwp_affiliate_dashboard_creatives_args', array( $this, 'filter_affiliate_area_creatives' ), 10, 2 );

		if ( is_admin() ) {
			return; // The hooks below don't need to be loaded in admin, let's not add them needlessly.
		}

		// Filter navigation and drop-down.
		add_action( 'template_redirect', array( $this, 'register_connectables' ) );
		add_action( 'template_redirect', array( $this, 'redirect_with_selected_filter' ) );

		// Only when the portal plugin is active...
		if ( class_exists( 'AffiliateWP_Affiliate_Portal_Requirements_Check' ) ) {
			add_filter( 'affwp_creatives_view_query_args', array( $this, 'filter_portal_creatives' ) );
		}
	}

	/**
	 * Filter portal creatives by affiliate.
	 *
	 * @since 2.13.0
	 *
	 * @param array $args The arguments the portal uses to show creatives.
	 *
	 * @return array
	 */
	public function filter_portal_creatives( array $args ) : array {

		// Won't be registered at this point if this plugin loads first.
		affwp_register_connectables();

		// Get a list of the creatives we would have shown (possibly filtered by category already in the portal codebase)...
		$creative_ids = affiliate_wp()->creatives->get_creatives(
			array_merge(
				$args,

				// But just get the ids...
				array(
					'fields' => 'ids',

					// Yes, make sure and get all of them for all pages we would show.
					'number' => -1,
				)
			)
		);

		// Filter out creatives (that we would have shown) that the affiliate does not have access to...
		$creatives_affiliate_has_access_to = $this->filter_out_creatives_current_affiliate_has_no_access_to( $creative_ids );

		return array_merge(
			$args,
			array(

				// Specify specific creatives to show...
				'creative_id' => empty( $creatives_affiliate_has_access_to )
					? array( 0 )
					: $creatives_affiliate_has_access_to,
			)
		);
	}

	/**
	 * Filter creatives to those that the affiliate has access to.
	 *
	 * @since 2.13.0
	 *
	 * @param array $creative_ids Creative ids.
	 *
	 * @return array
	 */
	private function filter_out_creatives_current_affiliate_has_no_access_to( array $creative_ids ) : array {

		$creatives_affiliate_has_access_to = $this->get_creatives_current_affiliate_has_access_to();

		return array_filter(
			$creative_ids,
			function( $creative_id ) use ( $creatives_affiliate_has_access_to ) {

				// Keep creatives the affiliate has access to.
				return in_array( intval( $creative_id ), array_map( 'intval', $creatives_affiliate_has_access_to ), true );
			}
		);
	}

	/**
	 * Get creatives current affiliate has access to.
	 *
	 * @since 2.13.0
	 *
	 * @return array
	 */
	private function get_creatives_current_affiliate_has_access_to() : array {

		static $cache = null;

		if ( is_array( $cache ) ) {
			return $cache;
		}

		return $cache = array_filter(

			// Get all the creatives...
			affiliate_wp()->creatives->get_creatives(
				array(
					'fields' => 'ids',

					// Yes, all of them that way we can report that back to the arguments a full list.
					'number' => -1,
					'status' => 'active',
				)
			),

			// Filter creatives affiliate has access to of all of these.
			function ( $creative_id ) {

				$affiliate_groups = $this->get_affiliate_groups_assigned_to_creative( $creative_id );
				$affiliate_ids    = $this->get_affiliates_assigned_to_creative( $creative_id );

				if ( empty( $affiliate_groups ) && empty( $affiliate_ids ) ) {

					// The creative isn't restricted to affiliate groups or individual affiliates, show it.
					return true;
				}

				// The creative must be limited to at least one affiliate group and/or an individual affiliate...
				return $this->is_current_affiliate_in( $affiliate_groups, $affiliate_ids )
					? true // The affiliate is in an affiliate group or directly assigned, show creative.
					: false; // The affiliates id was not found in affiliate groups nor directly assigned, hide creative.
			}
		);
	}

	/**
	 * Does the current affiliate have access to a creative?
	 *
	 * @since 2.13.1
	 *
	 * @param int $creative_id The creative id.
	 *
	 * @return bool
	 */
	public function affiliate_can_access( int $creative_id ) : bool {

		if (
			function_exists( 'get_current_screen' ) &&
			isset( get_current_screen()->base ) &&
			(
				strpos( get_current_screen()->base, 'affiliate-wp' ) !== false ||
				strpos( get_current_screen()->base, 'post' ) !== false
			)
		) {
			// If we are in the admin, they must be able to manage creatives in the admin to see it.
			return current_user_can( 'manage_creatives' );
		}

		$creative = affwp_get_creative( $creative_id );

		if ( ! is_a( $creative, '\AffWP\Creative' ) ) {
			return false; // Not a creative, fail gracefully.
		}

		return in_array(
			$creative_id,
			$this->get_creatives_current_affiliate_has_access_to(),
			true
		);
	}

	/**
	 * Get affiliate (ids) assigned (connected) to a creative.
	 *
	 * @since 2.13.0
	 *
	 * @param int $creative_id The creative id.
	 *
	 * @return array
	 */
	public function get_affiliate_groups_assigned_to_creative( int $creative_id ) : array {

		return affiliate_wp()->groups->filter_groups_by_type(
			affiliate_wp()->connections->get_connected(
				'group',
				'creative',
				$creative_id
			),
			'affiliate-group'
		);
	}

	/**
	 * Catch the POST then redirect with &cat= set.
	 *
	 * @since  2.12.0
	 *
	 * @return void If we are not filtering.
	 */
	public function redirect_with_selected_filter() {

		if (
			! isset( $_POST['filter-creative-category'] ) ||

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- We're just checking if it's a valid ID.
			! $this->is_numeric_and_gt_zero( $_POST['filter-creative-category'] )
		) {
			return;
		}

		if ( ! $this->verify_nonce_action( $this->filter_creatives_nonce, 'filter-creatives' ) ) {
			return; // Nonce expired.
		}

		check_admin_referer(
			$this->nonce_action( $this->filter_creatives_nonce, 'filter-creatives' ),
			$this->nonce_action( $this->filter_creatives_nonce, 'filter-creatives' )
		);

		// Do a re-direct where &cat=int is set, that way POST is not re-submitted and NONCE can't expire.
		wp_safe_redirect(
			add_query_arg(
				array_filter(
					array(
						'tab'       => 'creatives',
						'cat'       => intval( $_POST['filter-creative-category'] ),
						'type'      => affwp_filter_creative_type_input( 'POST' ),
						'order'     => in_array( trim( strtolower( (string) filter_input( INPUT_POST, 'order' ) ) ), array( '', 'desc' ), true )
							? 'desc'
							: 'asc',
						'orderby'   => filter_input( INPUT_POST, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? 'date_updated',
						'view_type' => in_array( filter_input( INPUT_POST, 'view_type' ), array( 'list', 'grid' ), true )
							? filter_input( INPUT_POST, 'view_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
							: '',
					)
				),
				get_the_permalink()
			)
		);

		exit;
	}

	/**
	 * Register connectables for creatives a groups (for the frontend).
	 *
	 * @since  2.12.0
	 *
	 * @throws \Exception If we cannot register the connectable.
	 *
	 * @return void If we already have the connectables registered.
	 */
	public function register_connectables() {
		affwp_register_connectables();
	}

	/**
	 * View
	 *
	 * @since  2.12.0
	 */
	public function view() {

		$groups = $this->get_all_non_empty_groups();

		if ( empty( $groups ) ) {
			return; // No groups to select.
		}

		?>

		<form
			class="affwp-category-dropdown"
			method="post"
			action="<?php echo esc_url( add_query_arg( 'tab', 'creatives', get_the_permalink() ) ); ?>">

			<div>
				<select name="filter-creative-category">

					<option value="">
						<?php

						// Translators: %s is the translated name of the group, e.g. Categories.
						echo esc_html( sprintf( __( 'All %s', 'affiliate-wp' ), $this->group_plural ) );

						?>
					</option>

					<?php foreach ( $groups as $group ) : ?>

						<?php $group_id = $group->get_id(); ?>

						<option
						<?php echo esc_attr( intval( filter_input( INPUT_GET, 'cat', FILTER_SANITIZE_NUMBER_INT ) ) === intval( $group_id ) ? 'selected' : '' ); ?>
						value="<?php echo absint( $group_id ); ?>">
						<?php echo esc_html( wp_trim_words( $group->get_title(), 10 ) ); ?>
					</option>
				<?php endforeach; ?>

			</select>

			<?php do_action( 'affwp_filter_creative_category_dropdown' ); ?>

			<input type="submit" class="button" value="<?php esc_html_e( 'Filter', 'affiliate-wp' ); ?>">
		</div>

		<?php

		wp_nonce_field(
			$this->nonce_action( $this->filter_creatives_nonce, 'filter-creatives' ),
			$this->nonce_action( $this->filter_creatives_nonce, 'filter-creatives' )
		);

		?>

		</form>

		<?php
	}
}
