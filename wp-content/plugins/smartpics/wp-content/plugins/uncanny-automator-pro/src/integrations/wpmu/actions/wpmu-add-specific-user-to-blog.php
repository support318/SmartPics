<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 *
 */
class WPMU_ADD_SPECIFIC_USER_TO_BLOG {
	use Recipe\Actions;

	/**
	 * @var Wpmu_Pro_Helpers
	 */
	protected $helper;

	/**
	 * Set up Automator action constructor.
	 */
	public function __construct() {
		$this->helper = new Wpmu_Pro_Helpers( false );
		$this->setup_action();
	}

	/**
	 * Define and register the action by pushing it into the Automator object
	 */
	protected function setup_action() {
		$this->set_integration( 'WPMU' );
		$this->set_action_code( 'WPMUADDSPECIFICUSERTOBLOG' );
		$this->set_action_meta( 'WPMUSITEID' );
		$this->set_requires_user( false );
		$this->set_is_pro( true );

		/* translators: Action - WooCommerce */
		$this->set_sentence( sprintf( esc_attr__( 'Add {{a specific user:%1$s}} to {{a specific subsite:%2$s}}', 'uncanny-automator-pro' ), 'WPUSERID:' . $this->get_action_meta(), 'SUBSITEID:' . $this->get_action_meta() ) );

		/* translators: Action - WooCommerce */
		$this->set_readable_sentence( esc_attr__( 'Add {{a specific user}} to {{a specific subsite}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		return Automator()->utilities->keep_order_of_options(
			array(
				'options_group' => array(
					$this->get_action_meta() => array(
						Automator()->helpers->recipe->field->text(
							array(
								'option_code' => 'WPUSERID',
								'label'       => __( 'User', 'uncanny-automator-pro' ),
								'tokens'      => true,
								'required'    => true,
								'description' => __( 'Accepts ID, email or username', 'uncanny-automator-pro' ),
							)
						),
						$this->helper->get_site_ids( null, 'SUBSITEID' ),
					),
				),
			)
		);
	}

	/**
	 * Process the action.
	 *
	 * @param $user_id
	 * @param $action_data
	 * @param $recipe_id
	 * @param $args
	 * @param $parsed
	 *
	 * @return void.
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		// Get User ID
		$user_id = isset( $parsed['WPUSERID'] ) ? sanitize_text_field( wp_strip_all_tags( $parsed['WPUSERID'] ) ) : 0;
		// Get Subsite ID
		$subsite_id = isset( $parsed['SUBSITEID'] ) ? absint( wp_strip_all_tags( $parsed['SUBSITEID'] ) ) : 0;

		$validate = get_site( $subsite_id );
		if ( 0 === $subsite_id || ! $validate instanceof \WP_Site ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = sprintf( '%s: %s', __( "Subsite doesn't exist. Subsite ID", 'uncanny-automator-pro' ), $subsite_id );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$user = $this->get_user( $user_id );
		if ( ! $user instanceof \WP_User ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = sprintf( '%s: %s', __( "User doesn't exist. User ID", 'uncanny-automator-pro' ), $user_id );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$user_id = $user->ID;
		$role    = get_option( 'default_role', 'subscriber' );
		if ( 1 === count( $user->roles ) ) {
			// IF the current user has only role, use that instead
			$role = $user->roles[0];
		}
		$role = apply_filters( 'automator_pro_wpmu_add_user_to_blog_role', $role, $user, $this );

		$r = add_user_to_blog( $subsite_id, $user_id, $role );

		if ( is_wp_error( $r ) ) {

			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $r->get_error_message() );

			return;
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}

	/**
	 * @param $user_id
	 *
	 * @return false|\WP_User
	 */
	public function get_user( $user_id ) {
		if ( is_numeric( $user_id ) ) {
			return get_user_by( 'ID', $user_id );
		}
		if ( is_email( $user_id ) ) {
			$maybe_user_id = email_exists( $user_id );
			if ( is_numeric( $maybe_user_id ) ) {
				return get_user_by( 'ID', $maybe_user_id );
			}
		}
		$maybe_user_id = username_exists( $user_id );
		if ( is_numeric( $maybe_user_id ) ) {
			return get_user_by( 'ID', $maybe_user_id );
		}

		return new \WP_User( $user_id );
	}
}
