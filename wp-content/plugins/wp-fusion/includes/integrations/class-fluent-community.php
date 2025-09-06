<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * FluentCommunity integration.
 *
 * @since 3.44.20
 * @link https://wpfusion.com/documentation/membership/fluent-community/
 */
class WPF_FluentCommunity extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.44.20
	 * @var string $slug
	 */
	public $slug = 'fluent-community';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.44.20
	 * @var string $name
	 */
	public $name = 'FluentCommunity';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.44.20
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/fluent-community/';

	/**
	 * Gets things started.
	 *
	 * @since 3.44.20
	 */
	public function init() {

		// Tag sync
		add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 );

		// Registration
		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );

		// Add portal access control
		add_filter( 'fluent_community/can_access_portal', array( $this, 'can_access_portal' ) );

		// Space membership hooks
		add_action( 'fluent_community/space/joined', array( $this, 'space_joined' ), 10, 3 );
		add_action( 'fluent_community/space/user_left', array( $this, 'space_left' ), 10, 3 );

		// Course hooks
		add_action( 'fluent_community/course/enrolled', array( $this, 'course_enrolled' ), 10, 2 );
		add_action( 'fluent_community/course/unenrolled', array( $this, 'course_unenrolled' ), 10, 2 );
		add_action( 'fluent_community/course/completed', array( $this, 'course_completed' ), 10, 2 );
		add_action( 'fluent_community/course/lesson_completed', array( $this, 'lesson_completed' ), 10, 2 );

		// Admin settings
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 40 );

		// Settings
	}


	/**
	 * Handles tag updates from the CRM and syncs course enrollments
	 *
	 * @since  3.44.20
	 *
	 * @param int   $user_id   The user ID.
	 * @param array $user_tags The user's tags.
	 */
	public function tags_modified( $user_id, $user_tags ) {

		$courses = $this->get_courses();

		foreach ( $courses as $course_id => $title ) {

			$tag_link = $this->get_setting( 'fc_course_' . $course_id . '_tag_link' );

			if ( empty( $tag_link ) ) {
				continue;
			}

			$tag_link = $tag_link[0]; // It's always a single tag.

			$is_enrolled = \FluentCommunity\Modules\Course\Services\CourseHelper::isEnrolled( $course_id, $user_id );

			if ( in_array( $tag_link, $user_tags ) && ! $is_enrolled ) {

				// Enroll in course.
				\FluentCommunity\Modules\Course\Services\CourseHelper::enrollCourse( $course_id, $user_id );

				wpf_log( 'info', $user_id, 'User enrolled in FluentCommunity course <strong>' . $title . '</strong> by linked tag <strong>' . wpf_get_tag_label( $tag_link ) . '</strong>' );

			} elseif ( ! in_array( $tag_link, $user_tags ) && $is_enrolled ) {

				// Un-enroll from course.
				\FluentCommunity\Modules\Course\Services\CourseHelper::leaveCourse( $course_id, $user_id );

				wpf_log( 'info', $user_id, 'User un-enrolled from FluentCommunity course <strong>' . $title . '</strong> by linked tag <strong>' . wpf_get_tag_label( $tag_link ) . '</strong>' );
			}
		}

		// Do the same for spaces
		$spaces = $this->get_spaces();

		foreach ( $spaces as $space_id => $title ) {

			$tag_link = $this->get_setting( 'fc_space_' . $space_id . '_tag_link' );

			if ( empty( $tag_link ) ) {
				continue;
			}

			$tag_link = $tag_link[0]; // It's always a single tag.

			$is_member = \FluentCommunity\App\Services\Helper::isUserInSpace( $user_id, $space_id );

			if ( in_array( $tag_link, $user_tags ) && ! $is_member ) {

				// Join space.
				\FluentCommunity\App\Services\Helper::addToSpace( $space_id, $user_id, 'member', 'by_admin' );

				wpf_log( 'info', $user_id, 'User joined FluentCommunity space <strong>' . $title . '</strong> by linked tag <strong>' . wpf_get_tag_label( $tag_link ) . '</strong>' );

			} elseif ( ! in_array( $tag_link, $user_tags ) && $is_member ) {

				// Leave space.
				\FluentCommunity\App\Services\Helper::removeFromSpace( $space_id, $user_id, 'by_admin' );

				wpf_log( 'info', $user_id, 'User left FluentCommunity space <strong>' . $title . '</strong> by linked tag <strong>' . wpf_get_tag_label( $tag_link ) . '</strong>' );
			}
		}
	}

	/**
	 * Handles registration data before it's sent to the CRM
	 *
	 * @since  3.44.20
	 *
	 * @param  array $post_data   The registration form data.
	 * @param  int   $user_id     The user ID.
	 * @return array The update data.
	 */
	public function user_register( $post_data, $user_id ) {

		if ( ! isset( $post_data['action'] ) || 'fcom_user_registration' !== $post_data['action'] ) {
			return $post_data;
		}

		if ( ! empty( $post_data['full_name'] ) ) {

			$parts = explode( ' ', $post_data['full_name'] );

			if ( count( $parts ) > 1 ) {
				$last_name  = array_pop( $parts );
				$first_name = implode( ' ', $parts );
			} else {
				$first_name = $post_data['full_name'];
				$last_name  = '';
			}

			$post_data['first_name'] = $first_name;
			$post_data['last_name']  = $last_name;
		}

		return $post_data;
	}


	/**
	 * Controls access to the community portal based on tags
	 *
	 * @since  3.44.20
	 *
	 * @param  bool $can_access_portal Whether the user can access the portal.
	 * @return bool  Access status
	 */
	public function can_access_portal( $can_access_portal ) {

		if ( ! wpf_is_user_logged_in() || wpf_admin_override() ) {
			return $can_access_portal;
		}

		// Make sure the request is actually a request to access the portal
		$request_uri = trim( $_SERVER['REQUEST_URI'], '/' );
		$portal_path = \FluentCommunity\App\Services\Helper::getPortalRequestPath( $request_uri );

		if ( ! $portal_path ) {
			return $can_access_portal;
		}

		$required_tags = $this->get_setting( 'fc_access_tags' );

		if ( ! empty( $required_tags ) ) {
			$can_access = wp_fusion()->user->has_tag( $required_tags );

			if ( ! $can_access ) {
				$redirect = $this->get_setting( 'redirect' );

				if ( ! empty( $redirect ) ) {
					if ( is_numeric( $redirect ) ) {
						$redirect = get_permalink( $redirect );
					}

					wp_safe_redirect( $redirect );
					exit;
				}
			}

			return $can_access;
		}

		return $can_access_portal;
	}

	/**
	 * Handle space joined
	 *
	 * @since  3.44.20
	 *
	 * @param FluentCommunity\App\Models\Space $space    The space object.
	 * @param int                              $user_id  The user ID.
	 * @param array                            $data     Additional data.
	 */
	public function space_joined( $space, $user_id, $data ) {

		// Update meta
		wp_fusion()->user->push_user_meta(
			$user_id,
			array(
				'fc_last_space_joined'      => $space->title,
				'fc_last_space_joined_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);

		$apply_tags = array();

		$tags = $this->get_setting( 'fc_space_' . $space->id . '_tags' );
		if ( ! empty( $tags ) ) {
			$apply_tags = array_merge( $apply_tags, $tags );
		}

		$tag_link = $this->get_setting( 'fc_space_' . $space->id . '_tag_link' );
		if ( ! empty( $tag_link ) ) {
			$apply_tags = array_merge( $apply_tags, $tag_link );
		}

		if ( ! empty( $apply_tags ) ) {
			wpf_log( 'info', $user_id, 'User joined FluentCommunity space <strong>' . $space->title . '</strong>. Applying tags:' );
			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
		}
	}

	/**
	 * Handle space left
	 *
	 * @since  3.44.20
	 *
	 * @param FluentCommunity\App\Models\Space $space    The space object.
	 * @param int                              $user_id  The user ID.
	 * @param array                            $data     Additional data.
	 */
	public function space_left( $space, $user_id, $data ) {

		$remove_tags = array();

		$remove = $this->get_setting( 'fc_space_' . $space->id . '_remove', false );
		$tags   = $this->get_setting( 'fc_space_' . $space->id . '_tags', array() );

		if ( $remove && ! empty( $tags ) ) {
			$remove_tags = array_merge( $remove_tags, $tags );
		}

		$tag_link = $this->get_setting( 'fc_space_' . $space->id . '_tag_link', array() );
		if ( ! empty( $tag_link ) ) {
			$remove_tags = array_merge( $remove_tags, $tag_link );
		}

		if ( ! empty( $remove_tags ) ) {
			wpf_log( 'info', $user_id, 'User left FluentCommunity space <strong>' . $space->title . '</strong>. Removing tags:', );

			remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ) ); // prevent looping.
			wp_fusion()->user->remove_tags( $remove_tags, $user_id );
			add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 ); // prevent looping.
		}
	}

	/**
	 * Handle course enrollment
	 *
	 * @since  3.44.20
	 *
	 * @param FluentCommunity\Modules\Course\Models\Course $course   The course object.
	 * @param int                                          $user_id  The user ID.
	 */
	public function course_enrolled( $course, $user_id ) {

		// Update meta
		wp_fusion()->user->push_user_meta(
			$user_id,
			array(
				'fc_last_course_enrolled'      => $course->title,
				'fc_last_course_enrolled_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);

		$apply_tags = $this->get_setting( 'fc_course_' . $course->id . '_tags', array() );

		$linked_tags = $this->get_setting( 'fc_course_' . $course->id . '_tag_link', array() );

		if ( ! wpf_has_tag( $linked_tags, $user_id ) ) {
			$apply_tags = array_merge( $apply_tags, $linked_tags );
		}

		if ( ! empty( $apply_tags ) ) {
			wpf_log( 'info', $user_id, 'User enrolled in FluentCommunity course <strong>' . $course->title . '</strong>. Applying tags:' );

			remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ) ); // prevent looping.
			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
			add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 ); // prevent looping.
		}
	}

	/**
	 * Handle course unenrollment
	 *
	 * @since  3.44.20
	 *
	 * @param FluentCommunity\Modules\Course\Models\Course $course   The course object.
	 * @param int                                          $user_id  The user ID.
	 */
	public function course_unenrolled( $course, $user_id ) {

		$remove_tags = array();

		$remove = $this->get_setting( 'fc_course_' . $course->id . '_remove', false );
		$tags   = $this->get_setting( 'fc_course_' . $course->id . '_tags', array() );

		if ( $remove && ! empty( $tags ) ) {
			$remove_tags = array_merge( $remove_tags, $tags );
		}

		$tag_link = $this->get_setting( 'fc_course_' . $course->id . '_tag_link', array() );
		if ( ! empty( $tag_link ) ) {
			$remove_tags = array_merge( $remove_tags, $tag_link );
		}

		if ( ! empty( $remove_tags ) ) {
			wpf_log( 'info', $user_id, 'User un-enrolled from FluentCommunity course <strong>' . $course->title . '</strong>. Removing tags:' );

			remove_action( 'wpf_tags_modified', array( $this, 'tags_modified' ) ); // prevent looping.
			wp_fusion()->user->remove_tags( $remove_tags, $user_id );
			add_action( 'wpf_tags_modified', array( $this, 'tags_modified' ), 10, 2 ); // prevent looping.
		}
	}

	/**
	 * Handle course completion
	 *
	 * @since  3.44.20
	 *
	 * @param FluentCommunity\Modules\Course\Models\Course $course   The course object.
	 * @param int                                          $user_id  The user ID.
	 */
	public function course_completed( $course, $user_id ) {

		// Update meta
		wp_fusion()->user->push_user_meta(
			$user_id,
			array(
				'fc_last_course_completed'      => $course->title,
				'fc_last_course_completed_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);

		$apply_tags = $this->get_setting( 'fc_course_' . $course->id . '_complete_tags', array() );

		if ( ! empty( $apply_tags ) ) {
			wpf_log( 'info', $user_id, 'User completed FluentCommunity course <strong>' . $course->title . '</strong>. Applying tags:' );
			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
		}
	}

	/**
	 * Handle lesson completed
	 *
	 * @since  3.44.20
	 *
	 * @param FluentCommunity\Modules\Course\Models\Lesson $lesson   The lesson object.
	 * @param int                                          $user_id  The user ID.
	 */
	public function lesson_completed( $lesson, $user_id ) {

		// Update meta
		wp_fusion()->user->push_user_meta(
			$user_id,
			array(
				'fc_last_lesson_completed'      => $lesson->title,
				'fc_last_lesson_completed_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);
	}


	/**
	 * Gets a setting
	 *
	 * @since  3.44.20
	 *
	 * @param  string $setting The setting key.
	 * @param  mixed  $default The default value.
	 * @return mixed The setting value.
	 */
	public function get_setting( $setting, $default = false ) {
		$settings = get_option( 'wpf_fluent_community_options', array() );
		return isset( $settings[ $setting ] ) ? $settings[ $setting ] : $default;
	}

	/**
	 * Saves settings
	 *
	 * @since  3.44.20
	 */
	public function save_settings() {

		if ( ! isset( $_POST['wpf_fluent_settings_nonce'] ) || ! wp_verify_nonce( $_POST['wpf_fluent_settings_nonce'], 'wpf_fluent_settings' ) ) {
			return;
		}

		$settings = array();

		if ( ! empty( $_POST['wpf-settings'] ) ) {
			$settings = wpf_clean( $_POST['wpf-settings'] );

			// Clean any tag fields
			foreach ( $settings as $key => $value ) {
				if ( strpos( $key, '_tags' ) !== false || strpos( $key, '_tag_link' ) !== false ) {
					$settings[ $key ] = wpf_clean_tags( $value );
				}
			}
		}

		update_option( 'wpf_fluent_community_options', $settings, false );
	}


	/**
	 * Add admin menu item
	 *
	 * @since 3.44.20
	 */
	public function admin_menu() {

		$id = add_submenu_page(
			'fluent-community',
			// translators: placeholder: CRM name.
			sprintf( __( '%s Integration', 'wp-fusion' ), wp_fusion()->crm->name ),
			__( 'WP Fusion', 'wp-fusion' ),
			'manage_options',
			'fluent-community-wpf-settings',
			array( $this, 'render_admin_menu' )
		);

		add_action( 'load-' . $id, array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 3.44.20
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css' );
		wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css' );
	}

	/**
	 * Render admin menu
	 *
	 * @since 3.44.20
	 */
	public function render_admin_menu() {

		if ( ! empty( $_POST ) ) {
			$this->save_settings();
		}

		?>
		<div class="wrap">

			<form id="wpf-fluent-settings" action="" method="post">

				<h1><?php echo wpf_logo_svg(); ?> <?php printf( esc_html__( '%s Integration', 'wp-fusion' ), wp_fusion()->crm->name ); ?></h1>

				<?php wp_nonce_field( 'wpf_fluent_settings', 'wpf_fluent_settings_nonce' ); ?>

				<input type="hidden" name="action" value="update">

				<?php if ( isset( $_POST['wpf_fluent_settings_nonce'] ) && wp_verify_nonce( $_POST['wpf_fluent_settings_nonce'], 'wpf_fluent_settings' ) ) : ?>
					<div id="message" class="updated fade">
						<p><strong><?php esc_html_e( 'Settings saved', 'wp-fusion' ); ?></strong></p>
					</div>
				<?php endif; ?>

				<h3><?php esc_html_e( 'Access Control', 'wp-fusion' ); ?></h3>
				<p class="description"><?php printf( esc_html__( 'Users will only be able to access the community portal if they have any of the specified tags in %s.', 'wp-fusion' ), wp_fusion()->crm->name ); ?></p>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="fc_access_tags"><?php esc_html_e( 'Required Tags', 'wp-fusion' ); ?></label>
							</th>
							<td>
								<?php
								$args = array(
									'setting'   => $this->get_setting( 'fc_access_tags', array() ),
									'meta_name' => 'wpf-settings',
									'field_id'  => 'fc_access_tags',
								);

								wpf_render_tag_multiselect( $args );
								?>
								<p class="description"><?php esc_html_e( 'If no tags are selected, all logged in users will be able to access the community portal.', 'wp-fusion' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpf-redirect"><?php esc_html_e( 'Redirect If Denied', 'wp-fusion' ); ?></label>
							</th>
							<td>
								<?php

								$settings = array(
									'redirect' => $this->get_setting( 'redirect' ),
								);

								wp_fusion()->admin_interfaces->page_redirect_select( null, $settings );

								?>
								<p class="description">
									<?php
									$base_url = \FluentCommunity\App\Services\Helper::baseUrl();
									printf(
										// translators: placeholder: portal settings URL.
										esc_html__( 'Select a page or enter a URL to redirect to if access is denied. Leave blank to show the %1$srestricted content message%2$s.', 'wp-fusion' ),
										'<a href="' . esc_url( $base_url . 'admin/settings' ) . '" target="_blank">',
										'</a>'
									);
									?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<h3><?php esc_html_e( 'Space Settings', 'wp-fusion' ); ?></h3>
				<p class="description"><?php esc_html_e( 'These settings will be applied when users join or leave spaces.', 'wp-fusion' ); ?></p>

				<?php $this->render_space_settings(); ?>

				<h3><?php esc_html_e( 'Course Settings', 'wp-fusion' ); ?></h3>
				<p class="description"><?php esc_html_e( 'These settings will be applied when users enroll in or leave courses.', 'wp-fusion' ); ?></p>

				<?php $this->render_course_settings(); ?>

				<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes', 'wp-fusion' ); ?>"/></p>

			</form>
		</div>
		<?php
	}



	/**
	 * Gets all spaces from FluentCommunity
	 *
	 * @since  3.44.20
	 *
	 * @return array Spaces
	 */
	private function get_spaces() {
		$spaces = \FluentCommunity\App\Functions\Utility::getSpaces();

		// Convert to id => title format
		$formatted = array();
		foreach ( $spaces as $space ) {
			$formatted[ $space->id ] = $space->title;
		}

		return $formatted;
	}

	/**
	 * Renders space settings table
	 *
	 * @since 3.44.20
	 */
	private function render_space_settings() {

		$spaces = $this->get_spaces();

		if ( empty( $spaces ) ) {
			echo '<p>' . esc_html__( 'No spaces found. Create some spaces first.', 'wp-fusion' ) . '</p>';
			return;
		}

		?>
		<table class="wpf-settings-table table table-hover">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Space', 'wp-fusion' ); ?></th>
					<th>
						<?php esc_html_e( 'Apply Tags - Join', 'wp-fusion' ); ?>
						<span class="dashicons dashicons-editor-help wpf-tip wpf-tip-right" data-tip="<?php printf( esc_attr__( 'These tags will be applied in %s when someone joins this space.', 'wp-fusion' ), wp_fusion()->crm->name ); ?>"></span>
					</th>
					<th>
						<?php esc_html_e( 'Remove Tags', 'wp-fusion' ); ?>
						<span class="dashicons dashicons-editor-help wpf-tip wpf-tip-right" data-tip="<?php esc_attr_e( 'Remove the tags when the user leaves the space.', 'wp-fusion' ); ?>"></span>
					</th>
					<th>
						<?php esc_html_e( 'Link with Tag', 'wp-fusion' ); ?>
						<span class="dashicons dashicons-editor-help wpf-tip wpf-tip-right" data-tip="<?php printf( esc_attr__( 'This tag will be applied in %1$s when a user joins, and will be removed when a user leaves. Likewise, if this tag is applied to a user from within %2$s, they will be automatically added to this space. If this tag is removed, the user will be removed from the space.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ); ?>"></span>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $spaces as $id => $title ) : ?>
					<tr>
						<td><?php echo esc_html( $title ); ?></td>
						<td>
							<?php
							$args = array(
								'setting'   => $this->get_setting( 'fc_space_' . $id . '_tags', array() ),
								'meta_name' => 'wpf-settings',
								'field_id'  => 'fc_space_' . $id . '_tags',
							);

							wpf_render_tag_multiselect( $args );
							?>
						</td>
						<td>
							<input type="checkbox" name="wpf-settings[fc_space_<?php echo esc_attr( $id ); ?>_remove]" value="1" <?php checked( $this->get_setting( 'fc_space_' . $id . '_remove' ) ); ?> />
						</td>
						<td>
							<?php
							$args = array(
								'setting'   => $this->get_setting( 'fc_space_' . $id . '_tag_link', array() ),
								'meta_name' => 'wpf-settings',
								'field_id'  => 'fc_space_' . $id . '_tag_link',
								'limit'     => 1,
							);

							wpf_render_tag_multiselect( $args );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Gets all courses from FluentCommunity
	 *
	 * @since  3.44.20
	 *
	 * @return array Courses
	 */
	private function get_courses() {
		$courses = \FluentCommunity\App\Functions\Utility::getCourses();

		// Convert to id => title format
		$formatted = array();
		foreach ( $courses as $course ) {
			$formatted[ $course->id ] = $course->title;
		}

		return $formatted;
	}

	/**
	 * Renders course settings table
	 *
	 * @since 3.44.20
	 */
	private function render_course_settings() {

		$courses = $this->get_courses();

		if ( empty( $courses ) ) {
			echo '<p>' . esc_html__( 'No courses found. Create some courses first.', 'wp-fusion' ) . '</p>';
			return;
		}

		?>
		<table class="wpf-settings-table table table-hover">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Course', 'wp-fusion' ); ?></th>
					<th>
						<?php esc_html_e( 'Apply Tags - Enrolled', 'wp-fusion' ); ?>
						<span class="dashicons dashicons-editor-help wpf-tip wpf-tip-right" data-tip="<?php printf( esc_attr__( 'These tags will be applied in %s when someone enrolls in this course.', 'wp-fusion' ), wp_fusion()->crm->name ); ?>"></span>
					</th>
					<th>
						<?php esc_html_e( 'Remove Tags', 'wp-fusion' ); ?>
						<span class="dashicons dashicons-editor-help wpf-tip wpf-tip-right" data-tip="<?php esc_attr_e( 'Remove the tags when the user leaves the course.', 'wp-fusion' ); ?>"></span>
					</th>
					<th>
						<?php esc_html_e( 'Link with Tag', 'wp-fusion' ); ?>
						<span class="dashicons dashicons-editor-help wpf-tip wpf-tip-right" data-tip="<?php printf( esc_attr__( 'This tag will be applied in %1$s when a user enrolls, and will be removed when a user leaves. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this course. If this tag is removed, the user will be removed from the course.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ); ?>"></span>
					</th>
					<th>
						<?php esc_html_e( 'Apply Tags - Complete', 'wp-fusion' ); ?>
						<span class="dashicons dashicons-editor-help wpf-tip wpf-tip-right" data-tip="<?php printf( esc_attr__( 'These tags will be applied in %s when someone completes this course.', 'wp-fusion' ), wp_fusion()->crm->name ); ?>"></span>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $courses as $id => $title ) : ?>
					<tr>
						<td><?php echo esc_html( $title ); ?></td>
						<td>
							<?php
							$args = array(
								'setting'   => $this->get_setting( 'fc_course_' . $id . '_tags', array() ),
								'meta_name' => 'wpf-settings',
								'field_id'  => 'fc_course_' . $id . '_tags',
							);

							wpf_render_tag_multiselect( $args );
							?>
						</td>
						<td>
							<input type="checkbox" name="wpf-settings[fc_course_<?php echo esc_attr( $id ); ?>_remove]" value="1" <?php checked( $this->get_setting( 'fc_course_' . $id . '_remove' ) ); ?> />
						</td>
						<td>
							<?php
							$args = array(
								'setting'   => $this->get_setting( 'fc_course_' . $id . '_tag_link', array() ),
								'meta_name' => 'wpf-settings',
								'field_id'  => 'fc_course_' . $id . '_tag_link',
								'limit'     => 1,
							);

							wpf_render_tag_multiselect( $args );
							?>
						</td>
						<td>
							<?php
							$args = array(
								'setting'   => $this->get_setting( 'fc_course_' . $id . '_complete_tags', array() ),
								'meta_name' => 'wpf-settings',
								'field_id'  => 'fc_course_' . $id . '_complete_tags',
							);

							wpf_render_tag_multiselect( $args );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}


	/**
	 * Add meta field group
	 *
	 * @since  3.44.20
	 *
	 * @param  array $field_groups The field groups.
	 * @return array The field groups.
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['fluent_community'] = array(
			'title' => __( 'FluentCommunity', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/membership/fluentcommunity/',
		);

		return $field_groups;
	}

	/**
	 * Prepare meta fields
	 *
	 * @since  3.44.20
	 *
	 * @param  array $meta_fields The meta fields.
	 * @return array The meta fields.
	 */
	public function add_meta_fields( $meta_fields ) {

		$meta_fields['fc_last_space_joined'] = array(
			'label'  => 'Last Space Joined',
			'type'   => 'text',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_space_joined_date'] = array(
			'label'  => 'Last Space Joined Date',
			'type'   => 'date',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_course_enrolled'] = array(
			'label'  => 'Last Course Enrolled',
			'type'   => 'text',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_course_enrolled_date'] = array(
			'label'  => 'Last Course Enrolled Date',
			'type'   => 'date',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_course_completed'] = array(
			'label'  => 'Last Course Completed',
			'type'   => 'text',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_course_completed_date'] = array(
			'label'  => 'Last Course Completed Date',
			'type'   => 'date',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_lesson_completed'] = array(
			'label'  => 'Last Lesson Completed',
			'type'   => 'text',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		$meta_fields['fc_last_lesson_completed_date'] = array(
			'label'  => 'Last Lesson Completed Date',
			'type'   => 'date',
			'group'  => 'fluent_community',
			'pseudo' => true,
		);

		return $meta_fields;
	}
}

new WPF_FluentCommunity();
