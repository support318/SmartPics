<?php
// phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound
// phpcs:disable Squiz.ControlStructures.ControlSignature.SpaceAfterCloseBrace

namespace Uncanny_Automator_Pro\Db_Query\Action;

use Exception;
use Uncanny_Automator;
use Uncanny_Automator\Recipe\Log_Properties;
use Uncanny_Automator_Pro\Db_Query_Helpers;

/**
 * Class SELECT_QUERY_RUN
 *
 * @package Uncanny_Automator
 */
class Select_Query_Run_Action extends Uncanny_Automator\Recipe\Action {

	use Log_Properties;

	/**
	 * The operators.
	 *
	 * @var string[][]
	 */
	protected $operators = array(
		'='                   => '=',
		'>'                   => '>',
		'<'                   => '<',
		'<='                  => '<=',
		'>='                  => '>=',
		'<>'                  => 'IS NOT',
		'like'                => 'LIKE',
		'like_token_both'     => 'LIKE %..%',
		'not_like'            => 'NOT LIKE',
		'not_like_token_both' => 'NOT LIKE %..%',
		'in'                  => 'IN (...)',
		'not_in'              => 'NOT IN (...)',
		'is_empty'            => 'IS EMPTY',
		'is_not_empty'        => 'IS NOT EMPTY',
		'is_null'             => 'IS NULL',
		'is_not_null'         => 'IS NOT NULL',
	);

