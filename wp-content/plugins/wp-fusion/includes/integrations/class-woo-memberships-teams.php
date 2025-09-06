<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Woo_Memberships_Teams extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'woo-memberships-teams';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'Teams for WooCommerce Memberships';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/teams-for-woocommerce-memberships/';

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */
	public function init() {

		add_action( 'wc_memberships_for_teams_add_team_member', array( $this, 'add_team_member' ), 10, 3 );
		add_action( 'wc_memberships_for_teams_after_remove_team_member', array( $this, 'after_remove_team_member' ), 10, 3 );
		add_action( 'wc_memberships_user_membership_cancelled', array( $this, 'owner_cancel_membership' ) );

		add_action( 'updated_user_meta', array( $this, 'sync_teams_role' ), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'sync_teams_role' ), 10, 4 );

		add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ) );

		// Meta Boxes.
		add_action( 'add_meta_boxes', array( $this, 'add_wpf_team_meta_box' ), 50 );
		add_action( 'wc_memberships_for_teams_process_team_meta', array( $this, 'save_meta_box_content' ), 50, 2 );

		// WPF Stuff.

		add_action( 'wpf_tags_modified', array( $this, 'maybe_add_user_to_team' ), 10, 2 );

		// Batch operations.
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_woo_memberships_teams_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_woo_memberships_teams', array( $this, 'batch_step' ) );
		add_filter( 'wpf_batch_woo_memberships_teams_tags_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_woo_memberships_teams_tags', array( $this, 'batch_step_tags' ) );
	}

	/**
	 * Maybe Add User To Team.
	 * Runs when a user's tags are modified. Checks if the user has a link tag for a team, and if so, adds them to the team. If the link tag is removed, removes the user from the team.
	 *
	 * @since 3.43.6
	 *
	 * @param int   $user_id The user ID.
	 * @param array $tags The user's tags.
	 */
	public function maybe_add_user_to_team( $user_id, $tags ) {

		$teams = wc_memberships_for_teams_get_teams();

		if ( empty( $teams ) ) {
			return;
		}

		remove_action( 'wpf_tags_modified', array( $this, 'maybe_add_user_to_team' ), 10, 2 );

		foreach ( $teams as $team ) {

			$team_id         = $team->get_id();
			$team_member_ids = $team->get_member_ids();

			$settings = get_post_meta( $team_id, 'wpf-settings-woo-memberships-teams', true );

			if ( empty( $settings ) || empty( $settings['link_tag_memberships_teams'] ) ) {
				continue;
			}

			// Link Tag.

			// If the user has the link tag, add them to the team.
			if ( array_intersect( $tags, $settings['link_tag_memberships_teams'] ) ) {
				// Only if they're not already in the team.
				if ( ! in_array( $user_id, $team_member_ids, false ) ) {

					// If theres an apply tag, add it.
					if ( ! empty( $settings['apply_tags_memberships_teams'] ) ) {
						wp_fusion()->user->apply_tags( $settings['apply_tags_memberships_teams'], $user_id );
					}

					wpf_log( 'info', $user_id, 'Link tag ' . wpf_get_tag_label( $settings['link_tag_memberships_teams'][0] ) . ' added to user ' . $user_id . '. Adding to user to team ' . $team->get_name() );

					// Add user to the team.
					$team->add_member( $user_id );
				}

				continue;
			}

			// If the link tag was removed, remove the user from the team.
			if ( in_array( $user_id, $team_member_ids, false ) ) {
				if ( ! array_intersect( $tags, $settings['link_tag_memberships_teams'] ) ) {

					wpf_log( 'info', $user_id, 'Link tag ' . wpf_get_tag_label( $settings['link_tag_memberships_teams'][0] ) . ' removed from user ' . $user_id . '. Removing user from team ' . $team->get_name() );

					// Remove user from the team.
					$team->remove_member( $user_id );
				}
			}
		}

		add_action( 'wpf_tags_modified', array( $this, 'maybe_add_user_to_team' ), 10, 2 );
	}

	/**
	 * Add Team Member.
	 * Runs when a team member accepts an invite and registers an account.
	 *
	 * @since unknown
	 * @since 3.43.6 Added team apply and link tags.
	 *
	 * @param WC_Memberships_For_Teams_Team_Member $member The team member.
	 * @param WC_Memberships_For_Teams_Team        $team The team.
	 * @param WC_Memberships_User_Membership       $user_membership The user membership.
	 */
	public function add_team_member( $member, $team, $user_membership ) {

		if ( empty( $member ) ) {
			return;
		}

		// Sync name and ID.

		$update_data = array(
			'wc_memberships_for_teams_team_name' => $team->get_name(),
			'wc_memberships_for_teams_team_id'   => $team->get_id(),
		);

		wp_fusion()->user->push_user_meta( $member->get_id(), $update_data );

		$product = $team->get_product();

		// Maybe apply tags for product.

		if ( ! empty( $product ) ) {

			$product_id = $product->get_id();

			$parent_id = $product->get_parent_id();

			if ( ! empty( $parent_id ) ) {
				$product_id = $parent_id;
			}

			$settings = get_post_meta( $product_id, 'wpf-settings-woo', true );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags_members'] ) ) {

				wp_fusion()->user->apply_tags( $settings['apply_tags_members'], $member->get_id() );

			}
		}

		// Apply the tags for the team to the user.

		$this->process_team_member( $team->get_id(), $member->get_id() );
	}

	/**
	 * After Remove Team Member
	 * Runs when a team member is removed from a team.
	 *
	 * @since unknown
	 * @since 3.43.6 Added team link tag.
	 *
	 * @param int                           $user_id The user ID.
	 * @param WC_Memberships_For_Teams_Team $team The team.
	 */
	public function after_remove_team_member( $user_id, $team ) {

		$product = $team->get_product();

		// Remove product tags.

		if ( ! empty( $product ) ) {

			$product_id = $product->get_id();

			$parent_id = $product->get_parent_id();

			if ( ! empty( $parent_id ) ) {
				$product_id = $parent_id;
			}

			$settings = get_post_meta( $product_id, 'wpf-settings-woo', true );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags_members'] ) && ! empty( $settings['remove_tags_members'] ) ) {

				wp_fusion()->user->remove_tags( $settings['apply_tags_members'], $user_id );
			}
		}

		// Remove team link tag.

		// Prevent looping.
		remove_action( 'wpf_tags_modified', array( $this, 'maybe_add_user_to_team' ), 10, 2 );

		$settings = get_post_meta( $team->get_id(), 'wpf-settings-woo-memberships-teams', true );

		if ( empty( $settings ) || empty( $settings['link_tag_memberships_teams'] ) ) {
			return;
		}

		wpf_log( 'info', $user_id, 'User ' . $user_id . ' removed from team ' . $team->get_name() . '. Removing link tag ' . wpf_get_tag_label( $settings['link_tag_memberships_teams'][0] ) );

		wp_fusion()->user->remove_tags( $settings['link_tag_memberships_teams'], $user_id );

		add_action( 'wpf_tags_modified', array( $this, 'maybe_add_user_to_team' ), 10, 2 );
	}


	/**
	 * Triggered when the owner of a team cancels his membership.
	 *
	 * @since 3.38.36
	 *
	 * @param WC_Memberships_User_Membership $membership The membership.
	 */
	public function owner_cancel_membership( $membership ) {

		$user_id = $membership->user_id;
		$teams   = wc_memberships_for_teams_get_teams( $user_id );
		if ( empty( $teams ) ) {
			return;
		}

		foreach ( $teams as $team ) {
			// Check if he is the owner.
			if ( intval( $user_id ) !== intval( $team->get_owner_id() ) ) {
				continue;
			}

			// Check if team has members.
			$members_ids = $team->get_member_ids();
			if ( empty( $members_ids ) ) {
				continue;
			}

			// Check if team has a product.
			$product = $team->get_product();
			if ( empty( $product ) ) {
				continue;
			}

			$product_id = $product->get_id();
			$parent_id  = $product->get_parent_id();

			if ( ! empty( $parent_id ) ) {
				$product_id = $parent_id;
			}

			$settings = get_post_meta( $product_id, 'wpf-settings-woo', true );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags_members'] ) && ! empty( $settings['remove_tags_members_cancelled'] ) ) {
				foreach ( $members_ids as $member_user_id ) {
					wp_fusion()->user->remove_tags( $settings['apply_tags_members'], $member_user_id );
				}
			}
		}
	}


	/**
	 * Sync changes to teams roles
	 *
	 * @access public
	 * @return void
	 */
	public function sync_teams_role( $meta_id, $user_id, $meta_key, $value ) {

		if ( strpos( $meta_key, '_wc_memberships_for_teams_team_' ) !== false && strpos( $meta_key, '_role' ) !== false ) {

			wp_fusion()->user->push_user_meta( $user_id, array( 'wc_memberships_for_teams_team_role' => $value ) );

		}
	}


	/**
	 * Writes subscriptions options to WPF/Woo panel
	 *
	 * @access public
	 * @return mixed
	 */
	public function panel_content( $post_id ) {

		$settings = array(
			'apply_tags_members'            => array(),
			'remove_tags_members'           => false,
			'remove_tags_members_cancelled' => false,
		);

		if ( get_post_meta( $post_id, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post_id, 'wpf-settings-woo', true ) );
		}

		echo '<div class="options_group wpf-product js-wc-memberships-for-teams-show-if-has-team-membership hidden">';

		echo '<p class="form-field"><label><strong>Team Membership</strong></label></p>';

		echo '<p class="form-field"><label>' . __( 'Apply tags to team members', 'wp-fusion' );
		echo ' <span class="dashicons dashicons-editor-help wpf-tip wpf-tip-bottom" data-tip="' . __( 'These tags will be applied to users when they are added as members to the team, and accept the invite.', 'wp-fusion' ) . '"></span>';
		echo '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags_members'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'apply_tags_members',
			)
		);

		echo '</p>';

		echo '<p class="form-field"><label for="wpf-remove-tags-members">' . __( 'Remove tags', 'wp-fusion' ) . '</label>';
		echo '<input class="checkbox" type="checkbox" id="wpf-remove-tags-members" name="wpf-settings-woo[remove_tags_members]" value="1" ' . checked( $settings['remove_tags_members'], 1, false ) . ' />';
		echo '<span class="description">' . __( 'Remove team member tags (above) when members are removed from the team.', 'wp-fusion' ) . '</span>';
		echo '</p>';

		echo '<p class="form-field"><label for="wpf-remove-tags-members-cancelled">' . __( 'Remove tags - Cancelled', 'wp-fusion' ) . '</label>';
		echo '<input class="checkbox" type="checkbox" id="wpf-remove-tags-members-cancelled" name="wpf-settings-woo[remove_tags_members_cancelled]" value="1" ' . checked( $settings['remove_tags_members_cancelled'], 1, false ) . ' />';
		echo '<span class="description">' . __( 'Remove team member tags when the team owner’s membership is cancelled.', 'wp-fusion' ) . '</span>';
		echo '</p>';

		echo '</div>';
	}

	/**
	 * Add WPF Team Meta Box
	 * Adds a meta box to the Teams post type for WP Fusion settings.
	 *
	 * @since 3.43.6
	 */
	public function add_wpf_team_meta_box() {

		add_meta_box(
			'wpf-woo-memberships-teams-meta',
			__( 'WP Fusion Settings', 'wp-fusion' ),
			array(
				$this,
				'wpf_team_meta_box_callback',
			),
			'wc_memberships_team',
		);
	}

	/**
	 * WPF Team Meta Box Callback
	 * Renders the meta box content.
	 *
	 * @since 3.43.6
	 *
	 * @param WP_Post $post The post object.
	 */
	public function wpf_team_meta_box_callback( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpf_meta_box_woo_members_teams', 'wpf_meta_box_woo_members_teams_nonce' );

		$settings = array(
			'apply_tags_memberships_teams' => array(),
			'link_tag_memberships_teams'   => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings-woo-memberships-teams', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings-woo-memberships-teams', true ) );
		}
		?>

			<div class="wpf-woo-teams-settings inside">
				<div class="section">

					<span class="label"><?php esc_html_e( 'Link with Tag', 'wp-fusion' ); ?>:</span>
					<br>

					<span class="value">
					<?php
						wpf_render_tag_multiselect(
							array(
								'setting'   => $settings['link_tag_memberships_teams'],
								'meta_name' => 'wpf-settings-woo-memberships-teams',
								'field_id'  => 'link_tag_memberships_teams',
								'limit'     => 1,
							)
						);
					?>
					</span>
					<br />
					<span class="label"><?php esc_html_e( 'This tag will be applied to users who join the team, and removed from the user if they leave the team. If the tag is applied, they will be added to the team, and if the tag is removed they will be removed from the team.', 'wp-fusion' ); ?></span>
				</div>
			</div>

		<?php
	}

	/**
	 * Save Meta Box Content
	 * Saves the meta box content when the team is updated.
	 *
	 * @since 3.43.6
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post The post object.
	 */
	public function save_meta_box_content( $post_id, $post ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_woo_members_teams_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_woo_members_teams_nonce'], 'wpf_meta_box_woo_members_teams' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't update on revisions.
		if ( 'revision' === $_POST['post_type'] ) {
			return;
		}

		if ( isset( $_POST['wpf-settings-woo-memberships-teams'] ) ) {

			// Update the meta field in the database.
			$data = apply_filters( 'wpf_sanitize_meta_box', $_POST['wpf-settings-woo-memberships-teams'] );
			update_post_meta( $post_id, 'wpf-settings-woo-memberships-teams', $data );

		} else {
			delete_post_meta( $post_id, 'wpf-settings-woo-memberships-teams' );
		}
	}

	/**
	 * Sets field labels and types for WooCommerce custom fields
	 *
	 * @access  public
	 * @return  array Meta fields
	 */
	public function add_meta_fields( $meta_fields ) {

		$meta_fields['wc_memberships_for_teams_team_role'] = array(
			'label'  => 'Memberships for Teams Role',
			'type'   => 'text',
			'group'  => 'woocommerce_memberships',
			'pseudo' => true,
		);

		$meta_fields['wc_memberships_for_teams_team_name'] = array(
			'label'  => 'Memberships for Teams Team Name',
			'type'   => 'text',
			'group'  => 'woocommerce_memberships',
			'pseudo' => true,
		);

		$meta_fields['wc_memberships_for_teams_team_id'] = array(
			'label'  => 'Memberships for Teams Team ID',
			'type'   => 'int',
			'group'  => 'woocommerce_memberships',
			'pseudo' => true,
		);

		return $meta_fields;
	}

	/**
	 * Process Team Member.
	 * Checks if the team has tags saved and applies them to team members.
	 *
	 * @since 3.43.6
	 *
	 * @param int $team_id The team ID.
	 * @param int $user_id The user ID.
	 */
	public function process_team_member( $team_id, $user_id ) {

		// Prevent looping.
		remove_action( 'wpf_tags_modified', array( $this, 'maybe_add_user_to_team' ), 10, 2 );

		$defaults = array(
			'apply_tags_memberships_teams' => array(),
			'link_tag_memberships_teams'   => array(),
		);

		$settings = wp_parse_args( (array) get_post_meta( $team_id, 'wpf-settings-woo-memberships-teams', true ), $defaults );

		$apply_tags = array_merge( $settings['apply_tags_memberships_teams'], $settings['link_tag_memberships_teams'] );

		if ( ! empty( $apply_tags ) ) {

			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
		}

		add_action( 'wpf_tags_modified', array( $this, 'maybe_add_user_to_team' ), 10, 2 );
	}

	/**
	 * Get User ID
	 * Gets the user ID from a team member ID.
	 *
	 * @since 3.43.6
	 *
	 * @param int $member_id The team member ID.
	 */
	public function get_user_id( $member_id ) {

		$user_membership = wc_memberships_get_user_membership( $member_id );
		$user_id         = $user_membership->get_user_id();

		if ( empty( $user_id ) ) {
			return false;
		}

		return $user_id;
	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds Woo Memberships for Teams batch operation.
	 *
	 * @since 3.37.26
	 * @since 3.43.6 Added team tags batch operation.
	 *
	 * @param  array $options The export options.
	 * @return array The export options.
	 */
	public function export_options( $options ) {

		$options['woo_memberships_teams'] = array(
			'label'   => __( 'WooCommerce Memberships for Teams team meta', 'wp-fusion' ),
			'title'   => __( 'Members', 'wp-fusion' ),
			/* translators: %s is the CRM name */
			'tooltip' => sprintf( __( 'For each member who is part of a team, syncs the team name and that member\'s role in the team to the corresponding custom fields in %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$options['woo_memberships_teams_tags'] = array(
			'label'   => __( 'WooCommerce Memberships for Teams team tags', 'wp-fusion' ),
			'title'   => __( 'Members', 'wp-fusion' ),
			/* translators: %s is the CRM name */
			'tooltip' => sprintf( __( 'For each member who is part of a team, applies any tags configured on that team in %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		return $options;
	}

	/**
	 * Get the all the members to export.
	 *
	 * @since  3.37.26
	 *
	 * @return array User membership IDs.
	 */
	public function batch_init() {
		$args  = array(
			'post_type'        => 'wc_user_membership',
			'posts_per_page'   => '-1',
			'fields'           => 'ids',
			'nopaging'         => true,
			'suppress_filters' => 1,
			'meta_query'       => array(
				array(
					'key'     => '_team_id',
					'compare' => 'EXISTS',
				),
			),
		);
		$query = new \WP_Query( $args );
		return $query->posts;
	}


	/**
	 * Batch Step
	 * Process team members one by one.
	 *
	 * @since 3.37.26
	 * @since 3.43.6 Removed $user_membership object, replaced with local get_user_id().
	 *
	 * @param int $member_id The team member ID.
	 */
	public function batch_step( $member_id ) {

		$user_id     = $this->get_user_id( $member_id );
		$team_id     = absint( get_post_meta( $member_id, '_team_id', true ) );
		$team_member = wc_memberships_for_teams_get_team_member( $team_id, $user_id );

		if ( false === $team_member ) { // if user was deleted or removed from team.
			return;
		}

		$update_data = array(
			'wc_memberships_for_teams_team_role' => $team_member->get_role(),
			'wc_memberships_for_teams_team_name' => $team_member->get_team()->get_name(),
			'wc_memberships_for_teams_team_id'   => $team_id,
		);

		wp_fusion()->user->push_user_meta( $user_id, $update_data );
	}

	/**
	 * Batch Step Tags
	 * Syncs team tags to team members one by one.
	 *
	 * @since 3.43.6
	 *
	 * @param int $member_id The team member ID.
	 */
	public function batch_step_tags( $member_id ) {

		$user_id = $this->get_user_id( $member_id );
		$teams   = wc_memberships_for_teams_get_teams( $user_id );

		if ( false === $teams ) {
			return;
		}

		foreach ( $teams as $team ) {

			$this->process_team_member( $team->get_id(), $user_id );
		}
	}
}

new WPF_Woo_Memberships_Teams();
