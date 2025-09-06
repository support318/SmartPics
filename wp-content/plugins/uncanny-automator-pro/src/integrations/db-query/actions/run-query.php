<?php
// phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound
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
class Run_Query extends Uncanny_Automator\Recipe\Action {

	use Log_Properties;

	/**
	 * Setup Action.
	 *
	 * @return void.
	 */
	protected function setup_action() {

		$this->set_integration( 'DB_QUERY' );
		$this->set_action_code( 'DB_QUERY_RUN_QUERY_STRING' );
		$this->set_action_meta( 'DB_QUERY_RUN_QUERY_STRING_META' );
		$this->set_is_pro( true );
		$this->set_support_link( Automator()->get_author_support_link( $this->get_action_code(), 'knowledge-base/db-query/' ) );
		$this->set_requires_user( false );

		$this->set_sentence(
			sprintf(
				/* translators: Action - WordPress */
				sprintf( esc_attr__( 'Run {{an SQL query:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ),
				'NON_EXISTING:' . $this->get_action_meta()
			)
		);

		/* translators: Action - WordPress */
		$this->set_readable_sentence( esc_attr__( 'Run {{an SQL query}}', 'uncanny-automator-pro' ) );

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
				'name' => _x( 'Results (CSV)', 'DB Query', 'uncanny-automator-pro' ),
			),
			'OUTPUT_JSON'             => array(
				'name' => _x( 'Results (JSON)', 'DB Query', 'uncanny-automator-pro' ),
			),
			'OUTPUT_ARRAY_SERIALIZED' => array(
				'name' => _x( 'Results (Serialized Array)', 'DB Query', 'uncanny-automator-pro' ),
			),
		);

	}

	/**
	 * Loads available options for the Trigger.
	 *
	 * @return array The available trigger options.
	 */
	public function options() {

		$sql_query = array(
			'input_type'      => 'textarea',
			'option_code'     => $this->get_action_meta(),
			'relevant_tokens' => array(),
			'label'           => esc_attr_x( 'Query string', 'DB Query', 'uncanny-automator' ),
			'required'        => true,
			'description'     => _x( 'SQL keywords not supported in this action: DROP, ALTER, EXEC, CREATE, TRUNCATE', 'DB Query', 'uncanny-automator-pro' ),
		);

		return array( $sql_query );

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

		$stmt = $wpdb->prepare( $parsed[ $this->get_action_meta() ] ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $stmt ) ) {
			throw new Exception( sprintf( 'Invalid query provided. The generated query is: %s', $stmt ) );
		}

		$trimmed_query = $this->trim_secure_select_query( $stmt );

		$trimmed_query = apply_filters( 'automator_pro_db_query_run_query_action_statement', $trimmed_query );

		$props = array(
			'type'       => 'code',
			'label'      => _x( 'Query string (trimmed)', 'Uncanny Automator', 'uncanny-automator' ),
			'value'      => $trimmed_query,
			'attributes' => array(
				'code_language' => 'json',
			),
		);

		$this->set_log_properties( $props );

		$results = $wpdb->get_results( $trimmed_query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $wpdb->last_error ) {
			throw new Exception( 'An error has occured while running the query: ' . $wpdb->last_error );
		}

		$csv = Db_Query_Helpers::array_to_csv( $results );

		$tokens = array(
			'OUTPUT_CSV'              => $csv,
			'OUTPUT_ARRAY_SERIALIZED' => maybe_serialize( $results ),
			'OUTPUT_JSON'             => wp_json_encode( $results ),
		);

		$this->hydrate_tokens( $tokens );

		return true;

	}

	/**
	 * Executes
	 * @param mixed $query
	 * @return string
	 */
	public function trim_secure_select_query( $query ) {

		// Trim and keep the original query for executing.
		$query_trimmed = trim( $query );

		// Convert the query to uppercase to check for prohibited keywords.
		$trimmed_upper_query = strtoupper( $query_trimmed );

		// Check if the query starts with "SELECT".
		$allowed_queries = array( 'SELECT', 'INSERT', 'UPDATE', 'DELETE' );
		$query_keyword   = substr( $trimmed_upper_query, 0, 6 );

		if ( ! in_array( $query_keyword, $allowed_queries ) ) {
			throw new Exception( 'Error: Only SELECT, INSERT, UPDATE, and DELETE queries are allowed.' );
		}

		// Disallow semicolons which might indicate multiple SQL statements.
		if ( strpos( $trimmed_upper_query, ';' ) !== false ) {
			throw new Exception( 'Error: Multiple statements are not allowed.' );
		}

		// Disallow certain SQL keywords to prevent SQL manipulation after SELECT
		$disallowed_keywords = apply_filters( 'automator_pro_sql_disallowed_keywords', array( 'DROP', 'ALTER', 'EXEC', 'CREATE', 'TRUNCATE' ) );

		foreach ( $disallowed_keywords as $keyword ) {

			if ( strpos( $trimmed_upper_query, $keyword ) !== false ) {
				throw new Exception( 'Error: Dangerous SQL keywords detected.' );
			}
		}

		return $query_trimmed;

	}


}
