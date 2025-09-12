<?php
/**
 * Affiliates Grouping (Groups) Admin Screen Management
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Affiliates
 * @copyright   Copyright (c) 2014, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.13.0
 *
 * @author      Aubrey Portwood <aportwood@awesomemotive.com>
 */

// phpcs:disable Generic.Commenting.DocComment.MissingShort -- No need to re-document some methods and properties.
// phpcs:disable PEAR.Functions.FunctionCallSignature.FirstArgumentPosition -- Allowing comments in function call lines.
// phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine -- Allowing comments in function call lines.
// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound -- Format for this file is OK.

namespace AffiliateWP\Admin\Groups\Affiliate_Groups;

use AffiliateWP\Groups\Group;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

affwp_require_util_traits( 'data' );

// Meta traits.
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/affiliate-groups/meta/trait-custom-rate.php';
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/affiliate-groups/meta/trait-rate.php';
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/affiliate-groups/meta/trait-rate-type.php';
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/affiliate-groups/meta/trait-default-group.php';
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/affiliate-groups/meta/trait-flat-rate-basis.php';
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/meta/trait-description.php';
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/affiliate-groups/meta/trait-referral-rate.php';
require_once untrailingslashit( AFFILIATEWP_PLUGIN_DIR ) . '/includes/admin/groups/class-management.php';

/**
 * Affiliates Grouping (Groups) Admin Screen Management.
 *
 * @since 2.13.0
 */
final class Management extends \AffiliateWP\Admin\Groups\Management {

	use \AffiliateWP\Utils\Data;

	/* Meta methods cut into traits for easier class organization. */
	use \AffiliateWP\Admin\Affiliates\Groups\Meta\Rate;
	use \AffiliateWP\Admin\Affiliates\Groups\Meta\Rate_Type;
	use \AffiliateWP\Admin\Affiliates\Groups\Meta\Default_Group;
	use \AffiliateWP\Admin\Affiliates\Groups\Meta\Flat_Rate_Basis;
	use \AffiliateWP\Admin\Affiliates\Groups\Meta\Description;
	use \AffiliateWP\Admin\Affiliates\Groups\Meta\Custom_Rate;
	use \AffiliateWP\Admin\Affiliates\Groups\Meta\Referral_Rate;

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $capability = 'manage_affiliates';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $group_type = 'affiliate-group';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $item = 'affiliate';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $menu_slug = 'affiliate-groups';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $object_id_property = 'affiliate_id';

	/** @var string This is documented in includes/admin/groups/class-management.php */
	protected $parent = 'affiliate-wp-affiliates';

	/**
	 * Construct
	 *
	 * @param string $connector_id The Connector ID if there is one.
	 *
	 * @since 2.13.0
	 * @since 2.14.0 Column values for rate-type, flat-rate-basis, and rate are all
	 *                  consolidated in the `includes/admin/affiliates/groups/meta/trait-referral-rate.php`
	 *                  trait `column_value` method.
	 * @since 2.18.0 (Aubrey Portwood) Updated to accept a matching connector ID if there is one.
	 */
	public function __construct( string $connector_id = '' ) {

		$this->item_plural  = __( 'Affiliates', 'affiliate-wp' );
		$this->item_single  = __( 'Affiliate', 'affiliate-wp' );
		$this->menu_title   = __( 'Groups', 'affiliate-wp' );
		$this->page_title   = __( 'Affiliate Groups', 'affiliate-wp' );
		$this->plural_title = __( 'Groups', 'affiliate-wp' );
		$this->single_title = __( 'Group', 'affiliate-wp' );

		// Meta fields (see ./meta/trait-*.php).
		$this->meta_fields = array(

			// Default group.
			'default-group'   => array(
				'main'  => array( $this, 'default_group_main' ),
				'edit'  => array( $this, 'default_group_edit' ),
				'save'  => array( $this, 'default_group_save' ),
				'hooks' => array( $this, 'default_group_hooks' ),
			),

			// Description field.
			'description'     => array(
				'main'          => array( $this, 'description_main' ),
				'edit'          => array( $this, 'description_edit' ),
				'save'          => array( $this, 'description_save' ),
				'column_header' => array( $this, 'description_column_header' ),
				'column_value'  => array( $this, 'description_column_value' ),
			),

			// Assign custom rate.
			'custom-rate'     => array(
				'main' => array( $this, 'custom_rate_main' ),
				'edit' => array( $this, 'custom_rate_edit' ),
			),

			// Rate type.
			'rate-type'       => array(
				'main'          => array( $this, 'rate_type_main' ),
				'edit'          => array( $this, 'rate_type_edit' ),
				'save'          => array( $this, 'rate_type_save' ),
			),

			// Flat rate basis.
			'flat-rate-basis' => array(
				'main'          => array( $this, 'flat_rate_basis_main' ),
				'edit'          => array( $this, 'flat_rate_basis_edit' ),
				'save'          => array( $this, 'flat_rate_basis_save' ),
			),

			// Rate value.
			'rate'            => array(
				'main'          => array( $this, 'rate_main' ),
				'edit'          => array( $this, 'rate_edit' ),
				'save'          => array( $this, 'rate_save' ),
			),

			// Referral Rate Column.
			'referral_rate'   => array(
				'column_header' => array( $this, 'referral_rate_column_header' ),
				'column_value'  => array( $this, 'referral_rate_column_value' ),
			),
		);

		parent::__construct( $connector_id );
	}

	/* phpcs:ignore Squiz.Commenting.FunctionComment.Missing -- This is documented in the parent class. */
	public function hooks() {

		parent::hooks();

		add_action( "wp_ajax_{$this->get_default_group_ajax_action()}", array( $this, 'ajax_set_default_group_response' ) );
	}

