<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

/**
 * Class WP_POST_META_MEETS_CONDITION
 *
 * @package Uncanny_Automator_Pro\Loop_Filters
 */
final class WP_POST_META_MEETS_CONDITION extends Loop_Filter {

	/**
	 * Setups the filter.
	 *
	 * @return void
	 */
	public function setup() {

		$this->set_integration( 'WP' );
		$this->set_meta( 'WP_POST_META_MEETS_CONDITION' );
		$this->set_sentence( esc_html_x( "A post's {{meta value}} meets a condition", 'WordPress', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
				esc_html_x( "A post's {{meta value:%1\$s}} {{meets:%2\$s}} {{a condition:%3\$s}}", 'WordPress', 'uncanny-automator-pro' ),
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
			'option_code' => $this->get_meta(),
			'type'        => 'text',
			'label'       => esc_html_x( 'Key', 'WordPress', 'uncanny-automator' ),
			'required'    => true,
		);

		$condition = array(
			'option_code'           => 'CONDITION',
			'type'                  => 'select',
			'label'                 => esc_html_x( 'Criteria', 'WordPress', 'uncanny-automator' ),
			'required'              => true,
			'options'               => $this->get_conditions(),
			'options_show_id'       => false,
			'supports_custom_value' => false,
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
				'text'  => esc_attr_x( 'contains', 'WP', 'uncanny-automator-pro' ),
				'value' => 'contains',
			),
			array(
				'text'  => esc_attr_x( 'does-not-contain', 'WP', 'uncanny-automator-pro' ),
				'value' => 'does-not-contain',
			),
		);
	}


	/**
	 * @param mixed[] $fields
	 *
	 * @return int[]
	 */
	public function retrieve_posts( $fields ) {

		$meta_key   = isset( $fields[ $this->get_meta() ] ) ? trim( $fields[ $this->get_meta() ] ) : '';
		$meta_value = isset( $fields['VALUE'] ) ? trim( $fields['VALUE'] ) : '';
		$condition  = isset( $fields['CONDITION'] ) ? trim( $fields['CONDITION'] ) : '';

		$meta_compare = $this->create_meta_compare_symbol( $condition );

		if ( false === $meta_compare ) {
			throw new Loops_Exception( 'Error: Meta compare value is not supported.', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		if ( 0 === strlen( $meta_key ) || 0 === strlen( $meta_value ) || 0 === strlen( $condition ) ) {
			throw new Loops_Exception( 'Error: The loop filter requires values for the meta key, meta value, and condition fields, but they are currently missing. Please ensure all required fields are filled out to proceed.', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		$args = array(
			'fields'     => 'ids',
			'post_type'  => get_post_types(),
			'meta_query' => array(
				array(
					'key'          => $meta_key,
					'value'        => $meta_value,
					'meta_compare' => $meta_compare,
				),
			),
		);

		$posts = (array) get_posts( $args );

		return $posts;

	}

	/**
	 * Creates a meta compare symbol.
	 *
	 * @param string $condition
	 *
	 * @return false|string Returns false if no matching condition. Otherwise the meta query symbol for matching condition.
	 */
	public function create_meta_compare_symbol( $condition ) {

		switch ( $condition ) {
			case 'is':
				return '=';
			case 'is-not':
				return '!=';
			case 'contains':
				return 'LIKE';
			case 'does-not-contain':
				return 'NOT LIKE';
			default:
				return false;
		}

	}

}
