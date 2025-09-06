<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * MemberDash integration
 *
 * @since 3.43.18
 */
class WPF_MemberDash extends WPF_Integrations_Base {

	/**
	 * This identifies the integration internally and makes it available.
	 *
	 * @var  string
	 * @since 3.43.18
	 */

	public $slug = 'memberdash';

	/**
	 * The human-readable name of the integration.
	 *
	 * @var  string
	 * @since 3.43.18
	 */

	public $name = 'MemberDash';

	/**
	 * Get things started.
	 *
	 * @since 3.43.18
	 */
	public function init() {

		// WPF stuff.
		add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

		// Added membership.
		add_action( 'ms_model_event_signed_up', array( $this, 'membership_assigned' ) );
		add_action( 'ms_model_event_renewed', array( $this, 'membership_assigned' ) );

		// Removed membership.
		add_action( 'ms_subscription_status-deactivated', array( $this, 'membership_dropped' ), 10, 2 ); // Handles manual deactivation.
		add_action( 'ms_subscription_status-expired', array( $this, 'membership_dropped' ), 10, 2 );
		add_action( 'ms_subscription_status-canceled', array( $this, 'membership_dropped' ), 10, 2 );

		// Moved membership.
		add_action( 'ms_model_event_deactivated', array( $this, 'membership_dropped' ), 10, 2 ); // Handles switching memberships.

		// System membership.
		add_action( 'ms_model_relationship_cancel_membership_after', array( $this, 'membership_dropped' ), 10, 2 ); // Handles switching off of the System membership.

		// WPF settings page.
		add_filter( 'ms_controller_membership_tabs', array( $this, 'add_wpf_settings_tab' ), 10, 2 );
		add_filter( 'ms_view_membership_edit_render_callback', array( $this, 'render_tab_wp_fusion' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Batch operations.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_memberdash_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_memberdash', array( $this, 'batch_step' ) );
	}

	/**
	 * Update Membership
	 * Processes link tags for memberships when tags are modified.
	 *
	 * @since 3.43.18
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user tags.
	 */
	public function update_membership( $user_id, $user_tags ) {

		// Check if the user is a member of any memberships.
		$memberships = MS_Model_Membership::get_memberships();

		// If there are no memberships, quit.
		if ( ! $memberships ) {
			return;
		}

		foreach ( $memberships as $membership ) {

			$settings = get_post_meta( $membership->get_id(), 'wpf-memberdash-settings', true );

			// Check if this membership has settings.
			if ( ! $settings || ! isset( $settings['link_with_tag'] ) ) {
				continue;
			}

			// Try to get the subscription.
			$subscription = MS_Model_Relationship::get_subscription( $user_id, $membership->get_id() );

			$link_tag = $settings['link_with_tag'][0];

			// Does the user have a subscription to this membership?
			if ( ! empty( $subscription ) && 'active' === $subscription->status ) {

				// Is the user missing the link tag?
				if ( ! in_array( $link_tag, $user_tags, true ) ) {

					// Prevent looping.
					remove_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

					// Remove them from the membership.
					$subscription->deactivate_membership();

					add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );
				}
			} elseif ( in_array( $link_tag, $user_tags, true ) ) {

				// User has the link tag, add them to the membership.

				// Prevent looping.
				remove_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

				// Add them to the membership.
				MS_Model_Relationship::create_ms_relationship( $membership->get_id(), $user_id );

				add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );
			}
		}
	}

	/**
	 * Membership Assigned
	 * Processes membership signup events.
	 *
	 * @since 3.43.18
	 *
	 * @param object $event The event.
	 */
	public function membership_assigned( $event ) {

		// Check if the event is valid.
		if ( ! isset( $event ) || ! isset( $event->membership_id ) || ! isset( $event->user_id ) ) {
			return;
		}

		// Process the membership.
		$this->process_membership( $event->membership_id, 'active', $event->user_id );
	}

	/**
	 * Membership Dropped
	 * Processes membership expired, cancelled and deactivated events.
	 * This is for when a membership is manually deactivated.
	 *
	 * @since 3.43.18
	 *
	 * @param object $relationship The relationship.
	 */
	public function membership_dropped( $relationship ) {

		// Check if the event is valid.
		if ( ! isset( $relationship ) || ! isset( $relationship->membership_id ) || ! isset( $relationship->user_id ) ) {
			return;
		}

		// Process the membership.
		$this->process_membership( $relationship->membership_id, 'deactivated', $relationship->user_id );
	}

