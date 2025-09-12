<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model;

use WP_Error;

/**
 * Represents the uap_entries_items table where properties are column names.
 *
 * @since 5.0
 */
class Loop_Entry_Item_Model {

	/**
	 * @var int $id
	 */
	protected $id = null;

	/**
	 * @var int $user_id
	 */
	protected $user_id = null;

	/**
	 * @var int $entity_id
	 */
	protected $entity_id = null;

	/**
	 * @var int $action_id
	 */
	protected $action_id = 0;

	/**
	 * @var string $filter_id
	 */
	protected $filter_id = null;

	/**
	 * @var string $status
	 */
	protected $status = '';

	/**
	 * @var string $error_message
	 */
	protected $error_message = '';

	/**
	 * @var int $recipe_id
	 */
	protected $recipe_id = null;

	/**
	 * @var int $recipe_log_id
	 */
	protected $recipe_log_id = null;

	/**
	 * @var int $recipe_run_number
	 */
	protected $recipe_run_number = null;

	/**
	 * @var string $action_data
	 */
	protected $action_data = '';

	/**
	 * @var string $action_tokens
	 */
	protected $action_tokens = '';

	/**
	 * @param mixed[] $args
	 *
	 * @return self
	 */
	public function hydrate_from_array( $args ) {

		$serialized_action_data = $args['action_data'];

		if ( ! is_string( $serialized_action_data ) || ! is_serialized( $serialized_action_data ) ) {
			$serialized_action_data = '';
		}

		$this->set_id( absint( $args['ID'] ) );
		$this->set_user( absint( $args['user_id'] ) );
		$this->set_entity_id( absint( $args['entity_id'] ) );
		$this->set_action_id( absint( $args['action_id'] ) );
		$this->set_status( $this->to_string( $args['status'] ) );
		$this->set_filter_id( $this->to_string( $args['filter_id'] ) );
		$this->set_recipe_id( absint( $args['recipe_id'] ) );
		$this->set_error_message( $this->to_string( $args['error_message'] ) );
		$this->set_recipe_log_id( absint( $args['recipe_log_id'] ) );
		$this->set_recipe_run_number( absint( $args['recipe_run_number'] ) );
		$this->set_action_data( $serialized_action_data );
		$this->set_action_tokens( $this->to_string( $args['action_tokens'] ) );

		return $this;

	}

	/**
	 * @param mixed $data
	 *
	 * @return string
	 */
	private function to_string( $data ) {

		if ( is_scalar( $data ) ) {
			return (string) $data;
		}

		return '';
	}

	/**
	 * @param int $id
	 *
	 * @return void
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function set_user( $user_id = 0 ) {
		$this->user_id = absint( $user_id );
	}

	/**
	 * @param int $entity_id
	 *
	 * @return void
	 */
	public function set_entity_id( $entity_id ) {
		$this->entity_id = $entity_id;
	}

	/**
	 * @param int $action_id
	 *
	 * @return void
	 */
	public function set_action_id( $action_id ) {
		$this->action_id = absint( $action_id );
	}

	/**
	 * @param string $status
	 *
	 * @return void
	 */
	public function set_status( $status ) {
		$this->status = $status;
	}

	/**
	 * @param string $filter_id
	 *
	 * @return void
	 */
	public function set_filter_id( $filter_id ) {
		$this->filter_id = $filter_id;
	}

	/**
	 * @param string $error_message
	 *
	 * @return void
	 */
	public function set_error_message( $error_message = '' ) {
		$this->error_message = $error_message;
	}

	/**
	 * @param int $recipe_id
	 *
	 * @return void
	 */
	public function set_recipe_id( $recipe_id ) {
		$this->recipe_id = $recipe_id;
	}

	/**
	 * @param int $recipe_log_id
	 *
	 * @return void
	 */
	public function set_recipe_log_id( $recipe_log_id ) {
		$this->recipe_log_id = $recipe_log_id;
	}

	/**
	 * @param int $recipe_run_number
	 *
	 * @return void
	 */
	public function set_recipe_run_number( $recipe_run_number ) {
		$this->recipe_run_number = $recipe_run_number;
	}

	/**
	 * Invokes '_doing_it_wrong' if param #1 is not a serialized string.
	 *
	 * @param string $serialized_action_data Must be serialized.
	 *
	 * @return void|WP_Error
	 */
	public function set_action_data( $serialized_action_data ) {

		if ( ! is_string( $serialized_action_data ) || ! is_serialized( $serialized_action_data, true ) ) {
			$message = 'Loop_Entry_Item_Model::set_action_data method param 1 must be a serialized string';
			_doing_it_wrong( 'Loop_Entry_Item_Model::set_action_data', esc_html( $message ), '5.0' );
			return new WP_Error( $message, '500' );
		}

		$this->action_data = $serialized_action_data;

	}

	/**
	 * @param string $action_tokens
	 *
	 * @return void
	 */
	public function set_action_tokens( $action_tokens ) {
		$this->action_tokens = $action_tokens;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * @return int
	 */
	public function get_entity_id() {
		return $this->entity_id;
	}

	/**
	 * @return int
	 */
	public function get_action_id() {
		return $this->action_id;
	}

	/**
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function get_filter_id() {
		return $this->filter_id;
	}

	/**
	 * @return string
	 */
	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * @return int
	 */
	public function get_recipe_id() {
		return $this->recipe_id;
	}

	/**
	 * @return int
	 */
	public function get_recipe_log_id() {
		return $this->recipe_log_id;
	}

	/**
	 * @return int
	 */
	public function get_recipe_run_number() {
		return $this->recipe_run_number;
	}

	/**
	 * @return string
	 */
	public function get_action_data() {
		return $this->action_data;
	}

	/**
	 * @return string
	 */
	public function get_action_tokens() {
		return $this->action_tokens;
	}

}
