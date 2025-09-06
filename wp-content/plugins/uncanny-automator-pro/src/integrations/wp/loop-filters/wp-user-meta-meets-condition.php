<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

/**
 * Loop filter "A {{user meta value}} meets a condition"
 *
 * @since 5.0
 */
final class WP_USER_META_MEETS_CONDITION extends Loop_Filter {

	public function setup() {

		$this->set_integration( 'WP' );

		$this->set_meta( 'WP_USER_META_MEETS_CONDITION' );

		$this->set_sentence( esc_html_x( 'A user {{meta value}} meets a condition', 'WordPress', 'uncanny-automator-pro' ) );

		$this->set_sentence_readable(
			sprintf(
				/* translators: Filter sentence */
				esc_html_x( 'A user {{meta value:%1$s}} {{meets:%2$s}} {{a condition:%3$s}}', 'WordPress', 'uncanny-automator-pro' ),
				$this->get_meta(),
				'CRITERIA',
				'VALUE'
			)
		);

		$this->set_fields( array( $this, 'load_options' ) );

		$this->set_entities( array( $this, 'retrieve_users_matching_criteria' ) );

	}

	/**
	 * @return mixed[]
	 */
	public function load_options() {

		return array(
			$this->get_meta() => array(
				array(
					'option_code'            => $this->get_meta(),
					'type'                   => 'text',
					'label'                  => esc_html_x( 'Meta', 'WordPress', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'supports_token'         => true,
				),
				array(
					'option_code'            => 'CRITERIA',
					'type'                   => 'select',
					'label'                  => esc_html_x( 'Criteria', 'WordPress', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'options'                => $this->get_criterias(),
					'required'               => true,
				),
				array(
					'option_code'            => 'VALUE',
					'type'                   => 'text',
					'label'                  => esc_html_x( 'Value', 'WordPress', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'supports_tokens'        => true,
				),
			),
		);

	}

	/**
	 * @param array{WP_USER_META_MEETS_CONDITION:string,CRITERIA:string,VALUE:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_matching_criteria( $fields ) {

		// Bail if any of the fields is falsy.
		if ( empty( $fields['VALUE'] ) || empty( $fields['WP_USER_META_MEETS_CONDITION'] ) || empty( $fields['CRITERIA'] ) ) {

			$fields_stringified = false !== wp_json_encode( $fields ) ? wp_json_encode( $fields ) : 'Error: Invalid JSON from fields array.';

			throw new Loops_Exception(
				'Error: The selected loop filter contains one or more empty field values, which are invalid.' . $fields_stringified,
				Automator_Status::COMPLETED_WITH_NOTICE
			);

		}

		global $wpdb;

		$meta_key   = $fields['WP_USER_META_MEETS_CONDITION'];
		$criteria   = $fields['CRITERIA'];
		$meta_value = $fields['VALUE'];

		// WP_User_Query is slow lets use simple query.
		$query = $this->get_query_logic( $meta_key, $criteria, $meta_value );

		if ( false === $query ) {
			return array();
		}

		// Add field validation here.
		$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $results ) ) {
			return array();
		}

		$user_ids = (array) array_column( $results, 'user_id' );

		return array_map( 'absint', $user_ids );

	}

	/**
	 * @param string $meta_key
	 * @param string $criteria
	 * @param string $meta_value
	 *
	 * @return false|string
	 */
	private function get_query_logic( $meta_key, $criteria, $meta_value ) {

		global $wpdb;

		switch ( $criteria ) {
			case 'is':
				// Compare the value as string.
				return $wpdb->prepare(
					"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
					$meta_key,
					$meta_value
				);
			case 'is-not':
				return $wpdb->prepare(
					"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value <> %s",
					$meta_key,
					$meta_value
				);
			case 'contains':
				$value = '%' . $wpdb->esc_like( $meta_value ) . '%';
				return $wpdb->prepare(
					"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
					$meta_key,
					$value
				);
			case 'does-not-contain':
				$value = '%' . $wpdb->esc_like( $meta_value ) . '%';
				return $wpdb->prepare(
					"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s and meta_value NOT LIKE %s",
					$meta_key,
					$value
				);
			case 'greater-than':
				return $this->fetch_users_with_comparison_type( $meta_key, $meta_value, '>' );
			case 'less-than':
				return $this->fetch_users_with_comparison_type( $meta_key, $meta_value, '<' );
			case 'greater-than-or-equal-to':
				return $this->fetch_users_with_comparison_type( $meta_key, $meta_value, '>=' );
			case 'less-than-or-equal-to':
				return $this->fetch_users_with_comparison_type( $meta_key, $meta_value, '<=' );
		}

		return false;

	}

	/**
	 * Resolves different query comparison type.
	 *
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param string $comparison_operator
	 *
	 * @return string The prepared query to use for fetching the users.
	 */
	protected function fetch_users_with_comparison_type( $meta_key = '', $meta_value = '', $comparison_operator = '' ) {

		global $wpdb;

		$token = '%s'; // Defaults as string comparison.

		if ( is_numeric( $meta_value ) ) {
			$meta_value = floatval( $meta_value );
			$token      = '%f'; // If its numeric, compare as float so we automatically support floating points.
		}

		return $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value " . $comparison_operator . ' ' . $token, //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, 
			$meta_key,
			$meta_value
		);

	}

	/**
	 * @return array{array{text:string,value:string}}
	 */
	private function get_criterias() {

		return array(
			array(
				'text'  => esc_html_x( 'is', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'is',
			),
			array(
				'text'  => esc_html_x( 'is not', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'is-not',
			),
			array(
				'text'  => esc_html_x( 'contains', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'contains',
			),
			array(
				'text'  => esc_html_x( 'does not contain', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'does-not-contain',
			),
			array(
				'text'  => esc_html_x( 'greater than', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'greater-than',
			),
			array(
				'text'  => esc_html_x( 'less than', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'less-than',
			),
			array(
				'text'  => esc_html_x( 'greater than or equal to', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'greater-than-or-equal-to',
			),
			array(
				'text'  => esc_html_x( 'less than or equal to', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'less-than-or-equal-to',
			),
		);

	}

}
