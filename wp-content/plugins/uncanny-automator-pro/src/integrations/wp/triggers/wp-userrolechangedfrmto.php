<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_USERROLECHANGEDFRMTO
 *
 * @package Uncanny_Automator_Pro
 */
class WP_USERROLECHANGEDFRMTO {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WP';

	/**
	 * @var string
	 */
	private $trigger_code;
	/**
	 * @var string
	 */
	private $trigger_meta;
	/**
	 * @var string
	 */
	private $trigger_meta_new;

	/**
	 * Set up Automator trigger constructor.
	 */
	public function __construct() {
		$this->trigger_code     = 'USERROLECHANGEDFRMTO';
		$this->trigger_meta     = 'WPROLEOLD';
		$this->trigger_meta_new = 'WPROLENEW';
		$this->define_trigger();
	}

	/**
	 * Define and register the trigger by pushing it into the Automator object
	 */
	public function define_trigger() {

		$trigger = array(
			'author'              => Automator()->get_author_name( $this->trigger_code ),
			'support_link'        => Automator()->get_author_support_link( $this->trigger_code, 'integration/wordpress-core/' ),
			'integration'         => self::$integration,
			'is_pro'              => true,
			'code'                => $this->trigger_code,
			'meta'                => $this->trigger_meta,
			/* translators: Logged-in trigger - WordPress Core */
			'sentence'            => sprintf( __( "A user's role changed from {{a specific role:%1\$s}} to {{a specific role:%2\$s}}", 'uncanny-automator-pro' ), $this->trigger_meta, $this->trigger_meta_new ),
			/* translators: Logged-in trigger - WordPress Core */
			'select_option_name'  => __( "A user's role changed from {{a specific role}} to {{a specific role}}", 'uncanny-automator-pro' ),
			'action'              => 'set_user_role',
			'priority'            => 100,
			'accepted_args'       => 3,
			'validation_function' => array( $this, 'set_user_role' ),
			'options_group'       => array(),
			'options_callback'    => array( $this, 'load_options' ),
		);

		Automator()->register->trigger( $trigger );
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options' => array(
					Automator()->helpers->recipe->wp->options->wp_user_roles( __( 'Old user role', 'uncanny-automator-pro' ), $this->trigger_meta ),
					Automator()->helpers->recipe->wp->options->wp_user_roles( __( 'New user role', 'uncanny-automator-pro' ), $this->trigger_meta_new ),
				),
			)
		);
	}

	/**
	 * @param $user_id
	 * @param $role
	 * @param $old_roles
	 */
	public function set_user_role( $user_id, $role, $old_roles ) {

		if ( empty( $old_roles ) && false === apply_filters( 'automator_pro_role_change_from_to_new_role_run_on_empty', true, $user_id, $role, $old_roles ) ) {
			return;
		}

		$recipes           = Automator()->get->recipes_from_trigger_code( $this->trigger_code );
		$required_old_role = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta );
		$required_new_role = Automator()->get->meta_from_recipes( $recipes, $this->trigger_meta_new );

		if ( ! $recipes || ! $required_old_role || ! $required_new_role ) {
			return;
		}

		$matched_recipe_trigger_roles = array();

		foreach ( $recipes as $recipe_id => $recipe ) {

			$required_recipe_old_roles = ! empty( $required_old_role[ $recipe_id ] ) ? $required_old_role[ $recipe_id ] : array();
			$required_recipe_new_roles = ! empty( $required_new_role[ $recipe_id ] ) ? $required_new_role[ $recipe_id ] : array();

			// If the recipe doesn't have the required meta, skip it.
			if ( empty( $required_recipe_old_roles ) || empty( $required_recipe_new_roles ) ) {
				continue;
			}

			// Loop through triggers and match new and old roles.
			foreach ( $recipe['triggers'] as $trigger ) {

				$trigger_id       = $trigger['ID'];
				$trigger_old_role = isset( $required_recipe_old_roles[ $trigger_id ] ) ? $required_recipe_old_roles[ $trigger_id ] : false;
				$trigger_new_role = isset( $required_recipe_new_roles[ $trigger_id ] ) ? $required_recipe_new_roles[ $trigger_id ] : false;

				// Match old role to trigger.
				$matched_old_role = false;
				// User has no old roles.
				if ( empty( $old_roles ) ) {
					$matched_old_role = true;
				} elseif ( ! empty( $old_roles ) && ! empty( $trigger_old_role ) ) {
					$matched_old_role = in_array( $trigger_old_role, $old_roles, true );
				}

				// Match new role to trigger.
				$matched_new_role = false;
				if ( ! empty( $trigger_new_role ) ) {
					$matched_new_role = (string) $role === (string) $trigger_new_role;
				}

				// Trigger doesn't match.
				if ( ! $matched_old_role || ! $matched_new_role ) {
					continue;
				}

				// Trigger matches.
				$matched_recipe_trigger_roles[] = array(
					'recipe_id'  => $recipe_id,
					'trigger_id' => $trigger_id,
					'old_role'   => $trigger_old_role,
					'new_role'   => $trigger_new_role,
				);
			}
		}

		// No recipe trigger and role combinations matched.
		if ( empty( $matched_recipe_trigger_roles ) ) {
			return;
		}

		// Loop through matched roles and add triggers.
		foreach ( $matched_recipe_trigger_roles as $matched_role ) {

			$pass_args = array(
				'code'             => $this->trigger_code,
				'meta'             => $this->trigger_meta,
				'user_id'          => $user_id,
				'recipe_to_match'  => $matched_role['recipe_id'],
				'trigger_to_match' => $matched_role['trigger_id'],
				'ignore_post_id'   => true,
			);

			$results = Automator()->maybe_add_trigger_entry( $pass_args, false );

			if ( $results ) {
				foreach ( $results as $result ) {
					if ( true === $result['result'] ) {

						$trigger_meta = array(
							'user_id'        => $user_id,
							'trigger_id'     => $result['args']['trigger_id'],
							'trigger_log_id' => $result['args']['trigger_log_id'],
							'run_number'     => $result['args']['run_number'],
						);

						// Get Role Names array.
						$roles = $this->get_role_names();

						// Populate trigger meta.

						// Existing role
						foreach ( $matched_recipe_trigger_roles as $o_role ) {
							$role_label                 = isset( $roles[ $o_role['old_role'] ] ) ? $roles[ $o_role['old_role'] ] : '';
							$trigger_meta['meta_key']   = $result['args']['trigger_id'] . ':' . $this->trigger_code . ':' . $this->trigger_meta;
							$trigger_meta['meta_value'] = maybe_serialize( $role_label );
						}
						Automator()->insert_trigger_meta( $trigger_meta );

						// New Role
						$role_label                 = isset( $roles[ $role ] ) ? $roles[ $role ] : '';
						$trigger_meta['meta_key']   = $result['args']['trigger_id'] . ':' . $this->trigger_code . ':' . $this->trigger_meta_new;
						$trigger_meta['meta_value'] = maybe_serialize( $role_label );
						Automator()->insert_trigger_meta( $trigger_meta );

						// Complete the trigger.
						Automator()->maybe_trigger_complete( $result['args'] );
					}
				}
			}
		}
	}

	/**
	 * Get role names.
	 *
	 * @return array
	 */
	public function get_role_names() {
		static $roles = null;
		if ( null === $roles ) {
			$roles = array();
			foreach ( wp_roles()->roles as $k => $role_info ) {
				$roles[ $k ] = $role_info['name'];
			}
		}
		return $roles;
	}

}
