<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Recipe;

/**
 *
 */
class WPMU_ADD_USER_TO_BLOG {
	use Recipe\Actions;

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
		$this->set_action_code( 'WPMUADDTOBLOG' );
		$this->set_action_meta( 'WPMUSITEID' );
		$this->set_requires_user( true );
		$this->set_is_pro( true );

		/* translators: Action - WooCommerce */
		$this->set_sentence( sprintf( esc_attr__( 'Add the user to {{a subsite:%1$s}}', 'uncanny-automator-pro' ), $this->get_action_meta() ) );

		/* translators: Action - WooCommerce */
		$this->set_readable_sentence( esc_attr__( 'Add the user to {{a subsite}}', 'uncanny-automator-pro' ) );

		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->register_action();
	}

	/**
	 * load_options
	 *
	 * @return array
	 */
	public function load_options() {

		$options = array(
			'options' => array(
				$this->helper->get_site_ids(),
			),
		);

		return Automator()->utilities->keep_order_of_options( $options );
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
		// Get Subsite ID
		$subsite_id = isset( $parsed[ $this->get_action_meta() ] ) ? sanitize_text_field( wp_unslash( $parsed[ $this->get_action_meta() ] ) ) : 0;
		$validate   = get_site( $subsite_id );
		if ( 0 === $subsite_id || ! $validate instanceof \WP_Site ) {
			$action_data['complete_with_errors'] = true;
			$error_message                       = __( "Subsite doesn't exist", 'uncanny-automator-pro' );
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $error_message );

			return;
		}

		$user = get_user_by( 'ID', $user_id );
		$role = apply_filters( 'automator_pro_wpmu_add_user_to_blog_role', 'subscriber', $user, $this );

		$r = add_user_to_blog( $subsite_id, $user_id, $role );
		if ( is_wp_error( $r ) ) {

			$action_data['complete_with_errors'] = true;
			Automator()->complete->action( $user_id, $action_data, $recipe_id, $r->get_error_message() );

			return;
		}

		Automator()->complete->action( $user_id, $action_data, $recipe_id );
	}
}
