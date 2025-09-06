<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Loop filter User has {{a specific role}}
 *
 * @since 5.0
 */
final class WP_USER_FIELD_MEETS_CONDITION extends Loop_Filter {

	public function setup() {

		$this->set_integration( 'WP' );

		$this->set_meta( 'WP_USER_FIELD_MEETS_CONDITION' );

		$this->set_sentence( esc_html_x( 'A user {{field}} meets a condition', 'WordPress', 'uncanny-automator-pro' ) );

		$this->set_sentence_readable(
			sprintf(
				/* translators: Filter sentence */
				esc_html_x( 'A user {{field:%1$s}} {{meets:%2$s}} {{a condition:%3$s}}', 'WordPress', 'uncanny-automator-pro' ),
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
					'option_code' => $this->get_meta(),
					'type'        => 'select',
					'label'       => esc_html_x( 'User field', 'WordPress', 'uncanny-automator-pro' ),
					'options'     => $this->get_user_fields(),
					'required'    => true,
				),
				array(
					'option_code' => 'CRITERIA',
					'type'        => 'select',
					'label'       => esc_html_x( 'Criteria', 'WordPress', 'uncanny-automator-pro' ),
					'options'     => $this->get_criterias(),
					'required'    => true,
				),
				array(
					'option_code'     => 'VALUE',
					'type'            => 'text',
					'label'           => esc_html_x( 'Value', 'WordPress', 'uncanny-automator-pro' ),
					'required'        => true,
					'supports_tokens' => true,
				),
			),
		);

	}

	/**
	 * @param array{WP_USER_FIELD_MEETS_CONDITION:string,CRITERIA:string,VALUE:string} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_matching_criteria( $fields ) {

		global $wpdb;

		// Bail if value is falsy.
		if ( empty( $fields['VALUE'] ) || empty( $fields['WP_USER_FIELD_MEETS_CONDITION'] ) || empty( $fields['CRITERIA'] ) ) {
			return array();
		}

		$column   = $fields['WP_USER_FIELD_MEETS_CONDITION'];
		$criteria = $fields['CRITERIA'];
		$value    = $fields['VALUE'];

		$query = $this->get_query_logic( $column, $criteria, $value );

		if ( false === $query ) {
			return array();
		}

		// Add field validation here.
		$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $results ) ) {
			return array();
		}

		return array_column( $results, 'ID' );

	}

	/**
	 * @param string $column
	 * @param string $criteria
	 * @param string $value
	 *
	 * @return string|false
	 */
	private function get_query_logic( $column, $criteria, $value ) {

		global $wpdb;

		/**
		 * We're using direct queries because wp user query can be slow. The difference is noticeable for large number of users.
		 *
		 * @since 5.0
		 */
		switch ( $criteria ) {
			case 'is':
				return $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE `" . esc_sql( $column ) . '` = %s', $value );
			case 'is-not':
				return $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE `" . esc_sql( $column ) . '` <> %s', $value );
			case 'contains':
				$value = '%' . $wpdb->esc_like( $value ) . '%';
				return $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE `" . esc_sql( $column ) . '` LIKE %s', $value );
			case 'does-not-contain':
				$value = '%' . $wpdb->esc_like( $value ) . '%';
				return $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE `" . esc_sql( $column ) . '` NOT LIKE %s', $value );
			case 'greater-than':
				return $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE `" . esc_sql( $column ) . '` > %s', $value );
			case 'less-than':
				return $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE `" . esc_sql( $column ) . '` < %s', $value );
			case 'greater-than-or-equal-to':
				return $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE `" . esc_sql( $column ) . '` >= %s', $value );
			case 'less-than-or-equal-to':
				return $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE `" . esc_sql( $column ) . '` <= %s', $value );
		}

		return false;

	}

	/**
	 * @return mixed[]
	 */
	private function get_user_fields() {

		return array(
			array(
				'text'  => esc_html_x( 'ID', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'ID',
			),
			array(
				'text'  => esc_html_x( 'Display name', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'display_name',
			),
			array(
				'text'  => esc_html_x( 'User email', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'user_email',
			),
			array(
				'text'  => esc_html_x( 'User login', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'user_login',
			),
			array(
				'text'  => esc_html_x( 'User nice name', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'user_nicename',
			),
			array(
				'text'  => esc_html_x( 'User registered', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'user_registered',
			),
			array(
				'text'  => esc_html_x( 'User status', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'user_status',
			),
			array(
				'text'  => esc_html_x( 'User URL', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'user_url',
			),
		);

	}

	/**
	 * @return mixed[]
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
