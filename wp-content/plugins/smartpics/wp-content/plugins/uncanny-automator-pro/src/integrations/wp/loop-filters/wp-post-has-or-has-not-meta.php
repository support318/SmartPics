<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

/**
 * Class WP_POST_HAS_OR_HAS_NOT_META
 *
 * @package Uncanny_Automator_Pro\Loop_Filters
 */
final class WP_POST_HAS_OR_HAS_NOT_META extends Loop_Filter {

	/**
	 * Setups the filter.
	 *
	 * @return void
	 */
	public function setup() {

		$this->set_integration( 'WP' );
		$this->set_meta( 'WP_POST_HAS_OR_HAS_NOT_META' );
		$this->set_sentence( esc_html_x( 'A post {{has/does not have}} {{a specific meta key}}', 'WordPress', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
				/* translators: Loop filter sentence */
				esc_html_x( 'A post {{has:%1$s}} {{a specific meta key:%2$s}}', 'WordPress', 'uncanny-automator-pro' ),
				'CONDITION',
				$this->get_meta()
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

		$condition = array(
			'option_code'           => 'CONDITION',
			'type'                  => 'select',
			'label'                 => esc_html_x( 'Condition', 'WordPress', 'uncanny-automator' ),
			'required'              => true,
			'options'               => $this->get_conditions(),
			'options_show_id'       => false,
			'supports_custom_value' => false,
		);

		$meta_key = array(
			'option_code' => $this->get_meta(),
			'type'        => 'text',
			'label'       => esc_html_x( 'Meta key', 'WordPress', 'uncanny-automator' ),
			'required'    => true,
		);

		return array(
			$this->get_meta() => array(
				$condition,
				$meta_key,
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
				'text'  => esc_attr_x( 'has', 'WP', 'uncanny-automator-pro' ),
				'value' => 'has',
			),
			array(
				'text'  => esc_attr_x( 'does not have', 'WP', 'uncanny-automator-pro' ),
				'value' => 'has-not',
			),
		);
	}


	/**
	 * @param mixed[] $fields
	 *
	 * @return int[]
	 */
	public function retrieve_posts( $fields ) {

		$meta_key  = isset( $fields[ $this->get_meta() ] ) ? trim( $fields[ $this->get_meta() ] ) : '';
		$condition = isset( $fields['CONDITION'] ) ? trim( $fields['CONDITION'] ) : '';

		if ( 0 === strlen( $meta_key ) || 0 === strlen( $condition ) ) {
			throw new Loops_Exception( 'Error: The loop filter requires values for the meta key and condition fields, which are currently missing. Please ensure all required fields are filled out to proceed.', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		global $wpdb;

		$query = $this->create_query( $condition, $meta_key );

		$results = (array) $wpdb->get_results(
			$query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		$posts = array_column( $results, 'post_id' );

		return $posts;

	}

	/**
	 * Creates a query statement based on the condition selected.
	 *
	 * @param string $condition
	 * @param string $meta_key
	 *
	 * @return string
	 */
	public function create_query( $condition, $meta_key ) {

		global $wpdb;

		$symbol = '=';

		if ( 'has-not' === $condition ) {
			$symbol = '<>';
		}

		$stmt = "SELECT post_id
			FROM $wpdb->postmeta as meta
			INNER JOIN $wpdb->posts as post
				ON post.ID = meta.post_id
			WHERE 1 = 1
				AND meta.meta_key " . $symbol . " %s
				AND post.post_type <> 'revision'
			GROUP BY post_id
		";

		$query = $wpdb->prepare( $stmt, $meta_key ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $query;

	}

}
