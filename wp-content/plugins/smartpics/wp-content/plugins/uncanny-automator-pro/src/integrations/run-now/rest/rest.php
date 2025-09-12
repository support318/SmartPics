<?php
namespace Uncanny_Automator_Pro\Integrations\Run_Now;

use Exception;
use WP_REST_Request;

class Rest {

	/**
	 * Hooks our method "register_route" to the init action hook.
	 *
	 * @return void
	 */
	public function initialize_rest() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Callback method from "init" action hook in the method "initialize_rest".
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			'uap/v2',
			'/run-now/start/(?P<recipe_id>\d+)',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => function () {
					$can_manage_options = current_user_can( 'manage_options' );
					return apply_filters( 'automator_run_now_permission_callback_return', $can_manage_options );
				},
				'args'                => array(
					'recipe_id' => array(
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public function handle( WP_REST_Request $request ) {

		$recipe_id  = $request->get_param( 'recipe_id' );
		$is_success = true;

		do_action( 'automator_pro_run_now_recipe', $recipe_id );

		try {
			$recipe_object = Automator()->get_recipe_object( $recipe_id );
		} catch ( Exception $e ) {
			$is_success = false;
		}

		$recipe_object_array = (array) json_decode( $recipe_object, true );

		$recipe_is_running    = $recipe_object_array['miscellaneous']['recipe_is_running'] ?? false;
		$recipe_run_completed = ! $recipe_is_running;

		return array(
			'success'              => $is_success,
			'is_user_logged_in'    => is_user_logged_in(),
			'recipe_id'            => $recipe_id,
			'_recipe'              => $recipe_object,
			'recipe_run_completed' => $recipe_run_completed,
		);
	}
}
