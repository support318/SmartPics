<?php
/**
 * Affiliates Grouping Meta: Default Group
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

namespace AffiliateWP\Admin\Affiliates\Groups\Meta;

affwp_require_util_traits( 'data' );

/**
 * Affiliate Group: Default Group: Meta
 *
 * Intended to be used on \AffiliateWP\Admin\Groups\Management.
 *
 * @since 2.13.0
 */
trait Default_Group {

	use \AffiliateWP\Utils\Data;

	/**
	 * Error message for when we require the trait be used on a manager object.
	 *
	 * @since 2.14.0
	 *
	 * @var string
	 */
	private $must_be_manager_error_message = 'This trait method can only be called on \AffiliateWP\Admin\Groups\Management object.';

	/**
	 * The default group meta key.
	 *
	 * @since 2.14.0
	 *
	 * @var string
	 */
	private $default_group_meta_key = 'default-group';

	/**
	 * Default Group Field: Description.
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	private function get_default_group_description() : string {
		return __( 'Set this group as the default group for new affiliates.', 'affiliate-wp' );
	}

	/**
	 * Default Group Field: Edit.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $group Group.
	 *
	 * @return string Markup.
	 */
	public function default_group_edit( \AffiliateWP\Groups\Group $group ) : string {

		$default_group = $group->get_meta( $this->default_group_meta_key, false );

		ob_start();

		?>

		<tr class="form-field term-name-wrap">

			<th scope="row">
				<?php esc_html_e( 'Default Group', 'affiliate-wp' ); ?>
			</th>

			<td>
				<p>
					<label for="default-group">
						<?php $this->default_group_input( $default_group ); ?>&nbsp;<?php echo esc_html( $this->get_default_group_description() ); ?>
					</label>
				</p>
			</td>
		</tr>

		<?php

		return ob_get_clean();
	}

	/**
	 * Default Group Field: Main/Add.
	 *
	 * @since 2.13.0
	 *
	 * @return string Markup.
	 */
	public function default_group_main() : string {

		ob_start();

		?>

		<div class="form-field term-name-wrap">
			<p>
				<label for="default-group" id="default-group-description">
					<?php $this->default_group_input(); ?>&nbsp;<?php echo esc_html( $this->get_default_group_description() ); ?>
				</label>
			</p>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Default Group Field: Save.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $edited_group Group object.
	 *
	 * @return bool If the default-group was saved.
	 *
	 * @throws \Exception If this isn't used on a \AffiliateWP\Admin\Groups\Management class.
	 */
	public function default_group_save( \AffiliateWP\Groups\Group $edited_group ) : bool {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( $this->must_be_manager_error_message );
		}

		// The POST is telling us the edited group should be the default.
		if ( 'on' === filter_input( INPUT_POST, $this->default_group_meta_key, FILTER_UNSAFE_RAW ) ) {

			// Set it as the default group (un-setting all the others).
			$this->set_default_group( $edited_group->get_id() );

			// Confirm.
			return true === $edited_group->get_meta( $this->default_group_meta_key, false );
		}

		// Set the edited group as NOT the default group (leave other's alone).
		$edited_group->update(
			array(
				'type' => $this->group_type,
				'meta' => array(
					$this->default_group_meta_key => null,
				),
			)
		);

		// Confirm.
		return 'unset' === $edited_group->get_meta( $this->default_group_meta_key, 'unset' );
	}

	/**
	 * Is a group the default group?
	 *
	 * @since 2.14.0
	 *
	 * @param \AffiliateWP\Groups\Group $group The group object.
	 *
	 * @return bool
	 *
	 * @throws \Exception If this isn't used on a \AffiliateWP\Admin\Groups\Management class.
	 */
	protected function is_default_group( \AffiliateWP\Groups\Group $group ) : bool {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( $this->must_be_manager_error_message );
		}

