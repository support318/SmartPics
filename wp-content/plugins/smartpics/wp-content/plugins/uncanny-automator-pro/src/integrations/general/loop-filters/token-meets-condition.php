<?php
//phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;
use Uncanny_Automator_Pro\Loops\Loop\Entity_Factory;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

/**
 * Loop filter - {{A loop token}} meets {{a condition}}
 *
 * Class TOKEN_MEETS_CONDITION
 *
 * @package Uncanny_Automator_Pro
 */
class TOKEN_MEETS_CONDITION extends Loop_Filter {

	/**
	 * @var string
	 */
	const META = 'TOKEN_MEETS_CONDITION';

	/**
	 * Sets up the filter.
	 *
	 * @return void
	 */
	public function setup() {

		$static_sentence = esc_html_x(
			'{{The item}} meets a condition',
			'Filter sentence',
			'uncanny-automator-pro'
		);

		$dynamic_sentence = sprintf(
			/* translators: Loop filter sentence */
			esc_html_x(
				'{{The item:%1$s}} {{matches:%2$s}} {{a value:%3$s}}',
				'Filter sentence',
				'uncanny-automator-pro'
			),
			self::META,
			'OPERATOR',
			'VALUE'
		);

		$this->set_integration( 'GEN' );
		$this->set_meta( self::META );
		$this->set_sentence( $static_sentence );
		$this->set_sentence_readable( $dynamic_sentence );
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'get_items' ) );
		$this->set_loop_type( Entity_Factory::TYPE_TOKEN );
		$this->register_hooks();

	}

	/**
	 * Conditionally loads the filter if all dependencies are active or not.
	 *
	 * @return bool Returns true if constant AUTOMATOR_PLUGIN_VERSION is greater than or equals 6.0. Returns false otherwise.
	 */
	protected function is_dependency_active() {

		return defined( 'AUTOMATOR_PLUGIN_VERSION' )
			&& is_string( AUTOMATOR_PLUGIN_VERSION )
			&& version_compare( AUTOMATOR_PLUGIN_VERSION, '6.0', '>=' );

	}

	/**
	 * Registers the endpoint for the dropdown.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_automator_token_loop_token_meets_condition_tokens', array( $this, 'render_child_tokens' ) );
	}

	/**
	 * Callback function to 'wp_ajax_automator_token_loop_token_meets_condition_tokens'.
	 *
	 * @return void
	 */
	public function render_child_tokens() {

		$recipe_id     = automator_filter_input( 'recipe_id', INPUT_POST );
		$loop_id       = automator_filter_input( 'parent_id', INPUT_POST );
		$recipe_object = Automator()->get_recipe_object( $recipe_id, ARRAY_A );
		$action_items  = $recipe_object['actions']['items'] ?? array();

		$recipe_loops = array();

		foreach ( (array) $action_items as $action_item ) {
			if ( isset( $action_item['type'] ) && 'loop' === $action_item['type'] ) {
				$recipe_loops[] = $action_item;
			}
		}

		$selected_loop = null;

		foreach ( $recipe_loops as $recipe_loop ) {
			if ( isset( $recipe_loop['id'] ) && absint( $loop_id ) === absint( $recipe_loop['id'] ) ) {
				$selected_loop = $recipe_loop;
			}
		}

		$child_tokens = $selected_loop['tokens'] ?? array();

		$options = array();
		$success = true;

		foreach ( $child_tokens as $child_token ) {

			$raw_token_parts = explode( ':', $child_token['id'] );

			if ( isset( $raw_token_parts[4] ) ) {
				$key = str_replace( '}}', '', $raw_token_parts[5] );
			}

			$options[] = array(
				'text'  => $child_token['name'],
				'value' => $key,
			);
		}

		$response = array(
			'success' => $success,
			'options' => $options,
		);

		wp_send_json( $response );

	}

	/**
	 * Load options.
	 *
	 * @return mixed[]
	 */
	public function load_options() {

		$token = array(
			'option_code'            => $this->get_meta(),
			'type'                   => 'select',
			'label'                  => esc_html_x( 'Item', 'General', 'uncanny-automator-pro' ),
			'options_show_id'        => false,
			'ajax'                   => array(
				'endpoint' => 'automator_token_loop_token_meets_condition_tokens',
				'event'    => 'on_load',
			),
			'show_label_in_sentence' => false,
		);

		$operator = array(
			'option_code'            => 'OPERATOR',
			'type'                   => 'select',
			'supports_custom_value'  => false,
			'label'                  => esc_html_x( 'Criteria', 'General', 'uncanny-automator-pro' ),
			'options'                => self::get_choices(),
			'options_show_id'        => false,
			'show_label_in_sentence' => false,
		);

		$value = array(
			'option_code'            => 'VALUE',
			'type'                   => 'text',
			'label'                  => esc_html_x( 'Value', 'General', 'uncanny-automator-pro' ),
			'show_label_in_sentence' => false,
		);

		return array(
			$this->get_meta() => array(
				$token,
				$operator,
				$value,
			),
		);

	}

	/**
	 * @param array{ITEM_NOT_EMPTY:string} $fields
	 *
	 * @return array{mixed[]}
	 */
	public function get_items( $fields ) {

		$key              = $fields[ $this->get_meta() ] ?? '';
		$operator         = $fields['OPERATOR'] ?? '';
		$value_to_compare = $fields['VALUE'] ?? '';

		$loopable_items_array          = $this->get_loopable_items();
		$filtered_loopable_items_array = array();

		foreach ( (array) $loopable_items_array as $item ) {

			$value = $item[ $key ] ?? '';

			if ( $this->compare_values( $operator, (string) $value, (string) $value_to_compare ) ) {
				$filtered_loopable_items_array[] = $item;
			}
		}

		return $filtered_loopable_items_array;

	}

	/**
	 * Matches the condition.
	 *
	 * @param string $value
	 * @param string $operator
	 * @param string $value_to_compare
	 *
	 * @return bool
	 *
	 * @throws Loops_Exception
	 */
	public function compare_values( $operator, $value, $value_to_compare ) {

		// Check if both values are numeric.
		if ( is_numeric( $value ) && is_numeric( $value_to_compare ) ) {
			$value            = (float) $value;
			$value_to_compare = (float) $value_to_compare;
		}

		// If either value is non-numeric, preserve original types as strings.
		if ( ! is_numeric( $value ) || ! is_numeric( $value_to_compare ) ) {
			$value            = (string) $value;
			$value_to_compare = (string) $value_to_compare;
		}

		// Perform the comparison based on the operator.
		switch ( $operator ) {
			case 'is':
				return $value === $value_to_compare;
			case 'is-not':
				return $value !== $value_to_compare;
			case 'is-greater-than':
				return $value > $value_to_compare;
			case 'is-greater-than-or-equal-to':
				return $value >= $value_to_compare;
			case 'is-less-than':
				return $value < $value_to_compare;
			case 'is-less-than-or-equal-to':
				return $value <= $value_to_compare;
			case 'contains':
				return strpos( (string) $value, (string) $value_to_compare ) !== false;
			case 'does-not-contain':
				return strpos( (string) $value, (string) $value_to_compare ) === false;
			case 'starts-with':
				return strpos( (string) $value, (string) $value_to_compare ) === 0;
			case 'does-not-starts-with':
				return strpos( (string) $value, (string) $value_to_compare ) !== 0;
			case 'ends-with':
				return substr( (string) $value, -strlen( (string) $value_to_compare ) ) === (string) $value_to_compare;
			case 'does-not-ends-with':
				return substr( (string) $value, -strlen( (string) $value_to_compare ) ) !== (string) $value_to_compare;
			default:
				return $value === $value_to_compare;
		}
	}




	/**
	 * Returns the choices for the 'OPERATOR'.
	 *
	 * @return array{text: string, value: string}[]
	 */
	public static function get_choices() {
		return array(
			array(
				'text'  => _x( 'is', 'General', 'uncanny-automator-pro' ),
				'value' => 'is',
			),
			array(
				'text'  => _x( 'is not', 'General', 'uncanny-automator-pro' ),
				'value' => 'is-not',
			),
			array(
				'text'  => _x( 'contains', 'General', 'uncanny-automator-pro' ),
				'value' => 'contains',
			),
			array(
				'text'  => _x( 'does not contain', 'General', 'uncanny-automator-pro' ),
				'value' => 'does-not-contain',
			),
			array(
				'text'  => _x( 'is greater than', 'General', 'uncanny-automator-pro' ),
				'value' => 'is-greater-than',
			),
			array(
				'text'  => _x( 'is greater than or equal to', 'General', 'uncanny-automator-pro' ),
				'value' => 'is-greater-than-or-equal-to',
			),
			array(
				'text'  => _x( 'is less than', 'General', 'uncanny-automator-pro' ),
				'value' => 'is-less-than',
			),
			array(
				'text'  => _x( 'is less than or equal to', 'General', 'uncanny-automator-pro' ),
				'value' => 'is-less-than-or-equal-to',
			),
			array(
				'text'  => _x( 'starts with', 'General', 'uncanny-automator-pro' ),
				'value' => 'starts-with',
			),
			array(
				'text'  => _x( 'does not starts with', 'General', 'uncanny-automator-pro' ),
				'value' => 'does-not-starts-with',
			),
			array(
				'text'  => _x( 'ends with', 'General', 'uncanny-automator-pro' ),
				'value' => 'ends-with',
			),
			array(
				'text'  => _x( 'does not ends with', 'General', 'uncanny-automator-pro' ),
				'value' => 'does-not-ends-with',
			),
		);
	}
}
