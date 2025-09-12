<?php
namespace Uncanny_Automator_Pro\Loops\Filter\Base;

use Exception;
use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;
use WP_Error;

/**
 * Abstract class Loop_Filter
 *
 * @since 5.0 - Initial release.
 *
 * @package Uncanny_Automator_Pro\Loops\Filter\Base
 */
abstract class Loop_Filter {

	/**
	 * Prevents the child classes from accidentally overwriting this property.
	 *
	 * @var mixed[]
	 */
	private $fields = array();

	/**
	 * Prevents child class from accidentally overwriting this property.
	 *
	 * @var int[]
	 */
	private $users = array();

	/**
	 * The parsed fields.
	 *
	 * @var mixed[]
	 */
	private $parsed_fields = array();

	/**
	 * The unique filter meta.
	 *
	 * @var string Defaults to null.
	 */
	protected $filter_meta = null;

	/**
	 * The integration code.
	 *
	 * @var string Defaults to null.
	 */
	protected $integration_code = null;

	/**
	 * The filter sentence.
	 *
	 * @var string
	 */
	protected $sentence = '';

	/**
	 * The dynamic sentence.
	 *
	 * @var string
	 */
	protected $sentence_readable = '';

	/**
	 * The users fetching function callback.
	 *
	 * @var callable
	 */
	protected $entities_fetching_func = null;

	/**
	 * The run argumenets.
	 *
	 * @var mixed[]
	 */
	protected $run_args = array();

	/**
	 * The loop.
	 *
	 * @var int
	 */
	protected $loop_id = 0;

	/**
	 * The loop type. Defaults to "users".
	 *
	 * @var string
	 */
	protected $loop_type = 'users';

	/**
	 * External class resolver. Allow 3rd-party to port their own filter.
	 *
	 * @var array{meta:string,class_name:string}
	 */
	protected $external_class_resolver = array(
		'meta'       => '',
		'class_name' => '',
	);

	/**
	 * Abstract method setup.
	 *
	 * Developers needs to overwrite this method to setup the filter object.
	 *
	 * @return void
	 */
	abstract public function setup();

	/**
	 * Setups the filter.
	 *
	 * @param int $filter_id Defaults to null.
	 * @param mixed[] $args Defaults to empty array.
	 * @param int|null $loop_id The id of the loop.
	 *
	 * @return void
	 */
	final public function __construct( $filter_id = null, $args = array(), $loop_id = null ) {

		if ( ! empty( $filter_id ) && ! empty( $args ) ) {
			$this->parse_fields( $filter_id, $args, $loop_id );
		}

		// Only load the filter if the dependency is loaded.
		if ( $this->is_dependency_active() ) { /** @phpstan-ignore-line treatPhpDocTypesAsCertain */
			try {
				// Setups the integration. Basically prepares the integration properties.
				$this->setup();
				// Register the loop filter into the global registry of filters for UI consumption.
				automator_pro_loop_filters()->register_filter(
					array(
						'integration'       => $this->get_integration(),
						'loop_type'         => $this->get_loop_type(),
						'meta'              => $this->get_meta(),
						'sentence'          => $this->get_sentence(),
						'sentence_readable' => $this->get_sentence_readable(),
						'fields'            => $this->get_fields(),
					)
				);
			} catch ( Exception $e ) {
				_doing_it_wrong( esc_html( get_class( $this ) ), esc_html( $e->getMessage() ), '5.2' );
			}
		}

	}

	/**
	 * Sets the run args.
	 *
	 * @param mixed[] $run_args
	 *
	 * @return void
	 */
	public function set_run_args( $run_args ) {
		$this->run_args = $run_args;
	}

	/**
	 * Get the run args.
	 *
	 * @return mixed[]
	 */
	public function get_run_args() {
		return $this->run_args;
	}

	/**
	 * Sets the loop id.
	 *
	 * @param int $loop_id
	 *
	 * @return void
	 */
	public function set_loop_id( $loop_id ) {
		$this->loop_id = $loop_id;
	}

	/**
	 * Get the loop id.
	 *
	 * @return int
	 */
	public function get_loop_id() {
		return $this->loop_id;
	}
	/**
	 * Automatically overwrites the class mapping for loop filter driver.
	 *
	 * @param string $class_name The fully qualified class name (e.g. self::class).
	 * @param string $meta The loop filter meta.
	 *
	 * @return void
	 */
	public function load_as_external( $class_name, $meta ) {

		$this->external_class_resolver = array(
			'class_name' => $class_name,
			'meta'       => $meta,
		);

		add_filter( 'uncanny_automator_pro_loop_filter_class', array( $this, 'apply_external_namespace' ), 10, 2 );

	}

