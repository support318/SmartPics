<?php


namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Buddypress_Helpers;

/**
 * Class Buddypress_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Buddypress_Pro_Helpers extends Buddypress_Helpers {

	/**
	 * Buddypress_Pro_Helpers constructor.
	 */
	public function __construct() {
		// Selectively load options

		add_action(
			'wp_ajax_select_bp_member_types',
			array(
				$this,
				'select_bp_member_types',
			)
		);
	}

	/**
	 * @param Buddypress_Pro_Helpers $pro
	 */
	public function setPro( Buddypress_Pro_Helpers $pro ) { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
		parent::setPro( $pro );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function list_base_profile_fields( $label = null, $option_code = 'BPFIELD', $args = array() ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr_x( 'Field', 'Buddypress', 'uncanny-automator' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr_x( 'Any field', 'Buddypress', 'uncanny-automator' ),
			)
		);

		$options = array();

		if ( Automator()->helpers->recipe->load_helpers ) {
			if ( $args['uo_include_any'] ) {
				$options[- 1] = $args['uo_any_label'];
			}
			$base_group_id = 1;
			if ( function_exists( 'bp_xprofile_base_group_id' ) ) {
				$base_group_id = bp_xprofile_base_group_id();
			}

			global $wpdb;
			$fields_table    = $wpdb->base_prefix . 'bp_xprofile_fields';
			$xprofile_fields = $wpdb->get_results( "SELECT * FROM {$fields_table} WHERE parent_id = 0 AND group_id = '{$base_group_id}' ORDER BY field_order ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			if ( ! empty( $xprofile_fields ) ) {
				foreach ( $xprofile_fields as $xprofile_field ) {
					$options[ $xprofile_field->id ] = $xprofile_field->name;
				}
			}
		}

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'required'                 => true,
			'options'                  => $options,
			'custom_value_description' => esc_attr_x( 'User ID', 'Buddypress', 'uncanny-automator' ),
		);

		return apply_filters( 'uap_option_list_base_profile_fields', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function get_profile_types( $label = null, $option_code = 'BPPROFILETYPE', $args = array() ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr_x( 'Any profile type', 'Buddypress', 'uncanny-automator' ),
			)
		);

		if ( ! $label ) {
			$label = esc_attr_x( 'Profile type', 'Buddypress', 'uncanny-automator' );
		}

		$options = array();

		if ( Automator()->helpers->recipe->load_helpers ) {
			if ( $args['uo_include_any'] ) {
				$options[- 1] = $args['uo_any_label'];
			}
			if ( function_exists( 'bp_get_member_types' ) ) {
				$types = bp_get_member_types( array() );

				if ( $types ) {
					foreach ( $types as $type ) {
						$options[ $type->ID ] = $type->post_title;
					}
				}
			}
		}

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'required'                 => true,
			'options'                  => $options,
			'is_ajax'                  => true,
			'custom_value_description' => esc_html_x( 'Profile Type ID', 'BuddyBoss', 'uncanny-automator' ),
			'endpoint'                 => 'select_bp_member_types',
		);

		return apply_filters( 'uap_option_get_profile_types', $option );
	}
	/**
	 * Select bp member types.
	 */
	public function select_bp_member_types() {

		Automator()->utilities->ajax_auth_check();

		$fields = array();
		if ( isset( $_POST ) && key_exists( 'value', $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$post_type = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( function_exists( 'bp_get_member_types' ) ) {
				$member_types = bp_get_member_types();

				if ( $member_types ) {
					foreach ( $member_types as $id => $type ) {
						$fields[] = array(
							'value' => $id,
							'text'  => $type,
						);
					}
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function list_all_profile_fields( $label = null, $option_code = 'BPFIELD', $args = array() ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr_x( 'Field', 'Buddypress', 'uncanny-automator' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr_x( 'Any field', 'Buddypress', 'uncanny-automator' ),
				'is_repeater'    => false,
			)
		);

		$options = array();

		if ( Automator()->helpers->recipe->load_helpers ) {
			if ( $args['uo_include_any'] ) {
				$options[- 1] = $args['uo_any_label'];
			}

			global $wpdb;
			$fields_table    = $wpdb->base_prefix . 'bp_xprofile_fields';
			$xprofile_fields = $wpdb->get_results( "SELECT * FROM {$fields_table} WHERE parent_id = 0 ORDER BY field_order ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			if ( ! empty( $xprofile_fields ) ) {
				foreach ( $xprofile_fields as $xprofile_field ) {
					if ( $args['is_repeater'] ) {
						$options[] = array(
							'value' => $xprofile_field->id,
							'text'  => $xprofile_field->name,
						);
					} else {
						$options[ $xprofile_field->id ] = $xprofile_field->name;
					}
				}
			}
		}

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'required'                 => true,
			'options'                  => $options,
			'custom_value_description' => esc_attr_x( 'User ID', 'Buddypress', 'uncanny-automator' ),
		);

		return apply_filters( 'uap_option_list_all_profile_fields', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function list_buddypress_forums( $label = null, $option_code = 'BPFORUMS', $args = array() ) {
		if ( ! $this->load_options ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr_x( 'Any forum', 'Buddypress', 'uncanny-automator' ),
			)
		);
		if ( ! $label ) {
			$label = esc_attr_x( 'Forum', 'Buddypress', 'uncanny-automator' );
		}

		$options    = array();
		$forum_args = array(
			'post_type'      => bbp_get_forum_post_type(),
			'posts_per_page' => 999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => array( 'publish', 'private' ),
		);

		if ( $args['uo_include_any'] ) {
			$options[- 1] = $args['uo_any_label'];
		}

		$forums = Automator()->helpers->recipe->options->wp_query( $forum_args );
		if ( ! empty( $forums ) ) {
			foreach ( $forums as $key => $forum ) {
				$options[ $key ] = $forum;
			}
		}

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code          => esc_attr_x( 'Forum title', 'Buddypress', 'uncanny-automator' ),
				$option_code . '_ID'  => esc_attr_x( 'Forum ID', 'Buddypress', 'uncanny-automator' ),
				$option_code . '_URL' => esc_attr_x( 'Forum URL', 'Buddypress', 'uncanny-automator' ),
			),
		);

		return apply_filters( 'uap_option_list_buddypress_forums', $option );
	}

	/**
	 * get_bp_group_types
	 *
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function get_bp_group_types( $label = null, $option_code = 'BP_GROUP_TYPES', $args = array() ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr_x( 'Any group type', 'Buddypress', 'uncanny-automator-pro' ),
			)
		);

		if ( ! $label ) {
			$label = esc_attr_x( 'Group type', 'Buddypress', 'uncanny-automator-pro' );
		}

		$options = array();
		if ( Automator()->helpers->recipe->load_helpers ) {
			if ( $args['uo_include_any'] ) {
				$options[- 1] = $args['uo_any_label'];
			}
			if ( function_exists( 'bp_groups_get_group_types' ) ) {
				$types = bp_groups_get_group_types( array(), 'objects' );

				if ( $types ) {
					foreach ( $types as $type ) {
						$options[ esc_attr( $type->name ) ] = esc_html( $type->labels['singular_name'] );
					}
				}
			}
		}

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'required'                 => true,
			'options'                  => $options,
			'is_ajax'                  => false,
			'relevant_tokens'          => array(),
			'custom_value_description' => esc_html_x( 'Group type ID', 'BuddyPress', 'uncanny-automator-pro' ),
		);

		return apply_filters( 'uap_option_get_bp_group_types', $option );
	}

	/**
	 * @param $user_xprofile_field_value
	 * @param $value
	 *
	 * @return bool
	 */
	public function check_field_value( $user_xprofile_field_value, $value ) {
		if ( is_array( $user_xprofile_field_value ) ) {
			if ( in_array( $value, $user_xprofile_field_value, true ) ) {
				return true;
			}
		} else {
			if ( $user_xprofile_field_value === $value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $option_code
	 * @param $label
	 *
	 * @return void
	 */
	public function get_bp_activity_actions_list( $option_code = 'BUDDYPRESS_ACTIVITY_ACTION', $label = null ) {
		if ( ! $label ) {
			$label = esc_attr_x( 'Activity action', 'Buddypress', 'uncanny-automator-pro' );
		}

		$options = array();

		// Get the actions.
		$activity_actions = bp_activity_get_actions();
		foreach ( $activity_actions as $component => $actions ) {
			foreach ( $actions as $action_key => $action_value ) {
				$options[ $action_key ] = sprintf( '%s &mdash; %s', ucfirst( $component ), ucfirst( $action_value['value'] ) );
			}
		}

		$option = array(
			'option_code'           => $option_code,
			'label'                 => $label,
			'input_type'            => 'select',
			'required'              => true,
			'options'               => $options,
			'default_value'         => 'activity_update',
			'supports_custom_value' => false,
		);

		return apply_filters( 'uap_option_get_bp_activity_actions_list', $option );
	}
}