	/**
	 * Process Membership
	 * Processes membership events.
	 *
	 * @since 3.43.18
	 *
	 * @param int    $membership_id The membership ID.
	 * @param string $status        The status.
	 * @param int    $user_id       The user ID.
	 */
	public function process_membership( $membership_id, $status, $user_id ) {

		$settings = get_post_meta( $membership_id, 'wpf-memberdash-settings', true );

		// Check if this membership has settings.
		if ( ! $settings ) {
			return;
		}

		// Membership Removed.

		// Remove any link tags first.
		if ( 'deactivated' === $status ) {
			if ( isset( $settings['link_with_tag'] ) ) {

				// Prevent looping.
				remove_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

				wpf_log( 'info', $user_id, 'Removing tag <strong>' . wpf_get_tag_label( $settings['link_with_tag'][0] ) . '</strong> because of link with tag for membership <a href="' . admin_url( 'post.php?post=' . $membership_id . '&action=edit' ) . '">#' . $membership_id . '</a> ' );
				wp_fusion()->user->remove_tags( $settings['link_with_tag'], $user_id );

				add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );
			}
		}

		// Membership Added.

		// Apply any enrollment tags and link tags.
		if ( 'active' === $status ) {
			if ( isset( $settings['apply_tags'] ) ) {

				// This prevents chaining.
				// We're already applying the tags here, so we don't need update_membership() to do it again.
				remove_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

				wpf_log( 'info', $user_id, 'Applying tags for membership <a href="' . admin_url( 'post.php?post=' . $membership_id . '&action=edit' ) . '">#' . $membership_id . '</a>' );
				wp_fusion()->user->apply_tags( $settings['apply_tags'], $user_id );

				add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );
			}
			if ( isset( $settings['link_with_tag'] ) ) {

				// Prevent looping.
				remove_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

				wpf_log( 'info', $user_id, 'Applying tag <strong>' . wpf_get_tag_label( $settings['link_with_tag'][0] ) . '</strong> because of link with tag for membership <a href="' . admin_url( 'post.php?post=' . $membership_id . '&action=edit' ) . '">#' . $membership_id . '</a> ' );
				wp_fusion()->user->apply_tags( array( $settings['link_with_tag'][0] ), $user_id );

				add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );
			}
		}
	}

	/**
	 * Add WPF Menu Page
	 * Adds WPF Settings menu page to MemberDash.
	 *
	 * @since 3.43.18
	 *
	 * @param  array  $menu_pages The menu pages.
	 * @param  bool   $limited_mode Whether or not to limit the menu pages.
	 * @param  object $controller The controller.
	 *
	 * @return array  The menu pages.
	 */
	public function add_wpf_menu_page( $menu_pages, $limited_mode, $controller ) {

		$menu_pages['wp-fusion'] = array(
			'title' => 'WP Fusion',
			'slug'  => 'wp-fusion',
		);

		return $menu_pages;
	}

	/**
	 * Add WPF Settings Tab
	 * Adds WPF Settings tab to MemberDash.
	 *
	 * @since 3.43.18
	 *
	 * @param array $tabs The settings tabs.
	 * @return array $tabs Modified settings tabs.
	 */
	public function add_wpf_settings_tab( $tabs ) {

		$tabs['wp-fusion'] = array(
			'title' => 'WP Fusion',
		);

		return $tabs;
	}

	/**
	 * Render WPF Settings
	 * WPF Settings tab callback function.
	 *
	 * @since 3.43.18
	 *
	 * @param array  $callback The callback.
	 * @param string $tab The tab.
	 * @param array  $data The data.
	 *
	 * return array $callback Modified callback.
	 */
	public function render_tab_wp_fusion( $callback, $tab, $data ) {

		if ( 'wp-fusion' === $tab ) {
			$callback = array( $this, 'render_wpf_settings' );
		}

		return $callback;
	}

	/**
	 * Render WPF Settings
	 * WPF Settings page callback function.
	 *
	 * @since 3.43.18
	 */
	public function render_wpf_settings() {

		// Fix for select4 tag multiselect.
		?>
			<script>
				// Disable MemberDash's select4 object.
				jQuery(document).ready(function($){
					$.fn.memberdashSelect = $;
				});
			</script>
		<?php

		$membership_id = isset( $_GET['membership_id'] ) ? intval( $_GET['membership_id'] ) : false;

		// Load the settings page.
		?>
			<div class="wrap">
				<?php

				// Save settings.
				if ( isset( $_POST['wpf_memberdash_settings_nonce'] ) && wp_verify_nonce( $_POST['wpf_memberdash_settings_nonce'], 'wpf_memberdash_settings' ) ) {

					$settings = array_map( 'wpf_clean_tags', $_POST['wpf_memberdash_settings'] );

					// Save the settings.
					update_post_meta( $membership_id, 'wpf-memberdash-settings', $settings );

					// Success message.
					echo '<div id="message" class="updated fade"><p><strong>' . esc_html__( 'Settings Saved', 'wp-fusion' ) . '</strong></p></div>';
				}

				// Get the settings and set the defaults.
				$defaults = array(
					'apply_tags'    => array(),
					'link_with_tag' => array(),
				);

				$settings = wp_parse_args( (array) get_post_meta( $membership_id, 'wpf-memberdash-settings', true ), $defaults );

				?>
				<form id="wpf-memdash-settings" action="" method="post" style="width: 100%; max-width: 1200px;">
					
					<?php wp_nonce_field( 'wpf_memberdash_settings', 'wpf_memberdash_settings_nonce' ); ?>

					<table class="table table-hover wpf-settings-table">
						<thead>
							<tr>
								<th scope="row" width="50%">
									<?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?>
									<?php sprintf( esc_html__( 'These tags will be applied in %s when someone is enrolled in this membership.', 'wp-fusion' ), wp_fusion()->crm->name ); ?>
								</th>
								<th scope="row" width="50%">
									<?php esc_html_e( 'Link with Tag', 'wp-fusion' ); ?>
									<?php sprintf( esc_html__( 'This tag will be applied in %1$s when a user is enrolled, and will be removed when a user is unenrolled. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this membership. If this tag is removed, the user will be removed from the membership.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ); ?>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									<?php
									wpf_render_tag_multiselect(
										array(
											'setting'   => $settings['apply_tags'],
											'meta_name' => 'wpf_memberdash_settings[apply_tags]',
										)
									);
									?>
								</td>
								<td>
									<?php
									wpf_render_tag_multiselect(
										array(
											'setting'   => $settings['link_with_tag'],
											'meta_name' => 'wpf_memberdash_settings[link_with_tag]',
											'limit'     => 1,
										)
									);
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit"><input name="Submit" type="submit" class="button-primary" value="Save Changes"/></p>
				</form>
			</div>

		<?php
	}

	/**
	 * Enqueues WPF styles.
	 *
	 * @since 3.43.18
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css', array(), WP_FUSION_VERSION );
	}

	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds Process Memberships to available export options
	 *
	 * @since  3.43.18
	 *
	 * @param  array $options The export options.
	 * @return array The export options.
	 */
	public function export_options( $options ) {

		$options['memberdash'] = array(
			'label'   => __( 'MemberDash memberships', 'wp-fusion' ),
			'title'   => __( 'Memberships', 'wp-fusion' ),
			/* translators: %s: CRM Name */
			'tooltip' => sprintf( __( 'For each membership, applies any configured membership tags to the user in %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		return $options;
	}

	/**
	 * Batch Init.
	 * Get all memberships to be processed.
	 *
	 * @since 3.43.18
	 *
	 * @return array The memberships that have settings saved.
	 */
	public function batch_init() {

		$all_memberships = MS_Model_Membership::get_memberships();
		$membership_ids  = array();

		foreach ( $all_memberships as $membership ) {
			$settings = get_post_meta( $membership->get_id(), 'wpf-memberdash-settings', true );

			if ( $settings ) {
				$membership_ids[] = $membership->get_id();
			}
		}

		return $membership_ids;
	}

	/**
	 * Batch Step.
	 * Processes memberships in batches.
	 *
	 * @since 3.43.18
	 *
	 * @param int $membership_id The membership ID.
	 */
	public function batch_step( $membership_id ) {

		// Get the membership object.
		$membership = MS_Factory::load( 'MS_Model_Membership', intval( $membership_id ) );

		// Use the mmebership object to find the members.
		$members = $membership->get_members();

		// Process the members.
		foreach ( $members as $member ) {

			// Get the user ID.
			$user_id = $member->get_user()->ID;

			// Process the membership.
			$this->process_membership( $membership->get_id(), 'active', $user_id );
		}
	}
}

new WPF_MemberDash();
