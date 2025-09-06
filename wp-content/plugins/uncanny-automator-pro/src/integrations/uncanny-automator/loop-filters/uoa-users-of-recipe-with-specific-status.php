<?php

namespace Uncanny_Automator_Pro\Loop_Filters;

use Uncanny_Automator\Automator_Status;
use Uncanny_Automator_Pro\Loops\Filter\Base\Loop_Filter;

/**
 * Class UOA_USERS_OF_RECIPE_WITH_SPECIFIC_STATUS
 * @package Uncanny_Automator_Pro
 */
class UOA_USERS_OF_RECIPE_WITH_SPECIFIC_STATUS extends Loop_Filter {

	public function setup() {
		$this->set_integration( 'UOA' );
		$this->set_meta( 'UOA_USERS_OF_RECIPE_WITH_SPECIFIC_STATUS' );
		$this->set_sentence( esc_html_x( 'Users of {{a recipe}} in {{a status}}', 'Uncanny Automator Pro', 'uncanny-automator-pro' ) );
		$this->set_sentence_readable(
			sprintf(
			/* translators: Filter sentence */
				esc_html_x( 'Users of {{a recipe:%1$s}} in {{a status:%2$s}}', 'Uncanny Automator Pro', 'uncanny-automator-pro' ),
				$this->get_meta(),
				'RECIPE_STATUS',
			)
		);
		$this->set_fields( array( $this, 'load_options' ) );
		$this->set_entities( array( $this, 'retrieve_users_of_recipe_with_specified_status' ) );

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
					'label'       => esc_html_x( 'Recipe', 'Uncanny Automator Pro', 'uncanny-automator-pro' ),
					'options'     => $this->get_recipes_as_options(),
					'required'    => true,
				),
				array(
					'option_code' => 'RECIPE_STATUS',
					'type'        => 'select',
					'label'       => esc_html_x( 'Status', 'Uncanny Automator Pro', 'uncanny-automator-pro' ),
					'options'     => $this->get_recipe_statuses(),
					'required'    => true,
				),
			),
		);

	}

	/**
	 * @return array
	 */
	public function get_recipes_as_options() {
		$options = array();

		$recipes = get_posts(
			array(
				'post_type'      => 'uo-recipe',
				'posts_per_page' => 9999,
			)
		);

		foreach ( $recipes as $recipe ) {
			$options[] = array(
				'value' => $recipe->ID,
				'text'  => $recipe->post_title,
			);
		}

		return $options;
	}

	/**
	 * @return array
	 */
	public function get_recipe_statuses() {
		$options = array();

		$recipes_statuses = Automator_Status::get_all_statuses();

		foreach ( $recipes_statuses as $status => $status_name ) {
			$options[] = array(
				'value' => $status,
				'text'  => $status_name,
			);
		}

		return $options;
	}

	/**
	 * @param array{UOA_USERS_OF_RECIPE_WITH_SPECIFIC_STATUS:integer,RECIPE_STATUS:integer} $fields
	 *
	 * @return int[]
	 */
	public function retrieve_users_of_recipe_with_specified_status( $fields ) {

		global $wpdb;

		// Bail if value is falsy.
		if ( empty( $fields['UOA_USERS_OF_RECIPE_WITH_SPECIFIC_STATUS'] ) || empty( $fields['RECIPE_STATUS'] ) ) {
			return array();
		}

		$recipe = absint( $fields['UOA_USERS_OF_RECIPE_WITH_SPECIFIC_STATUS'] );
		$status = absint( $fields['RECIPE_STATUS'] );

		global $wpdb;

		$recipe_users = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}uap_recipe_log WHERE completed = %d AND automator_recipe_id = %d",
				$status,
				$recipe
			),
			ARRAY_A
		);

		return array_unique( array_column( $recipe_users, 'user_id' ), SORT_REGULAR );

	}

}
