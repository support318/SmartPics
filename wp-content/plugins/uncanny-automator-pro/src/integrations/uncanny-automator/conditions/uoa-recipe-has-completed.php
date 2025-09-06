<?php

namespace Uncanny_Automator_Pro;

/**
 * Class UOA_RECIPE_HAS_COMPLETED
 *
 * @package Uncanny_Automator_Pro
 */
class UOA_RECIPE_HAS_COMPLETED extends Action_Condition {

	/**
	 * @var string CACHE_KEY
	 */
	const CACHE_KEY = 'UOA_RECIPE_HAS_COMPLETED_CONDITION_KEY';

	/**
	 * @var string CACHE_GROUP
	 */
	const CACHE_GROUP = 'UOA_RECIPE_HAS_COMPLETED_CONDITION_GROUP';

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration = 'UOA';
		/* translators: Token */
		$this->name = __( 'The user {{has}} completed {{the recipe}}', 'uncanny-automator-pro' );
		$this->code = 'RECIPE_COMPLETED_OR_NOT';
		/* translators: A token matches a value */
		$this->dynamic_name  = sprintf( esc_html__( 'The user {{has:%1$s}} completed {{the recipe:%2$s}}', 'uncanny-automator-pro' ), 'RECIPE_CONDITION', 'UOA_RECIPE' );
		$this->requires_user = true;
		$this->is_pro        = true;
	}

	/**
	 * fields
	 *
	 * @return array
	 */
	public function fields() {

		$recipes_args = array(
			'option_code'           => 'UOA_RECIPE',
			'label'                 => esc_html__( 'Recipe', 'uncanny-automator-pro' ),
			'required'              => true,
			'options'               => $this->get_all_publish_recipes(),
			'supports_custom_value' => true,
			'options_show_id'       => false,
		);

		return array(
			$this->field->select_field_args(
				array(
					'option_code'            => 'RECIPE_CONDITION',
					'label'                  => esc_html__( 'Condition', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'options'                => array(
						array(
							'value' => 'has',
							'text'  => __( 'has', 'uncanny-automator-pro' ),
						),
						array(
							'value' => 'has_not',
							'text'  => __( 'has not', 'uncanny-automator-pro' ),
						),
					),
					'supports_custom_value'  => false,
					'options_show_id'        => false,
				)
			),
			// Product field
			$this->field->select_field_args( $recipes_args ),
		);
	}

	/**
	 * @return array[]
	 */
	public function get_all_publish_recipes() {

		$args = array(
			'post_type'      => 'uo-recipe',
			'posts_per_page' => 9999, //phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$return = array();

		// Try retrieving from the run-time cache.
		$options = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );

		if ( false === $options ) {
			$options = Automator()->helpers->recipe->options->wp_query( $args, false, false );
		}

		wp_cache_set( self::CACHE_KEY, $options, self::CACHE_GROUP );

		if ( empty( $options ) ) {
			return $return;
		}

		foreach ( $options as $id => $text ) {
			$return[] = array(
				'value' => $id,
				'text'  => $text,
			);
		}

		return $return;
	}

	/**
	 * Evaluate_condition
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$condition = $this->get_parsed_option( 'RECIPE_CONDITION' );
		$recipe    = $this->get_parsed_option( 'UOA_RECIPE' );
		$user_id   = $this->user_id;
		switch ( $condition ) {
			case 'has':
				if ( Automator()->user_completed_recipe_number_times( $recipe, $user_id ) === 0 ) {
					$message = __( 'The user has not completed the recipe: ', 'uncanny-automator-pro' ) . $this->get_option( 'UOA_RECIPE_readable' );
					$this->condition_failed( $message );
				}
				break;
			case 'has_not':
				if ( Automator()->user_completed_recipe_number_times( $recipe, $user_id ) !== 0 ) {
					$message = __( 'The user has completed the recipe: ', 'uncanny-automator-pro' ) . $this->get_option( 'UOA_RECIPE_readable' );
					$this->condition_failed( $message );
				}
				break;
		}

	}

}
