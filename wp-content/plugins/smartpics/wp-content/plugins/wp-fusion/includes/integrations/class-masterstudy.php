<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_MasterStudy extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.43.1
	 * @var string $slug
	 */

	public $slug = 'masterstudy';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.43.1
	 * @var string $name
	 */
	public $name = 'MasterStudy';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.43.1
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/learning-management/masterstudy/';

	/**
	 * Default settings for courses.
	 *
	 * @since 3.44.19
	 * @var array $default_settings
	 */
	public $default_settings = array(
		'apply_tags_start'    => array(),
		'apply_tags_complete' => array(),
		'tag_link'            => array(),
	);

	/**
	 * Gets things started.
	 *
	 * @since 3.43.1
	 */
	public function init() {

		add_action( 'add_user_course', array( $this, 'course_enrolled' ), 10, 2 );
		add_action( 'stm_lms_progress_updated', array( $this, 'user_finish_course' ), 10, 3 );
		add_action( 'stm_lms_lesson_passed', array( $this, 'user_complete_lesson' ), 10, 3 );

		// Auto enrollments.
		add_action( 'wpf_tags_modified', array( $this, 'update_course_access' ), 10, 2 );

		// Access control.
		add_filter( 'stm_lms_has_course_access', array( $this, 'can_view_content' ), 10, 3 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 20, 2 );
	}


	/**
	 * Applies tags when a user enrolls in a MasterStudy course.
	 *
	 * @since 3.43.1
	 *
	 * @param int $user_id   The user ID.
	 * @param int $course_id The course ID.
	 */
	public function course_enrolled( $user_id, $course_id ) {

		$settings = wp_parse_args( get_post_meta( $course_id, 'wpf_settings_masterstudy', true ), $this->default_settings );

		$apply_tags = array_merge( $settings['apply_tags_start'], $settings['tag_link'] );

		if ( ! empty( $apply_tags ) ) {

			remove_action( 'wpf_tags_modified', array( $this, 'update_course_access' ) );

			wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			add_action( 'wpf_tags_modified', array( $this, 'update_course_access' ), 10, 2 );

		}
	}



	/**
	 * Applies tags when a MasterStudy course is completed
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @param int $progress
	 */
	public function user_finish_course( $course_id, $user_id, $progress ) {

		$wpf_settings = get_post_meta( $course_id, 'wpf_settings_masterstudy', true );

		if ( ! empty( $wpf_settings['apply_tags_complete'] ) && 100 === $progress ) {
			wp_fusion()->user->apply_tags( $wpf_settings['apply_tags_complete'], $user_id );
		}
	}

	/**
	 * Applies tags when a MasterStudy lesson is completed
	 *
	 * @param int $user_id
	 * @param int $lesson_id
	 * @param int $course_id
	 */
	public function user_complete_lesson( $user_id, $lesson_id, $course_id ) {

		$wpf_settings = get_post_meta( $lesson_id, 'wpf_settings_masterstudy', true );

		if ( ! empty( $wpf_settings['apply_tags_complete'] ) ) {
			wp_fusion()->user->apply_tags( $wpf_settings['apply_tags_complete'], $user_id );
		}
	}

	/**
	 * Update user course enrollments when tags are modified.
	 *
	 * @since  3.43.1
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user tags.
	 */
	public function update_course_access( $user_id, $user_tags ) {

		$linked_courses = get_posts(
			array(
				'post_type'  => 'stm-courses',
				'nopaging'   => true,
				'meta_query' => array(
					array(
						'key'     => 'wpf_settings_masterstudy',
						'compare' => 'EXISTS',
					),
				),
				'fields'     => 'ids',
			)
		);

		// Update course access based on user tags.
		if ( ! empty( $linked_courses ) ) {

			foreach ( $linked_courses as $course_id ) {

				$enrolled = STM_LMS_Course::get_user_course( $user_id, $course_id );

				$settings = get_post_meta( $course_id, 'wpf_settings_masterstudy', true );

				if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
					continue;
				}

				$tag_id = $settings['tag_link'][0];

				if ( in_array( $tag_id, $user_tags ) && ! $enrolled ) {

					// Needs auto-enrollment.

					wpf_log(
						'info',
						$user_id,
						'User auto-enrolled in MasterStudy course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>'
					);

					STM_LMS_Course::add_user_course(
						$course_id,
						$user_id,
						STM_LMS_Lesson::get_first_lesson( $course_id )
					);

					STM_LMS_Course::add_student( $course_id );

				} elseif ( ! in_array( $tag_id, $user_tags ) && $enrolled ) {

					// Needs un-enrollment.

					wpf_log(
						'info',
						$user_id,
						'User un-enrolled from MasterStudy course <a href="' . admin_url( 'post.php?post=' . $course_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $course_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>'
					);

					stm_lms_get_delete_user_course( $user_id, $course_id );
					STM_LMS_Course::remove_student( $course_id );

				}
			}
		}
	}

	/**
	 * Can a user access a lesson / other item within a course.
	 *
	 * @since 3.43.1
	 *
	 * @param bool $can_view Whether or not the user can view the content.
	 * @param int  $course_id  The course ID.
	 * @param int  $item_id  The course, lesson, or other item ID.
	 * @return bool Whether or not the user can view the content.
	 */
	public function can_view_content( $can_view, $course_id, $item_id ) {

		if ( ! wpf_user_can_access( $item_id ) ) {
			return false;
		}

		return $can_view;
	}


	/**
	 * Adds meta boxes.
	 *
	 * @since 3.43.1
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data The post data.
	 */
	public function add_meta_box( $post_id, $data ) {

		add_meta_box( 'wpf-masterstudy-meta', 'WP Fusion - Course Settings', array( $this, 'meta_box_callback_course' ), 'stm-courses' );
		add_meta_box( 'wpf-masterstudy-meta', 'WP Fusion - Lesson Settings', array( $this, 'meta_box_callback_lesson' ), 'stm-lessons' );
	}


	/**
	 * Meta box callback for courses.
	 *
	 * @since 3.43.1
	 *
	 * @param WP_Post $post The post.
	 */
	public function meta_box_callback_course( $post ) {
		global $post;

		$settings = wp_parse_args( get_post_meta( $post->ID, 'wpf_settings_masterstudy', true ), $this->default_settings );

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_start">' . esc_html__( 'Apply Tags - Enrolled', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_start'],
			'meta_name' => 'wpf_settings_masterstudy',
			'field_id'  => 'apply_tags_start',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">' . esc_html( sprintf( __( 'These tags will be applied in %s when someone is enrolled in this course.', 'wp-fusion' ), wp_fusion()->crm->name ) ) . '</span>';
		echo '</td>';

		echo '</tr>';

		echo '<tr>';

		echo '<th scope="row"><label for="tag_link">' . esc_html__( 'Link with Tag', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['tag_link'],
			'meta_name' => 'wpf_settings_masterstudy',
			'field_id'  => 'tag_link',
			'limit'     => 1,
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">' . esc_html( sprintf( __( 'This tag will be applied in %1$s when a user is enrolled, and will be removed when a user is unenrolled. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this course. If this tag is removed, the user will be removed from the course.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ) ) . '</span>';
		echo '</td>';

		echo '</tr>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_complete">' . esc_html__( 'Apply Tags - Completed', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_complete'],
			'meta_name' => 'wpf_settings_masterstudy',
			'field_id'  => 'apply_tags_complete',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">' . esc_html__( 'Apply these tags when the course is marked complete.', 'wp-fusion' ) . '</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';
	}


	/**
	 * Meta box callback for lessons.
	 *
	 * @since 3.43.1
	 *
	 * @param WP_Post $post The post.
	 */
	public function meta_box_callback_lesson( $post ) {

		wp_nonce_field( 'wpf_meta_box_masterstudy', 'wpf_meta_box_masterstudy_nonce' );

		$settings = array(
			'apply_tags_complete' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf_settings_masterstudy', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf_settings_masterstudy', true ) );
		}

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="tag_link">Apply tags when completed:</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_complete'],
			'meta_name' => 'wpf_settings_masterstudy',
			'field_id'  => 'apply_tags_complete',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">These tags will be applied to the user when they complete the lesson.</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';
	}


	/**
	 * Save the meta box data
	 *
	 * @since 3.43.1
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_meta_box_data( $post_id ) {

		// Check if our data is set.
		if ( ! isset( $_POST['wpf_settings_masterstudy'] ) ) {
			return;
		}

		$data = WPF_Admin_Interfaces::sanitize_tags_settings( wp_unslash( $_POST['wpf_settings_masterstudy'] ) );

		if ( ! empty( $data ) ) {
			update_post_meta( $post_id, 'wpf_settings_masterstudy', $data );
		} else {
			delete_post_meta( $post_id, 'wpf_settings_masterstudy' );
		}
	}
}

new WPF_MasterStudy();
