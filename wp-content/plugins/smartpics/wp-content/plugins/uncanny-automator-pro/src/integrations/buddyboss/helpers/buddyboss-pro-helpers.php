<?php


namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Buddyboss_Helpers;

/**
 * Class Buddyboss_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Buddyboss_Pro_Helpers extends Buddyboss_Helpers {

	/**
	 * Current BP user ID.
	 *
	 * @var int
	 */
	private $current_bp_user_id = 0;

	/**
	 * Buddypress_Pro_Helpers constructor.
	 */
	public function __construct( $load_action_hook = true ) {
		// Selectively load options

		if ( true === $load_action_hook ) {
			add_action(
				'wp_ajax_select_topic_from_forum_BDBTOPICREPLY_NOANY',
				array(
					$this,
					'select_topic_fields_func_noany',
				)
			);

			add_filter(
				'uap_option_all_buddyboss_users',
				array(
					$this,
					'add_multiple_select',
				),
				99,
				3
			);

			add_filter(
				'uap_option_all_buddyboss_users',
				array(
					$this,
					'remove_user_token',
				),
				99,
				1
			);

		}
	}


	/**
	 * Remove User token from the dropdown
	 *
	 * @param $option
	 *
	 * @return array
	 */
	public function remove_user_token( $option = array() ) {
		if ( 'BDB_RECEIVESPRIVATEMSGFROMUSER_META' === $option['option_code'] || 'BDB_SENDPRIVATEMSGTOUSER_META' === $option['option_code'] ) {
			$option['relevant_tokens'] = array();
		}

		return $option;
	}

	/**
	 * @param Buddyboss_Pro_Helpers $pro
	 */
	public function setPro( Buddyboss_Pro_Helpers $pro ) {
		parent::setPro( $pro );
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function add_multiple_select( $options ) {
		if ( empty( $options ) ) {
			return $options;
		}

		if ( 'BDBALLUSERS' !== $options['option_code'] ) {
			return $options;
		}

		$options['supports_multiple_values'] = true;

		return $options;
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function list_base_profile_fields(
		$label = null, $option_code = 'BDBFIELD', $args = array()
	) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Field', 'uncanny-automator' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any field', 'uncanny-automator' ),
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
			$xprofile_fields = $wpdb->get_results( "SELECT * FROM {$fields_table} WHERE parent_id = 0 AND group_id = '{$base_group_id}' ORDER BY field_order ASC" );
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
			'custom_value_description' => esc_attr__( 'User ID', 'uncanny-automator' ),
		);

		return apply_filters( 'uap_option_list_base_profile_fields', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function get_profile_types(
		$label = null, $option_code = 'BDBPROFILETYPE', $args = array()
	) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any profile type', 'uncanny-automator' ),
			)
		);

		if ( ! $label ) {
			$label = esc_attr__( 'Profile type', 'uncanny-automator' );
		}

		$options = array();

		if ( Automator()->helpers->recipe->load_helpers ) {
			if ( $args['uo_include_any'] ) {
				$options[- 1] = $args['uo_any_label'];
			}
			if ( function_exists( 'bp_get_active_member_types' ) ) {
				$types = bp_get_active_member_types(
					array(
						'fields' => '*',
					)
				);

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
			'custom_value_description' => _x( 'Profile Type ID', 'BuddyBoss', 'uncanny-automator' ),
		);

		return apply_filters( 'uap_option_get_profile_types', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function list_all_profile_fields( $label = null, $option_code = 'BDBFIELD', $args = array() ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Field', 'uncanny-automator' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any field', 'uncanny-automator' ),
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
			$xprofile_fields = $wpdb->get_results( "SELECT * FROM {$fields_table} WHERE parent_id = 0 ORDER BY field_order ASC" );
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
			'custom_value_description' => esc_attr__( 'User ID', 'uncanny-automator' ),
		);

		return apply_filters( 'uap_option_list_all_profile_fields', $option );
	}

	/**
	 * Return all the specific topics of a forum in ajax call
	 */
	public function select_topic_fields_func_noany() {

		Automator()->utilities->ajax_auth_check( $_POST );

		$fields = array();
		if ( isset( $_POST ) ) {

			$forum_id = (int) automator_filter_input( 'value', INPUT_POST );

			if ( $forum_id > 0 ) {
				$args = array(
					'post_type'      => bbp_get_topic_post_type(),
					'post_parent'    => $forum_id,
					'post_status'    => array_keys( get_post_stati() ),
					'posts_per_page' => 9999,
				);

				$topics = get_posts( $args );

				if ( ! empty( $topics ) ) {
					foreach ( $topics as $topic ) {
						$fields[] = array(
							'value' => $topic->ID,
							'text'  => $topic->post_title,
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
	public function get_groups_types( $label = null, $option_code = 'BDBGROUPTYPES', $args = array() ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'required'       => true,
				'uo_any_label'   => esc_attr__( 'Any group type', 'uncanny-automator-pro' ),
			)
		);

		if ( ! $label ) {
			$label = esc_attr__( 'Group type', 'uncanny-automator-pro' );
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
			'required'                 => $args['required'],
			'options'                  => $options,
			'relevant_tokens'          => ( isset( $args['relevant_tokens'] ) ) ? $args['relevant_tokens'] : array(),
			'custom_value_description' => _x( 'Group Type ID', 'BuddyBoss', 'uncanny-automator-pro' ),
		);

		return apply_filters( 'uap_option_get_groups_types', $option );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 * @param array $args
	 * @param bool $multi_select
	 *
	 * @return mixed
	 */
	public function list_buddyboss_forums( $label = null, $option_code = 'BDBFORUMS', $args = array(), $multi_select = false ) {
		if ( ! $this->load_options ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {

			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any'  => false,
				'uo_any_label'    => esc_attr__( 'Any forum', 'uncanny-automator' ),
				'support_custom'  => false,
				'custom_label'    => esc_attr__( 'Forum ID', 'uncanny-automator' ),
				'format_options'  => false,
				'relevant_tokens' => array(
					$option_code          => esc_attr__( 'Forum title', 'uncanny-automator' ),
					$option_code . '_ID'  => esc_attr__( 'Forum ID', 'uncanny-automator' ),
					$option_code . '_URL' => esc_attr__( 'Forum URL', 'uncanny-automator' ),
				),
			)
		);
		if ( ! $label ) {
			$label = esc_attr__( 'Forum', 'uncanny-automator' );
		}

		$options    = array();
		$forum_args = array(
			'post_type'      => bbp_get_forum_post_type(),
			'posts_per_page' => 999,
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

		// Format options to text / value arrays.
		if ( $args['format_options'] ) {
			$options = array_map(
				function ( $value, $text ) {
					return array(
						'text'  => $text,
						'value' => $value,
					);
				},
				array_keys( $options ),
				$options
			);
		}

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'supports_multiple_values' => $multi_select,
			'required'                 => true,
			'options'                  => $options,
			'relevant_tokens'          => $args['relevant_tokens'],
		);

		// Check if we should support custom values.
		if ( $args['support_custom'] ) {
			$option['supports_custom_value']    = true;
			$option['custom_value_description'] = $args['custom_label'];
		}

		return apply_filters( 'uap_option_list_buddyboss_forums', $option );
	}

	/**
	 * Checks if User is exists or not.
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function bdb_user_id_exists( $user_id ) {
		global $wpdb;
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users WHERE ID = %d", $user_id ) );

		return empty( $count ) || 1 > $count ? false : true;
	}

	/**
	 * @return int[]|\WP_Post[]
	 */
	public function bdb_get_group_types() {
		$args = array(
			'post_type'      => 'bp-group-type',
			'posts_per_page' => 9999,
			'post_status'    => 'any',
			'fields'         => 'ids',
		);

		return get_posts( $args );
	}

	/**
	 * Get the group title
	 *
	 * @param int $group_id
	 *
	 * @return string|null
	 */
	public function bdb_get_group_title( $group_id ) {

		// Empty title if no ID provided
		if ( absint( $group_id ) === 0 ) {
			return '';
		}

		$group = groups_get_group( $group_id );

		return $group->name;

	}

	/**
	 * Get notification type based on key.
	 *
	 * @param $key
	 *
	 * @return string|void
	 */
	public function get_notification_type( $key ) {

		if ( function_exists( 'bb_register_notification_preferences' ) ) {
			$all_notifications = bb_register_notification_preferences();

			if ( ! empty( $all_notifications ) ) {
				foreach ( $all_notifications as $notification ) {
					if ( ! empty( $notification['fields'] ) ) {
						foreach ( $notification['fields'] as $field ) {
							if ( $key === $field['notifications'][0]['component_action'] ) {
								return ucwords( $field['notifications'][0]['component'] ) . ' - ' . $field['label'];
							}
						}
					}
				}
			}
		}
	}


	/**
	 * @param $group_id
	 * @param $single
	 *
	 * @return array|false|mixed
	 */
	public function uo_bp_groups_get_group_type( $group_id, $single = true ) {

		$raw_types = bp_get_object_terms( $group_id, 'bp_group_type' );

		if ( ! is_wp_error( $raw_types ) ) {
			$types = array();

			// Only include currently registered group types.
			foreach ( $raw_types as $gtype ) {
				if ( bp_groups_get_group_type_object( $gtype->name ) ) {
					$types[] = $gtype->name;
				}
			}
		}

		$type = false;
		if ( ! empty( $types ) ) {
			if ( $single ) {
				$type = end( $types );
			} else {
				$type = $types;
			}
		}

		return $type;
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function all_buddyboss_actions( $label = null, $option_code = 'BDBACTIONS', $args = array() ) {

		if ( ! $label ) {
			$label = esc_attr__( 'Activity action', 'uncanny-automator-pro' );
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

		return apply_filters( 'uap_option_all_buddyboss_actions', $option );
	}

	/**
	 * Send Message To Users.
	 *
	 * @param array $args       - Message Args
	 * @param int   $bp_user_id - User ID
	 *
	 * @return bool
	 */
	public function send_message_to_users( $args, $bp_user_id ) {
		$filtered = false;
		// Check if bp_loggedin_user_id is empty.
		if ( empty( bp_loggedin_user_id() ) ) {
			$filtered                 = true;
			$this->current_bp_user_id = $bp_user_id;
			add_filter( 'bp_loggedin_user_id', array( $this, 'set_bp_loggedin_user_id' ), 10, 1 );
		}
		// Send the message.
		$send = messages_new_message( $args );

		// Remove the filter.
		if ( $filtered ) {
			remove_filter( 'bp_loggedin_user_id', array( $this, 'set_bp_loggedin_user_id' ), 10, 1 );
		}

		return $send;
	}

	/**
	 * Set BP Loggedin User ID.
	 *
	 * @param int $bp_user_id - User ID
	 *
	 * @return int
	 */
	public function set_bp_loggedin_user_id( $bp_user_id ) {
		return empty( $bp_user_id ) ? $this->current_bp_user_id : $bp_user_id;
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
	 * @param $action_content
	 * @param $activity_id
	 *
	 * @return void
	 */
	public function generate_preview( $action_content, $activity_id ) {
		// Extract URLs using regex.
		if ( ! preg_match_all( '/(https?:\/\/[^\s]+)/', strip_tags( $action_content ), $matches ) ) {
			return; // Bail early if no URLs found.
		}
		$link_url = $matches[0][0] ?? '';
		if ( empty( $link_url ) ) {
			return; // Bail early if no URL is extracted.
		}
		// Get URL parsed data and bail early if an error occurs.
		$preview_data = bp_core_parse_url( $link_url );
		if ( ! empty( $preview_data['error'] ) ) {
			return;
		}
		// Process image URL.
		$this->process_image_url( $preview_data, $link_url, $activity_id );
		// Check for embed URL and add embed preview.
		if ( ! empty( $preview_data['wp_embed'] ) ) {
			bp_activity_update_meta( $activity_id, '_link_embed', $link_url );
		}
	}

	/**
	 * @param $preview_data
	 * @param $link_url
	 * @param $activity_id
	 *
	 * @return void
	 */
	private function process_image_url( &$preview_data, $link_url, $activity_id ) {
		$link_image = filter_var( $preview_data['images'][0] ?? '', FILTER_VALIDATE_URL );
		if ( empty( $link_image ) ) {
			return; // Bail early if no valid image URL is found.
		}
		$preview_data['url'] = $link_url;
		$attachment_id       = bb_media_sideload_attachment( $link_image );
		if ( $attachment_id ) {
			$preview_data['attachment_id']         = $attachment_id;
			$preview_data['link_image_index_save'] = 0;
		} else {
			// Store non-downloadable URLs as it is in preview data.
			$preview_data['image_url'] = $link_image;
		}
		bp_activity_update_meta( $activity_id, '_link_preview_data', $preview_data );
	}

	/**
	 * Normalize multiselect forum IDs.
	 *
	 * @param mixed string || null - $forums
	 *
	 * @return array
	 */
	public function normalize_multiselect_forum_ids( $forums ) {

		// Attempt to decode JSON string
		$decoded = json_decode( $forums, true );

		// Check if decoding was successful and resulted in an array.
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			$forums = $decoded;
		} else {
			// Ensure we're dealing with an array value.
			$forums = is_array( $forums ) ? $forums : array( $forums );
		}

		// Return the array of forum IDs.
		return array_map( 'intval', $forums );
	}

	/**
	 * Validate forum IDs
	 *
	 * @param int   $user_id
	 * @param array $forums
	 *
	 * @return bool
	 */
	public function validate_forum_ids( $forums ) {
		$results = array(
			'valid'   => array(),
			'invalid' => array(),
		);

		foreach ( $forums as $forum_id ) {
			$k               = false === bbp_is_forum( $forum_id ) ? 'invalid' : 'valid';
			$results[ $k ][] = $forum_id;
		}

		return $results;
	}

	/**
	 * Validate if user is subscribed to forums
	 *
	 * @param int   $user_id
	 * @param array $forums
	 *
	 * @return array
	 */
	public function validate_user_forum_subscriptions( $user_id, $forums ) {
		$results = array(
			'subscribed'     => array(),
			'not-subscribed' => array(),
		);

		foreach ( $forums as $forum_id ) {
			$k               = false === bbp_is_user_subscribed( $user_id, $forum_id ) ? 'not-subscribed' : 'subscribed';
			$results[ $k ][] = $forum_id;
		}

		return $results;
	}


}
