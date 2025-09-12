<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Memberoni extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'memberoni';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'Memberoni';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/learning-management/memberoni/';

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.26.1
	 * @return  void
	 */
	public function init() {

		add_action( 'memberoni_after_mark_complete', array( $this, 'mark_complete' ) );
		add_action( 'memberoni_after_track_lesson', array( $this, 'mark_complete' ) );
		add_action( 'memberoni_after_roadmap_step', array( $this, 'mark_complete' ) );
		add_action( 'memberoni_after_roadmap_complete', array( $this, 'mark_complete' ) );

		// Add handlers for when lessons/steps are marked incomplete
		add_action( 'memberoni_after_track_lesson_unset', array( $this, 'mark_incomplete' ) );
		add_action( 'memberoni_after_roadmap_step_unset', array( $this, 'mark_incomplete' ) );

		// Add handlers for when courses/roadmaps are reset
		add_action( 'memberoni_after_clear_course_count', array( $this, 'mark_course_reset' ) );
		add_action( 'memberoni_after_clear_roadmap_count', array( $this, 'mark_roadmap_reset' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 20, 2 );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );
	}

	/**
	 * Apply tags when course, lesson, or roadmap step marked complete.
	 *
	 * @since 3.45.1
	 *
	 * @param int $post_id The ID of the post.
	 */
	public function mark_complete( $post_id ) {

		$settings = (array) get_post_meta( $post_id, 'wpf_settings_memberoni', true );

		if ( ! empty( $settings['apply_tags_complete'] ) ) {
			wp_fusion()->user->apply_tags( $settings['apply_tags_complete'] );
		}
	}

	/**
	 * Remove tags when lesson or roadmap step marked incomplete.
	 * Also removes tags from parent course/roadmap.
	 *
	 * @since 3.45.1
	 *
	 * @param int $post_id The ID of the post.
	 */
	public function mark_incomplete( $post_id ) {

		$settings = (array) get_post_meta( $post_id, 'wpf_settings_memberoni', true );

		// Remove tags for this specific lesson/step
		if ( ! empty( $settings['apply_tags_complete'] ) ) {
			wp_fusion()->user->remove_tags( $settings['apply_tags_complete'] );
		}

		// Get parent to remove its tags too (since the course/roadmap is now incomplete)
		$parent = wp_get_post_parent_id( $post_id );
		if ( $parent ) {
			// If we have multiple levels, find the top-most parent
			$ancestors = get_post_ancestors( $post_id );
			if ( ! empty( $ancestors ) ) {
				$root   = count( $ancestors ) - 1;
				$parent = isset( $ancestors[ $root ] ) ? $ancestors[ $root ] : $parent;
			}

			// Remove tags from the parent course/roadmap
			$parent_settings = (array) get_post_meta( $parent, 'wpf_settings_memberoni', true );
			if ( ! empty( $parent_settings['apply_tags_complete'] ) ) {
				wp_fusion()->user->remove_tags( $parent_settings['apply_tags_complete'] );
			}
		}
	}

	/**
	 * Remove tags when course progress is reset.
	 *
	 * @since 3.45.1
	 *
	 * @param int $post_id The ID of the course.
	 */
	public function mark_course_reset( $post_id ) {

		$settings = (array) get_post_meta( $post_id, 'wpf_settings_memberoni', true );

		if ( ! empty( $settings['apply_tags_complete'] ) ) {
			wp_fusion()->user->remove_tags( $settings['apply_tags_complete'] );
		}

		// Also remove tags from all child lessons
		$course_children = get_pages(
			array(
				'parent'      => $post_id,
				'post_type'   => 'memberoni_course',
				'post_status' => 'publish',
			)
		);

		if ( ! empty( $course_children ) ) {
			foreach ( $course_children as $child ) {
				$child_settings = (array) get_post_meta( $child->ID, 'wpf_settings_memberoni', true );
				if ( ! empty( $child_settings['apply_tags_complete'] ) ) {
					wp_fusion()->user->remove_tags( $child_settings['apply_tags_complete'] );
				}

				// Handle modules (which can contain lessons)
				if ( 'module' === get_field( 'course_page_type', $child->ID ) ) {
					$module_children = get_pages(
						array(
							'parent'      => $child->ID,
							'post_type'   => 'memberoni_course',
							'post_status' => 'publish',
						)
					);

					if ( ! empty( $module_children ) ) {
						foreach ( $module_children as $module_child ) {
							$module_child_settings = (array) get_post_meta( $module_child->ID, 'wpf_settings_memberoni', true );
							if ( ! empty( $module_child_settings['apply_tags_complete'] ) ) {
								wp_fusion()->user->remove_tags( $module_child_settings['apply_tags_complete'] );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Remove tags when roadmap progress is reset.
	 *
	 * @since 3.45.1
	 *
	 * @param int $post_id The ID of the roadmap.
	 */
	public function mark_roadmap_reset( $post_id ) {

		$settings = (array) get_post_meta( $post_id, 'wpf_settings_memberoni', true );

		if ( ! empty( $settings['apply_tags_complete'] ) ) {
			wp_fusion()->user->remove_tags( $settings['apply_tags_complete'] );
		}

		// Also remove tags from all child steps
		$roadmap_children = get_pages(
			array(
				'parent'      => $post_id,
				'post_type'   => 'memberoni_roadmap',
				'post_status' => 'publish',
			)
		);

		if ( ! empty( $roadmap_children ) ) {
			foreach ( $roadmap_children as $child ) {
				if ( 'step' === get_field( 'roadmap_page_type', $child->ID ) ) {
					$child_settings = (array) get_post_meta( $child->ID, 'wpf_settings_memberoni', true );
					if ( ! empty( $child_settings['apply_tags_complete'] ) ) {
						wp_fusion()->user->remove_tags( $child_settings['apply_tags_complete'] );
					}
				}
			}
		}
	}

	/**
	 * Adds meta box
	 */
	public function add_meta_box( $post_id, $data ) {

		add_meta_box( 'wpf-memberoni-meta', 'WP Fusion Settings', array( $this, 'meta_box_callback' ), array( 'memberoni_course', 'memberoni_roadmap' ) );
	}


	/**
	 * Displays meta box content
	 *
	 * @param WP_Post $post The post object.
	 */
	public function meta_box_callback( $post ) {

		$settings = array(
			'apply_tags_complete' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf_settings_memberoni', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf_settings_memberoni', true ) );
		}

		wp_nonce_field( 'wpf_meta_box_memberoni', 'wpf_meta_box_memberoni_nonce' );

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="tag_link">' . esc_html__( 'Apply tags when completed:', 'wp-fusion' ) . '</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_complete'],
			'meta_name' => 'wpf_settings_memberoni',
			'field_id'  => 'apply_tags_complete',
		);

		wpf_render_tag_multiselect( $args );

		$post_type    = get_post_type( $post );
		$content_type = 'course';

		if ( 'memberoni_roadmap' === $post_type ) {
			$content_type = 'step' === get_post_meta( $post->ID, 'roadmap_page_type', true ) ? 'roadmap step' : 'roadmap';
		} elseif ( 'lesson' === get_post_meta( $post->ID, 'course_page_type', true ) ) {
			$content_type = 'lesson';
		}

		// translators: %1$s is the CRM name, %2$s is the content type (course, lesson, roadmap, etc.)
		$description = sprintf( esc_html__( 'The selected tags will be applied in %1$s when this %2$s is marked complete, and removed when marked incomplete.', 'wp-fusion' ), esc_html( wp_fusion()->crm->name ), esc_html( $content_type ) );

		// Add additional info for lessons and roadmap steps
		if ( 'lesson' === $content_type || 'roadmap step' === $content_type ) {
			$description .= ' ' . esc_html__( 'When this is marked incomplete, completion tags from the parent course/roadmap will also be removed.', 'wp-fusion' );
		}

		echo '<span class="description">' . esc_html( $description ) . '</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';
	}

	/**
	 * Runs when WPF meta box is saved
	 *
	 * @param int $post_id The ID of the post.
	 */
	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_memberoni_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_memberoni_nonce'], 'wpf_meta_box_memberoni' ) ) {
			return;
		}

		if ( ! empty( $_POST['wpf_settings_memberoni'] ) ) {
			update_post_meta( $post_id, 'wpf_settings_memberoni', $_POST['wpf_settings_memberoni'] );
		} else {
			delete_post_meta( $post_id, 'wpf_settings_memberoni' );
		}
	}
}

new WPF_Memberoni();
