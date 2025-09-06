<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WPF_AffiliateWP extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'affiliate-wp';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'Affiliate WP';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/affiliates/affiliate-wp/';

	/**
	 * Init
	 * Gets things started.
	 *
	 * @since   1.0
	 */
	public function init() {

		// Settings fields.
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
		add_action( 'affwp_edit_affiliate_end', array( $this, 'edit_affiliate' ) );
		add_action( 'affwp_pre_update_affiliate', array( $this, 'save_edit_affiliate' ), 10, 3 );

		add_action( 'affwp_insert_affiliate', array( $this, 'add_affiliate' ), 15 );
		add_action( 'affwp_update_affiliate', array( $this, 'update_affiliate' ), 5 );
		add_action( 'affwp_affiliate_deleted', array( $this, 'affiliate_deleted' ), 10, 3 );
		add_action( 'affwp_set_affiliate_status', array( $this, 'affiliate_status_updated' ), 10, 3 );

		// Accepted referrals.
		add_action( 'affwp_referral_accepted', array( $this, 'referral_accepted' ), 10, 2 );
		add_action( 'wpf_fluent_forms_post_submission', array( $this, 'maybe_handle_fluent_forms_referral' ), 10, 5 );

		add_filter( 'wpf_get_user_meta', array( $this, 'get_user_meta' ), 10, 2 );
		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );

		// Tag linking.
		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		// Affiliate Groups Tag Linking.
		add_action( 'affwp_group_managment_meta_fields', array( $this, 'group_managment_meta_fields' ), 10, 5 );

		// Batch operations.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_affiliatewp_init', array( $this, 'batch_init_affiliates' ) );
		add_action( 'wpf_batch_affiliatewp', array( $this, 'batch_step_affiliates' ) );
		add_action( 'wpf_batch_affiliatewp_referrals_init', array( $this, 'batch_init_referrals' ) );
		add_action( 'wpf_batch_affiliatewp_referrals', array( $this, 'batch_step_referrals' ) );
	}

	/**
	 * Register Settings
	 * Registers additional AWP settings.
	 *
	 * @since unknown
	 * @since 3.43.8  Removed group link with tag settings. These were moved to the AWP group management screen.
	 *
	 * @see WPF_AffiliateWP::group_managment_meta_fields()
	 *
	 * @param  array $settings The settings.
	 * @param  array $options The options.
	 *
	 * @return array $settings The settings.
	 */
	public function register_settings( $settings, $options ) {

		$settings['awp_header'] = array(
			'title'   => __( 'AffiliateWP Integration', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['awp_apply_tags'] = array(
			'title'   => __( 'Apply Tags - Registration', 'wp-fusion' ),
			'desc'    => __( 'Apply these tags to new affiliates registered through AffiliateWP.', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		$settings['awp_apply_tags_first_referral'] = array(
			'title'   => __( 'Apply Tags - First Referral', 'wp-fusion' ),
			'desc'    => __( 'Apply these tags when affiliates get their first referral.', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		if ( property_exists( wp_fusion()->integrations, 'woocommerce' ) ) {

			$settings['awp_apply_tags_customers'] = array(
				'title'   => __( 'Apply Tags - Customers', 'wp-fusion' ),
				'desc'    => __( 'Apply these tags to new WooCommerce customers who signed up via an affiliate link.', 'wp-fusion' ),
				'std'     => array(),
				'type'    => 'assign_tags',
				'section' => 'integrations',
			);

		}

		// Statuses.

		if ( function_exists( 'affwp_get_affiliate_statuses' ) ) {

			foreach ( affwp_get_affiliate_statuses() as $slug => $label ) {

				$settings[ "awp_apply_tags_{$slug}" ] = array(
					'title'   => sprintf( __( 'Apply Tags - %s', 'wp-fusion' ), $label ),
					'desc'    => sprintf( __( 'Apply these tags when an afffiliate\'s status is set to %s.', 'wp-fusion' ), strtolower( $label ) ),
					'type'    => 'assign_tags',
					'section' => 'integrations',
				);

			}
		}

		$settings['awp_apply_tags_deleted'] = array(
			'title'   => __( 'Apply Tags - Deleted', 'wp-fusion' ),
			'desc'    => __( 'Apply these tags when affiliates are deleted.', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		// Linked tags.

		$settings['awp_tag_activate_link'] = array(
			'title'   => __( 'Link Tag - Affilate Activation', 'wp-fusion' ),
			'desc'    => __( 'When this tag is applied, an affiliate account will be created for the user and activated. If the tag is removed, the affiliate account will be deactivated.', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'integrations',
			'limit'   => 1,
		);

		return $settings;
	}


	/**
	 * Group Management Meta Fields
	 * Adds WPF settings to the group meta fields.
	 *
	 * @since 3.43.8
	 *
	 * @param array  $meta_fields       The meta fields.
	 * @param int    $group_type        The group type.
	 * @param int    $item              The item.
	 * @param int    $menu_slug         The menu slug.
	 * @param object $management_object The management object.
	 *
	 * @return array The meta fields.
	 */
	public function group_managment_meta_fields( array $meta_fields, $group_type, $item, $menu_slug, $management_object ): array {

		// WP Fusion link with tag field.
		$meta_fields['wpf-settings'] = array(
			// Add wpf field to the group creation and edit screens.
			'main'          => array( $this, 'main_wpf_settings_field' ),
			'edit'          => array( $this, 'edit_wpf_settings_field' ),
			'save'          => array( $this, 'save_wpf_settings_field' ),
			'column_header' => array( $this, 'wpf_column_header' ),
			'column_value'  => array( $this, 'wpf_column_value' ),
		);

		return $meta_fields;
	}

	/**
	 * WPF Column Header
	 * Adds the WPF logo to the group column header.
	 *
	 * @since 3.43.8
	 *
	 * @param int $position The column position.
	 *
	 * @return mixed The column HTML output.
	 */
	public function wpf_column_header( $position ) {

		ob_start();

		?>

		<th scope="col"class="column-posts manage-column wpf-settings" style="width: 14; text-align: center; padding-left: 2%;">
			<?php echo wpf_logo_svg( 14 ); ?>
		</th>

		<?php

		return ob_get_clean();
	}

	/**
	 * WPF Column Value
	 * Adds a dashicon to the WPF column to indicate if the group has a link tag.
	 *
	 * @since 3.43.8
	 *
	 * @param object $group The group object.
	 *
	 * @return mixed The column HTML output.
	 */
	public function wpf_column_value( $group ) {

		$linked_tag = wpf_get_option( 'awp_group_tag_' . $group->group_id );

		ob_start();

		?>

			<td
				class="rate column-wpf-settings"
				style="text-align: center; vertical-align: middle;">

				<?php
				// Try to display the status icon.
				// Older versions of AffiliateWP may not have certain icons, so we display a warning if we catch an error.
				try {
					if ( $linked_tag ) {
						affwp_icon_tooltip(
							sprintf( __( 'Linked with %1$s tag %2$s', 'wp-fusion' ), wp_fusion()->crm->name, wpf_get_tag_label( $linked_tag[0] ) ),
							'locked',
							true
						);
					} else {
						affwp_icon_tooltip(
							__( 'Not linked with tag', 'wp-fusion' ),
							'mdash',
							true
						);
					}
				} catch ( InvalidArgumentException $e ) {
					affwp_icon_tooltip( 'Link with Tag ' . $linked_tag[0] . '. But an error occurred while retrieving the dashicon.', 'warning', true );
				}

				?>

			</td>

		<?php

		return ob_get_clean();
	}

	/**
	 * Main WPF Settings Field
	 * Adds Link with Tag field to the group creation screen.
	 *
	 * @since 3.43.8
	 *
	 * @return mixed The settings HTML output.
	 */
	public function main_wpf_settings_field() {

		wp_nonce_field( 'wpf_awp_settings', 'wpf_awp_settings_nonce' );

		// We use a temporary setting to store the link tag for saving. We then remove it later once the group is saved.
		$args = array(
			'setting'   => array(),
			'meta_name' => 'awp_group_temp_settings',
			'limit'     => 1,
		);

		ob_start();

		?>

		<div class="form-field term-name-wrap">

			<label for="group-wpf-settings">
				<?php esc_html_e( 'Link with Tag', 'wp-fusion' ); ?>
			</label>

			<?php wpf_render_tag_multiselect( $args ); ?>

			<p id="description">
				<?php echo wp_kses( $this->wpf_settings_description(), affwp_get_tooltip_allowed_html() ); ?>
			</p>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Edit WPF Settings Field
	 * Adds Link with Tag field to the group edit screen.
	 *
	 * @since 3.43.8
	 *
	 * @param object $meta The group meta.
	 *
	 * @return mixed The settings HTML output.
	 */
	public function edit_wpf_settings_field( $meta ) {

		$args = array(
			'setting'   => null !== ( wpf_get_option( 'awp_group_tag_' . $meta->group_id ) ) ? wpf_get_option( 'awp_group_tag_' . $meta->group_id ) : array(),
			'meta_name' => 'awp_group_' . $meta->group_id . '_settings',
			'limit'     => 1,
		);

		wp_nonce_field( 'wpf_awp_settings', 'wpf_awp_settings_nonce' );

		ob_start();

		?>

		<tr class="form-field term-name-wrap">

			<th scope="row">
				<?php esc_html_e( 'WP Fusion Settings', 'wp-fusion' ); ?>
			</th>

			<td>
				<?php wpf_render_tag_multiselect( $args ); ?>

				<p id="description">
				<?php echo wp_kses( $this->wpf_settings_description(), affwp_get_tooltip_allowed_html() ); ?>
				</p>
			</td>
		</tr>

		<?php

		return ob_get_clean();
	}

	/**
	 * Save WPF Settings Field
	 * Saves tag settings to WPF settings.
	 *
	 * @since 3.43.8
	 *
	 * @param object $group The group object.
	 *
	 * @return bool true|false Whether or not the settings were saved.
	 */
	public function save_wpf_settings_field( $group ) {

		// Nonce verification.
		if ( ! isset( $_POST ) || ! isset( $_POST['wpf_awp_settings_nonce'] ) && false !== wp_verify_nonce( $_POST['wpf_awp_settings_nonce'], 'wpf_awp_settings' ) ) {
			return false;
		}

		if ( isset( $_POST[ 'awp_group_' . $group->group_id . '_settings' ] ) ) {

			// Save the link tag.

			$tag_id = $_POST[ 'awp_group_' . $group->group_id . '_settings' ];

			wp_fusion()->settings->set( 'awp_group_tag_' . $group->group_id, $tag_id );

		} elseif ( isset( $_POST['awp_group_temp_settings'] ) ) {

			// If this is a new group, save the link tag and unset the temp setting.

			$tag_id = $_POST['awp_group_temp_settings'];

			wp_fusion()->settings->set( 'awp_group_tag_' . $group->group_id, $tag_id );
			wp_fusion()->settings->set( 'awp_group_temp_settings', null );
		} else {

			// If no tag is set, unset the setting.

			wp_fusion()->settings->set( 'awp_group_tag_' . $group->group_id, null );
		}

		return true;
	}

	/**
	 * WPF Settings Description
	 * Helper function to add the description and tip text to the settings field.
	 *
	 * @since 3.43.8
	 *
	 * @return mixed The description HTML output.
	 */
	public function wpf_settings_description() {

		return __( 'Select a tag to link with this group. When the tag is applied, the user will automatically be enrolled. When the tag is removed the user will be unenrolled.', 'wp-fusion' );
	}

	/**
	 * Settings on Edit Affiliate screen
	 *
	 * @access public
	 * @return mixed Affiliate Settings
	 */
	public function edit_affiliate( $affiliate ) {

		if ( ! property_exists( wp_fusion()->integrations, 'woocommerce' ) ) {
			return;
		}

		?>

		<tr class="form-row">

			<th scope="row">
				<label for="notes"><?php _e( 'Apply Tags', 'wp-fusion' ); ?></label>
			</th>

			<td>

				<?php

				$setting = affwp_get_affiliate_meta( $affiliate->affiliate_id, 'apply_tags_customers', true );

				if ( empty( $setting ) ) {
					$setting = array();
				}

				$args = array(
					'setting'   => $setting,
					'meta_name' => 'apply_tags_customers',
				);

				wpf_render_tag_multiselect( $args );

				?>


				<p class="description"><?php _e( 'These tags will be applied to any WooCommerce customers who purchase using this affiliate\'s referral URL.', 'wp-fusion' ); ?></p>
			</td>

		</tr>

		<?php
	}


	/**
	 * Save Edit Affiliate
	 * Saves WP Fusion settings on the Edit Affiliate screen.
	 * Also applies tags to the affiliate if they're added to a group and the group has a linked tag.
	 *
	 * @since 3.41.29 Added support for Affiliate Groups.
	 *
	 * @param object $affiliate The affiliate.
	 * @param array  $args The affiliate args.
	 * @param array  $data The saved data.
	 */
	public function save_edit_affiliate( $affiliate, $args, $data ) {

		if ( ! empty( $data['apply_tags_customers'] ) ) {

			affwp_update_affiliate_meta( $affiliate->affiliate_id, 'apply_tags_customers', $data['apply_tags_customers'] );

		} else {

			affwp_delete_affiliate_meta( $affiliate->affiliate_id, 'apply_tags_customers' );

		}

		if ( class_exists( '\AffiliateWP\Groups\Group' ) && class_exists( '\AffiliateWP\Groups\DB' ) ) {

			$affwp = new AffiliateWP\Groups\DB();

			$link_tag  = array();
			$user_tags = wpf_get_tags( $args['user_id'] );

			// We only need to check the first key since affiliates can only have one group.
			if ( isset( $data['affiliate-groups_items'] ) && 'none' !== $data['affiliate-groups_items'][0] ) {

				foreach ( $data['affiliate-groups_items'] as $key => $group_id ) {

					$settings = wpf_get_option( 'awp_group_tag_' . $group_id );

					// If a link tag is set and the user has the tag.
					// We update the affiliate meta for consistancy with other WP Fusion integration.
					if ( $settings && ! array_intersect( $settings, $user_tags ) ) {

						$link_tag = array_merge( $link_tag, $settings );

						wpf_log( 'info', $args['user_id'], 'Affiliate was added to AffiliateWP group <a href="' . admin_url( 'admin.php?page=affiliate-wp-affiliate-groups&action=edit&group_id=' . $group_id ) . '" target="_blank">' . $affwp->get_group_title( $group_id ) . '</a>. Applying tags.' );
						affwp_update_affiliate_meta( $affiliate->affiliate_id, 'tag_link_' . $group_id, $link_tag );

						wp_fusion()->user->apply_tags( $link_tag, $args['user_id'] );

					} else {

						affwp_delete_affiliate_meta( $affiliate->affiliate_id, 'tag_link_' . $group_id );

					}
				}
			} else {
				// If the user isn't in a group, we need to check if the affiliate has a linked tag.
				foreach ( $affwp->get_groups() as $group_id ) {

					$settings = wpf_get_option( 'awp_group_tag_' . $group_id );

					if ( $settings && array_intersect( $settings, $user_tags ) ) {

						$link_tag = array_merge( $link_tag, $settings );

						wpf_log( 'info', $args['user_id'], 'Affiliate was removed from AffiliateWP group <a href="' . admin_url( 'admin.php?page=affiliate-wp-affiliate-groups&action=edit&group_id=' . $group_id ) . '" target="_blank">' . $affwp->get_group_title( $group_id ) . '</a>. Removing tags.' );
						affwp_delete_affiliate_meta( $affiliate->affiliate_id, 'tag_link_' . $group_id );

						wp_fusion()->user->remove_tags( $link_tag, $args['user_id'] );

					}
				}
			}
		}
	}

	/**
	 * Tags Modified.
	 *
	 * Manages an affiliates group access based on linked tags.
	 *
	 * @since 3.41.29
	 * @since 3.41.42   Added link tags.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $user_tags The user tags.
	 */
	public function tags_modified( $user_id, $user_tags ) {

		if ( ! class_exists( '\AffiliateWP\Groups\Group' ) ) {
			return; // only works in AffiliateWP 2.13.0+.
		}

		$affwp = new AffiliateWP\Groups\DB();
		$awpdb = new AffiliateWP\Connections\DB();

		// Group Tag Linking.

		$groups       = $affwp->get_groups();
		$affiliate_id = affwp_get_affiliate_id( $user_id );

		// Auto-activate affiliate.

		$linked_tag = wpf_get_option( 'awp_tag_activate_link', array() );
		$user_tags  = wpf_get_tags( $user_id );

		if ( ! empty( $linked_tag ) ) {

			$linked_tag = $linked_tag[0];

			// We check if $affiliate_id is empty, rather than false for a new affiliate.
			if ( in_array( $linked_tag, $user_tags ) && empty( $affiliate_id ) ) {

				// Create new affiliate.

				wpf_log(
					'info',
					$user_id,
					'User granted affiliate account by linked tag <strong>' . wpf_get_tag_label( $linked_tag ) . '</strong>.'
				);

				affwp_add_affiliate(
					array(
						'user_id' => $user_id,
						'status'  => 'active',
					)
				);

			} elseif ( in_array( $linked_tag, $user_tags ) && 'active' !== affwp_get_affiliate_status( $affiliate_id ) ) {

				// Existing inactive affiliate, activate them.

				wpf_log(
					'info',
					$user_id,
					'Affiliate activated by linked tag <strong>' . wpf_get_tag_label( $linked_tag ) . '</strong>.'
				);

				affwp_set_affiliate_status( $affiliate_id, 'active' );

			} elseif ( ! in_array( $linked_tag, $user_tags ) && 'active' === affwp_get_affiliate_status( $affiliate_id ) ) {

				// Tag is missing and affiliate is active, deactivate them.

				wpf_log(
					'info',
					$user_id,
					'Affiliate deactivated by linked tag <strong>' . wpf_get_tag_label( $linked_tag ) . '</strong>.'
				);

				affwp_set_affiliate_status( $affiliate_id, 'inactive' );

			}
		}

		// We check all the groups to see if the affiliate has a linked tag.
		foreach ( $groups as $group_id ) {

			$settings = wpf_get_option( 'awp_group_tag_' . $group_id, array() );

			// If the affiliate doesn't exist but they have a linked tag, add them.
			if ( array_intersect( $settings, $user_tags ) ) {

				if ( ! $affiliate_id ) {
					$affiliate_id = affwp_add_affiliate(
						array(
							'user_id' => $user_id,
							'status'  => 'active',
						)
					);
				} elseif ( 'active' !== affwp_get_affiliate_status( $affiliate_id ) ) {
					affwp_set_affiliate_status( $affiliate_id, 'active' );
				}
			}

			if ( ! $affiliate_id ) {
				// If there's no affiliate, we don't need to enroll them.
				continue;
			}

			// If the affiliate has a linked tag and isn't in the group, we add them.
			if ( ! empty( $settings ) && array_intersect( $settings, $user_tags ) && 0 === affwp_get_affiliate_group_id( $affiliate_id ) ) {

				wpf_log(
					'info',
					$user_id,
					sprintf(
						/* translators: 1: Group edit URL, 2: Group title, 3: Tag label */
						__( 'User added to AffiliateWP group <a href="%1$s" target="_blank">%2$s</a> by linked tag <strong>%3$s</strong>.', 'wp-fusion' ),
						admin_url( 'admin.php?page=affiliate-wp-affiliate-groups&action=edit&group_id=' . $group_id ),
						$affwp->get_group_title( $group_id ),
						wp_fusion()->user->get_tag_label( $settings[0] )
					),
					array( 'source' => 'affiliate-wp' )
				);

				/**
				 * Register the connectable.
				 * This is required for the connect() method to work.
				 * We need to use the connect() method in class-connections-db.php because
				 * AffiliateWP doesn't have a function to add an affiliate to a group.
				 *
				 * @param array $args The array of arguments to register the connectable.
				 */
				$args = array(
					'name'   => 'group',
					'table'  => 'wp_affiliate_wp_connections',
					'column' => 'group',
				);
				$awpdb->register_connectable( $args );

				$args = array(
					'name'   => 'affiliate',
					'table'  => 'wp_affiliate_wp_connections',
					'column' => 'affiliate',
				);
				$awpdb->register_connectable( $args );

				$args = array(
					'group'     => $group_id,
					'affiliate' => $affiliate_id,
				);

				// Connect the affiliate to the group in the database.
				// This adds them to the group.
				$awpdb->connect( $args );
			}

			// If the affiliate has a linked tag and is not in the group, we remove them.
			if ( ! empty( $settings ) && ! array_intersect( $settings, $user_tags ) && affwp_get_affiliate_group_id( $affiliate_id ) === $group_id ) {

				wpf_log(
					'info',
					$user_id,
					sprintf(
						/* translators: 1: Group edit URL, 2: Group title, 3: Tag label */
						__( 'User removed from AffiliateWP group <a href="%1$s" target="_blank">%2$s</a> by linked tag <strong>%3$s</strong>.', 'wp-fusion' ),
						admin_url( 'admin.php?page=affiliate-wp-affiliate-groups&action=edit&group_id=' . $group_id ),
						$affwp->get_group_title( $group_id ),
						wp_fusion()->user->get_tag_label( $settings[0] )
					),
					array( 'source' => 'affiliate-wp' )
				);

				// Get the connection ID.
				// 'fields' => 'ids' returns the connection ids that are set in the database.
				$args = array(
					'fields' => 'ids',
				);

				$connection_ids = $awpdb->get_connections( $args );

				// If a connection for this affiliate exists, we delete it.
				// This removes them from the group.
				foreach ( $connection_ids as $connection_id ) {

					$connection = $awpdb->get_connected_ids( $connection_id );

					// phpcs:ignore
					if ( $affiliate_id == $connection['affiliate'] ) {

						$awpdb->delete_connection( $connection_id );

					}
				}
			}
		}
	}


	/**
	 * Affiliate Deleted.
	 *
	 * Applies specified tags when an affiliate is deleted.
	 *
	 * @since 3.41.42
	 *
	 * @param int    $affiliate_id The affiliate ID.
	 * @param array  $data The affiliate delete data.
	 * @param object $affiliate The affiliate object.
	 */
	public function affiliate_deleted( $affiliate_id, $data, $affiliate ) {

		$this->affiliate_status_updated( $affiliate_id, 'deleted' );
	}

	/**
	 * Affiliate Status Updated.
	 *
	 * Applies specified tags when the affiliate is deactivated or activated.
	 *
	 * @since 3.41.30
	 * @since 3.41.42 Added linked tags.
	 *
	 * @param int    $affiliate_id The affiliate ID.
	 * @param string $status       The affiliate status.
	 * @param string $old_status   The old affiliate status.
	 */
	public function affiliate_status_updated( $affiliate_id = 0, $status = '', $old_status = '' ) {

		// If the affiliate ID is invalid or if the affiliate status hasn't changed, do nothing.
		if ( empty( $affiliate_id ) || $status === $old_status ) {
			return;
		}

		$user_id = affwp_get_affiliate_user_id( $affiliate_id );

		// Sync the data.
		wp_fusion()->user->push_user_meta( $user_id, array( 'awp_affiliate_status' => $status ) );

		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ) );

		if ( ! doing_action( 'wpf_tags_modified' ) ) {

			// Possibly apply or remove Linked Tag. Only it it wasn't just modified.

			$linked_tag = wpf_get_option( 'awp_tag_activate_link' );

			if ( ! empty( $linked_tag ) ) {

				if ( 'active' === $status ) {
					wp_fusion()->user->apply_tags( $linked_tag, $user_id );
				} elseif ( 'inactive' === $status || 'rejected' === $status || 'deleted' === $status ) {
					wp_fusion()->user->remove_tags( $linked_tag, $user_id );
				}
			}
		}

		// Apply tags for the status.

		$apply_tags = wpf_get_option( "awp_apply_tags_{$status}" );

		if ( ! empty( $apply_tags ) ) {
			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
		}

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
	}

	/**
	 * Adds AWP field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['awp']          = array(
			'title' => __( 'Affiliate WP - Affiliate', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/affiliates/affiliatewp/',
		);
		$field_groups['awp_referrer'] = array(
			'title' => __( 'Affiliate WP - Referrer', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/affiliates/affiliatewp/',
		);

		return $field_groups;
	}

	/**
	 * Adds AWP meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */
	public function add_meta_fields( $meta_fields = array() ) {

		// Affiliate
		$meta_fields['awp_affiliate_id'] = array(
			'label'  => 'Affiliate\'s Affiliate ID',
			'type'   => 'text',
			'group'  => 'awp',
			'pseudo' => true,
		);

		$meta_fields['awp_affiliate_status'] = array(
			'label'  => 'Affiliate\'s Status',
			'type'   => 'text',
			'group'  => 'awp',
			'pseudo' => true,
		);

		$meta_fields['awp_referral_rate'] = array(
			'label'  => 'Affiliate\'s Referral Rate',
			'type'   => 'text',
			'group'  => 'awp',
			'pseudo' => true,
		);

		$meta_fields['awp_payment_email'] = array(
			'label'  => 'Affiliate\'s Payment Email',
			'type'   => 'text',
			'group'  => 'awp',
			'pseudo' => true,
		);

		$meta_fields['affwp_user_url'] = array(
			'label'  => 'Affiliate\'s Website URL',
			'type'   => 'text',
			'group'  => 'awp',
			'pseudo' => true,
		);

		$meta_fields['affwp_promotion_method'] = array(
			'label'  => 'Affiliate\'s Promotion Method',
			'type'   => 'text',
			'group'  => 'awp',
			'pseudo' => true,
		);

		$meta_fields['affwp_total_earnings'] = array(
			'label'  => 'Affiliate\'s Total Earnings',
			'type'   => 'int',
			'group'  => 'awp',
			'pseudo' => true,
		);

		$meta_fields['affwp_referral_count'] = array(
			'label'  => 'Affiliate\'s Total Referrals',
			'type'   => 'int',
			'group'  => 'awp',
			'pseudo' => true,
		);

		// Groups
		if ( class_exists( 'AffiliateWP_Affiliate_Groups' ) ) {

			$meta_fields['affiliate_groups'] = array(
				'label'  => 'Affiliate\'s Groups',
				'type'   => 'multiselect',
				'group'  => 'awp',
				'pseudo' => true,
			);

		}

		// Custom slugs
		if ( class_exists( 'AffiliateWP_Custom_Affiliate_Slugs' ) ) {

			$meta_fields['custom_slug'] = array(
				'label'  => 'Affiliate\'s Custom Slug',
				'type'   => 'text',
				'group'  => 'awp',
				'pseudo' => true,
			);

		}

		// Landing pages

		if ( class_exists( 'AffiliateWP_Affiliate_Landing_Pages' ) ) {

			$meta_fields['affwp_landing_page'] = array(
				'label'  => 'Affiliate\'s Landing Page',
				'type'   => 'text',
				'group'  => 'awp',
				'pseudo' => true,
			);

		}

		// Referrer
		$meta_fields['awp_referrer_id'] = array(
			'label'  => 'Referrer\'s Affiliate ID',
			'type'   => 'text',
			'group'  => 'awp_referrer',
			'pseudo' => true,
		);

		$meta_fields['awp_referrer_first_name'] = array(
			'label'  => 'Referrer\'s First Name',
			'type'   => 'text',
			'group'  => 'awp_referrer',
			'pseudo' => true,
		);

		$meta_fields['awp_referrer_last_name'] = array(
			'label'  => 'Referrer\'s Last Name',
			'type'   => 'text',
			'group'  => 'awp_referrer',
			'pseudo' => true,
		);

		$meta_fields['awp_referrer_email'] = array(
			'label'  => 'Referrer\'s Email',
			'type'   => 'text',
			'group'  => 'awp_referrer',
			'pseudo' => true,
		);

		$meta_fields['awp_referrer_username'] = array(
			'label'  => 'Referrer\'s Username',
			'type'   => 'text',
			'group'  => 'awp_referrer',
			'pseudo' => true,
		);

		$meta_fields['awp_referrer_url'] = array(
			'label'  => 'Referrer\'s Website URL',
			'type'   => 'text',
			'group'  => 'awp_referrer',
			'pseudo' => true,
		);

		$meta_fields['awp_landing_page'] = array(
			'label'  => 'Visit Landing Page',
			'type'   => 'text',
			'group'  => 'awp_referrer',
			'pseudo' => true,
		);

		$meta_fields['awp_referring_url'] = array(
			'label'  => 'Referring URL',
			'type'   => 'text',
			'group'  => 'awp_referrer',
			'pseudo' => true,
		);

		return $meta_fields;
	}

	/**
	 * Gets all the relevant metdata for an affiliate
	 *
	 * @since 3.35.14
	 *
	 * @param int $affiliate_id The ID of the affiliate to get the data for
	 * @return array User Meta
	 */
	public function get_affiliate_meta( $affiliate_id ) {

		// Default fields
		$affiliate = affwp_get_affiliate( $affiliate_id );

		$rate = isset( $affiliate->rate ) ? $affiliate->rate : null;

		if ( empty( $rate ) ) {
			$rate = affiliate_wp()->settings->get( 'referral_rate', 20 );
		}

		$user = get_userdata( $affiliate->user_id );

		$affiliate_data = array(
			'first_name'           => $user->first_name,
			'last_name'            => $user->last_name,
			'awp_affiliate_id'     => $affiliate_id,
			'awp_referral_rate'    => $rate,
			'awp_payment_email'    => $affiliate->payment_email,
			'awp_affiliate_status' => $affiliate->status,
		);

		// Custom meta
		$data = affwp_get_affiliate_meta( $affiliate_id );

		if ( ! empty( $data ) ) {

			foreach ( $data as $key => $value ) {
				$affiliate_data[ $key ] = maybe_unserialize( $value[0] );
			}
		}

		// These fields require queries so let's only get that data if they're enabled for sync
		if ( wpf_is_field_active( 'affwp_referral_count' ) ) {
			$affiliate_data['affwp_referral_count'] = affwp_count_referrals( $affiliate_id, array( 'paid', 'unpaid' ) );
		}

		if ( wpf_is_field_active( 'affwp_total_earnings' ) ) {
			$affiliate_data['affwp_total_earnings'] = affiliate_wp()->referrals->get_earnings_by_status( array( 'paid', 'unpaid' ), $affiliate_id );
		}

		if ( function_exists( 'affwp_alp_get_landing_page_ids' ) ) {

			$page_ids = affwp_alp_get_landing_page_ids( $user->user_login );

			if ( ! empty( $page_ids ) ) {
				$affiliate_data['affwp_landing_page'] = get_the_permalink( $page_ids[0] );
			}
		}

		return $affiliate_data;
	}


	/**
	 * Gets any custom fields out of the wp_affiliatemeta table
	 *
	 * @since 3.35.14
	 *
	 * @param array $user_meta The user's metadata, in key => value pairs
	 * @param int   $user_id   The ID of the user to get the metadata for
	 * @return array User Meta
	 */
	public function get_user_meta( $user_meta, $user_id ) {

		$affiliate_id = affwp_get_affiliate_id( $user_id );

		if ( $affiliate_id ) {

			$affiliate_data = $this->get_affiliate_meta( $affiliate_id );
			$user_meta      = array_merge( $user_meta, $affiliate_data );

		}

		return $user_meta;
	}


	/**
	 * Triggered when new user registered through AWP
	 *
	 * @access  public
	 * @return  array Post Data
	 */
	public function user_register( $post_data, $user_id ) {

		$field_map = array(
			'affwp_user_name'     => 'display_name',
			'affwp_user_login'    => 'user_login',
			'affwp_user_email'    => 'user_email',
			'affwp_payment_email' => 'awp_payment_email',
			'affwp_user_url'      => 'user_url',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		return $post_data;
	}

	/**
	 * Triggered when affiliate updated
	 *
	 * @access  public
	 * @return  void
	 */
	public function update_affiliate( $data ) {

		$affiliate = affwp_get_affiliate( $data['affiliate_id'] );

		if ( empty( $data['rate'] ) ) {
			$data['rate'] = affiliate_wp()->settings->get( 'referral_rate', 20 );
		}

		$affiliate_data = array(
			'awp_affiliate_id'     => $data['affiliate_id'],
			'awp_referral_rate'    => $data['rate'],
			'awp_payment_email'    => $data['payment_email'],
			'awp_affiliate_status' => $affiliate->status,
		);

		// Groups.
		foreach ( $data as $key => $value ) {

			if ( 0 === strpos( $key, 'affiliate_group_' ) ) {

				if ( ! isset( $affiliate_data['affiliate_groups'] ) ) {
					$affiliate_data['affiliate_groups'] = array();
				}

				if ( ! in_array( $key, $affiliate_data['affiliate_groups'] ) ) {
					$affiliate_data['affiliate_groups'][] = $key;
				}
			}
		}

		wp_fusion()->user->push_user_meta( $affiliate->user_id, $affiliate_data );
	}

	/**
	 * Triggered when a referral is accepted
	 *
	 * @access  public
	 * @return  void
	 */
	public function referral_accepted( $affiliate_id, $referral ) {

		// Update data
		$aff_user_id = affwp_get_affiliate_user_id( $affiliate_id );

		$aff_user = get_user_by( 'id', $aff_user_id );

		// Get visit data.

		$referrer_data = array(
			'awp_referrer_id'         => $affiliate_id,
			'awp_referrer_first_name' => $aff_user->first_name,
			'awp_referrer_last_name'  => $aff_user->last_name,
			'awp_referrer_email'      => $aff_user->user_email,
			'awp_referrer_url'        => $aff_user->user_url,
			'awp_referrer_username'   => $aff_user->user_login,
		);

		$visit = affwp_get_visit( $referral->visit_id );

		if ( $visit ) {
			$referrer_data['awp_landing_page']  = $visit->url;
			$referrer_data['awp_referring_url'] = $visit->referrer;
		}

		// Handle different referral contexts
		if ( 'woocommerce' === $referral->context ) {

			// Get the customer's ID
			$order = wc_get_order( $referral->reference );

			if ( false == $order ) {
				return;
			}

			$user_id    = $order->get_user_id();
			$contact_id = wp_fusion()->integrations->woocommerce->get_contact_id_from_order( $order );

			// Get any tags to apply
			$apply_tags = wpf_get_option( 'awp_apply_tags_customers' );

			if ( empty( $apply_tags ) ) {
				$apply_tags = array();
			}

			$setting = affwp_get_affiliate_meta( $affiliate_id, 'apply_tags_customers', true );

			if ( empty( $setting ) ) {
				$setting = array();
			}

			$apply_tags = array_merge( $apply_tags, $setting );

			if ( empty( $contact_id ) ) {

				// If it's a new contact, merge the referral data into the order data.

				add_filter(
					'wpf_woocommerce_customer_data',
					function ( $customer_data ) use ( &$referrer_data ) {

						$customer_data = array_merge( $customer_data, $referrer_data );

						return $customer_data;
					}
				);
			}
		} elseif ( 'gravityforms' === $referral->context ) {

			// The referral is awarded before WPF processes the feed, so we'll register the filter here to merge the data, using a closure.
			add_filter(
				'wpf_gform_pre_submission',
				function ( $update_data, $user_id, $contact_id, $form_id ) use ( &$referrer_data ) {

					$referrer_data = wp_fusion()->crm->map_meta_fields( $referrer_data );

					$update_data = array_merge( $update_data, $referrer_data );

					return $update_data;
				},
				10,
				4
			);

		} elseif ( 'ultimate_member_signup' === $referral->context ) {

			// Get user ID from UM signup
			$user_id = $referral->reference;

		} elseif ( 'fluentforms' === $referral->context ) {

			if ( empty( $referral->contact_id ) ) {
				return; // WPF hasn't finished processing it yet.
			}

			$user_id    = $referral->user_id;
			$contact_id = $referral->contact_id;

		} elseif ( 'edd' === $referral->context ) {

			$payment    = edd_get_payment( $referral->reference );
			$user_id    = $payment->user_id;
			$contact_id = $payment->get_meta( WPF_CONTACT_ID_META_KEY );

		} else {

			wpf_log( 'info', wpf_get_current_user_id(), 'AffiliateWP referral detected but unable to sync referrer data since referral context <code>' . $referral->context . '</code> is not currently supported.' );

		}

		// If we've found a user or contact for the referral, update their record and apply tags
		if ( ! empty( $user_id ) ) {

			wp_fusion()->user->push_user_meta( $user_id, $referrer_data );

			if ( ! empty( $apply_tags ) ) {
				wp_fusion()->user->apply_tags( $apply_tags, $user_id );
			}
		} elseif ( ! empty( $contact_id ) ) {

			wpf_log( 'info', wpf_get_current_user_id(), 'Syncing AffiliateWP referrer meta:', array( 'meta_array' => $referrer_data ) );

			wp_fusion()->crm->update_contact( $contact_id, $referrer_data );

			if ( ! empty( $apply_tags ) ) {
				wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );
			}
		}

		// Maybe sync data to the affiliate.
		$affiliate_data = array();

		// These fields require queries so let's only get that data if they're enabled for sync
		if ( wpf_is_field_active( 'affwp_referral_count' ) ) {
			$affiliate_data['affwp_referral_count'] = affwp_count_referrals( $affiliate_id );
		}

		if ( wpf_is_field_active( 'affwp_total_earnings' ) ) {
			$affiliate_data['affwp_total_earnings'] = affiliate_wp()->referrals->get_earnings_by_status( array( 'paid', 'unpaid' ), $affiliate_id );
		}

		if ( ! empty( $affiliate_data ) ) {

			$user_id = affwp_get_affiliate_user_id( $affiliate_id );

			wp_fusion()->user->push_user_meta( $user_id, $affiliate_data );

		}

		// Maybe apply first referral tags to the affiliate
		$apply_tags = wpf_get_option( 'awp_apply_tags_first_referral', array() );

		if ( ! empty( $apply_tags ) ) {

			$referral_count = affiliate_wp()->referrals->unpaid_count( '', $affiliate_id );

			if ( $referral_count == 1 ) {

				$user_id = affwp_get_affiliate_user_id( $affiliate_id );

				wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			}
		}
	}

	/**
	 * Handle a Fluent Forms referral.
	 *
	 * @since 3.40.37
	 *
	 * @param array           $update_data The data that was synced to the CRM.
	 * @param int|false       $user_id     The user ID, or false.
	 * @param string|WP_Error $contact_id  The contact ID in the CRM, or WP_Error.
	 * @param int             $form_id     The form ID.
	 * @param int|false       $entry_id    The entry ID, or false if unknown.
	 */
	public function maybe_handle_fluent_forms_referral( $update_data, $user_id, $contact_id, $form_id, $entry_id ) {

		$referral = affiliate_wp()->referrals->get_by( 'reference', $entry_id );

		if ( $referral ) {

			// Set the contact ID so it can be used in the next step.

			$referral->contact_id = $contact_id;
			$referral->user_id    = $user_id;

			// Sync the referrer fields.
			$this->referral_accepted( $referral->affiliate_id, $referral );

		}
	}


	/**
	 * Add Affiliate.
	 *
	 * Triggered when an affiliate is added and applies apply tags and link tags.
	 *
	 * @since 3.41.42 Added link tags.
	 *
	 * @param int $affiliate_id The affiliate ID.
	 */
	public function add_affiliate( $affiliate_id ) {

		$affiliate      = affwp_get_affiliate( $affiliate_id );
		$affiliate_data = $this->get_affiliate_meta( $affiliate_id );

		if ( ! wp_fusion()->user->has_contact_id( $affiliate->user_id ) ) {

			// This is necessary so the data gets sent when Auto Register Affiliates is enabled.
			wp_fusion()->user->user_register( $affiliate->user_id, $affiliate_data );

			remove_action( 'user_register', array( wp_fusion()->user, 'user_register' ), 20 );

		} else {

			wp_fusion()->user->push_user_meta( $affiliate->user_id, $affiliate_data );

		}

		$apply_tags = wpf_get_option( 'awp_apply_tags', array() );

		// If the affiliates are approved, make sure they have these tags as well.
		if ( 'active' === $affiliate->status ) {
			$approved_tags = wpf_get_option( 'awp_apply_tags_active', array() );
			$linked_tag    = wpf_get_option( 'awp_tag_activate_link', array() );
			$apply_tags    = array_merge( $apply_tags, $approved_tags, $linked_tag );
		} elseif ( 'pending' === $affiliate->status ) {
			$pending_tags = wpf_get_option( 'awp_apply_tags_pending', array() );
			$apply_tags   = array_merge( $apply_tags, $pending_tags );
		}

		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ) );

		wp_fusion()->user->apply_tags( $apply_tags, $affiliate->user_id );

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds AffiliateWP to available export options
	 *
	 * @access public
	 * @return array Options
	 */
	public function export_options( $options ) {

		$options['affiliatewp'] = array(
			'label'   => __( 'AffiliateWP affiliate data', 'wp-fusion' ),
			'title'   => 'affiliates',
			'tooltip' => sprintf( __( 'Syncs any enabled \'AffiliateWP - Affiliate\' fields to %s, and applies any configured affiliate tags.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$options['affiliatewp_referrals'] = array(
			'label'   => __( 'AffiliateWP referrals', 'wp-fusion' ),
			'title'   => 'referrals',
			'tooltip' => sprintf( __( 'Processes all Accepted or Paid referrals as if they had just been accepted: syncs any enabled \'AffiliateWP - Referral\' fields to %s, and applies any configured tags, such as First Referral Accepted and/or any affiliate tags applied to WooCommerce customers.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		return $options;
	}

	/**
	 * Get all affiliates to be processed
	 *
	 * @access public
	 * @return array Members
	 */
	public function batch_init_affiliates() {

		$args = array(
			'number' => -1,
			'fields' => 'ids',
		);

		$affiliates = affiliate_wp()->affiliates->get_affiliates( $args );

		return $affiliates;
	}

	/**
	 * Processes affiliate actions in batches
	 *
	 * @access public
	 * @return void
	 */
	public function batch_step_affiliates( $affiliate_id ) {

		$this->add_affiliate( $affiliate_id );
	}

	/**
	 * Gets all Accepted or Paid referrals to be processed.
	 *
	 * @since 3.44.2
	 *
	 * @return array Referrals.
	 */
	public function batch_init_referrals() {

		$args = array(
			'number' => -1,
			'fields' => 'ids',
			'status' => array( 'unpaid', 'paid' ),
		);

		$referrals = affiliate_wp()->referrals->get_referrals( $args );

		return $referrals;
	}

	/**
	 * Processes referral actions in batches.
	 *
	 * @since 3.44.2
	 *
	 * @param int $referral_id The referral ID.
	 */
	public function batch_step_referrals( $referral_id ) {

		$referral = affwp_get_referral( $referral_id );

		$this->referral_accepted( $referral->affiliate_id, $referral );
	}
}

new WPF_AffiliateWP();
