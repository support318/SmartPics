<?php

namespace Uncanny_Automator_Pro;

/**
 * Class BDB_SETUSERPROFILETYPE
 *
 * @package Uncanny_Automator_Pro
 */
class BDB_SETUSERPROFILETYPE {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'BDB';

	private $action_code = 'BDBSETUSERPROFILETYPE';

	private $action_meta = 'BDBPROFILETYPE';


	public function __construct() {

		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object.
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/buddyboss/' ),
			'is_pro'             => true,
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			/* translators: Action - BuddyBoss */
			'sentence'           => sprintf( __( "Set the user's profile type to {{a specific type:%1\$s}}", 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - BuddyBoss */
			'select_option_name' => __( "Set the user's profile type to {{a specific type}}", 'uncanny-automator-pro' ),
			'priority'           => 10,
			'accepted_args'      => 1,
			'execution_function' => array( $this, 'update_user_profile_type' ),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * Load the options available for this action.
	 *
	 * @return array The formatted option.
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->buddyboss->pro->get_profile_types( null, $this->action_meta ),
				),
			)
		);

	}

	/**
	 * Update user profile type
	 *
	 * @param string $user_id
	 * @param array $action_data
	 * @param string $recipe_id
	 *
	 * @since 1.1
	 * @return void
	 */
	public function update_user_profile_type( $user_id, $action_data, $recipe_id, $args ) {

		$post_id = $action_data['meta'][ $this->action_meta ];

		if ( empty( $post_id ) ) {

			$action_data['complete_with_errors'] = true;

			return Automator()->complete->action( $user_id, $action_data, $recipe_id, esc_html__( 'Empty profile type selected.', 'uncanny-automator-pro' ) );

		}

		// Get post id of selected profile type.
		$member_type = get_post_meta( $post_id, '_bp_member_type_key', true );

		if ( empty( $member_type ) ) {

			$type_post = get_post( $post_id );

			$member_type = $type_post->post_name;

		}

		if ( false === bp_set_member_type( $user_id, $member_type ) ) {

			$action_data['complete_with_errors'] = true;

			return Automator()->complete->action( $user_id, $action_data, $recipe_id, esc_html__( 'An error has occured while setting profile type.', 'uncanny-automator-pro' ) );

		}

		return Automator()->complete->action( $user_id, $action_data, $recipe_id );

	}

}
