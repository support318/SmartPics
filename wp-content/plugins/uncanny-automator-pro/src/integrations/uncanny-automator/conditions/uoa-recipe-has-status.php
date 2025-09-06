<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Automator_Status;

/**
 * Class UOA_RECIPE_HAS_STATUS
 *
 * @package Uncanny_Automator_Pro
 */
class UOA_RECIPE_HAS_STATUS extends Action_Condition {

	/**
	 * @var string CACHE_KEY
	 */
	const CACHE_KEY = 'UOA_RECIPE_HAS_STATUS_CONDITION_KEY';

	/**
	 * @var string CACHE_GROUP
	 */
	const CACHE_GROUP = 'UOA_RECIPE_HAS_STATUS_CONDITION_GROUP';

	/**
	 * Define_condition
	 *
	 * @return void
	 */
	public function define_condition() {
		$this->integration = 'UOA';
		/* translators: Token */
		$this->name = __( 'A user has a {{recipe}} in a {{status}}', 'uncanny-automator-pro' );
		$this->code = 'RECIPE_IN_A_STATUS';
		/* translators: A token matches a value */
		$this->dynamic_name  = sprintf( esc_html__( 'The user has a {{recipe:%1$s}} in a {{status:%2$s}}', 'uncanny-automator-pro' ), 'UOA_RECIPE', 'RECIPE_STATUS' );
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

		$recipe_statuses_options = array();
		$recipe_statuses         = Automator_Status::get_all_statuses();
		foreach ( $recipe_statuses as $status_id => $status_label ) {
			$recipe_statuses_options[] = array(
				'value' => $status_id,
				'text'  => $status_label,
			);
		}

		return array(
			$this->field->select_field_args( $recipes_args ),
			$this->field->select_field_args(
				array(
					'option_code'            => 'RECIPE_STATUS',
					'label'                  => esc_html__( 'Status', 'uncanny-automator-pro' ),
					'show_label_in_sentence' => false,
					'required'               => true,
					'options'                => $recipe_statuses_options,
					'supports_custom_value'  => false,
					'options_show_id'        => false,
				)
			),
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

		$recipe_status = $this->get_parsed_option( 'RECIPE_STATUS' );
		$recipe_id     = $this->get_parsed_option( 'UOA_RECIPE' );
		$user_id       = $this->user_id;

		global $wpdb;

		$log_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->prefix}uap_recipe_log WHERE user_id = %d AND completed = %d AND automator_recipe_id = %d",
				$user_id,
				$recipe_status,
				$recipe_id
			)
		);

		if ( 0 === $log_id ) {
			$message = sprintf( esc_html__( 'The recipe %1$s is not in a %2$s status', 'uncanny-automator-pro' ), $this->get_option( 'UOA_RECIPE_readable' ), $this->get_option( 'RECIPE_STATUS_readable' ) );
			$this->condition_failed( $message );
		}

	}

	/**
	 * Check if Automator Status dependencies are active
	 *
	 * @return bool
	 */
	protected function is_dependency_active() {
		return method_exists( '\Uncanny_Automator\Automator_Status', 'get_all_statuses' );
	}

}