	/**
	 * Callback method to 'uncanny_automator_pro_loop_filter_class'.
	 *
	 * @param string $class
	 * @param string[] $args
	 *
	 * @return string The fully qualified class name.
	 */
	public function apply_external_namespace( $class, $args ) {

		if ( 'uncanny_automator_pro_loop_filter_class' !== current_filter() ) {

			_doing_it_wrong(
				esc_html( self::class . '::apply_external_namespace' ),
				'apply_external_namespace method should not be called directly.',
				'5.3'
			);

			return $class;
		}

		if ( $this->external_class_resolver['meta'] === $args['filter'] ) {
			return $this->external_class_resolver['class_name'];
		}

		return $class;

	}

	/**
	 * Determines whether one or more external dependencies are loaded.
	 *
	 * @return true By default this will return true.
	 */
	protected function is_dependency_active() {
		$is_active = true;
		
		/**
		 * Filter to override the dependency active status for a specific loop filter.
		 *
		 * @param bool   $is_active The dependency active status.
		 * @param string $class_name The filter class name.
		 * @param object $filter The filter instance.
		 */
		return apply_filters( 'automator_pro_loop_filter_is_dependency_active', $is_active, get_class( $this ), $this );
	}

	/**
	 * Parses the fields value before they are read by set_users callable parameter.
	 *
	 * @param int $filter_id
	 * @param mixed[] $args The process args.
	 * @param int|null $loop_id The loop ID.
	 *
	 * @return self
	 */
	public function parse_fields( $filter_id = null, $args = null, $loop_id = null ) {

		if ( ! is_array( $args ) ) {
			return $this;
		}

		if ( ! isset( $args['recipe_id'] ) ) {
			return $this;
		}

		if ( ! isset( $args['user_id'] ) ) {
			return $this;
		}

		$fields                 = get_post_meta( absint( $filter_id ), 'fields', true );
		$extracted_tokens_value = $this->get_extracted_tokens_value( absint( $loop_id ) );

		$parse_pairs = array();

		// Parse the loopable token.
		if ( ! empty( $extracted_tokens_value ) ) {

			// Parse the text.
			$loopable_tokens = Automator()->parse->text(
				$extracted_tokens_value,
				$args['recipe_id'],
				$args['user_id'],
				$args
			);

			$parse_pairs['_*loopable_tokens'] = $loopable_tokens;

			if ( empty( $loopable_tokens ) ) {
				// Dont process the loop if the parsing returns empty result already.
				throw new Exception( 'The token "' . $extracted_tokens_value . '" did not return any results.', Automator_Status::COMPLETED_WITH_NOTICE );
			}
		}

		if ( ! is_string( $fields ) ) {
			$fields = '';
		}

		$fields_arr = (array) json_decode( $fields, true );

		foreach ( $fields_arr as $code => $field ) {

			if ( ! is_array( $field ) ) {
				continue; // Skip
			}

			$parse_pairs[ $code ] = Automator()->parse->text(
				$field['value'],
				$args['recipe_id'],
				$args['user_id'],
				$args
			);

			$parse_pairs['raw'][ $code ] = $field['value'];

		}

		$this->parsed_fields = $parse_pairs;

		return $this;

	}

	/**
	 * Extracts the token value of the loopable expression from loop id.
	 *
	 * @param int $loop_id
	 *
	 * @return string
	 */
	public function get_extracted_tokens_value( int $loop_id ) {

		$extracted_tokens       = array();
		$extracted_tokens_value = '';
		$loopable_expression    = get_post_meta( absint( $loop_id ), 'iterable_expression', true );

		if ( is_array( $loopable_expression ) && isset( $loopable_expression['fields'] ) ) {
			// Decode the loopable fields.
			$extracted_tokens = json_decode( $loopable_expression['fields'], true );
			// Assign the token value to the extracted token value.
			if ( is_array( $extracted_tokens ) && isset( $extracted_tokens['TOKEN']['value'] ) ) {
				$extracted_tokens_value = $extracted_tokens['TOKEN']['value'];
			}
		}

		if ( ! is_string( $extracted_tokens_value ) ) {
			return '';
		}

		return $extracted_tokens_value;

	}

	/**
	 * Each filters belong to a specific integration.
	 *
	 * @param string $integration_code
	 *
	 * @return void
	 */
	public function set_integration( $integration_code ) {

		if ( empty( $integration_code ) ) {
			throw new \Exception( "Loop filter's integration code should not be empty", 400 );
		}

		$this->integration_code = $integration_code;

	}

	/**
	 * Sets the filter's sentence. Sentence are displayed during filters selection.
	 *
	 * @param string $sentence
	 *
	 * @return void
	 */
	public function set_sentence( $sentence = '' ) {

		if ( empty( $sentence ) ) {
			throw new \Exception( "Loop filter's sentence should not be empty", 400 );
		}

		$this->sentence = $sentence;
	}