	/**
	 * Setup Action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'DB_QUERY' );
		$this->set_action_code( 'DB_QUERY_SELECT_QUERY_RUN' );
		$this->set_action_meta( 'DB_QUERY_SELECT_QUERY_RUN_META' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/db-query/' ) );
		$this->set_requires_user( false );

		$this->set_sentence(
			sprintf(
				/* translators: Action - WordPress */
				sprintf( esc_attr_x( 'Run {{a SELECT query:%1$s}}', 'Db Query', 'uncanny-automator-pro' ), $this->get_action_meta() ),
				$this->get_action_meta()
			)
		);

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr_x( 'Run {{a SELECT query}}', 'Db Query', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );
	}

	/**
	 * Action tokens.
	 *
	 * @return array
	 */
	public function define_tokens() {

		return array(
			'OUTPUT_CSV'              => array(
				'name' => esc_html_x( 'Results (CSV)', 'DB Query', 'uncanny-automator-pro' ),
			),
			'OUTPUT_JSON'             => array(
				'name' => esc_html_x( 'Results (JSON)', 'DB Query', 'uncanny-automator-pro' ),
			),
			'OUTPUT_ARRAY_SERIALIZED' => array(
				'name' => esc_html_x( 'Results (Serialized Array)', 'DB Query', 'uncanny-automator-pro' ),
			),
		);
	}

	/**
	 * Loads available options for the Trigger.
	 *
	 * @return array The available trigger options.
	 */
	public function options() {

		$should_support_custom_value = apply_filters( 'automator_select_query_run_should_support_custom_value', false, $this );

		$columns = array(
			'input_type'               => 'select',
			'option_code'              => 'COLUMN',
			'label'                    => esc_attr_x( 'Columns', 'DB Query', 'uncanny-automator-pro' ),
			'description'              => esc_attr_x( "The columns (fields) that will be returned by the query. The data in these columns will populate the action's tokens.", 'DB Query', 'uncanny-automator-pro' ),
			'required'                 => true,
			'placeholder'              => esc_attr_x( 'Click to choose columns.', 'DB Query', 'uncanny-automator-pro' ),
			'supports_custom_value'    => $should_support_custom_value,
			'options_show_id'          => false,
			'supports_multiple_values' => true,
			'relevant_tokens'          => array(),
			'ajax'                     => array(
				'endpoint'      => 'automator_db_query_select_run_retrieve_selected_columns',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( 'TABLE' ),
			),
		);

		$table = array(
			'input_type'            => 'select',
			'option_code'           => 'TABLE',
			'relevant_tokens'       => array(),
			'label'                 => esc_attr_x( 'Table', 'DB Query', 'uncanny-automator-pro' ),
			'description'           => esc_attr_x( 'The table to fetch the data from.', 'DB Query', 'uncanny-automator-pro' ),
			'required'              => true,
			'supports_custom_value' => $should_support_custom_value,
			'options_show_id'       => false,
			'ajax'                  => array(
				'endpoint' => 'automator_db_query_select_run_retrieve_tables',
				'event'    => 'on_load',
			),
		);

		$where = array(
			'input_type'      => 'repeater',
			'relevant_tokens' => array(),
			'option_code'     => 'WHERE',
			'label'           => esc_attr_x( 'Where', 'DB Query', 'uncanny-automator-pro' ),
			'description'     => esc_html_x( "The criteria to determine which records are returned. Fields left blank will not be used. To match an empty value, use '[empty]' without the single quotes.", 'DB Query', 'uncanny-automator-pro' ),
			'required'        => true,
			'fields'          => array(
				array(
					'input_type'            => 'text',
					'option_code'           => 'WHERE_COLUMN',
					'label'                 => esc_attr_x( 'Column', 'DB Query', 'uncanny-automator-pro' ),
					'required'              => true,
					'supports_custom_value' => $should_support_custom_value,
					'relevant_tokens'       => array(),
					'read_only'             => true,
				),
				array(
					'input_type'            => 'select',
					'option_code'           => 'WHERE_OPERATOR',
					'label'                 => esc_attr_x( 'Operator', 'DB Query', 'uncanny-automator-pro' ),
					'required'              => true,
					'supports_custom_value' => $should_support_custom_value,
					'relevant_tokens'       => array(),
					'options'               => $this->get_operators(),
					'options_show_id'       => false,
				),
				array(
					'input_type'            => 'text',
					'option_code'           => 'WHERE_VALUE',
					'label'                 => esc_attr_x( 'Value', 'DB Query', 'uncanny-automator-pro' ),
					'relevant_tokens'       => array(),
					'required'              => false,
					'supports_custom_value' => $should_support_custom_value,
				),
			),
			'hide_actions'    => true,
			'ajax'            => array(
				'event'          => 'parent_fields_change',
				'endpoint'       => 'automator_db_query_select_run_retrieve_selected_columns_repeater',
				'listen_fields'  => array( 'TABLE' ),
				'mapping_column' => 'WHERE_COLUMN',
			),
		);

		$order_by = array(
			'input_type'            => 'select',
			'option_code'           => 'ORDER_BY',
			'label'                 => esc_attr_x( 'Order by', 'DB Query', 'uncanny-automator-pro' ),
			'description'           => esc_attr_x( 'Order the results by the selected column.', 'DB Query', 'uncanny-automator-pro' ),
			'supports_custom_value' => $should_support_custom_value,
			'options_show_id'       => false,
			'relevant_tokens'       => array(),
			'ajax'                  => array(
				'endpoint'      => 'automator_db_query_select_run_retrieve_selected_columns',
				'event'         => 'parent_fields_change',
				'listen_fields' => array( 'TABLE' ),
			),
		);

		$order = array(
			'input_type'      => 'select',
			'option_code'     => 'ORDER',
			'label'           => esc_attr_x( 'Order', 'DB Query', 'uncanny-automator-pro' ),
			'description'     => esc_attr_x( 'Choose whether the results will be sorted in ascending or descending order.', 'DB Query', 'uncanny-automator-pro' ),
			'options_show_id' => false,
			'relevant_tokens' => array(),
			'options'         => array(
				array(
					'text'  => esc_attr_x( 'Ascending', 'DB Query', 'uncanny-automator-pro' ),
					'value' => 'ASC',
				),
				array(
					'text'  => esc_attr_x( 'Descending', 'DB Query', 'uncanny-automator-pro' ),
					'value' => 'DESC',
				),
			),
		);

		$limit = array(
			'input_type'      => 'text',
			'option_code'     => 'LIMIT',
			'default_value'   => 1,
			'relevant_tokens' => array(),
			'label'           => esc_attr_x( 'Limit', 'DB Query', 'uncanny-automator-pro' ),
			'description'     => esc_attr_x( 'Limit the number of records returned by the query.', 'DB Query', 'uncanny-automator-pro' ),
			'required'        => true,
		);

		return array(
			$table,
			$columns,
			$where,
			$order_by,
			$order,
			$limit,
		);
	}

	/**
	 * Retrieve the operatos.
	 *
	 * @return mixed
	 */
	protected function get_operators() {

		$operators = array();

		foreach ( $this->operators as $id => $operator ) {
			$operators[] = array(
				'text'  => $operator,
				'value' => $id,
			);
		}

		return apply_filters( 'automator_db_query_operators', $operators );
	}

	/**
	 * Processes the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {

		global $wpdb;

		$table    = $parsed['TABLE'] ?? '';
		$columns  = $parsed['COLUMN'] ?? '';
		$where    = $parsed['WHERE'] ?? '';
		$limit    = $parsed['LIMIT'] ?? '';
		$order_by = $parsed['ORDER_BY'] ?? '';
		$order    = $parsed['ORDER'] ?? '';
		$order    = strtoupper( $order );

		// Only allow 'ASC' and 'DESC'.
		$order_safe = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';

		if ( absint( $limit ) > 5000 ) {
			throw new Exception( 'The maximum number of allowed rows (5000) has been exceeded. Please reduce the number of rows in your dataset by adjusting the "Limit" field and try again.' );
		}

		$columns = json_decode( $columns, true );

		if ( ! is_array( $columns ) ) {
			throw new Exception( 'The "Columns" field contains invalid JSON format: ' . esc_html( json_last_error_msg() ), 'uncanny-automator-pro' );
		}

		$where = json_decode( $where, true );

		if ( ! is_array( $where ) ) {
			throw new Exception( 'The "Where" field contains invalid JSON format: ' . esc_html( json_last_error_msg() ), 'uncanny-automator-pro' );
		}

		// Before the ORDER BY clause, validate that $order_by exists in the columns
		if ( ! empty( $order_by ) && ! in_array( $order_by, $columns, true ) ) {
			throw new Exception( 'Invalid ORDER BY column specified.' );
		}

		$stmt = 'SELECT '
			. $this->real_escape_columns( $columns )
			. ' FROM ' . esc_sql( $table )
			. $this->get_where_statement_safe( $where )
			. ' ORDER BY `' . esc_sql( $order_by ) . '` ' . $order_safe
			. ' LIMIT %d';

		$args = array(
			'statement' => $stmt,
			'columns'   => $columns,
			'table'     => $table,
			'where'     => $where,
			'limit'     => $limit,
			'order_by'  => $order_by,
			'order'     => $order_safe,
		);

		$stmt = apply_filters( 'automator_pro_db_query_select_query_run_action_statement', $stmt, $args );

		// Ignoring PHPCS since we're constructing our own safe strings.
		$query = $wpdb->prepare( $stmt, absint( $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $query ) ) {
			throw new Exception( sprintf( 'Invalid query provided. The generated query is: %s', esc_html( $stmt ) ) );
		}

		// Serialize breaks when MySQL escapes like statements. This is NOT the actual query. This is only for properties.
		$querystring = preg_replace( '/{[a-f0-9]+}/i', '%', $query );

		$props = array(
			'type'       => 'code',
			'label'      => esc_html_x( 'Query string', 'Uncanny Automator', 'uncanny-automator-pro' ),
			'value'      => $querystring,
			'attributes' => array(
				'code_language' => 'json',
			),
		);

		$this->set_log_properties( $props );

		// Ignoring PHPCS since we're constructing our own safe strings.
		$results = (array) $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $wpdb->last_error ) {

			throw new Exception(
				esc_html_x( 'Database error: ', 'Db Query', 'uncanny-automator-pro' ) . esc_html( $wpdb->last_error )
			);

		}

		$csv = Db_Query_Helpers::array_to_csv( $results );

		$tokens = array(
			'OUTPUT_CSV'              => $csv,
			'OUTPUT_ARRAY_SERIALIZED' => maybe_serialize( $results ),
			'OUTPUT_JSON'             => wp_json_encode( $results ),
		);

		$field_vals = get_post_meta( $action_data['ID'], 'COLUMN', true );

		if ( is_string( $field_vals ) && ! empty( $field_vals ) ) {
			$decoded = (array) json_decode( $field_vals );
			foreach ( $decoded as $decoded_val ) {
				// Sanitize the column name before using it
				$column_key = sanitize_key( $decoded_val );
				// Check if the column actually exists in the results
				if ( isset( $results[0][ $decoded_val ] ) ) {
					$tokens[ 'OUTPUT_' . $column_key ] = join( ', ', array_column( $results, $decoded_val ) ) ?? '';
				}
			}
		}

		$this->hydrate_tokens( $tokens );

		return true;
	}

	/**
	 * Constructs a safe sql where statement.
	 *
	 * @param string[] $key_values
	 *
	 * @return string
	 */
	protected function get_where_statement_safe( $key_values ) {

		$this->remove_empty_key_elements( $key_values, 'WHERE_VALUE' );

		$this->mysql_escape_array_values( $key_values, array( 'WHERE_COLUMN', 'WHERE_VALUE' ) );

		return $this->generate_mysql_where( $key_values );
	}

	/**
	 * Returns a valid mysql where statement where the column would be the 'WHERE_COLUMN', the operator would be the 'WHERE_OPERATOR', and the value would be 'WHERE_VALUE'.
	 *
	 * @param mixed[] $conditions FROM fields.
	 *
	 * @return string
	 */
	public function generate_mysql_where( $conditions ) {

		$where = '1=1';

		foreach ( $conditions as $condition ) {

			$column            = '`' . esc_sql( $condition['WHERE_COLUMN'] ) . '`';
			$original_operator = $condition['WHERE_OPERATOR'];
			$value             = $condition['WHERE_VALUE'];

			// Identify the operator that we need.
			$operator = self::identify_operator( $original_operator );

			// In statements are formatted differently.
			if ( in_array( $original_operator, array( 'in', 'not_in' ), true ) ) {
				$sanitized_value = $this->sanitize_in_values( $value );
				$where          .= " AND $column $operator ($sanitized_value)";
			}
			// For is null and is not null statements, we dont need the value.
			elseif ( in_array( $original_operator, array( 'is_null', 'is_not_null' ), true ) ) {
				// WHERE COLUMN ([IS NULL],[IS NOT NULL]).
				$where .= " AND $column $operator";
			}
			// For is empty and is not empty statements, we just assign an empty string.
			elseif ( in_array( $original_operator, array( 'is_empty', 'is_not_empty' ), true ) ) {
				// WHERE COLUMN ([=],[<>]) ''.
				$where .= " AND $column $operator ''";
			}
			// Otherwise, compare column value equality.
			else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found

				// Check if the value is numeric
				if ( is_numeric( $value ) ) {
					// If numeric, don't add single quotes
					$where .= " AND $column $operator " . floatval( $value );
				} elseif ( 'not_like_token_both' === $condition['WHERE_OPERATOR'] || 'like_token_both' === $condition['WHERE_OPERATOR'] ) {
						$where .= " AND $column $operator '%" . esc_sql( $value ) . "%'";
				} else {
					$where .= " AND $column $operator '" . esc_sql( $value ) . "'";
				}
			}
		}

		// Remove the leading 'AND' from the first condition.
		$where = ltrim( $where, ' AND' );

		return ' WHERE ' . $where;
	}

	/**
	 * Identify which operator to use.
	 *
	 * @param mixed $operator
	 * @return string
	 * @throws Exception
	 */
	public static function identify_operator( $operator ) {
		switch ( $operator ) {
			case '=':
			case '>=':
			case '<=':
			case '>':
			case '<':
			case '<>':
				return $operator;
			case 'like':
			case 'like_token_both':
				return 'LIKE';
			case 'not_like':
			case 'not_like_token_both':
				return 'NOT LIKE';
			case 'in':
				return 'IN';
			case 'not_in':
				return 'NOT IN';
			case 'is_null':
				return 'IS NULL';
			case 'is_not_null':
				return 'IS NOT NULL';
			case 'is_empty':
				return '=';
			case 'is_not_empty':
				return '<>';
			default:
				throw new Exception( 'Invalid operator. Operator not found.', 400 );
		}
	}

	/**
	 * Escapes specific elements of an associative array to make them safe for MySQL statements.
	 *
	 * @param mixed $array
	 * @param mixed $keys
	 * @return void
	 */
	protected function mysql_escape_array_values( &$array_of_items, $keys ) {

		global $wpdb;

		foreach ( $array_of_items as &$item ) {
			foreach ( $keys as $key ) {
				if ( isset( $item[ $key ] ) ) {
					$item[ $key ] = esc_sql( $item[ $key ] );
				}
			}
		}
	}


	/**
	 * Removes an associative array element specified by their keys.
	 *
	 * @param string[] $array
	 * @param string $key
	 *
	 * @return none $array is pass as reference.
	 */
	protected function remove_empty_key_elements( &$array_of_items, $key ) {

		foreach ( $array_of_items as $index => $item ) {
			if ( isset( $item[ $key ] ) && empty( $item[ $key ] ) ) {
				unset( $array_of_items[ $index ] );
			}
		}
	}


	/**
	 * Escapes the columns.
	 *
	 * @param string[] $columns
	 *
	 * @return string
	 */
	protected function real_escape_columns( $columns ) {

		// Escape all the columns.
		$escaped_columns = array_map(
			function ( $value ) {
				return '`' . esc_sql( $value ) . '`';
			},
			$columns
		);

		$sanitized_columns = join( ',', $escaped_columns );

		return $sanitized_columns;
	}

	/**
	 * Safely processes values for IN/NOT IN SQL clauses to prevent SQL injection.
	 *
	 * @param string $value Raw comma-separated list of values.
	 * @return string Safely formatted string for use in IN/NOT IN clauses.
	 */
	private function sanitize_in_values( $value ) {

		// Split the input by commas.
		$values_array     = array_map( 'trim', explode( ',', $value ) );
		$sanitized_values = array();

		foreach ( $values_array as $single_value ) {
			// Skip empty values.
			if ( '' === $single_value ) {
				continue;
			}

			// For numeric values.
			if ( is_numeric( $single_value ) ) {
				$sanitized_values[] = floatval( $single_value );
			} else {
				// For string values - properly escape and quote
				$sanitized_values[] = "'" . esc_sql( $single_value ) . "'";
			}
		}

		// Return safely formatted list or a default that matches no records if empty
		if ( ! empty( $sanitized_values ) ) {
			return implode( ',', $sanitized_values );
		}

		// Return a value that will match no records when the input is empty
		return "''";
	}
}