	/**
	 * Set Default Group (AJAX).
	 *
	 * @since 2.14.0
	 *
	 * @return void
	 */
	public function ajax_set_default_group_response() : void {

		if ( ! wp_doing_ajax() ) {
			exit;
		}

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], $this->get_set_default_group_nonce_action() ) ) {
			exit;
		}

		if ( ! current_user_can( $this->capability ) ) {

			wp_send_json_error();
			return;
		}

		$group_id = $_GET['group_id'];

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {

			wp_send_json_error();
			return;
		}

		$group = affiliate_wp()->groups->get_group( intval( $group_id ) );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {

			wp_send_json_error();
			return;
		}

		if ( $group->get_type() !== $this->group_type ) {

			wp_send_json_error();
			return;
		}

		$this->set_default_group( $group->get_id() );

		wp_send_json_success();
	}

	/**
	 * The ajax action key for set as default group.
	 *
	 * @since 2.14.0
	 *
	 * @return string
	 */
	private function get_default_group_ajax_action() : string {
		return 'set_default_affiliate_group';
	}

	/* phpcs:ignore Squiz.Commenting.FunctionComment.Missing -- This is documented in the parent class. */
	public function scripts() {

		parent::scripts();

		// Load jQuery and AlpineJS for setting as default group.
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'alpine-js' );
	}

	/* phpcs:ignore Squiz.Commenting.FunctionComment.Missing -- This is documented in the parent class. */
	protected function row_classes( \AffiliateWP\Groups\Group $group ): string {

		return implode(
			' ',
			array(
				$this->is_default_group( $group )
					? 'default'
					: '',
				parent::row_classes( $group ),
			)
		);
	}

	/* phpcs:ignore Squiz.Commenting.FunctionComment.Missing -- This is documented in the parent class. */
	protected function row_actions_after( \AffiliateWP\Groups\Group $group ) : void {

		?>

		<span class="set-default"> |

			<span class="inline">
				<a
					class="editinline default-group"
					style="cursor: pointer;"
					x-data="{

						// Set as the default group.
						async setGroupAsDefault() {

							const $jqel = jQuery( $el );

							// Perform the ajax request.
							let response = await( await fetch( $jqel.data( 'ajax-url' ) ) ).json();

							if ( false === response.success || false ) {

								// There was an error in PHP.
								window.alert( $jqel.data( 'ajax-error-msg' ) );
								return;
							}

							const transitionTiming = 500;

							// Set all groups as not the default, visually.
							jQuery( 'tr.group-row' ).each( function( i, row ) {

								const $row = jQuery( row );

								jQuery( '.status-default-group', $row )
									.fadeOut( transitionTiming / 4 );

								$row.removeClass( 'default' );
							} );

							// Let the user know we switched the default group after a moment.
							setTimeout(
								function() {

									const $row = jQuery( '#group-' + $jqel.data( 'group-id' ) );

									// Fade in the new setting.
									jQuery( '.status-default-group', $row )
										.fadeIn(
											transitionTiming,

											// Once it's faded in...
											function() {

												$row.addClass( 'default' );

												window.alert( $jqel.data( 'default-updated-msg' ) );
											}
										);
								},

								// Yes, wait a bit so it's not too fast.
								transitionTiming
							);
						}
					}"
					x-on:click="setGroupAsDefault"
					data-ajax-error-msg="<?php esc_attr_e( 'There was an issue changing the default affiliate group, please try again.', 'affiliate-wp' ); ?>"
					data-ajax-url="<?php echo esc_url( add_query_arg( array( 'action' => $this->get_default_group_ajax_action(), 'group_id' => $group->get_id(), 'nonce' => $this->get_set_default_group_nonce() ), admin_url( 'admin-ajax.php' ) ) ); ?>"
					data-default-updated-msg="<?php /* Translators: %s is the title of the group. */ echo esc_attr( sprintf( __( '%1$s is now the default affiliate group.', 'affiliate-wp' ), $group->get_title() ) ); ?>"
					data-group-id="<?php echo absint( $group->get_id() ); ?>"
					aria-label="<?php /* Translators: %s is the title of the group. */ echo esc_attr( sprintf( __( "Make '%s' the default group.", 'affiliate-wp' ), $group->get_title() ) ); ?>"
					aria-expanded="false">

					<?php esc_html_e( 'Make&nbsp;Default', 'affiliate-wp' ); ?>
				</a>
			</span>
		</span>

		<?php

		parent::row_actions_after( $group );
	}

	/**
	 * The nonce for the set as default group action.
	 *
	 * @since 2.14.0
	 *
	 * @return string
	 */
	private function get_set_default_group_nonce() : string {
		return wp_create_nonce( $this->get_set_default_group_nonce_action() );
	}

	/**
	 * Get the nonce action for set as default group.
	 *
	 * @since 2.14.0
	 *
	 * @return strin
	 */
	private function get_set_default_group_nonce_action() : string {
		return $this->nonce_action( 'set', 'default-affiliate-group' );
	}

	/**
	 * The description for the name/title input.
	 *
	 * @since 2.14.0
	 *
	 * @return string
	 */
	protected function get_name_description() : string {

		/**
		 * Filter the name/title description.
		 *
		 * @since 2.14.0
		 *
		 * @param string $description The description.
		 * @param string $group_type  The group type.
		 * @param string $item        The item.
		 * @param sting  $menu_slug   The menu slug.
		 */
		return apply_filters(
			'affwp_group_management_name_description',
			__( 'The name is how it appears on your site.', 'affiliate-wp' ),
			$this->group_type,
			$this->item,
			$this->menu_slug
		);
	}
}
