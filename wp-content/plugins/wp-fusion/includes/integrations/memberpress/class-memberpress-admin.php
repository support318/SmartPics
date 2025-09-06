<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles admin UI and settings functionality.
 *
 * @since 3.45.0
 */
class WPF_MemberPress_Admin {

	/**
	 * Get things started.
	 *
	 * @since 3.45.0
	 */
	public function __construct() {

		// Meta fields
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_fields' ) );

		// MemberPress admin tools
		add_action( 'mepr-product-options-tabs', array( $this, 'output_product_nav_tab' ) );
		add_action( 'mepr-product-options-pages', array( $this, 'output_product_content_tab' ) );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

		// Coupons
		add_action( 'add_meta_boxes', array( $this, 'add_coupon_meta_box' ), 20, 2 );

		// Courses
		if ( class_exists( 'memberpress\courses\models\course' ) ) {
			add_action( 'add_meta_boxes', array( $this, 'add_courses_meta_box' ) );
		}

		// Admin UI
		add_filter( 'mepr-admin-transactions-cols', array( $this, 'admin_columns' ) );
		add_action( 'mepr-admin-transactions-cell', array( $this, 'admin_columns_content' ), 10, 3 );
		add_action( 'mepr_edit_transaction_table_after', array( $this, 'transaction_table_after' ) );
		add_action( 'mepr_table_controls_search', array( $this, 'transactions_debug' ), 5 );
	}

	/**
	 * Gets available CRM fields for mapping.
	 *
	 * @since  3.45.0
	 *
	 * @return array The CRM fields.
	 */
	private function get_membership_crm_fields() {
		return array(
			'membership_level'   => array(
				'name' => __( 'Membership Level Name', 'wp-fusion' ),
				'type' => 'text',
			),
			'membership_status'  => array(
				'name' => __( 'Membership Status', 'wp-fusion' ),
				'type' => 'text',
			),
			'reg_date'           => array(
				'name' => __( 'Registration Date', 'wp-fusion' ),
				'type' => 'date',
			),
			'sub_status'         => array(
				'name' => __( 'Subscription Status', 'wp-fusion' ),
				'type' => 'text',
			),
			'transaction_status' => array(
				'name' => __( 'Transaction Status', 'wp-fusion' ),
				'type' => 'text',
			),
			'transaction_number' => array(
				'name' => __( 'Transaction Number', 'wp-fusion' ),
				'type' => 'text',
			),
			'expiration'         => array(
				'name' => __( 'Expiration Date', 'wp-fusion' ),
				'type' => 'date',
			),
			'trial_duration'     => array(
				'name' => __( 'Trial Duration (days)', 'wp-fusion' ),
				'type' => 'int',
			),
			'payment_method'     => array(
				'name' => __( 'Payment Method', 'wp-fusion' ),
				'type' => 'text',
			),
			'sub_total'          => array(
				'name' => __( 'Subscription Total', 'wp-fusion' ),
				'type' => 'text',
			),
			'transaction_total'  => array(
				'name' => __( 'Transaction Total', 'wp-fusion' ),
				'type' => 'text',
			),
			'coupon'             => array(
				'name' => __( 'Coupon Used', 'wp-fusion' ),
				'type' => 'text',
			),
			'total_spent'        => array(
				'name' => __( 'Total Spent', 'wp-fusion' ),
				'type' => 'text',
			),
		);
	}


