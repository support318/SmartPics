<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WM_REMOVEUSER_A
 *
 * @package Uncanny_Automator_Pro
 */
class WM_REMOVEUSER_A {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WISHLISTMEMBER';

	/**
	 * @var string
	 */
	private $action_code;
	/**
	 * @var string
	 */
	private $action_meta;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->action_code = 'WMREMOVEUSER';
		$this->action_meta = 'WMMEMBERSHIPLEVELS';
		$this->define_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 * Add the user to {a membership level}
	 */
	public function define_action() {

		$action = array(
			'author'             => Automator()->get_author_name(),
			'support_link'       => Automator()->get_author_support_link( $this->action_code, 'integration/wishlist-member/' ),
			'integration'        => self::$integration,
			'code'               => $this->action_code,
			'is_pro'             => true,
			/* translators: Action - Wishlist Member */
			'sentence'           => sprintf( esc_attr__( 'Remove the user from {{a membership level:%1$s}}', 'uncanny-automator-pro' ), $this->action_meta ),
			/* translators: Action - Wishlist Member */
			'select_option_name' => esc_attr__( 'Remove the user from {{a membership level}}', 'uncanny-automator-pro' ),
			'priority'           => 99,
			'accepted_args'      => 1,
			'execution_function' => array(
				$this,
				'remove_user_to_membership_levels',
			),
			'options_callback'   => array( $this, 'load_options' ),
		);

		Automator()->register->action( $action );
	}

	/**
	 * @return array[]
	 */
	public function load_options() {
		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->wishlist_member->options->wm_get_all_membership_levels(
						null,
						$this->action_meta,
						array(
							'include_all' => true,
						)
					),
				),
			)
		);
	}

	/**
	 * Validation function when the trigger action is hit
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 */
	public function remove_user_to_membership_levels( $user_id, $action_data, $recipe_id, $args ) {
		global $WishListMemberInstance; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

		// Get the membership levels for the user.
		$get_levels_function = method_exists( $WishListMemberInstance, 'get_membership_levels' ) ? 'get_membership_levels' : 'GetMembershipLevels'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		$level_ids           = $WishListMemberInstance->$get_levels_function( $user_id ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		// Clear out non-numeric values.
		$level_ids = is_array( $level_ids ) ? array_filter( array_map( 'intval', $level_ids ) ) : array();
		// Get the level to remove.
		$wm_level = (int) $action_data['meta'][ $this->action_meta ];

		// Check for errors.
		$error = false;
		if ( empty( $level_ids ) ) {
			$error = _x( 'The user is not in any membership level.', 'WishList Member', 'uncanny-automator-pro' );
		}
		if ( ! $error && $wm_level > 0 && ! in_array( $wm_level, $level_ids ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			$error = _x( 'The user was not a member of the specified level.', 'WishList Member', 'uncanny-automator-pro' );
		}

		if ( $error ) {
			$args['do-nothing']                  = true;
			$action_data['do-nothing']           = true;
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action( $user_id, $action_data, $recipe_id, $error, $action_data['recipe_log_id'], $args );

			return;
		}

		// Only remove the user from the specified level.
		if ( $wm_level > 0 ) {
			$level_ids = array( $wm_level );
		}

		$wlm_api = new \WLMAPIMethods( 3600 );

		// Remove the user from the level.
		foreach ( $level_ids as $level_id ) {
			$wlm_api->remove_member_from_level( $level_id, $user_id );
		}

		Automator()->complete_action( $user_id, $action_data, $recipe_id );
	}
}
