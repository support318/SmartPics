<?php


namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wp_Helpers;

/**
 * Class Wp_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Wp_Pro_Helpers extends Wp_Helpers {

	/**
	 * Wp_Pro_Helpers constructor.
	 */
	public function __construct() {

		add_action( 'wp_ajax_select_custom_post_by_type_post_meta', array( $this, 'select_custom_post_func' ) );
		add_action( 'wp_ajax_select_all_post_from_SELECTEDPOSTTYPE', array( $this, 'select_posts_by_post_type' ) );
		add_action( 'wp_ajax_select_all_terms_of_SELECTEDTAXONOMY', array( $this, 'select_terms_by_taxonomy' ) );
		add_action( 'wp_ajax_select_all_post_of_selected_post_type', array( $this, 'select_all_posts_by_post_type' ) );
		add_action(
			'wp_ajax_select_post_type_taxonomies_SELECTEDTAXONOMY',
			array(
				$this,
				'endpoint_all_taxonomies_by_post_type',
			)
		);

		add_action(
			'wp_ajax_select_all_fields_of_selected_post',
			array(
				$this,
				'select_all_fields_of_selected_post',
			)
		);
		add_action(
			'wp_ajax_select_all_post_of_selected_post_type_no_all',
			array(
				$this,
				'select_all_posts_by_post_type_no_all',
			)
		);
		add_filter( 'uap_option_wp_user_roles', array( $this, 'add_any_option' ), 99, 3 );
	}

	/**
	 * @param $options
	 *
	 * @return array
	 */
	public function add_any_option( $options ) {
		if ( empty( $options ) ) {
			return $options;
		}

		if ( 'USERCREATEDWITHROLE' !== $options['option_code'] ) {
			return $options;
		}

		$options['options'] = array( '-1' => esc_attr__( 'Any role', 'uncanny-automator-pro' ) ) + $options['options'];

		return $options;
	}

	/**
	 * @param Wp_Pro_Helpers $pro
	 */
	public function setPro( Wp_Pro_Helpers $pro ) {
		parent::setPro( $pro );
	}


	/**
	 * Method all_wp_post_types.
	 *
	 * @param string $label The field label.
	 * @param string $option_code The option code.
	 * @param array $args The arguments supplied for the option fields.
	 *
	 * @return array The paramters to be supplied for option fields
	 */
	public function all_wp_post_types( $label = '', $option_code = 'WPPOSTTYPES', $args = array() ) {

		$include_relevant_tokens = isset( $args['include_relevant_tokens'] ) ? (bool) $args['include_relevant_tokens'] : true;

		$defaults = array(
			'token'           => false,
			'comments'        => false,
			'is_ajax'         => false,
			'is_any'          => true,
			'plural_label'    => false,
			'target_field'    => '',
			'endpoint'        => '',
			'options_show_id' => true,
			'relevant_tokens' => $include_relevant_tokens
				? wp_list_pluck( $this->get_post_relevant_tokens( 'trigger', $option_code ), 'name' )
				: array(),
		);

		$args = wp_parse_args( $args, apply_filters( 'automator_all_wp_post_types_defaults', $defaults, $option_code, $args, $this ) );

		$options = array();

		if ( true === $args['is_any'] ) {

			$options['-1'] = __( 'Any post type', 'uncanny-automator-pro' );

		}

		$post_types = get_post_types( array(), 'objects' );

		if ( ! empty( $post_types ) ) {

			foreach ( $post_types as $post_type ) {

				if ( $this->is_post_type_valid( $post_type ) ) {

					$options[ $post_type->name ] = ( true === $args['plural_label'] ) ? esc_html( $post_type->labels->name ) : esc_html( $post_type->labels->singular_name );

				}
			}
		}

		// Dropdown supports comments.
		if ( $args['comments'] ) {

			foreach ( $options as $post_type => $opt ) {

				if ( intval( $post_type ) !== intval( '-1' ) && ! post_type_supports( $post_type, 'comments' ) ) {

					unset( $options[ $post_type ] );

				}
			}
		}

		// Sort alphabetically.
		// asort( $options, SORT_STRING );

		$option = array(
			'input_type'      => 'select',
			'option_code'     => $option_code,
			'label'           => ! empty( $label ) ? $label : __( 'Post types', 'uncanny-automator-pro' ),
			'required'        => true,
			'supports_tokens' => $args['token'],
			'is_ajax'         => $args['is_ajax'],
			'fill_values_in'  => $args['target_field'],
			'endpoint'        => $args['endpoint'],
			'options'         => $options,
			'relevant_tokens' => $args['relevant_tokens'],
			'options_show_id' => $args['options_show_id'],
		);

		return apply_filters( 'uap_option_all_wp_post_types', $option );

	}

	/**
	 * Get relevant tokens for post.
	 *
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function get_post_relevant_tokens( $type = 'trigger', $option_code = 'WPPOSTTYPES' ) {
		$tokens = array(
			$option_code                => array(
				'name' => esc_attr_x( 'Post title', 'WordPress Token', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			$option_code . '_ID'        => array(
				'name' => esc_attr_x( 'Post ID', 'WordPress Token', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			$option_code . '_URL'       => array(
				'name' => esc_attr_x( 'Post URL', 'WordPress Token', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			$option_code . '_POSTNAME'  => array(
				'name' => esc_attr_x( 'Post slug', 'WordPress Token', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
			$option_code . '_THUMB_ID'  => array(
				'name' => esc_attr_x( 'Post featured image ID', 'WordPress Token', 'uncanny-automator-pro' ),
				'type' => 'int',
			),
			$option_code . '_THUMB_URL' => array(
				'name' => esc_attr_x( 'Post featured image URL', 'WordPress Token', 'uncanny-automator-pro' ),
				'type' => 'text',
			),
		);

		return apply_filters( "automator_set_wordpress_post_{$type}_tokens", $tokens );
	}

	/**
	 * Hydrate post relevant tokens
	 *
	 * @param string $option_code
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function hydrate_post_relevant_tokens( $option_code = 'WPPOSTTYPES', $post_id = 0 ) {
		$relevant_tokens = $this->get_post_relevant_tokens( 'action', $option_code );
		$tokens          = array();
		foreach ( $relevant_tokens as $token => $config ) {
			switch ( $token ) {
				case $option_code:
					$tokens[ $token ] = get_the_title( $post_id );
					break;
				case $option_code . '_ID':
					$tokens[ $token ] = $post_id;
					break;
				case $option_code . '_URL':
					$tokens[ $token ] = get_permalink( $post_id );
					break;
				case $option_code . '_POSTNAME':
					$tokens[ $token ] = get_post_field( 'post_name', $post_id );
					break;
				case $option_code . '_THUMB_ID':
					$tokens[ $token ] = get_post_thumbnail_id( $post_id );
					break;
				case $option_code . '_THUMB_URL':
					$tokens[ $token ] = get_the_post_thumbnail_url( $post_id );
					break;
				default:
					$tokens[ $token ] = apply_filters( "automator_hydrate_wordpress_post_token_{$token}", '', $post_id );
					break;
			}
		}

		return $tokens;
	}

	/**
	 * Return all the specific fields of post type in ajax call
	 */
	public function select_posts_by_post_type() {

		Automator()->utilities->ajax_auth_check( $_POST );
		$fields = array();
		if ( isset( $_POST ) && key_exists( 'value', $_POST ) && ! empty( automator_filter_input( 'value', INPUT_POST ) ) ) {
			$post_type = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) );

			$args       = array(
				'posts_per_page'   => 999,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'suppress_filters' => true,
				'fields'           => array( 'ids', 'titles' ),
			);
			$posts_list = Automator()->helpers->recipe->options->wp_query( $args, false, __( 'Any Post', 'uncanny-automator-pro' ) );

			if ( ! empty( $posts_list ) ) {

				$post_type_label = get_post_type_object( $post_type )->labels->singular_name;

				$fields[] = array(
					'value' => '-1',
					'text'  => sprintf( _x( 'Any %s', 'WordPress post type', 'uncanny-automator-pro' ), strtolower( $post_type_label ) ),
				);
				foreach ( $posts_list as $post_id => $post_title ) {
					// Check if the post title is defined
					$post_title = ! empty( $post_title ) ? $post_title : sprintf( __( 'ID: %1$s (no title)', 'uncanny-automator-pro' ), $post_id );

					$fields[] = array(
						'value' => $post_id,
						'text'  => $post_title,
					);
				}
			} else {
				$post_type_label = 'post';

				if ( $post_type != - 1 ) {
					$post_type_label = get_post_type_object( $post_type )->labels->singular_name;
				}

				$fields[] = array(
					'value' => '-1',
					'text'  => sprintf( _x( 'Any %s', 'WordPress post type', 'uncanny-automator-pro' ), strtolower( $post_type_label ) ),
				);
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * Return all the specific fields of post type in ajax call
	 */
	public function select_all_posts_by_post_type() {

		Automator()->utilities->ajax_auth_check( $_POST );
		$fields = array();
		if ( isset( $_POST ) && key_exists( 'value', $_POST ) && ! empty( automator_filter_input( 'value', INPUT_POST ) ) ) {
			$post_type = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) );

			$args       = array(
				'posts_per_page'   => 999,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'suppress_filters' => true,
				'fields'           => array( 'ids', 'titles' ),
			);
			$posts_list = Automator()->helpers->recipe->options->wp_query( $args, false, __( 'Any Post', 'uncanny-automator-pro' ) );

			if ( ! empty( $posts_list ) ) {
				$post_type_label = get_post_type_object( $post_type )->labels->name;
				if ( automator_filter_has_var( 'group_id', INPUT_POST ) && 'SETPOSTMETA' !== automator_filter_input( 'group_id', INPUT_POST ) ) {
					$fields[] = array(
						'value' => '-1',
						'text'  => sprintf( _x( 'All %s', 'WordPress post type', 'uncanny-automator-pro' ), strtolower( $post_type_label ) ),
					);
				}
				foreach ( $posts_list as $post_id => $post_title ) {
					// Check if the post title is defined
					$post_title = ! empty( $post_title ) ? $post_title : sprintf( __( 'ID: %1$s (no title)', 'uncanny-automator-pro' ), $post_id );

					$fields[] = array(
						'value' => $post_id,
						'text'  => $post_title,
					);
				}
			} else {
				if ( automator_filter_has_var( 'group_id', INPUT_POST ) && 'SETPOSTMETA' !== automator_filter_input( 'group_id', INPUT_POST ) ) {
					$fields[] = array(
						'value' => '-1',
						'text'  => __( 'All posts', 'uncanny-automator' ),
					);
				}
			}
		}

		echo wp_json_encode( $fields );

		die();
	}

	/**
	 * @param null $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed|void
	 */
	public function all_wp_taxonomy( $label = null, $option_code = 'WPTAXONOMIES', $args = array() ) {

		if ( ! $label ) {
			$label = __( 'Taxonomy', 'uncanny-automator-pro' );
		}

		$token        = key_exists( 'token', $args ) ? $args['token'] : false;
		$is_ajax      = key_exists( 'is_ajax', $args ) ? $args['is_ajax'] : false;
		$is_any       = key_exists( 'is_any', $args ) ? $args['is_any'] : false;
		$is_all       = key_exists( 'is_all', $args ) ? $args['is_all'] : false;
		$target_field = key_exists( 'target_field', $args ) ? $args['target_field'] : '';
		$end_point    = key_exists( 'endpoint', $args ) ? $args['endpoint'] : '';
		$placeholder  = key_exists( 'placeholder', $args ) ? $args['placeholder'] : '';
		$options      = array();

		if ( $is_any && ! $is_all ) {
			$options['-1'] = __( 'Any taxonomy', 'uncanny-automator-pro' );
		} elseif ( $is_all ) {
			$options['-1'] = __( 'All taxonomies', 'uncanny-automator-pro' );
		}

		// now get regular post types.
		$args = array(
			'public'   => true,
			'_builtin' => true,
		);

		$output   = 'object';
		$operator = 'and';

		$taxonomies = get_taxonomies( $args, $output, $operator );

		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				$options[ $taxonomy->name ] = esc_html( $taxonomy->labels->singular_name );
			}
		}

		// get all custom post types
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);

		$output   = 'object';
		$operator = 'and';

		$custom_taxonomies = get_taxonomies( $args, $output, $operator );

		if ( ! empty( $custom_taxonomies ) ) {
			foreach ( $custom_taxonomies as $custom_taxonomy ) {
				$options[ $custom_taxonomy->name ] = esc_html( $custom_taxonomy->labels->singular_name );
			}
		}

		$type = 'select';

		$option = array(
			'placeholder'     => $placeholder,
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => $type,
			'required'        => true,
			'supports_tokens' => $token,
			'is_ajax'         => $is_ajax,
			'fill_values_in'  => $target_field,
			'endpoint'        => $end_point,
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code => __( 'Taxonomy', 'uncanny-automator-pro' ),
			),
		);

		return apply_filters( 'uap_option_all_wp_taxonomy', $option );
	}

	/**
	 * Return all the specific fields of post type in ajax call
	 */
	public function select_terms_by_taxonomy() {

		Automator()->utilities->ajax_auth_check();
		$fields   = array();
		$group_id = automator_filter_has_var( 'group_id', INPUT_POST ) ? automator_filter_input( 'group_id', INPUT_POST ) : '';
		if ( ! empty( $group_id ) && 'WPREMOVETAXONOMY' === $group_id ) {
			$fields[] = array(
				'value' => - 1,
				'text'  => __( 'All terms', 'uncanny-automator-pro' ),
			);
		} elseif ( 'WPSETTAXONOMY' === $group_id ) {
			//Nothing here
		} else {
			$fields[] = array(
				'value' => - 1,
				'text'  => __( 'Any term', 'uncanny-automator-pro' ),
			);
		}

		if ( isset( $_POST ) && key_exists( 'value', $_POST ) && ! empty( automator_filter_input( 'value', INPUT_POST ) ) ) {
			$taxonomy = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) );

			$terms = array();

			if ( '-1' !== (string) $taxonomy ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				);
			}

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					// Check if the post title is defined
					$term_name = ! empty( $term->name ) ? $term->name : sprintf( __( 'ID: %1$s (no title)', 'uncanny-automator-pro' ), $term->term_id );

					$fields[] = array(
						'value' => $term->term_id,
						'text'  => $term_name,
					);
				}
			}
		}
		echo wp_json_encode( $fields );
		die();
	}

	/**
	 * Endpoint: Fetch all taxonomies by post type.
	 *
	 * @return string @see wp_json_encode();
	 */
	public function endpoint_all_taxonomies_by_post_type() {

		Automator()->utilities->ajax_auth_check();

		$fields = apply_filters( 'automator_endpoint_all_taxonomies_by_post_type_fields_default', array() );

		if ( ! automator_filter_has_var( 'values', INPUT_POST ) ) {

			echo wp_json_encode( $fields );

			die();

		}

		// Check post type trigger key ( Actions VS Triggers ).
		$post_type_key = isset( $_POST['values']['WPSPOSTTYPES'] ) ? 'WPSPOSTTYPES' : false;
		$post_type_key = ! $post_type_key && isset( $_POST['values']['WPPOSTTYPES'] ) ? 'WPPOSTTYPES' : $post_type_key;
		// Get post type or default to 'post'.
		$request_post_type = $post_type_key ? sanitize_text_field( $_POST['values'][ $post_type_key ] ) : 'post';
		$group_id          = automator_filter_has_var( 'group_id', INPUT_POST ) ? automator_filter_input( 'group_id', INPUT_POST ) : '';
		$post_type         = get_post_type_object( $request_post_type );

		if ( ! empty( $group_id ) && 'WPSETTAXONOMY' !== $group_id ) {
			$fields[] = array(
				'value' => - 1,
				'text'  => __( 'Any taxonomy', 'uncanny-automator-pro' ),
			);
		}

		if ( null !== $post_type ) {

			$taxonomies = get_object_taxonomies( $post_type->name, 'object' );

			if ( ! empty( $taxonomies ) ) {

				foreach ( $taxonomies as $taxonomy ) {

					$fields[] = array(
						'value' => $taxonomy->name,
						'text'  => esc_html( $taxonomy->labels->singular_name ),
					);

				}
			}
		}

		echo wp_json_encode( $fields );

		die();

	}

	/**
	 * @param null $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed|void
	 */
	public function wp_post_statuses( $label = null, $option_code = 'WPPOSTSTATUSES', $args = array() ) {

		if ( ! $label ) {
			$label = __( 'Status', 'uncanny-automator-pro' );
		}

		$is_any          = key_exists( 'is_any', $args ) ? $args['is_any'] : false;
		$any_label       = key_exists( 'any_label', $args ) ? $args['any_label'] : __( 'Any status', 'uncanny-autoamtor-pro' );
		$relevant_tokens = key_exists( 'relevant_tokens', $args ) ? $args['relevant_tokens'] : array();
		$options         = array();

		if ( $is_any ) {
			$options['-1'] = $any_label;
		}

		if ( empty( $relevant_tokens ) ) {
			$include_relevant = key_exists( 'include_relevant_tokens', $args ) ? (bool) $args['include_relevant_tokens'] : true;
			if ( $include_relevant ) {
				$relevant_tokens = array(
					$option_code => __( 'Status', 'uncanny-automator-pro' ),
				);
			}
		}

		$post_statuses = get_post_stati( array(), 'objects' );

		if ( ! empty( $post_statuses ) ) {
			foreach ( $post_statuses as $name => $status ) {
				$options[ $name ] = esc_html( $status->label );
			}
		}

		if ( class_exists( 'EF_Custom_Status' ) ) {
			$ef_Custom_Status = $this->register_edit_flow_status();
			if ( ! empty( $ef_Custom_Status ) ) {
				foreach ( $ef_Custom_Status as $ef_status ) {
					$options[ $ef_status->slug ] = esc_html( $ef_status->name );
				}
			}
		}

		$type = 'select';

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => $type,
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => $relevant_tokens,
		);

		return apply_filters( 'uap_option_wp_post_statuses', $option );
	}

	/**
	 * @param null $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed|void
	 */
	public function wp_user_profile_fields( $label = null, $option_code = 'WPUSERFIELDS', $args = array() ) {

		if ( ! $label ) {
			$label = __( 'Profile field', 'uncanny-automator-pro' );
		}

		$is_any          = key_exists( 'is_any', $args ) ? $args['is_any'] : false;
		$relevant_tokens = key_exists( 'relevant_tokens', $args ) ? $args['relevant_tokens'] : '';
		$options         = array();

		if ( $is_any ) {
			$options['-1'] = __( 'Any profile field', 'uncanny-automator-pro' );
		}

		$options['display_name'] = __( 'Display name', 'uncanny-automator-pro' );
		$options['user_email']   = __( 'Email', 'uncanny-automator-pro' );
		$options['user_login']   = __( 'Login', 'uncanny-automator-pro' );
		$options['user_pass']    = __( 'Password', 'uncanny-automator-pro' );
		$options['user_url']     = __( 'Website', 'uncanny-automator-pro' );
		//$options['description']  = __( 'Biographical Info', 'uncanny-automator-pro' );
		//$options['first_name']   = __( 'First name', 'uncanny-automator-pro' );
		//$options['last_name']    = __( 'Last name', 'uncanny-automator-pro' );
		//$options['nickname']     = __( 'Nickname', 'uncanny-automator-pro' );
		$type = 'select';

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => $type,
			'required'        => true,
			'options'         => $options,
			'relevant_tokens' => $relevant_tokens,
		);

		return apply_filters( 'uap_option_wp_user_profile_fields', $option );
	}

	/**
	 * Getting custom post statuses used in Edit Flow plugin.
	 *
	 * @return array
	 */
	private function register_edit_flow_status() {
		global $wp_post_statuses;
		$taxonomy_key = 'post_status';
		// Register new taxonomy so that we can store all our fancy new custom statuses (or is it stati?)
		if ( ! taxonomy_exists( $taxonomy_key ) ) {
			$args = array(
				'hierarchical'          => false,
				'update_count_callback' => '_update_post_term_count',
				'label'                 => false,
				'query_var'             => false,
				'rewrite'               => false,
				'show_ui'               => false,
			);
			register_taxonomy( $taxonomy_key, 'post', $args );
		}
		// Handle if the requested taxonomy doesn't exist
		$args     = array(
			'hide_empty' => false,
			'taxonomy'   => $taxonomy_key,
		);
		$statuses = get_terms( $args );
		if ( is_wp_error( $statuses ) || empty( $statuses ) ) {
			$statuses = array();
		}

		// Expand and order the statuses
		$ordered_statuses = array();
		$hold_to_end      = array();
		foreach ( $statuses as $key => $status ) {
			// Unencode and set all of our psuedo term meta because we need the position if it exists
			$unencoded_description = maybe_unserialize( base64_decode( $status->description ) );
			if ( is_array( $unencoded_description ) ) {
				foreach ( $unencoded_description as $key => $value ) {
					$status->$key = $value;
				}
			}
			// We require the position key later on (e.g. management table)
			if ( ! isset( $status->position ) ) {
				$status->position = false;
			}
			// Only add the status to the ordered array if it has a set position and doesn't conflict with another key
			// Otherwise, hold it for later
			if ( $status->position && ! array_key_exists( $status->position, $ordered_statuses ) ) {
				$ordered_statuses[ (int) $status->position ] = $status;
			} else {
				$hold_to_end[] = $status;
			}
		}
		// Sort the items numerically by key
		ksort( $ordered_statuses, SORT_NUMERIC );
		// Append all of the statuses that didn't have an existing position
		foreach ( $hold_to_end as $unpositioned_status ) {
			$ordered_statuses[] = $unpositioned_status;
		}

		return $ordered_statuses;
	}

	/**
	 * @return array|void
	 */
	public function select_all_fields_of_selected_post() {

		Automator()->utilities->ajax_auth_check();

		$selected_post_id = automator_filter_input( 'value', INPUT_POST );

		$items = array(
			array(
				'value' => '-1',
				'text'  => esc_html__( 'Any field', 'uncanny-automator' ),
			),
		);

		if ( empty( $selected_post_id ) ) {
			return array();
		}

		$fields = $this->get_post_fields( $selected_post_id );

		foreach ( $fields as $field ) {
			$items[] = array(
				'value' => $field,
				'text'  => $field,
			);
		}

		wp_send_json( $items );

	}

	/**
	 * @param $post_id
	 *
	 * @return array
	 */
	public function get_post_fields( $post_id = 0 ) {

		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DISTINCT meta_key FROM wp_postmeta WHERE post_id = %d',
				$post_id
			),
			OBJECT
		);

		// Disable all the meta starting with underscore.
		$results = array_map(
			function ( $key ) {
				if ( '_' === substr( $key->meta_key, 0, 1 ) ) {
					return false;
				}

				return $key->meta_key;

			},
			$results
		);

		$fields = array();

		foreach ( $results as $result ) {
			if ( false !== $result ) {
				$fields[] = $result;
			}
		}

		return $fields;
	}

	/**
	 * Return all the specific fields of post type in ajax call (without 'All (post-type)' option).
	 */
	public function select_all_posts_by_post_type_no_all() {

		Automator()->utilities->ajax_auth_check();

		$fields = array();

		if ( isset( $_POST ) && key_exists( 'value', $_POST ) && ! empty( $_POST['value'] ) ) {

			$post_type = sanitize_text_field( automator_filter_input( 'value', INPUT_POST ) );

			$args = array(
				'posts_per_page'   => 999,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'suppress_filters' => true,
				'fields'           => array( 'ids', 'titles' ),
			);

			$posts_list = Automator()->helpers->recipe->options->wp_query( $args, false, __( 'Any Post', 'uncanny-automator-pro' ) );

			if ( ! empty( $posts_list ) ) {

				$post_type_label = get_post_type_object( $post_type )->labels->name;

				$fields[] = array();

				foreach ( $posts_list as $post_id => $post_title ) {
					// Check if the post title is defined
					$post_title = ! empty( $post_title ) ? $post_title : sprintf( __( 'ID: %1$s (no title)', 'uncanny-automator-pro' ), $post_id );

					$fields[] = array(
						'value' => $post_id,
						'text'  => $post_title,
					);
				}
			}
		}

		echo wp_json_encode( $fields );

		die();
	}

	/**
	 * All WP Post Types.
	 *
	 * @param null $label
	 * @param string $option_code
	 * @param array $args
	 *
	 * @return mixed|void
	 */
	public function all_wp_post_types_set_taxonomy( $label = null, $option_code = 'WPPOSTTYPES', $args = array() ) {
		$args['plural_label'] = true;

		return $this->all_wp_post_types( $label, $option_code, $args );
	}

	/**
	 * Method is_post_type_valid.
	 *
	 * @param string $post_type The post type name.
	 *
	 * @return boolean True if post type meets the criteria. Otherwise, false.
	 */
	public function is_post_type_valid( $post_type ) {

		$invalid_post_types = $this->get_disabled_post_types();

		// Disable attachments.
		if ( in_array( $post_type->name, $invalid_post_types, true ) ) {

			return false;

		}

		return ! empty( $post_type->name ) && ! empty( $post_type->labels->name ) && ! empty( $post_type->labels->singular_name );

	}

	/**
	 * Method get_disabled_post_types.
	 *
	 * @return array A list of post types that should be disabled in dropdown.
	 */
	public function get_disabled_post_types() {

		$post_types = array(
			'attachment',
			'uo-action',
			'uo-closure',
			'uo-trigger',
			'uo-recipe',
			'uo-loop',
			'uo-loop-filter',
			'customize_changeset',
			'custom_css',
			'wp_global_styles',
			'wp_template',
			'wp_template_part',
			'wp_block',
			'user_request',
			'oembed_cache',
			'revision',
			'wp_navigation',
			'nav_menu_item',
		);

		return apply_filters( 'automator_wp_get_disabled_post_types', $post_types );

	}

	/**
	 * @param string $label
	 * @param string $option_code
	 *
	 * @return mixed
	 */
	public function all_wp_users( $label = null, $option_code = 'WPUSERS', $args = array() ) {

		if ( ! $label ) {
			$label = esc_attr__( 'User', 'uncanny-automator-pro' );
		}

		$options = array();
		$users   = Automator()->helpers->recipe->wp_users();

		foreach ( $users as $user ) {
			$options[ $user->ID ] = $user->display_name;
		}

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'required'                 => true,
			'options'                  => $options,
			'supports_custom_value'    => $options,
			'custom_value_description' => esc_attr__( 'User ID', 'uncanny-automator' ),
		);

		return apply_filters( 'uap_option_all_wp_users', $option );
	}

	/**
	 * Conditional child taxonomy checkbox
	 *
	 * @param string $label
	 * @param string $option_code
	 * @param string $comparision_code
	 *
	 * @return array
	 */
	public static function pro_conditional_child_taxonomy_checkbox( $label = null, $option_code = 'WPTAXONOMIES_CHILDREN', $comparision_code = 'WPTAXONOMIES' ) {

		if ( empty( $label ) ) {
			$label = esc_attr__( 'Also include child categories', 'uncanny-automator' );
		}

		$args = array(
			'option_code'           => $option_code,
			'label'                 => $label,
			'input_type'            => 'checkbox',
			'required'              => false,
			'exclude_default_token' => true,
			/*
			'dynamic_visibility' => array(
				'default_state'    => 'hidden',
				'visibility_rules' => array(
					array(
						'operator'             => 'AND',
						'rule_conditions'      => array(
							array(
								'option_code' => $comparision_code,
								'compare'     => '==',
								'value'       => 'category',
							),
						),
						'resulting_visibility' => 'show',
					),
				),
			),
			*/
		);

		return Automator()->helpers->recipe->field->text( $args );
	}

	/**
	 * Get term child of a parent term from provided post terms.
	 *
	 * @param array $post_terms
	 * @param int $parent_term_id
	 * @param string $taxonomy
	 * @param int $post_id
	 *
	 * @return mixed WP_Term|false
	 */
	public static function pro_get_term_child_of( $post_terms, $parent_term_id, $taxonomy, $post_id ) {

		if ( empty( $post_terms ) || ! is_array( $post_terms ) ) {
			return false;
		}

		// Check Post Type for the post
		$allowed_post_types = apply_filters( 'automator_allowed_category_taxonomy_children_post_types', array( 'post' ) );
		if ( ! in_array( get_post_type( $post_id ), $allowed_post_types, true ) ) {
			return false;
		}

		$allowed_taxonomies = apply_filters( 'automator_allowed_category_taxonomy_children_taxonomies', array( 'category' ) );
		if ( ! is_array( $allowed_taxonomies ) || ! in_array( $taxonomy, $allowed_taxonomies, true ) ) {
			return false;
		}

		// Get all child terms of the parent term
		$child_terms = get_term_children( $parent_term_id, $taxonomy );
		if ( empty( $child_terms ) || is_wp_error( $child_terms ) ) {
			return false;
		}

		foreach ( $post_terms as $post_term ) {
			if ( in_array( $post_term->term_id, $child_terms, true ) && $post_term->taxonomy === $taxonomy ) {
				return $post_term;
			}
		}

		return false;
	}

}
