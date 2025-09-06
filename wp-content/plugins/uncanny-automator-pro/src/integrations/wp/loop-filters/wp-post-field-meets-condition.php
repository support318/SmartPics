<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

/**
 * Class WP_POST_FIELD_MEETS_CONDITION
 *
 * @package Uncanny_Automator_Pro\Loop_Filters
 */
final class WP_POST_FIELD_MEETS_CONDITION extends Loop_Filter {

	/**
	 * Setups the filter.
	 *
	 * @return void
	 */
	public function setup() {

		$this->set_integration( 'WP' );
		$this->set_meta( 'WP_POST_FIELD_MEETS_CONDITION' );
		$this->set_sentence( esc_html_x( "A post's {{field}} meets a condition", 'WordPress', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
				esc_html_x( "A post's {{field:%1\$s}} {{meets:%2\$s}} {{a condition:%3\$s}}", 'WordPress', 'uncanny-automator-pro' ),
				$this->get_meta(),
				'CONDITION',
				'VALUE'
			)
		);
		$this->set_loop_type( 'posts' );
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_posts' ) );

	}

	/**
	 * Loads the fields.
	 *
	 * @return mixed[]
	 */
	public function load_options() {

		$field = array(
			'option_code'     => $this->get_meta(),
			'type'            => 'select',
			'label'           => esc_html_x( 'Field', 'WordPress', 'uncanny-automator' ),
			'required'        => true,
			'options'         => $this->get_columns(),
			'options_show_id' => false,
		);

		$condition = array(
			'option_code'     => 'CONDITION',
			'type'            => 'select',
			'label'           => esc_html_x( 'Criteria', 'WordPress', 'uncanny-automator' ),
			'required'        => true,
			'options'         => $this->get_conditions(),
			'options_show_id' => false,
		);

		$value_compare = array(
			'option_code' => 'VALUE',
			'type'        => 'text',
			'label'       => esc_html_x( 'Value', 'WordPress', 'uncanny-automator' ),
			'required'    => true,
		);

		return array(
			$this->get_meta() => array(
				$field,
				$condition,
				$value_compare,
			),
		);

	}

	/**
	 * Retrieves conditions.
	 *
	 * @return array{array{text:string,value:string}}
	 */
	private function get_conditions() {
		return array(
			array(
				'text'  => esc_attr_x( 'is', 'WP', 'uncanny-automator-pro' ),
				'value' => 'is',
			),
			array(
				'text'  => esc_attr_x( 'is not', 'WP', 'uncanny-automator-pro' ),
				'value' => 'is-not',
			),
			array(
				'text'  => esc_attr_x( 'less than', 'WP', 'uncanny-automator-pro' ),
				'value' => 'less-than',
			),
			array(
				'text'  => esc_attr_x( 'greater than', 'WP', 'uncanny-automator-pro' ),
				'value' => 'greater-than',
			),
			array(
				'text'  => esc_attr_x( 'greater than or equal to', 'WP', 'uncanny-automator-pro' ),
				'value' => 'greater-than-or-equal-to',
			),
			array(
				'text'  => esc_attr_x( 'less than or equal to', 'WP', 'uncanny-automator-pro' ),
				'value' => 'less-than-or-equal-to',
			),
		);
	}

	/**
	 * Retrieves columns.
	 *
	 * @return array{array{text:string,value:string}}
	 */
	private function get_columns() {
		return array(
			array(
				'text'  => esc_attr_x( 'ID', 'WP', 'uncanny-automator-pro' ),
				'value' => 'ID',
			),
			array(
				'text'  => esc_attr_x( 'post_author', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_author',
			),
			array(
				'text'  => esc_attr_x( 'post_date', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_date',
			),
			array(
				'text'  => esc_attr_x( 'post_content', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_content',
			),
			array(
				'text'  => esc_attr_x( 'post_title', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_title',
			),
			array(
				'text'  => esc_attr_x( 'post_excerpt', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_excerpt',
			),
			array(
				'text'  => esc_attr_x( 'post_status', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_status',
			),
			array(
				'text'  => esc_attr_x( 'comment_status', 'WP', 'uncanny-automator-pro' ),
				'value' => 'comment_status',
			),
			array(
				'text'  => esc_attr_x( 'ping_status', 'WP', 'uncanny-automator-pro' ),
				'value' => 'ping_status',
			),
			array(
				'text'  => esc_attr_x( 'post_password', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_password',
			),
			array(
				'text'  => esc_attr_x( 'post_name', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_name',
			),
			array(
				'text'  => esc_attr_x( 'to_ping', 'WP', 'uncanny-automator-pro' ),
				'value' => 'to_ping',
			),
			array(
				'text'  => esc_attr_x( 'pinged', 'WP', 'uncanny-automator-pro' ),
				'value' => 'pinged',
			),
			array(
				'text'  => esc_attr_x( 'post_parent', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_parent',
			),
			array(
				'text'  => esc_attr_x( 'guid', 'WP', 'uncanny-automator-pro' ),
				'value' => 'guid',
			),
			array(
				'text'  => esc_attr_x( 'menu_order', 'WP', 'uncanny-automator-pro' ),
				'value' => 'menu_order',
			),
			array(
				'text'  => esc_attr_x( 'post_type', 'WP', 'uncanny-automator-pro' ),
				'value' => 'post_type',
			),
			array(
				'text'  => esc_attr_x( 'comment_count', 'WP', 'uncanny-automator-pro' ),
				'value' => 'comment_count',
			),
		);
	}

	/**
	 * Creates a symbol for db query.
	 *
	 * @param string $condition
	 *
	 * @return string|null
	 */
	private function create_symbol( $condition ) {

		$symbols = array(
			'is'                       => '=',
			'is-not'                   => '!=',
			'less-than'                => '<',
			'greater-than'             => '>',
			'less-than-or-equal-to'    => '<=',
			'greater-than-or-equal-to' => '>=',
		);

		return isset( $symbols[ $condition ] ) ? $symbols[ $condition ] : null;

	}


	/**
	 * @param mixed[] $fields
	 *
	 * @return int[]
	 */
	public function retrieve_posts( $fields ) {

		$column    = isset( $fields[ $this->get_meta() ] ) ? $fields[ $this->get_meta() ] : '';
		$condition = isset( $fields['CONDITION'] ) ? $fields['CONDITION'] : '';
		$value     = isset( $fields['VALUE'] ) ? $fields['VALUE'] : '';
		$symbol    = $this->create_symbol( $condition );

		if ( empty( $column ) || empty( $condition ) || 0 === strlen( trim( $value ) || empty( $symbol ) ) ) {
			return array();
		}

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE `" . esc_sql( (string) $column ) . '` ' . $wpdb->_real_escape( (string) $symbol ) . " %s AND post_type <> 'revision'", //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$value
		);

		if ( $this->is_symbol_numeric( $symbol ) && false === $this->is_field_numeric( $column ) ) {
			throw new Loops_Exception( 'Unable to compare non-numeric field as number', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$found_post_entities = (array) array_column( $results, 'ID' );

		return $found_post_entities;

	}

	/**
	 * Determines if a specific field is numeric.
	 *
	 * @param string $column
	 *
	 * @return bool
	 */
	public function is_field_numeric( $column ) {
		return in_array( $column, array( 'comment_count', 'menu_order', 'post_author', 'ID' ), true );
	}

	/**
	 * Determines if the symbol yields numeric comparison or not.
	 *
	 * @param string $symbol
	 *
	 * @return bool
	 */
	private function is_symbol_numeric( $symbol ) {
		return in_array( $symbol, array( '>=', '<=', '>', '<' ), true );
	}

}
