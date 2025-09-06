<?php
namespace Uncanny_Automator_Pro\Loops\Recipe;

use Uncanny_Automator_Pro\Loops\Loop\Entity_Factory;
use Uncanny_Automator_Pro\Utilities;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * The Endpoint class for handling different actions related to Loop.
 *
 * Endpoint: wp-json/automator/v1/loop/<recipe id>
 *
 * @since 5.0
 */
class Endpoint {

	/**
	 * @var \wpdb $db
	 */
	protected $db = null;

	/**
	 * @var WP_REST_Request $request
	 */
	protected $request;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public function set_request( WP_REST_Request $request ) {
		$this->request = $request;
	}

	/**
	 * @return WP_REST_Request
	 */
	public function get_request() {
		return $this->request;
	}

	/**
	 * Register various hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {

		// Registers various endpoints for controlling loops related things from the Recipe UI.
		add_action( 'rest_api_init', array( $this, 'register_loop_endpoint' ) );

	}

	/**
	 * Registers loop endpoint to handle recipe ui updates.
	 *
	 * @return void
	 */
	public function register_loop_endpoint() {

		register_rest_route(
			'uap/v2',
			'/loop/(?P<recipe_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'actions' ),
				'permission_callback' => function( $request ) {
					return current_user_can( 'manage_options' );
				},
			)
		);

	}

	/**
	 * Routes given action to its handler.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function actions( WP_REST_Request $request ) {

		$this->set_request( $request );

		$action    = $this->get_request()->get_param( 'action' );
		$recipe_id = absint( $this->get_request()->get_param( 'recipe_id' ) );

		$recipe = get_post( $recipe_id );

		if ( empty( $recipe ) || 'uo-recipe' !== $recipe->post_type ) {
			return new WP_REST_Response(
				array(
					'status'  => 404,
					'message' => 'Recipe (ID: ' . $recipe_id . ') not found.',
				)
			);
		}

		switch ( $action ) {
			case 'add_loop_block':
				return $this->add_loop_block();
			case 'update_loop_block':
				return $this->update_loop_block();
			case 'delete_loop_block':
				return $this->delete_loop_block();
			case 'add_filter':
				return $this->add_filter();
			case 'delete_filter':
				return $this->delete_filter();
			case 'get_filter':
				return $this->get_filter();
			case 'update_filter':
				return $this->update_filter();
			default:
				return new WP_REST_Response(
					array(
						'error'  => 'Bad request: Action not found',
						'status' => 404,
					),
					404
				);
		}

	}

	/**
	 * Adds a loop block
	 *
	 * @return WP_REST_Response
	 */
	public function add_loop_block() {

		$recipe_id           = absint( $this->get_request()->get_param( 'recipe_id' ) );
		$loopable_expression = (array) $this->get_request()->get_param( 'iterable_expression' );
		$post_type           = 'uo-loop';

		// Prepare the loop post object
		$loop = array(
			'post_title'  => 'loop-recipe-' . time() . '-' . $recipe_id,
			'post_status' => 'publish',
			'post_type'   => $post_type,
			'post_author' => get_current_user_id(),
			'post_parent' => $recipe_id,
		);

		// Insert the post into the post table.
		$loop_id = wp_insert_post( $loop, true );

		if ( is_wp_error( $loop_id ) ) {
			return $this->respond_with_error( $loop_id );
		}

		update_post_meta( $loop_id, 'iterable_expression', $loopable_expression );

		$default_added = $this->assign_default_filter( $loop_id, $loopable_expression );
		// Set loop default meta.
		$type        = isset( $loopable_expression['type'] ) ? strtoupper( $loopable_expression['type'] ) : 'UNKNOWN';
		$integration = in_array( $type, array( 'POSTS', 'USERS', 'TOKEN' ), true ) ? 'WP' : 'UNKNOWN';
		Automator()->set_recipe_part_meta( $loop_id, 'LOOP_' . $type, $integration, $post_type );

		return $this->respond_with_data(
			array(
				'loop_id'             => $loop_id,
				'default_added'       => is_wp_error( $default_added ) ? $default_added->get_error_message() : $default_added,
				'recipe_id'           => $recipe_id,
				'iterable_expression' => get_post_meta( $loop_id, 'iterable_expression', true ),
			),
			$recipe_id
		);

	}

	/**
	 * Updates loop block.
	 *
	 * @return WP_REST_Response
	 */
	public function update_loop_block() {

		$loop_id             = absint( $this->get_request()->get_param( 'loop_id' ) );
		$loopable_expression = (array) $this->get_request()->get_param( 'iterable_expression' );

		// Prepare the loop post object.
		$loop = array(
			'ID' => $loop_id,
		);

		// Updates the post into the post table.
		$loop_id = wp_update_post( $loop, true );

		if ( is_wp_error( $loop_id ) ) {
			return $this->respond_with_error( $loop_id );
		}

		update_post_meta( $loop_id, 'iterable_expression', $loopable_expression );

		$loop = get_post( $loop_id );

		$recipe_id = $loop->post_parent ?? null;

		return $this->respond_with_data(
			array(
				'loop_id'             => $loop_id,
				'updated'             => true,
				'recipe_id'           => $recipe_id,
				'iterable_expression' => get_post_meta( $loop_id, 'iterable_expression', true ),
			),
			absint( $recipe_id )
		);

	}

	/**
	 * Deletes a specific loop block.
	 *
	 * - Deletes the loop post
	 * - Deletes all the filters that are under it.
	 * - Moves the actions back to the recipe
	 *
	 *  @return WP_REST_Response
	 */
	public function delete_loop_block() {

		$request = $this->get_request();

		$recipe_id = absint( $request->get_param( 'recipe_id' ) );
		$loop_id   = absint( $request->get_param( 'loop_id' ) );

		$filters = get_posts(
			array(
				'post_parent'    => $loop_id,
				'post_type'      => 'uo-loop-filter',
				'posts_per_page' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			)
		);

		// Deletes all filters.
		// Advantage of deleting each filter is that we can use wp_delete_post and maybe hook into it.
		foreach ( $filters as $filter ) {
			wp_delete_post( $filter->ID, true );
		}

		// Move the actions back to its parent.
		$actions = get_posts(
			array(
				'post_parent'    => $loop_id,
				'post_type'      => 'uo-action',
				'posts_per_page' => 9999, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			)
		);

		$errors = array();

		foreach ( $actions as $action ) {

			$updated = wp_update_post(
				array(
					'ID'          => $action->ID,
					'post_parent' => $recipe_id,
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				$errors[] = $updated->get_error_message();
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_REST_Response(
				array(
					'success'  => false,
					'error'    => 'Error moving actions from the specified loop',
					'messages' => $errors,
				),
				400
			);
		}

		// Finally delete the loop.
		wp_delete_post( $loop_id, true );

		return $this->respond_with_data(
			array(
				'success'         => true,
				'loop_deleted'    => $loop_id,
				'filters_deleted' => array_column( (array) $filters, 'ID' ),
				'actions_moved'   => array_column( (array) $actions, 'ID' ),
			),
			$recipe_id
		);

	}

	/**
	 * Adds a filter to a specific loop.
	 *
	 * @return WP_REST_Response
	 */
	public function add_filter() {

		$request = $this->get_request();

		$recipe_id                    = absint( $request->get_param( 'recipe_id' ) );
		$loop_id                      = absint( $request->get_param( 'loop_id' ) );
		$loop_filter_integration_code = $request->get_param( 'loop_filter_integration_code' );
		$loop_filter_code             = $request->get_param( 'loop_filter_code' );

		if ( empty( $loop_filter_code ) || ! is_string( $loop_filter_code ) ) {
			$loop_filter_code = '';
		}

		$backup = $this->to_string( $request->get_param( 'backup' ) );
		$fields = $this->to_string( $request->get_param( 'fields' ) );

		$backup_validity = $this->validate_json_field( 'backup', (string) $backup );
		$fields_validity = $this->validate_json_field( 'fields', (string) $fields );

		if ( is_wp_error( $backup_validity ) ) {
			return $this->respond_with_error( $backup_validity );
		}

		if ( is_wp_error( $fields_validity ) ) {
			return $this->respond_with_error( $fields_validity );
		}

		// Persist the filter to the db.
		$filter_id = $this->persist_filter( $loop_id, $loop_filter_code );

		if ( is_wp_error( $filter_id ) ) {
			return $this->respond_with_error( $filter_id );
		}

		// Set defaults meta.
		Automator()->set_recipe_part_meta( $filter_id, $loop_filter_code, $loop_filter_integration_code, 'uo-loop-filter' );

		// Update integration_code.
		update_post_meta( $filter_id, 'integration_code', $loop_filter_integration_code );
		// Update fields.
		update_post_meta( absint( $filter_id ), 'fields', $fields );
		// Update backup.
		update_post_meta( absint( $filter_id ), 'backup', $backup );

		return $this->respond_with_data(
			array(
				'filter_id'       => $filter_id,
				'filter_postmeta' => \Uncanny_Automator_Pro\Utilities::flatten_post_meta( (array) get_post_meta( $filter_id ) ),
				'filter_post'     => get_post( $filter_id ),
			),
			$recipe_id
		);
	}

	/**
	 * @param int $loop_id
	 * @param string $loop_filter_code
	 *
	 * @return int|WP_Error
	 */
	protected function persist_filter( $loop_id, $loop_filter_code ) {

		$filter_id = wp_insert_post(
			array(
				'post_parent' => $loop_id,
				'post_title'  => sprintf( 'loop_filter_%s_%d', $loop_filter_code, $loop_id ), //@phpstan-ignore-line There is no need to check for filter code type.
				'post_type'   => 'uo-loop-filter',
				'post_status' => 'publish',
			),
			true
		);

		return $filter_id;
	}

	/**
	 * Assigns a default filter to the loop.
	 *
	 * @param int $loop_id The Loop ID.
	 * @param mixed[] $loopable_expression The loopable expression.
	 *
	 * @return true|WP_Error
	 */
	protected function assign_default_filter( $loop_id, $loopable_expression ) {

		$filter_id        = 0;
		$code             = '';
		$fields           = '';
		$backup           = '';
		$integration_code = 'WP';

		if ( Entity_Factory::TYPE_POSTS === $loopable_expression['type'] ) {
			// The filter ID.
			$filter_id = $this->persist_filter( $loop_id, 'WP_POST_EQUALS_POST_TYPE' );
			// The filter code.
			$code = 'WP_POST_EQUALS_POST_TYPE';
			// Default field values for post.
			$fields = self::get_default_loop_filter_fields_post();
			// Default backup values for post.
			$backup = self::get_default_loop_filter_backup_post();
		}

		if ( Entity_Factory::TYPE_USERS === $loopable_expression['type'] ) {
			// The filter code for posts.
			$code = 'WP_USER_HAS_ROLE';
			// The filter id for posts.
			$filter_id = $this->persist_filter( $loop_id, 'WP_USER_HAS_ROLE' );
			// Default field values.
			$fields = self::get_default_loop_filter_fields_user();
			// Default backup values.
			$backup = self::get_default_loop_filter_backup_user();
		}

		if ( Entity_Factory::TYPE_TOKEN === $loopable_expression['type'] ) {
			$integration_code = 'GEN';
			// The filter code for posts.
			$code = 'ITEM_NOT_EMPTY';
			// The filter id for posts.
			$filter_id = $this->persist_filter( $loop_id, 'ITEM_NOT_EMPTY' );
			// Default field values.
			$fields = self::get_default_loop_filter_fields_token();
			// Default backup values.
			$backup = self::get_default_loop_filter_backup_token();
		}

		if ( is_wp_error( $filter_id ) ) {
			return $filter_id;
		}

		update_post_meta( $filter_id, 'integration_code', $integration_code );
		// Set defaults.
		Automator()->set_recipe_part_meta( $filter_id, $code, 'WP', 'uo-loop-filter' );

		update_post_meta( $filter_id, 'fields', wp_json_encode( $fields ) );
		update_post_meta( $filter_id, 'backup', wp_json_encode( $backup ) );

		return true;

	}

	/**
	 * Deletes a specific filter from loop.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_filter() {

		$request = $this->get_request();

		$recipe_id = absint( $request->get_param( 'recipe_id' ) );
		$filter_id = absint( $request->get_param( 'loop_filter_id' ) );

		wp_delete_post( $filter_id, true );

		return $this->respond_with_data(
			array(
				'filter_deleted'   => empty( get_post( $filter_id ) ),
				'postmeta_deleted' => empty( get_post_meta( $filter_id, 'code', true ) ),
			),
			$recipe_id
		);

	}

	/**
	 * Retrieves a specific filter using the integration code and the filter code (meta).
	 *
	 * @return WP_REST_Response
	 */
	public function get_filter() {

		$request = $this->get_request();

		$recipe_id                    = $request->get_param( 'recipe_id' );
		$loop_filter_integration_code = $request->get_param( 'loop_filter_integration_code' );
		$loop_filter_code             = $request->get_param( 'loop_filter_code' );

		$registered_filters = automator_pro_loop_filters()->get_filters();

		if ( ! isset( $registered_filters[ $loop_filter_integration_code ][ $loop_filter_code ] ) ) { // @phpstan-ignore-line (Cannot access offset mixed on mixed.)
			return new WP_REST_Response(
				array(
					'success'  => false,
					'error'    => 'filter_not_found',
					'message'  => 'Filter is not found',
					'recieved' => $request->get_params(),
				),
				404
			);
		}

		return $this->respond_with_data( $registered_filters[ $loop_filter_integration_code ][ $loop_filter_code ], absint( $recipe_id ) ); // @phpstan-ignore-line (Cannot access offset mixed on mixed.)

	}

	/**
	 * Updates a specific filter.
	 *
	 * @return WP_REST_Response
	 */
	public function update_filter() {

		$request = $this->get_request();

		$recipe_id = absint( $request->get_param( 'recipe_id' ) );
		$filter_id = absint( $request->get_param( 'loop_filter_id' ) );

		$backup = $this->to_string( $request->get_param( 'backup' ) );
		$fields = $this->to_string( $request->get_param( 'fields' ) );

		$backup_validity = $this->validate_json_field( 'backup', (string) $backup );
		$fields_validity = $this->validate_json_field( 'fields', (string) $fields );

		if ( is_wp_error( $backup_validity ) ) {
			return $this->respond_with_error( $backup_validity );
		}

		if ( is_wp_error( $fields_validity ) ) {
			return $this->respond_with_error( $fields_validity );
		}

		// Update fields.
		update_post_meta( absint( $filter_id ), 'fields', $fields );
		// Update backup.
		update_post_meta( absint( $filter_id ), 'backup', $backup );

		return $this->respond_with_data(
			array(
				'fields' => json_decode( $this->to_string( get_post_meta( $filter_id, 'fields', true ) ), true ),
				'meta'   => Utilities::flatten_post_meta( (array) get_post_meta( $filter_id ) ),
			),
			absint( $recipe_id )
		);

	}

	/**
	 * Validates the given json string.
	 *
	 * @param string $label use to mark the specific field.
	 * @param string $json_string The JSON string.
	 *
	 * @return wp_error|true Returns true if field is not empty and if its a valid JSON. Returns an instance of WP_Error, otherwise.
	 */
	public function validate_json_field( $label = '', $json_string = '' ) {

		if ( empty( $json_string ) ) {
			return new WP_Error( 400, 'Parameter: ' . $label . ' is missing or has empty value.', array() );
		}

		$decoded_string = json_decode( $json_string, true );

		// Validate JSON Type fields
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 400, 'Cannot decode sent parameter ' . $label . ' into JSON. Make sure the JSON string is valid.', array() );
		}

		return true;

	}

	/**
	 * Respond successfully (status:200) with recipe_object
	 *
	 * @param mixed[] $args The arguments you want to send to the client back.
	 * @param int $recipe_id
	 *
	 * @return WP_REST_Response
	 */
	private function respond_with_data( $args, $recipe_id ) {

		$args['success'] = true;

		$args['_recipe'] = Automator()->get_recipe_object( $recipe_id, 'JSON' );

		return new WP_REST_Response( $args, 200 );

	}

	/**
	 * Response with error code
	 *
	 * @param WP_Error $error
	 *
	 * @return WP_REST_Response
	 */
	private function respond_with_error( \WP_Error $error ) {

		$status = array(
			'success'  => false,
			'status'   => $error->get_error_code(),
			'message'  => $error->get_error_message(),
			'received' => $this->get_request()->get_params(),
		);

		return new WP_REST_Response( $status, absint( $error->get_error_code() ) );

	}

	/**
	 * Cast mixed valus to string. Object and Array will return empty string.
	 *
	 * @param mixed $var
	 *
	 * @return string
	 */
	private function to_string( $var = '' ) {

		if ( ! is_scalar( $var ) ) {
			return '';
		}

		return (string) $var;

	}

	/**
	 * Retrieves default loop filter for post type.
	 *
	 * @return array{'WP_POST_EQUALS_POST_TYPE':mixed[]}
	 */
	public static function get_default_loop_filter_fields_post() {

		return array(
			'WP_POST_EQUALS_POST_TYPE' => array(
				'type'     => 'select',
				'value'    => 'post',
				'readable' => 'Posts',
				'backup'   => array(
					'label'                    => 'Post type',
					'supports_custom_value'    => true,
					'supports_multiple_values' => false,
				),
			),
		);

	}

	/**
	 * Retrieves default loop filter for post type.
	 *
	 * @return string[]
	 */
	public static function get_default_loop_filter_backup_post() {

		return array(
			'integration_name' => 'WordPress',
			'sentence'         => 'Post type is {{a specific post type:WP_POST_EQUALS_POST_TYPE}}',
			'sentence_html'    => '&lt;span class=&quot;sentence sentence--standard&quot;&gt;&lt;span class=&quot;sentence-plain&quot;&gt;A post is &lt;/span&gt;&lt;span class=&quot;sentence-pill&quot; size=&quot;small&quot; filled=&quot;&quot;&gt;&lt;span class=&quot;sentence-pill-label&quot;&gt;Post type: &lt;/span&gt;&lt;span class=&quot;sentence-pill-value&quot;&gt;Posts&lt;/span&gt;&lt;/span&gt;&lt;/span&gt;',
		);

	}

	/**
	 * Retrieves default loop filter for user type.
	 *
	 * @return array{'CRITERIA':mixed[],'WP_USER_HAS_ROLE':mixed[]}
	 */
	public static function get_default_loop_filter_fields_user() {

		return array(
			'CRITERIA'         => array(
				'type'     => 'select',
				'value'    => 'does-not-have',
				'readable' => 'does not have',
				'backup'   => array(
					'label'                    => 'Criteria',
					'supports_custom_value'    => false,
					'supports_multiple_values' => false,
				),
			),
			'WP_USER_HAS_ROLE' => array(
				'type'     => 'select',
				'value'    => 'administrator',
				'readable' => 'Administrator',
				'backup'   => array(
					'label'                    => 'Role',
					'supports_custom_value'    => false,
					'supports_multiple_values' => false,
				),
			),
		);

	}

	/**
	 * Retrieves default loop filter for user type.
	 *
	 * @return string[]
	 */
	public static function get_default_loop_filter_backup_user() {
		return array(
			'integration_name' => 'WordPress',
			'sentence'         => 'User {{has:CRITERIA}} {{a specific role:WP_USER_HAS_ROLE}}',
			'sentence_html'    =>
			'&lt;span class=&quot;sentence sentence--standard&quot;&gt;&lt;span class=&quot;sentence-plain&quot;&gt;User &lt;/span&gt;&lt;span class=&quot;sentence-pill&quot; size=&quot;small&quot; filled=&quot;&quot;&gt;&lt;span class=&quot;sentence-pill-label&quot;&gt;Criteria: &lt;/span&gt;&lt;span class=&quot;sentence-pill-value&quot;&gt;does not have&lt;/span&gt;&lt;/span&gt;&lt;span class=&quot;sentence-plain&quot;&gt;&lt;/span&gt;&lt;span class=&quot;sentence-pill&quot; size=&quot;small&quot; filled=&quot;&quot;&gt;&lt;span class=&quot;sentence-pill-label&quot;&gt;Role: &lt;/span&gt;&lt;span class=&quot;sentence-pill-value&quot;&gt;Administrator&lt;/span&gt;&lt;/span&gt;&lt;/span&gt;',
		);
	}

	/**
	 * Retrieves default loop filter for token type.
	 *
	 * @return mixed[]
	 */
	public static function get_default_loop_filter_fields_token() {

		return array(
			'ITEM_NOT_EMPTY' => array(
				'type'     => 'select',
				'value'    => 'is_not_empty',
				'readable' => _x( 'The item is not empty', 'General', 'uncanny-automator-pro' ),
				'backup'   => array(
					'label'                    => esc_html_x( 'Condition', 'General', 'uncanny-automator-pro' ),
					'show_label_in_sentence'   => true,
					'supports_custom_value'    => false,
					'supports_multiple_values' => false,
				),
			),
		);

	}

	/**
	 * Retrieves default loop filter for token type.
	 *
	 * @return string[]
	 */
	public static function get_default_loop_filter_backup_token() {

		return array(
			'integration_name' => 'GEN',
			'sentence'         => _x( 'The item is not empty', 'General', 'uncanny-automator-pro' ),
			'sentence_html'    => '&lt;span class=&quot;sentence sentence--standard&quot;&gt;&lt;span class=&quot;sentence-plain&quot;&gt;The item is not empty&lt;/span&gt;&lt;/span&gt;',
		);

	}

}