	/**
	 * Gets available tag settings.
	 *
	 * @since  3.45.0
	 *
	 * @return array The tag settings.
	 */
	private function get_membership_tag_settings() {
		return array(
			'apply_tags_registration'        => array(
				'label'       => __( 'Apply Tags - Active', 'wp-fusion' ),
				'description' => sprintf( __( 'These tags will be applied to the customer in %s upon registering for this membership, as well as when a subscription to this membership changes status to Active, and when a renewal transaction is received (if the member doesn\'t already have the tags).', 'wp-fusion' ), wp_fusion()->crm->name ),
				'no_dupes'    => array( 'tag_link' ),
				'default'     => array(),
			),
			'remove_tags'                    => array(
				'type'        => 'checkbox',
				'label'       => __( 'Remove Tags', 'wp-fusion' ),
				'description' => __( 'Remove original tags (above) when the membership expires or is changed to a different level.', 'wp-fusion' ),
				'default'     => false,
			),
			'tag_link'                       => array(
				'label'       => __( 'Link with Tag', 'wp-fusion' ),
				'description' => sprintf( __( 'This tag will be applied in %1$s when a member is registered. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this membership. If the tag is removed they will be removed from the membership.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ),
				'placeholder' => __( 'Select Tag', 'wp-fusion' ),
				'limit'       => 1,
				'no_dupes'    => array( 'apply_tags_registration', 'apply_tags_cancelled' ),
				'default'     => array(),
			),
			'apply_tags_cancelled'           => array(
				'label'       => __( 'Subscription Cancelled', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a subscription is cancelled. Happens when an admin or user cancels a subscription, or if the payment gateway has canceled the subscription due to too many failed payments (will be removed if the membership is resumed).', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_suspended'           => array(
				'label'       => __( 'Subscription Paused', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a subscription is paused.', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_resumed'             => array(
				'label'       => __( 'Subscription Resumed', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a paused subscription is resumed. The Subscription Paused tags will be removed automatically.', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_upgraded'            => array(
				'label'       => __( 'Subscription Upgraded', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a subscription at another level is upgraded to a subscription at this membership level.', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_downgraded'          => array(
				'label'       => __( 'Subscription Downgraded', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a subscription at another level is downgraded to a subscription at this membership level.', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_payment_failed'      => array(
				'label'       => __( 'Subscription Payment Failed', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a recurring payment fails (will be removed if a payment is made).', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_trial'               => array(
				'label'       => __( 'Trial', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a subscription is created in a trial status.', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_converted'           => array(
				'label'       => __( 'Subscription Converted', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a trial converts to a normal subscription.', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_refunded'            => array(
				'label'       => __( 'Transaction Refunded', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a transaction is refunded.', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_expired'             => array(
				'label'       => __( 'Transaction Expired', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a transaction expires (will be removed if the membership is resumed).', 'wp-fusion' ),
				'default'     => array(),
			),
			'apply_tags_pending'             => array(
				'label'       => __( 'Pending', 'wp-fusion' ),
				'description' => __( 'Apply these tags when a subscription or transaction is pending.', 'wp-fusion' ),
				'no_dupes'    => array( 'tag_link' ),
				'default'     => array(),
			),
			'apply_tags_corporate_accounts'  => array(
				'label'       => __( 'Corporate Accounts', 'wp-fusion' ),
				'description' => __( 'Apply these tags to members added as sub-accounts to this account.', 'wp-fusion' ),
				'show_if'     => 'MPCA_PLUGIN_NAME',
				'default'     => array(),
			),
			'remove_tags_corporate_accounts' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Remove Corporate Account Tags', 'wp-fusion' ),
				'description' => __( 'Remove tags applied to sub accounts (above) when the parent corporate membership is cancelled.', 'wp-fusion' ),
				'show_if'     => 'MPCA_PLUGIN_NAME',
				'default'     => false,
			),
		);
	}

	/**
	 * Adds MemberPress field group to meta fields list.
	 *
	 * @since 2.9.1
	 *
	 * @param array $field_groups Field groups.
	 * @return array Field groups.
	 */
	public function add_meta_field_group( $field_groups ) {
		$field_groups['memberpress'] = array(
			'title' => __( 'MemberPress', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/membership/memberpress/',
		);
		return $field_groups;
	}

	/**
	 * Sets field labels and types for Custom MemberPress fields.
	 *
	 * @since 2.9.1
	 *
	 * @param array $meta_fields Meta fields.
	 * @return array Meta fields.
	 */
	public function add_meta_fields( $meta_fields ) {

		$mepr_options = MeprOptions::fetch();
		$mepr_fields  = array_merge( $mepr_options->custom_fields, $mepr_options->address_fields );

		foreach ( $mepr_fields as $field_object ) {

			if ( 'radios' == $field_object->field_type ) {
				$field_object->field_type = 'radio';
			}

			if ( 'checkboxes' == $field_object->field_type ) {
				$field_object->field_type = 'multiselect';
			}

			$meta_fields[ $field_object->field_key ] = array(
				'label' => $field_object->field_name,
				'type'  => $field_object->field_type,
				'group' => 'memberpress',
			);

			if ( $field_object->field_key == 'mepr-address-country' ) {
				$meta_fields[ $field_object->field_key ]['type'] = 'country';
			}

			if ( $field_object->field_key == 'mepr-address-state' ) {
				$meta_fields[ $field_object->field_key ]['type'] = 'state';
			}
		}

		// Global fields
		$crm_fields = $this->get_membership_crm_fields();

		foreach ( $crm_fields as $key => $value ) {
			$meta_fields[ 'mepr_' . $key ] = array(
				'label'  => $value['name'],
				'type'   => $value['type'],
				'group'  => 'memberpress',
				'pseudo' => true,
			);
		}

		// Fill in product-specific fields
		$contact_fields = wp_fusion()->settings->get( 'contact_fields', array() );

		foreach ( $contact_fields as $key => $value ) {

			foreach ( $crm_fields as $crm_key => $crm_value ) {

				if ( 0 === strpos( $key, 'mepr_' . $crm_key . '_' ) ) {

					$post_id = str_replace( 'mepr_' . $crm_key . '_', '', $key );

					$meta_fields[ $key ] = array(
						'label'  => get_the_title( $post_id ) . ' - ' . $crm_value['name'],
						'type'   => $crm_value['type'],
						'group'  => 'memberpress',
						'pseudo' => true,
					);
				}
			}
		}

		if ( defined( 'MPCA_PLUGIN_NAME' ) ) {

			$meta_fields['mepr_corporate_parent_email'] = array(
				'label'  => 'Corporate Account Parent Email',
				'type'   => 'email',
				'group'  => 'memberpress',
				'pseudo' => true,
			);
		}

		return $meta_fields;
	}

	/**
	 * Outputs <li> nav item for membership level configuration.
	 *
	 * @since 2.9.1
	 * @param MeprProduct $product The product.
	 */
	public function output_product_nav_tab( $product ) {
		echo '<a class="nav-tab main-nav-tab" href="#" id="wp-fusion-tags">';
		esc_html_e( 'WP Fusion Tags', 'wp-fusion' );
		echo '</a>';

		echo '<a class="nav-tab main-nav-tab" href="#" id="wp-fusion-fields">';
		esc_html_e( 'WP Fusion Fields', 'wp-fusion' );
		echo '</a>';
	}

	/**
	 * Output Product Content Tab
	 * Outputs tabbed content area for WPF membership settings.
	 *
	 * @since 2.9.1
	 * @since 3.43.1 Added option for removing tags from corporate sub-accounts.
	 *
	 * @param MeprProduct $product The product.
	 */
	public function output_product_content_tab( $product ) {

		echo '<div class="product_options_page wp-fusion wp-fusion-tags">';

		echo '<div class="product-options-panel">';

		echo '<p>';

		printf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/membership/memberpress/" target="_blank">', '</a>' );

		echo '</p>';

		wp_nonce_field( 'wpf_meta_box_memberpress', 'wpf_meta_box_memberpress_nonce' );

		$tag_settings = $this->get_membership_tag_settings();

		// Get saved settings and merge with defaults
		$defaults = wp_list_pluck( $tag_settings, 'default' );

		$settings = wp_parse_args( get_post_meta( $product->ID, 'wpf-settings-memberpress', true ), $defaults );

		// Output tag settings
		foreach ( $tag_settings as $id => $setting ) {

			// Skip if this is a conditional setting
			if ( ! empty( $setting['show_if'] ) && ! defined( $setting['show_if'] ) ) {
				continue;
			}

			if ( isset( $setting['type'] ) && 'checkbox' === $setting['type'] ) {

				echo '<input class="checkbox" type="checkbox" id="wpf-' . esc_attr( $id ) . '-memberpress" name="wpf-settings-memberpress[' . esc_attr( $id ) . ']" value="1" ' . checked( $settings[ $id ], 1, false ) . ' />';
				echo '<label for="wpf-' . esc_attr( $id ) . '-memberpress">' . esc_html( $setting['description'] ) . '</label><br /><br />';

				continue;
			}

			echo '<p class="form-field">';

			echo '<label for="wpf-settings-memberpress-' . esc_attr( $id ) . '"><strong>' . esc_html( $setting['label'] ) . ':</strong></label>';

			$args = array(
				'setting'   => $settings[ $id ],
				'meta_name' => 'wpf-settings-memberpress',
				'field_id'  => $id,
			);

			if ( ! empty( $setting['no_dupes'] ) ) {
				$args['no_dupes'] = $setting['no_dupes'];
			}

			if ( ! empty( $setting['placeholder'] ) ) {
				$args['placeholder'] = $setting['placeholder'];
			}

			if ( ! empty( $setting['limit'] ) ) {
				$args['limit'] = $setting['limit'];
			}

			wpf_render_tag_multiselect( $args );

			if ( ! empty( $setting['description'] ) ) {
				echo '<br /><span class="description"><small>';
				echo esc_html( $setting['description'] );
				echo '</small></span>';
			}

			echo '</p>';
		}

		do_action( 'wpf_memberpress_meta_box', $settings, $product );

		echo '</div>';

		echo '</div>';

		// CRM fields section
		echo '<div class="product_options_page wp-fusion wp-fusion-fields">';

		echo '<div class="product-options-panel">';

		echo '<p class="form-field"><label><strong>' . esc_html__( 'Membership Fields', 'wp-fusion' ) . '</strong></label></p>';

		$crm_fields = $this->get_membership_crm_fields();

		$fields = wpf_get_option( 'contact_fields' );

		foreach ( $crm_fields as $key => $value ) {

			$id = 'mepr_' . $key . '_' . $product->ID;

			echo '<p class="form-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $value['name'] ) . '</label>';

			wpf_render_crm_field_select(
				isset( $fields[ $id ] ) ? $fields[ $id ]['crm_field'] : false,
				'wpf_settings_memberpress_crm_fields',
				$id
			);
			echo '</p>';
		}

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Saves data captured in the new interfaces to a post meta field for the membership
	 *
	 * @since 3.45.0
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_memberpress_nonce'] ) || ! wp_verify_nonce( $_POST['wpf_meta_box_memberpress_nonce'], 'wpf_meta_box_memberpress' ) || $_POST['post_type'] == 'revision' ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( $_POST['post_type'] == 'memberpressproduct' ) {

			// Memberships
			if ( isset( $_POST['wpf-settings-memberpress'] ) ) {
				$data = $_POST['wpf-settings-memberpress'];
			} else {
				$data = array();
			}

			// Update the meta field in the database.
			update_post_meta( $post_id, 'wpf-settings-memberpress', $data );

			// Save any CRM fields to the field mapping.
			if ( isset( $_POST['wpf_settings_memberpress_crm_fields'] ) ) {

				$contact_fields = wp_fusion()->settings->get( 'contact_fields', array() );

				$memberpress_fields = $this->get_membership_crm_fields();

				foreach ( $_POST['wpf_settings_memberpress_crm_fields'] as $key => $value ) {

					if ( ! empty( $value['crm_field'] ) ) {

						$contact_fields[ $key ]['crm_field'] = $value['crm_field'];
						$contact_fields[ $key ]['type']      = $memberpress_fields[ $key ]['type'];
						$contact_fields[ $key ]['active']    = true;

					} elseif ( isset( $contact_fields[ $key ] ) ) {

						// If the setting has been removed we can un-list it from the main Contact Fields list.
						unset( $contact_fields[ $key ] );
					}
				}

				wp_fusion()->settings->set( 'contact_fields', $contact_fields );
			}
		} elseif ( $_POST['post_type'] == 'memberpresscoupon' ) {

			// Coupons
			if ( isset( $_POST['wpf-settings'] ) ) {
				$data = $_POST['wpf-settings'];
			} else {
				$data = array();
			}

			// Update the meta field in the database.
			update_post_meta( $post_id, 'wpf-settings', $data );

		}
	}

	/**
	 * Adds meta box for coupon settings.
	 *
	 * @since 3.18.12
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data The data.
	 */
	public function add_coupon_meta_box( $post_id, $data ) {
		add_meta_box( 'wpf-memberpress-meta', 'WP Fusion - Coupon Settings', array( $this, 'coupon_meta_box_callback' ), 'memberpresscoupon' );
	}

	/**
	 * Displays meta box content.
	 *
	 * @since 3.18.12
	 *
	 * @param object $post The post object.
	 * @return mixed
	 */
	public function coupon_meta_box_callback( $post ) {

		$settings = array(
			'apply_tags_coupon' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings', true ) );
		}

		wp_nonce_field( 'wpf_meta_box_memberpress', 'wpf_meta_box_memberpress_nonce' );

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="tag_link">Apply tags:</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_coupon'],
			'meta_name' => 'wpf-settings',
			'field_id'  => 'apply_tags_coupon',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">These tags will be applied when this coupon is used.</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';
	}

	/**
	 * Adds course/lesson/quiz meta boxes.
	 *
	 * @since 3.41.15
	 * @return mixed
	 */
	public function add_courses_meta_box() {
		add_meta_box( 'wpf-memberpress-course-meta', 'WP Fusion - Course Settings', array( $this, 'course_meta_box_callback' ), 'mpcs-course' );
		add_meta_box( 'wpf-memberpress-lesson-meta', 'WP Fusion - Lesson Settings', array( $this, 'lesson_meta_box_callback' ), 'mpcs-lesson' );
		add_meta_box( 'wpf-memberpress-quiz-meta', 'WP Fusion - Quiz Settings', array( $this, 'quiz_meta_box_callback' ), 'mpcs-quiz' );
	}


	/**
	 * Add course meta box fields.
	 *
	 * @since 3.41.15
	 * @param object $post The course post object.
	 * @return mixed
	 */
	public function course_meta_box_callback( $post ) {
		$settings = array(
			'apply_tags_course_complete' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings', true ) );
		}

		wp_nonce_field( 'wpf_meta_box_memberpress', 'wpf_meta_box_memberpress_nonce' );

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_course_complete">Apply tags - Course Complete:</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_course_complete'],
			'meta_name' => 'wpf-settings',
			'field_id'  => 'apply_tags_course_complete',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">These tags will be applied when this course is completed.</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';
	}

	/**
	 * Add lesson meta box fields.
	 *
	 * @since 3.41.15
	 * @param object $post The lesson post object.
	 * @return mixed
	 */
	public function lesson_meta_box_callback( $post ) {
		$settings = array(
			'apply_tags_lesson_complete' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings', true ) );
		}

		wp_nonce_field( 'wpf_meta_box_memberpress', 'wpf_meta_box_memberpress_nonce' );

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_lesson_complete">Apply tags - Lesson Complete:</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_lesson_complete'],
			'meta_name' => 'wpf-settings',
			'field_id'  => 'apply_tags_lesson_complete',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">These tags will be applied when this lesson is completed.</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';
	}

	/**
	 * Add quiz meta box fields.
	 *
	 * @since 3.41.15
	 * @param object $post The quiz post object.
	 * @return mixed
	 */
	public function quiz_meta_box_callback( $post ) {
		$settings = array(
			'apply_tags_quiz_pass' => array(),
			'apply_tags_quiz_fail' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings', true ) );
		}

		wp_nonce_field( 'wpf_meta_box_memberpress', 'wpf_meta_box_memberpress_nonce' );

		echo '<table class="form-table"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_quiz_pass">Apply tags - Quiz Pass:</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_quiz_pass'],
			'meta_name' => 'wpf-settings',
			'field_id'  => 'apply_tags_quiz_pass',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">These tags will be applied when this quiz is passed.</span>';
		echo '</td>';

		echo '</tr>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_quiz_fail">Apply tags - Quiz Fail:</label></th>';
		echo '<td>';

		$args = array(
			'setting'   => $settings['apply_tags_quiz_fail'],
			'meta_name' => 'wpf-settings',
			'field_id'  => 'apply_tags_quiz_fail',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">These tags will be applied when this quiz is failed.</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';
	}


	/**
	 * Adds status column to transactions list.
	 *
	 * @since 3.41.23
	 *
	 * @param array $columns The columns.
	 * @return array The columns.
	 */
	public function admin_columns( $columns ) {

		$new_column = '<span class="wpf-tip wpf-tip-bottom wpf-woo-column-title" data-tip="' . esc_attr__( 'WP Fusion Status', 'wp-fusion' ) . '"><span>' . __( 'WP Fusion Status', 'wp-fusion' ) . '</span>' . wpf_logo_svg( 14 ) . '</span>';

		return wp_fusion()->settings->insert_setting_after( 'col_status', $columns, array( 'wp_fusion' => $new_column ) );
	}

	/**
	 * Adds content to the status column.
	 *
	 * @since 3.41.23
	 *
	 * @param string $column_name The column name.
	 * @param object $rec         The record.
	 * @param string $attributes  The attributes.
	 */
	public function admin_columns_content( $column_name, $rec, $attributes ) {

		if ( 'wp_fusion' === $column_name ) {

			echo '<td ' . $attributes . '>';

			$txn = new MeprTransaction( $rec->id );

			$complete = $txn->get_meta( 'wpf_complete', true );

			if ( $complete || $txn->get_meta( 'wpf_ec_complete', true ) ) {

				$class = 'success';

				// Get the contact edit URL.

				$contact_id = wpf_get_contact_id( $txn->user_id );

				if ( $contact_id ) {

					$url = wp_fusion()->crm->get_contact_edit_url( $contact_id );

					if ( $url ) {
						$id_text = '<a href="' . esc_url_raw( $url ) . '" target="_blank">#' . esc_html( $contact_id ) . '</a>';
					} else {
						$id_text = '#' . esc_html( $contact_id );
					}
				} else {
					$class   = 'partial-success';
					$id_text = '<em>' . __( 'unknown', 'wp-fusion' ) . '</em>';
				}

				if ( $complete ) {
					$show_date = date_i18n( get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ), strtotime( $complete ) );
				} else {
					$show_date = '<em>' . __( 'unknown', 'wp-fusion' ) . '</em>';
				}
				$tooltip = sprintf(
					__( 'This transaction was synced to %1$s contact ID %2$s on %3$s.', 'wp-fusion' ),
					esc_html( wp_fusion()->crm->name ),
					$id_text,
					esc_html( $show_date )
				);

				if ( function_exists( 'wp_fusion_ecommerce' ) ) {

					// Enhanced ecommerce.

					if ( $txn->get_meta( 'wpf_ec_complete', true ) ) {

						$invoice_id = $txn->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true );

						if ( $invoice_id ) {
							$tooltip .= '<br /><br />' . sprintf( __( 'It was processed by Enhanced Ecommerce with invoice ID #%s.', 'wp-fusion' ), $invoice_id );
						} else {
							$tooltip .= '<br /><br />' . __( 'It was processed by Enhanced Ecommerce.', 'wp-fusion' );
						}
					} else {

						$class    = 'partial-success';
						$tooltip .= '<br /><br />' . __( 'It was not processed by Enhanced Ecommerce.', 'wp-fusion' );

					}
				}
			} else {
				$class   = 'fail';
				$tooltip = sprintf( __( 'This transaction was not synced to %s.', 'wp-fusion' ), wp_fusion()->crm->name );
			}

			echo '<i class="icon-wp-fusion wpf-tip wpf-tip-bottom ' . esc_attr( $class ) . '" data-tip="' . esc_attr( $tooltip ) . '"></i>';

			echo '</td>';

		}
	}

	/**
	 * Adds WP Fusion info to a single transaction edit page.
	 *
	 * @since 3.41.23
	 *
	 * @param Mepr_Transaction $txn The transaction
	 * @return mixed HTML output.
	 */
	public function transaction_table_after( $txn ) {

		if ( isset( $_GET['order_action'] ) && 'wpf_process' === $_GET['order_action'] ) {

			$txn->delete_meta( 'wpf_complete' ); // unlock it.

			wp_fusion()->integrations->memberpress->transactions->sync_transaction_fields( $txn->id );

			wp_fusion()->integrations->memberpress->transactions->transaction_complete( $txn );

			if ( function_exists( 'wp_fusion_ecommerce' ) && ! empty( wp_fusion_ecommerce()->integrations ) ) {
				wp_fusion_ecommerce()->integrations->memberpress->transaction_created( $txn );
			}

			// Redirect so the query var is removed.
			wp_safe_redirect( admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ) );
			exit;

		}

		?>

		<tr id="wp-fusion-user-profile-settings"><th><?php echo wpf_logo_svg(); ?><h2 style="margin: 0;display: inline-block;vertical-align: super;margin-left: 10px;"><?php esc_html_e( 'WP Fusion', 'wp-fusion' ); ?></h2></th></tr>

		<tr class="wp-fusion-status-row">
			<th scope="row"><label><?php printf( __( 'Synced to %s:', 'wp-fusion' ), wp_fusion()->crm->name ); ?></label></th>
			<td>
				<?php if ( $txn->get_meta( 'wpf_complete', true ) ) : ?>
					<span><?php echo date_i18n( get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ), strtotime( $txn->get_meta( 'wpf_complete', true ) ) ); ?></span>
					<span class="dashicons dashicons-yes-alt"></span>
				<?php elseif ( $txn->get_meta( 'wpf_ec_complete', true ) ) : ?>
					<?php // from before we stored wpf_complete on the transaction (3.41.23). ?>
					<span><?php _e( 'Yes', 'wp-fusion' ); ?></span>
					<span class="dashicons dashicons-yes-alt"></span>
				<?php else : ?>
					<span><?php _e( 'No', 'wp-fusion' ); ?></span>
					<span class="dashicons dashicons-no"></span>
				<?php endif; ?>

				<?php if ( 'complete' !== $txn->status ) : ?>

					- <?php esc_html_e( 'Transaction is not Complete', 'wp-fusion' ); ?>

				<?php endif; ?>

			</td>
		</tr>

		<?php $contact_id = wpf_get_contact_id( $txn->user_id ); ?>

		<?php if ( $contact_id ) : ?>

			<tr class="wp-fusion-status-row">
				<th scope="row"><label><?php _e( 'Contact ID:', 'wp-fusion' ); ?></label></th>
				<td>
					<?php echo esc_html( $contact_id ); ?>
					<?php $url = wp_fusion()->crm->get_contact_edit_url( $contact_id ); ?>
					<?php if ( false !== $url ) : ?>
						- <a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php printf( esc_html__( 'View in %s', 'wp-fusion' ), esc_html( wp_fusion()->crm->name ) ); ?> &rarr;</a>
					<?php endif; ?>
				</td>
			</tr>

		<?php endif; ?>

		<?php
		/*
		if ( wpf_get_option( 'email_optin' ) ) : ?>

			<tr class="wp-fusion-status-row">
				<th scope="row"><label><?php _e( 'Opted In:', 'wp-fusion' ); ?></label></th>

				<td>
					<?php if ( $txn->get_meta( 'email_optin', true ) ) : ?>
						<span><?php _e( 'Yes', 'wp-fusion' ); ?></span>
						<span class="dashicons dashicons-yes-alt"></span>
					<?php else : ?>
						<span><?php _e( 'No', 'wp-fusion' ); ?></span>
						<span class="dashicons dashicons-no"></span>
					<?php endif; ?>
				</td>
			</tr>

		<?php endif; */
		?>

		<?php if ( class_exists( 'WP_Fusion_Ecommerce' ) ) : ?>

			<tr class="wp-fusion-status-row">
				<th scope="row"><label><?php printf( __( 'Enhanced Ecommerce:', 'wp-fusion' ), wp_fusion()->crm->name ); ?></label></th>
				<td>
					<?php if ( $txn->get_meta( 'wpf_ec_complete', true ) ) : ?>
						<span><?php _e( 'Yes', 'wp-fusion' ); ?></span>
						<span class="dashicons dashicons-yes-alt"></span>
					<?php else : ?>
						<span><?php _e( 'No', 'wp-fusion' ); ?></span>
						<span class="dashicons dashicons-no"></span>
					<?php endif; ?>
				</td>
			</tr>

			<?php $invoice_id = $txn->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true ); ?>

			<?php if ( $invoice_id ) : ?>

				<tr class="wp-fusion-status-row">
					<th scope="row"><label><?php _e( 'Invoice ID:', 'wp-fusion' ); ?></label></th>
					<td>
						<span><?php echo esc_html( $invoice_id ); ?></span>
					</td>
			</tr>

			<?php endif; ?>

		<?php endif; ?>

		<?php if ( 'complete' === $txn->status ) : ?>

			<tr class="wp-fusion-status-row">

				<th scope="row"><label><?php _e( 'Actions:', 'wp-fusion' ); ?></label></th>
				<td>
					<a
					href="<?php echo esc_url( add_query_arg( array( 'order_action' => 'wpf_process' ) ) ); ?>"
					class="wpf-action-button button-secondary wpf-tip wpf-tip-bottom"
					data-tip="<?php printf( esc_html__( 'The transaction will be processed again as if the customer had just checked out. Any enabled fields will be synced to %s, and any configured tags will be applied.', 'wp-fusion' ), wp_fusion()->crm->name ); ?>">
						<?php _e( 'Process WP Fusion actions again ', 'wp-fusion' ); ?>
					</a>
					<br />
					<br />
				</td>

			</tr>

		<?php endif; ?>

		<?php
	}

	/**
	 * Adds WP Fusion info to a single transaction edit page.
	 *
	 * @since 3.41.23
	 *
	 * @param Mepr_Transaction $txn The transaction
	 * @return mixed HTML output.
	 */
	public function transactions_debug() {

		if ( isset( $_REQUEST['page'] ) && 'memberpress-trans' === $_REQUEST['page'] && isset( $_REQUEST['wpf_debug'] ) ) {

			$transactions_db = MeprTransaction::get_all();

			$transactions_by_status = array();

			foreach ( $transactions_db as $txn ) {

				$txn = new MeprTransaction( $txn->id );

				if ( ! isset( $transactions_by_status[ $txn->status ] ) ) {
					$transactions_by_status[ $txn->status ] = array();
				}

				$transactions_by_status[ $txn->status ][ $txn->id ] = array(
					'user_id'         => $txn->user_id,
					'wpf_complete'    => $txn->get_meta( 'wpf_complete', true ),
					'wpf_ec_complete' => $txn->get_meta( 'wpf_ec_complete', true ),
					'wpf_invoice_id'  => $txn->get_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true ),
				);

			}

			echo '<ul>';

			foreach ( $transactions_by_status as $status => $transactions ) {

				echo '<li><strong>Status ' . $status . '</strong> - ' . count( $transactions ) . '</li>';
				// echo '</li><strong>' . $status . ' + wpf_complete</strong> - ' . count( array_filter( wp_list_pluck( $transactions, 'wpf_complete' ) ) ) . '</li>';
				echo '<li><strong>' . $status . ' + wpf_ec_complete</strong> - ' . count( array_filter( wp_list_pluck( $transactions, 'wpf_ec_complete' ) ) ) . '</li>';
				echo '<li><strong>' . $status . ' + ' . wp_fusion()->crm->name . ' invoice</strong> - ' . count( array_filter( wp_list_pluck( $transactions, 'wpf_invoice_id' ) ) ) . '</li>';
				echo '<li>';

					echo '<pre>';
					print_r( $transactions );
					echo '</pre>';

				echo '</li>';

			}
		}
	}
}