		return true === $group->get_meta( $this->default_group_meta_key, false ) &&
			$group->get_type() === $this->group_type;
	}

	/**
	 * Set the default group.
	 *
	 * @since 2.14.0
	 *
	 * @param int $group_id The group id.
	 *
	 * @return void Makes the changes, but you should verify them on your own.
	 *
	 * @throws \Exception If this isn't used on a \AffiliateWP\Admin\Groups\Management class.
	 */
	protected function set_default_group( int $group_id ) : void {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( $this->must_be_manager_error_message );
		}

		if ( ! current_user_can( $this->capability ) ) {
			return; // You must be the right user to make any modifications.
		}

		$group = affiliate_wp()->groups->get_group( $group_id );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {
			return; // Refuse to modify a non-group.
		}

		if ( $group->get_type() !== $this->group_type ) {
			return; // Refuse to modify group of a different type.
		}

		// Set the group as the default group.
		$group->update(
			array(
				'type' => $this->group_type,
				'meta' => array(
					$this->default_group_meta_key => true,
				),
			)
		);

		// Unset all the other groups as the default group.
		foreach ( affiliate_wp()->groups->get_groups(
			array(
				'type'   => $this->group_type,
				'fields' => 'objects',
				'number' => apply_filters( 'affwp_unlimited', -1, 'unset_all_other_groups_as_default_group' ),
			)
		) as $group_to_unset ) {

			if ( $group->get_id() === $group_to_unset->get_id() ) {
				continue; // Skip the edited group, we already configured it as the default group.
			}

			// Set all the other groups as disconnected.
			$group_to_unset->update(
				array(
					'type' => $this->group_type,
					'meta' => array(
						$this->default_group_meta_key => null,
					),
				)
			);
		}
	}

	/**
	 * Input
	 *
	 * @since 2.13.0
	 *
	 * @param bool $default_group The default-group value for the input.
	 *
	 * @return void
	 */
	private function default_group_input( bool $default_group = false ) : void {

		?>

		<input
			name="default-group"
			id="default-group"
			type="checkbox"
			<?php if ( true === $default_group ) : ?>
				checked="checked"
			<?php endif; ?>
			aria-required="false"
			aria-describedby="default-group-description"><?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterOpen,Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- We want to eliminate the tabs from showing as an extra space.
	}

	/**
	 * Hooks.
	 *
	 * @since 2.13.0
	 */
	public function default_group_hooks() : void {

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'affwp_group_management_after_row_title', array( $this, 'show_default_group' ), 10, 2 );
		add_action( 'affwp_group_connector_after_group_option', array( $this, 'show_default_group_option_is_default' ), 10, 2 );
		add_action( 'affwp_group_management_after_column_group_title', array( $this, 'show_default_group_in_connector_column' ), 10, 2 );
		add_filter( 'affwp_group_management_after_column_group_title_kses', array( $this, 'wp_kses_show_default_group_in_connector_column' ) );
		add_filter( 'affwp_group_connector_group_is_selected_new_item', array( $this, 'set_default_affiliate_group_on_new_affiliates' ), 10, 4 );
	}

	/**
	 * Show the default group next to the group name.
	 *
	 * @since 2.13.0
	 *
	 * @param string $group_title The group title.
	 * @param string $group_type  The group type.
	 *
	 * @return string Markup
	 *
	 * @throws \Exception If this isn't used on a \AffiliateWP\Admin\Groups\Management class.
	 */
	public function show_default_group_in_connector_column( string $group_title, string $group_type ) : string {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( $this->must_be_manager_error_message );
		}

		if ( $this->group_type !== $group_type ) {
			return $group_title; // Not our group type.
		}

		global $wpdb;

		$group_id = $wpdb->get_var(
			$wpdb->prepare(

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We need to avoid tick marks around the table name.
				str_replace(
					'{table_name}',

					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- We need to avoid tick marks around the table name.
					affiliate_wp()->groups->table_name,
					'SELECT group_id FROM {table_name} WHERE type = %s AND title = %s'
				),
				$group_type,
				wp_strip_all_tags( $group_title )
			)
		);

		if ( ! $this->is_numeric_and_gt_zero( $group_id ) ) {
			return $group_title; // Can't get group ID by title.
		}

		$group = affiliate_wp()->groups->get_group( $group_id );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {
			return $group_title; // Can't get group object.
		}

		if ( true !== $group->get_meta( $this->default_group_meta_key ) ) {
			return $group_title;
		}

		return sprintf(
			"<span title='%s'>{$group_title}</span>",
			esc_attr( __( 'This group is the default group.', 'affiliate-wp' ) )
		);
	}

	/**
	 * Allow more HTML when showing the connector column value.
	 *
	 * @since 2.13.0
	 *
	 * @param array $allowed_html Allowed HTML.
	 *
	 * @return array
	 */
	public function wp_kses_show_default_group_in_connector_column( array $allowed_html ) : array {

		return array_merge(
			array(
				'span' => array(
					'title' => true,
				),
			),
			$allowed_html
		);
	}

	/**
	 * Show what group is the default group when adding/editing affiliates via the connector.
	 *
	 * @since 2.13.0
	 *
	 * @param \AffiliateWP\Groups\Group $group      Group object.
	 * @param string                    $group_type Group type.
	 *
	 * @return void If it's not our affiliate type.
	 *              If the group is not the default group.
	 *
	 * @throws \Exception If this isn't used on a \AffiliateWP\Admin\Groups\Management class.
	 */
	public function show_default_group_option_is_default( \AffiliateWP\Groups\Group $group, string $group_type ) : void {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( $this->must_be_manager_error_message );
		}

		if ( $this->group_type !== $group_type ) {
			return; // Not our group type.
		}

		if ( true !== $group->get_meta( $this->default_group_meta_key, false ) ) {
			return; // Not the default group.
		}

		// phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen, Squiz.PHP.EmbeddedPhp.ContentAfterEnd, Squiz.PHP.EmbeddedPhp.NoSemicolon, Squiz.PHP.EmbeddedPhp.ContentBeforeOpen -- We want to suppress whitespace.
		?>&nbsp;&mdash;&nbsp;<?php esc_html_e( 'Default', 'affiliate-wp' ) ?><?php
	}

	/**
	 * Set the selected default group in the connector selector to our default group.
	 *
	 * @since 2.13.0
	 *
	 * @param bool   $default    The default, usually false.
	 * @param int    $group_id   The group's ID.
	 * @param string $group_type The group type of the connector.
	 * @param string $item       The item of the connector.
	 *
	 * @return bool `true` if the group is set as the default group, causing it to be selected in the selector from the connector.
	 *
	 * @throws \Exception If this isn't used on a \AffiliateWP\Admin\Groups\Management class.
	 */
	public function set_default_affiliate_group_on_new_affiliates( bool $default, int $group_id, string $group_type, string $item ) : bool {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( $this->must_be_manager_error_message );
		}

		if ( $this->group_type !== $group_type ) {
			return $default; // Not our group type (affiliate-group), don't mess with others.
		}

		if ( 'affiliate' !== $item ) {
			return $default; // Don't set for other connector items.
		}

		$group = affiliate_wp()->groups->get_group( $group_id );

		if ( ! is_a( $group, '\AffiliateWP\Groups\Group' ) ) {
			return $default;
		}

		// If this group is set as the default group, automatically select it via the connector.
		return true === $group->get_meta( $this->default_group_meta_key, false );
	}

	/**
	 * Show default group next to group title.
	 *
	 * @since 2.13.0
	 *
	 * @param string                    $group_type Group title.
	 * @param \AffiliateWP\Groups\Group $group      The group object.
	 *
	 * @throws \Exception If this isn't used on a \AffiliateWP\Admin\Groups\Management class.
	 */
	public function show_default_group( string $group_type, \AffiliateWP\Groups\Group $group ) : void {

		if ( ! is_a( $this, '\AffiliateWP\Admin\Groups\Management' ) ) {
			throw new \Exception( $this->must_be_manager_error_message );
		}

		if ( $this->group_type !== $group_type ) {
			return;
		}

		echo wp_kses(

			// We show and hide this using JavaScript.
			sprintf(
				'<strong class="status-default-group" title="%1$s">&nbsp;&mdash;&nbsp;%2$s</strong>',
				__( 'New affiliates, by default, will be assigned to this group during registration and adding new affiliates via the admin.', 'affiliate-wp' ),
				__( 'Default', 'affiliate-wp' )
			),
			array(
				'strong' => array(
					'title' => true,
					'class' => true,
				),
			)
		);
	}
}
