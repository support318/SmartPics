<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Uoa_Pro_Helpers
 *
 * Provides helper functions for the Uncanny Automator Pro plugin, including methods to
 * set helper options, manage recipe statuses, and fetch recipe data.
 *
 * @package Uncanny_Automator_Pro
 */
class Uoa_Pro_Helpers {

	/**
	 * Holds options for Uncanny Automator Pro.
	 *
	 * @var Uoa_Pro_Helpers
	 */
	public $options;

	/**
	 * Holds an instance of Uncanny Automator Pro.
	 *
	 * @var Uoa_Pro_Helpers
	 */
	public $pro;

	/**
	 * Sets the Pro instance.
	 *
	 * @param Uoa_Pro_Helpers $pro Instance of Uncanny Automator Pro.
	 *
	 * @return void
	 */
	public function setPro( Uoa_Pro_Helpers $pro ) {

		$this->pro = $pro;

	}

	/**
	 * Sets the options instance.
	 *
	 * @param Uoa_Pro_Helpers $options Instance containing options for Uncanny Automator Pro.
	 *
	 * @return void
	 */
	public function setOptions( Uoa_Pro_Helpers $options ) {

		$this->options = $options;

	}

	/**
	 * Uoa_Pro_Helpers constructor.
	 *
	 * Initializes the class by adding a WordPress AJAX action for handling recipe status changes.
	 */
	public function __construct() {

		add_action( 'wp_ajax_automator_pro_recipe_change_status_action_recipe_field', array( $this, 'recipe_change_status_recipe_field_handler' ) );

	}

	/**
	 * Handles AJAX request to change the recipe status.
	 *
	 * Fetches a list of recipes and sends them as JSON response for populating recipe fields.
	 *
	 * @return void
	 */
	public function recipe_change_status_recipe_field_handler() {

		Automator()->utilities->verify_nonce();

		$options = array();

		foreach ( self::fetch_recipes() as $recipe ) {
			$title = empty( $recipe['title'] ) ? __( '(No title)', 'uncanny-automator-pro)' ) : $recipe['title'];
			if ( empty( $recipe['ID'] ) ) {
				continue; // Skip.
			}
			$options[] = array(
				'text'  => $title,
				'value' => $recipe['ID'],
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'options' => $options,
			)
		);

	}

	/**
	 * Fetches all published recipes.
	 *
	 * Retrieves all posts of type 'uo-recipe', returning an array with each recipe's ID and title.
	 *
	 * @return array Array of recipe data, each containing 'ID' and 'title' keys.
	 */
	public static function fetch_recipes() {

		$args = array(
			'post_type'      => 'uo-recipe',
			'posts_per_page' => -1, // Retrieve all posts.
			'post_status'    => array( 'publish', 'draft' ),
			'fields'         => 'ids', // Only retrieve IDs for better performance.
		);

		$post_ids = get_posts( $args );

		// Build an array of post data with each post's ID and title.
		return array_map(
			function( $post_id ) {
				return array(
					'ID'    => $post_id,
					'title' => get_the_title( $post_id ),
				);
			},
			$post_ids
		);

	}

}
