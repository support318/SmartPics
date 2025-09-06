<?php
namespace Uncanny_Automator_Pro\Loops\Recipe;

trait Trait_Process {

	/**
	 * @var string $flow
	 */
	protected $flow;

	/**
	 * @var int $recipe_id
	 */
	protected $recipe_id;

	/**
	 * @var int $user_id
	 */
	protected $user_id;

	/**
	 * @var int $recipe_log_id
	 */
	protected $recipe_log_id;

	/**
	 * @var mixed[] $process_args
	 */
	protected $process_args;

	/**
	 * @var string $flow_type
	 */
	protected $flow_type;

	/**
	 * Get the value of flow
	 *
	 * @return string
	 */
	public function get_flow() {
		return $this->flow_type;
	}

	/**
	 * Set the value of flow
	 *
	 * @param string $flow
	 *
	 * @return self
	 */
	public function set_flow( $flow ) {
		$this->flow = $flow;

		return $this;
	}

	/**
	 * Get the value of recipe_id
	 *
	 * @return int
	 */
	public function get_recipe_id() {

		return $this->recipe_id;

	}

	/**
	 * Set the value of recipe_id
	 *
	 * @param int $recipe_id;
	 *
	 * @return  self
	 */
	public function set_recipe_id( $recipe_id ) {
		$this->recipe_id = $recipe_id;
		return $this;
	}

	/**
	 * Get the value of user_id
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Set the value of user_id
	 *
	 * @param int $user_id
	 *
	 * @return self
	 */
	public function set_user_id( $user_id ) {

		$this->user_id = $user_id;

		return $this;
	}

	/**
	 * Get the value of recipe_log_id
	 *
	 * @return int
	 */
	public function get_recipe_log_id() {
		return $this->recipe_log_id;
	}

	/**
	 * Set the value of recipe_log_id
	 *
	 * @param int $recipe_log_id
	 *
	 * @return self
	 */
	public function set_recipe_log_id( $recipe_log_id ) {
		$this->recipe_log_id = $recipe_log_id;

		return $this;
	}

	/**
	 * Get the value of process_args
	 *
	 * @return mixed[]
	 */
	public function get_process_args() {
		return $this->process_args;
	}

	/**
	 * Set the value of process_args
	 *
	 * @param mixed[] $process_args
	 *
	 * @return self
	 */
	public function set_process_args( $process_args ) {

		$this->process_args = $process_args;

		return $this;
	}
}
