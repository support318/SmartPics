<?php
namespace Uncanny_Automator_Pro\Loops\Filter;

final class Registry {

	/**
	 * The array of filters.
	 *
	 * @var mixed[]
	 */
	protected $filters = array();

	/**
	 * Retrieves the instance of the object.
	 *
	 * @return self
	 */
	public static function get_instance() {

		static $instance = null;

		if ( null === $instance ) {
			$instance = new static();
		}

		return $instance;

	}

	/**
	 * Registers a specific filter into memory.
	 *
	 * @param mixed[] $filter
	 *
	 * @return void
	 */
	public function register_filter( $filter = array() ) {

		if ( empty( $filter['integration'] ) ) {
			throw new \Exception( 'You are trying to register a loop filter without specifying the integration code', 400 );
		}

		if ( empty( $filter['meta'] ) ) {
			throw new \Exception( 'You are trying to register a loop filter without specifying the filter meta. ', 400 );
		}

		$integration = $filter['integration'];

		$meta = $filter['meta'];

		$this->filters[ $integration ][ $meta ] = $filter; //@phpstan-ignore-line Cannot access offset mixed on mixed.

	}

	/**
	 * Retrieves a specific the process transient.
	 *
	 * @param string $process_transient_key
	 *
	 * @return array( items:mixed[], args:mixed[] loop_item:array( id:int, filter_id:string, recipe_id:int, recipe_log_id:int, run_number:int ) )
	 *
	 * @phpstan-ignore-next-line
	 */
	public static function get_process_transient( $process_transient_key ) {

		return wp_parse_args(
			(array) get_transient( $process_transient_key ),
			array(
				'items'     => array(),
				'args'      => array(),
				'loop_item' => array(),
			)
		);

	}

	/**
	 * Retrieves the filters.
	 *
	 * @return mixed[]
	 */
	public function get_filters() {

		return $this->filters;

	}

	/**
	 * Prevents constructs.
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Prevents cloning.
	 *
	 * @return void
	 */
	protected function __clone() {}

	/**
	 * Prevents serializations.
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function __wakeup() {

		throw new \Exception( 'Cannot unserialize a singleton.' );

	}

}
