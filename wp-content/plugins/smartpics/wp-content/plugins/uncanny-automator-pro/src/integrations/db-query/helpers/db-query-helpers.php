<?php
// phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound
namespace Uncanny_Automator_Pro;

/**
 * Class Db_Query_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Db_Query_Helpers {

	/**
	 * Utility method to fetch all tables.
	 *
	 * @return string[]
	 */
	public static function get_db_tables() {

		global $wpdb;

		return (array) $wpdb->get_results(
			// Retrieve all tables except from views.
			$wpdb->prepare(
				'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_TYPE = %s ORDER BY TABLE_NAME ASC',
				DB_NAME,
				'BASE TABLE'
			),
			ARRAY_A
		);

	}

	/**
	 * Utility method to fetch all columns.
	 *
	 * @return string[]
	 */
	public static function get_table_columns( $table_name ) {

		global $wpdb;

		return (array) $wpdb->get_results(
			// Retrieve all tables except from views.
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$table_name
			),
			ARRAY_A
		);

	}

	/**
	 * A wp_ajax callback method to field 1.
	 *
	 * @return void
	 */
	public static function retrieve_tables() {

		Automator()->utilities->verify_nonce();

		$tables = array();

		foreach ( self::get_db_tables() as $table ) {
			$tables[] = array(
				'text'  => $table['TABLE_NAME'],
				'value' => $table['TABLE_NAME'],
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'options' => $tables,
			)
		);
	}

	/**
	 * A wp_ajax callback method to field 2.
	 *
	 * @return void
	 */
	public static function retrieve_selected_columns() {

		Automator()->utilities->verify_nonce();

		$options = array();

		$request        = automator_filter_input_array( 'values', INPUT_POST );
		$selected_table = $request['TABLE'] ?? '';

		$columns = self::get_table_columns( $selected_table );

		foreach ( $columns as $column ) {
			$options[] = array(
				'text'  => $column['COLUMN_NAME'],
				'value' => $column['COLUMN_NAME'],
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'options' => $options,
			)
		);
	}

	/**
	 * A wp_ajax callback method to the "where" field.
	 *
	 * @return void
	 */
	public static function retrieve_selected_columns_repeater() {

		Automator()->utilities->verify_nonce();

		$options = array();

		$request_fields_values = automator_filter_input_array( 'values', INPUT_POST );
		$selected_table        = $request_fields_values['TABLE'] ?? array();
		$columns               = self::get_table_columns( $selected_table );

		foreach ( $columns as $column ) {
			$options[] = array(
				'WHERE_COLUMN'   => $column['COLUMN_NAME'],
				'WHERE_OPERATOR' => '',
				'WHERE_VALUE'    => '',
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'rows'    => $options,
			)
		);
	}

	/**
	 * Convert an associative array into an array of arrays (indexed array).
	 * @param array $array
	 * @return array
	 */
	public static function associative_array_to_indexed_array( array $array ) {

		$indexed_array = array();

		foreach ( $array as $row ) {
			$indexed_array[] = array_values( $row );
		}

		return $indexed_array;

	}

	/**
	 * Accepts associative array and converts them into CSV.
	 *
	 * @param mixed $data
	 * @return string
	 */
	public static function array_to_csv( $array, $delimiter = ',', $enclosure = '"', $escape_char = '\\' ) {

		if ( 0 === count( $array ) ) {
			return null;
		}

		$df = fopen( 'php://temp', 'r+' );

		fputcsv( $df, array_keys( reset( $array ) ) );

		foreach ( $array as $row ) {
			fputcsv( $df, $row );
		}

		rewind( $df );

		$csv = stream_get_contents( $df );

		fclose( $df ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return $csv;

	}

	/**
	 * Insert dynamic action tokens.
	 *
	 * @param array $registered_tokens
	 * @param mixed $action_id
	 * @param mixed $recipe_id
	 *
	 * @return array
	 */
	public function insert_dynamic_action_tokens( $registered_tokens = array(), $action_id = null, $recipe_id = null ) {

		$field_val = get_post_meta( $action_id, 'COLUMN', true );

		if ( ! is_string( $field_val ) || empty( $field_val ) ) {
			return $registered_tokens;
		}

		$decoded = (array) json_decode( $field_val );

		foreach ( $decoded as $decoded_val ) {
			$registered_tokens[] = array(
				'tokenId'     => 'OUTPUT_' . $decoded_val,
				'tokenParent' => 'DB_QUERY_SELECT_QUERY_RUN',
				'tokenName'   => $decoded_val,
				'tokenType'   => 'text',
			);
		}

		return $registered_tokens;
	}

}