	/**
	 * Sets the filter's readable sentence. Readable sentence are displayed when the field values are saved.
	 *
	 * @param string $sentence_readable
	 *
	 * @return void
	 */
	public function set_sentence_readable( $sentence_readable = '' ) {

		if ( empty( $sentence_readable ) ) {
			throw new \Exception( "Loop filter's readable sentence should not be empty", 400 );
		}

		$this->sentence_readable = $sentence_readable;
	}

	/**
	 * Sets the meta for a specific filter.
	 *
	 * @param string $filter_meta
	 *
	 * @return void
	 */
	public function set_meta( $filter_meta ) {

		if ( empty( $filter_meta ) ) {
			throw new \Exception( "Loop filter's meta should not be empty", 400 );
		}

		$this->filter_meta = $filter_meta;

	}

	/**
	 * @param callable $fields_callback
	 *
	 * @return void
	 */
	public function set_fields( callable $fields_callback ) {

		$fields = call_user_func_array( $fields_callback, array() );

		$this->fields = Automator()->utilities->keep_order_of_options( (array) $fields );

	}

	/**
	 * @param callable $entities_fetching_func
	 *
	 * @return true|WP_Error
	 */
	public function set_entities( callable $entities_fetching_func ) {

		if ( ! is_callable( $entities_fetching_func ) ) {
			return new WP_Error(
				421,
				'Argument 1 of the method set_entities expects a callable parameter.'
			);
		}

		$this->entities_fetching_func = $entities_fetching_func;

		return true;

	}

	/**
	 * @param string $loop_type
	 *
	 * @return true|WP_Error
	 */
	public function set_loop_type( $loop_type = 'users' ) {

		if ( empty( $loop_type ) ) {
			return new WP_Error(
				421,
				'Loop type must not be empty'
			);
		}

		$this->loop_type = $loop_type;

		return true;

	}

	/**
	 * @return string
	 */
	public function get_sentence() {
		return $this->sentence;
	}

	/**
	 * @return string
	 */
	public function get_sentence_readable() {
		return $this->sentence_readable;
	}

	/**
	 * @return string
	 */
	public function get_meta() {
		return $this->filter_meta;
	}

	/**
	 * @return mixed[]
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * @return int[]|WP_Error Returns an array of user IDs. Otherwise, returns WP_Error
	 */
	public function get_entities() {

		$result = call_user_func_array(
			$this->entities_fetching_func,
			array(
				'fields' => $this->parsed_fields,
			)
		);

		if ( ! is_array( $result ) ) {
			return new WP_Error(
				421,
				'Callback argument to the set_users method must return an array'
			);
		}

		$this->users = $result;

		return $this->users;

	}

	/**
	 * @return string
	 */
	public function get_integration() {
		return $this->integration_code;
	}

	/**
	 * @return string
	 */
	public function get_loop_type() {
		return $this->loop_type;
	}

	/**
	 * Returns the loopable items.
	 *
	 * @return array{mixed[]}
	 */
	public function get_loopable_items() {

		$loopable_items_array = array();

		if ( isset( $this->parsed_fields['_*loopable_tokens'] ) && is_string( $this->parsed_fields['_*loopable_tokens'] ) ) {

			$loopable_token_value = $this->parsed_fields['_*loopable_tokens'];

			// Do not decode falsy values.
			if ( empty( $loopable_token_value ) ) {
				return apply_filters( 'automator_loops_filter_get_loopable_items', $loopable_items_array, $this );
			}

			// Make sure the JSON does not contain any breaking characters.
			$loopable_token_value = str_replace( array( "\r", "\n" ), array( '\\r', '\\n' ), $loopable_token_value );

			// Check and fix invalid backslashes.
			$loopable_token_value = preg_replace_callback(
				'/(?<!\\\\)\\\\(?![\\\\\/"bfnrtu])/',
				function ( $matches ) {
					return '\\\\'; // Replace single backslash with double backslash
				},
				$loopable_token_value
			);

			// Decode JSON string to an associative array.
			$loopable_items_array = json_decode( $loopable_token_value, true );

		}

		// Check for JSON decode errors.
		if ( json_last_error() !== JSON_ERROR_NONE ) {

			$json_last_error_msg = json_last_error_msg();

			$log_message = array(
				'$loopable_token_value(raw-from-parse-fields)' => $this->parsed_fields['_*loopable_tokens'],
				'$loopable_token_value(original-string-after-replacements)' => $loopable_token_value,
				'$loopable_items_array(decoded-array)' => $loopable_items_array,
				'json_last_error_msg()'                => $json_last_error_msg,

			);

			automator_log( $log_message, 'Returning the loopable items error.', true, 'loop-error' );

			throw new Loops_Exception( 'Failed to decode JSON: ' . $json_last_error_msg );

		}

		// Handle falsy statements.
		if ( empty( $loopable_items_array ) ) {
			$loopable_items_array = array();
		}

		return apply_filters( 'automator_loops_filter_get_loopable_items', $loopable_items_array, $this );

	}

}
