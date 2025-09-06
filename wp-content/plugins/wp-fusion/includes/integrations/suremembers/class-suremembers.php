<?php
/**
 * Holds the Integration for SureMembers.
 *
 * @package WP_Fusion
 */

use WP_Fusion\Includes\Admin\WPF_Tags_Select_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles the integration with SureMembers.
 *
 * @since 3.41.23
 */
class WPF_SureMembers extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.41.23
	 *
	 * @var string $slug
	 */

	public $slug = 'suremembers';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.41.23
	 *
	 * @var string $name
	 */
	public $name = 'SureMembers';

	/**
	 * Gets things started.
	 *
	 * @since 3.41.23
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
		add_action( 'suremembers_after_access_grant', array( $this, 'add_user_tags' ), 10, 2 );
		add_action( 'suremembers_after_access_revoke', array( $this, 'remove_user_tags' ), 10, 2 );
		add_action( 'suremembers_after_submit_form', array( $this, 'save_meta_box_data' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue Assets.
	 *
	 * @since 3.41.23
	 * @since 3.41.44 Fixed PHP errors, PHPCS errors, and array to string conversion.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// phpcs:ignore
		if ( ! isset( $_GET['page'] ) || 'suremembers_rules' !== $_GET['page'] || ! isset( $_GET['post_id'] ) ) {
			return;
		}

		wp_enqueue_script(
			'wpf-suremembers-integration',
			WPF_DIR_URL . 'build/suremembers-integration.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wpf-admin' ),
			WP_FUSION_VERSION,
			true
		);

		// phpcs:ignore
		$post_id  = intval( sanitize_text_field( $_GET['post_id'] ) );
		$settings = get_post_meta( $post_id, 'suremembers_plan_rules', true );

		$args = array(
			'nonce'             => wp_create_nonce( 'wpf_meta_box_suremembers' ),
			'apply_tags'        => WPF_Tags_Select_API::format_tags_to_props( $settings['apply_tags'] ?? array() ),
			'tag_link'          => WPF_Tags_Select_API::format_tags_to_props( $settings['tag_link'] ?? array() ),
			'raw_apply_tags'    => WPF_Tags_Select_API::format_tags_to_string( $settings['apply_tags'] ?? array() ),
			'raw_tag_link'      => WPF_Tags_Select_API::format_tags_to_string( $settings['tag_link'] ?? array() ),
			'apply_tags_string' => sprintf(
				// translators: %s is the name of the CRM.
				__( 'Apply the selected tags in %s when a user is added to this access group.', 'wp-fusion' ),
				wp_fusion()->crm->name,
			),
			'tag_link_string'   => sprintf(
				// translators: %s is the name of the CRM.
				__( 'Select a tag to link with this access group. When the tag is applied in %s, the user will be enrolled. When the tag is removed, the user will be unenrolled.', 'wp-fusion' ),
				wp_fusion()->crm->name
			),
		);

		wp_localize_script( 'wpf-suremembers-integration', 'wpf_suremembers', $args );
	}

	/**
	 * Adds tags to the user when they are added to a group.
	 *
	 * @since 3.41.23
	 * @since 3.41.44 Fixed issue where tags were not being added to the user.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $access_group_ids The ID of the access group(s) that is being granted.
	 *
	 * @return void
	 */
	public function add_user_tags( $user_id, array $access_group_ids = array() ) {
		// @phpstan-ignore-next-line - We are removing the action using priority and arguments for safety.
		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		$user_tags = wpf_get_tags( $user_id );

		foreach ( $access_group_ids as $group_id ) {
			$settings = get_post_meta( $group_id, 'suremembers_plan_rules', true );

			if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_id   = $settings['apply_tags'][0];
			$tag_link = $settings['tag_link'];

			$user_can_access = get_user_meta( $user_id, 'suremembers_user_access_group_' . $group_id, true );
			$group_url       = admin_url( 'edit.php?post_type=wsm_access_group&page=suremembers_rules&post_id=' . $group_id );
			$group_title     = get_the_title( $group_id );
			$tag_label       = wpf_get_tag_label( $tag_link[0] );

			if (
				! array_intersect( $tag_link, $user_tags ) &&
				'active' === $user_can_access['status'] &&
				! user_can( $user_id, 'manage_options' )
			) {
				wpf_log(
					'info',
					$user_id,
					"User added to access group <a href=\"$group_url\">$group_title</a> Applying link tag <strong>$tag_label</strong>."
				);

				wp_fusion()->user->apply_tags( $tag_link, $user_id );
			}

			if (
				// The type of $tag_id is unknown so we can't use strict.
				// phpcs:ignore
				! in_array( $tag_id, $user_tags ) &&
				'active' === $user_can_access['status'] &&
				! user_can( $user_id, 'manage_options' )
			) {
				wpf_log(
					'info',
					$user_id,
					"User added to access group <a href=\"$group_url\">$group_title</a>. Applying tags."
				);

				wp_fusion()->user->apply_tags( $settings['apply_tags'], $user_id );
			}
		}

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
	}

	/**
	 * Removes the link tag when a user is removed from a group.
	 *
	 * @since 3.41.23
	 * @since 3.41.44 Cleaned up code, removed unnecessary checks & fixed tags not being applied.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $access_group_ids The ID of the access group(s) that is being revoked.
	 *
	 * @return void
	 */
	public function remove_user_tags( $user_id, array $access_group_ids = array() ) {
		// @phpstan-ignore-next-line - We are removing the action using priority and arguments for safety.
		remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		$user_tags = wpf_get_tags( $user_id );

		foreach ( $access_group_ids as $group_id ) {
			$settings = get_post_meta( $group_id, 'suremembers_plan_rules', true );

			if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_link        = $settings['tag_link'];
			$user_can_access = get_user_meta( $user_id, 'suremembers_user_access_group_' . $group_id, true );

			if (
				array_intersect( $tag_link, $user_tags ) &&
				'revoked' === $user_can_access['status'] &&
				! user_can( $user_id, 'manage_options' )
			) {
				$group_url   = admin_url( 'edit.php?post_type=wsm_access_group&page=suremembers_rules&post_id=' . $group_id );
				$group_title = get_the_title( $group_id );
				$tag_label   = wpf_get_tag_label( $tag_link[0] );

				wpf_log(
					'info',
					$user_id,
					"User removed from access group <a href=\"$group_url\">$group_title</a>. Removing link tag <strong>$tag_label</strong>."
				);

				wp_fusion()->user->remove_tags( $tag_link, $user_id );
			}
		}

		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );
	}

	/**
	 * Updates user's access groups if a tag linked to a SureMembers access group is changed.
	 *
	 * @since 3.41.23
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user tags.
	 *
	 * @return void
	 */
	public function tags_modified( $user_id, array $user_tags = array() ) {
		$access_groups      = SureMembers\Inc\Access_Groups::get_active();
		$user_access_groups = (array) get_user_meta( $user_id, 'suremembers_user_access_group', true );

		foreach ( $access_groups as $group_id => $group ) {
			$settings = get_post_meta( $group_id, 'suremembers_plan_rules', true );

			if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_id          = $settings['tag_link'][0];
			$user_can_access = get_user_meta( $user_id, 'suremembers_user_access_group_' . $group_id, true );
			$tag_label       = wpf_get_tag_label( $tag_id );
			$group_url       = admin_url( 'edit.php?post_type=wsm_access_group&page=suremembers_rules&post_id=' . $group_id );

			// The type of $tag_id is unknown so we can't use strict.
			if (
				// phpcs:ignore
				( in_array( $tag_id, $user_tags ) && empty( $user_access_groups ) ) ||
				// phpcs:ignore
				( in_array( $tag_id, $user_tags ) && ! in_array( $group_id, $user_access_groups ) ) ||
				// phpcs:ignore
				( in_array( $tag_id, $user_tags ) && 'revoked' === $user_can_access['status'] )
			) {
				wpf_log(
					'info',
					$user_id,
					"Linked tag <strong>$tag_label</strong> applied to user. Adding user to access group <a href=\"$group_url\">$group</a>."
				);
				SureMembers\Inc\Access::grant( $user_id, $group_id, 'wp-fusion' );
			} elseif (
				// phpcs:ignore
				! in_array( $tag_id, $user_tags ) &&
				// phpcs:ignore
				in_array( $group_id, $user_access_groups ) &&
				'active' === $user_can_access['status']
			) {
				wpf_log(
					'info',
					$user_id,
					"Linked tag <strong>$tag_label</strong> removed from user. Removing user from access group <a href=\"$group_url\">$group</a>."
				);
				SureMembers\Inc\Access::revoke( $user_id, $group_id );
			}
		}
	}

	/**
	 * Saves SureMembers meta box data.
	 *
	 * @since 3.41.23
	 * @since 3.41.44 Cleaned up code.
	 *
	 * @param int $access_group The access group ID.
	 *
	 * @return void
	 */
	public function save_meta_box_data( $access_group ) {
		if (
			! isset( $_POST['wpf_meta_box_suremembers_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpf_meta_box_suremembers_nonce'] ) ), 'wpf_meta_box_suremembers' ) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			empty( $_POST['suremembers_post'] )
		) {
			return;
		}

		$settings   = get_post_meta( $access_group, 'suremembers_plan_rules', true );
		$apply_tags = ! empty( $settings['apply_tags'] ) ? $settings['apply_tags'] : null;
		$tag_link   = ! empty( $settings['tag_link'] ) ? $settings['tag_link'] : null;

		if ( ! empty( $_POST['wp_fusion']['apply_tags'] ) ) {
			$post_tags  = sanitize_text_field( wp_unslash( $_POST['wp_fusion']['apply_tags'] ) );
			$apply_tags = explode( ',', $post_tags );

			$_POST['suremembers_post']['apply_tags'] = $apply_tags;
		}

		if ( ! empty( $_POST['wp_fusion']['tag_link'] ) ) {
			$post_tags = sanitize_text_field( wp_unslash( $_POST['wp_fusion']['tag_link'] ) );
			$tag_link  = explode( ',', $post_tags );

			$_POST['suremembers_post']['tag_link'] = $tag_link;
		}

		// PHPCS doesn't recognize the sanitize_recursively method.
		// phpcs:ignore
		$data = SureMembers\Inc\Utils::sanitize_recursively( 'sanitize_text_field', $_POST['suremembers_post'] );
		$data = SureMembers\Inc\Utils::remove_blank_array( $data );

		update_post_meta( $access_group, 'suremembers_plan_rules', $data );
	}
}

new WPF_SureMembers();
