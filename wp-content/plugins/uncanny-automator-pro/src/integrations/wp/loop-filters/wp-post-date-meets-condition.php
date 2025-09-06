<?php
namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class WP_POST_DATE_MEETS_CONDITION
 *
 * @package Uncanny_Automator_Pro\Loop_Filters
 */
class WP_POST_DATE_MEETS_CONDITION extends Loop_Filter {

	/**
	 * Setups the filter.
	 *
	 * @return void
	 */
	public function setup() {

		$this->register_hooks();
		$this->set_integration( 'WP' );
		$this->set_meta( 'WP_POST_DATE_MEETS_CONDITION' );
		$this->set_sentence( esc_html_x( "A {{specific type of post's}} {{date}} meets a condition", 'WordPress', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
				/* translators: Loop filter sentence */
				esc_html_x( "A {{a specific type of post's:%1\$s}} {{date:%2\$s}} meets a condition", 'WordPress', 'uncanny-automator-pro' ),
				'POST_TYPE',
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

		$date = array(
			'option_code'           => $this->get_meta(),
			'type'                  => 'select',
			'label'                 => esc_html_x( 'Post date', 'WordPress', 'uncanny-automator' ),
			'required'              => true,
			'options'               => $this->get_date_options(),
			'supports_custom_value' => false,
			'options_show_id'       => false,
		);

		$post_type = array(
			'option_code'     => 'POST_TYPE',
			'type'            => 'select',
			'label'           => esc_html_x( 'Post type', 'WordPress', 'uncanny-automator' ),
			'required'        => true,
			'options'         => array(),
			'ajax'            => array(
				'endpoint' => 'retrieve_post_types',
				'event'    => 'on_load',
			),
			'options_show_id' => false,
		);

		$condition = array(
			'option_code'           => 'CONDITION',
			'type'                  => 'select',
			'label'                 => esc_html_x( 'Criteria', 'WordPress', 'uncanny-automator' ),
			'required'              => true,
			'options'               => $this->get_conditions(),
			'supports_custom_value' => false,
			'options_show_id'       => false,
		);

		$value_compare = array(
			'option_code'     => 'DATE_VALUE',
			'type'            => 'date',
			'date_format'     => 'Y-m-d',
			'date_alt_format' => 'Y-m-d',
			'label'           => esc_html_x( 'Date', 'WordPress', 'uncanny-automator' ),
			'required'        => true,
			'description'     => _x( 'Dates are expected to be in Y-m-d or timestamp format', 'WordPress', 'uncanny-automator' ),
		);

		$date_modifier = array(
			'option_code' => 'DATE_MODIFIER',
			'type'        => 'text',
			'label'       => esc_html_x( 'Date modifier', 'WordPress', 'uncanny-automator' ),
			'required'    => false,
			'description' => _x( 'This is an optional modifier to allow you to adjust the selected date with a date modification string (e.g., "+1 week", "+5 days", "-1 month")', 'WordPress', 'uncanny-automator' ),
		);

		return array(
			$this->get_meta() => array(
				$date,
				$post_type,
				$condition,
				$value_compare,
				$date_modifier,
			),
		);

	}

	/**
	 * Retrieve date options.
	 *
	 * @return array
	 */
	private function get_date_options() {
		return array(
			array(
				'text'  => esc_html_x( 'Published date', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'post_date',
			),
			array(
				'text'  => esc_html_x( 'Published date GMT', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'post_date_gmt',
			),
			array(
				'text'  => esc_html_x( 'Modified date', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'post_modified',
			),
			array(
				'text'  => esc_html_x( 'Modified date GMT', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'post_modified_gmt',
			),
		);
	}

	/**
	 * @return void
	 */
	protected function register_hooks() {
		add_action( 'wp_ajax_retrieve_post_types', array( $this, 'retrieve_post_types_handler' ) );
	}

		/**
	 * @return void
	 */
	public function retrieve_post_types_handler() {

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'object'
		);
		$options    = array();

		if ( ! empty( $post_types ) && is_iterable( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( $post_type instanceof \WP_Post_Type ) {
					$options[] = array(
						'text'  => $post_type->label,
						'value' => $post_type->name,
					);
				}
			}
		}

		$response = array(
			'success' => true,
			'options' => $options,
		);

		wp_send_json( $response );
	}

	/**
	 * Retrieve condition options.
	 *
	 * @return array
	 */
	private function get_conditions() {
		return array(
			array(
				'text'  => esc_html_x( 'Before', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'before',
			),
			array(
				'text'  => esc_html_x( 'After', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'after',
			),
			array(
				'text'  => esc_html_x( 'On', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'on',
			),
			array(
				'text'  => esc_html_x( 'Year', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'year',
			),
			array(
				'text'  => esc_html_x( 'Month', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'month',
			),
			array(
				'text'  => esc_html_x( 'Day', 'WordPress', 'uncanny-automator-pro' ),
				'value' => 'day',
			),
		);
	}


	/**
	 * @param mixed[] $fields
	 *
	 * @return int[]
	 */
	public function retrieve_posts( $fields ) {

		$column        = isset( $fields[ $this->get_meta() ] ) ? $fields[ $this->get_meta() ] : '';
		$post_type     = isset( $fields['POST_TYPE'] ) ? $fields['POST_TYPE'] : '';
		$condition     = isset( $fields['CONDITION'] ) ? $fields['CONDITION'] : '';
		$date          = isset( $fields['DATE_VALUE'] ) ? $fields['DATE_VALUE'] : '';
		$date_modifier = isset( $fields['DATE_MODIFIER'] ) ? $fields['DATE_MODIFIER'] : '';

		// Bail if required fields are empty.
		if ( empty( $column ) || empty( $post_type ) || empty( $condition ) || empty( $date ) ) {
			return array();
		}

		// Validate post type.
		if ( ! post_type_exists( $post_type ) ) {
			throw new \Exception( 'Invalid post type selected', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		// Validate date.
		$date_time = \DateTime::createFromFormat( 'Y-m-d', $date );
		if ( $date_time === false ) {
			// If the date is not in 'Y-m-d' format, try to treat it as a timestamp
			if ( is_numeric( $date ) ) {
				$date_time = ( new \DateTime() )->setTimestamp( $date );
			}
		}

		if ( $date_time === false ) {
			throw new \Exception( 'Invalid date format.', Automator_Status::COMPLETED_WITH_ERRORS );
		}

		// Apply date modifier.
		if ( ! empty( $date_modifier ) ) {
			try {
				$date_time->modify( $date_modifier );
			} catch ( \Exception $e ) {
				throw new \Exception( 'Invalid date modifier.', Automator_Status::COMPLETED_WITH_ERRORS );
			}
		}

		// Convert the date to 'Y-m-d' format for the query
		$date = $date_time->format( 'Y-m-d' );

		// Build date query.
		$date_query = array(
			'column' => $column,
		);

		switch ( $condition ) {
			case 'before':
				$date_query['before']    = $date;
				$date_query['inclusive'] = true;
				break;
			case 'after':
				$date_query['after']     = $date;
				$date_query['inclusive'] = true;
				break;
			case 'on':
				$date_query['year']  = date( 'Y', strtotime( $date ) );
				$date_query['month'] = date( 'm', strtotime( $date ) );
				$date_query['day']   = date( 'd', strtotime( $date ) );
				break;
			case 'year':
				$date_query['year'] = date( 'Y', strtotime( $date ) );
				break;
			case 'month':
				$date_query['month'] = date( 'm', strtotime( $date ) );
				break;
			case 'day':
				$date_query['day'] = date( 'd', strtotime( $date ) );
				break;
		}

		// Allow filter posts per page.
		$posts_per_page = apply_filters(
			'automator_pro_post_loop_date_filter_posts_per_page',
			99999,
			array(
				'post_type'  => $post_type,
				'date_query' => $date_query,
			)
		);

		// Query posts.
		$query = new \WP_Query(
			array(
				'date_query'     => array( $date_query ),
				'fields'         => 'ids',
				'posts_per_page' => $posts_per_page,
				'post_type'      => $post_type,
			)
		);

		return (array) $query->posts;
	}

}
